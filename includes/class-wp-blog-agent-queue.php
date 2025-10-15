<?php
class WP_Blog_Agent_Queue {

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

    // các hàm khác giữ nguyên
}
