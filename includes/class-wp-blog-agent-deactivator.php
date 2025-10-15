<?php
/**
 * Plugin Deactivator
 */
class WP_Blog_Agent_Deactivator {
    
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear scheduled events
        $timestamp = wp_next_scheduled('wp_blog_agent_generate_post');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wp_blog_agent_generate_post');
        }
    }
}
