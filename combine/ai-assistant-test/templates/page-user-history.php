<?php
/**
 * ✅ صفحه تاریخچه سرویسها - با دکمه دانلود توافق نامه
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

if (!defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

get_header();

$current_user_id = get_current_user_id();
$history_manager = AI_Assistant_History_Manager::get_instance();
$logger = AI_Assistant_Logger::get_instance();

// دریافت تاریخچه کاربر
$history = $history_manager->get_user_history($current_user_id, 10);

// دریافت مشاورین
$consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
$user_consultations = $consultation_db->get_user_consultation_requests($current_user_id);

$consultation_statuses = [];
foreach ($user_consultations as $consultation) {
    $consultation_statuses[$consultation->service_history_id] = $consultation;
}

global $wpdb;
$total_items = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}service_history WHERE user_id = %d",
    $current_user_id
));

?>

<div class="ai-history-page">
    <div class="ai-history-header">
        <h1>
            <span class="dashicons dashicons-clock"></span>
            تاریخچه سرویسها
        </h1>
    </div>

    <div class="ai-history-actions">
        <a href="<?php echo esc_url(home_url('ai-dashboard')); ?>" class="ai-back-button">
            <span class="dashicons dashicons-arrow-right-alt"></span>
            بازگشت
        </a>
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
                            
                            $service_info = AI_Assistant_Service_Manager::get_instance()->get_service($item->service_id);
                            $service_name = $service_info['name'] ?? $item->service_id;
                            $output_url = home_url('service-output/' . $item->ID . '/');
                            $delete_url = add_query_arg(['delete_history' => $item->ID, 'wpnonce' => wp_create_nonce('delete_history_' . $item->ID)], get_permalink());
                            
                            $history_status = $item->status;
                            $status_badges = [
                                'queued' => 'ai-status-pending',
                                'processing' => 'ai-status-processing',
                                'completed' => 'ai-status-completed',
                                'consultant_queue' => 'ai-status-pending',
                                'under_review' => 'ai-status-review',
                                'draft' => 'ai-status-draft',
                                'approved' => 'ai-status-approved',
                                'error' => 'ai-status-error'
                            ];
                            
                            $status_texts = [
                                'queued' => 'در صف انتظار',
                                'processing' => 'در حال پردازش',
                                'completed' => 'تکمیل شده',
                                'consultant_queue' => 'در انتظار مشاور',
                                'under_review' => 'در حال بازبینی',
                                'draft' => 'پیشنویس',
                                'approved' => 'تایید شده',
                                'error' => 'خطا در پردازش'
                            ];
                            
                            $badge_class = $status_badges[$history_status] ?? 'ai-status-pending';
                            $status_text = $status_texts[$history_status] ?? 'در صف انتظار';
                            ?>
                            <tr data-history-id="<?php echo esc_attr($item->ID); ?>">
                                <td>
                                    <div class="ai-service-info">
                                        <?php if ($service_info && isset($service_info['icon'])) : ?>
                                            <span class="dashicons <?php echo esc_attr($service_info['icon']); ?>"></span>
                                        <?php endif; ?>
                                        <span><?php echo esc_html($service_name); ?></span>
                                    </div>
                                </td>

                                <td>
                                    <?php echo esc_html(date_i18n('j F Y - H:i', strtotime($item->created_at))); ?>
                                </td>

                                <td>
                                    <span class="ai-status-badge <?php echo esc_attr($badge_class); ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                    <?php if ($history_status === 'approved' && isset($consultation_statuses[$item->ID]) && isset($consultation_statuses[$item->ID]->reviewed_at)) : ?>
                                        <br>
                                        <small class="ai-consultation-date">
                                            <?php echo esc_html(date_i18n('j F Y', strtotime($consultation_statuses[$item->ID]->reviewed_at))); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="ai-history-actions">
                                        <!-- دکمه مشاهده -->
                                        <a href="<?php echo esc_url($output_url); ?>" 
                                           class="ai-view-button" 
                                           target="_blank" 
                                           title="مشاهده نتیجه">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>

                                        <!-- دکمه تکرار درخواست -->
                                        <a href="<?php echo esc_url(home_url('service/' . $item->service_id . '/')); ?>" 
                                           class="ai-repeat-button" 
                                           title="تکرار درخواست">
                                            <span class="dashicons dashicons-update"></span>
                                        </a>

                                        <!-- ✅ دکمه دانلود توافق نامه -->
                                        <?php
                                        
                                        $terms_db = Terms_Acceptance_DB::get_instance();
                                        $acceptance = $terms_db->get_acceptance_by_service_history_id($item->ID);
                                        
                                        if ($acceptance) {
                                        }
                                        
                                        if ($acceptance && !empty($acceptance->archive_file_path)) {
                                            $terms_url = esc_url($acceptance->archive_file_path);
                                            ?>
                                            <a href="<?php echo $terms_url; ?>" 
                                               class="ai-terms-button" 
                                               target="_blank" 
                                               rel="noopener noreferrer"
                                               title="دانلود توافق نامه">
                                                <span class="dashicons dashicons-download"></span>
                                            </a>
                                            <?php
                                        } else {
                                            error_log('🔍 [TERMS] ❌ NO button - acceptance: ' . ($acceptance ? 'found but no path' : 'not found'));
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- صفحهبندی -->
            <div class="ai-history-pagination">
                <?php echo wp_kses_post(paginate_links([
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => ceil($total_items / 10),
                    'prev_text' => '« قبلی',
                    'next_text' => 'بعدی »'
                ])); ?>
            </div>

        <?php else : ?>
            <div class="ai-history-empty">
                <div class="ai-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <h3>هنوز سرویسی استفاده نکردهاید</h3>
                    <p>برای شروع، یکی از سرویسها را انتخاب کنید</p>
                    <a href="<?php echo esc_url(home_url('ai-services')); ?>" class="ai-button">
                        بروز به سرویسها
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
