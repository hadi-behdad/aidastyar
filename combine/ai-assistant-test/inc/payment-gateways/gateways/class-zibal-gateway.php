<?php
/**
 * Zibal Payment Gateway
 * 
 * درگاه پرداخت Zibal (زیبال)
 * https://gateway.zibal.ir
 * 
 * @package AI_Assistant
 * @subpackage Payment_Gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Zibal_Payment_Gateway
 * 
 * پیاده‌سازی درگاه Zibal
 */
class AI_Zibal_Payment_Gateway implements AI_Payment_Gateway_Interface {

    /**
     * Zibal Merchant ID
     */
    private $merchant;

    /**
     * Zibal API Base URL
     */
    private $api_url = 'https://gateway.zibal.ir';

    /**
     * سازنده
     */
    public function __construct() {
        if ( function_exists( 'aiassistant_get_zibal_merchant_id' ) ) {
            $this->merchant = aiassistant_get_zibal_merchant_id();
        } else {
            $this->merchant = '';
        }
    }

    /**
     * ارسال درخواست پرداخت
     * 
     * @param int    $user_id    شناسه کاربر
     * @param float  $amount     مبلغ پرداخت (به ریال)
     * @param string $return_url URL برای بازگشت
     * @param array  $extra_data اطلاعات اضافی
     * 
     * @return array نتیجه درخواست
     */
    public function request_payment($user_id, $amount, $return_url, $extra_data = array()) {
        //error_log('🟣 [ZIBAL_ADAPTER] Requesting payment: User=' . $user_id . ', Amount=' . $amount);

        // بررسی Merchant ID
        if (empty($this->merchant)) {
            error_log('❌ [ZIBAL_ADAPTER] Merchant ID not configured');
            return array(
                'status'    => false,
                'url'       => '',
                'authority' => '',
                'message'   => 'Zibal Merchant ID not configured'
            );
        }

        try {
            // آدرس کال‌بک را مستقل از $return_url تنظیم کن تا همیشه به wallet-checkout برگردد
            $callback_url = add_query_arg(
                'payment_verify',
                1,
                home_url('/wallet-checkout')
            );
            
            $request_body = array(
                'merchant'    => $this->merchant,
                'amount'      => (int)$amount,
                'callbackUrl' => $callback_url,
                'description' => 'شارژ کیف پول',
                'orderId'     => 'wallet_' . $user_id . '_' . time()
            );


            // اضافه کردن اطلاعات اضافی (اگر موجود)
            if (!empty($extra_data['mobile'])) {
                $request_body['mobile'] = $extra_data['mobile'];
            }

            //error_log('🔵 [ZIBAL_ADAPTER] Request Body: ' . json_encode($request_body));

            // ارسال درخواست
            $response = wp_remote_post($this->api_url . '/v1/request', array(
                'method'      => 'POST',
                'timeout'     => 30,
                'sslverify'   => true,
                'headers'     => array(
                    'Content-Type' => 'application/json',
                ),
                'body'        => json_encode($request_body),
            ));

            // بررسی خطای HTTP
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log('❌ [ZIBAL_ADAPTER] HTTP Error: ' . $error_message);
                return array(
                    'status'    => false,
                    'url'       => '',
                    'authority' => '',
                    'message'   => 'HTTP Error: ' . $error_message
                );
            }

            // پارس کردن پاسخ
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            //error_log('🔵 [ZIBAL_ADAPTER] Response: ' . $response_body);

            // بررسی نتیجه
            if (isset($response_data['result']) && $response_data['result'] == 100) {
                $track_id = $response_data['trackId'] ?? null;

                if (!$track_id) {
                    error_log('❌ [ZIBAL_ADAPTER] No trackId in response');
                    return array(
                        'status'    => false,
                        'url'       => '',
                        'authority' => '',
                        'message'   => 'No trackId received from Zibal'
                    );
                }

                // ساخت URL درگاه
                $payment_url = $this->api_url . '/start/' . $track_id;

                //error_log('✅ [ZIBAL_ADAPTER] Payment request successful, TrackId: ' . $track_id);

                // ساخت URL درگاه
                $payment_url = $this->api_url . '/start/' . $track_id;
                //error_log('✅ [ZIBAL_ADAPTER] Payment request successful, TrackId: ' . $track_id);
                
                // ذخیره تراکنش در جدول pending مثل زرین‌پال
                global $wpdb;
                $table_name = $wpdb->prefix . 'wallet_pending_payments';
                
                $amount_int = (int) $amount; // مبلغ به همان فرمتی که در زرین‌پال ذخیره می‌شود
                
                // اطمینان از وجود جدول (در صورت نیاز، ولی چون قبلاً برای زرین‌پال ساخته شده، معمولاً وجود دارد)
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name ) {
                    $wpdb->replace(
                        $table_name,
                        array(
                            'user_id'   => $user_id,
                            'amount'    => $amount_int,
                            'authority' => (string) $track_id,
                            'status'    => 'pending',
                        ),
                        array( '%d', '%d', '%s', '%s' )
                    );
                    //error_log('🔵 [ZIBAL_ADAPTER] Pending payment saved: UserID=' . $user_id . ', Amount=' . $amount_int . ', TrackId=' . $track_id);
                } else {
                    error_log('❌ [ZIBAL_ADAPTER] Pending payments table does not exist: ' . $table_name);
                }
                
                return array(
                    'status'    => true,
                    'url'       => $payment_url,
                    'authority' => $track_id,
                    'message'   => '',
                );

            } else {
                // خطای Zibal
                $error_message = $response_data['message'] ?? 'Unknown error';
                error_log('❌ [ZIBAL_ADAPTER] Zibal Error: ' . $error_message);

                return array(
                    'status'    => false,
                    'url'       => '',
                    'authority' => '',
                    'message'   => $error_message
                );
            }

        } catch (Exception $e) {
            error_log('❌ [ZIBAL_ADAPTER] Exception: ' . $e->getMessage());
            return array(
                'status'    => false,
                'url'       => '',
                'authority' => '',
                'message'   => $e->getMessage()
            );
        }
    }

    /**
     * تأیید پرداخت
     * 
     * @param string $authority شناسه تراکنش (trackId)
     * @param float  $amount    مبلغ (برای بررسی)
     * 
     * @return array نتیجه تأیید
     */
    public function verify_payment($authority, $amount) {
        //error_log('🟣 [ZIBAL_ADAPTER] Verifying payment: TrackId=' . $authority . ', Amount=' . $amount);

        // بررسی Merchant ID
        if (empty($this->merchant)) {
            error_log('❌ [ZIBAL_ADAPTER] Merchant ID not configured');
            return array(
                'status'     => false,
                'ref_id'     => '',
                'message'    => 'Zibal Merchant ID not configured',
                'gateway_id' => $this->get_gateway_id()
            );
        }

        try {
            // آماده کردن درخواست تأیید
            $request_body = array(
                'merchant' => $this->merchant,
                'trackId'  => (int)$authority,
            );

            //error_log('🔵 [ZIBAL_ADAPTER] Verify Request Body: ' . json_encode($request_body));

            // ارسال درخواست تأیید
            $response = wp_remote_post($this->api_url . '/v1/verify', array(
                'method'      => 'POST',
                'timeout'     => 30,
                'sslverify'   => true,
                'headers'     => array(
                    'Content-Type' => 'application/json',
                ),
                'body'        => json_encode($request_body),
            ));

            // بررسی خطای HTTP
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log('❌ [ZIBAL_ADAPTER] HTTP Error: ' . $error_message);
                return array(
                    'status'     => false,
                    'ref_id'     => '',
                    'message'    => 'HTTP Error: ' . $error_message,
                    'gateway_id' => $this->get_gateway_id()
                );
            }

            // پارس کردن پاسخ
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            //error_log('🔵 [ZIBAL_ADAPTER] Verify Response: ' . $response_body);

            // بررسی نتیجه
            if (isset($response_data['result']) && $response_data['result'] == 100) {
                $ref_number = $response_data['refNumber'] ?? '';
                $status = $response_data['status'] ?? -1;

                // وضعیت 1 = پرداخت شده و تایید شده
                if ($status == 1) {
                    //error_log('✅ [ZIBAL_ADAPTER] Payment verified successfully, RefNumber: ' . $ref_number);

                    return array(
                        'status'     => true,
                        'ref_id'     => $ref_number,
                        'message'    => '',
                        'gateway_id' => $this->get_gateway_id()
                    );
                } else {
                    //error_log('⚠️  [ZIBAL_ADAPTER] Payment status is not completed: ' . $status);

                    return array(
                        'status'     => false,
                        'ref_id'     => '',
                        'message'    => 'Payment not completed. Status: ' . $status,
                        'gateway_id' => $this->get_gateway_id()
                    );
                }
            } else {
                // خطای Zibal
                $error_message = $response_data['message'] ?? 'Unknown verification error';
                error_log('❌ [ZIBAL_ADAPTER] Zibal Verification Error: ' . $error_message);

                return array(
                    'status'     => false,
                    'ref_id'     => '',
                    'message'    => $error_message,
                    'gateway_id' => $this->get_gateway_id()
                );
            }

        } catch (Exception $e) {
            error_log('❌ [ZIBAL_ADAPTER] Exception: ' . $e->getMessage());
            return array(
                'status'     => false,
                'ref_id'     => '',
                'message'    => $e->getMessage(),
                'gateway_id' => $this->get_gateway_id()
            );
        }
    }

    /**
     * دریافت شناسه درگاه
     */
    public function get_gateway_id() {
        return 'zibal';
    }

    /**
     * دریافت نام درگاه
     */
    public function get_gateway_name() {
        return 'درگاه زیبال';
    }

    /**
     * بررسی فعال‌بودن درگاه
     */
    public function is_enabled() {
        return !empty($this->merchant);
    }
}