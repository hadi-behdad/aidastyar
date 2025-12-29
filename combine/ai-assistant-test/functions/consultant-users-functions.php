<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/consultant-users-functions.php
 */

if (!defined('ABSPATH')) exit;

// دریافت لیست مشاورین تغذیه از کلاس جدید
function get_nutrition_consultants() {
    // بررسی nonce
    if (!wp_verify_nonce($_POST['security'], 'ai_assistant_nonce')) {
        wp_die('خطای امنیتی');
    }
    
    // استفاده از کلاس جدید برای دریافت مشاورین
    $diet_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $consultants = $diet_db->get_active_consultants();
    
    wp_send_json_success([
        'consultants' => $consultants
    ]);
}
add_action('wp_ajax_get_nutrition_consultants', 'get_nutrition_consultants');
add_action('wp_ajax_nopriv_get_nutrition_consultants', 'get_nutrition_consultants');


/**
 * تابع اصلاح شده: دریافت قیمت سرویس با تخفیف
 * تغییرات: پیام خطای بهتر برای nonce و بازگشت structured error
 */
function get_service_price_with_discount() {
    // بررسی nonce - اگر منقضی شده، خطا برمیگردونه
    if (!wp_verify_nonce($_POST['nonce'], 'ai_assistant_nonce')) {
        wp_send_json_error([
            'message' => 'Nonce verification failed'
        ]);
        return;
    }
    
    $service_id = sanitize_text_field($_POST['service_id']);
    $include_consultant_fee = isset($_POST['include_consultant_fee']) && $_POST['include_consultant_fee'] === '1';
    $consultant_fee = isset($_POST['consultant_fee']) ? floatval($_POST['consultant_fee']) : 0;
    
    // دریافت قیمت پایه سرویس
    $base_price = get_diet_service_base_price();
    
    // محاسبه قیمت اصلی (با در نظر گرفتن هزینه مشاور)
    $original_price = $base_price;
    if ($include_consultant_fee && $consultant_fee > 0) {
        $original_price += $consultant_fee;
    }
    
    // اعمال تخفیفهای خودکار
    $discount_result = apply_auto_discounts($service_id, $original_price);
    
    wp_send_json_success([
        'original_price' => $original_price,
        'final_price' => $discount_result['final_price'],
        'discount_amount' => $discount_result['discount_amount'],
        'has_discount' => $discount_result['has_discount'],
        'discount' => $discount_result['discount_data']
    ]);
}

add_action('wp_ajax_get_service_price_with_discount', 'get_service_price_with_discount');
add_action('wp_ajax_nopriv_get_service_price_with_discount', 'get_service_price_with_discount');




/**
 * تابع جدید: Refresh کردن nonce وقتی منقضی شده
 * این تابع یک nonce جدید تولید و برمیگردونه
 */
function refresh_ajax_nonce() {
    wp_send_json_success([
        'nonce' => wp_create_nonce('ai_assistant_nonce')
    ]);
}

add_action('wp_ajax_refresh_ajax_nonce', 'refresh_ajax_nonce');
add_action('wp_ajax_nopriv_refresh_ajax_nonce', 'refresh_ajax_nonce');
