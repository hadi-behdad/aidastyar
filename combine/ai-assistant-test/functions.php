<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define('DISABLE_WP_CRON', true);


/**
 * Fix for ob_end_flush() notice
 * Replaces wp_ob_end_flush_all() with a safer version
 */
remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

add_action( 'shutdown', function() {
    while ( @ob_end_flush() );
} );


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



require_once get_template_directory() . '/inc/ai-assistant-api/ai-assistant-api.php';


require_once get_template_directory() . '/inc/jobs/job-monitoring.php';
require_once get_template_directory() . '/inc/jobs/process-requests-job.php';
require_once get_template_directory() . '/inc/jobs/ai-article-generator.php';

require_once get_template_directory() . '/inc/jobs/class-ai-job-queue.php';
//AI_Job_Queue::get_instance();

require_once get_template_directory() . '/inc/class-service-db.php';
require_once get_template_directory() . '/inc/class-service-manager.php';
require_once get_template_directory() . '/inc/class-payment-handler.php';

// درگاه‌های پرداخت (جدید)
require_once get_template_directory() . '/inc/payment-gateways/interface-payment-gateway.php';
require_once get_template_directory() . '/inc/payment-gateways/class-payment-gateway-manager.php';
require_once get_template_directory() . '/inc/payment-gateways/gateways/class-zarinpal-gateway.php';
require_once get_template_directory() . '/inc/payment-gateways/gateways/class-zibal-gateway.php';


require_once get_template_directory() . '/inc/class-history-manager.php';
// سپس کلاس‌های جدید مشاور
require_once get_template_directory() . '/inc/class-email-template.php';
require_once get_template_directory() . '/inc/class-notification-manager.php';
require_once get_template_directory() . '/inc/class-diet-consultation-db.php';
require_once get_template_directory() . '/inc/class-nutrition-consultant-manager.php';



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
// foreach (glob(get_template_directory() . '/services/*/functions.php') as $service_file) {
//     require_once $service_file;
// }

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
        'user_id' => get_current_user_id(),
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

add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'mail.aidastyar.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 465;
    $phpmailer->Username   = 'info@aidastyar.com';
    $phpmailer->Password   = '373565@Hatef';
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->From       = 'info@aidastyar.com';
    $phpmailer->FromName   = 'Aidastyar';
});





// اضافه کردن endpoint جدید برای دریافت قیمت سرویس
add_action('wp_ajax_get_diet_service_price', 'get_diet_service_price_callback');
add_action('wp_ajax_nopriv_get_diet_service_price', 'get_diet_service_price_callback');

function get_diet_service_price_callback() {
    if (!class_exists('AI_Assistant_Service_Manager')) {
        wp_send_json_error(['message' => 'کلاس مدیریت سرویس موجود نیست.']);
    }

    $service_id = 'diet';
    $service_manager = AI_Assistant_Service_Manager::get_instance();
    $price = $service_manager->get_service_price($service_id);

    if ($price === false) {
        wp_send_json_error(['message' => 'قیمت سرویس یافت نشد.']);
    }

    wp_send_json_success(['price' => $price]);
}

// مطمئن شوید endpoint موجودی کاربر نیز وجود دارد
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


// require_once get_template_directory() . '/inc/class-history-manager.php';


function ai_service_history_output() {
    add_rewrite_rule(
        '^service-output/([0-9]+)/?$',
        'index.php?pagename=service-output&history_id=$matches[1]',
        'top'
    );
}
add_action('init', 'ai_service_history_output');

function ai_service_add_query_vars( $vars ) {
    $vars[] = 'history_id';
    return $vars;
}
add_filter( 'query_vars', 'ai_service_add_query_vars' );




// در functions.php
add_action('init', 'add_service_output_rewrite_rule');
function add_service_output_rewrite_rule() {
    add_rewrite_rule(
        '^service-output/([0-9]+)/?$',
        'index.php?pagename=service-output&history_id=$matches[1]',
        'top'
    );
    
    // فلاش rewrite rules (فقط یک بار اجرا کنید)
    if (get_option('service_output_rewrite_flushed') != '1') {
        flush_rewrite_rules();
        update_option('service_output_rewrite_flushed', '1');
    }
}

// اضافه کردن query var
add_filter('query_vars', 'add_service_output_query_vars');
function add_service_output_query_vars($vars) {
    $vars[] = 'history_id';
    return $vars;
}



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
    // همیشه در محیط تست اسکریپت را لود کنیم، اما رفتار آن با کلیک دکمه کنترل شود
    if (defined('OTP_ENV') && OTP_ENV === 'sandbox') {
        
        $js_path = '/assets/js/auto-fill.js';
        $full_path = get_template_directory() . $js_path;
        
        if (file_exists($full_path)) {

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
                'themeUrl' => get_template_directory_uri(),
                'nonce' => wp_create_nonce('ai_assistant_nonce'),
                'user_id' => get_current_user_id(),
                'i18n' => [
                    'error' => __('خطا', 'ai-assistant'),
                    'loading' => __('در حال پردازش...', 'ai-assistant')
                ]
            ]);
        } else {
            error_log('🔧 [ERROR] File does NOT exist: ' . $full_path);
        }
    } else {
        // error_log('🔧 [DEBUG] Production environment or OTP_ENV not set');
    }
}
add_action('wp_enqueue_scripts', 'ai_assistant_load_test_scripts', 20);

// در فایل functions.php یا فایل اصلی تم
wp_localize_script('your-script-handle', 'siteEnv', [
    'otpEnv' => OTP_ENV,
    'baseUrl' => (OTP_ENV === 'sandbox') ? 'https://test.aidastyar.com' : 'https://aidastyar.com'
]);

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


// حذف تابع قدیمی و جایگزینی با این کد ساده:
function ai_assistant_handle_diet_result_redirect() {
    return;
    // if (isset($_GET['ai_diet_result']) && $_GET['ai_diet_result'] === '1' && is_user_logged_in()) {
    //     $user_id = get_current_user_id();
    //     $history_manager = AI_Assistant_History_Manager::get_instance();
    //     $history = $history_manager->get_user_history($user_id, 1);
        
    //     if (!empty($history)) {
    //         wp_redirect(home_url('/service-output/' . $history[0]->ID . '/'));
    //         exit;
    //     }
        
    //     wp_redirect(home_url('/page-user-history/'));
    //     exit;
    // }
}
add_action('template_redirect', 'ai_assistant_handle_diet_result_redirect');


// اضافه کردن فایل هوک‌های ووکامرس
// require_once get_template_directory() . '/inc/woocommerce-hooks.php';

// اضافه کردن این هوک در functions.php یا فایل مربوطه
add_filter('woocommerce_cart_item_price', function($price, $cart_item, $cart_item_key) {
    if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
        return wc_price($cart_item['ai_wallet_charge']['amount']);
    }
    return $price;
}, 10, 3);

// اضافه کردن متا داده به آیتم سفارش
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
    if (isset($values['ai_wallet_charge'])) {
        $item->add_meta_data('ai_wallet_charge', $values['ai_wallet_charge']);
    }
}, 10, 4);

// نمایش اطلاعات شارژ در صفحه مدیریت سفارشات
add_action('woocommerce_after_order_itemmeta', function($item_id, $item, $product) {
    if ($item->get_meta('ai_wallet_charge')) {
        $charge_data = $item->get_meta('ai_wallet_charge');
        echo '<div class="wallet-charge-info">';
        echo '<strong>شارژ کیف پول:</strong> ' . number_format($charge_data['amount']) . ' تومان';
        echo '<br><small>شناسه: ' . $charge_data['unique_id'] . '</small>';
        echo '</div>';
    }
}, 10, 3);

add_filter('woocommerce_is_taxable', function($taxable, $product) {
    $wallet_product_id = get_option('ai_assistant_wallet_product_id');
    
    if ($product && $product->get_id() == $wallet_product_id) {
        return false;
    }
    
    return $taxable;
}, 10, 2);

add_filter('woocommerce_product_needs_shipping', function($needs_shipping, $product) {
    $wallet_product_id = get_option('ai_assistant_wallet_product_id');
    
    if ($product && $product->get_id() == $wallet_product_id) {
        return false;
    }
    
    return $needs_shipping;
}, 10, 2);

// در functions.php این کد رو اضافه کنید
add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    $wallet_total = 0;
    $has_wallet_item = false;
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
            $has_wallet_item = true;
            $amount = floatval($cart_item['ai_wallet_charge']['amount']);
            
            // تنظیم قیمت
            $cart_item['data']->set_price($amount);
            $cart_item['data']->set_regular_price($amount);
            $cart_item['data']->set_sale_price($amount);
            
            // اضافه کردن به مجموع
            $wallet_total += $amount * $cart_item['quantity'];
        }
    }
    
    if ($has_wallet_item) {
        // Override کامل محاسبات ووکامرس
        add_filter('woocommerce_cart_get_total', function($total) use ($wallet_total) {
            return floatval($wallet_total);
        }, 9999);
        
        add_filter('woocommerce_cart_get_subtotal', function($subtotal) use ($wallet_total) {
            return floatval($wallet_total);
        }, 9999);
        
        add_filter('woocommerce_cart_get_totals', function($totals) use ($wallet_total) {
            $totals['total'] = floatval($wallet_total);
            $totals['subtotal'] = floatval($wallet_total);
            $totals['subtotal_tax'] = 0;
            $totals['tax_total'] = 0;
            $totals['shipping_total'] = 0;
            $totals['shipping_tax'] = 0;
            return $totals;
        }, 9999);
    }
}, 9999);

// غیرفعال کردن کامل مالیات و حمل و نقل برای محصولات کیف پول
add_filter('woocommerce_cart_needs_shipping', function($needs_shipping) {
    $cart = WC()->cart;
    
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['ai_wallet_charge'])) {
            return false;
        }
    }
    
    return $needs_shipping;
}, 9999);

add_filter('woocommerce_cart_needs_payment', function($needs_payment) {
    $cart = WC()->cart;
    
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['ai_wallet_charge'])) {
            return true; // همچنان نیاز به پرداخت دارد
        }
    }
    
    return $needs_payment;
}, 9999);

// نمایش صحیح قیمت‌ها در checkout
add_filter('woocommerce_cart_item_subtotal', function($subtotal, $cart_item, $cart_item_key) {
    if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
        return wc_price($cart_item['ai_wallet_charge']['amount'] * $cart_item['quantity']);
    }
    return $subtotal;
}, 9999, 3);

add_filter('woocommerce_cart_subtotal', function($subtotal, $compound, $cart) {
    $wallet_total = 0;
    
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
            $wallet_total += $cart_item['ai_wallet_charge']['amount'] * $cart_item['quantity'];
        }
    }
    
    if ($wallet_total > 0) {
        return wc_price($wallet_total);
    }
    
    return $subtotal;
}, 9999, 3);

add_filter('woocommerce_cart_total', function($total) {
    $cart = WC()->cart;
    $wallet_total = 0;
    
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
            $wallet_total += $cart_item['ai_wallet_charge']['amount'] * $cart_item['quantity'];
        }
    }
    
    if ($wallet_total > 0) {
        return wc_price($wallet_total);
    }
    
    return $total;
}, 9999);


// تابع برای فرمت اعداد به فارسی
function format_number_fa($number) {
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    
    return str_replace($english_numbers, $persian_numbers, number_format($number));
}

// تابع برای فرمت اعداد به فارسی
function number_fa($number) {
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    
    return str_replace($english_numbers, $persian_numbers, $number);
}

require_once get_template_directory() . '/templates/profile-functions.php';

require_once get_template_directory() . '/templates/account-functions.php';

require_once get_template_directory() . '/templates/wallet-functions.php';

require_once get_template_directory() . '/functions/farsi-num-functions.php';

require_once get_template_directory() . '/functions/comments-functions.php';

// بارگذاری گزارش کیف پول
require get_template_directory() . '/inc/admin/ai-wallet-admin-report.php';

// فراخوانی کلاس‌های مدیریت تخفیف
require_once get_template_directory() . '/inc/admin/class-discount-db.php';

require_once get_template_directory() . '/templates/service-info-functions.php';

// بارگذاری سیستم مدیریت نظرات Front-end
require_once get_template_directory() . '/inc/class-comments-frontend-admin.php';

require_once get_template_directory() . '/functions/discounts-functions.php';
require_once get_template_directory() . '/functions/discount-core-functions.php';

require_once get_template_directory() . '/templates/page-admin-payout-manager-functions.php';

require_once get_template_directory() . '/templates/consultant-dashboard-functions.php';

require_once get_template_directory() . '/functions/consultant-users-functions.php';

// ایجاد نقش مشاور تغذیه
function add_nutrition_consultant_role() {
    add_role('nutrition_consultant', 'مشاور تغذیه', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'upload_files' => true,
        'manage_nutrition_consultation' => true
    ]);
}
add_action('init', 'add_nutrition_consultant_role');

// اضافه کردن capability به ادمین
function add_consultant_cap_to_admin() {
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_nutrition_consultation');
    }
}
add_action('init', 'add_consultant_cap_to_admin');


add_action('wp_ajax_check_user_auth', 'handle_check_user_auth');
add_action('wp_ajax_nopriv_check_user_auth', 'handle_check_user_auth_no_priv');

function handle_check_user_auth() {
    check_ajax_referer('ai_assistant_nonce', 'nonce');
    
    wp_send_json_success([
        'is_logged_in' => is_user_logged_in(),
        'user_id' => get_current_user_id()
    ]);
}

function handle_check_user_auth_no_priv() {
    wp_send_json_success([
        'is_logged_in' => false,
        'user_id' => 0
    ]);
}


function is_sandbox_environment() {
    return defined('OTP_ENV') && OTP_ENV === 'sandbox';
}

// انتقال متغیر محیطی به جاوااسکریپت
function add_environment_vars_to_js() {
    wp_localize_script('ai-assistant-main', 'siteEnv', [
        'otpEnv' => defined('OTP_ENV') ? OTP_ENV : 'production',
        'isSandbox' => is_sandbox_environment()
    ]);
}
add_action('wp_enqueue_scripts', 'add_environment_vars_to_js', 20);


// دریافت قیمت‌های سرویس رژیم غذایی
function get_diet_service_prices() {
    // بررسی nonce
    if (!wp_verify_nonce($_POST['security'], 'ai_assistant_nonce')) {
        wp_send_json_error('خطای امنیتی');
    }
    
    $diet_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    
    // قیمت پایه سرویس
    $base_price = $diet_db->get_diet_service_base_price();
    
    // قیمت مشاور (می‌توانید از اولین مشاور فعال استفاده کنید یا میانگین بگیرید)
    $consultants = $diet_db->get_active_consultants();
    $consultant_price = 25000; // قیمت پیش‌فرض
    
    if (!empty($consultants)) {
        // از قیمت اولین مشاور استفاده می‌کنیم
        $consultant_price = $consultants[0]->consultation_price;
    }
    
    wp_send_json_success([
        'base_price' => $base_price,
        'consultant_price' => $consultant_price
    ]);
}
add_action('wp_ajax_get_diet_service_prices', 'get_diet_service_prices');
add_action('wp_ajax_nopriv_get_diet_service_prices', 'get_diet_service_prices');



/**
 * Disable Gravatar completely and use local default avatar
 */
add_filter('avatar_defaults', 'local_default_avatar');
function local_default_avatar($avatars) {
    $local_avatar = get_template_directory_uri() . '/assets/images/default-avatar.png';
    $avatars[$local_avatar] = 'Local Avatar';
    return $avatars;
}

add_filter('get_avatar_url', 'force_local_avatar', 10, 3);
function force_local_avatar($url, $id_or_email, $args) {
    return get_template_directory_uri() . '/assets/images/default-avatar.png';
}

add_filter('pre_get_avatar', 'use_local_avatar_only', 10, 3);
function use_local_avatar_only($avatar, $id_or_email, $args) {
    $local_avatar = get_template_directory_uri() . '/assets/images/default-avatar.png';

    $size = isset($args['size']) ? intval($args['size']) : 80;

    return sprintf(
        '<img src="%s" class="avatar avatar-%d photo" width="%d" height="%d" />',
        esc_url($local_avatar),
        $size,
        $size,
        $size
    );
}


// اضافه کردن تب معرف به حساب کاربری
// function ai_assistant_add_referral_tab($items) {
//     $new_items = [];
//     foreach ($items as $key => $value) {
//         $new_items[$key] = $value;
//         if ($key === 'dashboard') {
//             $new_items['referral-dashboard'] = __('سیستم معرف', 'ai-assistant');
//         }
//     }
//     return $new_items;
// }
// add_filter('woocommerce_account_menu_items', 'ai_assistant_add_referral_tab');

// function ai_assistant_add_referral_endpoint() {
//     add_rewrite_endpoint('referral-dashboard', EP_ROOT | EP_PAGES);
// }
// add_action('init', 'ai_assistant_add_referral_endpoint');

// function ai_assistant_referral_tab_content() {
//     include get_template_directory() . '/templates/referral-dashboard.php';
// }
// add_action('woocommerce_account_referral-dashboard_endpoint', 'ai_assistant_referral_tab_content');

// بارگذاری کلاس سیستم معرف
require_once get_template_directory() . '/inc/class-referral-system.php';



add_filter('show_admin_bar', function($show) {
    if (!current_user_can('manage_options')) { // برای غیرمدیران
        return false;
    }
    return $show;
});



// بارگذاری کلاس‌های مدیریت تأییدیه
require_once get_template_directory() . '/inc/class-terms-acceptance-db.php';
require_once get_template_directory() . '/inc/class-terms-manager.php';
require_once get_template_directory() . '/inc/ajax-terms-handlers.php';


add_action('init', function() {
    Terms_Acceptance_DB::get_instance();
}, 1);


// Load Terms Content (Single Source of Truth)
require_once get_template_directory() . '/inc/terms-content.php';


add_action('wp_ajax_get_current_user_info', 'ai_assistant_get_current_user_info');

function ai_assistant_get_current_user_info() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'وارد شوید'], 401);
    }

    $user_id = get_current_user_id();
    $current_user = get_user_by('ID', $user_id);

    wp_send_json_success([
        'user_id' => $user_id,
        'email' => $current_user->user_email,
        'email_is_valid' => !empty($current_user->user_email) && is_email($current_user->user_email),
        'first_name' => get_user_meta($user_id, 'first_name', true),
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'phone' => get_user_meta($user_id, 'billing_phone', true)
    ]);
}

// بارگذاری استایل‌های Service Info
require_once get_template_directory() . '/functions/service-info-styles.php';




// در فایل functions.php اضافه کنید:

// AJAX handler برای آپلود فایل آزمایش
add_action('wp_ajax_upload_lab_test', 'handle_lab_test_upload');
add_action('wp_ajax_nopriv_upload_lab_test', 'handle_lab_test_upload');

function handle_lab_test_upload() {
    check_ajax_referer('diet-form-nonce', 'security');

    if (!isset($_FILES['lab_test_file'])) {
        wp_send_json_error(['message' => 'فایلی انتخاب نشده است']);
    }

    $file = $_FILES['lab_test_file'];
    
    // بررسی نوع فایل
    $allowed_types = ['application/pdf'];
    $file_type = wp_check_filetype($file['name']);
    
    if ($file['type'] !== 'application/pdf') {
        wp_send_json_error(['message' => 'فقط فایل PDF مجاز است']);
    }

    // بررسی حجم (10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        wp_send_json_error(['message' => 'حجم فایل نباید بیشتر از 10 مگابایت باشد']);
    }

    // آپلود فایل
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    
    $upload_overrides = [
        'test_form' => false,
        'mimes' => ['pdf' => 'application/pdf']
    ];
    
    $uploaded_file = wp_handle_upload($file, $upload_overrides);

    if (isset($uploaded_file['error'])) {
        wp_send_json_error(['message' => $uploaded_file['error']]);
    }

    // ذخیره اطلاعات در متادیتای کاربر (اختیاری)
    $user_id = get_current_user_id();
    if ($user_id) {
        update_user_meta($user_id, 'lab_test_file_url', $uploaded_file['url']);
        update_user_meta($user_id, 'lab_test_file_path', $uploaded_file['file']);
        update_user_meta($user_id, 'lab_test_upload_date', current_time('mysql'));
    }

    wp_send_json_success([
        'fileUrl' => $uploaded_file['url'],
        'filePath' => $uploaded_file['file'],
        'fileName' => basename($uploaded_file['file']),
        'message' => 'فایل با موفقیت آپلود شد'
    ]);
}



// اضافه کردن session timeout به functions.php

// تنظیم زمان session
add_action('init', 'ai_assistant_init_session_timeout', 3 * 3600);
function ai_assistant_init_session_timeout() {
    if (!session_id()) {
        // تنظیم timeout به 60 دقیقه (3600 ثانیه)
        if (!headers_sent() && session_id() == '') {
            ini_set('session.gc_maxlifetime', 3 * 3600);
            session_set_cookie_params(3 * 3600);
            session_start();
        }
    }
    
    // بررسی آخرین فعالیت
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3 * 3600)) {
        // بیش از 60 دقیقه از آخرین فعالیت گذشته
        session_unset();
        session_destroy();
        wp_logout();
        wp_redirect(home_url('/otp-login'));
        exit;
    }
    
    // آپدیت زمان آخرین فعالیت (sliding expiry)
    $_SESSION['LAST_ACTIVITY'] = time();
}


// ذخیره موقت PDF‌های آزمایش
require_once get_template_directory() . '/services/diet/upload-pdf-temp.php';


// AJAX handler برای بروزرسانی ایمیل کاربر
function aidastyar_update_user_email() {
    // بررسی nonce
    check_ajax_referer('ai_assistant_nonce', 'security');
    
    // بررسی لاگین بودن کاربر
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'کاربر وارد نشده است'
        ]);
    }
    
    $user_id = get_current_user_id();
    $new_email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    
    // اعتبارسنجی ایمیل
    if (empty($new_email)) {
        wp_send_json_error([
            'message' => 'ایمیل نمی‌تواند خالی باشد'
        ]);
    }
    
    if (!is_email($new_email)) {
        wp_send_json_error([
            'message' => 'فرمت ایمیل صحیح نیست'
        ]);
    }
    
    // بررسی تکراری نبودن ایمیل
    if (email_exists($new_email) && email_exists($new_email) !== $user_id) {
        wp_send_json_error([
            'message' => 'این ایمیل قبلاً ثبت شده است'
        ]);
    }
    
    // بروزرسانی ایمیل
    $result = wp_update_user([
        'ID' => $user_id,
        'user_email' => $new_email
    ]);
    
    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => 'خطا در بروزرسانی ایمیل: ' . $result->get_error_message()
        ]);
    }
    
    // ذخیره لاگ
    error_log("Email updated for user $user_id: $new_email");
    
    wp_send_json_success([
        'message' => 'ایمیل با موفقیت بروزرسانی شد',
        'email' => $new_email
    ]);
}

add_action('wp_ajax_update_user_email', 'aidastyar_update_user_email');


// غیرفعال کردن ایموجی‌های وردپرس
function disable_wp_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    
    // حذف DNS prefetch برای s.w.org
    add_filter('emoji_svg_url', '__return_false');
    
    // حذف از TinyMCE
    add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
}
add_action('init', 'disable_wp_emojis');

function disable_emojis_tinymce($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    }
    return array();
}