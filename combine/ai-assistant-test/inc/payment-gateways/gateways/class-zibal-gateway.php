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

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'AI_Assistant_Logger' ) ) {
    require_once WP_CONTENT_DIR . '/themes/ai-assistant-test/inc/ai-assistant-api/class-logger.php';
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
    private $logger;
    private $api_url = 'https://gateway.zibal.ir';

    public function __construct() {
        if ( function_exists( 'ai_assistant_get_zibal_merchant_id' ) ) {
            $this->merchant = ai_assistant_get_zibal_merchant_id();
        } else {
            $this->merchant = '';
        }
    
        $this->logger = AI_Assistant_Logger::get_instance();
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
        
        $this->logger->log(
            'ZIBAL_ADAPTER Requesting payment',
            [
                'user_id'  => $user_id,
                'amount'   => $amount,
                'merchant' => $this->merchant,
            ]
        );

        // بررسی Merchant ID
        if (empty($this->merchant)) {
            
            $this->logger->log_error(
                'ZIBAL_ADAPTER Merchant ID not configured in request_payment',
                [ 'user_id' => $user_id ]
            );
            
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
            
            $this->logger->log_debug(
                'ZIBAL_ADAPTER Request Body',
                [
                    'user_id'      => $user_id,
                    'amount'       => $amount,
                    'request_body' => $request_body,
                ]
            );
            
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
            
                $this->logger->log_error(
                    'ZIBAL_ADAPTER HTTP Error in request_payment',
                    [
                        'user_id' => $user_id,
                        'amount'  => $amount,
                        'error'   => $error_message,
                    ]
                );                
                
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

            $this->logger->log_debug(
                'ZIBAL_ADAPTER Response',
                [
                    'user_id'       => $user_id,
                    'amount'        => $amount,
                    'raw_response'  => $response_body,
                    'response_data' => $response_data,
                ]
            );

            // بررسی نتیجه
            if (isset($response_data['result']) && $response_data['result'] == 100) {
                $track_id = $response_data['trackId'] ?? null;

                if (!$track_id) {
                    $this->logger->log_error(
                        'ZIBAL_ADAPTER No trackId in response',
                        [
                            'user_id'       => $user_id,
                            'amount'        => $amount,
                            'response_data' => $response_data,
                        ]
                    );
                    
                    
                    return array(
                        'status'    => false,
                        'url'       => '',
                        'authority' => '',
                        'message'   => 'No trackId received from Zibal'
                    );
                }

                // ساخت URL درگاه
                $payment_url = $this->api_url . '/start/' . $track_id;
                
                $this->logger->log(
                    'ZIBAL_ADAPTER Payment request successful',
                    array(
                        'user_id'    => $user_id,
                        'amount'     => $amount,
                        'track_id'   => $track_id,
                        'payment_url'=> $payment_url,
                    )
                );
                
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
                    
                    $this->logger->log(
                        'ZIBAL_ADAPTER Pending payment saved',
                        array(
                            'user_id'  => $user_id,
                            'amount'   => $amount_int,
                            'track_id' => $track_id,
                            'table'    => $table_name,
                        )
                    );                    
                } else {
                    $this->logger->log_error(
                        'ZIBAL_ADAPTER Pending payments table does not exist',
                        array(
                            'user_id' => $user_id,
                            'amount'  => $amount_int,
                            'table'   => $table_name,
                        )
                    );
                }
                
                $this->logger->log(
                    'ZIBAL_ADAPTER Pending payment saved',
                    [
                        'user_id'  => $user_id,
                        'amount'   => $amount_int,
                        'track_id' => $track_id,
                    ]
                );
                
                return array(
                    'status'    => true,
                    'url'       => $payment_url,
                    'authority' => $track_id,
                    'message'   => '',
                );

            } else {
                $error_message = isset( $response_data['message'] ) ? $response_data['message'] : 'Unknown error';

                $this->logger->log_error(
                    'ZIBAL_ADAPTER Zibal Error in request_payment',
                    array(
                        'user_id'       => $user_id,
                        'amount'        => $amount,
                        'response_data' => $response_data,
                        'error'         => $error_message,
                    )
                );

                return array(
                    'status'    => false,
                    'url'       => '',
                    'authority' => '',
                    'message'   => $error_message
                );
            }

        } catch (Exception $e) {
            $this->logger->log_error(
                'ZIBAL_ADAPTER Exception in request_payment',
                array(
                    'user_id' => $user_id,
                    'amount'  => $amount,
                    'error'   => $e->getMessage(),
                )
            );
            
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

        $this->logger->log(
            'ZIBAL_ADAPTER Verifying payment',
            array(
                'authority' => $authority,
                'amount'    => $amount,
                'merchant'  => $this->merchant,
            )
        );
        
        // بررسی Merchant ID
        if (empty($this->merchant)) {
            $this->logger->log_error(
                'ZIBAL_ADAPTER Merchant ID not configured in verify_payment',
                array(
                    'authority' => $authority,
                    'amount'    => $amount,
                )
            );
            
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

            $this->logger->log_debug(
                'ZIBAL_ADAPTER Verify Request Body',
                array(
                    'authority'    => $authority,
                    'amount'       => $amount,
                    'request_body' => $request_body,
                )
            );
            
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

                $this->logger->log_error(
                    'ZIBAL_ADAPTER HTTP Error in verify_payment',
                    array(
                        'authority' => $authority,
                        'amount'    => $amount,
                        'error'     => $error_message,
                    )
                );
                
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
            
            $this->logger->log_debug(
                'ZIBAL_ADAPTER Verify Response',
                array(
                    'authority'     => $authority,
                    'amount'        => $amount,
                    'raw_response'  => $response_body,
                    'response_data' => $response_data,
                )
            );
            
            // بررسی نتیجه
            if (isset($response_data['result']) && $response_data['result'] == 100) {
                $ref_number = $response_data['refNumber'] ?? '';
                $status = $response_data['status'] ?? -1;

                // وضعیت 1 = پرداخت شده و تایید شده
                if ($status == 1) {
                    $this->logger->log(
                        'ZIBAL_ADAPTER Payment verified successfully',
                        array(
                            'authority'  => $authority,
                            'amount'     => $amount,
                            'ref_number' => $ref_number,
                            'status'     => $status,
                        )
                    );
                    
                    return array(
                        'status'     => true,
                        'ref_id'     => $ref_number,
                        'message'    => '',
                        'gateway_id' => $this->get_gateway_id()
                    );
                } else {
                    $this->logger->log_warning(
                        'ZIBAL_ADAPTER Payment status is not completed',
                        array(
                            'authority' => $authority,
                            'amount'    => $amount,
                            'status'    => $status,
                        )
                    );                    
                    return array(
                        'status'     => false,
                        'ref_id'     => '',
                        'message'    => 'Payment not completed. Status: ' . $status,
                        'gateway_id' => $this->get_gateway_id()
                    );
                }
            } else {

                $error_message = isset( $response_data['message'] ) ? $response_data['message'] : 'Unknown verification error';
    
                $this->logger->log_error(
                    'ZIBAL_ADAPTER Zibal Verification Error in verify_payment',
                    array(
                        'authority'     => $authority,
                        'amount'        => $amount,
                        'response_data' => $response_data,
                        'error'         => $error_message,
                    )
                );


                return array(
                    'status'     => false,
                    'ref_id'     => '',
                    'message'    => $error_message,
                    'gateway_id' => $this->get_gateway_id()
                );
            }

        } catch (Exception $e) {
            $this->logger->log_error(
                'ZIBAL_ADAPTER Exception in verify_payment',
                array(
                    'authority' => $authority,
                    'amount'    => $amount,
                    'error'     => $e->getMessage(),
                )
            );
            
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