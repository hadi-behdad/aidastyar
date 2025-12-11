<?php
// /inc/class-notification-manager.php

class AI_Assistant_Notification_Manager {
    private static $instance;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ارسال ایمیل به مشاور برای درخواست جدید
     */
    public function send_consultation_request($consultant_id, $request_id) {
        $consultant = get_user_by('id', $consultant_id);
        if (!$consultant) {
            error_log('[Notification] Consultant not found: ' . $consultant_id);
            return false;
        }
        
        
                    
        $Consultation_DB = AI_Assistant_Diet_Consultation_DB::get_instance();
        $contract = $Consultation_DB->get_active_contract($consultant_id);  
        $full_payment_hours = $contract ->full_payment_hours;
        
        $deadline_date = date_i18n('j F Y - H:i', strtotime("+{$full_payment_hours} hours"));


        // $consultation_url = admin_url("admin.php?page=nutrition-consultation&action=review&id={$request_id}");
        
        $consultation_url = home_url("/consultant-dashboard");
         
        $deadline_date = date_i18n('j F Y - H:i', strtotime('+1 days'));
        
        // محتوای ایمیل با قالب جدید
        $email_content = "
            <p>سلام <strong>{$consultant->display_name}</strong> عزیز،</p>
            <p>یک درخواست جدید برای بازبینی رژیم غذایی در سامانه ثبت شده است.</p>
            
            " . AI_Assistant_Email_Template::create_deadline_box("
                <strong>⏰ مهلت بررسی:</strong><br>
                {$deadline_date}
            ") . "
            
            <p>لطفاً در اسرع وقت این درخواست را بررسی و پاسخ لازم را ارائه نمایید.</p>
            
            " . AI_Assistant_Email_Template::create_button($consultation_url, '🔍 مشاهده و بررسی درخواست') . "
            
            <p style='margin-top: 30px;'>
                برای دسترسی به تمامی درخواست‌ها می‌توانید به پنل مدیریت مراجعه کنید.
            </p>
        ";
        
        $subject = '📋 درخواست جدید بازبینی رژیم غذایی';
        $message = AI_Assistant_Email_Template::get_email_template($email_content, $subject);
        
        return $this->send_email($consultant->user_email, $subject, $message);
    }

    /**
     * ارسال ایمیل به کاربر برای نتیجه بازبینی
     */
    public function send_consultation_result($user_id, $request_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('[Notification] User not found: ' . $user_id);
            return false;
        }

        $history_url = home_url("/page-user-history/");
        
        // محتوای ایمیل با قالب جدید
        $email_content = "
            <p>سلام <strong>{$user->display_name}</strong> عزیز،</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; display: inline-block;'>
                    <h3 style='margin: 0; color: #155724;'>✅ رژیم غذایی شما تایید شد</h3>
                </div>
            </div>
            
            <p>رژیم غذایی شما توسط مشاور تغذیه به طور کامل بررسی و تایید نهایی شد.</p>
            
            <p>هم اکنون می‌توانید رژیم نهایی و توصیه‌های مشاور را مشاهده کنید.</p>
            
            " . AI_Assistant_Email_Template::create_button($history_url, '📄 مشاهده رژیم نهایی') . "
            
            " . AI_Assistant_Email_Template::create_info_box("
                <strong>💡 نکته مهم:</strong><br>
                برای دسترسی همیشگی به رژیم غذایی خود، این صفحه را در مرورگر خود بوکمارک کنید.
            ") . "
            
            <p>در صورت وجود هرگونه سوال، تیم پشتیبانی آماده پاسخگویی به شما می‌باشد.</p>
        ";
        
        $subject = '✅ نتیجه بازبینی رژیم غذایی شما';
        $message = AI_Assistant_Email_Template::get_email_template($email_content, $subject);
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * تابع عمومی ارسال ایمیل
     */
    private function send_email($to, $subject, $message) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <info@aidastyar.com>'
        ];

        $result = wp_mail($to, $subject, trim($message), $headers);

        if (!$result) {
            error_log("[Notification] Email sending failed to {$to} (Subject: {$subject})");
        }

        return $result;
    }
    
    /**
     * دریافت متن terms برای یک کاربر و سرویس
     * @param int $user_id
     * @param string $service_id
     * @return array|null
     */
    private function get_user_terms_acceptance($user_id, $service_id = 'diet') {
        $terms_db = Terms_Acceptance_DB::get_instance();
        
        // دریافت آخرین terms acceptance برای این کاربر و سرویس
        $acceptance = $terms_db->get_latest_acceptance($user_id, $service_id);
        
        if (!$acceptance) {
            error_log('[Notification] No terms acceptance found for user: ' . $user_id);
            return null;
        }
        
        return $acceptance;
    }
    
    /**
     * ارسال ایمیل به کاربر: رژیم + قوانین پذیرفته‌شده
     * @param int $user_id
     * @param int $request_id
     * @param string $diet_content (محتوای رژیم)
     */
    public function send_result_ready_with_terms($user_id, $request_id, $diet_content = '') {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('[Notification] User not found: ' . $user_id);
            return false;
        }
    
        $history_url = home_url("/page-user-history/");
        
        // دریافت متن terms که کاربر تائید کرده
        $terms_acceptance = $this->get_user_terms_acceptance($user_id, 'diet');
        
        // ساخت بخش terms در ایمیل
        $terms_section = '';
        if ($terms_acceptance) {
            $terms_content = $terms_acceptance->terms_content ?? '';
            
            // برای ایمیل، فقط خلاصه‌ای از terms (اولین ۵۰۰ کاراکتر)
            $terms_preview = substr(strip_tags($terms_content), 0, 500) . '...';
            
            $terms_section = "
                " . AI_Assistant_Email_Template::create_deadline_box("
                    <strong>📋 قوانین و شرایط پذیرفته‌شده:</strong><br>
                    <div style='background: #f5f5f5; padding: 15px; border-radius: 8px; margin-top: 10px; font-size: 12px; direction: rtl;'>
                        " . nl2br(htmlspecialchars($terms_preview)) . "
                    </div>
                    <p style='margin-top: 10px; font-size: 12px; color: #666;'>
                        <a href='" . $history_url . "'>برای مشاهده قوانین کامل اینجا را کلیک کنید</a>
                    </p>
                ") . "
            ";
        }
        
        // ساخت محتوای ایمیل
        $email_content = "
            <p>سلام <strong>{$user->display_name}</strong> عزیز،</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; display: inline-block;'>
                    <h3 style='margin: 0; color: #155724;'>🎉 رژیم غذایی شما آماده است!</h3>
                </div>
            </div>
            
            <p>رژیم غذایی شما آماده شده و قابل مشاهده است</p>
            <p>هم اکنون می‌توانید رژیم نهایی و توصیه‌ها را در بخش تاریخچه مشاهده کنید.</p>            
            
            " . AI_Assistant_Email_Template::create_button($history_url, '📋 مشاهده رژیم آماده‌شده') . "
            
            " . $terms_section . "
            
            " . AI_Assistant_Email_Template::create_deadline_box("
                <strong>📅 تاریخ تکمیل:</strong><br>
                " . date_i18n('j F Y - H:i') . "
            ") . "
            
            " . AI_Assistant_Email_Template::create_info_box("
                <strong>💡 نکته:</strong><br>
                برای رفع ابهام‌ها یا درخواست بازنگری، می‌توانید از طریق صفحه تاریخچه با مشاور تماس بگیرید.
            ") . "
        ";
        
        $subject = '🎉 رژیم غذایی شما آماده است!';
        $message = AI_Assistant_Email_Template::get_email_template($email_content, $subject);
        
        return $this->send_email($user->user_email, $subject, $message);
    }
    
    
    /**
     * ارسال ایمیل به کاربر: درخواست ثبت شد
     * فراخوانی هنگام ثبت درخواست جدید
     */
    public function send_request_received($user_id, $request_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('[Notification] User not found: ' . $user_id);
            return false;
        }
    
        $history_url = home_url("/page-user-history/");
        
        $email_content = "
            <p>سلام <strong>{$user->display_name}</strong> عزیز،</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <div style='background: #cce5ff; color: #004085; padding: 20px; border-radius: 10px; display: inline-block;'>
                    <h3 style='margin: 0; color: #004085;'>✓ درخواست شما با موفقیت ثبت شد</h3>
                </div>
            </div>
            
            
            <p>درخواست تهیه رژیم غذایی شما با موفقیت ثبت گردید.</p>            
            <p>تا زمانی که رژیم نهایی شما آماده شود، ایمیل اطلاع‌رسانی برای شما ارسال خواهد شد.</p>
            
            " . AI_Assistant_Email_Template::create_info_box("
                <strong>⏳ وضعیت:</strong><br>
                درخواست شما در لیست انتظار است. با تکمیل بررسی، فوری‌ترین طریق ممکن اطلاع دهیم.
            ") . "
            
            " . AI_Assistant_Email_Template::create_button($history_url, '📊 مشاهده وضعیت درخواست') . "
            
            <p style='margin-top: 30px; color: #666;'>
                برای پیگیری درخواست‌های خود می‌توانید هر زمان به بخش تاریخچه مراجعه کنید.
            </p>
        ";
        
        $subject = '✓ درخواست رژیم غذایی شما ثبت شد';
        $message = AI_Assistant_Email_Template::get_email_template($email_content, $subject);
        
        return $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * ارسال ایمیل به کاربر: رژیم آماده شد
     * فراخوانی هنگام تایید نهایی رژیم توسط مشاور
     */
    public function send_result_ready($user_id, $request_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('[Notification] User not found: ' . $user_id);
            return false;
        }
    
        $history_url = home_url("/page-user-history/");
        
        $email_content = "
            <p>سلام <strong>{$user->display_name}</strong> عزیز،</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; display: inline-block;'>
                    <h3 style='margin: 0; color: #155724;'>🎉 رژیم غذایی شما آماده است!</h3>
                </div>
            </div>
            
            <p>رژیم غذایی شما آماده شده و قابل مشاهده است</p>
            <p>هم اکنون می‌توانید رژیم نهایی و توصیه‌ها را در بخش تاریخچه مشاهده کنید.</p>            
            
            " . AI_Assistant_Email_Template::create_button($history_url, '📋 مشاهده رژیم آماده‌شده') . "
            
            " . AI_Assistant_Email_Template::create_deadline_box("
                <strong>📅 تاریخ تکمیل:</strong><br>
                " . date_i18n('j F Y - H:i') . "
            ") . "
            
            " . AI_Assistant_Email_Template::create_info_box("
                <strong>💡 نکته:</strong><br>
                برای رفع ابهام‌ها یا درخواست بازنگری، می‌توانید از طریق صفحه تاریخچه با مشاور تماس بگیرید.
            ") . "
        ";
        
        $subject = '🎉 رژیم غذایی شما آماده است!';
        $message = AI_Assistant_Email_Template::get_email_template($email_content, $subject);
        
        return $this->send_email($user->user_email, $subject, $message);
    }
    
}