<div class="wrap">
    <h1><?php echo esc_html__('Image Generation', 'wp-blog-agent'); ?></h1>
    
    <?php
    // Display messages
    if (isset($_GET['settings_saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'wp-blog-agent') . '</p></div>';
    }
    if (isset($_GET['generated'])) {
        $attachment_id = intval($_GET['generated']);
        $image_url = wp_get_attachment_url($attachment_id);
        $edit_link = admin_url('post.php?post=' . $attachment_id . '&action=edit');
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(
                 esc_html__('Image generated successfully! %s | %s', 'wp-blog-agent'),
                 '<a href="' . esc_url($edit_link) . '">' . esc_html__('View in Media Library', 'wp-blog-agent') . '</a>',
                 '<a href="' . esc_url($image_url) . '" target="_blank">' . esc_html__('View Image', 'wp-blog-agent') . '</a>'
             ) . 
             '</p></div>';
    }
    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($_GET['error']) . '</p></div>';
    }
    ?>
    
    <div class="wp-blog-agent-image-settings" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px;">
        <h2><?php echo esc_html__('Image Generation Settings', 'wp-blog-agent'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('wp_blog_agent_image_settings', 'wp_blog_agent_image_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gemini_image_api_key"><?php echo esc_html__('Gemini Image API Key', 'wp-blog-agent'); ?></label>
                    </th>
                    <td>
                        <input type="password" name="gemini_image_api_key" id="gemini_image_api_key" value="<?php echo esc_attr(get_option('wp_blog_agent_gemini_image_api_key', '')); ?>" class="regular-text" />
                        <p class="description"><?php echo esc_html__('Enter your Google Gemini API key for image generation (Imagen API). Get one at https://makersuite.google.com/app/apikey', 'wp-blog-agent'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Settings', 'wp-blog-agent')); ?>
        </form>
    </div>
    
    <div class="wp-blog-agent-image-container">
        <div class="wp-blog-agent-generate-image">
            <h2><?php echo esc_html__('Generate Image', 'wp-blog-agent'); ?></h2>
            <p><?php echo esc_html__('Generate images using Gemini Imagen API and save them to your media library.', 'wp-blog-agent'); ?></p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="wp_blog_agent_generate_image">
                <?php wp_nonce_field('wp_blog_agent_generate_image'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="image_prompt"><?php echo esc_html__('Image Prompt', 'wp-blog-agent'); ?> *</label>
                        </th>
                        <td>
                            <textarea name="image_prompt" id="image_prompt" class="large-text" rows="4" required></textarea>
                            <p class="description"><?php echo esc_html__('Describe the image you want to generate. Be specific and detailed for best results.', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="post_id"><?php echo esc_html__('Attach to Post (Optional)', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="post_id" id="post_id" class="regular-text" min="0" value="0">
                            <p class="description"><?php echo esc_html__('Enter a post ID to attach the image to that post and set it as featured image. Leave 0 to just save to media library.', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="aspect_ratio"><?php echo esc_html__('Aspect Ratio', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <select name="aspect_ratio" id="aspect_ratio">
                                <option value="16:9" selected><?php echo esc_html__('16:9 (Widescreen)', 'wp-blog-agent'); ?></option>
                                <option value="4:3"><?php echo esc_html__('4:3 (Standard)', 'wp-blog-agent'); ?></option>
                                <option value="1:1"><?php echo esc_html__('1:1 (Square)', 'wp-blog-agent'); ?></option>
                                <option value="3:4"><?php echo esc_html__('3:4 (Portrait)', 'wp-blog-agent'); ?></option>
                            </select>
                            <p class="description"><?php echo esc_html__('Choose the aspect ratio for the generated image.', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="image_size"><?php echo esc_html__('Image Size', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <select name="image_size" id="image_size">
                                <option value="1K" selected><?php echo esc_html__('1K (1024px)', 'wp-blog-agent'); ?></option>
                                <option value="2K"><?php echo esc_html__('2K (2048px)', 'wp-blog-agent'); ?></option>
                                <option value="4K"><?php echo esc_html__('4K (4096px)', 'wp-blog-agent'); ?></option>
                            </select>
                            <p class="description"><?php echo esc_html__('Select the resolution for the generated image.', 'wp-blog-agent'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="set_featured"><?php echo esc_html__('Set as Featured Image', 'wp-blog-agent'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="set_featured" id="set_featured" value="1" checked>
                            <label for="set_featured"><?php echo esc_html__('Automatically set as featured image (only if post ID is provided)', 'wp-blog-agent'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Generate Image', 'wp-blog-agent'), 'primary', 'submit', true); ?>
            </form>
        </div>
        
        <hr>
        
        <div class="wp-blog-agent-image-info">
            <h2><?php echo esc_html__('Recent Generated Images', 'wp-blog-agent'); ?></h2>
            
            <?php
            // Get recent images generated by the plugin
            $args = array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => 12,
                'meta_query' => array(
                    array(
                        'key' => '_wp_blog_agent_generated_image',
                        'compare' => 'EXISTS'
                    )
                ),
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            $images = new WP_Query($args);
            
            if ($images->have_posts()) {
                echo '<div class="wp-blog-agent-image-grid">';
                while ($images->have_posts()) {
                    $images->the_post();
                    $attachment_id = get_the_ID();
                    $image_url = wp_get_attachment_url($attachment_id);
                    $thumbnail = wp_get_attachment_image($attachment_id, 'medium', false, array('style' => 'max-width: 100%; height: auto;'));
                    $edit_link = admin_url('post.php?post=' . $attachment_id . '&action=edit');
                    $prompt = get_post_meta($attachment_id, '_wp_blog_agent_image_prompt', true);
                    
                    echo '<div class="wp-blog-agent-image-item">';
                    echo '<a href="' . esc_url($edit_link) . '">' . $thumbnail . '</a>';
                    echo '<div class="wp-blog-agent-image-details">';
                    echo '<p class="wp-blog-agent-image-date">' . get_the_date() . '</p>';
                    if ($prompt) {
                        echo '<p class="wp-blog-agent-image-prompt">' . esc_html(mb_strimwidth($prompt, 0, 80, '...')) . '</p>';
                    }
                    echo '<a href="' . esc_url($edit_link) . '" class="button button-small">' . esc_html__('View Details', 'wp-blog-agent') . '</a>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                wp_reset_postdata();
            } else {
                echo '<p>' . esc_html__('No generated images yet. Use the form above to generate your first image.', 'wp-blog-agent') . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<style>
.wp-blog-agent-image-container {
    max-width: 1200px;
}
.wp-blog-agent-generate-image {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
}
.wp-blog-agent-image-info {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.wp-blog-agent-image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.wp-blog-agent-image-item {
    border: 1px solid #ddd;
    padding: 10px;
    background: #fafafa;
    border-radius: 4px;
}
.wp-blog-agent-image-item img {
    width: 100%;
    height: auto;
    display: block;
    margin-bottom: 10px;
    border-radius: 2px;
}
.wp-blog-agent-image-details {
    padding: 5px 0;
}
.wp-blog-agent-image-date {
    font-size: 12px;
    color: #666;
    margin: 5px 0;
}
.wp-blog-agent-image-prompt {
    font-size: 13px;
    color: #333;
    margin: 5px 0 10px;
    line-height: 1.4;
}
</style>
