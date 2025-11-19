<?php
if (!defined('ABSPATH')) exit;

class AI_Job_Queue {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', [$this, 'init']);
        // اضافه کردن هوک برای اجرای دستی
        add_action('wp_ajax_nopriv_run_ai_jobs', [$this, 'run_jobs_manually']);
        add_action('wp_ajax_run_ai_jobs', [$this, 'run_jobs_manually']);
    }

    public function init() {
        error_log('🔄 [JOB_QUEUE] Initializing...');
        $this->setup_cron_schedules();
        $this->schedule_jobs();
        
        // اجرای دستی jobs اگر cron فعال نباشد
        $this->maybe_run_jobs_manually();
    }

    public function setup_cron_schedules() {
        error_log('🔄 [JOB_QUEUE] setup_cron_schedules...');
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
    }

    public function add_cron_intervals($schedules) {
        
        error_log('🔄 [JOB_QUEUE] add_cron_intervals...');
        if (!isset($schedules['every_minute'])) {
            $schedules['every_minute'] = ['interval' => 60, 'display' => __('Every Minute')];
        }
        if (!isset($schedules['every_24_hours'])) {
            $schedules['every_24_hours'] = ['interval' => 86400, 'display' => __('Every 24 Hours')];
        }
        return $schedules;
    }

    public function schedule_jobs() {
        error_log('📅 [JOB_QUEUE] Scheduling jobs...');
        
        //Process requests job - every minute
        if (!wp_next_scheduled('ai_process_requests_job')) {
            wp_schedule_event(time(), 'every_minute', 'ai_process_requests_job');
            error_log('✅ [JOB_QUEUE] Scheduled ai_process_requests_job');
        }
        add_action('ai_process_requests_job', [$this, 'execute_process_requests_job']);

        // Article generator job - every 24 hours
        if (!wp_next_scheduled('article_generator_job')) {
            wp_schedule_event(time(), 'every_minute', 'article_generator_job');
            error_log('✅ [JOB_QUEUE] Scheduled airticle_generator_job');
        }
        add_action('article_generator_job', [$this, 'execute_article_generator_job']);

        // Log scheduled events
      //  $this->log_scheduled_events();
    }

    public function execute_process_requests_job() {
        error_log('🎯 [JOB_QUEUE] Executing process_requests_job at ' . current_time('mysql'));
        
        if (!class_exists('process_requests_job')) {
            error_log('❌ [JOB_QUEUE] process_requests_job class not found');
            return;
        }
        
        $job = new process_requests_job();
        $job->handle();
    }

    public function execute_article_generator_job() {
        error_log('🎯 [JOB_QUEUE] Executing ai_article_generator_job at ' . current_time('mysql'));
        
        if (!class_exists('ai_article_generator_job')) {
            error_log('❌ [JOB_QUEUE] ai_article_generator_job class not found');
            return;
        }
        
        $job = new ai_article_generator_job();
        $job->handle();
    }

    private function log_scheduled_events() {
        error_log('📋 [JOB_QUEUE] Scheduled events:');
        error_log('   - ai_process_requests_job: ' . (wp_next_scheduled('ai_process_requests_job') ? 'YES' : 'NO'));
        error_log('   - ai_article_generator_job: ' . (wp_next_scheduled('article_generator_job') ? 'YES' : 'NO'));
    }

    /**
     * اجرای دستی jobs اگر cron فعال نباشد
     */
    private function maybe_run_jobs_manually() {
        // فقط در صورت درخواست دستی اجرا شود
        if (isset($_GET['run_ai_jobs']) && $_GET['run_ai_jobs'] === '1') {
            $this->run_jobs_manually();
        }
    }

    /**
     * اجرای دستی همه jobs
     */
    public function run_jobs_manually() {
        error_log('🔧 [JOB_QUEUE] Manually running jobs');
        
        // اجرای jobهای schedule شده
        if (wp_next_scheduled('ai_process_requests_job')) {
            $this->execute_process_requests_job();
        }
        
        if (wp_next_scheduled('ai_article_generator_job')) {
            $this->execute_article_generator_job();
        }
        
        // اگر از طریق AJAX فراخوانی شده، پاسخ برگردان
        if (wp_doing_ajax()) {
            wp_die('Jobs executed manually');
        }
    }

    /**
     * غیرفعال کردن WP-Cron و استفاده از سیستم cron واقعی
     */
    public static function disable_wp_cron() {
        if (!defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }
    }
}

// غیرفعال کردن WP-Cron داخلی
//AI_Job_Queue::disable_wp_cron();

// Initialize
AI_Job_Queue::get_instance();