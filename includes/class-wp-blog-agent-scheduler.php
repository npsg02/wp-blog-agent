<?php
/**
 * Scheduler for automated post generation
 */
class WP_Blog_Agent_Scheduler {
    
    public function __construct() {
        // Hook into WordPress cron
        add_action('wp_blog_agent_generate_post', array($this, 'scheduled_generation'));
        
        // Add custom cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules($schedules) {
        $schedules['hourly'] = array(
            'interval' => 3600,
            'display'  => __('Every Hour', 'wp-blog-agent')
        );
        $schedules['twicedaily'] = array(
            'interval' => 43200,
            'display'  => __('Twice Daily', 'wp-blog-agent')
        );
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display'  => __('Once Weekly', 'wp-blog-agent')
        );
        return $schedules;
    }
    
    /**
     * Generate post on schedule
     */
    public function scheduled_generation() {
        // Check if scheduling is enabled
        $enabled = get_option('wp_blog_agent_schedule_enabled', 'no');
        
        if ($enabled !== 'yes') {
            return;
        }
        
        // Generate a post
        $generator = new WP_Blog_Agent_Generator();
        $result = $generator->generate_post();
        
        // Log the result
        if (is_wp_error($result)) {
            error_log('WP Blog Agent: Failed to generate post - ' . $result->get_error_message());
        } else {
            error_log('WP Blog Agent: Successfully generated post ID: ' . $result);
        }
    }
    
    /**
     * Update schedule frequency
     */
    public static function update_schedule($frequency) {
        // Clear existing schedule
        $timestamp = wp_next_scheduled('wp_blog_agent_generate_post');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wp_blog_agent_generate_post');
        }
        
        // Schedule new event
        if ($frequency && $frequency !== 'none') {
            wp_schedule_event(time(), $frequency, 'wp_blog_agent_generate_post');
        }
    }
}
