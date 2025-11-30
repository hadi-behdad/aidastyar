<?php
/**
 * AJAX Handlers for Terms Acceptance
 * 
 * به‌روزرسانی شده: استفاده از تابع مرکزی aidastyar_get_terms_with_title()
 */

add_action('wp_ajax_savetermsacceptance', 'ajax_save_terms_acceptance');
add_action('wp_ajax_nopriv_savetermsacceptance', 'ajax_save_terms_acceptance');

function ajax_save_terms_acceptance() {
    error_log('=== AJAX save_terms_acceptance ===');
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aidastyar_terms_nonce')) {
        error_log('نشست نامعتبر است');
        wp_send_json_error(['message' => 'نشست نامعتبر است'], 403);
    }

    if (!is_user_logged_in()) {
        error_log('کاربر وارد نشده');
        wp_send_json_error(['message' => 'لطفاً وارد شوید'], 401);
    }

    $user_id = get_current_user_id();
    $service_id = isset($_POST['serviceid']) ? sanitize_text_field($_POST['serviceid']) : 'diet';

    error_log("user_id: $user_id, service_id: $service_id");

    // ✅ استفاده از تابع مرکزی به جای getfulltermscontent() قبلی
    $terms_content = aidastyar_get_terms_with_title();

    $terms_db = TermsAcceptanceDB::get_instance();
    $acceptance_id = $terms_db->save_acceptance($user_id, $terms_content, $service_id);

    if ($acceptance_id) {
        $acceptance = $terms_db->get_acceptance_by_id($acceptance_id);
        error_log("ذخیره شد با ID: $acceptance_id");
        wp_send_json_success([
            'message' => 'شرایط با موفقیت ثبت شد',
            'acceptance_id' => $acceptance_id,
            'terms_version' => $acceptance->terms_version,
            'accepted_at' => $acceptance->accepted_at
        ]);
    } else {
        error_log('خطا در ثبت پذیرش شرایط');
        wp_send_json_error(['message' => 'خطا در ثبت پذیرش شرایط']);
    }
}

// ❌ تابع getfulltermscontent() حذف شد
// ✅ از aidastyar_get_terms_with_title() در inc/terms-content.php استفاده می‌شود
