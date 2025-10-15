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
        add_action('admin_post_wp_blog_agent_generate_manual', array($this, 'handle_generate_manual'));
        add_action('admin_post_wp_blog_agent_generate_image', array($this, 'handle_generate_image'));
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
            __('Queue', 'wp-blog-agent'),
            __('Queue', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent-queue',
            array($this, 'render_queue_page')
        );
        
        add_submenu_page(
            'wp-blog-agent',
            __('Logs', 'wp-blog-agent'),
            __('Logs', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent-logs',
            array($this, 'render_logs_page')
        );
        
        add_submenu_page(
            'wp-blog-agent',
            __('Image Generation', 'wp-blog-agent'),
            __('Image Generation', 'wp-blog-agent'),
            'manage_options',
            'wp-blog-agent-image-gen',
            array($this, 'render_image_gen_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_ai_provider');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_openai_api_key');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_openai_base_url');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_openai_model');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_openai_max_tokens');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_openai_system_prompt');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_gemini_api_key');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_gemini_model');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_gemini_max_tokens');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_gemini_system_prompt');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_gemini_image_api_key');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_ollama_base_url');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_ollama_model');
        register_setting('wp_blog_agent_settings', 'wp_blog_agent_ollama_system_prompt');
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
            update_option('wp_blog_agent_openai_base_url', esc_url_raw($_POST['openai_base_url']));
            update_option('wp_blog_agent_openai_model', sanitize_text_field($_POST['openai_model']));
            update_option('wp_blog_agent_openai_max_tokens', !empty($_POST['openai_max_tokens']) ? absint($_POST['openai_max_tokens']) : '');
            update_option('wp_blog_agent_openai_system_prompt', sanitize_textarea_field($_POST['openai_system_prompt']));
            update_option('wp_blog_agent_gemini_api_key', sanitize_text_field($_POST['gemini_api_key']));
            update_option('wp_blog_agent_gemini_model', sanitize_text_field($_POST['gemini_model']));
            update_option('wp_blog_agent_gemini_max_tokens', !empty($_POST['gemini_max_tokens']) ? absint($_POST['gemini_max_tokens']) : '');
            update_option('wp_blog_agent_gemini_system_prompt', sanitize_textarea_field($_POST['gemini_system_prompt']));
            update_option('wp_blog_agent_gemini_image_api_key', sanitize_text_field($_POST['gemini_image_api_key']));
            update_option('wp_blog_agent_ollama_base_url', esc_url_raw($_POST['ollama_base_url']));
            update_option('wp_blog_agent_ollama_model', sanitize_text_field($_POST['ollama_model']));
            update_option('wp_blog_agent_ollama_system_prompt', sanitize_textarea_field($_POST['ollama_system_prompt']));
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
     * Render queue page
     */
    public function render_queue_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle cleanup action
        if (isset($_POST['cleanup_queue']) && check_admin_referer('wp_blog_agent_cleanup_queue')) {
            $days = isset($_POST['cleanup_days']) ? intval($_POST['cleanup_days']) : 7;
            $deleted = WP_Blog_Agent_Queue::cleanup($days);
            echo '<div class="notice notice-success"><p>' . sprintf(__('Cleaned up %d completed/failed tasks!', 'wp-blog-agent'), $deleted) . '</p></div>';
        }
        
        include WP_BLOG_AGENT_PLUGIN_DIR . 'admin/queue-page.php';
    }
    
    /**
     * Render image generation page
     */
    public function render_image_gen_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include WP_BLOG_AGENT_PLUGIN_DIR . 'admin/image-gen-page.php';
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
        
        $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : null;
        
        // Add to queue
        $queue_id = WP_Blog_Agent_Queue::enqueue($topic_id, 'manual');
        
        if ($queue_id === false) {
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&error=' . urlencode('Failed to add generation task to queue')));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&queued=' . $queue_id));
        }
        exit;
    }
    
    /**
     * Handle manual topic generation
     */
    public function handle_generate_manual() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('wp_blog_agent_generate_manual');
        
        // Validate topic data
        $validated = WP_Blog_Agent_Validator::validate_topic(
            $_POST['manual_topic'],
            $_POST['manual_keywords'],
            isset($_POST['manual_hashtags']) ? $_POST['manual_hashtags'] : ''
        );
        
        if (is_wp_error($validated)) {
            WP_Blog_Agent_Logger::warning('Manual topic validation failed', array('error' => $validated->get_error_message()));
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&error=' . urlencode($validated->get_error_message())));
            exit;
        }
        
        WP_Blog_Agent_Logger::info('Manual generation with custom topic triggered', array('topic' => $validated['topic']));
        
        // Parse keywords and hashtags
        $keywords = array_filter(array_map('trim', explode(',', $validated['keywords'])));
        $hashtags = array_filter(array_map('trim', explode(',', $validated['hashtags'])));
        
        // Add # prefix to hashtags if not present
        $hashtags = array_map(function($tag) {
            return strpos($tag, '#') === 0 ? $tag : '#' . $tag;
        }, $hashtags);
        
        // Get AI provider
        $provider = get_option('wp_blog_agent_ai_provider', 'openai');
        
        // Generate content
        if ($provider === 'gemini') {
            $ai = new WP_Blog_Agent_Gemini();
        } elseif ($provider === 'ollama') {
            $ai = new WP_Blog_Agent_Ollama();
        } else {
            $ai = new WP_Blog_Agent_OpenAI();
        }
        
        $content = $ai->generate_content($validated['topic'], $keywords, $hashtags);
        
        if (is_wp_error($content)) {
            WP_Blog_Agent_Logger::error('Manual content generation failed', array(
                'error' => $content->get_error_message(),
                'provider' => $provider
            ));
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&error=' . urlencode($content->get_error_message())));
            exit;
        }
        
        WP_Blog_Agent_Logger::info('Manual content generated successfully', array('provider' => $provider));
        
        // Parse content to extract title and body
        $parsed = $this->parse_generated_content($content);
        
        // Determine post status
        $auto_publish = get_option('wp_blog_agent_auto_publish', 'yes');
        $post_status = ($auto_publish === 'yes') ? 'publish' : 'draft';
        
        // Create the post
        $post_data = array(
            'post_title'   => $parsed['title'],
            'post_content' => $parsed['content'],
            'post_status'  => $post_status,
            'post_author'  => 1,
            'post_type'    => 'post',
            'post_excerpt' => $this->generate_excerpt($parsed['content']),
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            WP_Blog_Agent_Logger::error('Post creation failed', array('error' => $post_id->get_error_message()));
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-topics&error=' . urlencode($post_id->get_error_message())));
            exit;
        }
        
        WP_Blog_Agent_Logger::success('Manual post created successfully', array(
            'post_id' => $post_id,
            'title' => $parsed['title'],
            'status' => $post_status
        ));
        
        // Add metadata
        update_post_meta($post_id, '_wp_blog_agent_generated', true);
        update_post_meta($post_id, '_wp_blog_agent_topic_id', 0); // 0 indicates manual generation
        update_post_meta($post_id, '_wp_blog_agent_keywords', implode(', ', $keywords));
        update_post_meta($post_id, '_wp_blog_agent_hashtags', implode(' ', $hashtags));
        update_post_meta($post_id, '_wp_blog_agent_provider', $provider);
        
        wp_redirect(admin_url('admin.php?page=wp-blog-agent-posts&generated=' . $post_id));
        exit;
    }
    
    /**
     * Generate excerpt from content
     */
    private function generate_excerpt($content) {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        if (strlen($text) > 150) {
            $text = substr($text, 0, 147) . '...';
        }
        return $text;
    }
    
    /**
     * Parse content to extract title and body
     */
    private function parse_generated_content($content) {
        // Try to extract title from h1 tag
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $matches)) {
            $title = strip_tags($matches[1]);
            $content = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $content, 1);
        } else {
            // Try to get first line as title
            $lines = explode("\n", strip_tags($content));
            $title = trim($lines[0]);
            if (strlen($title) > 100) {
                $title = substr($title, 0, 97) . '...';
            }
        }
        
        return array(
            'title' => $title ?: 'Untitled Post',
            'content' => trim($content)
        );
    }
    
    /**
     * Handle image generation
     */
    public function handle_generate_image() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('wp_blog_agent_generate_image');
        
        // Validate input
        if (empty($_POST['image_prompt'])) {
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-image-gen&error=' . urlencode('Image prompt is required.')));
            exit;
        }
        
        $prompt = sanitize_textarea_field($_POST['image_prompt']);
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $aspect_ratio = isset($_POST['aspect_ratio']) ? sanitize_text_field($_POST['aspect_ratio']) : '16:9';
        $image_size = isset($_POST['image_size']) ? sanitize_text_field($_POST['image_size']) : '1K';
        $set_featured = isset($_POST['set_featured']) && $_POST['set_featured'] === '1';
        
        WP_Blog_Agent_Logger::info('Image generation triggered', array(
            'prompt' => substr($prompt, 0, 100),
            'post_id' => $post_id,
            'aspect_ratio' => $aspect_ratio,
            'image_size' => $image_size
        ));
        
        // Generate and save image
        $image_generator = new WP_Blog_Agent_Image_Generator();
        
        $params = array(
            'aspectRatio' => $aspect_ratio,
            'imageSize' => $image_size,
            'sampleCount' => 1,
            'outputMimeType' => 'image/jpeg',
            'personGeneration' => 'ALLOW_ALL'
        );
        
        // If set_featured is false or post_id is 0, don't attach to post
        $attach_post_id = ($set_featured && $post_id > 0) ? $post_id : 0;
        
        $attachment_id = $image_generator->generate_and_save($prompt, $attach_post_id, $params);
        
        if (is_wp_error($attachment_id)) {
            WP_Blog_Agent_Logger::error('Image generation failed', array(
                'error' => $attachment_id->get_error_message()
            ));
            wp_redirect(admin_url('admin.php?page=wp-blog-agent-image-gen&error=' . urlencode($attachment_id->get_error_message())));
            exit;
        }
        
        // Store metadata
        update_post_meta($attachment_id, '_wp_blog_agent_generated_image', true);
        update_post_meta($attachment_id, '_wp_blog_agent_image_prompt', $prompt);
        update_post_meta($attachment_id, '_wp_blog_agent_image_params', array(
            'aspect_ratio' => $aspect_ratio,
            'image_size' => $image_size
        ));
        
        if ($post_id > 0) {
            update_post_meta($attachment_id, '_wp_blog_agent_attached_post', $post_id);
        }
        
        WP_Blog_Agent_Logger::success('Image generated and saved successfully', array(
            'attachment_id' => $attachment_id,
            'post_id' => $post_id
        ));
        
        wp_redirect(admin_url('admin.php?page=wp-blog-agent-image-gen&generated=' . $attachment_id));
        exit;
    }
}
