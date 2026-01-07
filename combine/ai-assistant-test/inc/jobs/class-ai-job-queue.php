<?php
/**
 * AI Job Queue Manager
 * مدیریت Job های زمان‌بندی شده برای هوش مصنوعی
 * 
 * نحوه استفاده:
 * 1. تنظیم Cron در cPanel: * * * * * curl -s "https://test.aidastyar.com/wp-cron.php?doing_wp_cron" > /dev/null
 * 2. فعال‌سازی در wp-config.php: define('DISABLE_WP_CRON', true);
 * 3. این فایل را در functions.php یا mu-plugins لود کنید
 * 
 * @version 2.0.0
 * @author Your Name
 */



if (!defined('ABSPATH')) {
    exit;
}


class AI_Job_Queue {
    
    /**
     * نسخه singleton
     */
    private static $instance = null;
    
    /**
     * برای جلوگیری از scheduling مکرر در یک request
     */
    private static $initialized = false;
    
    /**
     * نام hook ها - مهم: باید با add_action ها یکسان باشند
     */
    const HOOK_PROCESS_REQUESTS = 'ai_cron_process_requests';
    const HOOK_ARTICLE_GENERATOR = 'ai_cron_article_generator';
    
    /**
     * نام option برای ذخیره وضعیت
     */
    const OPTION_LAST_PROCESS = 'ai_job_last_process_run';
    const OPTION_LAST_ARTICLE = 'ai_job_last_article_run';
    
    
    /**
     * شمارنده API Calls
     */
    const OPTION_API_CALLS_STATS = 'ai_api_calls_stats_v2';
      
    
    /**
     * دریافت instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // فقط یکبار initialize شود
        if (self::$initialized) {
            return;
        }
        
        // error_log('🔄 [JOB_QUEUE] Initializing v2.0...');
        
        // اضافه کردن schedule های سفارشی
        add_filter('cron_schedules', [$this, 'add_custom_schedules']);
        
        // Hook کردن job ها
        add_action(self::HOOK_PROCESS_REQUESTS, [$this, 'execute_process_requests_job']);
        add_action(self::HOOK_ARTICLE_GENERATOR, [$this, 'execute_article_generator_job']);
        
        // Schedule کردن job ها در اولین بار
        add_action('init', [$this, 'maybe_schedule_jobs'], 5);
        
        // اجرای دستی از URL (برای تست)
        add_action('init', [$this, 'handle_manual_run'], 10);
        
        // // اضافه کردن دستور WP-CLI (اختیاری)
        // if (defined('WP_CLI') && WP_CLI) {
        //     WP_CLI::add_command('ai-jobs', [$this, 'cli_commands']);
        // }
        
        self::$initialized = true;
        // error_log('✅ [JOB_QUEUE] Initialized successfully');
    }
    
    /**
     * اضافه کردن schedule های سفارشی
     */
    public function add_custom_schedules($schedules) {
        // هر 1 دقیقه
        if (!isset($schedules['every_minute'])) {
            $schedules['every_minute'] = [
                'interval' => 60,
                'display'  => __('Every Minute')
            ];
        }
        
        // هر 5 دقیقه
        if (!isset($schedules['every_5_minute'])) {
            $schedules['every_5_minute'] = [
                'interval' => 300,
                'display'  => __('Every 5 Minute')
            ];
        }        
        
        // هر 24 ساعت
        if (!isset($schedules['every_24_hours'])) {
            $schedules['every_24_hours'] = [
                'interval' => 86400,
                'display'  => __('Every 24 Hours')
            ];
        }
        
        // هر 3 روز
        if (!isset($schedules['every_3_days'])) {
            $schedules['every_3_days'] = [
                'interval' => 259200,  // 3 روز × 24 ساعت × 3600 ثانیه = 259200 ثانیه
                'display'  => __('Every 3 Days')
            ];
        }
        
        
        return $schedules;
    }
    
    /**
     * Schedule کردن job ها (فقط در صورت نیاز)
     */
    public function maybe_schedule_jobs() {
        // بررسی و schedule کردن process_requests_job
        // ⏱️ هر 1 دقیقه یکبار
        if (!wp_next_scheduled(self::HOOK_PROCESS_REQUESTS)) {
            $scheduled = wp_schedule_event(time(), 'every_minute', self::HOOK_PROCESS_REQUESTS);
            if ($scheduled !== false) {
                //error_log('✅ [JOB_QUEUE] Scheduled ' . self::HOOK_PROCESS_REQUESTS . ' (every minute)');
            } else {
                error_log('❌ [JOB_QUEUE] Failed to schedule ' . self::HOOK_PROCESS_REQUESTS);
            }
        }
        
        // بررسی و schedule کردن article_generator_job
        // ⏱️ هر 3 روز یکبار (ساعت 2 بامداد)
        if (!wp_next_scheduled(self::HOOK_ARTICLE_GENERATOR)) {
            // محاسبه زمان: 3 روز بعد ساعت 2 بامداد
            $in_3_days_2am = strtotime('+3 days 2:00am');
            
            $scheduled = wp_schedule_event($in_3_days_2am, 'every_3_days', self::HOOK_ARTICLE_GENERATOR);
        

            if ($scheduled !== false) {
               
                error_log('✅ [JOB_QUEUE] Scheduled ' . self::HOOK_ARTICLE_GENERATOR . ' for ' . date('Y-m-d H:i:s', $start_time) . ' (every  3_days)');
            } else {
                error_log('❌ [JOB_QUEUE] Failed to schedule ' . self::HOOK_ARTICLE_GENERATOR);
            }
        }
    }

    
    /**
     * اجرای process_requests_job
     * هر 1 دقیقه یکبار
     */
    public function execute_process_requests_job() {
        $start_time = microtime(true);
        $current_time = current_time('mysql');
        
        //error_log('🎯 [PROCESS_JOB] Starting at ' . $current_time);
        
        // بررسی کلاس
        if (!class_exists('AI_Assistant_Process_Requests_Job')) {
            error_log('❌ [PROCESS_JOB] Class "AI_Assistant_Process_Requests_Job" not found');
            return;
        }
        
        // Lock برای جلوگیری از اجرای همزمان
        $lock_key = 'ai_process_job_lock';
        $lock = get_transient($lock_key);
        
        if ($lock) {
         //   error_log('⏸️ [PROCESS_JOB] Already running (locked), skipping...');
            return;
        }
        
        // Set lock برای 3 دقیقه
        set_transient($lock_key, true, 180);
        
        try {
            // اجرای job
            $job = AI_Assistant_Process_Requests_Job::get_instance();
            $job->run();
            
            // ذخیره زمان آخرین اجرا
            update_option(self::OPTION_LAST_PROCESS, time());
            
            $elapsed = round(microtime(true) - $start_time, 2);
            //error_log("✅ [PROCESS_JOB] Completed in {$elapsed}s");
            
        } catch (Exception $e) {
            error_log('❌ [PROCESS_JOB] Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
        } finally {
            // پاک کردن lock
            delete_transient($lock_key);
        }
    }
    
    /**
     * اجرای article_generator_job
     */
    public function execute_article_generator_job() {
        $start_time = microtime(true);
        $current_time = current_time('mysql');
        
        //error_log('🎯 [ARTICLE_JOB] Starting at ' . $current_time);
        
        // بررسی کلاس
        if (!class_exists('ai_article_generator_job')) {
            error_log('❌ [ARTICLE_JOB] Class "ai_article_generator_job" not found');
            return;
        }
        
         
        // Lock برای جلوگیری از اجرای همزمان
        $lock_key = 'ai_article_job_lock';
        $lock = get_transient($lock_key);
       
        if ($lock) {
            
        //    error_log('⏸️ [ARTICLE_JOB] Already running (locked), skipping...:' . $lock);
            return;
        }
        
        // Set lock برای 2 ساعت
        set_transient($lock_key, true, 7200);
        
        try {
            // اجرای job
            $job = new ai_article_generator_job();
            $job->handle();
            
            // ذخیره زمان آخرین اجرا
            update_option(self::OPTION_LAST_ARTICLE, time());
            
            $elapsed = round(microtime(true) - $start_time, 2);
            //error_log("✅ [ARTICLE_JOB] Completed in {$elapsed}s");
            
        } catch (Exception $e) {
            error_log('❌ [ARTICLE_JOB] Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
        } finally {
            // پاک کردن lock
            delete_transient($lock_key);
        }
    }
    
    /**
     * اجرای دستی از URL
     * استفاده: https://test.aidastyar.com/?run_ai_jobs=1&secret=YOUR_SECRET
     */
    public function handle_manual_run() {
        if (!isset($_GET['run_ai_jobs']) || $_GET['run_ai_jobs'] !== '1') {
            return;
        }
        
        // امنیت: باید secret key درست باشد
        $secret = defined('AI_JOBS_SECRET') ? AI_JOBS_SECRET : 'change_me_please';
        if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
            wp_die('❌ Invalid secret key', 'Unauthorized', ['response' => 401]);
        }
        
        //error_log('🔧 [JOB_QUEUE] Manual execution requested');
        
        // اجرای هر دو job
        $this->execute_process_requests_job();
        $this->execute_article_generator_job();
        
        wp_die('✅ Jobs executed manually at ' . current_time('mysql'));
    }
    
    /**
     * دریافت وضعیت job ها
     */
    public function get_status() {
        $process_next = wp_next_scheduled(self::HOOK_PROCESS_REQUESTS);
        $article_next = wp_next_scheduled(self::HOOK_ARTICLE_GENERATOR);
        
        $process_last = get_option(self::OPTION_LAST_PROCESS, 0);
        $article_last = get_option(self::OPTION_LAST_ARTICLE, 0);
        
        return [
            'process_requests' => [
                'next_run' => $process_next ? date('Y-m-d H:i:s', $process_next) : 'Not scheduled',
                'last_run' => $process_last ? date('Y-m-d H:i:s', $process_last) : 'Never',
                'interval' => 'Every minute'
            ],
            'article_generator' => [
                'next_run' => $article_next ? date('Y-m-d H:i:s', $article_next) : 'Not scheduled',
                'last_run' => $article_last ? date('Y-m-d H:i:s', $article_last) : 'Never',
                'interval' => 'Every 24 hours'
            ]
        ];
    }
    
    /**
     * حذف تمام job های schedule شده
     */
    public function clear_schedules() {
        $process_cleared = wp_clear_scheduled_hook(self::HOOK_PROCESS_REQUESTS);
        $article_cleared = wp_clear_scheduled_hook(self::HOOK_ARTICLE_GENERATOR);
        
        //error_log('🗑️ [JOB_QUEUE] Cleared schedules: process=' . ($process_cleared ? 'yes' : 'no') . ', article=' . ($article_cleared ? 'yes' : 'no'));
        
        return [
            'process_requests' => $process_cleared,
            'article_generator' => $article_cleared
        ];
    }
  //----------------------------------monitoring------------------------------------------------  
    public static function increment_api_call($job_type = 'unknown') {
        $today = date('Y-m-d');
        $stats = get_option(self::OPTION_API_CALLS_STATS, []);
        
        // مقداردهی اولیه برای امروز
        if (!isset($stats[$today])) {
            $stats[$today] = [
                'article_generator' => 0,
                'process_requests' => 0,
                'manual' => 0,
                'total' => 0
            ];
        }
        
        // افزایش شمارنده برای Job مشخص
        if (isset($stats[$today][$job_type])) {
            $stats[$today][$job_type]++;
        } else {
            $stats[$today]['manual']++;
        }
        
        // افزایش مجموع
        $stats[$today]['total']++;
        
        // ذخیره
        update_option(self::OPTION_API_CALLS_STATS, $stats);
        
        // لاگ
        $new_count = $stats[$today][$job_type] ?? $stats[$today]['manual'];
        error_log("📊 [API_COUNTER] {$job_type} - امروز: {$new_count} (مجموع: {$stats[$today]['total']})");
        
        return $new_count;
    }
    
    /**
     * دریافت آمار API Calls
     */
    public static function get_api_stats($date = null) {
        $stats = get_option(self::OPTION_API_CALLS_STATS, []);
        $today = $date ?: date('Y-m-d');
        
        // آمار امروز
        $today_stats = $stats[$today] ?? [
            'article_generator' => 0,
            'process_requests' => 0,
            'manual' => 0,
            'total' => 0
        ];
        
        // محاسبه مجموع هفته (۷ روز گذشته)
        $weekly_stats = [
            'article_generator' => 0,
            'process_requests' => 0,
            'manual' => 0,
            'total' => 0
        ];
        
        for ($i = 0; $i < 7; $i++) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            if (isset($stats[$day])) {
                foreach ($weekly_stats as $key => $value) {
                    if (isset($stats[$day][$key])) {
                        $weekly_stats[$key] += $stats[$day][$key];
                    }
                }
            }
        }
        
        // محاسبه مجموع ماه (۳۰ روز گذشته)
        $monthly_stats = [
            'article_generator' => 0,
            'process_requests' => 0,
            'manual' => 0,
            'total' => 0
        ];
        
        for ($i = 0; $i < 30; $i++) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            if (isset($stats[$day])) {
                foreach ($monthly_stats as $key => $value) {
                    if (isset($stats[$day][$key])) {
                        $monthly_stats[$key] += $stats[$day][$key];
                    }
                }
            }
        }
        
        // محاسبه مجموع کل
        $all_time_stats = [
            'article_generator' => 0,
            'process_requests' => 0,
            'manual' => 0,
            'total' => 0
        ];
        
        foreach ($stats as $day_stats) {
            foreach ($all_time_stats as $key => $value) {
                if (isset($day_stats[$key])) {
                    $all_time_stats[$key] += $day_stats[$key];
                }
            }
        }
        
        return [
            'today' => $today_stats,
            'this_week' => $weekly_stats,
            'this_month' => $monthly_stats,
            'all_time' => $all_time_stats,
            'raw_data' => $stats // برای دیباگ
        ];
    }
    
    /**
     * دریافت داده‌های نمودار ۷ روز گذشته
     */
    public static function get_chart_data($days = 7) {
        $stats = get_option(self::OPTION_API_CALLS_STATS, []);
        $chart_data = [
            'labels' => [],
            'datasets' => [
                'article_generator' => [],
                'process_requests' => [],
                'total' => []
            ]
        ];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $persian_date = self::gregorian_to_jalali($date);
            
            $chart_data['labels'][] = $persian_date;
            
            if (isset($stats[$date])) {
                $chart_data['datasets']['article_generator'][] = $stats[$date]['article_generator'] ?? 0;
                $chart_data['datasets']['process_requests'][] = $stats[$date]['process_requests'] ?? 0;
                $chart_data['datasets']['total'][] = $stats[$date]['total'] ?? 0;
            } else {
                $chart_data['datasets']['article_generator'][] = 0;
                $chart_data['datasets']['process_requests'][] = 0;
                $chart_data['datasets']['total'][] = 0;
            }
        }
        
        return $chart_data;
    }
    
    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    private static function gregorian_to_jalali($gregorian_date) {
        $date = new DateTime($gregorian_date);
        $year = (int)$date->format('Y');
        $month = (int)$date->format('m');
        $day = (int)$date->format('d');
        
        // تبدیل ساده (می‌توانید از کتابخانه کامل استفاده کنید)
        $jalali_months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        
        // تبدیل تقریبی
        $jalali_month_index = ($month + 2) % 12;
        $jalali_day = $day;
        
        return $jalali_day . ' ' . $jalali_months[$jalali_month_index];
    }
    
    /**
     * پاک کردن آمار
     */
    public static function reset_api_stats($date = null) {
        $stats = get_option(self::OPTION_API_CALLS_STATS, []);
        
        if ($date === 'all') {
            delete_option(self::OPTION_API_CALLS_STATS);
            error_log('🗑️ [API_COUNTER] All stats cleared');
            return true;
        } elseif ($date) {
            if (isset($stats[$date])) {
                unset($stats[$date]);
                update_option(self::OPTION_API_CALLS_STATS, $stats);
                error_log('🗑️ [API_COUNTER] Stats cleared for: ' . $date);
                return true;
            }
        } else {
            $today = date('Y-m-d');
            if (isset($stats[$today])) {
                $stats[$today] = [
                    'article_generator' => 0,
                    'process_requests' => 0,
                    'manual' => 0,
                    'total' => 0
                ];
                update_option(self::OPTION_API_CALLS_STATS, $stats);
                error_log('🗑️ [API_COUNTER] Today stats cleared');
                return true;
            }
        }
        
        return false;
    }
    /**
     * WP-CLI Commands (اختیاری)
     */
    // public function cli_commands($args, $assoc_args) {
    //     $command = isset($args[0]) ? $args[0] : 'status';
        
    //     switch ($command) {
    //         case 'status':
    //             $status = $this->get_status();
    //             WP_CLI::line('📊 Job Queue Status:');
    //             WP_CLI::line('');
    //             WP_CLI::line('Process Requests Job:');
    //             WP_CLI::line('  Next: ' . $status['process_requests']['next_run']);
    //             WP_CLI::line('  Last: ' . $status['process_requests']['last_run']);
    //             WP_CLI::line('');
    //             WP_CLI::line('Article Generator Job:');
    //             WP_CLI::line('  Next: ' . $status['article_generator']['next_run']);
    //             WP_CLI::line('  Last: ' . $status['article_generator']['last_run']);
    //             break;
                
    //         case 'run':
    //             $job = isset($args[1]) ? $args[1] : 'all';
    //             if ($job === 'all' || $job === 'process') {
    //                 WP_CLI::line('Running process_requests_job...');
    //                 $this->execute_process_requests_job();
    //             }
    //             if ($job === 'all' || $job === 'article') {
    //                 WP_CLI::line('Running article_generator_job...');
    //                 $this->execute_article_generator_job();
    //             }
    //             WP_CLI::success('Jobs executed');
    //             break;
                
    //         case 'clear':
    //             $this->clear_schedules();
    //             WP_CLI::success('Schedules cleared');
    //             break;
                
    //         case 'reschedule':
    //             $this->clear_schedules();
    //             $this->maybe_schedule_jobs();
    //             WP_CLI::success('Schedules reset');
    //             break;
                
    //         default:
    //             WP_CLI::error('Unknown command. Available: status, run, clear, reschedule');
    //     }
    // }
}


// اضافه کردن URL برای clear کردن schedules
add_action('init', function() {
    if (isset($_GET['clear_ai_schedules']) && $_GET['clear_ai_schedules'] === '1') {
        $cleared = AI_Job_Queue::get_instance()->clear_schedules();
        //error_log('🗑️ Cleared schedules manually');
        wp_die('✅ Schedules cleared! Reload the page to reschedule.');
    }
});


// اضافه کردن URL برای دیدن schedules
add_action('init', function() {
    if (isset($_GET['check_ai_schedules']) && $_GET['check_ai_schedules'] === '1') {
        $process_next = wp_next_scheduled('ai_cron_process_requests');
        $article_next = wp_next_scheduled('ai_cron_article_generator');
        
        echo '<h2>Current Schedules:</h2>';
        echo '<p><strong>Process Job:</strong> ' . ($process_next ? date('Y-m-d H:i:s', $process_next) : 'Not scheduled') . '</p>';
        echo '<p><strong>Article Job:</strong> ' . ($article_next ? date('Y-m-d H:i:s', $article_next) : 'Not scheduled') . '</p>';
        
        echo '<h2>All Cron Events:</h2>';
        echo '<pre>' . print_r(_get_cron_array(), true) . '</pre>';
        
        wp_die();
    }
});


// Initialize
AI_Job_Queue::get_instance();
