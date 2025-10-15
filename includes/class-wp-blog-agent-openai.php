<?php
/**
 * OpenAI API Integration
 */
class WP_Blog_Agent_OpenAI {
    
    private $api_key;
    private $api_url;
    private $model;
    
    public function __construct() {
        $this->api_key = get_option('wp_blog_agent_openai_api_key', '');
        $this->api_url = get_option('wp_blog_agent_openai_base_url', 'https://api.openai.com/v1/chat/completions');
        $this->model = get_option('wp_blog_agent_openai_model', 'gpt-3.5-turbo');
    }
    
    /**
     * Generate blog post using OpenAI
     */
    public function generate_content($topic, $keywords, $hashtags) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key is not configured.');
        }
        
        $prompt = $this->build_prompt($topic, $keywords, $hashtags);
        
        $request_body = array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a professional blog writer who creates SEO-optimized, engaging content.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 2000,
            'temperature' => 0.7,
        );
        
        // Log request in debug mode
        WP_Blog_Agent_Logger::debug('OpenAI API Request', array(
            'url' => $this->api_url,
            'model' => $this->model,
            'body' => $request_body
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
            WP_Blog_Agent_Logger::debug('OpenAI API Error', array(
                'error' => $response->get_error_message()
            ));
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log response in debug mode
        WP_Blog_Agent_Logger::debug('OpenAI API Response', array(
            'status_code' => wp_remote_retrieve_response_code($response),
            'body' => $body
        ));
        
        if (isset($body['error'])) {
            return new WP_Error('openai_error', $body['error']['message']);
        }
        
        if (!isset($body['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API.');
        }
        
        return $body['choices'][0]['message']['content'];
    }
    
    /**
     * Build prompt for content generation
     */
    private function build_prompt($topic, $keywords, $hashtags) {
        $prompt = "Write a comprehensive, SEO-optimized blog post about: {$topic}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "1. Include these keywords naturally: " . implode(', ', $keywords) . "\n";
        $prompt .= "2. Write in an engaging, conversational tone\n";
        $prompt .= "3. Include a compelling title\n";
        $prompt .= "4. Structure with clear headings and subheadings\n";
        $prompt .= "5. Include an introduction, main content, and conclusion\n";
        $prompt .= "6. Optimize for SEO with proper keyword density\n";
        $prompt .= "7. Add these hashtags at the end: " . implode(' ', $hashtags) . "\n\n";
        $prompt .= "Format the response with HTML tags (h1, h2, p, ul, li, etc.)";
        
        return $prompt;
    }
}
