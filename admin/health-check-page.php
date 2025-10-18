<?php
/**
 * Health Check Admin Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Run health checks
$health_results = WP_Blog_Agent_Health_Check::run_all_checks();

?>

<div class="wrap">
    <h1><?php echo esc_html__('Health Check', 'wp-blog-agent'); ?></h1>
    
    <div class="health-check-container" style="margin-top: 20px;">
        
        <!-- Overall Status Card -->
        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">
                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($health_results['overall_status']); ?>
                Overall System Status: <?php echo esc_html(WP_Blog_Agent_Health_Check::get_status_label($health_results['overall_status'])); ?>
            </h2>
            <p style="color: #666;">
                Last checked: <?php echo esc_html($health_results['timestamp']); ?>
            </p>
            <p>
                <button type="button" class="button button-primary" onclick="location.reload();">
                    <?php echo esc_html__('Refresh Health Check', 'wp-blog-agent'); ?>
                </button>
            </p>
        </div>
        
        <!-- Database Health -->
        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">
                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($health_results['database']['status']); ?>
                Database Health
            </h2>
            
            <?php if (!empty($health_results['database']['issues'])): ?>
                <div class="notice notice-<?php echo $health_results['database']['status'] === 'error' ? 'error' : 'warning'; ?> inline">
                    <ul style="margin: 0.5em 0;">
                        <?php foreach ($health_results['database']['issues'] as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Table', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Status', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Exists', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Columns', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Rows', 'wp-blog-agent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($health_results['database']['tables'] as $table_name => $table_info): ?>
                        <tr>
                            <td><code><?php echo esc_html($table_name); ?></code></td>
                            <td>
                                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($table_info['status']); ?>
                                <?php echo esc_html(WP_Blog_Agent_Health_Check::get_status_label($table_info['status'])); ?>
                            </td>
                            <td><?php echo $table_info['exists'] ? '✓' : '✗'; ?></td>
                            <td><?php echo isset($table_info['columns']) ? esc_html($table_info['columns']) : 'N/A'; ?></td>
                            <td><?php echo isset($table_info['rows']) ? esc_html($table_info['rows']) : 'N/A'; ?></td>
                        </tr>
                        <?php if (!empty($table_info['missing_columns'])): ?>
                            <tr>
                                <td colspan="5" style="padding-left: 40px; color: #dc3232;">
                                    Missing columns: <?php echo esc_html(implode(', ', $table_info['missing_columns'])); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- LLM API Health -->
        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">
                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($health_results['llm_api']['status']); ?>
                LLM API Health
            </h2>
            
            <p><strong>Active Provider:</strong> <?php echo esc_html(strtoupper($health_results['llm_api']['active_provider'])); ?></p>
            
            <?php if (!empty($health_results['llm_api']['issues'])): ?>
                <div class="notice notice-<?php echo $health_results['llm_api']['status'] === 'error' ? 'error' : 'warning'; ?> inline">
                    <ul style="margin: 0.5em 0;">
                        <?php foreach ($health_results['llm_api']['issues'] as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Provider', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Status', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Response Time', 'wp-blog-agent'); ?></th>
                        <th><?php echo esc_html__('Message', 'wp-blog-agent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($health_results['llm_api']['providers'] as $provider_name => $provider_info): ?>
                        <tr>
                            <td><strong><?php echo esc_html(strtoupper($provider_name)); ?></strong></td>
                            <td>
                                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($provider_info['status']); ?>
                                <?php echo esc_html(WP_Blog_Agent_Health_Check::get_status_label($provider_info['status'])); ?>
                            </td>
                            <td>
                                <?php 
                                if (isset($provider_info['response_time'])) {
                                    echo esc_html($provider_info['response_time']) . ' ms';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($provider_info['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #72aee6;">
                <p style="margin: 0;"><strong>Note:</strong> API tests generate minimal content to verify connectivity. This may consume a small amount of API credits.</p>
            </div>
        </div>
        
        <!-- Queue Health -->
        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">
                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($health_results['queue']['status']); ?>
                Queue Health
            </h2>
            
            <?php if (!empty($health_results['queue']['issues'])): ?>
                <div class="notice notice-<?php echo $health_results['queue']['status'] === 'error' ? 'error' : 'warning'; ?> inline">
                    <ul style="margin: 0.5em 0;">
                        <?php foreach ($health_results['queue']['issues'] as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                        <?php echo esc_html($health_results['queue']['statistics']['pending']); ?>
                    </div>
                    <div style="color: #666;">Pending Tasks</div>
                </div>
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 24px; font-weight: bold; color: #ffb900;">
                        <?php echo esc_html($health_results['queue']['statistics']['processing']); ?>
                    </div>
                    <div style="color: #666;">Processing Tasks</div>
                </div>
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 24px; font-weight: bold; color: #46b450;">
                        <?php echo esc_html($health_results['queue']['statistics']['completed']); ?>
                    </div>
                    <div style="color: #666;">Completed Tasks</div>
                </div>
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 24px; font-weight: bold; color: #dc3232;">
                        <?php echo esc_html($health_results['queue']['statistics']['failed']); ?>
                    </div>
                    <div style="color: #666;">Failed Tasks</div>
                </div>
                <?php if (isset($health_results['queue']['statistics']['stuck'])): ?>
                    <div style="padding: 15px; background: #fff3cd; border-radius: 4px;">
                        <div style="font-size: 24px; font-weight: bold; color: #856404;">
                            <?php echo esc_html($health_results['queue']['statistics']['stuck']); ?>
                        </div>
                        <div style="color: #666;">Stuck Tasks</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($health_results['queue']['statistics']['failure_rate'])): ?>
                <div style="margin-top: 15px;">
                    <strong>Failure Rate:</strong> <?php echo esc_html($health_results['queue']['statistics']['failure_rate']); ?>%
                </div>
            <?php endif; ?>
            
            <?php if (isset($health_results['queue']['cron'])): ?>
                <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid <?php echo $health_results['queue']['cron']['scheduled'] ? '#46b450' : '#dc3232'; ?>;">
                    <p style="margin: 0;">
                        <strong>Queue Processing Cron:</strong>
                        <?php if ($health_results['queue']['cron']['scheduled']): ?>
                            Scheduled - Next run: <?php echo esc_html($health_results['queue']['cron']['next_run']); ?>
                            (<?php echo esc_html($health_results['queue']['cron']['time_until']); ?>)
                        <?php else: ?>
                            Not scheduled
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Version & Updates -->
        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">
                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($health_results['version']['status']); ?>
                Version Information
            </h2>
            
            <?php if (!empty($health_results['version']['issues'])): ?>
                <div class="notice notice-<?php echo $health_results['version']['status'] === 'error' ? 'error' : 'warning'; ?> inline">
                    <ul style="margin: 0.5em 0;">
                        <?php foreach ($health_results['version']['issues'] as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <table class="widefat" style="margin-top: 15px;">
                <tbody>
                    <tr>
                        <td style="width: 200px;"><strong>Plugin Version</strong></td>
                        <td><?php echo esc_html($health_results['version']['current_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database Version</strong></td>
                        <td><?php echo esc_html($health_results['version']['db_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version</strong></td>
                        <td><?php echo esc_html($health_results['version']['wordpress_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version</strong></td>
                        <td><?php echo esc_html($health_results['version']['php_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Needs Upgrade</strong></td>
                        <td>
                            <?php if ($health_results['version']['needs_upgrade']): ?>
                                <span style="color: #dc3232;">Yes</span>
                            <?php else: ?>
                                <span style="color: #46b450;">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Image Generation Health -->
        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">
                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($health_results['image_generation']['status']); ?>
                Image Generation Health
            </h2>
            
            <?php if (!empty($health_results['image_generation']['issues'])): ?>
                <div class="notice notice-<?php echo $health_results['image_generation']['status'] === 'error' ? 'error' : 'warning'; ?> inline">
                    <ul style="margin: 0.5em 0;">
                        <?php foreach ($health_results['image_generation']['issues'] as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <table class="widefat" style="margin-top: 15px;">
                <tbody>
                    <tr>
                        <td style="width: 250px;"><strong>Auto Generate Enabled</strong></td>
                        <td>
                            <?php if ($health_results['image_generation']['auto_generate_enabled']): ?>
                                <span style="color: #46b450;">✓ Yes</span>
                            <?php else: ?>
                                <span style="color: #666;">✗ No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>API Key Configured</strong></td>
                        <td>
                            <?php if ($health_results['image_generation']['api_key_configured']): ?>
                                <span style="color: #46b450;">✓ Yes</span>
                            <?php else: ?>
                                <span style="color: #dc3232;">✗ No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Upload Directory Writable</strong></td>
                        <td>
                            <?php if ($health_results['image_generation']['upload_dir_writable']): ?>
                                <span style="color: #46b450;">✓ Yes</span>
                            <?php else: ?>
                                <span style="color: #dc3232;">✗ No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (isset($health_results['image_generation']['api_test'])): ?>
                        <tr>
                            <td><strong>API Test Status</strong></td>
                            <td>
                                <?php echo WP_Blog_Agent_Health_Check::get_status_icon($health_results['image_generation']['api_test']['status']); ?>
                                <?php echo esc_html($health_results['image_generation']['api_test']['message']); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #72aee6;">
                <p style="margin: 0;"><strong>Note:</strong> Full image generation API tests are skipped to avoid unnecessary API costs. Only configuration and key format are validated.</p>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;">
                <span class="dashicons dashicons-info"></span>
                System Information
            </h2>
            
            <table class="widefat">
                <tbody>
                    <tr>
                        <td style="width: 250px;"><strong>Server Software</strong></td>
                        <td><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>MySQL Version</strong></td>
                        <td><?php global $wpdb; echo esc_html($wpdb->db_version()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Memory Limit</strong></td>
                        <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Max Execution Time</strong></td>
                        <td><?php echo esc_html(ini_get('max_execution_time')); ?> seconds</td>
                    </tr>
                    <tr>
                        <td><strong>Max Upload Size</strong></td>
                        <td><?php echo esc_html(ini_get('upload_max_filesize')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Post Max Size</strong></td>
                        <td><?php echo esc_html(ini_get('post_max_size')); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
    </div>
</div>

<style>
    .health-check-container .card h2 {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .health-check-container .card h2 .dashicons {
        font-size: 24px;
        width: 24px;
        height: 24px;
    }
    
    .health-check-container .widefat td {
        padding: 10px;
    }
    
    .health-check-container .widefat tbody tr:nth-child(even) {
        background: #f9f9f9;
    }
</style>
