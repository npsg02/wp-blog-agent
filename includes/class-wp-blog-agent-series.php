<?php
/**
 * Series Management Class
 * Handles post series creation, management, and AI topic suggestions
 */
class WP_Blog_Agent_Series {
    
    /**
     * Get all series
     */
    public static function get_all_series() {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series';
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
    
    /**
     * Get series by ID
     */
    public static function get_series($series_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $series_id));
    }
    
    /**
     * Create new series
     */
    public static function create_series($name, $description = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series';
        
        $result = $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'status' => 'active'
            ),
            array('%s', '%s', '%s')
        );
        
        if ($result === false) {
            WP_Blog_Agent_Logger::error('Failed to create series: ' . $wpdb->last_error);
            return false;
        }
        
        WP_Blog_Agent_Logger::info('Series created: ' . $name);
        return $wpdb->insert_id;
    }
    
    /**
     * Update series
     */
    public static function update_series($series_id, $name, $description = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series';
        
        $result = $wpdb->update(
            $table,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description)
            ),
            array('id' => $series_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            WP_Blog_Agent_Logger::error('Failed to update series: ' . $wpdb->last_error);
            return false;
        }
        
        WP_Blog_Agent_Logger::info('Series updated: ' . $series_id);
        return true;
    }
    
    /**
     * Delete series
     */
    public static function delete_series($series_id) {
        global $wpdb;
        $series_table = $wpdb->prefix . 'blog_agent_series';
        $posts_table = $wpdb->prefix . 'blog_agent_series_posts';
        
        // Delete all relationships first
        $wpdb->delete($posts_table, array('series_id' => $series_id), array('%d'));
        
        // Delete the series
        $result = $wpdb->delete($series_table, array('id' => $series_id), array('%d'));
        
        if ($result === false) {
            WP_Blog_Agent_Logger::error('Failed to delete series: ' . $wpdb->last_error);
            return false;
        }
        
        WP_Blog_Agent_Logger::info('Series deleted: ' . $series_id);
        return true;
    }
    
    /**
     * Add post to series
     */
    public static function add_post_to_series($series_id, $post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series_posts';
        
        // Get the current max position
        $max_position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) FROM $table WHERE series_id = %d",
            $series_id
        ));
        
        $position = ($max_position === null) ? 1 : ($max_position + 1);
        
        $result = $wpdb->insert(
            $table,
            array(
                'series_id' => $series_id,
                'post_id' => $post_id,
                'position' => $position
            ),
            array('%d', '%d', '%d')
        );
        
        if ($result === false) {
            WP_Blog_Agent_Logger::error('Failed to add post to series: ' . $wpdb->last_error);
            return false;
        }
        
        // Add series metadata to the post
        update_post_meta($post_id, '_wp_blog_agent_series_id', $series_id);
        
        WP_Blog_Agent_Logger::info('Post added to series: post_id=' . $post_id . ', series_id=' . $series_id);
        return true;
    }
    
    /**
     * Remove post from series
     */
    public static function remove_post_from_series($series_id, $post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series_posts';
        
        $result = $wpdb->delete(
            $table,
            array('series_id' => $series_id, 'post_id' => $post_id),
            array('%d', '%d')
        );
        
        if ($result === false) {
            WP_Blog_Agent_Logger::error('Failed to remove post from series: ' . $wpdb->last_error);
            return false;
        }
        
        delete_post_meta($post_id, '_wp_blog_agent_series_id');
        
        WP_Blog_Agent_Logger::info('Post removed from series: post_id=' . $post_id . ', series_id=' . $series_id);
        return true;
    }
    
    /**
     * Get all posts in a series
     */
    public static function get_series_posts($series_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series_posts';
        
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM $table WHERE series_id = %d ORDER BY position ASC",
            $series_id
        ));
        
        if (empty($post_ids)) {
            return array();
        }
        
        $args = array(
            'post__in' => $post_ids,
            'post_type' => 'post',
            'posts_per_page' => -1,
            'orderby' => 'post__in',
            'post_status' => array('publish', 'draft', 'pending')
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Get series statistics
     */
    public static function get_series_stats($series_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'blog_agent_series_posts';
        
        $total_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE series_id = %d",
            $series_id
        ));
        
        return array(
            'total_posts' => (int)$total_posts
        );
    }
    
    /**
     * Generate AI topic suggestions based on existing posts in series
     */
    public static function generate_topic_suggestions($series_id, $num_suggestions = 5) {
        // Get all posts in the series
        $posts = self::get_series_posts($series_id);
        
        if (empty($posts)) {
            return new WP_Error('no_posts', __('This series has no posts yet. Add at least one post to generate suggestions.', 'wp-blog-agent'));
        }
        
        // Get series info
        $series = self::get_series($series_id);
        if (!$series) {
            return new WP_Error('invalid_series', __('Invalid series ID', 'wp-blog-agent'));
        }
        
        // Extract post titles
        $titles = array_map(function($post) {
            return $post->post_title;
        }, $posts);
        
        // Build prompt for AI
        $titles_list = implode("\n- ", $titles);
        $prompt = sprintf(
            "Based on this series titled '%s' with the following blog post titles:\n\n- %s\n\nSuggest %d relevant topics for the next blog posts in this series. Each topic should:\n1. Follow the theme and pattern of existing posts\n2. Add new valuable information to the series\n3. Be specific and actionable\n4. Be different from existing topics\n\nProvide only the topic titles, one per line, without numbering or additional explanation.",
            $series->name,
            $titles_list,
            $num_suggestions
        );
        
        // Get AI provider
        $provider = get_option('wp_blog_agent_ai_provider', 'openai');
        
        try {
            $response = null;
            
            if ($provider === 'openai') {
                $openai = new WP_Blog_Agent_OpenAI();
                $response = $openai->generate_topic_suggestions($prompt);
            } elseif ($provider === 'gemini') {
                $gemini = new WP_Blog_Agent_Gemini();
                $response = $gemini->generate_topic_suggestions($prompt);
            } else {
                return new WP_Error('invalid_provider', __('Invalid AI provider', 'wp-blog-agent'));
            }
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            // Parse the response - split by newlines and clean up
            $suggestions = array_filter(array_map('trim', explode("\n", $response)));
            
            // Remove any numbering or bullet points
            $suggestions = array_map(function($suggestion) {
                return preg_replace('/^[\d\.\-\*\#\s]+/', '', $suggestion);
            }, $suggestions);
            
            WP_Blog_Agent_Logger::info('Generated ' . count($suggestions) . ' topic suggestions for series: ' . $series_id);
            
            return array_values(array_filter($suggestions));
            
        } catch (Exception $e) {
            WP_Blog_Agent_Logger::error('Failed to generate topic suggestions: ' . $e->getMessage());
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }
}
