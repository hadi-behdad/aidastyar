<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/templates/single-ai_service_history.php-->
<?php
/**
 * Template Name: نمایش خروجی سرویس
 */

get_header();

global $wp_query;

// بررسی وجود پست
if (!isset($wp_query->post) || $wp_query->post->post_type !== 'ai_service_history') {
    status_header(404);
    get_template_part(404);
    exit;
}


$post_id = $wp_query->post->ID;
$history_manager = AI_Assistant_History_Manager::get_instance();
$user_id = get_current_user_id();

// بررسی مالکیت
if (!$history_manager->is_user_owner($post_id, $user_id)) {
    wp_redirect(home_url());
    exit;
}

// تنظیم title صفحه
add_filter('document_title_parts', function($title) use ($post_id) {
    $service_name = get_post_meta($post_id, 'service_name', true);
    $title['title'] = $service_name ?: 'خروجی سرویس';
    return $title;
});

// نمایش محتوا
echo '<div class="ai-service-output-container" style="max-width: 800px; margin: 2rem auto; padding: 1rem;">';
echo '<h1 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">' . esc_html(get_post_meta($post_id, 'service_name', true)) . '</h1>';
echo '<div class="service-content" style="background: #f9f9f9; padding: 1.5rem; border-radius: 5px; margin-top: 1rem;">';
echo apply_filters('the_content', $wp_query->post->post_content);
echo '</div>';
echo '</div>';




get_footer();