<?php
/* /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/inc/class-wallet-checkout-handler.php */
class AI_Assistant_Wallet_Checkout_Handler {
    private static $instance;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function connect_to_zarinpal($amount) {
      //  $amount_int = (int) $amount * 10;
        $amount_int = (int) $amount ;
        //error_log('🔵 [WALLET] Connecting to ZarinPal, Amount: ' . $amount);
        
        // اضافه کردن این خط - دریافت user_id
        $user_id = get_current_user_id();
        //error_log('🔵 [WALLET] User ID: ' . $user_id);
        
        $merchant_id = ai_assistant_get_zarinpal_merchant_id();
        $callback_url = home_url('/wallet-checkout?payment_verify=1');
        
        //error_log('🔵 [WALLET] Merchant ID: ' . $merchant_id);
        //error_log('🔵 [WALLET] Callback URL: ' . $callback_url);
        
        $data = array(
            'merchant_id' => $merchant_id,
            'amount' => $amount_int, // استفاده از amount به صورت integer
            'callback_url' => $callback_url,
            'description' => 'شارژ کیف پول هوش مصنوعی',
        );
        
        // استفاده از API مناسب برای sandbox
        $api_url = ZARINPAL_SANDBOX ? 
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' :
            'https://api.zarinpal.com/pg/v4/payment/request.json';
        
        $jsonData = json_encode($data);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            error_log('❌ [WALLET] cURL Error: ' . $err);
            return array('status' => false, 'message' => $err);
        } else {
            //error_log('🔵 [WALLET] ZarinPal Response: ' . $result);
            $result = json_decode($result, true);
            
            if (isset($result['errors']) && !empty($result['errors'])) {
                error_log('❌ [WALLET] ZarinPal Error: ' . print_r($result['errors'], true));
                return array(
                    'status' => false,
                    'message' => $result['errors']['message']
                );
            }
            
            if ($result['data']['code'] == 100) {
                //error_log('✅ [WALLET] Payment request successful, Authority: ' . $result['data']["authority"]);
                
                $this->save_payment_authority($user_id, $amount, $result['data']["authority"]);
                
                $gateway_url = ai_assistant_get_zarinpal_gateway_url();
                return array(
                    'status' => true,
                    'url' => $gateway_url . $result['data']["authority"],
                    'authority' => $result['data']["authority"]
                );
            } else {
                error_log('❌ [WALLET] Payment request failed, Code: ' . $result['data']['code']);
                
                return array(
                    'status' => false,
                    'message' => 'خطا در اتصال به درگاه پرداخت. کد خطا: ' . $result['data']['code']
                );
            }
        }
    }
    
    // تابع برای تأیید پرداخت
    public function verify_payment($authority, $amount) {
       // $amount_int = (int) $amount * 10;
         $amount_int = (int) $amount ;
        //error_log('🔵 [WALLET] Verifying payment, Authority: ' . $authority . ', Amount: ' . $amount);
        
        $merchant_id = ai_assistant_get_zarinpal_merchant_id();
        //error_log('🔵 [WALLET] Merchant ID for verify: ' . $merchant_id);
        
        $data = array(
            'merchant_id' => $merchant_id,
            'amount' => $amount_int, // استفاده از amount به صورت integer
            'authority' => $authority
        );
        
        // استفاده از API مناسب برای sandbox
        $api_url = ZARINPAL_SANDBOX ? 
            'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' :
            'https://api.zarinpal.com/pg/v4/payment/verify.json';
        
        $jsonData = json_encode($data);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            error_log('❌ [WALLET] Verify cURL Error: ' . $err);
            return array('status' => false, 'message' => $err);
        } else {
            //error_log('🔵 [WALLET] Verify Response: ' . $result);
            $result = json_decode($result, true);
            
            if (isset($result['errors']) && !empty($result['errors'])) {
                error_log('❌ [WALLET] Verify Error: ' . print_r($result['errors'], true));
                return array(
                    'status' => false,
                    'message' => $result['errors']['message']
                );
            }
            
            //error_log('🔵 [WALLET] Verify Code: ' . $result['data']['code']);
            
            if ($result['data']['code'] == 100) {
                //error_log('✅ [WALLET] Payment verified successfully, Ref ID: ' . $result['data']['ref_id']);
                return array(
                    'status' => true,
                    'ref_id' => $result['data']['ref_id']
                );
            } else {
                error_log('❌ [WALLET] Payment verification failed, Code: ' . $result['data']['code']);
                return array(
                    'status' => false,
                    'message' => 'پرداخت ناموفق بود. کد خطا: ' . $result['data']['code']
                );
            }
        }
    }
    
    // بعد از تابع verify_payment اضافه کنید:
    public function cleanup_old_payments() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wallet_pending_payments';
        
        // فقط اگر جدول وجود دارد اجرا شود
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $wpdb->query(
                "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
        }
    }    
    
    private function save_payment_authority($user_id, $amount, $authority) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wallet_pending_payments';
        
        $amount_int = (int) $amount; // تبدیل به integer
        
        //error_log('🔵 [WALLET] Saving payment authority: ' . $authority);
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            //error_log('🔵 [WALLET] Creating table: ' . $table_name);
            
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                amount decimal(10,2) NOT NULL,
                authority varchar(255) NOT NULL,
                status varchar(20) DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY authority (authority),
                KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            //error_log('✅ [WALLET] Table created successfully');
        } else {
            // اگر جدول وجود دارد اما ستون status وجود ندارد، آن را اضافه کنید
            $column_check = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_NAME = %s AND COLUMN_NAME = 'status'",
                $table_name
            ));
            
            if (!$column_check) {
                //error_log('🔵 [WALLET] Adding status column to existing table');
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN status varchar(20) DEFAULT 'pending'");
            }
        }
        
        // ذخیره اطلاعات - بدون ارسال status اگر ستون وجود ندارد
        $result = $wpdb->replace($table_name, array(
            'user_id' => $user_id,
            'amount' => $amount_int, // ذخیره به صورت integer
            'authority' => $authority
        ), array('%d', '%f', '%s'));
        
        if ($result === false) {
            error_log('❌ [WALLET] Failed to save authority: ' . $wpdb->last_error);
            return false;
        } else {
            //error_log('✅ [WALLET] Authority saved successfully, ID: ' . $wpdb->insert_id);
            return true;
        }
    }
    
    public function get_payment_by_authority($authority) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wallet_pending_payments';
        
        //error_log('🔵 [WALLET] Looking for authority: ' . $authority . ' in table: ' . $table_name);
        
        // بررسی وجود جدول
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            error_log('❌ [WALLET] Table does not exist: ' . $table_name);
            return false;
        }
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE authority = %s",
            $authority
        ));
        
        if ($result) {
            // تبدیل amount به integer برای اطمینان
            $result->amount = (int) $result->amount;
            //error_log('✅ [WALLET] Record found: UserID=' . $result->user_id . ', Amount=' . $result->amount);
            return $result;
        }
        
        return false;
    }
}