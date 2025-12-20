<?php
/**
 * Payment Gateway Manager
 * 
 * مدیریت درگاه‌های پرداخت
 * انتخاب درگاه مناسب، ثبت درگاه‌های جدید، مدیریت فعال/غیرفعال
 * 
 * @package AI_Assistant
 * @subpackage Payment_Gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Payment_Gateway_Manager
 * 
 * مدیریت درگاه‌های پرداخت و انتخاب بین آن‌ها
 */
class AI_Payment_Gateway_Manager {

    /**
     * Instance تک‌شنبه (Singleton)
     */
    private static $instance = null;

    /**
     * آرایه درگاه‌های ثبت‌شده
     */
    private $gateways = array();

    /**
     * درگاه فعلی
     */
    private $active_gateway = null;

    /**
     * دریافت یا ایجاد instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * سازنده - بارگذاری درگاه‌های موجود
     */
    private function __construct() {
        $this->load_gateways();
    }

    /**
     * بارگذاری درگاه‌های موجود
     */
    private function load_gateways() {
        //error_log('🔵 [GATEWAY_MANAGER] Loading payment gateways...');

        // بارگذاری ZarinPal (درگاه پیشفرض)
        if (class_exists('AI_ZarinPal_Payment_Gateway')) {
            $zarinpal = new AI_ZarinPal_Payment_Gateway();
            $this->register_gateway($zarinpal);
            //error_log('✅ [GATEWAY_MANAGER] ZarinPal gateway registered');
        }

        // Zibal
        if ( class_exists( 'AI_Zibal_Payment_Gateway' ) ) {
            $zibal = new AI_Zibal_Payment_Gateway();
            $this->register_gateway( $zibal );
            //error_log('✅ [GATEWAY_MANAGER] Zibal gateway registered');
        }
        
        // Hook برای ثبت درگاه‌های سفارشی
        do_action('ai_register_payment_gateways', $this);

        // تنظیم درگاه فعال
        $this->set_active_gateway();
    }

    /**
     * ثبت درگاه جدید
     */
    public function register_gateway($gateway) {
        if (!$gateway instanceof AI_Payment_Gateway_Interface) {
            error_log('❌ [GATEWAY_MANAGER] Gateway does not implement interface');
            return false;
        }

        $gateway_id = $gateway->get_gateway_id();
        $this->gateways[$gateway_id] = $gateway;

        //error_log('✅ [GATEWAY_MANAGER] Gateway registered: ' . $gateway_id);
        return true;
    }

    /**
     * دریافت درگاه بر اساس شناسه
     */
    public function get_gateway($gateway_id = null) {
        if (null === $gateway_id) {
            return $this->active_gateway;
        }

        return isset($this->gateways[$gateway_id]) ? $this->gateways[$gateway_id] : null;
    }

    /**
     * دریافت تمام درگاه‌های فعال
     */
    public function get_active_gateways() {
        $active = array();

        foreach ($this->gateways as $gateway) {
            if ($gateway->is_enabled()) {
                $active[$gateway->get_gateway_id()] = $gateway;
            }
        }

        return $active;
    }

    /**
     * تنظیم درگاه فعال
     */
    public function set_active_gateway($gateway_id = null) {
        if (null === $gateway_id) {
            $gateway_id = get_option('ai_payment_default_gateway', 'zarinpal');
        }

        $gateway = $this->get_gateway($gateway_id);

        if (!$gateway) {
            error_log('❌ [GATEWAY_MANAGER] Gateway not found: ' . $gateway_id);
            $gateways = $this->get_active_gateways();
            if (!empty($gateways)) {
                $gateway = reset($gateways);
                //error_log('⚠️  [GATEWAY_MANAGER] Using fallback gateway: ' . $gateway->get_gateway_id());
            } else {
                error_log('❌ [GATEWAY_MANAGER] No active gateway found!');
                return false;
            }
        }

        $this->active_gateway = $gateway;
        //error_log('✅ [GATEWAY_MANAGER] Active gateway set to: ' . $this->active_gateway->get_gateway_id());
        return true;
    }

    /**
     * ارسال درخواست پرداخت
     */
    public function request_payment($user_id, $amount, $return_url, $extra_data = array()) {
        if (!$this->active_gateway) {
            return array(
                'status'  => false,
                'message' => 'No payment gateway available'
            );
        }

        return $this->active_gateway->request_payment($user_id, $amount, $return_url, $extra_data);
    }

    /**
     * تأیید پرداخت
     */
    public function verify_payment($authority, $amount) {
        if (!$this->active_gateway) {
            return array(
                'status'  => false,
                'message' => 'No payment gateway available'
            );
        }

        return $this->active_gateway->verify_payment($authority, $amount);
    }

    /**
     * دریافت نام درگاه فعال
     */
    public function get_active_gateway_name() {
        if (!$this->active_gateway) {
            return 'درگاه نامشخص';
        }
        return $this->active_gateway->get_gateway_name();
    }

    /**
     * دریافت شناسه درگاه فعال
     */
    public function get_active_gateway_id() {
        if (!$this->active_gateway) {
            return '';
        }
        return $this->active_gateway->get_gateway_id();
    }

    /**
     * دریافت اطلاعات درگاه‌های موجود
     */
    public function get_gateways_info() {
        $info = array();

        foreach ($this->gateways as $gateway) {
            $info[$gateway->get_gateway_id()] = array(
                'id'      => $gateway->get_gateway_id(),
                'name'    => $gateway->get_gateway_name(),
                'enabled' => $gateway->is_enabled()
            );
        }

        return $info;
    }

    /**
     * جلوگیری از Clone کردن
     */
    private function __clone() {}

    /**
     * جلوگیری از Serialize کردن
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize Payment Gateway Manager');
    }
}