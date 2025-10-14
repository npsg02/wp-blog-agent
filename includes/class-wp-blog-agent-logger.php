<?php
/**
 * Logger Class for WP Blog Agent
 * Provides centralized logging functionality
 */
class WP_Blog_Agent_Logger {
    
    private static $log_file = null;
    
    /**
     * Initialize logger
     */
    public static function init() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/wp-blog-agent-logs';
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // Add .htaccess to protect log files
            file_put_contents($log_dir . '/.htaccess', "Deny from all\n");
        }
        
        self::$log_file = $log_dir . '/wp-blog-agent-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = array()) {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = array()) {
        self::log('ERROR', $message, $context);
        
        // Also log to WordPress error log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP Blog Agent Error: ' . $message);
        }
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = array()) {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log success message
     */
    public static function success($message, $context = array()) {
        self::log('SUCCESS', $message, $context);
    }
    
    /**
     * Main logging function
     */
    private static function log($level, $message, $context = array()) {
        if (self::$log_file === null) {
            self::init();
        }
        
        // Check if logging is enabled
        if (!get_option('wp_blog_agent_enable_logging', true)) {
            return;
        }
        
        $timestamp = current_time('mysql');
        $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $log_entry = sprintf("[%s] [%s] %s%s\n", $timestamp, $level, $message, $context_str);
        
        // Append to log file
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND);
        
        // Rotate logs if file is too large (>5MB)
        self::rotate_logs();
    }
    
    /**
     * Rotate log files if they get too large
     */
    private static function rotate_logs() {
        if (self::$log_file === null || !file_exists(self::$log_file)) {
            return;
        }
        
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (filesize(self::$log_file) > $max_size) {
            $archive_name = self::$log_file . '.' . time() . '.old';
            rename(self::$log_file, $archive_name);
        }
    }
    
    /**
     * Get recent log entries
     */
    public static function get_recent_logs($lines = 100) {
        if (self::$log_file === null) {
            self::init();
        }
        
        if (!file_exists(self::$log_file)) {
            return array();
        }
        
        $file = new SplFileObject(self::$log_file, 'r');
        $file->seek(PHP_INT_MAX);
        $last_line = $file->key();
        $start_line = max(0, $last_line - $lines);
        
        $logs = array();
        $file->seek($start_line);
        
        while (!$file->eof()) {
            $line = $file->current();
            if (!empty(trim($line))) {
                $logs[] = $line;
            }
            $file->next();
        }
        
        return array_reverse($logs);
    }
    
    /**
     * Clear old log files
     */
    public static function clear_old_logs($days = 30) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/wp-blog-agent-logs';
        
        if (!is_dir($log_dir)) {
            return;
        }
        
        $files = glob($log_dir . '/*.log*');
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
}
