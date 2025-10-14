<div class="wrap">
    <h1><?php echo esc_html__('Activity Logs', 'wp-blog-agent'); ?></h1>
    
    <p><?php echo esc_html__('View recent plugin activity and errors.', 'wp-blog-agent'); ?></p>
    
    <div style="margin: 20px 0;">
        <form method="post" style="display: inline;">
            <?php wp_nonce_field('wp_blog_agent_clear_logs'); ?>
            <input type="hidden" name="clear_logs" value="1">
            <button type="submit" class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear all logs?', 'wp-blog-agent')); ?>')">
                <?php echo esc_html__('Clear All Logs', 'wp-blog-agent'); ?>
            </button>
        </form>
        
        <button type="button" class="button" onclick="location.reload()">
            <?php echo esc_html__('Refresh', 'wp-blog-agent'); ?>
        </button>
    </div>
    
    <?php
    $logs = WP_Blog_Agent_Logger::get_recent_logs(200);
    
    if (!empty($logs)) {
        ?>
        <div class="wp-blog-agent-logs-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 150px;"><?php echo esc_html__('Timestamp', 'wp-blog-agent'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Level', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Message', 'wp-blog-agent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <?php
                        // Parse log entry
                        if (preg_match('/^\[(.*?)\] \[(.*?)\] (.*)$/', $log, $matches)) {
                            $timestamp = $matches[1];
                            $level = $matches[2];
                            $message = $matches[3];
                            
                            // Determine row class based on level
                            $row_class = '';
                            switch ($level) {
                                case 'ERROR':
                                    $row_class = 'wp-blog-agent-log-error';
                                    break;
                                case 'WARNING':
                                    $row_class = 'wp-blog-agent-log-warning';
                                    break;
                                case 'SUCCESS':
                                    $row_class = 'wp-blog-agent-log-success';
                                    break;
                                default:
                                    $row_class = 'wp-blog-agent-log-info';
                            }
                            ?>
                            <tr class="<?php echo esc_attr($row_class); ?>">
                                <td><?php echo esc_html($timestamp); ?></td>
                                <td><strong><?php echo esc_html($level); ?></strong></td>
                                <td style="word-break: break-word;"><?php echo esc_html($message); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .wp-blog-agent-logs-container {
            margin-top: 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .wp-blog-agent-log-error td {
            background-color: #fcf0f1 !important;
            border-left: 4px solid #dc3232;
        }
        .wp-blog-agent-log-warning td {
            background-color: #fff9e6 !important;
            border-left: 4px solid #f0b849;
        }
        .wp-blog-agent-log-success td {
            background-color: #ecf7ed !important;
            border-left: 4px solid #46b450;
        }
        .wp-blog-agent-log-info td {
            border-left: 4px solid #72aee6;
        }
        </style>
        <?php
    } else {
        ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No logs available yet. Logs will appear here as the plugin performs actions.', 'wp-blog-agent'); ?></p>
        </div>
        <?php
    }
    ?>
</div>
