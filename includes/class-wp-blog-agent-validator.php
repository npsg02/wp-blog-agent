<?php
/**
 * Settings Validator
 * Validates and sanitizes plugin settings
 */
class WP_Blog_Agent_Validator {
    
    /**
     * Validate AI provider
     */
    public static function validate_ai_provider($provider) {
        $valid_providers = array('openai', 'gemini');
        return in_array($provider, $valid_providers) ? $provider : 'openai';
    }
    
    /**
     * Validate API key format
     */
    public static function validate_api_key($key, $provider = 'openai') {
        $key = trim($key);
        
        if (empty($key)) {
            return '';
        }
        
        // Basic validation - API keys should be alphanumeric with hyphens/underscores
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $key)) {
            return new WP_Error('invalid_api_key', 'API key contains invalid characters.');
        }
        
        // OpenAI keys typically start with 'sk-'
        if ($provider === 'openai' && substr($key, 0, 3) !== 'sk-') {
            WP_Blog_Agent_Logger::warning('OpenAI API key does not start with "sk-"');
        }
        
        return $key;
    }
    
    /**
     * Validate schedule frequency
     */
    public static function validate_schedule_frequency($frequency) {
        $valid_frequencies = array('hourly', 'twicedaily', 'daily', 'weekly', 'none');
        return in_array($frequency, $valid_frequencies) ? $frequency : 'daily';
    }
    
    /**
     * Validate yes/no option
     */
    public static function validate_yes_no($value) {
        return ($value === 'yes' || $value === '1' || $value === 1) ? 'yes' : 'no';
    }
    
    /**
     * Validate topic data
     */
    public static function validate_topic($topic, $keywords = '', $hashtags = '') {
        $errors = array();
        
        // Validate topic
        $topic = trim(sanitize_text_field($topic));
        if (empty($topic)) {
            $errors[] = 'Topic cannot be empty.';
        } elseif (strlen($topic) > 255) {
            $errors[] = 'Topic is too long (max 255 characters).';
        }
        
        // Validate keywords (now optional)
        $keywords = trim(sanitize_textarea_field($keywords));
        if (!empty($keywords)) {
            $keyword_array = array_map('trim', explode(',', $keywords));
            $keyword_array = array_filter($keyword_array);
            
            if (count($keyword_array) > 50) {
                $errors[] = 'Too many keywords (max 50).';
            }
        }
        
        // Validate hashtags (optional)
        $hashtags = trim(sanitize_textarea_field($hashtags));
        if (!empty($hashtags)) {
            $hashtag_array = array_map('trim', explode(',', $hashtags));
            $hashtag_array = array_filter($hashtag_array);
            
            if (count($hashtag_array) > 30) {
                $errors[] = 'Too many hashtags (max 30).';
            }
            
            // Check hashtag format
            foreach ($hashtag_array as $tag) {
                $tag = str_replace('#', '', $tag);
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $tag)) {
                    $errors[] = 'Hashtag "' . esc_html($tag) . '" contains invalid characters.';
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }
        
        return array(
            'topic' => $topic,
            'keywords' => $keywords,
            'hashtags' => $hashtags
        );
    }
    
    /**
     * Test API connection
     */
    public static function test_api_connection($provider, $api_key) {
        if ($provider === 'openai') {
            $ai = new WP_Blog_Agent_OpenAI();
            // Simple test with minimal token usage
            $test_content = $ai->generate_content('Test', array('test'), array());
        } else {
            $ai = new WP_Blog_Agent_Gemini();
            $test_content = $ai->generate_content('Test', array('test'), array());
        }
        
        if (is_wp_error($test_content)) {
            return $test_content;
        }
        
        return true;
    }
    
    /**
     * Sanitize integer with bounds
     */
    public static function sanitize_int($value, $min = 0, $max = PHP_INT_MAX) {
        $value = intval($value);
        return max($min, min($max, $value));
    }
    
    /**
     * Validate email list
     */
    public static function validate_email_list($emails) {
        if (empty($emails)) {
            return array();
        }
        
        $email_array = array_map('trim', explode(',', $emails));
        $valid_emails = array();
        
        foreach ($email_array as $email) {
            if (is_email($email)) {
                $valid_emails[] = $email;
            }
        }
        
        return $valid_emails;
    }
}
