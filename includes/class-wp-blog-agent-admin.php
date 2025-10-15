<?php
/**
 * Admin Interface
 */
class WP_Blog_Agent_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_wp_blog_agent_add_topic', array($this, 'handle_add_topic'));
        add_action('admin_post_wp_blog_agent_delete_topic', array($this, 'handle_delete_topic'));
        add_action('admin_post_wp_blog_agent_generate_now', array($this, 'handle_generate_now'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WP Blog Agent', 'wp-blog-agent'),
            __('Blog Agent', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent',
            array($this, 'render_settings_page'),
            'dashicons-edit-large',
            30
        );
        
        add_submenu_page(
            'wp-blog-agent',
            __('Settings', 'wp-blog-agent'),
            __('Settings', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'wp-blog-agent',
            __('Topics', 'wp-blog-agent'),
            __('Topics', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent-topics',
            array($this, 'render_topics_page')
        );
        
        add_submenu_page(
            'wp-blog-agent',
            __('Generated Posts', 'wp-blog-agent'),
            __('Generated Posts', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent-posts',
            array($this, 'render_posts_page')
        );
        
        add_submenu_page(
            'wp-blog-agent',
            __('Logs', 'wp-blog-agent'),
            __('Logs', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent-logs',
            array($this, 'render_logs_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_ai_provider');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_openai_api_key');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_gemini_api_key');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_schedule_enabled');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_schedule_frequency');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_auto_publish');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-blog-agent') === false) {
            return;
        }
        
        wp_enqueue_style('wp-blog-agent-admin', WP_BLOG_AGENT_PLUGIN_URL . 'assets/css/admin.css', array(), WP_BLOG_AGENT_VERSION);
        wp_enqueue_script('wp-blog-agent-admin', WP_BLOG_AGENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WP_BLOG_AGENT_VERSION, true);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save settings
        if (isset($_POST['wp_blog_agent_settings_nonce']) && 
            wp_verify_nonce($_POST['wp_blog_agent_settings_nonce'], 'wp_blog_agent_settings')) {
            
            update_option('wp_blog_agent_ai_provider', sanitize_text_field($_POST['ai_provider']));
            update_option('wp_blog_agent_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
            update_option('wp_blog_agent_gemini_api_key', sanitize_text_field($_POST['gemini_api_key']));
            update_option('wp_blog_agent_schedule_enabled', sanitize_text_field($_POST['schedule_enabled']));
            update_option('wp_blog_agent_auto_publish', sanitize_text_field($_POST['auto_publish']));
            
            $frequency = sanitize_text_field($_POST['schedule_frequency']);
            update_option('wp_blog_agent_schedule_frequency', $frequency);
            
            // Update cron schedule
            WP_Blog_Agent_Scheduler::update_schedule($frequency);
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wp-blog-agent') . '</p></div>';
        }
        
        include WP_BLOG_AGENT_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    /**
     * Render topics page
     */
    public function render_topics_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include WP_BLOG_AGENT_PLUGIN_DIR . 'admin/topics-page.php';
    }
    
    /**
     * Render posts page
     */
    public function render_posts_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include WP_BLOG_AGENT_PLUGIN_DIR . 'admin/posts-page.php';
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle clear logs action
        if (isset($_POST['clear_logs']) && check_admin_referer('wp_blog_agent_clear_logs')) {
            WP_Blog_Agent_Logger::clear_old_logs(0);
            echo '<div class="notice notice-success"><p>' . __('Logs cleared successfully!', 'wp-blog-agent') . '</p></div>';
        }
        
        include WP_BLOG_AGENT_PLUGIN_DIR . 'admin/logs-page.php';
    }
    
    /**
     * Handle add topic form submission
     */
    public function handle_add_topic() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('wp_blog_agent_add_topic');
        
        // Validate topic data
        $validated = WP_Blog_Agent_Validator::validate_topic(
            $_POST['topic'],
            $_POST['keywords'],
            $_POST['hashtags']
        );
        
        if (is_wp_error($validated)) {
            WP_Blog_Agent_Logger::warning('Topic validation failed', array('error' => $validated->get_error_message()));
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&error=' . urlencode($validated->get_error_message())));
            exit;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'blog_agent_topics';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'topic' => $validated['topic'],
                'keywords' => $validated['keywords'],
                'hashtags' => $validated['hashtags'],
                'status' => 'active',
            )
        );
        
        if ($result) {
            WP_Blog_Agent_Logger::success('Topic added', array('topic' => $validated['topic']));
        } else {
            WP_Blog_Agent_Logger::error('Failed to add topic', array('topic' => $validated['topic']));
        }
        
        wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&added=1'));
        exit;
    }
    
    /**
     * Handle delete topic
     */
    public function handle_delete_topic() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('wp_blog_agent_delete_topic_' . $_GET['id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'blog_agent_topics';
        
        $topic_id = intval($_GET['id']);
        
        $wpdb->delete(
            $table_name,
            array('id' => $topic_id)
        );
        
        WP_Blog_Agent_Logger::info('Topic deleted', array('topic_id' => $topic_id));
        
        wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&deleted=1'));
        exit;
    }
    
    /**
     * Handle generate now action
     */
    public function handle_generate_now() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('wp_blog_agent_generate_now');
        
        WP_Blog_Agent_Logger::info('Manual generation triggered');
        
        $generator = new WP_Blog_Agent_Generator();
        $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : null;
        $result = $generator->generate_post($topic_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-posts&generated=' . $result));
        }
        exit;
    }
}
