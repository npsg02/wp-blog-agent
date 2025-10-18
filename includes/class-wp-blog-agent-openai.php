<?php
/**
 * OpenAI API Integration
 */
class WP_Blog_Agent_OpenAI {
    
    private $api_key;
    private $api_url;
    private $model;
    private $max_tokens;
    private $system_prompt;
    
    public function __construct() {
        $this->api_key = get_option('wp_blog_agent_openai_api_key', '');
        $this->api_url = get_option('wp_blog_agent_openai_base_url', 'https://api.openai.com/v1/chat/completions');
        $this->model = get_option('wp_blog_agent_openai_model', 'gpt-3.5-turbo');
        $this->max_tokens = get_option('wp_blog_agent_openai_max_tokens', '');
        $this->system_prompt = get_option('wp_blog_agent_openai_system_prompt', 'You are a professional blog writer who creates SEO-optimized, engaging content.');
    }
    
    /**
     * Generate blog post using OpenAI
     */
    public function generate_content($topic, $keywords = array(), $hashtags = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key is not configured.');
        }
        
        $prompt = $this->build_prompt($topic, $keywords, $hashtags);
        
        $request_body = array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $this->system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
        );
        
        // Add max_tokens only if it's set (unlimited by default)
        if (!empty($this->max_tokens)) {
            $request_body['max_tokens'] = intval($this->max_tokens);
        }
        
        // Log request
        WP_Blog_Agent_Logger::info('OpenAI API Request', array(
            'url' => $this->api_url,
            'model' => $this->model,
            'prompt_length' => strlen($prompt)
        ));
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body' => json_encode($request_body),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            WP_Blog_Agent_Logger::error('OpenAI API Error', array(
                'error' => $response->get_error_message()
            ));
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log response
        WP_Blog_Agent_Logger::info('OpenAI API Response', array(
            'status_code' => $status_code,
            'has_choices' => isset($body['choices']),
            'choices_count' => isset($body['choices']) ? count($body['choices']) : 0
        ));
        
        // Handle HTTP errors
        if ($status_code !== 200) {
            $error_message = 'OpenAI API returned status code ' . $status_code;
            if (isset($body['error']['message'])) {
                $error_message .= ': ' . $body['error']['message'];
            } elseif (isset($body['error'])) {
                $error_message .= ': ' . (is_string($body['error']) ? $body['error'] : json_encode($body['error']));
            }
            WP_Blog_Agent_Logger::error('OpenAI API HTTP Error', array(
                'status_code' => $status_code,
                'body' => $body
            ));
            return new WP_Error('openai_http_error', $error_message);
        }
        
        // Handle API errors
        if (isset($body['error'])) {
            $error_message = is_array($body['error']) && isset($body['error']['message']) 
                ? $body['error']['message'] 
                : (is_string($body['error']) ? $body['error'] : 'Unknown error');
            WP_Blog_Agent_Logger::error('OpenAI API Error Response', array('error' => $body['error']));
            return new WP_Error('openai_error', $error_message);
        }
        
        // Validate response structure
        if (!is_array($body)) {
            WP_Blog_Agent_Logger::error('OpenAI API Invalid Response Format', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response format from OpenAI API: Response is not an array.');
        }
        
        if (!isset($body['choices']) || !is_array($body['choices']) || empty($body['choices'])) {
            WP_Blog_Agent_Logger::error('OpenAI API Missing Choices', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API: No choices returned.');
        }
        
        if (!isset($body['choices'][0]['message']['content'])) {
            WP_Blog_Agent_Logger::error('OpenAI API Missing Content', array('choice' => $body['choices'][0]));
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API: No content in message.');
        }
        
        $content = $body['choices'][0]['message']['content'];
        
        if (empty($content)) {
            WP_Blog_Agent_Logger::error('OpenAI API Empty Content', array('choice' => $body['choices'][0]));
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API: Content is empty.');
        }
        
        WP_Blog_Agent_Logger::info('OpenAI Content Generated Successfully', array(
            'content_length' => strlen($content)
        ));
        
        return $content;
    }
    
    /**
     * Build prompt for content generation
     */
    private function build_prompt($topic, $keywords = array(), $hashtags = array()) {
        $prompt = "Write a comprehensive, SEO-optimized blog post about: {$topic}\n\n";
        $prompt .= "Requirements:\n";
        
        $requirement_num = 1;
        
        // Add keywords if provided
        if (!empty($keywords) && is_array($keywords)) {
            $prompt .= "{$requirement_num}. Include these keywords naturally: " . implode(', ', $keywords) . "\n";
            $requirement_num++;
        }
        
        $prompt .= "{$requirement_num}. Write in an engaging, conversational tone\n";
        $requirement_num++;
        $prompt .= "{$requirement_num}. Include a compelling title\n";
        $requirement_num++;
        $prompt .= "{$requirement_num}. Structure with clear headings and subheadings\n";
        $requirement_num++;
        $prompt .= "{$requirement_num}. Include an introduction, main content, and conclusion\n";
        $requirement_num++;
        $prompt .= "{$requirement_num}. Optimize for SEO with proper keyword density\n";
        $requirement_num++;
        
        // Check if inline images are enabled
        $auto_generate_inline_images = get_option('wp_blog_agent_auto_generate_inline_images', 'no');
        if ($auto_generate_inline_images === 'yes') {
            $prompt .= "{$requirement_num}. Add 2-4 image placeholders throughout the article using this exact format: [IMAGE: description of the image needed]\n";
            $prompt .= "   - Place image placeholders where visual content would enhance understanding\n";
            $prompt .= "   - Each placeholder should have a clear, descriptive text about what the image should show\n";
            $prompt .= "   - Example: [IMAGE: A professional workspace with laptop and coffee]\n";
            $requirement_num++;
        }
        
        // Add hashtags if provided
        if (!empty($hashtags) && is_array($hashtags)) {
            $prompt .= "{$requirement_num}. Add these hashtags at the end: " . implode(' ', $hashtags) . "\n";
            $requirement_num++;
        }
        
        $prompt .= "\nFormat the response with HTML tags (h1, h2, p, ul, li, etc.)";
        
        return $prompt;
    }
    
    /**
     * Generate topic suggestions using OpenAI
     */
    public function generate_topic_suggestions($prompt) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key is not configured.');
        }
        
        $request_body = array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a creative content strategist who suggests relevant topics based on existing content.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.8,
        );
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body' => json_encode($request_body),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code !== 200) {
            $error_message = 'OpenAI API returned status code ' . $status_code;
            if (isset($body['error']['message'])) {
                $error_message .= ': ' . $body['error']['message'];
            }
            return new WP_Error('openai_http_error', $error_message);
        }
        
        if (isset($body['error'])) {
            $error_message = is_array($body['error']) && isset($body['error']['message']) 
                ? $body['error']['message'] 
                : 'Unknown error';
            return new WP_Error('openai_error', $error_message);
        }
        
        if (!isset($body['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API');
        }
        
        return $body['choices'][0]['message']['content'];
    }
}
