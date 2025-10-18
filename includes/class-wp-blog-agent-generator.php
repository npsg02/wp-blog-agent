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
        
        // Process inline images if enabled
        $auto_generate_inline_images = get_option('wp_blog_agent_auto_generate_inline_images', 'no');
        if ($auto_generate_inline_images === 'yes') {
            WP_Blog_Agent_Logger::info('Processing inline image placeholders');
            $parsed['content'] = $this->process_image_placeholders($parsed['content'], $topic->topic);
        }
        
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
        
        // Auto-generate featured image if enabled
        $auto_generate_image = get_option('wp_blog_agent_auto_generate_image', 'no');
        if ($auto_generate_image === 'yes') {
            $this->generate_featured_image($post_id, $parsed['title'], $topic->topic);
        }
        
        // Auto-generate RankMath SEO meta if enabled
        $auto_generate_seo = get_option('wp_blog_agent_auto_generate_seo', 'no');
        if ($auto_generate_seo === 'yes') {
            $this->generate_rankmath_seo($post_id);
        }
        
        return $post_id;
    }
    
    /**
     * Parse content to extract title and body
     */
    public function parse_content($content) {
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
    public function generate_excerpt($content) {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        if (strlen($text) > 150) {
            $text = substr($text, 0, 147) . '...';
        }
        return $text;
    }
    
    /**
     * Generate and set featured image for a post
     */
    private function generate_featured_image($post_id, $title, $topic) {
        try {
            // Create image prompt based on post title and topic
            $prompt = sprintf(
                'Create a professional, eye-catching blog header image for a blog post titled "%s" about %s. The image should be visually appealing, modern, and relevant to the topic.',
                $title,
                $topic
            );
            
            WP_Blog_Agent_Logger::info('Auto-generating featured image', array(
                'post_id' => $post_id,
                'prompt' => substr($prompt, 0, 100)
            ));
            
            // Initialize image generator
            $image_generator = new WP_Blog_Agent_Image_Generator();
            
            // Set parameters for featured image
            $params = array(
                'aspectRatio' => '16:9', // Best for blog headers
                'imageSize' => '1K',
                'sampleCount' => 1,
                'outputMimeType' => 'image/jpeg',
                'personGeneration' => 'ALLOW_ALL'
            );
            
            // Generate and save image
            $attachment_id = $image_generator->generate_and_save($prompt, $post_id, $params);
            
            if (is_wp_error($attachment_id)) {
                WP_Blog_Agent_Logger::error('Failed to auto-generate featured image', array(
                    'post_id' => $post_id,
                    'error' => $attachment_id->get_error_message()
                ));
                return false;
            }
            
            // Store metadata
            update_post_meta($attachment_id, '_wp_blog_agent_generated_image', true);
            update_post_meta($attachment_id, '_wp_blog_agent_image_prompt', $prompt);
            update_post_meta($attachment_id, '_wp_blog_agent_attached_post', $post_id);
            update_post_meta($attachment_id, '_wp_blog_agent_auto_generated', true);
            
            WP_Blog_Agent_Logger::success('Featured image auto-generated successfully', array(
                'post_id' => $post_id,
                'attachment_id' => $attachment_id
            ));
            
            return $attachment_id;
        } catch (Exception $e) {
            WP_Blog_Agent_Logger::error('Exception during auto-image generation', array(
                'post_id' => $post_id,
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Generate RankMath SEO meta for a post
     */
    private function generate_rankmath_seo($post_id) {
        try {
            WP_Blog_Agent_Logger::info('Auto-generating RankMath SEO meta', array(
                'post_id' => $post_id
            ));
            
            $rankmath = new WP_Blog_Agent_RankMath();
            $results = $rankmath->generate_all_seo_meta($post_id);
            
            WP_Blog_Agent_Logger::success('RankMath SEO meta generated', array(
                'post_id' => $post_id,
                'description' => $results['description'],
                'keyword' => $results['keyword']
            ));
            
            return true;
        } catch (Exception $e) {
            WP_Blog_Agent_Logger::error('Exception during RankMath SEO generation', array(
                'post_id' => $post_id,
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Process image placeholders in content and replace with generated images
     */
    public function process_image_placeholders($content, $topic) {
        // Find all image placeholders in the format [IMAGE: description]
        preg_match_all('/\[IMAGE:\s*([^\]]+)\]/i', $content, $matches);
        
        if (empty($matches[0])) {
            WP_Blog_Agent_Logger::info('No image placeholders found in content');
            return $content;
        }
        
        $placeholders = $matches[0]; // Full placeholder text
        $descriptions = $matches[1]; // Image descriptions
        
        WP_Blog_Agent_Logger::info('Found image placeholders', array(
            'count' => count($placeholders),
            'descriptions' => $descriptions
        ));
        
        $image_generator = new WP_Blog_Agent_Image_Generator();
        
        // Process each placeholder
        foreach ($placeholders as $index => $placeholder) {
            $description = trim($descriptions[$index]);
            
            // Create enhanced prompt for image generation
            $image_prompt = sprintf(
                'Create a high-quality, professional image for a blog post about "%s". The image should show: %s. Make it visually appealing, modern, and relevant.',
                $topic,
                $description
            );
            
            WP_Blog_Agent_Logger::info('Generating inline image', array(
                'index' => $index + 1,
                'description' => $description,
                'prompt' => substr($image_prompt, 0, 100)
            ));
            
            // Set parameters for inline images
            $params = array(
                'aspectRatio' => '16:9',
                'imageSize' => '1K',
                'sampleCount' => 1,
                'outputMimeType' => 'image/jpeg',
                'personGeneration' => 'ALLOW_ALL'
            );
            
            // Generate image
            $images = $image_generator->generate_image($image_prompt, $params);
            
            if (is_wp_error($images)) {
                WP_Blog_Agent_Logger::error('Failed to generate inline image', array(
                    'index' => $index + 1,
                    'error' => $images->get_error_message()
                ));
                // Replace with a comment indicating failure
                $replacement = sprintf(
                    '<!-- Image placeholder: %s (generation failed) -->',
                    esc_html($description)
                );
                $content = str_replace($placeholder, $replacement, $content);
                continue;
            }
            
            // Upload the first generated image
            $image = $images[0];
            $filename = sanitize_title(substr($description, 0, 50));
            if (empty($filename)) {
                $filename = 'inline-image-' . ($index + 1);
            }
            
            $attachment_id = $image_generator->upload_to_media_library(
                $image['base64'],
                $filename,
                $image['mime_type'],
                0 // Not attached to post yet
            );
            
            if (is_wp_error($attachment_id)) {
                WP_Blog_Agent_Logger::error('Failed to upload inline image', array(
                    'index' => $index + 1,
                    'error' => $attachment_id->get_error_message()
                ));
                // Replace with a comment indicating failure
                $replacement = sprintf(
                    '<!-- Image placeholder: %s (upload failed) -->',
                    esc_html($description)
                );
                $content = str_replace($placeholder, $replacement, $content);
                continue;
            }
            
            // Get image URL
            $image_url = wp_get_attachment_url($attachment_id);
            
            // Store metadata
            update_post_meta($attachment_id, '_wp_blog_agent_generated_image', true);
            update_post_meta($attachment_id, '_wp_blog_agent_image_prompt', $image_prompt);
            update_post_meta($attachment_id, '_wp_blog_agent_inline_image', true);
            
            // Create HTML image tag
            $image_html = sprintf(
                '<figure class="wp-block-image"><img src="%s" alt="%s" class="wp-blog-agent-inline-image" /><figcaption>%s</figcaption></figure>',
                esc_url($image_url),
                esc_attr($description),
                esc_html($description)
            );
            
            // Replace placeholder with actual image
            $content = str_replace($placeholder, $image_html, $content);
            
            WP_Blog_Agent_Logger::success('Inline image generated and inserted', array(
                'index' => $index + 1,
                'attachment_id' => $attachment_id,
                'url' => $image_url
            ));
        }
        
        return $content;
    }
}
