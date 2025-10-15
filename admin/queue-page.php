<div class="wrap">
    <h1><?php echo esc_html__('Generation Queue', 'wp-blog-agent'); ?></h1>
    
    <p><?php echo esc_html__('View and manage the post generation queue.', 'wp-blog-agent'); ?></p>
    
    <?php
    // Get queue statistics
    $stats = WP_Blog_Agent_Queue::get_stats();
    ?>
    
    <div class="wp-blog-agent-queue-stats" style="margin: 20px 0;">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; min-width: 150px;">
                <div style="font-size: 24px; font-weight: 600; color: #2271b1;">
                    <?php echo esc_html($stats['pending']); ?>
                </div>
                <div style="color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Pending', 'wp-blog-agent'); ?>
                </div>
            </div>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; min-width: 150px;">
                <div style="font-size: 24px; font-weight: 600; color: #d63638;">
                    <?php echo esc_html($stats['processing']); ?>
                </div>
                <div style="color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Processing', 'wp-blog-agent'); ?>
                </div>
            </div>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; min-width: 150px;">
                <div style="font-size: 24px; font-weight: 600; color: #00a32a;">
                    <?php echo esc_html($stats['completed']); ?>
                </div>
                <div style="color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Completed', 'wp-blog-agent'); ?>
                </div>
            </div>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; min-width: 150px;">
                <div style="font-size: 24px; font-weight: 600; color: #646970;">
                    <?php echo esc_html($stats['failed']); ?>
                </div>
                <div style="color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Failed', 'wp-blog-agent'); ?>
                </div>
            </div>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; min-width: 150px;">
                <div style="font-size: 24px; font-weight: 600; color: #2271b1;">
                    <?php echo esc_html($stats['total']); ?>
                </div>
                <div style="color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Total', 'wp-blog-agent'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div style="margin: 20px 0;">
        <form method="post" style="display: inline;">
            <?php wp_nonce_field('wp_blog_agent_cleanup_queue'); ?>
            <input type="hidden" name="cleanup_queue" value="1">
            <label for="cleanup_days"><?php echo esc_html__('Keep items from last', 'wp-blog-agent'); ?></label>
            <input type="number" name="cleanup_days" id="cleanup_days" value="7" min="1" max="365" style="width: 60px;">
            <?php echo esc_html__('days', 'wp-blog-agent'); ?>
            <button type="submit" class="button" onclick="return confirm('<?php echo esc_js(__('This will delete completed and failed tasks older than the specified days. Continue?', 'wp-blog-agent')); ?>')">
                <?php echo esc_html__('Cleanup Old Tasks', 'wp-blog-agent'); ?>
            </button>
        </form>
        
        <button type="button" class="button" onclick="location.reload()">
            <?php echo esc_html__('Refresh', 'wp-blog-agent'); ?>
        </button>
    </div>
    
    <h2><?php echo esc_html__('Recent Queue Items', 'wp-blog-agent'); ?></h2>
    
    <?php
    $queue_items = WP_Blog_Agent_Queue::get_recent(50);
    
    if (!empty($queue_items)) {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php echo esc_html__('ID', 'wp-blog-agent'); ?></th>
                    <th><?php echo esc_html__('Topic', 'wp-blog-agent'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Status', 'wp-blog-agent'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Trigger', 'wp-blog-agent'); ?></th>
                    <th style="width: 80px;"><?php echo esc_html__('Attempts', 'wp-blog-agent'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Created', 'wp-blog-agent'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Completed', 'wp-blog-agent'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Post', 'wp-blog-agent'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queue_items as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item->id); ?></td>
                        <td>
                            <?php 
                            if ($item->topic_id && $item->topic) {
                                echo esc_html($item->topic);
                            } else if ($item->topic_id) {
                                echo esc_html__('Topic #', 'wp-blog-agent') . esc_html($item->topic_id);
                            } else {
                                echo '<em>' . esc_html__('Random topic', 'wp-blog-agent') . '</em>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $status_badge = '';
                            switch ($item->status) {
                                case 'pending':
                                    $status_badge = '<span style="background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html__('Pending', 'wp-blog-agent') . '</span>';
                                    break;
                                case 'processing':
                                    $status_badge = '<span style="background: #d63638; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html__('Processing', 'wp-blog-agent') . '</span>';
                                    break;
                                case 'completed':
                                    $status_badge = '<span style="background: #00a32a; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html__('Completed', 'wp-blog-agent') . '</span>';
                                    break;
                                case 'failed':
                                    $status_badge = '<span style="background: #646970; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html__('Failed', 'wp-blog-agent') . '</span>';
                                    break;
                            }
                            echo $status_badge;
                            ?>
                        </td>
                        <td><?php echo esc_html(ucfirst($item->trigger)); ?></td>
                        <td><?php echo esc_html($item->attempts); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at))); ?></td>
                        <td>
                            <?php 
                            if ($item->completed_at) {
                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->completed_at)));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($item->post_id) {
                                $edit_link = get_edit_post_link($item->post_id);
                                if ($edit_link) {
                                    echo '<a href="' . esc_url($edit_link) . '">' . esc_html__('View', 'wp-blog-agent') . '</a>';
                                } else {
                                    echo esc_html__('Post #', 'wp-blog-agent') . esc_html($item->post_id);
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php if ($item->status === 'failed' && $item->error_message) : ?>
                        <tr style="background: #fff8e5;">
                            <td colspan="8" style="padding-left: 30px;">
                                <strong><?php echo esc_html__('Error:', 'wp-blog-agent'); ?></strong>
                                <?php echo esc_html($item->error_message); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p>' . esc_html__('No queue items found.', 'wp-blog-agent') . '</p>';
    }
    ?>
</div>
