<?php
// ✅ اصلاح شده
add_action('wp_ajax_save_terms_acceptance', 'ajax_save_terms_acceptance');
add_action('wp_ajax_nopriv_save_terms_acceptance', 'ajax_save_terms_acceptance');  // ✅ nopriv اضافه کن!

function ajax_save_terms_acceptance() {
    error_log('=== AJAX save_terms_acceptance ===');
    
    // بررسی nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aidastyar_terms_nonce')) {
        error_log('نشست نامعتبر است');
        wp_send_json_error(['message' => 'نشست نامعتبر است'], 403);
    }

    // ✅ برای کاربران غیر لاگین شده اجازه دهید
    if (!is_user_logged_in()) {
        error_log('کاربر وارد نشده - اجازه بدون لاگین');
        // این مرحله را بپذیریم بدون لاگین
        // یا ذخیره کنیم برای بعد از لاگین
        
        wp_send_json_success([
            'message' => 'شرایط ثبت شد (پس از لاگین ثبت خواهد شد)',
            'is_guest' => true
        ]);
        return;
    }

    $user_id = get_current_user_id();
    $service_id = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : 'diet';

    error_log("user_id: $user_id, service_id: $service_id");

    $terms_content = aidastyar_get_terms_with_title();
    $terms_db = TermsAcceptanceDB::get_instance();
    $acceptance_id = $terms_db->save_acceptance($user_id, $terms_content, $service_id);

    if ($acceptance_id) {
        error_log("ذخیره شد با ID: $acceptance_id");
        wp_send_json_success([
            'message' => 'شرایط با موفقیت ثبت شد',
            'acceptance_id' => $acceptance_id,
            'is_guest' => false
        ]);
    } else {
        error_log('خطا در ثبت پذیرش شرایط');
        wp_send_json_error(['message' => 'خطا در ثبت پذیرش شرایط']);
    }
}
?>
