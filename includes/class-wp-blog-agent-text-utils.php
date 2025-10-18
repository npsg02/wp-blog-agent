<?php
/**
 * Text Utilities for WP Blog Agent
 * Provides text sanitization and cleaning functions to handle encoding issues
 */
class WP_Blog_Agent_Text_Utils {
    
    /**
     * Clean text for use in JSON encoding
     * Removes invalid UTF-8 sequences and problematic characters
     * 
     * @param string $text The text to clean
     * @return string The cleaned text
     */
    public static function clean_for_json($text) {
        if (empty($text)) {
            return '';
        }
        
        // Convert to string if not already
        $text = (string) $text;
        
        // Fix UTF-8 encoding issues
        $text = self::fix_utf8_encoding($text);
        
        // Remove null bytes
        $text = str_replace("\0", '', $text);
        
        // Remove control characters except for common whitespace
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Remove any remaining problematic characters
        $text = preg_replace('/[^\x{0009}\x{000A}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);
        
        return $text;
    }
    
    /**
     * Fix UTF-8 encoding issues
     * 
     * @param string $text The text to fix
     * @return string The fixed text
     */
    public static function fix_utf8_encoding($text) {
        if (empty($text)) {
            return '';
        }
        
        // Check if the string is valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to detect and convert encoding
            $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            
            if ($encoding && $encoding !== 'UTF-8') {
                $text = mb_convert_encoding($text, 'UTF-8', $encoding);
                WP_Blog_Agent_Logger::warning('Text encoding converted', array(
                    'from' => $encoding,
                    'to' => 'UTF-8'
                ));
            } else {
                // If detection fails, use utf8_encode as fallback
                $text = utf8_encode($text);
                WP_Blog_Agent_Logger::warning('Text encoding fixed using utf8_encode fallback');
            }
        }
        
        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        return $text;
    }
    
    /**
     * Sanitize text for use in prompts
     * Cleans and normalizes text while preserving meaningful content
     * 
     * @param string $text The text to sanitize
     * @return string The sanitized text
     */
    public static function sanitize_for_prompt($text) {
        if (empty($text)) {
            return '';
        }
        
        // First, clean for JSON encoding
        $text = self::clean_for_json($text);
        
        // Strip HTML tags but preserve spacing
        $text = wp_strip_all_tags($text, true);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Validate that text can be JSON encoded
     * 
     * @param mixed $data The data to validate
     * @return bool True if data can be JSON encoded, false otherwise
     */
    public static function is_json_encodable($data) {
        $json = json_encode($data);
        return $json !== false && json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Safe JSON encode with error logging
     * 
     * @param mixed $data The data to encode
     * @param int $options JSON encode options
     * @param int $depth Maximum depth
     * @return string|false JSON string or false on failure
     */
    public static function safe_json_encode($data, $options = 0, $depth = 512) {
        $json = json_encode($data, $options, $depth);
        
        if ($json === false) {
            $error = json_last_error();
            $error_msg = json_last_error_msg();
            
            WP_Blog_Agent_Logger::error('JSON encoding failed', array(
                'error_code' => $error,
                'error_message' => $error_msg,
                'data_type' => gettype($data),
                'data_summary' => is_array($data) ? array_keys($data) : (is_object($data) ? get_class($data) : substr((string)$data, 0, 100))
            ));
            
            return false;
        }
        
        return $json;
    }
    
    /**
     * Clean array recursively for JSON encoding
     * 
     * @param array $array The array to clean
     * @return array The cleaned array
     */
    public static function clean_array_for_json($array) {
        if (!is_array($array)) {
            return $array;
        }
        
        $cleaned = array();
        
        foreach ($array as $key => $value) {
            $clean_key = self::clean_for_json($key);
            
            if (is_array($value)) {
                $cleaned[$clean_key] = self::clean_array_for_json($value);
            } elseif (is_string($value)) {
                $cleaned[$clean_key] = self::clean_for_json($value);
            } else {
                $cleaned[$clean_key] = $value;
            }
        }
        
        return $cleaned;
    }
}
