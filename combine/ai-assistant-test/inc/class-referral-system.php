<?php
/**
 * File: inc/class-referral-system.php
 * Referral System with Reward Cap Implementation
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
        $this->table_name = $wpdb->prefix . 'ai_referrals';
        $this->maybe_create_table();
        
        // Job Queue Hook
        add_action('ai_assistant_first_purchase_completed', array($this, 'process_referral_reward'), 10, 3);
        
        // error_log('REFERRAL SYSTEM: Hooks registered successfully');
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
                    referrer_id bigint(20) UNSIGNED NOT NULL COMMENT 'کاربر معرف',
                    referred_id bigint(20) UNSIGNED NOT NULL COMMENT 'کاربر معرفی شده',
                    referrer_mobile varchar(20) NOT NULL COMMENT 'موبایل معرف',
                    referred_mobile varchar(20) NOT NULL COMMENT 'موبایل معرفی شده',
                    first_purchase_completed tinyint(1) DEFAULT 0 COMMENT 'اولین خرید انجام شد؟',
                    reward_amount decimal(15,2) DEFAULT 0 COMMENT 'مبلغ پاداش پرداخت شده',
                    reward_paid tinyint(1) DEFAULT 0 COMMENT 'پاداش پرداخت شد؟',
                    first_order_id bigint(20) DEFAULT NULL COMMENT 'شناسه اولین سفارش',
                    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY unique_referral (referrer_id, referred_id),
                    KEY referrer_id (referrer_id),
                    KEY referred_id (referred_id),
                    KEY referrer_mobile (referrer_mobile),
                    KEY reward_paid (reward_paid)
                ) $charset_collate;";
                
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
                
                // error_log('REFERRAL: Table created');
            }
        } finally {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
        }
    }

    /**
     * محاسبه مجموع پاداش‌های دریافت شده توسط یک کاربر
     */
    public function get_total_earned_rewards($user_id) {
        global $wpdb;
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(reward_amount) 
             FROM {$this->table_name} 
             WHERE referrer_id = %d 
             AND reward_paid = 1",
            $user_id
        ));
        
        return $total ? floatval($total) : 0;
    }

    /**
     * محاسبه مبلغ پاداشی که می‌تواند پرداخت شود (با در نظر گرفتن سقف)
     */
    public function calculate_allowed_reward($referrer_id, $full_reward_amount) {
        // حداکثر مجموع پاداش مجاز
        $max_total_reward = defined('AI_REFERRAL_MAX_REWARD_TOTAL') 
            ? AI_REFERRAL_MAX_REWARD_TOTAL 
            : 150000; // default
        
        // مجموع پاداش‌های قبلی
        $current_total = $this->get_total_earned_rewards($referrer_id);
        
        // فضای باقی‌مانده
        $remaining_space = $max_total_reward - $current_total;
        
        // اگر قبلاً به سقف رسیده
        if ($remaining_space <= 0) {
            // error_log("REFERRAL: User {$referrer_id} has reached maximum reward limit");
            return 0;
        }
        
        // اگر پاداش کامل جا داره
        if ($full_reward_amount <= $remaining_space) {
            return $full_reward_amount;
        }
        
        // فقط مقدار باقی‌مانده رو پرداخت کن
        // error_log("REFERRAL: Partial reward for User {$referrer_id} - {$remaining_space} out of {$full_reward_amount}");
        return $remaining_space;
    }

    /**
     * ثبت کد معرف
     */
    public function register_referral($referred_user_id, $referral_code) {
        global $wpdb;
        
        $referral_code = trim($referral_code);
        // error_log("REFERRAL REGISTER: Start - Referred User ID: {$referred_user_id}, Referral Code: {$referral_code}");
        
        // بررسی فرمت کد معرف - باید شماره موبایل 09 باشه و 11 رقمی
        if (!preg_match('/^09[0-9]{9}$/', $referral_code)) {
            //error_log("REFERRAL REGISTER: Invalid referral code format: {$referral_code}");
            return false;
        }
        
        // پیدا کردن کاربر معرف
        $referrer = get_users(array(
            'meta_key' => 'mobile',
            'meta_value' => $referral_code,
            'number' => 1
        ));
        
        if (empty($referrer)) {
            // اگر با meta پیدا نشد، با user_login امتحان کن
            $referrer_user = get_user_by('login', $referral_code);
            if ($referrer_user) {
                $referrer = array($referrer_user);
            }
        }
        
        if (empty($referrer)) {
            //error_log("REFERRAL REGISTER: Referrer not found for code: {$referral_code}");
            return false;
        }
        
        $referrer_id = $referrer[0]->ID;
        
        // جلوگیری از خود-معرفی
        if ($referrer_id == $referred_user_id) {
            //error_log("REFERRAL REGISTER: Self-referral detected - User ID: {$referred_user_id}");
            return false;
        }
        
        // موبایل کاربر معرفی شده
        $referred_mobile = get_user_meta($referred_user_id, 'mobile', true);
        if (empty($referred_mobile)) {
            $referred_user = get_user_by('id', $referred_user_id);
            $referred_mobile = $referred_user->user_login;
        }
        
        //error_log("REFERRAL REGISTER: Referrer ID: {$referrer_id}, Referred ID: {$referred_user_id}");
        
        // ثبت در دیتابیس
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'referrer_id' => $referrer_id,
                'referred_id' => $referred_user_id,
                'referrer_mobile' => $referral_code,
                'referred_mobile' => $referred_mobile,
                'first_purchase_completed' => 0,
                'reward_paid' => 0
            ),
            array('%d', '%d', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            //error_log("REFERRAL REGISTER: Successfully inserted - Referrer: {$referrer_id}, Referred: {$referred_user_id}");
            return true;
        } else {
            //error_log("REFERRAL REGISTER: Insert failed - " . $wpdb->last_error);
            return false;
        }
    }

    /**
     * پردازش پاداش معرفی - با سقف محدودیت
     */
    public function process_referral_reward($user_id, $history_id, $final_price) {
        global $wpdb;
        
        //error_log("========== REFERRAL REWARD START ==========");
        //error_log("REFERRAL REWARD: User ID: {$user_id}, History ID: {$history_id}, Price: {$final_price}");
        
        // پیدا کردن رکورد معرفی
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE referred_id = %d 
             AND reward_paid = 0",
            $user_id
        ));
        
        if (!$referral) {
            //error_log("REFERRAL REWARD: No referral record found for User ID: {$user_id}");
            //error_log("========== REFERRAL REWARD END (NO REFERRAL) ==========");
            return;
        }
        
        //error_log("REFERRAL REWARD: Referral found - ID: {$referral->id}, Referrer: {$referral->referrer_id}");
        
        // مبلغ پاداش کامل
        $full_reward_amount = defined('AI_REFERRAL_REWARD_AMOUNT') 
            ? AI_REFERRAL_REWARD_AMOUNT 
            : 50000; // default
        
        // محاسبه مبلغ مجاز با توجه به سقف
        $allowed_reward = $this->calculate_allowed_reward($referral->referrer_id, $full_reward_amount);
        
        if ($allowed_reward <= 0) {
            //error_log("REFERRAL REWARD: Referrer {$referral->referrer_id} has reached maximum reward limit");
            
            // ثبت در دیتابیس که پاداش به دلیل سقف پرداخت نشد
            $wpdb->update(
                $this->table_name,
                array(
                    'first_purchase_completed' => 1,
                    'reward_amount' => 0,
                    'reward_paid' => 1, // علامت زده می‌شه اما مبلغ 0
                    'first_order_id' => $history_id
                ),
                array('id' => $referral->id),
                array('%d', '%f', '%d', '%d'),
                array('%d')
            );
            
            //error_log("========== REFERRAL REWARD END (CAP REACHED) ==========");
            return;
        }
        
        //error_log("REFERRAL REWARD: Full reward: {$full_reward_amount}, Allowed: {$allowed_reward}");
        
        // پرداخت پاداش
        $payment_handler = AI_Assistant_Payment_Handler::get_instance();
        $success = $payment_handler->add_credit(
            $referral->referrer_id,
            $allowed_reward,
            sprintf(
                'پاداش معرفی کاربر %s (تاریخچه %d)',
                $referral->referred_mobile,
                $history_id
            ),
            'referral_history_' . $history_id
        );
        
        if ($success) {
            //error_log("REFERRAL REWARD: Credit added successfully to User ID: {$referral->referrer_id}");
            
            // به‌روزرسانی رکورد
            $update_result = $wpdb->update(
                $this->table_name,
                array(
                    'first_purchase_completed' => 1,
                    'reward_amount' => $allowed_reward, // مبلغ واقعی پرداخت شده
                    'reward_paid' => 1,
                    'first_order_id' => $history_id
                ),
                array('id' => $referral->id),
                array('%d', '%f', '%d', '%d'),
                array('%d')
            );
            
            if ($update_result !== false) {
                //error_log("REFERRAL REWARD: Referral record updated - Rows affected: {$update_result}");
            } else {
                //error_log("REFERRAL REWARD: Failed to update referral record - " . $wpdb->last_error);
            }
            
            // ارسال نوتیفیکیشن
            $this->send_referral_notification($referral->referrer_id, $allowed_reward, $referral->referred_mobile);
            
            //error_log("REFERRAL REWARD: Process completed successfully");
            //error_log("========== REFERRAL REWARD END (SUCCESS) ==========");
        } else {
            //error_log("REFERRAL REWARD: Failed to add credit to User ID: {$referral->referrer_id}");
            //error_log("========== REFERRAL REWARD END (CREDIT FAILED) ==========");
        }
    }

    /**
     * ارسال نوتیفیکیشن پاداش معرفی
     */
    private function send_referral_notification($referrer_id, $amount, $referred_mobile) {
        //error_log("REFERRAL NOTIFICATION: Sending to User ID: {$referrer_id}, Amount: {$amount}");
        
        try {
            if (!class_exists('AIAssistantNotificationManager')) {
                //error_log("REFERRAL NOTIFICATION: Notification manager class not found");
                return;
            }
            
            $notification_manager = AIAssistantNotificationManager::get_instance();
            
            if (!method_exists($notification_manager, 'send_notification')) {
                //error_log("REFERRAL NOTIFICATION: send_notification method not found");
                return;
            }
            
            $notification_manager->send_notification(
                $referrer_id,
                sprintf(
                    'شما %s تومان پاداش معرفی کاربر %s دریافت کردید.',
                    number_format($amount),
                    $referred_mobile
                ),
                'referral_reward'
            );
            
            //error_log("REFERRAL NOTIFICATION: Sent successfully");
        } catch (Throwable $e) {
            // Ignore notification errors
            //error_log("REFERRAL NOTIFICATION: Error ignored - " . $e->getMessage());
        }
    }

    /**
     * دریافت آمار معرفی‌ها
     */
    public function get_referral_stats($user_id) {
        global $wpdb;
        
        $stats = array(
            'total_referrals' => 0,
            'completed_purchases' => 0,
            'pending_purchases' => 0,
            'total_earned' => 0,
            'recent_referrals' => array()
        );
        
        $stats['total_referrals'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE referrer_id = %d",
            $user_id
        ));
        
        $stats['completed_purchases'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE referrer_id = %d AND first_purchase_completed = 1",
            $user_id
        ));
        
        $stats['pending_purchases'] = $stats['total_referrals'] - $stats['completed_purchases'];
        
        $stats['total_earned'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(reward_amount) FROM {$this->table_name} 
             WHERE referrer_id = %d AND reward_paid = 1",
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

    /**
     * دریافت لینک معرفی
     */
    public function get_referral_link($user_id) {
        $user_mobile = get_user_meta($user_id, 'mobile', true);
        
        if (empty($user_mobile)) {
            $user = get_user_by('id', $user_id);
            $user_mobile = $user->user_login;
        }
        
        return add_query_arg('ref', $user_mobile, home_url('/otp-login'));
    }
}

// Initialize
AI_Assistant_Referral_System::get_instance();
