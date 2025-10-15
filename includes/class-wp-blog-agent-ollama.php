<?php
/**
 * Ollama API Integration
 */
class WP_Blog_Agent_Ollama {
    
    private $api_url;
    private $model;
    
    public function __construct() {
        $this->api_url = get_option('wp_blog_agent_ollama_base_url', 'http://localhost:11434/api/generate');
        $this->model = get_option('wp_blog_agent_ollama_model', 'llama2');
    }
    
    /**
     * Generate blog post using Ollama
     */
    public function generate_content($topic, $keywords, $hashtags) {
        $prompt = $this->build_prompt($topic, $keywords, $hashtags);
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
            )),
            'timeout' => 120, // Ollama might take longer for local models
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('ollama_error', $body['error']);
        }
        
        if (!isset($body['response'])) {
            return new WP_Error('invalid_response', 'Invalid response from Ollama API.');
        }
        
        return $body['response'];
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
