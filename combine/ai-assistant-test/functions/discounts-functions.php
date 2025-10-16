<?php
/* /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/discounts-functions.php */
if (!defined('ABSPATH')) exit;


// بارگذاری سیستم مدیریت تخفیف Front-end
require_once get_template_directory() . '/inc/admin/class-discount-frontend-admin.php';

// ایجاد خودکار صفحه مدیریت تخفیف‌ها
function create_discount_admin_page() {
    $page_slug = 'management-discounts';
    
    // بررسی وجود صفحه
    $page = get_page_by_path($page_slug);
    
    if (!$page) {
        // ایجاد صفحه جدید
        $page_id = wp_insert_post([
            'post_title' => 'مدیریت کدهای تخفیف',
            'post_name' => $page_slug,
            'post_content' => '[discount_codes_admin]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ]);
        
        if ($page_id && !is_wp_error($page_id)) {
            update_option('ai_discount_admin_page_id', $page_id);
        }
    }
}

// اجرای ایجاد صفحه هنگام فعال سازی تم
add_action('after_switch_theme', 'create_discount_admin_page');
add_action('init', 'create_discount_admin_page');


// هندلر اعتبارسنجی کد تخفیف
add_action('wp_ajax_validate_discount_code', 'handle_validate_discount_code');
add_action('wp_ajax_nopriv_validate_discount_code', 'handle_validate_discount_code');

function handle_validate_discount_code() {
    check_ajax_referer('discount_frontend_admin_nonce', 'nonce');
    
    $discount_code = sanitize_text_field($_POST['discount_code']);
    $service_id = sanitize_text_field($_POST['service_id']);
    $user_id = get_current_user_id();
    
    $result = AI_Assistant_Discount_Manager::validate_discount($discount_code, $service_id, $user_id);
    
    if ($result['valid']) {
        // دریافت قیمت سرویس از Service Manager موجود
        $service_manager = AI_Assistant_Service_Manager::get_instance();
        $original_price = $service_manager->get_service_price($service_id);
        
        $final_price = AI_Assistant_Discount_Manager::calculate_discounted_price(
            $original_price, 
            $result['discount']
        );
        
        wp_send_json_success([
            'message' => $result['message'],
            'original_price' => $original_price,
            'discount_amount' => $original_price - $final_price,
            'final_price' => $final_price,
            'discount_type' => $result['discount']->type,
            'discount_value' => $result['discount']->amount
        ]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}