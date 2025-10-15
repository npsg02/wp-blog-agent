<?php
/**
 * Plugin Activator
 */
class WP_Blog_Agent_Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'blog_agent_topics';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            topic varchar(255) NOT NULL,
            keywords text NOT NULL,
            hashtags text NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default options
        if (!get_option('wp_blog_agent_ai_provider')) {
            add_option('wp_blog_agent_ai_provider', 'openai');
        }
        if (!get_option('wp_blog_agent_schedule_enabled')) {
            add_option('wp_blog_agent_schedule_enabled', 'no');
        }
        if (!get_option('wp_blog_agent_schedule_frequency')) {
            add_option('wp_blog_agent_schedule_frequency', 'daily');
        }
        if (!get_option('wp_blog_agent_auto_publish')) {
            add_option('wp_blog_agent_auto_publish', 'yes');
        }
        
        // Schedule cron event if not already scheduled
        if (!wp_next_scheduled('wp_blog_agent_generate_post')) {
            wp_schedule_event(time(), 'daily', 'wp_blog_agent_generate_post');
        }
    }
}
