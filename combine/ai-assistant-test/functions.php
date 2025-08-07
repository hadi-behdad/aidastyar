<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions.php
 * Functions for AI Assistant Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// 1. تنظیمات پایه قالب
function ai_assistant_setup() {
    load_theme_textdomain('ai-assistant', get_template_directory() . '/languages');
    
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    
    // ثبت منوها
    register_nav_menus([
        'primary' => __('منوی اصلی', 'ai-assistant'),
        'footer' => __('منوی فوتر', 'ai-assistant')
    ]);
}
add_action('after_setup_theme', 'ai_assistant_setup');

// 2. بارگذاری فایل‌های جانبی
require_once get_template_directory() . '/inc/class-service-manager.php';
require_once get_template_directory() . '/inc/class-payment-handler.php';


function ai_assistant_load_css() {
    
    // بارگذاری main.css
    wp_enqueue_style(
        'ai-main',
        get_template_directory_uri() . '/assets/css/main.css',
        array(),
        filemtime(get_template_directory() . '/assets/css/main.css')
    );
    
  // بارگذاری dashicons از پوشه core وردپرس
    wp_enqueue_style('dashicons'); // این خط به صورت پیش‌فرض dashicons را از هسته بارگذاری می‌کند
    
    
    // بارگذاری chat.css فقط در صفحه چت
    if (is_page('service')) {
        $service_id = get_query_var('service');
        if ($service_id) {
            $css_path = '/assets/css/services/' . $service_id . '.css';
            if (file_exists(get_template_directory() . $css_path)) {
                wp_enqueue_style(
                    'ai-assistant-' . $service_id . '-css',
                    get_template_directory_uri() . $css_path,
                    array(),
                    wp_get_theme()->get('Version')
                );
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'ai_assistant_load_css');

// 3. بارگذاری سرویس‌ها
foreach (glob(get_template_directory() . '/services/*/functions.php') as $service_file) {
    require_once $service_file;
}

// 4. ایجاد صفحات ضروری
function ai_assistant_create_pages() {
    $pages = [
        'ai-services' => [
            'title' => __('سرویس‌های هوش مصنوعی', 'ai-assistant'),
            'template' => 'templates/services-page.php'
        ],
        'service' => [
            'title' => __('سرویس', 'ai-assistant'),
            'template' => 'templates/single-service.php'
        ]
    ];
    
    foreach ($pages as $slug => $page) {
        if (!get_page_by_path($slug)) {
            wp_insert_post([
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'page_template' => $page['template']
            ]);
        }
    }
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'ai_assistant_create_pages');

// 5. تنظیمات rewrite برای URL سرویس‌ها
function ai_assistant_add_rewrite_rules() {
    add_rewrite_rule('^service/([^/]+)/?', 'index.php?pagename=service&service=$matches[1]', 'top');
}
add_action('init', 'ai_assistant_add_rewrite_rules');

function ai_assistant_add_query_vars($vars) {
    $vars[] = 'service';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_query_vars');

// 6. بارگذاری اسکریپت‌ها و استایل‌ها
function ai_assistant_scripts() {
    // استایل اصلی
    wp_enqueue_style('ai-assistant-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
    
    // اسکریپت اصلی
    wp_enqueue_script(
        'ai-assistant-main', 
        get_template_directory_uri() . '/assets/js/main.js', 
        ['jquery'], 
        wp_get_theme()->get('Version'), 
        true
    );
    
    // داده‌های محلی شده
    wp_localize_script('ai-assistant-main', 'aiAssistantVars', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'themeUrl' => get_template_directory_uri(),
        'nonce' => wp_create_nonce('ai_assistant_nonce'),
        'i18n' => [
            'error' => __('خطا', 'ai-assistant'),
            'loading' => __('در حال پردازش...', 'ai-assistant')
        ]
    ]);
}
add_action('wp_enqueue_scripts', 'ai_assistant_scripts');

// 7. اضافه کردن تب به حساب کاربری ووکامرس
function ai_assistant_add_woocommerce_tab($items) {
    $items['ai-assistant'] = __('دستیار هوش مصنوعی', 'ai-assistant');
    return $items;
}
add_filter('woocommerce_account_menu_items', 'ai_assistant_add_woocommerce_tab');

function ai_assistant_add_endpoint() {
    add_rewrite_endpoint('ai-assistant', EP_ROOT | EP_PAGES);
}
add_action('init', 'ai_assistant_add_endpoint');

function ai_assistant_tab_content() {
    include get_template_directory() . '/templates/dashboard.php';
}
add_action('woocommerce_account_ai-assistant_endpoint', 'ai_assistant_tab_content');

//----------کیف پول
add_action('woocommerce_thankyou', 'ai_wallet_payment_complete');

function ai_wallet_payment_complete($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $user_id = $order->get_user_id();
    if (!$user_id) return;

    $pending_data = get_user_meta($user_id, 'wallet_charge_pending', true);

    if (!$pending_data || $pending_data['status'] !== 'pending') return;

    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        if (strpos($product_name, $pending_data['id']) !== false) {
            // تأیید موفقیت‌آمیز و افزودن اعتبار
            $amount = (float) $pending_data['amount'];
            AI_Assistant_Payment_Handler::get_instance()->add_credit($user_id, $amount);

            // پاک‌سازی متا
            delete_user_meta($user_id, 'wallet_charge_pending');

            // ثبت پیام موفقیت
            wc_add_notice('شارژ کیف پول با موفقیت انجام شد.', 'success');
            break;
        }
    }
}

//------------------------------------

add_action('wp_ajax_get_user_wallet_credit', 'get_user_wallet_credit_callback');
add_action('wp_ajax_nopriv_get_user_wallet_credit', 'get_user_wallet_credit_callback');

function get_user_wallet_credit_callback() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'کاربر وارد نشده است.']);
    }

    $user_id = get_current_user_id();
    if (!class_exists('AI_Assistant_Payment_Handler')) {
        wp_send_json_error(['message' => 'کلاس کیف پول موجود نیست.']);
    }

    $credit = AI_Assistant_Payment_Handler::get_instance()->get_user_credit($user_id);
    wp_send_json_success(['credit' => $credit]);
}

require_once __DIR__ . '/inc/admin/services-admin.php';

//--------------------------------------------------------------


require_once get_template_directory() . '/inc/class-history-manager.php';

add_action('init', function() {
    add_rewrite_rule(
        '^service-output/([0-9]+)/?$',
        'index.php?post_type=ai_service_history&p=$matches[1]',
        'top'
    );
    
    add_rewrite_rule(
        '^service-output/([0-9]+)/?$',
        'index.php?post_type=ai_wallet_history&p=$matches[1]',
        'top'
    );
    
});

add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && isset($query->query['post_type']) && $query->query['post_type'] === 'ai_service_history') {
        $query->set('post_type', 'ai_service_history');
        $query->set('post_status', 'publish');
    }
});

add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && isset($query->query['service-output'])) {
        $query->set('post_type', 'ai_service_history');
    }
});

// بعد از اضافه کردن این کدها، به تنظیمات > پیوندهای یکتا رفته و ذخیره کنید
//---------------------------------------------------------
// حذف آیتم تاریخچه با AJAX
add_action('wp_ajax_delete_history_item', function() {
    check_ajax_referer('history_ajax_nonce', '_wpnonce');

    $post_id = absint($_POST['post_id']);
    $user_id = get_current_user_id();
    $history_manager = AI_Assistant_History_Manager::get_instance();

    if ($history_manager->delete_history_item($post_id, $user_id)) {
        wp_send_json_success('آیتم با موفقیت حذف شد.');
    } else {
        wp_send_json_error('خطا در حذف آیتم.');
    }
});

//---------------------------------------------------------

require_once get_template_directory() . '/inc/class-wallet-history-manager.php';



add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && isset($query->query['post_type']) && $query->query['post_type'] === 'ai_wallet_history') {
        $query->set('post_type', 'ai_wallet_history');
        $query->set('post_status', 'publish');
    }
});

add_action('init', function() {
    add_rewrite_endpoint('service-output', EP_PERMALINK);
});


// اضافه کردن پارامتر logged_in بعد از لاگین موفق
add_filter('login_redirect', function($redirect_to, $request, $user) {
    // اگر redirect_to وجود داشت، آن را حفظ کنید
    if ($redirect_to) {
        $redirect_to = remove_query_arg('logged_in', $redirect_to);
        $redirect_to = add_query_arg('logged_in', '1', $redirect_to);
        return $redirect_to;
    }
    
    // در غیر این صورت به صفحه اصلی با پارامتر logged_in ریدایرکت شود
    return add_query_arg('logged_in', '1', home_url());
}, 10, 3);

// اجازه دادن به پارامتر logged_in در وردپرس
add_filter('query_vars', function($vars) {
    $vars[] = 'logged_in';
    return $vars;
});

require_once get_template_directory() . '/modules/otp/class-otp-handler.php';

// تغییر مسیر صفحه ورود پیشفرض
add_action('init', function() {
    global $pagenow;
    
    // فقط برای کاربران غیروارد شده اعمال شود و فقط زمانی که به wp-login.php دسترسی دارند
    // و نه زمانی که به wp-admin دسترسی دارند
    if (!is_user_logged_in() && 'wp-login.php' == $pagenow && !isset($_POST['wp-submit'])) {
        // اگر در حال دسترسی به wp-admin نیست، به otp-login ریدایرکت شود
        if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false) {
            wp_redirect(home_url('/otp-login'));
            exit();
        }
    }
});

// اضافه کردن این کد در انتهای فایل
add_filter('logout_url', 'custom_logout_url', 10, 2);
function custom_logout_url($logout_url, $redirect) {
    return wp_nonce_url(home_url('/wp-login.php?action=logout&redirect_to=' . home_url()), 'log-out');
}

add_action('wp_logout', 'clear_auth_cookies_completely');
function clear_auth_cookies_completely() {
    wp_clear_auth_cookie();
    
    if (isset($_COOKIE)) {
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'wordpress') !== false) {
                unset($_COOKIE[$name]);
                setcookie($name, '', time() - 3600, '/', COOKIE_DOMAIN);
            }
        }
    }
    
    nocache_headers();
}

function ai_assistant_load_test_scripts() {
    error_log('🔧 [DEBUG] ai_assistant_load_test_scripts() executed');
    
    // همیشه در محیط تست اسکریپت را لود کنیم، اما رفتار آن با کلیک دکمه کنترل شود
    if (defined('OTP_ENV') && OTP_ENV === 'sandbox') {
        error_log('🔧 [DEBUG] Sandbox environment detected - loading auto-fill.js');
        
        $js_path = '/assets/js/auto-fill.js';
        $full_path = get_template_directory() . $js_path;
        
        if (file_exists($full_path)) {
            error_log('🔧 [DEBUG] File exists: ' . $full_path);
            wp_enqueue_script(
                'ai-assistant-auto-fill',
                get_template_directory_uri() . $js_path,
                array('ai-assistant-main'),
                filemtime($full_path),
                true
            );
            
            // اضافه کردن متغیرهای مورد نیاز برای diet.js
            wp_localize_script('ai-assistant-auto-fill', 'aiAssistantVars', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_assistant_nonce'),
                'i18n' => [
                    'error' => __('خطا', 'ai-assistant'),
                    'loading' => __('در حال پردازش...', 'ai-assistant')
                ]
            ]);
        } else {
            error_log('🔧 [ERROR] File does NOT exist: ' . $full_path);
        }
    } else {
        error_log('🔧 [DEBUG] Production environment or OTP_ENV not set');
    }
}
add_action('wp_enqueue_scripts', 'ai_assistant_load_test_scripts', 20);

function enqueue_diet_form_scripts() {
    wp_enqueue_script('diet-form-script', get_template_directory_uri() . '/assets/js/services/diet/form-steps.js', array(), '1.0', true);

    // انتقال متغیرهای PHP به جاوااسکریپت
    wp_localize_script('diet-form-script', 'wpVars', array(
        'themeBasePath' => (OTP_ENV === 'sandbox') 
            ? '/wp-content/themes/ai-assistant-test' 
            : '/wp-content/themes/ai-assistant',
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_diet_form_scripts');

function enqueue_aidastyar_loader() {
    wp_enqueue_script(
        'aidastyar-loader',
        get_template_directory_uri() . '/assets/js/components/aidastyar-loader.js',
        array(),
        filemtime(get_template_directory() . '/assets/js/components/aidastyar-loader.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_aidastyar_loader');