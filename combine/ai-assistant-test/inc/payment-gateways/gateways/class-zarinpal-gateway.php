<?php
/**
 * ZarinPal Payment Gateway
 * 
 * Adapter برای درگاه پرداخت زرین‌پال
 * این کلاس موجود `class-wallet-checkout-handler.php` را wrap می‌کند
 * 
 * @package AI_Assistant
 * @subpackage Payment_Gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ZarinPal_Payment_Gateway
 * 
 * پیاده‌سازی درگاه ZarinPal
 */
class AI_ZarinPal_Payment_Gateway implements AI_Payment_Gateway_Interface {

    /**
     * Instance موجود درگاه ZarinPal
     * 
     * @var AI_Assistant_Wallet_Checkout_Handler
     */
    private $zarinpal_handler;

    /**
     * سازنده
     */
    public function __construct() {
        if (class_exists('AI_Assistant_Wallet_Checkout_Handler')) {
            $this->zarinpal_handler = AI_Assistant_Wallet_Checkout_Handler::get_instance();
        }
    }

    /**
     * ارسال درخواست پرداخت
     * 
     * @param int    $user_id    شناسه کاربر
     * @param float  $amount     مبلغ پرداخت (به تومان)
     * @param string $return_url URL برای بازگشت
     * @param array  $extra_data اطلاعات اضافی
     * 
     * @return array نتیجه درخواست
     */
    public function request_payment($user_id, $amount, $return_url, $extra_data = array()) {
        //error_log('🔵 [ZARINPAL_ADAPTER] Requesting payment: User=' . $user_id . ', Amount=' . $amount);

        if (!$this->zarinpal_handler) {
            return array(
                'status' => false,
                'message' => 'ZarinPal handler not available',
                'url' => '',
                'authority' => ''
            );
        }

        // استفاده از متد موجود
        $result = $this->zarinpal_handler->connect_to_zarinpal($amount);

        // تبدیل خروجی
        return array(
            'status'    => $result['status'] ?? false,
            'url'       => $result['url'] ?? '',
            'message'   => $result['message'] ?? 'Unknown error',
            'authority' => $result['authority'] ?? ''
        );
    }

    /**
     * تأیید پرداخت
     * 
     * @param string $authority شناسه تراکنش
     * @param float  $amount    مبلغ
     * 
     * @return array نتیجه تأیید
     */
    public function verify_payment($authority, $amount) {
        //error_log('🔵 [ZARINPAL_ADAPTER] Verifying payment: Authority=' . $authority . ', Amount=' . $amount);

        if (!$this->zarinpal_handler) {
            return array(
                'status'     => false,
                'ref_id'     => '',
                'message'    => 'ZarinPal handler not available',
                'gateway_id' => $this->get_gateway_id()
            );
        }

        // استفاده از متد موجود
        $result = $this->zarinpal_handler->verify_payment($authority, $amount);

        // تبدیل خروجی
        return array(
            'status'     => $result['status'] ?? false,
            'ref_id'     => $result['ref_id'] ?? '',
            'message'    => $result['message'] ?? 'Unknown error',
            'gateway_id' => $this->get_gateway_id()
        );
    }

    /**
     * دریافت شناسه درگاه
     * 
     * @return string
     */
    public function get_gateway_id() {
        return 'zarinpal';
    }

    /**
     * دریافت نام درگاه
     * 
     * @return string
     */
    public function get_gateway_name() {
        return 'درگاه زرین‌پال';
    }

    /**
     * بررسی فعال‌بودن درگاه
     * 
     * @return bool
     */
    public function is_enabled() {
        // بررسی اینکه merchant_id تنظیم شده یا نه
        $merchant_id = ai_assistant_get_zarinpal_merchant_id();
        return !empty($merchant_id);
    }
}