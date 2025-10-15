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
    
    private static function ensure_table_exists() {
        global $wpdb;

        $table_name = self::get_table_name();

        // Kiểm tra tồn tại bảng an toàn
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
        if ($exists === $table_name) {
            return true;
        }

        // Tạo bảng
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            topic_id bigint(20) unsigned DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            trigger_source varchar(50) NOT NULL DEFAULT 'manual',
            post_id bigint(20) unsigned DEFAULT NULL,
            attempts int(11) NOT NULL DEFAULT 0,
            error_message longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            started_at datetime NULL DEFAULT NULL,
            completed_at datetime NULL DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Xác minh tạo thành công
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
        if ($exists !== $table_name) {
            WP_Blog_Agent_Logger::error('Failed to create queue table', [
                'table_name' => $table_name,
                'error' => $wpdb->last_error
            ]);
            return false;
        }

        WP_Blog_Agent_Logger::info('Queue table created successfully', ['table_name' => $table_name]);
        return true;
    }
    
   public static function enqueue($topic_id = null, $trigger = 'manual') {
        global $wpdb;

        if (!self::ensure_table_exists()) {
            WP_Blog_Agent_Logger::error('Cannot enqueue task - table does not exist and could not be created', [
                'topic_id' => $topic_id,
                'trigger' => $trigger
            ]);
            return false;
        }

        $table_name = self::get_table_name();

        $result = $wpdb->insert(
            $table_name,
            [
                'topic_id' => $topic_id,
                'status' => 'pending',
                'trigger_source' => $trigger,
                'created_at' => current_time('mysql'),
                'attempts' => 0
            ],
            ['%d', '%s', '%s', '%s', '%d']
        );

        if ($result === false) {
            WP_Blog_Agent_Logger::error('Failed to enqueue generation task', [
                'topic_id' => $topic_id,
                'trigger' => $trigger,
                'error' => $wpdb->last_error
            ]);
            return false;
        }

        $queue_id = $wpdb->insert_id;

        WP_Blog_Agent_Logger::info('Generation task enqueued', [
            'queue_id' => $queue_id,
            'topic_id' => $topic_id,
            'trigger' => $trigger
        ]);

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
        
        // Ensure table exists
        if (!self::ensure_table_exists()) {
            return null;
        }
        
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
    
    public static function process_queue() {
        WP_Blog_Agent_Logger::info('Processing generation queue');

        $task = self::get_next_task();
        if (!$task) {
            WP_Blog_Agent_Logger::info('No pending tasks in queue');
            return;
        }

        WP_Blog_Agent_Logger::info('Processing task', [
            'queue_id' => $task->id,
            'topic_id' => $task->topic_id,
            'trigger' => $task->trigger_source ?? 'manual'
        ]);

        self::mark_processing($task->id);

        $generator = new WP_Blog_Agent_Generator();
        $result = $generator->generate_post($task->topic_id);

        if (is_wp_error($result)) {
            self::mark_failed($task->id, $result->get_error_message());
        } else {
            self::mark_completed($task->id, $result);
        }

        $next_task = self::get_next_task();
        if ($next_task && !wp_next_scheduled('wp_blog_agent_process_queue')) {
            wp_schedule_single_event(time() + 10, 'wp_blog_agent_process_queue');
        }
    }
    
    /**
     * Get queue statistics
     * 
     * @return array Queue statistics
     */
    public static function get_stats() {
        global $wpdb;
        
        // Ensure table exists
        if (!self::ensure_table_exists()) {
            return array(
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0
            );
        }
        
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
        
        // Ensure table exists
        if (!self::ensure_table_exists()) {
            return array();
        }
        
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