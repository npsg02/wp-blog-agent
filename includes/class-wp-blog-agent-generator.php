<?php
/**
 * Blog Post Generator
 */
class WP_Blog_Agent_Generator {
    
    /**
     * Generate and publish a blog post
     */
    public function generate_post($topic_id = null) {
        global $wpdb;
        
        WP_Blog_Agent_Logger::info('Starting post generation', array('topic_id' => $topic_id));
        
        // Get a random active topic if not specified
        if ($topic_id === null) {
            $table_name = $wpdb->prefix . 'blog_agent_topics';
            $topic = $wpdb->get_row(
                "SELECT * FROM $table_name WHERE status = 'active' ORDER BY RAND() LIMIT 1"
            );
        } else {
            $table_name = $wpdb->prefix . 'blog_agent_topics';
            $topic = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $topic_id
            ));
        }
        
        if (!$topic) {
            WP_Blog_Agent_Logger::error('No active topic found');
            return new WP_Error('no_topic', 'No active topic found.');
        }
        
        WP_Blog_Agent_Logger::info('Selected topic', array('topic' => $topic->topic, 'id' => $topic->id));
        
        // Parse keywords and hashtags
        $keywords = array_filter(array_map('trim', explode(',', $topic->keywords)));
        $hashtags = array_filter(array_map('trim', explode(',', $topic->hashtags)));
        
        // Add # prefix to hashtags if not present
        $hashtags = array_map(function($tag) {
            return strpos($tag, '#') === 0 ? $tag : '#' . $tag;
        }, $hashtags);
        
        // Get AI provider
        $provider = get_option('wp_blog_agent_ai_provider', 'openai');
        
        // Generate content
        if ($provider === 'gemini') {
            $ai = new WP_Blog_Agent_Gemini();
        } elseif ($provider === 'ollama') {
            $ai = new WP_Blog_Agent_Ollama();
        } else {
            $ai = new WP_Blog_Agent_OpenAI();
        }
        
        $content = $ai->generate_content($topic->topic, $keywords, $hashtags);
        
        if (is_wp_error($content)) {
            WP_Blog_Agent_Logger::error('Content generation failed', array(
                'error' => $content->get_error_message(),
                'provider' => $provider,
                'topic_id' => $topic->id
            ));
            return $content;
        }
        
        WP_Blog_Agent_Logger::info('Content generated successfully', array('provider' => $provider));
        
        // Extract title and content
        $parsed = $this->parse_content($content);
        
        // Determine post status
        $auto_publish = get_option('wp_blog_agent_auto_publish', 'yes');
        $post_status = ($auto_publish === 'yes') ? 'publish' : 'draft';
        
        // Create the post
        $post_data = array(
            'post_title'   => $parsed['title'],
            'post_content' => $parsed['content'],
            'post_status'  => $post_status,
            'post_author'  => 1,
            'post_type'    => 'post',
            'post_excerpt' => $this->generate_excerpt($parsed['content']),
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            WP_Blog_Agent_Logger::error('Post creation failed', array('error' => $post_id->get_error_message()));
            return $post_id;
        }
        
        WP_Blog_Agent_Logger::success('Post created successfully', array(
            'post_id' => $post_id,
            'title' => $parsed['title'],
            'status' => $post_status
        ));
        
        // Add metadata
        update_post_meta($post_id, '_wp_blog_agent_generated', true);
        update_post_meta($post_id, '_wp_blog_agent_topic_id', $topic->id);
        update_post_meta($post_id, '_wp_blog_agent_keywords', implode(', ', $keywords));
        update_post_meta($post_id, '_wp_blog_agent_hashtags', implode(' ', $hashtags));
        update_post_meta($post_id, '_wp_blog_agent_provider', $provider);
        
        return $post_id;
    }
    
    /**
     * Parse content to extract title and body
     */
    private function parse_content($content) {
        // Try to extract title from h1 tag
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $matches)) {
            $title = strip_tags($matches[1]);
            $content = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $content, 1);
        } else {
            // Try to get first line as title
            $lines = explode("\n", strip_tags($content));
            $title = trim($lines[0]);
            if (strlen($title) > 100) {
                $title = substr($title, 0, 97) . '...';
            }
        }
        
        return array(
            'title' => $title ?: 'Untitled Post',
            'content' => trim($content)
        );
    }
    
    /**
     * Generate excerpt from content
     */
    private function generate_excerpt($content) {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        if (strlen($text) > 150) {
            $text = substr($text, 0, 147) . '...';
        }
        return $text;
    }
}
