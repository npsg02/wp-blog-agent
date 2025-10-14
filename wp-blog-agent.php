<?php
/**
 * Plugin Name: WP Blog Agent
 * Plugin URI: https://github.com/np2023v2/wp-blog-agent
 * Description: Automated blog post generation using OpenAI or Gemini API with hashtags, keywords, SEO optimization, and scheduled publishing.
 * Version: 1.0.0
 * Author: NP2023
 * Author URI: https://github.com/np2023v2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-blog-agent
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_BLOG_AGENT_VERSION', '1.0.0');
define('WP_BLOG_AGENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_BLOG_AGENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_BLOG_AGENT_PLUGIN_FILE', __FILE__);

// Include required files
require_once WP_BLOG_AGENT_PLUGIN_DIR . 'includes/class-wp-blog-agent-activator.php';
require_once WP_BLOG_AGENT_PLUGIN_DIR . 'includes/class-wp-blog-agent-deactivator.php';
require_once WP_BLOG_AGENT_PLUGIN_DIR . 'includes/class-wp-blog-agent-admin.php';
require_once WP_BLOG_AGENT_PLUGIN_DIR . 'includes/class-wp-blog-agent-generator.php';
require_once WP_BLOG_AGENT_PLUGIN_DIR . 'includes/class-wp-blog-agent-openai.php';
require_once WP_BLOG_AGENT_PLUGIN_DIR . 'includes/class-wp-blog-agent-gemini.php';
require_once WP_BLOG_AGENT_PLUGIN_DIR . 'includes/class-wp-blog-agent-scheduler.php';

// Activation hook
register_activation_hook(__FILE__, array('WP_Blog_Agent_Activator', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('WP_Blog_Agent_Deactivator', 'deactivate'));

// Initialize the plugin
function wp_blog_agent_init() {
    $admin = new WP_Blog_Agent_Admin();
    $scheduler = new WP_Blog_Agent_Scheduler();
}
add_action('plugins_loaded', 'wp_blog_agent_init');
