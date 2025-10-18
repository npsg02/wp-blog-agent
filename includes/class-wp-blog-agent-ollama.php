<?php
/**
 * Ollama API Integration
 */
class WP_Blog_Agent_Ollama {
    
    private $api_url;
    private $model;
    private $system_prompt;
    
    public function __construct() {
        $this->api_url = get_option('wp_blog_agent_ollama_base_url', 'http://localhost:11434/api/generate');
        $this->model = get_option('wp_blog_agent_ollama_model', 'llama2');
        $this->system_prompt = get_option('wp_blog_agent_ollama_system_prompt', 'You are a professional blog writer who creates SEO-optimized, engaging content.');
    }
    
    /**
     * Generate blog post using Ollama
     */
    public function generate_content($topic, $keywords = array(), $hashtags = array()) {
        $prompt = $this->build_prompt($topic, $keywords, $hashtags);
        
        // Prepend system prompt to the user prompt for Ollama
        $full_prompt = $this->system_prompt . "\n\n" . $prompt;
        
        // Clean prompt for JSON encoding
        $clean_full_prompt = WP_Blog_Agent_Text_Utils::clean_for_json($full_prompt);
        
        $request_body = array(
            'model' => $this->model,
            'prompt' => $clean_full_prompt,
            'stream' => false,
        );
        
        // Log request
        WP_Blog_Agent_Logger::info('Ollama API Request', array(
            'url' => $this->api_url,
            'model' => $this->model,
            'prompt_length' => strlen($prompt)
        ));
        
        // Use safe JSON encoding with error logging
        $json_body = WP_Blog_Agent_Text_Utils::safe_json_encode($request_body);
        
        if ($json_body === false) {
            WP_Blog_Agent_Logger::error('Ollama JSON encoding failed');
            return new WP_Error('json_encode_failed', 'Failed to encode request body for Ollama API.');
        }
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => $json_body,
            'timeout' => 120, // Ollama might take longer for local models
        ));
        
        if (is_wp_error($response)) {
            WP_Blog_Agent_Logger::error('Ollama API Error', array(
                'error' => $response->get_error_message()
            ));
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log response
        WP_Blog_Agent_Logger::info('Ollama API Response', array(
            'status_code' => $status_code,
            'has_response' => isset($body['response'])
        ));
        
        // Handle HTTP errors
        if ($status_code !== 200) {
            $error_message = 'Ollama API returned status code ' . $status_code;
            if (isset($body['error'])) {
                $error_message .= ': ' . (is_string($body['error']) ? $body['error'] : json_encode($body['error']));
            }
            WP_Blog_Agent_Logger::error('Ollama API HTTP Error', array(
                'status_code' => $status_code,
                'body' => $body
            ));
            return new WP_Error('ollama_http_error', $error_message);
        }
        
        // Handle API errors
        if (isset($body['error'])) {
            $error_message = is_string($body['error']) ? $body['error'] : json_encode($body['error']);
            WP_Blog_Agent_Logger::error('Ollama API Error Response', array('error' => $body['error']));
            return new WP_Error('ollama_error', $error_message);
        }
        
        // Validate response structure
        if (!is_array($body)) {
            WP_Blog_Agent_Logger::error('Ollama API Invalid Response Format', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response format from Ollama API: Response is not an array.');
        }
        
        if (!isset($body['response'])) {
            WP_Blog_Agent_Logger::error('Ollama API Missing Response', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response from Ollama API: No response field.');
        }
        
        $content = $body['response'];
        
        if (empty($content)) {
            WP_Blog_Agent_Logger::error('Ollama API Empty Content', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response from Ollama API: Content is empty.');
        }
        
        WP_Blog_Agent_Logger::info('Ollama Content Generated Successfully', array(
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
}
