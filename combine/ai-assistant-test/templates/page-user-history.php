<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/templates/page-user-history.php-->
<?php
/**
 * Template Name: تاریخچه سرویس‌های من
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$current_user_id = get_current_user_id();
$history_manager = AI_Assistant_History_Manager::get_instance();
$history = $history_manager->get_user_history($current_user_id, 10);
?>

<div class="ai-history-page">
    <div class="ai-history-header">
        <h1><span class="dashicons dashicons-clock"></span> تاریخچه سرویس‌های من</h1>
        <div class="ai-history-actions">
            <a href="<?php echo esc_url(home_url('/ai-dashboard')); ?>" class="ai-back-button">
                <span class="dashicons dashicons-arrow-right-alt"></span> بازگشت به داشبورد
            </a>
        </div>
    </div>

    <div class="ai-history-container">
        <?php if (!empty($history)) : ?>
            <div class="ai-history-table-responsive">
                <table class="ai-history-table">
                    <thead>
                        <tr>
                            <th>نام سرویس</th>
                            <th>تاریخ استفاده</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $item) : 
                             
                            $service_id = get_post_meta($item->ID, 'service_id', true); 
                            $service_info = AI_Assistant_Service_Manager::get_instance()->get_service($service_id);
                            $service_name = esc_attr($service_info['name']);
                            $output_url = home_url('/service-output/' . $item->ID);
                            $delete_url = add_query_arg([
                                'delete_history' => $item->ID,
                                '_wpnonce' => wp_create_nonce('delete_history_' . $item->ID),
                            ], get_permalink());
                           
                          
                        ?>
                            <tr data-history-id="<?php echo esc_attr($item->ID); ?>">
                                <td >
                                    <div class="ai-service-info">
                                        <?php if ($service_info && isset($service_info['icon'])) : ?>
                                            <span class="dashicons <?php echo esc_attr($service_info['icon']); ?>"></span>
                                            <span><?php echo esc_attr($service_info['name']); ?></span>
                                        <?php endif; ?>
                                        
                                    </div>
                                </td>
                                <td >
                                    <?php echo esc_html(get_the_date('j F Y - H:i', $item)); ?>
                                </td>
                                <td >
                                    <span class="ai-status-badge ai-status-completed">تکمیل شده</span>
                                </td>
                                <td class="ai-history-actions" >
                                    <a href="<?php echo esc_url($output_url); ?>" class="ai-view-button" target="_blank" title="مشاهده نتیجه">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <?php /*
                                    <a href="#" class="ai-delete-button delete-history" 
                                                                       data-nonce="<?php echo esc_attr(wp_create_nonce('delete_history_' . $item->ID)); ?>" 
                                                                       data-id="<?php echo esc_attr($item->ID); ?>" 
                                                                       title="حذف از تاریخچه">
                                                                        <span class="dashicons dashicons-trash"></span>
                                                                    </a>
                                    */ ?>
                                    <a href="<?php echo esc_url(home_url('/service/' . $service_id . '/')); ?>" class="ai-repeat-button" title="استفاده مجدد">
                                        <span class="dashicons dashicons-update"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ai-history-pagination">
                <?php echo paginate_links([
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => ceil(wp_count_posts('ai_service_history')->publish / 10),
                    'prev_text' => __('&laquo; قبلی'),
                    'next_text' => __('بعدی &raquo;'),
                ]); ?>
            </div>
        <?php else : ?>
            <div class="ai-history-empty">
                <div class="ai-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <h3>تاریخچه شما خالی است</h3>
                    <p>شما هنوز از هیچ سرویسی استفاده نکرده‌اید.</p>
                    <a href="<?php echo esc_url(home_url('/ai-services')); ?>" class="ai-button">
                        شروع به استفاده از سرویس‌ها
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// اسکریپت AJAX برای حذف
wp_enqueue_script('history-ajax', get_template_directory_uri() . '/assets/js/history-ajax.js', ['jquery'], null, true);
wp_localize_script('history-ajax', 'history_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('history_ajax_nonce'),
    'confirm_delete' => __('آیا از حذف این آیتم از تاریخچه مطمئن هستید؟', 'ai-assistant'),
]);

get_footer();