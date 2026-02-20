<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AI_Assistant_Api_Handler {
    private static $instance;
   
    private $logger;

    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // تغییر نام option به ai_assistant_deepseek_key
       
        $this->logger = AI_Assistant_Logger::get_instance();
        
        $this->register_hooks();

        // اطمینان از وجود پوشه لاگ
        if (!file_exists(WP_CONTENT_DIR.'/ai-assistant-logs')) {
            wp_mkdir_p(WP_CONTENT_DIR.'/ai-assistant-logs');
        }
    }   

    private function register_hooks() {
   
        add_action('wp_ajax_ai_assistant_process', [$this, 'process_request']);
        add_action('wp_ajax_nopriv_ai_assistant_process', [$this, 'handle_unauthorized']);
    }









    public function process_request() {
        global $wpdb;
        
        check_ajax_referer('ai_assistant_nonce', 'security');         

        if (ob_get_length()) ob_clean();

        // Initialize transaction flag
        $transaction_started = false;
        
        try {
            // Validate user
            $user_id = get_current_user_id();
            if (!$user_id) {
                wp_send_json_error(__('برای استفاده از این سرویس باید وارد شوید.', 'ai-assistant'));
                return;
            }
            
            // Validate and sanitize inputs
            if (empty($_POST['service_id'])) {
                throw new Exception('شناسه سرویس ارسال نشده است');
            }
            
            if (empty($_POST['userData'])) {
                throw new Exception('اطلاعات کاربر ارسال نشده است');
            }
            
            $service_id = sanitize_text_field($_POST['service_id']);
            $userData = stripslashes($_POST['userData']);
            
            // Validate service exists
            $all_services = get_option('ai_assistant_services', []);
            if (!isset($all_services[$service_id])) {
                throw new Exception('سرویس درخواستی یافت نشد');
            }
            
            $service_name = $all_services[$service_id]['name'];
            
            error_log('📝 [API_HANDLER] Starting request for service: ' . $service_name . ', user: ' . $user_id);
            
            // Validate userData JSON
            $decoded_data = json_decode($userData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('فرمت داده‌های ارسالی نامعتبر است: ' . json_last_error_msg());
            }
            
            // ============================================
            // START TRANSACTION
            // ============================================
            $wpdb->query('START TRANSACTION');
            $transaction_started = true;
            
            error_log('🔄 [TRANSACTION] Started for user: ' . $user_id);
            
            try {
                // Step 1: Save history
                $history_manager = AI_Assistant_History_Manager::get_instance();
                $history_id = $history_manager->save_history(
                    $user_id,
                    $service_id,
                    $service_name,
                    $userData,
                    null // response is null initially
                );
                
                if ($history_id === false || empty($history_id)) {
                    throw new Exception('خطا در ذخیره تاریخچه درخواست');
                }
                
                error_log('✅ [API_HANDLER] History saved with ID: ' . $history_id);
                
                // Step 2: Enqueue job
                $job_processor = AI_Assistant_Process_Requests_Job::get_instance();
                $job_id = $job_processor->enqueue_job($history_id, $user_id);
                
                if ($job_id === false || empty($job_id)) {
                    throw new Exception('خطا در ثبت درخواست در صف پردازش');
                }
                
                error_log('✅ [API_HANDLER] Job enqueued with ID: ' . $job_id);
                
                // ============================================
                // COMMIT TRANSACTION
                // ============================================
                $wpdb->query('COMMIT');
                $transaction_started = false;


                // در کلاس یا فایلی که درخواست را ثبت می‌کنید
                $notification_manager = AI_Assistant_Notification_Manager::get_instance();
                // بعد از ثبت درخواست
                $notification_manager->send_request_received($user_id, $history_id);
                                
                error_log('✅ [TRANSACTION] Committed successfully - History: ' . $history_id . ', Job: ' . $job_id);
                
                // Log discount info if exists (optional, after successful commit)
                if (isset($decoded_data['discountInfo']) && !empty($decoded_data['discountInfo'])) {
                    $this->logger->log('DISCOUNT INFO:', [
                        'history_id' => $history_id,
                        'job_id' => $job_id,
                        'discount_info' => $decoded_data['discountInfo']
                    ]);
                }
                
                // Send success response
                header('Content-Type: application/json; charset=utf-8');
                wp_send_json_success([
                    'message' => 'درخواست شما با موفقیت ثبت شد',
                    'history_id' => $history_id,
                    'job_id' => $job_id,
                    'response' => true
                ]);
                
            } catch (Exception $inner_e) {
                // Something went wrong, rollback
                if ($transaction_started) {
                    $wpdb->query('ROLLBACK');
                    error_log('🔄 [TRANSACTION] Rolled back due to error: ' . $inner_e->getMessage());
                }
                
                // Re-throw to outer catch
                throw $inner_e;
            }

        } catch (Exception $e) {
            // Rollback if transaction is still active
            if (isset($transaction_started) && $transaction_started) {
                try {
                    $wpdb->query('ROLLBACK');
                    error_log('🔄 [TRANSACTION] Rolled back in outer catch');
                } catch (Exception $rollback_e) {
                    error_log('❌ [ROLLBACK] Failed: ' . $rollback_e->getMessage());
                }
            }
            
            // Log error
            $error_message = $e->getMessage();
            error_log('❌ [API_HANDLER] Error: ' . $error_message);
            
            $this->logger->log_error($error_message, [
                'post_data' => $_POST,
                'user_id' => isset($user_id) ? $user_id : 'unknown',
                'service_id' => isset($service_id) ? $service_id : 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            // Send error response
            wp_send_json_error([
                'message' => $error_message,
                'error_code' => 'REQUEST_FAILED'
            ]);
        }

        wp_die();
    }
    
    public function handle_unauthorized() {
        wp_send_json_error([
            'message' => 'برای استفاده از این سرویس باید وارد حساب کاربری خود شوید',
            'login_url' => wp_login_url()
        ], 401);
    }
}
