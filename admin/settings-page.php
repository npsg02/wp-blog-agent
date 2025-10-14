<div class="wrap">
    <h1><?php echo esc_html__('WP Blog Agent - Settings', 'wp-blog-agent'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wp_blog_agent_settings', 'wp_blog_agent_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ai_provider"><?php echo esc_html__('AI Provider', 'wp-blog-agent'); ?></label>
                </th>
                <td>
                    <select name="ai_provider" id="ai_provider" class="regular-text">
                        <option value="openai" <?php selected(get_option('wp_blog_agent_ai_provider', 'openai'), 'openai'); ?>>OpenAI (GPT)</option>
                        <option value="gemini" <?php selected(get_option('wp_blog_agent_ai_provider'), 'gemini'); ?>>Google Gemini</option>
                    </select>
                    <p class="description"><?php echo esc_html__('Choose which AI service to use for generating content.', 'wp-blog-agent'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="openai_api_key"><?php echo esc_html__('OpenAI API Key', 'wp-blog-agent'); ?></label>
                </th>
                <td>
                    <input type="password" name="openai_api_key" id="openai_api_key" value="<?php echo esc_attr(get_option('wp_blog_agent_openai_api_key', '')); ?>" class="regular-text" />
                    <p class="description"><?php echo esc_html__('Enter your OpenAI API key. Get one at https://platform.openai.com/api-keys', 'wp-blog-agent'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="gemini_api_key"><?php echo esc_html__('Gemini API Key', 'wp-blog-agent'); ?></label>
                </th>
                <td>
                    <input type="password" name="gemini_api_key" id="gemini_api_key" value="<?php echo esc_attr(get_option('wp_blog_agent_gemini_api_key', '')); ?>" class="regular-text" />
                    <p class="description"><?php echo esc_html__('Enter your Google Gemini API key. Get one at https://makersuite.google.com/app/apikey', 'wp-blog-agent'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="schedule_enabled"><?php echo esc_html__('Enable Scheduling', 'wp-blog-agent'); ?></label>
                </th>
                <td>
                    <select name="schedule_enabled" id="schedule_enabled" class="regular-text">
                        <option value="yes" <?php selected(get_option('wp_blog_agent_schedule_enabled', 'no'), 'yes'); ?>><?php echo esc_html__('Yes', 'wp-blog-agent'); ?></option>
                        <option value="no" <?php selected(get_option('wp_blog_agent_schedule_enabled', 'no'), 'no'); ?>><?php echo esc_html__('No', 'wp-blog-agent'); ?></option>
                    </select>
                    <p class="description"><?php echo esc_html__('Enable automatic post generation on schedule.', 'wp-blog-agent'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="schedule_frequency"><?php echo esc_html__('Schedule Frequency', 'wp-blog-agent'); ?></label>
                </th>
                <td>
                    <select name="schedule_frequency" id="schedule_frequency" class="regular-text">
                        <option value="hourly" <?php selected(get_option('wp_blog_agent_schedule_frequency', 'daily'), 'hourly'); ?>><?php echo esc_html__('Hourly', 'wp-blog-agent'); ?></option>
                        <option value="twicedaily" <?php selected(get_option('wp_blog_agent_schedule_frequency', 'daily'), 'twicedaily'); ?>><?php echo esc_html__('Twice Daily', 'wp-blog-agent'); ?></option>
                        <option value="daily" <?php selected(get_option('wp_blog_agent_schedule_frequency', 'daily'), 'daily'); ?>><?php echo esc_html__('Daily', 'wp-blog-agent'); ?></option>
                        <option value="weekly" <?php selected(get_option('wp_blog_agent_schedule_frequency', 'daily'), 'weekly'); ?>><?php echo esc_html__('Weekly', 'wp-blog-agent'); ?></option>
                    </select>
                    <p class="description"><?php echo esc_html__('How often to generate new posts.', 'wp-blog-agent'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_publish"><?php echo esc_html__('Auto Publish', 'wp-blog-agent'); ?></label>
                </th>
                <td>
                    <select name="auto_publish" id="auto_publish" class="regular-text">
                        <option value="yes" <?php selected(get_option('wp_blog_agent_auto_publish', 'yes'), 'yes'); ?>><?php echo esc_html__('Yes', 'wp-blog-agent'); ?></option>
                        <option value="no" <?php selected(get_option('wp_blog_agent_auto_publish', 'yes'), 'no'); ?>><?php echo esc_html__('No (Save as Draft)', 'wp-blog-agent'); ?></option>
                    </select>
                    <p class="description"><?php echo esc_html__('Automatically publish generated posts or save as drafts for review.', 'wp-blog-agent'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'wp-blog-agent')); ?>
    </form>
    
    <hr>
    
    <h2><?php echo esc_html__('Quick Actions', 'wp-blog-agent'); ?></h2>
    <p>
        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wp_blog_agent_generate_now'), 'wp_blog_agent_generate_now'); ?>" class="button button-primary">
            <?php echo esc_html__('Generate Post Now', 'wp-blog-agent'); ?>
        </a>
    </p>
    
    <hr>
    
    <h2><?php echo esc_html__('Next Scheduled Generation', 'wp-blog-agent'); ?></h2>
    <?php
    $timestamp = wp_next_scheduled('wp_blog_agent_generate_post');
    if ($timestamp) {
        echo '<p>' . sprintf(
            esc_html__('Next generation: %s', 'wp-blog-agent'),
            '<strong>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp) . '</strong>'
        ) . '</p>';
    } else {
        echo '<p>' . esc_html__('No scheduled generation.', 'wp-blog-agent') . '</p>';
    }
    ?>
</div>
