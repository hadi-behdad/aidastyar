<?php
// modules/otp/otp-ajax.php
if (!defined('ABSPATH')) exit;

/**
 * ارسال کد OTP
 */
function send_otp_request() {
    try {
        $mobile = sanitize_text_field($_POST['mobile']);
        $is_sandbox = (defined('OTP_ENV') && OTP_ENV === 'sandbox');
        $is_bypass = (defined('OTP_ENV') && OTP_ENV === 'bypass');

        // Rate Limit (bypass در حالت sandbox)
        if (!$is_sandbox && !$is_bypass) {
            $rate_check = OTP_Handler::check_rate_limit($mobile);
            if (is_wp_error($rate_check)) {
                throw new Exception($rate_check->get_error_message());
            }
        }

        if (empty($mobile) || !preg_match('/^09[0-9]{9}$/', $mobile)) {
            throw new Exception('شماره موبایل باید با 09 شروع شود و 11 رقم باشد (مثال: 09123456789)');
        }

        $otp_code = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $transient_name = 'otp_' . $mobile;
        set_transient($transient_name, $otp_code, 10 * MINUTE_IN_SECONDS);

        // در حالت sandbox و bypass، کد را برگردان
        if ($is_sandbox || $is_bypass) {
            wp_send_json_success([
                'message' => 'کد تایید ارسال شد',
                'debug_code' => $otp_code,
                'mobile' => $mobile,
                'is_test' => true
            ]);
            return;
        }

        // ارسال واقعی SMS
        $response = wp_remote_post('https://api.sms.ir/v1/send/verify', [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'text/plain',
                'x-api-key' => SMS_API_KEY
            ],
            'body' => json_encode([
                'mobile' => $mobile,
                'templateId' => SMS_TEMPLATE_ID,
                'parameters' => [
                    ['name' => 'Code', 'value' => $otp_code]
                ]
            ])
        ]);

        error_log('📤 OTP API REQUEST: Request => ' . print_r([
            'url' => 'https://api.sms.ir/v1/send/verify',
            'headers' => ['x-api-key' => '...' . substr(SMS_API_KEY, -4)],
            'body' => ['mobile' => $mobile, 'templateId' => SMS_TEMPLATE_ID]
        ], true));

        if (is_wp_error($response)) {
            throw new Exception('خطا در ارتباط با سرویس پیامک: ' . $response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        error_log("📥 OTP API RESPONSE: HTTP Code={$http_code}, Body={$body}");

        if ($http_code != 200) {
            throw new Exception("خطای سرور پیامک (کد {$http_code})");
        }

        $data = json_decode($body, true);
        if (!$data) {
            throw new Exception('پاسخ نامعتبر از سرور: ' . $body);
        }

        if (isset($data['status']) && $data['status'] == 1) {
            wp_send_json_success(['message' => 'کد تایید ارسال شد']);
        } else {
            $error = $data['message'] ?? 'خطای ناشناخته';
            throw new Exception($error);
        }
    } catch (Exception $e) {
        error_log('❌ OTP ERROR: Mobile=' . $mobile . ', Error: ' . $e->getMessage());
        wp_send_json_error('خطا در ارسال کد: ' . $e->getMessage());
    }
}
add_action('wp_ajax_send_otp', 'send_otp_request');
add_action('wp_ajax_nopriv_send_otp', 'send_otp_request');

/**
 * تایید کد OTP
 */
function verify_otp_request() {
    try {
        // دریافت و بررسی ورودی‌ها
        $mobile = sanitize_text_field($_POST['mobile']);
        $otp_code = sanitize_text_field($_POST['otp_code']);
        $referral_code = isset($_POST['referral_code']) ? sanitize_text_field($_POST['referral_code']) : '';

        error_log("🔐 OTP VERIFY START: Mobile={$mobile}, OTP={$otp_code}, ReferralCode={$referral_code}");

        if (empty($mobile) || empty($otp_code)) {
            throw new Exception('شماره موبایل و کد تایید الزامی است');
        }

        // بررسی کد OTP از transient
        $transient_name = 'otp_' . $mobile;
        $stored_otp = get_transient($transient_name);

        if ($stored_otp === false) {
            throw new Exception('کد تایید منقضی شده است. لطفاً مجدداً درخواست کنید.');
        }

        if ($stored_otp != $otp_code) {
            // مدیریت تلاش‌های ناموفق
            $fail_key = 'otp_fails_' . md5($mobile . $_SERVER['REMOTE_ADDR']);
            $fails = (int) get_transient($fail_key);
            set_transient($fail_key, $fails + 1, 15 * MINUTE_IN_SECONDS);

            if ($fails >= 5) {
                throw new Exception('تعداد تلاش‌های شما به حداکثر رسیده است. لطفاً بعداً تلاش کنید.');
            }

            throw new Exception('کد تایید نامعتبر است');
        }

        // کد صحیح است، کاربر را پیدا یا ایجاد کن
        $user = get_user_by('login', $mobile);
        $is_new_user = false;

        if (!$user) {
            // ✅ ایجاد کاربر جدید
            $is_new_user = true;
            error_log("👤 [OTP] ایجاد کاربر جدید: {$mobile}");

            $userdata = array(
                'user_login' => $mobile,
                'user_pass' => wp_generate_password(12, true, true),
                'role' => 'customer',
                'display_name' => $mobile
            );

            $user_id = wp_insert_user($userdata);

            if (is_wp_error($user_id)) {
                error_log("❌ [OTP] خطا در ایجاد کاربر: " . $user_id->get_error_message());
                throw new Exception('خطا در ایجاد حساب کاربری: ' . $user_id->get_error_message());
            }

            // ✅ ذخیره شماره موبایل
            update_user_meta($user_id, 'mobile', $mobile);
            error_log("✅ [OTP] کاربر جدید ایجاد شد: user_id={$user_id}");

            // ✅ پردازش کد معرف برای کاربر جدید
            if (!empty($referral_code)) {
                error_log("🔗 [REFERRAL] شروع پردازش کد معرف: {$referral_code} برای کاربر {$user_id}");

                if (class_exists('AI_Assistant_Referral_System')) {
                    $referral_system = AI_Assistant_Referral_System::get_instance();
                    $referral_result = $referral_system->register_referral($user_id, $referral_code);

                    if ($referral_result) {
                        error_log("✅ [REFERRAL] کد معرف با موفقیت ثبت شد");
                    } else {
                        error_log("⚠️ [REFERRAL] کد معرف ثبت نشد (ممکن است نامعتبر باشد)");
                    }
                } else {
                    error_log("❌ [REFERRAL] کلاس AI_Assistant_Referral_System یافت نشد");
                }
            }

            // ✅ دریافت آبجکت کاربر
            $user = get_user_by('id', $user_id);

        } else {
            // ✅ کاربر موجود
            $user_id = $user->ID;
            error_log("✅ [OTP] کاربر موجود وارد شد: user_id={$user_id}");
        }

        // ✅ بررسی نهایی
        if (!$user || !$user_id) {
            error_log("❌ [OTP] خطای حیاتی: کاربر معتبر نیست");
            throw new Exception('خطا در پردازش حساب کاربری');
        }

        // ✅ لاگین کاربر
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // ✅ سشن WooCommerce
        if (function_exists('wc_set_customer_auth_cookie')) {
            wc_set_customer_auth_cookie($user_id);
        }

        do_action('wp_login', $user->user_login, $user);

        // ✅ پاک کردن transient
        delete_transient($transient_name);

        // ✅ تعیین صفحه بازگشت
        $redirect_url = home_url('/my-account');

        if (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
            $redirect_url = esc_url_raw($_POST['redirect_to']);
        }

        error_log("✅ [OTP] لاگین موفق: user_id={$user_id}, redirect={$redirect_url}");

        wp_send_json_success([
            'message' => $is_new_user ? 'حساب کاربری شما با موفقیت ایجاد شد' : 'خوش آمدید',
            'redirect_url' => $redirect_url,
            'user_id' => $user_id,
            'is_new_user' => $is_new_user
        ]);

    } catch (Exception $e) {
        error_log('❌ [OTP VERIFY ERROR]: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_verify_otp', 'verify_otp_request');
add_action('wp_ajax_nopriv_verify_otp', 'verify_otp_request');

/**
 * Force Logout Handler
 */
add_action('wp_ajax_force_logout', 'force_logout_handler');
add_action('wp_ajax_nopriv_force_logout', 'force_logout_handler');

function force_logout_handler() {
    check_ajax_referer('custom_logout_nonce', 'security');

    $session = WP_Session_Tokens::get_instance(get_current_user_id());
    $session->destroy_all();

    wp_clear_auth_cookie();
    wp_set_current_user(0);

    if (isset($_COOKIE)) {
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'wordpress_') !== false || strpos($name, 'wp-settings-') !== false) {
                unset($_COOKIE[$name]);
                setcookie($name, '', time() - 3600, '/');
                setcookie($name, '', time() - 3600, '/', COOKIE_DOMAIN);
            }
        }
    }

    nocache_headers();
    wp_send_json_success(['redirect' => home_url()]);
}
