<div class="wrap">
    <h1><?php echo esc_html__('Manage Topics', 'wp-blog-agent'); ?></h1>
    
    <?php
    // Display messages
    if (isset($_GET['added'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Topic added successfully!', 'wp-blog-agent') . '</p></div>';
    }
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Topic deleted successfully!', 'wp-blog-agent') . '</p></div>';
    }
    if (isset($_GET['queued'])) {
        $queue_id = intval($_GET['queued']);
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Generation task #%d added to queue! The post will be generated shortly.', 'wp-blog-agent'), $queue_id) . ' <a href="' . admin_url('admin.php?page=wp-blog-agent-queue') . '">' . esc_html__('View Queue', 'wp-blog-agent') . '</a></p></div>';
    }
    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($_GET['error']) . '</p></div>';
    }
    ?>
    
    <div class="wp-blog-agent-topics-container">
        <div class="wp-blog-agent-add-topic">
            <h2><?php echo esc_html__('Quick Generate (Manual Topic)', 'wp-blog-agent'); ?></h2>
            <p><?php echo esc_html__('Generate a blog post immediately without saving the topic to the database.', 'wp-blog-agent'); ?></p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="wp_blog_agent_generate_manual">
                <?php wp_nonce_field('wp_blog_agent_generate_manual'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="manual_topic"><?php echo esc_html__('Topic', 'wp-blog-agent'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="manual_topic" id="manual_topic" class="regular-text" required>
                            <p class="description"><?php echo esc_html__('Main topic or subject for the blog post.', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="manual_keywords"><?php echo esc_html__('Keywords', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <textarea name="manual_keywords" id="manual_keywords" class="large-text" rows="2"></textarea>
                            <p class="description"><?php echo esc_html__('Comma-separated keywords for SEO optimization (optional).', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="manual_hashtags"><?php echo esc_html__('Hashtags', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <textarea name="manual_hashtags" id="manual_hashtags" class="large-text" rows="2"></textarea>
                            <p class="description"><?php echo esc_html__('Comma-separated hashtags (optional).', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Generate Now', 'wp-blog-agent'), 'primary', 'submit', true); ?>
            </form>
        </div>
        
        <hr>
        
        <div class="wp-blog-agent-add-topic">
            <h2><?php echo esc_html__('Add New Topic', 'wp-blog-agent'); ?></h2>
            <p><?php echo esc_html__('Save a topic for scheduled or future generation.', 'wp-blog-agent'); ?></p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="wp_blog_agent_add_topic">
                <?php wp_nonce_field('wp_blog_agent_add_topic'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="topic"><?php echo esc_html__('Topic', 'wp-blog-agent'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="topic" id="topic" class="regular-text" required>
                            <p class="description"><?php echo esc_html__('Main topic or subject for the blog post.', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keywords"><?php echo esc_html__('Keywords', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <textarea name="keywords" id="keywords" class="large-text" rows="3"></textarea>
                            <p class="description"><?php echo esc_html__('Comma-separated keywords for SEO optimization (optional). Example: wordpress, blogging, content marketing', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="hashtags"><?php echo esc_html__('Hashtags', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <textarea name="hashtags" id="hashtags" class="large-text" rows="2"></textarea>
                            <p class="description"><?php echo esc_html__('Comma-separated hashtags. Example: #wordpress, #blogging, #seo', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Add Topic', 'wp-blog-agent')); ?>
            </form>
        </div>
        
        <hr>
        
        <h2><?php echo esc_html__('Existing Topics', 'wp-blog-agent'); ?></h2>
        
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'blog_agent_topics';
        $topics = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        if ($topics) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Topic', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Keywords', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Hashtags', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Status', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Created', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Actions', 'wp-blog-agent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topics as $topic) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($topic->topic); ?></strong></td>
                        <td><?php echo esc_html($topic->keywords); ?></td>
                        <td><?php echo esc_html($topic->hashtags); ?></td>
                        <td>
                            <span class="wp-blog-agent-status-<?php echo esc_attr($topic->status); ?>">
                                <?php echo esc_html(ucfirst($topic->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($topic->created_at))); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wp_blog_agent_generate_now&topic_id=' . $topic->id), 'wp_blog_agent_generate_now'); ?>" class="button button-small">
                                <?php echo esc_html__('Generate', 'wp-blog-agent'); ?>
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wp_blog_agent_delete_topic&id=' . $topic->id), 'wp_blog_agent_delete_topic_' . $topic->id); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this topic?', 'wp-blog-agent')); ?>')">
                                <?php echo esc_html__('Delete', 'wp-blog-agent'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>' . esc_html__('No topics found. Add your first topic above.', 'wp-blog-agent') . '</p>';
        }
        ?>
    </div>
</div>

<style>
.wp-blog-agent-topics-container {
    max-width: 1200px;
}
.wp-blog-agent-add-topic {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.wp-blog-agent-status-active {
    color: #46b450;
    font-weight: 600;
}
.wp-blog-agent-status-inactive {
    color: #dc3232;
    font-weight: 600;
}
</style>
