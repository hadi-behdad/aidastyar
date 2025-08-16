<?php
/**
 * Template Name: نمایش خروجی سرویس
 */

get_header();

// دریافت ID از URL
//$history_id = isset($_GET['history_id']) ? intval($_GET['history_id']) : 0;
$history_id = get_query_var('history_id');
$history_manager = AI_Assistant_History_Manager::get_instance();
$user_id = get_current_user_id();

// بررسی وجود آیتم و مالکیت
if (!$history_id || !$history_manager->is_user_owner($history_id, $user_id)) {
    status_header(404);
    get_template_part(404);
    exit;
}

// دریافت اطلاعات از جدول
global $wpdb;
$table_name = $wpdb->prefix . 'service_history';
$item = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE id = %d",
    $history_id
));

if (!$item) {
    status_header(404);
    get_template_part(404);
    exit;
}

// تنظیم title صفحه
add_filter('document_title_parts', function($title) use ($item) {
    $title['title'] = $item->service_name ?: 'خروجی سرویس';
    return $title;
});

// نمایش محتوا
echo '<div class="ai-service-output-container" style="max-width: 800px; margin: 2rem auto; padding: 1rem;">';
echo '<h1 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">' . esc_html($item->service_name) . '</h1>';
echo '<div class="service-content" style="background: #f9f9f9; padding: 1.5rem; border-radius: 5px; margin-top: 1rem;">';
echo apply_filters('the_content', $item->html_output);
echo '</div>';
echo '</div>';

get_footer();