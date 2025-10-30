<?php
if (!defined('ABSPATH')) exit;

class AI_Job_Queue {

    private static $instance = null;
    private $table_name;
    private $max_concurrent_jobs = 3; // افزایش به 3 برای عملکرد بهتر
    private $processing_timeout = 600; // افزایش به 10 دقیقه برای API
    private $api_timeout = 300; // 5 دقیقه برای API
    private $logger;

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'job_queue';
        $this->logger = AI_Assistant_Logger::get_instance();

        add_action('init', [$this, 'maybe_create_table']);
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);

        if (!wp_next_scheduled('ai_process_job_queue')) {
            wp_schedule_event(time(), 'every_minute', 'ai_process_job_queue');
        }
        add_action('ai_process_job_queue', [$this, 'process_pending_jobs']);

        if (!wp_next_scheduled('ai_cleanup_stuck_jobs')) {
            wp_schedule_event(time(), 'every_5_minutes', 'ai_cleanup_stuck_jobs');
        }
        add_action('ai_cleanup_stuck_jobs', [$this, 'cleanup_stuck_jobs']);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ... (توابع maybe_create_table, add_cron_intervals, enqueue_job, cleanup_stuck_jobs مثل قبل) ...
    

    public function maybe_create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name));
        if ($table_exists === $this->table_name) return;


        // استفاده از file lock
        $lock_file = WP_CONTENT_DIR . '/ai_job_queue_table.lock';
        $lock_handle = fopen($lock_file, 'w');
        
        if (!flock($lock_handle, LOCK_EX | LOCK_NB)) {
            fclose($lock_handle);
            return;
        }

        try {
            
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$this->table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                service_id VARCHAR(50) NOT NULL,
                prompt LONGTEXT NOT NULL,
                final_price DECIMAL(10,2) DEFAULT 0,
                user_data LONGTEXT NULL,
                status ENUM('pending','processing','done','error') DEFAULT 'pending',
                error_message TEXT NULL,
                started_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                retry_count INT DEFAULT 0,
                last_attempt DATETIME NULL,
                processing_log LONGTEXT NULL,
                PRIMARY KEY (id),
                INDEX (status),
                INDEX (started_at),
                INDEX (service_id),
                INDEX (created_at)
            ) $charset_collate;";
    
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('🗃️ [AI_QUEUE] Job table created/verified');
        
            
        } finally {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
        }        
    }

    public function add_cron_intervals($schedules) {
        if (!isset($schedules['every_minute'])) {
            $schedules['every_minute'] = [
                'interval' => 60,
                'display'  => __('Every Minute')
            ];
        }
        if (!isset($schedules['every_3_minutes'])) {
            $schedules['every_3_minutes'] = [
                'interval' => 180,
                'display'  => __('Every 3 Minutes')
            ];
        }
        return $schedules;
    }

    public function enqueue_job($user_id, $service_id, $prompt, $final_price, $user_data = []) {
        global $wpdb;

        $inserted = $wpdb->insert($this->table_name, [
            'user_id'     => $user_id,
            'service_id'  => $service_id,
            'prompt'      => $prompt,
            'final_price' => $final_price,
            'user_data'   => maybe_serialize($user_data),
            'status'      => 'pending',
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql')
        ]);

        if ($inserted === false) {
            error_log('❌ [ENQUEUE] Failed to insert job: ' . $wpdb->last_error);
            return false;
        }

        $job_id = $wpdb->insert_id;
        error_log('✅ [ENQUEUE] Job #' . $job_id . ' added - Service: ' . $service_id);
        return $job_id;
    }

    /** 🔄 بازیابی jobهای گیر کرده با منطق بهبود یافته */
    public function cleanup_stuck_jobs() { 
        global $wpdb;
        
        $timeout_threshold = date('Y-m-d H:i:s', time() - $this->processing_timeout);
        
        error_log('🧹 [CLEANUP] Checking stuck jobs before: ' . $timeout_threshold);
        
        $stuck_jobs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE status = 'processing' 
            AND started_at < %s
            ORDER BY started_at ASC
        ", $timeout_threshold));
        
        error_log('🧹 [CLEANUP] Found ' . count($stuck_jobs) . ' stuck jobs');
        
        foreach ($stuck_jobs as $job) {
            // بررسی لاگ پردازش برای تشخیص وضعیت واقعی
            $has_recent_log = false;
            if (!empty($job->processing_log)) {
                $logs = explode("\n", $job->processing_log);
                $last_log = end($logs);
                // اگر در 2 دقیقه گذشته لاگ داشته، هنوز در حال اجراست
                if (strpos($last_log, date('Y-m-d H:i', time() - 120)) !== false) {
                    $has_recent_log = true;
                }
            }
            
            if ($job->retry_count < 2 && !$has_recent_log) {
                // فقط اگر لاگ جدیدی ندارد، ریست کن
                $wpdb->update($this->table_name, [
                    'status' => 'pending',
                    'started_at' => null,
                    'retry_count' => $job->retry_count + 1,
                    'last_attempt' => current_time('mysql'),
                    'error_message' => 'Stuck - Retry ' . ($job->retry_count + 1),
                    'processing_log' => $job->processing_log . "\n[RETRY] Reset at " . current_time('mysql')
                ], ['id' => $job->id]);
                error_log('🔄 [CLEANUP] Job #' . $job->id . ' reset for retry');
            } elseif (!$has_recent_log) {
                // خطای نهایی فقط اگر لاگ جدید ندارد
                $wpdb->update($this->table_name, [
                    'status' => 'error',
                    'error_message' => 'Job stuck after 2 retries - possible async failure',
                    'updated_at' => current_time('mysql'),
                    'processing_log' => $job->processing_log . "\n[ERROR] Marked as stuck at " . current_time('mysql')
                ], ['id' => $job->id]);
                error_log('❌ [CLEANUP] Job #' . $job->id . ' marked as error');
            } else {
                error_log('⚠️ [CLEANUP] Job #' . $job->id . ' has recent logs, skipping');
            }
        }
    }    

    /** ⚡ پردازش REAL موازی */
    public function process_pending_jobs() {
        global $wpdb;
        
        error_log('🔄 [CRON] Started at: ' . current_time('mysql'));
        
        $this->cleanup_stuck_jobs();
    
        $processing_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$this->table_name} 
            WHERE status = 'processing'
        ");

        error_log('📊 [CRON] Currently processing: ' . $processing_count . '/' . $this->max_concurrent_jobs);
        
        if ($processing_count >= $this->max_concurrent_jobs) {
            error_log('⏸️ [CRON] Max concurrent jobs reached, skipping');
            return;
        }

        $available_slots = $this->max_concurrent_jobs - $processing_count;
    
        $jobs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE status = 'pending' 
            ORDER BY id ASC 
            LIMIT %d
        ", $available_slots));
    
        error_log('📦 [CRON] Found ' . count($jobs) . ' jobs to process');
        
        if (empty($jobs)) {
            error_log('📭 [CRON] No pending jobs found');
            return;
        }
    
        // بررسی آیا pcntl_fork موجود است
        if (function_exists('pcntl_fork')) {
            error_log('🚀 [PARALLEL] Using parallel processing with pcntl_fork');
            $this->process_jobs_in_parallel($jobs);
        } else {
            error_log('⚠️ [SEQUENTIAL] Using sequential processing (pcntl not available)');
            $this->process_jobs_sequential($jobs);
        }
        
        error_log('✅ [CRON] Finished at: ' . current_time('mysql'));
    }

    /** 🔥 اجرای REAL موازی */
    private function process_jobs_in_parallel($jobs) {
        $child_processes = [];
        
        foreach ($jobs as $job) {
            $pid = pcntl_fork();
            
            if ($pid == -1) {
                error_log('❌ [PARALLEL] Failed to fork for job #' . $job->id);
                $this->process_single_job_direct($job->id);
            } elseif ($pid) {
                $child_processes[$pid] = $job->id;
                error_log('👶 [PARALLEL] Forked child process ' . $pid . ' for job #' . $job->id);
            } else {
                error_log('🚀 [CHILD] Starting parallel processing for job #' . $job->id);
                $this->process_single_job_in_child($job->id);
                exit(0);
            }
        }
        
        foreach ($child_processes as $pid => $job_id) {
            pcntl_waitpid($pid, $status);
            $exit_code = pcntl_wexitstatus($status);
            error_log('✅ [PARALLEL] Child process ' . $pid . ' for job #' . $job_id . ' finished');
        }
    }

/** 🎯 پردازش REAL API در child process */
/** 🎯 پردازش REAL API در child process - نسخه ایمن */
private function process_single_job_in_child($job_id) {
    $child_wpdb = null;
    
    try {
        // ایجاد اتصال جدید به دیتابیس
        $child_wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        
        error_log('🎯 [CHILD] Processing job #' . $job_id . ' in parallel');
        
        // قفل job
        $locked = $child_wpdb->update(
            $this->table_name,
            [
                'status' => 'processing', 
                'started_at' => current_time('mysql'),
                'last_attempt' => current_time('mysql'),
                'processing_log' => "\n[PARALLEL] Processing in child process at " . current_time('mysql')
            ],
            [
                'id' => $job_id, 
                'status' => 'pending'
            ]
        );

        if (!$locked) {
            error_log('❌ [CHILD] Failed to lock job #' . $job_id);
            return;
        }

        $job = $child_wpdb->get_row($child_wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $job_id));

        if (!$job) {
            error_log('❌ [CHILD] Job #' . $job_id . ' not found');
            return;
        }

        error_log('🎯 [CHILD] Starting API processing for job #' . $job->id);
        
        // افزایش محدودیت‌ها
        set_time_limit($this->api_timeout + 60);
        ini_set('max_execution_time', $this->api_timeout + 60);
        ini_set('memory_limit', '512M');
        
        // شروع تراکنش
        $child_wpdb->query('START TRANSACTION');

        // اعتبارسنجی
        $payment_handler = AI_Assistant_Payment_Handler::get_instance();
        $this->validate_request($job->prompt, $job->service_id, $job->user_id, $job->final_price, $payment_handler);
        
        // 🔥 فراخوانی API (تست)
        error_log('📡 [CHILD] Calling API for job #' . $job->id);
        $start_api = microtime(true);
        
        // تست با sleep
        sleep(10);
        $response = "Test response for job #" . $job->id;
        // $response = $this->call_deepseek_api($job->prompt);
        
        $api_time = round(microtime(true) - $start_api, 2);
        error_log('⏱️ [CHILD] API call completed in ' . $api_time . 's for job #' . $job->id);
        
        if (!$response) {
            throw new Exception('API call failed');
        }

        $cleaned = $this->clean_api_response($response);

        // کسر اعتبار
        $credit_success = $payment_handler->deduct_credit($job->user_id, $job->final_price, $job->service_id);
        if (!$credit_success) {
            throw new Exception('Credit deduction failed');
        }

        // ذخیره تاریخچه - با مدیریت خطا
        $history_manager = AI_Assistant_History_Manager::get_instance();
        $history_saved = $history_manager->save_history(
            $job->user_id,
            $job->service_id,
            $job->service_id, // service_name
            maybe_unserialize($job->user_data),
            $cleaned
        );
        
        if (!$history_saved) {
            error_log('⚠️ [CHILD] History saving failed for job #' . $job->id . ', but continuing...');
            // تاریخچه ضروری نیست، ادامه بده
        }

        // commit تراکنش
        $child_wpdb->query('COMMIT');

        // به روزرسانی وضعیت job
        $update_success = $child_wpdb->update($this->table_name, [
            'status' => 'done',
            'updated_at' => current_time('mysql'),
            'processing_log' => $job->processing_log . "\n[SUCCESS] Parallel processing completed in " . $api_time . "s at " . current_time('mysql')
        ], ['id' => $job->id]);

        if ($update_success) {
            error_log('✅ [CHILD] Job #' . $job->id . ' completed successfully in ' . $api_time . 's');
        } else {
            error_log('❌ [CHILD] Failed to update job status for job #' . $job->id);
        }

    } catch (Exception $e) {
        // rollback در صورت خطا
        if ($child_wpdb) {
            try { 
                $child_wpdb->query('ROLLBACK'); 
            } catch (Exception $ex) {
                error_log('⚠️ [CHILD] Rollback failed: ' . $ex->getMessage());
            }
        }

        $error_message = $e->getMessage();
        error_log('❌ [CHILD] Job #' . $job_id . ' failed: ' . $error_message);
        
        // به روزرسانی وضعیت به error
        if ($child_wpdb) {
            $child_wpdb->update($this->table_name, [
                'status' => 'error',
                'error_message' => substr($error_message, 0, 500),
                'updated_at' => current_time('mysql'),
                'processing_log' => (isset($job->processing_log) ? $job->processing_log : '') . "\n[ERROR] " . $error_message . " at " . current_time('mysql')
            ], ['id' => $job_id]);
        }
        
    } finally {
        // تمیز کردن منابع
        if ($child_wpdb) {
            $child_wpdb->close();
        }
    }
    
    // خروج explicit
    exit(0);
}



    /** 📡 فراخوانی REAL API */
    private function call_deepseek_api($prompt) {
        $prompt = 'خیلی خلاصه و در یک خط بگو که برای گرفتن یک رژیم غذایی چه نکته طلایی باید رعایت کنم';
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
            'timeout' => $this->api_timeout,
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

    private function validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler) {
        if (empty($prompt) || empty($service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        if (!$payment_handler->has_enough_credit($user_id, $final_price)) {
            throw new Exception('موجودی حساب شما کافی نیست');
        } 
    }

}

// ثبت hookهای AJAX
add_action('wp_ajax_ai_process_single_job', function() {
    AI_Job_Queue::get_instance()->process_single_job(intval($_GET['id']));
});

add_action('wp_ajax_nopriv_ai_process_single_job', function() {
    AI_Job_Queue::get_instance()->process_single_job(intval($_GET['id']));
});

AI_Job_Queue::get_instance();