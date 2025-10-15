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
    public function generate_content($topic, $keywords = array(), $hashtags = array()) {
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
        
        // Log request
        WP_Blog_Agent_Logger::info('Gemini API Request', array(
            'url' => $api_url,
            'model' => $this->model,
            'prompt_length' => strlen($prompt)
        ));
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_body),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            WP_Blog_Agent_Logger::error('Gemini API Error', array(
                'error' => $response->get_error_message()
            ));
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log response
        WP_Blog_Agent_Logger::info('Gemini API Response', array(
            'status_code' => $status_code,
            'has_candidates' => isset($body['candidates']),
            'candidates_count' => isset($body['candidates']) ? count($body['candidates']) : 0
        ));
        
        // Handle HTTP errors
        if ($status_code !== 200) {
            $error_message = 'Gemini API returned status code ' . $status_code;
            if (isset($body['error']['message'])) {
                $error_message .= ': ' . $body['error']['message'];
            } elseif (isset($body['error'])) {
                $error_message .= ': ' . (is_string($body['error']) ? $body['error'] : json_encode($body['error']));
            }
            WP_Blog_Agent_Logger::error('Gemini API HTTP Error', array(
                'status_code' => $status_code,
                'body' => $body
            ));
            return new WP_Error('gemini_http_error', $error_message);
        }
        
        // Handle API errors
        if (isset($body['error'])) {
            $error_message = is_array($body['error']) && isset($body['error']['message']) 
                ? $body['error']['message'] 
                : (is_string($body['error']) ? $body['error'] : 'Unknown error');
            WP_Blog_Agent_Logger::error('Gemini API Error Response', array('error' => $body['error']));
            return new WP_Error('gemini_error', $error_message);
        }
        
        // Validate response structure
        if (!is_array($body)) {
            WP_Blog_Agent_Logger::error('Gemini API Invalid Response Format', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response format from Gemini API: Response is not an array.');
        }
        
        if (!isset($body['candidates']) || !is_array($body['candidates']) || empty($body['candidates'])) {
            WP_Blog_Agent_Logger::error('Gemini API Missing Candidates', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response from Gemini API: No candidates returned.');
        }
        
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            WP_Blog_Agent_Logger::error('Gemini API Missing Text', array('candidate' => $body['candidates'][0]));
            return new WP_Error('invalid_response', 'Invalid response from Gemini API: No text in candidate.');
        }
        
        $content = $body['candidates'][0]['content']['parts'][0]['text'];
        
        if (empty($content)) {
            WP_Blog_Agent_Logger::error('Gemini API Empty Content', array('candidate' => $body['candidates'][0]));
            return new WP_Error('invalid_response', 'Invalid response from Gemini API: Content is empty.');
        }
        
        WP_Blog_Agent_Logger::info('Gemini Content Generated Successfully', array(
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
        
        // Add hashtags if provided
        if (!empty($hashtags) && is_array($hashtags)) {
            $prompt .= "{$requirement_num}. Add these hashtags at the end: " . implode(' ', $hashtags) . "\n";
            $requirement_num++;
        }
        
        $prompt .= "\nFormat the response with HTML tags (h1, h2, p, ul, li, etc.)";
        
        return $prompt;
    }
}
