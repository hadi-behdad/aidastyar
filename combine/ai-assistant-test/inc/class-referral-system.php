<?php
/**
 * File: inc/class-referral-system.php
 */

class AI_Assistant_Referral_System {
    private static $instance = null;
    private $table_name;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'referrals';
        $this->maybe_create_table();
        
        // هوک سفارشی برای سیستم Job Queue شما
        add_action('ai_assistant_first_purchase_completed', [$this, 'process_referral_reward'], 10, 3);
        
        error_log("REFERRAL SYSTEM: Hooks registered successfully");
    }

    private function maybe_create_table() {
        global $wpdb;
        
        $lock_file = WP_CONTENT_DIR . '/ai_referral_table.lock';
        $lock_handle = fopen($lock_file, 'w');
        
        if (!flock($lock_handle, LOCK_EX | LOCK_NB)) {
            fclose($lock_handle);
            return;
        }
        
        try {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
                $charset_collate = $wpdb->get_charset_collate();
                
                $sql = "CREATE TABLE {$this->table_name} (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    referrer_id bigint(20) UNSIGNED NOT NULL COMMENT 'شناسه کاربر معرف',
                    referred_id bigint(20) UNSIGNED NOT NULL COMMENT 'شناسه کاربر معرفی‌شده',
                    referrer_mobile varchar(20) NOT NULL COMMENT 'شماره موبایل معرف',
                    referred_mobile varchar(20) NOT NULL COMMENT 'شماره موبایل معرفی‌شده',
                    first_purchase_completed tinyint(1) DEFAULT 0 COMMENT 'آیا اولین خرید انجام شده',
                    reward_amount decimal(15,2) DEFAULT 0 COMMENT 'مبلغ پاداش',
                    reward_paid tinyint(1) DEFAULT 0 COMMENT 'آیا پاداش پرداخت شده',
                    first_order_id bigint(20) DEFAULT NULL COMMENT 'شناسه اولین سفارش',
                    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_referral (referrer_id, referred_id),
                    KEY referrer_id (referrer_id),
                    KEY referred_id (referred_id),
                    KEY referrer_mobile (referrer_mobile),
                    KEY reward_paid (reward_paid)
                ) $charset_collate;";
                
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
                error_log("REFERRAL: Table created");
            }
        } finally {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
        }
    }

    public function register_referral($referred_user_id, $referral_code) {
        global $wpdb;
        
        $referral_code = trim($referral_code);
        
        error_log("REFERRAL REGISTER: Start - Referred User ID: $referred_user_id, Referral Code: $referral_code");
        
        // بررسی فرمت کد معرف (باید شماره موبایل باشد - 09 و 11 رقم)
        if (!preg_match('/^09[0-9]{9}$/', $referral_code)) {
            error_log("REFERRAL REGISTER: Invalid referral code format: $referral_code");
            return false;
        }
        
        // پیدا کردن کاربر معرف
        $referrer = get_users([
            'meta_key' => 'mobile',
            'meta_value' => $referral_code,
            'number' => 1
        ]);
        
        if (empty($referrer)) {
            $referrer_user = get_user_by('login', $referral_code);
            if ($referrer_user) {
                $referrer = [$referrer_user];
            }
        }
        
        if (empty($referrer)) {
            error_log("REFERRAL REGISTER: Referrer not found for code: $referral_code");
            return false;
        }
        
        $referrer_id = $referrer[0]->ID;
        
        // جلوگیری از خود-معرفی
        if ($referrer_id == $referred_user_id) {
            error_log("REFERRAL REGISTER: Self-referral detected - User ID: $referred_user_id");
            return false;
        }
        
        // دریافت شماره موبایل معرفی‌شده
        $referred_mobile = get_user_meta($referred_user_id, 'mobile', true);
        if (empty($referred_mobile)) {
            $referred_user = get_user_by('id', $referred_user_id);
            $referred_mobile = $referred_user->user_login;
        }
        
        error_log("REFERRAL REGISTER: Referrer ID: $referrer_id, Referred ID: $referred_user_id");
        
        // درج رکورد معرفی
        $result = $wpdb->insert(
            $this->table_name,
            [
                'referrer_id' => $referrer_id,
                'referred_id' => $referred_user_id,
                'referrer_mobile' => $referral_code,
                'referred_mobile' => $referred_mobile,
                'first_purchase_completed' => 0,
                'reward_paid' => 0
            ],
            ['%d', '%d', '%s', '%s', '%d', '%d']
        );
        
        if ($result) {
            error_log("REFERRAL REGISTER: Successfully inserted - Referrer: $referrer_id, Referred: $referred_user_id");
            return true;
        }
        
        error_log("REFERRAL REGISTER: Insert failed - " . $wpdb->last_error);
        return false;
    }

    public function process_referral_reward($user_id, $history_id, $final_price) {
        global $wpdb;
        
        error_log("========== REFERRAL REWARD START ==========");
        error_log("REFERRAL REWARD: User ID: $user_id, History ID: $history_id, Price: $final_price");
        
        // پیدا کردن رکورد معرفی
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE referred_id = %d AND reward_paid = 0",
            $user_id
        ));
        
        if (!$referral) {
            error_log("REFERRAL REWARD: No referral record found for User ID: $user_id");
            error_log("========== REFERRAL REWARD END (NO REFERRAL) ==========");
            return;
        }
        
        error_log("REFERRAL REWARD: Referral found - ID: {$referral->id}, Referrer: {$referral->referrer_id}");
        
        // مبلغ پاداش
        $reward_amount = get_option('ai_referral_reward_amount', 50000);
        error_log("REFERRAL REWARD: Reward amount: $reward_amount");
        
        // اضافه کردن اعتبار به حساب معرف
        $payment_handler = AI_Assistant_Payment_Handler::get_instance();
        $success = $payment_handler->add_credit(
            $referral->referrer_id,
            $reward_amount,
            sprintf('پاداش معرفی - %s (تاریخچه #%d)', $referral->referred_mobile, $history_id),
            'referral_history_' . $history_id
        );
        
        if ($success) {
            error_log("REFERRAL REWARD: Credit added successfully to User ID: {$referral->referrer_id}");
            
            // بروزرسانی رکورد معرفی
            $update_result = $wpdb->update(
                $this->table_name,
                [
                    'first_purchase_completed' => 1,
                    'reward_amount' => $reward_amount,
                    'reward_paid' => 1,
                    'first_order_id' => $history_id
                ],
                ['id' => $referral->id],
                ['%d', '%f', '%d', '%d'],
                ['%d']
            );
            
            if ($update_result !== false) {
                error_log("REFERRAL REWARD: Referral record updated - Rows affected: $update_result");
            } else {
                error_log("REFERRAL REWARD: Failed to update referral record - " . $wpdb->last_error);
            }
            
            // ارسال نوتیفیکیشن
            $this->send_referral_notification($referral->referrer_id, $reward_amount, $referral->referred_mobile);
            
            error_log("REFERRAL REWARD: Process completed successfully");
            error_log("========== REFERRAL REWARD END (SUCCESS) ==========");
        } else {
            error_log("REFERRAL REWARD: Failed to add credit to User ID: {$referral->referrer_id}");
            error_log("========== REFERRAL REWARD END (CREDIT FAILED) ==========");
        }
    }

    private function send_referral_notification($referrer_id, $amount, $referred_mobile) {
        error_log("REFERRAL NOTIFICATION: Sending to User ID: $referrer_id, Amount: $amount");
        
        try {
            if (!class_exists('AI_Assistant_Notification_Manager')) {
                error_log("REFERRAL NOTIFICATION: Notification manager class not found");
                return;
            }
            
            $notification_manager = AI_Assistant_Notification_Manager::get_instance();
            
            if (!method_exists($notification_manager, 'send_notification')) {
                error_log("REFERRAL NOTIFICATION: send_notification method not found");
                return;
            }
            
            $notification_manager->send_notification(
                $referrer_id,
                'پاداش معرفی',
                sprintf('مبلغ %s تومان بابت خرید کاربر %s به اعتبار شما اضافه شد.', number_format($amount), $referred_mobile),
                'referral_reward'
            );
            
            error_log("REFERRAL NOTIFICATION: Sent successfully");
            
        } catch (Throwable $e) {
            // خطای نوتیفیکیشن را ignore می‌کنیم
            error_log("REFERRAL NOTIFICATION: Error (ignored) - " . $e->getMessage());
        }
    }


    public function get_referral_stats($user_id) {
        global $wpdb;
        
        $stats = [
            'total_referrals' => 0,
            'completed_purchases' => 0,
            'pending_purchases' => 0,
            'total_earned' => 0,
            'recent_referrals' => []
        ];
        
        $stats['total_referrals'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE referrer_id = %d",
            $user_id
        ));
        
        $stats['completed_purchases'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE referrer_id = %d AND first_purchase_completed = 1",
            $user_id
        ));
        
        $stats['pending_purchases'] = $stats['total_referrals'] - $stats['completed_purchases'];
        
        $stats['total_earned'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(reward_amount) FROM {$this->table_name} WHERE referrer_id = %d AND reward_paid = 1",
            $user_id
        ));
        
        if (is_null($stats['total_earned'])) {
            $stats['total_earned'] = 0;
        }
        
        $stats['recent_referrals'] = $wpdb->get_results($wpdb->prepare(
            "SELECT referred_mobile, first_purchase_completed, reward_amount, created_at 
             FROM {$this->table_name} 
             WHERE referrer_id = %d 
             ORDER BY created_at DESC 
             LIMIT 10",
            $user_id
        ));
        
        return $stats;
    }

    public function get_referral_link($user_id) {
        $user_mobile = get_user_meta($user_id, 'mobile', true);
        if (empty($user_mobile)) {
            $user = get_user_by('id', $user_id);
            $user_mobile = $user->user_login;
        }
        
        return add_query_arg('ref', $user_mobile, home_url('/otp-login/'));
    }
}

// اجرای سیستم
AI_Assistant_Referral_System::get_instance();
