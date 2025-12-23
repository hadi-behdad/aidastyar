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

if ( !class_exists( 'AI_Assistant_Logger' ) ) {
    require_once WP_CONTENT_DIR . '/themes/ai-assistant-test/inc/ai-assistant-api/class-logger.php'; // مسیر واقعی‌ات را بگذار
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
    private $logger;
    /**
     * سازنده
     */
    public function __construct() {
        if (class_exists('AI_Assistant_Wallet_Checkout_Handler')) {
            $this->zarinpal_handler = AI_Assistant_Wallet_Checkout_Handler::get_instance();
        }
        $this->logger           = AI_Assistant_Logger::get_instance();
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

        if ( !$this->zarinpal_handler ) {
            $this->logger->log_error(
                'ZarinPal handler not available in request_payment',
                array(
                    'user_id'    => $user_id,
                    'amount'     => $amount,
                    'return_url' => $return_url,
                    'extra'      => $extra_data,
                )
            );

            return array(
                'status'    => false,
                'message'   => 'درگاه پرداخت موقتا در دسترس نیست',
                'url'       => '',
                'authority' => '',
            );
        }


        try {
            // 2) لاگ شروع درخواست
            $this->logger->log(
                'ZarinPal request_payment called',
                array(
                    'user_id'    => $user_id,
                    'amount'     => $amount,
                    'return_url' => $return_url,
                )
            );

            // مبلغ در کل سیستم بر حسب تومان است؛ زرین‌پال ریال می‌خواهد
            $amount_toman = (int) $amount;
            $amount_rial  = $amount_toman * 10;
            
            // استفاده از متد موجود با مبلغ ریالی
            $result = $this->zarinpal_handler->connect_to_zarinpal( $amount_rial );


            // 3) لاگ نتیجه خام
            $this->logger->log_debug(
                'ZarinPal connect_to_zarinpal response',
                array(
                    'user_id'   => $user_id,
                    'amount'    => $amount,
                    'raw_result'=> $result,
                )
            );

            // تبدیل خروجی
            return array(
                'status'    => $result['status']    ?? false,
                'url'       => $result['url']       ?? '',
                'message'   => $result['message']   ?? 'خطای نامشخص در ارتباط با درگاه',
                'authority' => $result['authority'] ?? '',
            );

        } catch ( Exception $e ) {

            // 4) لاگ خطا
            $this->logger->log_error(
                'ZarinPal request_payment exception',
                array(
                    'user_id'    => $user_id,
                    'amount'     => $amount,
                    'return_url' => $return_url,
                    'extra'      => $extra_data,
                    'exception'  => $e->getMessage(),
                )
            );

            return array(
                'status'    => false,
                'message'   => 'در فرآیند اتصال به درگاه خطایی رخ داد',
                'url'       => '',
                'authority' => '',
            );
        }
    }

    /**
     * تأیید پرداخت
     * 
     * @param string $authority شناسه تراکنش
     * @param float  $amount    مبلغ
     * 
     * @return array نتیجه تأیید
     */
    public function verify_payment( $authority, $amount ) {

        if ( !$this->zarinpal_handler ) {
            $this->logger->log_error(
                'ZarinPal handler not available in verify_payment',
                array(
                    'authority' => $authority,
                    'amount'    => $amount,
                )
            );

            return array(
                'status'     => false,
                'ref_id'     => '',
                'message'    => 'درگاه پرداخت موقتا در دسترس نیست',
                'gateway_id' => $this->get_gateway_id(),
            );
        }

        try {
            $this->logger->log(
                'ZarinPal verify_payment called',
                array(
                    'authority' => $authority,
                    'amount'    => $amount,
                )
            );

            // استفاده از متد موجود
            $result = $this->zarinpal_handler->verify_payment( $authority, $amount );

            $this->logger->log_debug(
                'ZarinPal verify_payment response',
                array(
                    'authority' => $authority,
                    'amount'    => $amount,
                    'raw_result'=> $result,
                )
            );

            return array(
                'status'     => $result['status']   ?? false,
                'ref_id'     => $result['ref_id']   ?? '',
                'message'    => $result['message']  ?? 'خطای نامشخص در تایید پرداخت',
                'gateway_id' => $this->get_gateway_id(),
            );

        } catch ( Exception $e ) {

            $this->logger->log_error(
                'ZarinPal verify_payment exception',
                array(
                    'authority' => $authority,
                    'amount'    => $amount,
                    'exception' => $e->getMessage(),
                )
            );

            return array(
                'status'     => false,
                'ref_id'     => '',
                'message'    => 'در تایید پرداخت خطایی رخ داد',
                'gateway_id' => $this->get_gateway_id(),
            );
        }
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