<?php
/**
 * Process Requests Job - Refactored and Improved
 * Version: 2.0
 * 
 * Improvements:
 * - Better error handling and transaction management
 * - Fixed race conditions
 * - Improved logging and debugging
 * - Better validation and security
 * - Cleaned up logic flow
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Assistant_Process_Requests_Job {
    
    private static $instance = null;
    private $table_name;
    private $lock_option_key = 'ai_process_requests_lock';
    private $current_job_option_key = 'ai_current_processing_job';
    private $processing_timeout = 300; // 5 minutes
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'job_queue';
        
        
        $upload_dir = wp_upload_dir();
        $this->generation_log_file = $upload_dir['basedir'] . '/ai-article-generation.log';

        // Register actions
     //   add_action('ai_process_job_queue', [$this, 'run']);
        
        // Create table if needed
        $this->maybe_create_table();
    }
    
    /**
     * Main entry point - called by cron every minute
     */
    public function run() {
        // Check if processing is already in progress
        $lock = get_transient($this->lock_option_key);
        
        if ($lock !== false) {
            //error_log('⏸️ [PROCESS_REQUESTS] Processing already in progress, skipping');
            return;
        }
        
        // Acquire lock using transient (better than options for shared hosting)
        set_transient($this->lock_option_key, time(), 300); // 5 minute lock
        
        try {
            $this->process_jobs_sequentially();
        } catch (Exception $e) {
            error_log('❌ [PROCESS_REQUESTS] Fatal error: ' . $e->getMessage());
        } finally {
            // Release lock
            delete_transient($this->lock_option_key);
        }
    }
    
    /**
     * Create database table if not exists
     */
    public function maybe_create_table() {
        global $wpdb;
        
        // Check if table already exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") == $this->table_name) {
            return;
        }
        
        // Use file lock to prevent race condition during table creation
        $lock_file = WP_CONTENT_DIR . '/ai_job_queue_table.lock';
        $lock_handle = @fopen($lock_file, 'w');
        
        if (!$lock_handle || !flock($lock_handle, LOCK_EX | LOCK_NB)) {
            if ($lock_handle) {
                fclose($lock_handle);
            }
            return;
        }
        
        try {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                history_id BIGINT UNSIGNED NOT NULL,
                status ENUM('pending','processing','done','error') DEFAULT 'pending',
                error_message TEXT NULL,
                started_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                retry_count INT DEFAULT 0,
                last_attempt DATETIME NULL,
                processing_log LONGTEXT NULL,
                priority INT DEFAULT 0,
                PRIMARY KEY (id),
                INDEX idx_status (status),
                INDEX idx_started_at (started_at),
                INDEX idx_created_at (created_at),
                INDEX idx_priority (priority),
                INDEX idx_history (history_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            //error_log('✅ [JOB_QUEUE] Table created/verified successfully');
            
        } finally {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
        }
    }
    
    /**
     * Enqueue a new job
     */
    public function enqueue_job($history_id, $user_id) {
        global $wpdb;
        
        // Validate inputs
        if (empty($history_id) || empty($user_id)) {
            error_log('❌ [ENQUEUE] Invalid parameters: history_id or user_id is empty');
            return false;
        }
        
        // Check for duplicate jobs
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE history_id = %d AND status IN ('pending', 'processing')",
            $history_id
        ));
        
        if ($existing) {
            //error_log('⚠️ [ENQUEUE] Job already exists for history_id: ' . $history_id);
            return $existing;
        }
        
        $inserted = $wpdb->insert(
            $this->table_name,
            [
                'history_id' => $history_id,
                'user_id' => $user_id,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
        
        if ($inserted === false) {
            error_log('❌ [ENQUEUE] Failed to insert job: ' . $wpdb->last_error);
            return false;
        }
        
        $job_id = $wpdb->insert_id;
        //error_log('✅ [ENQUEUE] Job #' . $job_id . ' added for history_id: ' . $history_id);
        
        return $job_id;
    }
    
    /**
     * Process jobs sequentially (one at a time)
     */
    public function process_jobs_sequentially() {
        global $wpdb;
        
        //error_log('🔄 [SEQUENTIAL] Processing cycle started at: ' . current_time('mysql'));
        
        // First, cleanup any stuck jobs
        $this->cleanup_stuck_jobs();
        
        // Check if there's already a job being processed
        $current_job_id = get_transient($this->current_job_option_key);
        
        if ($current_job_id) {
            $current_job = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $current_job_id
            ));
            
            if ($current_job && $current_job->status === 'processing') {
                //error_log('⏸️ [SEQUENTIAL] Job #' . $current_job_id . ' is still processing, waiting...');
                return;
            } else {
                // Current job is done or failed, clear it
                delete_transient($this->current_job_option_key);
            }
        }
        
        // Get the oldest pending job
        $pending_job = $wpdb->get_row(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'pending' 
             ORDER BY priority DESC, id ASC 
             LIMIT 1"
        );
        
        if (!$pending_job) {
            //error_log('📭 [SEQUENTIAL] No pending jobs found');
            return;
        }
        
        //error_log('🎯 [SEQUENTIAL] Starting processing for job #' . $pending_job->id);
        
        // Claim the job (atomic operation using WHERE clause)
        $now = current_time('mysql');
        $log_entry = "\n[" . $now . "] Claimed by processor";
        
        $updated = $wpdb->update(
            $this->table_name,
            [
                'status' => 'processing',
                'started_at' => $now,
                'last_attempt' => $now,
                'processing_log' => $wpdb->prepare('%s', $pending_job->processing_log . $log_entry)
            ],
            [
                'id' => $pending_job->id,
                'status' => 'pending' // Only update if still pending
            ],
            ['%s', '%s', '%s', '%s'],
            ['%d', '%s']
        );
        
        if (!$updated || $wpdb->rows_affected === 0) {
            error_log('❌ [SEQUENTIAL] Failed to claim job #' . $pending_job->id . ' (already claimed by another process)');
            return;
        }
        
        // Set as current job
        set_transient($this->current_job_option_key, $pending_job->id, 300);
        
        // Process the job
        $this->process_single_job($pending_job->id);
    }
    
    /**
     * Cleanup stuck jobs
     */
    public function cleanup_stuck_jobs() {
        global $wpdb;
        
        $timeout_threshold = date('Y-m-d H:i:s', time() - $this->processing_timeout);
        
        $stuck_jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'processing' 
             AND started_at < %s 
             ORDER BY started_at ASC",
            $timeout_threshold
        ));
        
        if (empty($stuck_jobs)) {
            return;
        }
        
        foreach ($stuck_jobs as $job) {
            $log_entry = "\n[" . current_time('mysql') . "] Cleanup: timeout detected";
            
            if ($job->retry_count < 2) {
                // Reset for retry
                $wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'pending',
                        'started_at' => null,
                        'retry_count' => $job->retry_count + 1,
                        'last_attempt' => current_time('mysql'),
                        'error_message' => 'Timeout - Retry ' . ($job->retry_count + 1),
                        'processing_log' => $job->processing_log . $log_entry
                    ],
                    ['id' => $job->id],
                    ['%s', '%s', '%d', '%s', '%s', '%s'],
                    ['%d']
                );
                
                //error_log('🔄 [CLEANUP] Job #' . $job->id . ' reset for retry (' . ($job->retry_count + 1) . '/2)');
            } else {
                // Mark as error after 2 retries
                $wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'error',
                        'error_message' => 'Job stuck after 2 retries',
                        'updated_at' => current_time('mysql'),
                        'processing_log' => $job->processing_log . $log_entry . " - Max retries exceeded"
                    ],
                    ['id' => $job->id],
                    ['%s', '%s', '%s', '%s'],
                    ['%d']
                );
                
                error_log('❌ [CLEANUP] Job #' . $job->id . ' marked as error (max retries exceeded)');
            }
        }
        
        // Clear current job if it's stuck
        $current_job_id = get_transient($this->current_job_option_key);
        if ($current_job_id) {
            $current_job = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $current_job_id
            ));
            
            if ($current_job && 
                $current_job->status === 'processing' && 
                strtotime($current_job->started_at) < time() - $this->processing_timeout) {
                delete_transient($this->current_job_option_key);
                //error_log('🔄 [CLEANUP] Cleared stuck current job #' . $current_job_id);
            }
        }
    }
    
    private function recordTermsAcceptanceInTransaction($userid, $service_id = 'diet', $history_id = null) {
        try {
            //error_log("🔍 [DEBUG] recordTermsAcceptanceInTransaction called");
            //error_log("  - userid: $userid");
            //error_log("  - service_id: $service_id");
            //error_log("  - history_id: $history_id");
            
            $terms_db = Terms_Acceptance_DB::get_instance();
            $terms_content = $this->getFullTermsContent();
            
            if ( empty( $terms_content ) ) {
                throw new Exception( 'Terms content empty' );
            }
            
            //error_log("🔍 [DEBUG] Before saveAcceptanceInTransaction - history_id: $history_id");
            
            $acceptance_id = $terms_db->saveAcceptanceInTransaction( 
                $userid, 
                $terms_content, 
                $service_id, 
                $history_id
            );
            
            //error_log("🔍 [DEBUG] After saveAcceptanceInTransaction - acceptance_id: $acceptance_id");
            
            if ( ! $acceptance_id ) {
                throw new Exception( 'Failed to record terms acceptance' );
            }
            
            return $acceptance_id;
        } catch ( Exception $e ) {
            error_log( "ERROR recording terms: " . $e->getMessage() );
            throw $e;
        }
    }
    
    
    /**
     * دریافت متن کامل شرایط از منبع مرکزی
     * 
     * @return string
     * @throws Exception
     */
    private function getFullTermsContent() {
        //error_log('WORKER: Getting terms from central source');
        
        // ✅ استفاده از تابع مرکزی
        $terms_content = aidastyar_get_terms_with_title();
        
        if (empty($terms_content)) {
            //error_log('Terms content is EMPTY');
            throw new Exception('Terms content is empty');
        }
        
        //error_log('Content length: ' . strlen($terms_content));
        return $terms_content;
    }

    
    /**
     * Process a single job
     */
    private function process_single_job($job_id) {
        global $wpdb;
        
        //error_log('🎯 [WORKER] Starting processing for job #' . $job_id);
        
        // Load job
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $job_id
        ));
        
        if (!$job) {
            //error_log('❌ [WORKER] Job #' . $job_id . ' not found');
            delete_transient($this->current_job_option_key);
            return false;
        }
        
        // Initialize variables
        $history_id = $job->history_id;
        $user_id = $job->user_id;
        $history_manager = null;
        $start_time = microtime(true);
        
        try {
            // Environment setup
            @set_time_limit(300);
            @ini_set('max_execution_time', '300');
            @ini_set('memory_limit', '256M');
            
            // Get history manager
            $history_manager = AI_Assistant_History_Manager::get_instance();
            $history = $history_manager->get_history_item($history_id);
            
            if (!$history) {
                throw new Exception('History item not found: ' . $history_id);
            }
            
            // Update history to processing
            //error_log('📝 [WORKER] Updating history to processing for job #' . $job_id);
            $update_result = $history_manager->update_history($history_id, 'processing');
            
            if (!$update_result) {
                throw new Exception('Failed to update history status to processing');
            }
            
            // Extract data from history
            $service_id = $history->service_id;
            $userData = $history->user_data;
            $decodedData = json_decode($userData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in user_data: ' . json_last_error_msg());
            }
            
            $userInfo = $decodedData['userInfo'] ?? [];
            $serviceSelection = $decodedData['serviceSelection'] ?? [];
            $discountDetails = $decodedData['discountDetails'] ?? [];
            $discountInfo = $decodedData['discountInfo'] ?? [];
            
            // Get service information
            $all_services = get_option('ai_assistant_services', []);
            if (!isset($all_services[$service_id])) {
                throw new Exception('Service not found: ' . $service_id);
            }
            
            $service_name = $all_services[$service_id]['name'];
            $service_manager = AI_Assistant_Service_Manager::get_instance();
            $service_info = $service_manager->get_service($service_id);
            
            if (!$service_info || !isset($service_info['system_prompt'])) {
                throw new Exception('Service information or system_prompt not found');
            }
            
            $system_prompt = $service_info['system_prompt'];
            $original_price = $service_manager->get_service_price($service_id);
            
            // Calculate final price with discount
            list($final_price, $discount_applied, $discount_code, $discount_data) = 
                $this->calculate_final_price($original_price, $discountDetails, $discountInfo, $service_id, $user_id);
            
            //error_log('💰 [WORKER] Price calculation - Original: ' . $original_price . ', Final: ' . $final_price . ', Discount: ' . ($discount_applied ? 'YES' : 'NO'));
            
            // Prepare prompt
            $userInfoString = is_array($userInfo) ? json_encode($userInfo, JSON_UNESCAPED_UNICODE) : $userInfo;
            $prompt = $system_prompt . "\n\n" . $userInfoString;
            
            // Validate request
            $payment_handler = AI_Assistant_Payment_Handler::get_instance();
            $this->validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler);
            
            // Call API
            //error_log('📡 [WORKER] Calling API for job #' . $job_id);
            
            // ✅ بر اساس OTP_ENV تصمیم بگیر
            if (defined('OTP_ENV') && OTP_ENV === 'production') {
                // ✅ PRODUCTION: استفاده از API واقعی DeepSeek
                //error_log('🔴 [PRODUCTION] Calling REAL DeepSeek API for job #' . $job_id);
                $response = $this->call_deepseek_api($prompt);
            } else {
                // ✅ SANDBOX/BYPASS: استفاده از داده‌های نمونه
                //error_log('🟢 [SANDBOX] Using MOCK DATA for job #' . $job_id . ' (OTP_ENV: ' . (defined('OTP_ENV') ? OTP_ENV : 'undefined') . ')');
                
              
                
                $response = '
                {
                    "title": "برنامه تغذیهای بالینی",
                    "sections": [
                        {
                            "title": "۷. توصیهها",
                            "content": {
                                "type": "list",
                                "items": [
                                    "مصرف 3.5 لیتر آب در روز را ادامه دهید",
                                    "وعدههای غذایی را منظم و در زمانهای مشخص مصرف کنید",
                                    "پروتئین کافی در هر وعده دریافت کنید",
                                    "میوه و سبزیجات متنوع مصرف کنید",
                                    "خواب کافی (7-8 ساعت) داشته باشید"
                                ]
                            }
                        }
                    ]
                }';
            }
            
            if (!$response || (is_array($response) && isset($response['error']))) {
                $err = is_array($response) && isset($response['error']) ? $response['error'] : 'Empty or invalid API response';
                throw new Exception('API call failed: ' . $err);
            }
            
            $cleaned_response = $this->clean_api_response($response);
            
            // Start database transaction
            $wpdb->query('START TRANSACTION');
            $transaction_started = true;
            
            try {
                // STEP 1: Record Terms Acceptance FIRST
                $this->recordTermsAcceptanceInTransaction( $user_id, $service_id, $job->history_id );
                
                // Deduct credit
                //error_log('💰 [WORKER] Deducting credit for job #' . $job_id);
                $credit_success = $payment_handler->deduct_credit(
                    $user_id,
                    $final_price,
                    'استفاده از سرویس: ' . $service_name,
                    'job_' . $job_id
                );
                
                if ($credit_success === false || (is_array($credit_success) && isset($credit_success['error']))) {
                    $err = is_array($credit_success) && isset($credit_success['error']) ? $credit_success['error'] : 'Deduct credit failed';
                    throw new Exception('Payment deduction failed: ' . $err);
                }
                
                // Update history with response
                //error_log('📝 [WORKER] Updating history with response for job #' . $job_id);
                $update_result = $history_manager->update_history(
                    $history_id,
                    'completed',
                    $cleaned_response
                );
                
                if (!$update_result) {
                    throw new Exception('Failed to update history with response');
                }
                
                // Update history with response
                //error_log("📝 [WORKER] Updating history with response for job #$job_id");
                $update_result = $history_manager->update_history($history_id, 'completed', $cleaned_response);
                if (!$update_result) {
                    throw new Exception("Failed to update history with response");
                }
                
                // ✅ پردازش referral - داخل Transaction فعلی
                //error_log("🎯 [REFERRAL] Checking if this is first purchase for user: $user_id");
                
                // بررسی اینکه آیا این اولین خرید است
                $previous_jobs = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} 
                     WHERE user_id = %d AND status = 'done' AND id < %d",
                    $user_id, 
                    $job_id
                ));
                
                //error_log("🎯 [REFERRAL] Previous completed jobs: $previous_jobs");
                
                if ($previous_jobs == 0) {
                    //error_log("🎯 [REFERRAL] This is the FIRST purchase! Triggering referral reward...");
                    
                    // فراخوانی هوک - همه Query ها داخل Transaction فعلی هستند
                    do_action('ai_assistant_first_purchase_completed', $user_id, $history_id, $final_price);
                    
                    //error_log("🎯 [REFERRAL] Referral processing completed");
                } else {
                    //error_log("🎯 [REFERRAL] Not first purchase (count: $previous_jobs), skipping referral");
                }

                
                // Increment discount usage if applicable
                if ($discount_applied && isset($discount_data['discount']) && $discount_data['discount']->scope === 'coupon') {
                    $discount_db = AI_Assistant_Discount_DB::get_instance();
                    $discount_db->increment_usage($discount_data['discount']->id);
                    //error_log('✅ [DISCOUNT] Usage incremented for code: ' . $discount_code);
                }
                
                                
                $dietType = $serviceSelection['dietType'] ?? null;
                
                if ($dietType !== 'with-specialist') {
                    // ✅ ارسال ایمیل با شامل کردن Terms Acceptance
                    $notification_manager = AI_Assistant_Notification_Manager::get_instance();
                    
                    // دریافت محتوای رژیم (اختیاری)
                    $diet_content = $cleaned_response ?? '';
                    
                    // ارسال ایمیل با terms
                    $notification_manager->send_result_ready_with_terms(
                        $user_id, 
                        $history_id, 
                        $diet_content
                    );
                    
                    //error_log('✅ [EMAIL] Result ready email sent with terms acceptance for user: ' . $user_id);
                }
                elseif($dietType === 'with-specialist') {
                    //error_log('$dietType......dont send email...............------' . $dietType );
                    // Handle diet consultation if needed
                    $consultation_handled = $this->handle_diet_consultation(
                        $service_id,
                        $serviceSelection,
                        $history_id,
                        $history_manager
                    );
                    
                }
                
                // Commit transaction
                $wpdb->query('COMMIT');
                $transaction_started = false;
                //error_log('✅ [TRANSACTION] Committed for job #' . $job_id);
                
                // Calculate processing time
                $api_time = round(microtime(true) - $start_time, 2);
                $log_entry = "\n[" . current_time('mysql') . "] Completed successfully in " . $api_time . "s";
                
                // Mark job as done
                $update_success = $wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'done',
                        'updated_at' => current_time('mysql'),
                        'processing_log' => $job->processing_log . $log_entry
                    ],
                    ['id' => $job_id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
                
                if (!$update_success) {
                    //error_log('⚠️ [WORKER] Failed to update job status to done, but job completed successfully');
                }
                
                //error_log('✅ [WORKER] Job #' . $job_id . ' completed successfully in ' . $api_time . 's');
                
                // Clear current job
                delete_transient($this->current_job_option_key);
                
                return true;
                
            } catch (Exception $inner_e) {
                // Rollback transaction if it was started
                if (isset($transaction_started) && $transaction_started) {
                    $wpdb->query('ROLLBACK');
                    //error_log('🔄 [TRANSACTION] Rolled back for job #' . $job_id);
                }
                throw $inner_e; // Re-throw to outer catch
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            $error_trace = $e->getTraceAsString();
            error_log('❌ [WORKER] Job #' . $job_id . ' failed: ' . $error_message);
            error_log('Stack trace: ' . $error_trace);
            
            // Rollback if transaction is still active
            if (isset($transaction_started) && $transaction_started) {
                try {
                    $wpdb->query('ROLLBACK');
                    //error_log('🔄 [TRANSACTION] Rolled back for job #' . $job_id);
                } catch (Exception $rollback_e) {
                    error_log('❌ [ROLLBACK] Failed: ' . $rollback_e->getMessage());
                }
            }
            
            $log_entry = "\n[" . current_time('mysql') . "] Error: " . $error_message;
            
            // Update job status to error
            $wpdb->update(
                $this->table_name,
                [
                    'status' => 'error',
                    'error_message' => substr($error_message, 0, 500),
                    'updated_at' => current_time('mysql'),
                    'processing_log' => $job->processing_log . $log_entry
                ],
                ['id' => $job_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );
            
            // Update history status to error
            if ($history_manager && isset($history_id)) {
                try {
                    $history_manager->update_history($history_id, 'error');
                    //error_log('📝 [WORKER] History updated to error status');
                } catch (Exception $history_e) {
                    error_log('❌ [WORKER] Failed to update history: ' . $history_e->getMessage());
                }
            }
            
            // Clear current job
            delete_transient($this->current_job_option_key);
            
            return false;
        }
    }
    
    /**
     * Calculate final price with discount
     */
    private function calculate_final_price($original_price, $discountDetails, $discountInfo, $service_id, $user_id) {
        $final_price = $original_price;
        $discount_applied = false;
        $discount_code = null;
        $discount_data = [];
        
        try {
            // First check discountDetails (from frontend calculation)
            if (!empty($discountDetails) && isset($discountDetails['finalPrice'])) {
                $final_price = floatval($discountDetails['finalPrice']);
                $original_price = floatval($discountDetails['originalPrice'] ?? $final_price);
                $discount_applied = ($final_price < $original_price);
                
                //error_log('✅ [DISCOUNT] Using discountDetails - Final: ' . $final_price . ', Discount: ' . ($discount_applied ? 'YES' : 'NO'));
            }
            // Fallback to discountInfo validation
            else if (!empty($discountInfo)) {
                $discount_code = $discountInfo['discountCode'] ?? null;
                $discount_applied_flag = $discountInfo['discountApplied'] ?? false;
                
                if (!empty($discount_code) && $discount_applied_flag) {
                    // Validate discount code
                    $validation_result = AI_Assistant_Discount_Manager::validate_discount(
                        $discount_code,
                        $service_id,
                        $user_id
                    );
                    
                    if ($validation_result['valid']) {
                        $discounted_price = AI_Assistant_Discount_Manager::calculate_discounted_price(
                            $original_price,
                            $validation_result['discount']
                        );
                        
                        $final_price = $discounted_price;
                        $discount_applied = true;
                        $discount_data = $validation_result;
                        
                        //error_log('✅ [DISCOUNT] Code validated - ' . $discount_code . ', Final: ' . $final_price);
                    } else {
                        //error_log('⚠️ [DISCOUNT] Invalid code: ' . $discount_code . ' - ' . $validation_result['message']);
                    }
                }
            }
            
            if (!$discount_applied) {
                //error_log('ℹ️ [DISCOUNT] No discount applied, using original price: ' . $original_price);
            }
            
        } catch (Exception $e) {
            error_log('❌ [DISCOUNT] Error processing discount: ' . $e->getMessage());
            $final_price = $original_price;
            $discount_applied = false;
        }
        
        return [$final_price, $discount_applied, $discount_code, $discount_data];
    }
    
    /**
     * Handle diet consultation request
     */
    private function handle_diet_consultation($service_id, $serviceSelection, $history_id, $history_manager) {
        if ($service_id !== 'diet') {
            return false;
        }
        
        $dietType = $serviceSelection['dietType'] ?? null;
        
        if ($dietType !== 'with-specialist') {
            return false;
        }
        
        //error_log('📝 [CONSULTATION] Processing diet consultation request');
        
        $selectedSpecialist = $serviceSelection['selectedSpecialist'] ?? null;
        
        if (!is_array($selectedSpecialist) || empty($selectedSpecialist['id'])) {
            throw new Exception('Selected specialist data is invalid or missing');
        }
        
        $specialist_id = $selectedSpecialist['id'];
        $specialist_name = $selectedSpecialist['name'] ?? 'Unknown';
        
        //error_log('📝 [CONSULTATION] Specialist: ' . $specialist_name . ' (ID: ' . $specialist_id . ')');
        
        // Get consultant and contract
        $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
        $consultant = $consultation_db->get_consultant($specialist_id);
        
        if (!$consultant) {
            throw new Exception('Consultant not found: ' . $specialist_id);
        }
        
        $contract = $consultation_db->get_active_contract($consultant->id);
        
        if (empty($contract)) {
            throw new Exception('No active contract found for consultant: ' . $specialist_id);
        }
        
        // Submit consultation request
        $nutrition_manager = AI_Assistant_Nutrition_Consultant_Manager::get_instance();
        $consultation_result = $nutrition_manager->submit_consultation_request(
            $history_id,
            $consultant->id,
            $contract->commission_value
        );
        
        if ($consultation_result === false || (is_array($consultation_result) && isset($consultation_result['error']))) {
            $err = is_array($consultation_result) && isset($consultation_result['error']) ? 
                   $consultation_result['error'] : 'submit_consultation_request failed';
            throw new Exception('Consultation request failed: ' . $err);
        }
        
        // Update history status to consultant_queue
        $history_update = $history_manager->update_history($history_id, 'consultant_queue');
        
        if (!$history_update) {
            throw new Exception('Failed to update history to consultant_queue status');
        }
        
        //error_log('✅ [CONSULTATION] Request submitted successfully for history #' . $history_id);
        
        return true;
    }
    
    /**
     * Call DeepSeek API
     */
    private function call_deepseek_api($prompt) {
        
        
     //   $prompt= 'یه جمله ساده بگو';
        if (!defined('DEEPSEEK_API_KEY') || empty(DEEPSEEK_API_KEY)) {
            throw new Exception('DEEPSEEK_API_KEY is not defined');
        }
        
        // شمارنده API برای Process Requests
        $call_number = AI_Job_Queue::increment_api_call('process_requests');
        error_log("🌐 API call #{$call_number} for Process Requests");
    
        $api_key = DEEPSEEK_API_KEY;
        $api_url = 'https://api.deepseek.com/v1/chat/completions';
        
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'deepseek-coder',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 8000
            ]),
            'timeout' => 300,
            'httpversion' => '1.1',
            'sslverify' => true
        ];
        
        $response = wp_remote_post($api_url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('خطا در ارتباط با سرور DeepSeek: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            //error_log('DeepSeek API Error - Code: ' . $response_code . ', Body: ' . substr($body, 0, 500));
            throw new Exception('خطا از سمت DeepSeek API. کد وضعیت: ' . $response_code);
        }
        
        $decoded_body = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('پاسخ API قابل decode نیست: ' . json_last_error_msg());
        }
        
        if (empty($decoded_body['choices'][0]['message']['content'])) {
            throw new Exception('پاسخ نامعتبر از API دریافت شد');
        }
        
        return $decoded_body['choices'][0]['message']['content'];
    }
    
    /**
     * Clean API response
     */
    private function clean_api_response($response_content) {
        if (empty($response_content)) {
            return '';
        }
        
        // Remove markdown code blocks
        $patterns = [
            '/^```json\s*/',
            '/\s*```$/',
            '/^```\s*/',
            '/\s*```$/',
        ];
        
        $cleaned_response = preg_replace($patterns, '', $response_content);
        $cleaned_response = trim($cleaned_response);
        
        // Remove control characters
        $cleaned_response = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned_response);
        
        return $cleaned_response;
    }
    
    /**
     * Validate request before processing
     */
    private function validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler) {
        // Validate user
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            throw new Exception('کاربر یافت نشد');
        }
        
        // Validate inputs
        if (empty($prompt)) {
            throw new Exception('پرامپت خالی است');
        }
        
        if (empty($service_id)) {
            throw new Exception('شناسه سرویس خالی است');
        }
        
        // Check credit
        if (!$payment_handler->has_enough_credit($user_id, $final_price)) {
            throw new Exception('موجودی حساب شما کافی نیست. قیمت: ' . number_format($final_price) . ' تومان');
        }
        
        return true;
    }
    
    /**
     * Get queue statistics
     */
    public function get_queue_stats() {
        global $wpdb;
        
        return [
            'pending' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'"),
            'processing' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'"),
            'done' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'done'"),
            'error' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'error'"),
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"),
        ];
    }
    
    /**
     * Get job status
     */
    public function get_job_status($job_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $job_id
        ));
    }
    
    /**
     * Reset failed job
     */
    public function reset_failed_job($job_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'pending',
                'error_message' => null,
                'started_at' => null,
                'retry_count' => 0,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $job_id, 'status' => 'error'],
            ['%s', '%s', '%s', '%d', '%s'],
            ['%d', '%s']
        );
    }
    
    /**
     * Delete old completed jobs (cleanup)
     */
    public function cleanup_old_jobs($days = 30) {
        global $wpdb;
        
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE status = 'done' 
             AND updated_at < %s",
            $threshold
        ));
        
        if ($deleted) {
            //error_log('🧹 [CLEANUP] Deleted ' . $deleted . ' old completed jobs');
        }
        
        return $deleted;
    }
}

// Initialize
AI_Assistant_Process_Requests_Job::get_instance();