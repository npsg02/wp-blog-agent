<?php
/**
 * Queue Manager for Post Generation Tasks
 */
class WP_Blog_Agent_Queue {
    
    /**
     * Get queue table name
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'blog_agent_queue';
    }
    
    /**
     * Add a generation task to the queue
     * 
     * @param int|null $topic_id Topic ID to generate from (null for random)
     * @param string $trigger Source of generation (manual, scheduled, etc)
     * @return int|false Queue item ID on success, false on failure
     */
    public static function enqueue($topic_id = null, $trigger = 'manual') {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'topic_id' => $topic_id,
                'status' => 'pending',
                'trigger' => $trigger,
                'created_at' => current_time('mysql'),
                'attempts' => 0
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            WP_Blog_Agent_Logger::error('Failed to enqueue generation task', array(
                'topic_id' => $topic_id,
                'trigger' => $trigger,
                'error' => $wpdb->last_error
            ));
            return false;
        }
        
        $queue_id = $wpdb->insert_id;
        
        WP_Blog_Agent_Logger::info('Generation task enqueued', array(
            'queue_id' => $queue_id,
            'topic_id' => $topic_id,
            'trigger' => $trigger
        ));
        
        // Schedule immediate processing
        if (!wp_next_scheduled('wp_blog_agent_process_queue')) {
            wp_schedule_single_event(time(), 'wp_blog_agent_process_queue');
        }
        
        return $queue_id;
    }
    
    /**
     * Get next pending task from queue
     * 
     * @return object|null Queue item object or null if no pending tasks
     */
    public static function get_next_task() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $task = $wpdb->get_row(
            "SELECT * FROM $table_name 
            WHERE status = 'pending' 
            AND attempts < 3
            ORDER BY created_at ASC 
            LIMIT 1"
        );
        
        return $task;
    }
    
    /**
     * Mark task as processing
     * 
     * @param int $queue_id Queue item ID
     * @return bool Success status
     */
    public static function mark_processing($queue_id) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'processing',
                'started_at' => current_time('mysql')
            ),
            array('id' => $queue_id),
            array('%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Mark task as completed
     * 
     * @param int $queue_id Queue item ID
     * @param int $post_id Generated post ID
     * @return bool Success status
     */
    public static function mark_completed($queue_id, $post_id) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'completed',
                'post_id' => $post_id,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $queue_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        WP_Blog_Agent_Logger::success('Generation task completed', array(
            'queue_id' => $queue_id,
            'post_id' => $post_id
        ));
        
        return $result !== false;
    }
    
    /**
     * Mark task as failed
     * 
     * @param int $queue_id Queue item ID
     * @param string $error_message Error message
     * @return bool Success status
     */
    public static function mark_failed($queue_id, $error_message) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        // Get current attempts
        $task = $wpdb->get_row($wpdb->prepare(
            "SELECT attempts FROM $table_name WHERE id = %d",
            $queue_id
        ));
        
        if (!$task) {
            return false;
        }
        
        $attempts = intval($task->attempts) + 1;
        $status = ($attempts >= 3) ? 'failed' : 'pending';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'attempts' => $attempts,
                'error_message' => $error_message,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $queue_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        WP_Blog_Agent_Logger::error('Generation task failed', array(
            'queue_id' => $queue_id,
            'attempts' => $attempts,
            'error' => $error_message
        ));
        
        // Schedule retry if not max attempts reached
        if ($attempts < 3 && !wp_next_scheduled('wp_blog_agent_process_queue')) {
            wp_schedule_single_event(time() + 300, 'wp_blog_agent_process_queue'); // Retry in 5 minutes
        }
        
        return $result !== false;
    }
    
    /**
     * Process queue tasks
     */
    public static function process_queue() {
        WP_Blog_Agent_Logger::info('Processing generation queue');
        
        $task = self::get_next_task();
        
        if (!$task) {
            WP_Blog_Agent_Logger::info('No pending tasks in queue');
            return;
        }
        
        WP_Blog_Agent_Logger::info('Processing task', array(
            'queue_id' => $task->id,
            'topic_id' => $task->topic_id,
            'trigger' => $task->trigger
        ));
        
        // Mark as processing
        self::mark_processing($task->id);
        
        // Generate the post
        $generator = new WP_Blog_Agent_Generator();
        $result = $generator->generate_post($task->topic_id);
        
        // Handle result
        if (is_wp_error($result)) {
            self::mark_failed($task->id, $result->get_error_message());
        } else {
            self::mark_completed($task->id, $result);
        }
        
        // Check if there are more tasks
        $next_task = self::get_next_task();
        if ($next_task && !wp_next_scheduled('wp_blog_agent_process_queue')) {
            wp_schedule_single_event(time() + 10, 'wp_blog_agent_process_queue'); // Process next task in 10 seconds
        }
    }
    
    /**
     * Get queue statistics
     * 
     * @return array Queue statistics
     */
    public static function get_stats() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $stats = array(
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'total' => 0
        );
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $table_name GROUP BY status"
        );
        
        if ($results) {
            foreach ($results as $row) {
                $stats[$row->status] = intval($row->count);
                $stats['total'] += intval($row->count);
            }
        }
        
        return $stats;
    }
    
    /**
     * Get recent queue items
     * 
     * @param int $limit Number of items to retrieve
     * @return array Queue items
     */
    public static function get_recent($limit = 10) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, t.topic 
            FROM $table_name q 
            LEFT JOIN {$wpdb->prefix}blog_agent_topics t ON q.topic_id = t.id 
            ORDER BY q.created_at DESC 
            LIMIT %d",
            $limit
        ));
        
        return $items;
    }
    
    /**
     * Clear completed and failed tasks older than specified days
     * 
     * @param int $days Number of days to keep
     * @return int Number of items deleted
     */
    public static function cleanup($days = 7) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name 
            WHERE status IN ('completed', 'failed') 
            AND completed_at < %s",
            $date_limit
        ));
        
        if ($deleted > 0) {
            WP_Blog_Agent_Logger::info('Queue cleanup completed', array(
                'items_deleted' => $deleted,
                'older_than' => $days . ' days'
            ));
        }
        
        return $deleted;
    }
}
