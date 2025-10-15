<?php
/**
 * Google Gemini API Integration
 */
class WP_Blog_Agent_Gemini {
    
    private $api_key;
    private $model;
    
    public function __construct() {
        $this->api_key = get_option('wp_blog_agent_gemini_api_key', '');
        $this->model = get_option('wp_blog_agent_gemini_model', 'gemini-pro');
    }
    
    /**
     * Generate blog post using Gemini
     */
    public function generate_content($topic, $keywords, $hashtags) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Gemini API key is not configured.');
        }
        
        $prompt = $this->build_prompt($topic, $keywords, $hashtags);
        
        $api_url = 'https://generativelanguage.googleapis.com/v1/models/' . $this->model . ':generateContent';
        $url = $api_url . '?key=' . $this->api_key;
        
        $request_body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            )
        );
        
        // Log request in debug mode
        WP_Blog_Agent_Logger::debug('Gemini API Request', array(
            'url' => $api_url,
            'model' => $this->model,
            'body' => $request_body
        ));
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_body),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            WP_Blog_Agent_Logger::debug('Gemini API Error', array(
                'error' => $response->get_error_message()
            ));
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log response in debug mode
        WP_Blog_Agent_Logger::debug('Gemini API Response', array(
            'status_code' => wp_remote_retrieve_response_code($response),
            'body' => $body
        ));
        
        if (isset($body['error'])) {
            return new WP_Error('gemini_error', $body['error']['message']);
        }
        
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('invalid_response', 'Invalid response from Gemini API.');
        }
        
        return $body['candidates'][0]['content']['parts'][0]['text'];
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
