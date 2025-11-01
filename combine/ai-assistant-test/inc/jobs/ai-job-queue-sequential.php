<?php
if (!defined('ABSPATH')) exit;

class AI_Job_Queue_Sequential {
    private static $instance = null;
    private $table_name;
    private $processing_timeout = 600;
    private $logger;
    private $is_processing = false;

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'job_queue';
        $this->logger = AI_Assistant_Logger::get_instance();

        // جایگزین کردن هوک اصلی پردازش با نسخه ترتیبی
        remove_action('ai_process_job_queue', [AI_Job_Queue::get_instance(), 'process_pending_jobs']);
        add_action('ai_process_job_queue', [$this, 'process_jobs_sequentially']);

        // اضافه کردن هوک برای پردازش بلافاصله پس از ثبت جاب
        add_action('ai_job_enqueued', [$this, 'maybe_process_immediately'], 10, 1);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * پردازش ترتیبی جاب‌ها - فقط یک جاب در هر اجرا
     */
    public function process_jobs_sequentially() {
        global $wpdb;

        // جلوگیری از اجرای موازی
        if ($this->is_processing) {
            error_log('⏸️ [SEQUENTIAL] Another process is already running, skipping');
            return;
        }

        $this->is_processing = true;
        
        try {
            error_log('🔄 [SEQUENTIAL] Starting sequential processing cycle');

            // پاکسازی جاب‌های گیر کرده
            $this->cleanup_stuck_jobs();

            // بررسی اگر جاب در حال پردازش وجود دارد
            $processing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'");
            
            if ($processing_count > 0) {
                error_log('⏸️ [SEQUENTIAL] A job is already being processed, waiting...');
                $this->is_processing = false;
                return;
            }

            // دریافت قدیمی ترین جاب در حالت pending
            $pending_job = $wpdb->get_row("
                SELECT * FROM {$this->table_name} 
                WHERE status = 'pending' 
                ORDER BY id ASC 
                LIMIT 1
            ");

            if (!$pending_job) {
                error_log('📭 [SEQUENTIAL] No pending jobs found');
                $this->is_processing = false;
                return;
            }

            // رزرو کردن جاب برای پردازش
            $now = current_time('mysql');
            $updated = $wpdb->update(
                $this->table_name,
                [
                    'status' => 'processing',
                    'started_at' => $now,
                    'last_attempt' => $now,
                    'processing_log' => $pending_job->processing_log . "\n[SEQUENTIAL] Claimed at " . $now
                ],
                ['id' => $pending_job->id, 'status' => 'pending'],
                ['%s', '%s', '%s', '%s'],
                ['%d', '%s']
            );

            if (!$updated || $wpdb->rows_affected === 0) {
                error_log('⚠️ [SEQUENTIAL] Could not claim job #' . $pending_job->id . ' (race condition)');
                $this->is_processing = false;
                return;
            }

            error_log('🚀 [SEQUENTIAL] Starting processing of job #' . $pending_job->id);

            // پردازش مستقیم جاب
            $this->process_single_job_direct($pending_job->id);

        } catch (Exception $e) {
            error_log('❌ [SEQUENTIAL] Error in sequential processing: ' . $e->getMessage());
        } finally {
            $this->is_processing = false;
        }
    }

    /**
     * پردازش مستقیم یک جاب
     */
    private function process_single_job_direct($job_id) {
        global $wpdb;

        error_log('🎯 [SEQUENTIAL-WORKER] Processing job #' . $job_id);

        // بارگذاری جاب
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $job_id));
        if (!$job) {
            error_log('❌ [SEQUENTIAL-WORKER] Job #' . $job_id . ' not found');
            return false;
        }

        try {
            // تنظیمات محیط اجرا
            set_time_limit(300);
            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '256M');

            // اعتبارسنجی جاب
            $this->validate_job($job);

            // فراخوانی API
            error_log('📡 [SEQUENTIAL-WORKER] Calling API for job #' . $job_id);
            $start_time = microtime(true);

            $response = $this->call_deepseek_api($job->prompt);
            $api_time = round(microtime(true) - $start_time, 2);

            if (!$response || (is_array($response) && isset($response['error']))) {
                $err = is_array($response) && isset($response['error']) ? $response['error'] : 'Empty or invalid API response';
                throw new Exception("API call failed: " . $err);
            }

            $cleaned_response = $this->clean_api_response($response);

            // شروع تراکنش
            $wpdb->query('START TRANSACTION');

            // کسر اعتبار
            error_log('💰 [SEQUENTIAL-WORKER] Deducting credit for job #' . $job_id);
            $payment_handler = AI_Assistant_Payment_Handler::get_instance();
            $credit_success = $payment_handler->deduct_credit(
                $job->user_id,
                $job->final_price,
                'استفاده از سرویس: ' . $job->service_id,
                'job_' . $job_id
            );

            if ($credit_success === false || (is_array($credit_success) && isset($credit_success['error']))) {
                $err = is_array($credit_success) && isset($credit_success['error']) ? $credit_success['error'] : 'Deduct credit failed';
                throw new Exception("Payment deduction failed: " . $err);
            }

            // ذخیره تاریخچه
            error_log('📝 [SEQUENTIAL-WORKER] Saving history for job #' . $job_id);
            $history_manager = AI_Assistant_History_Manager::get_instance();
            $history_success = $history_manager->save_history(
                $job->user_id,
                $job->service_id,
                $job->service_id,
                maybe_unserialize($job->user_data),
                $response
            );

            if ($history_success === false || empty($history_success)) {
                throw new Exception('Failed to save history');
            }

            $wpdb->query('COMMIT');

            // علامت گذاری به عنوان انجام شده
            $update_success = $wpdb->update(
                $this->table_name,
                [
                    'status' => 'done',
                    'updated_at' => current_time('mysql'),
                    'processing_log' => $job->processing_log . "\n[SUCCESS] Completed in " . $api_time . "s at " . current_time('mysql')
                ],
                ['id' => $job_id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            if ($update_success) {
                error_log('✅ [SEQUENTIAL-WORKER] Job #' . $job_id . ' completed successfully in ' . $api_time . 's');
                
                // تلاش برای پردازش جاب بعدی بلافاصله
                $this->schedule_next_processing();
                return true;
            } else {
                throw new Exception('Failed to update job status');
            }

        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log('❌ [SEQUENTIAL-WORKER] Job #' . $job_id . ' failed: ' . $error_message);

            $wpdb->update(
                $this->table_name,
                [
                    'status' => 'error',
                    'error_message' => substr($error_message, 0, 500),
                    'updated_at' => current_time('mysql'),
                    'processing_log' => $job->processing_log . "\n[ERROR] " . $error_message . " at " . current_time('mysql')
                ],
                ['id' => $job_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            // حتی در صورت خطا هم جاب بعدی را پردازش کن
            $this->schedule_next_processing();
            return false;
        }
    }

    /**
     * زمان‌بندی پردازش جاب بعدی
     */
    private function schedule_next_processing() {
        // برنامه‌ریزی برای اجرای پردازش در 10 ثانیه آینده
        wp_schedule_single_event(time() + 10, 'ai_process_job_queue');
        spawn_cron();
        error_log('⏱️ [SEQUENTIAL] Scheduled next processing in 10 seconds');
    }

    /**
     * پردازش بلافاصله پس از ثبت جاب جدید
     */
    public function maybe_process_immediately($job_id) {
        // اگر هیچ جاب در حال پردازشی وجود ندارد، بلافاصله شروع کن
        global $wpdb;
        
        $processing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'");
        
        if ($processing_count === '0') {
            error_log('⚡ [SEQUENTIAL] No jobs processing, triggering immediate processing');
            wp_schedule_single_event(time() + 2, 'ai_process_job_queue');
            spawn_cron();
        }
    }

    /**
     * پاکسازی جاب‌های گیر کرده
     */
    public function cleanup_stuck_jobs() {
        global $wpdb;

        $timeout_threshold = date('Y-m-d H:i:s', time() - $this->processing_timeout);

        $stuck_jobs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE status = 'processing' 
            AND started_at < %s
            ORDER BY started_at ASC
        ", $timeout_threshold));

        foreach ($stuck_jobs as $job) {
            if ($job->retry_count < 2) {
                $wpdb->update($this->table_name, [
                    'status' => 'pending',
                    'started_at' => null,
                    'retry_count' => $job->retry_count + 1,
                    'last_attempt' => current_time('mysql'),
                    'error_message' => 'Timeout - Retry ' . ($job->retry_count + 1),
                    'processing_log' => $job->processing_log . "\n[RETRY] Reset at " . current_time('mysql')
                ], ['id' => $job->id]);

                error_log('🔄 [SEQUENTIAL-CLEANUP] Job #' . $job->id . ' reset for retry');
            } else {
                $wpdb->update($this->table_name, [
                    'status' => 'error',
                    'error_message' => 'Job stuck after 2 retries',
                    'updated_at' => current_time('mysql'),
                    'processing_log' => $job->processing_log . "\n[ERROR] Marked as stuck at " . current_time('mysql')
                ], ['id' => $job->id]);

                error_log('❌ [SEQUENTIAL-CLEANUP] Job #' . $job->id . ' marked as error');
            }
        }
    }

    /**
     * اعتبارسنجی جاب
     */
    private function validate_job($job) {
        if (empty($job->prompt) || empty($job->service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        $user = get_user_by('ID', $job->user_id);
        if (!$user) {
            throw new Exception('کاربر یافت نشد');
        }

        $payment_handler = AI_Assistant_Payment_Handler::get_instance();
        $has_credit = $payment_handler->has_enough_credit($job->user_id, $job->final_price);

        if (is_wp_error($has_credit) || !$has_credit) {
            throw new Exception('موجودی حساب کافی نیست');
        }
    }

    /**
     * فراخوانی API - کپی از متد اصلی
     */
    private function call_deepseek_api($prompt) {
        $api_key = DEEPSEEK_API_KEY;
        $api_url = 'https://api.deepseek.com/v1/chat/completions';

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 8000
            ]),
            'timeout' => 180,
            'httpversion' => '1.1'
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            throw new Exception('خطا در ارتباط با سرور DeepSeek: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            throw new Exception('خطا از سمت DeepSeek API. کد وضعیت: ' . $response_code);
        }

        $decoded_body = json_decode($body, true);

        if (empty($decoded_body['choices'][0]['message']['content'])) {
            throw new Exception('پاسخ نامعتبر از API دریافت شد');
        }

        return $decoded_body['choices'][0]['message']['content'];
    }

    /**
     * پاکسازی پاسخ API - کپی از متد اصلی
     */
    private function clean_api_response($response_content) {
        $patterns = [
            '/^```json\s*/',
            '/\s*```$/',
            '/^```\s*/',
            '/\s*```$/',
        ];

        $cleaned_response = preg_replace($patterns, '', $response_content);
        $cleaned_response = trim($cleaned_response);
        $cleaned_response = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned_response);

        return $cleaned_response;
    }
}

// راه‌اندازی سیستم ترتیبی
AI_Job_Queue_Sequential::get_instance();