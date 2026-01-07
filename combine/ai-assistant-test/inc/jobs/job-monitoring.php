<?php
/**
 * Plugin Name: AI Job Status Monitor
 * Plugin URI: https://test.aidastyar.com
 * Description: نمایش وضعیت Job های زمان‌بندی شده هوش مصنوعی و آمار API Calls
 * Version: 2.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================
// 1. منوی مدیریت
// ============================================
add_action('admin_menu', 'add_ai_job_status_page');

function add_ai_job_status_page() {
    add_menu_page(
        'وضعیت AI Jobs و API Calls',
        'AI Job Status',
        'manage_options',
        'ai-job-status',
        'display_ai_job_status_page',
        'dashicons-chart-area',
        30
    );
}

// ============================================
// 2. صفحه مدیریت اصلی
// ============================================
function display_ai_job_status_page() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // دریافت آمار API
    $api_stats = AI_Job_Queue::get_api_stats();
    $chart_data = AI_Job_Queue::get_chart_data(7);
    
    // دریافت وضعیت Jobها
    $status = AI_Job_Queue::get_instance()->get_status();
    
    // بررسی cron events
    $process_next = wp_next_scheduled('ai_cron_process_requests');
    $article_next = wp_next_scheduled('ai_cron_article_generator');
    
    // پیام‌های موفقیت
    if (isset($_GET['message'])) {
        $messages = [
            'job_executed' => '✅ Job با موفقیت اجرا شد.',
            'rescheduled' => '✅ Job ها دوباره برنامه‌ریزی شدند.',
            'stats_cleared' => '✅ آمار API با موفقیت پاک شد.',
            'debug_complete' => '✅ دیباگ کامل انجام شد.'
        ];
        
        if (isset($messages[$_GET['message']])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . $messages[$_GET['message']] . '</p></div>';
        }
    }
    ?>
    
    <!-- HTML صفحه مدیریت -->
    <div class="wrap">
        <h1><span class="dashicons dashicons-chart-area"></span> وضعیت AI Jobs و API Calls</h1>
        
        <!-- کارت‌های خلاصه -->
        <div class="ai-stats-cards">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <span class="dashicons dashicons-update"></span>
                    <h3>امروز</h3>
                </div>
                <div class="card-body">
                    <div class="stat-number"><?php echo $api_stats['today']['total']; ?></div>
                    <div class="stat-details">
                        <div>📝 مقاله: <strong><?php echo $api_stats['today']['article_generator']; ?></strong></div>
                        <div>🔄 پردازش: <strong><?php echo $api_stats['today']['process_requests']; ?></strong></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <h3>این هفته</h3>
                </div>
                <div class="card-body">
                    <div class="stat-number"><?php echo $api_stats['this_week']['total']; ?></div>
                    <div class="stat-details">
                        <div>📝 مقاله: <strong><?php echo $api_stats['this_week']['article_generator']; ?></strong></div>
                        <div>🔄 پردازش: <strong><?php echo $api_stats['this_week']['process_requests']; ?></strong></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <span class="dashicons dashicons-chart-line"></span>
                    <h3>این ماه</h3>
                </div>
                <div class="card-body">
                    <div class="stat-number"><?php echo $api_stats['this_month']['total']; ?></div>
                    <div class="stat-details">
                        <div>📝 مقاله: <strong><?php echo $api_stats['this_month']['article_generator']; ?></strong></div>
                        <div>🔄 پردازش: <strong><?php echo $api_stats['this_month']['process_requests']; ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- جدول آمار دقیق -->
        <div class="card full-width">
            <h2><span class="dashicons dashicons-analytics"></span> آمار دقیق API Calls</h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>بازه زمانی</th>
                        <th>📝 Article Generator</th>
                        <th>🔄 Process Requests</th>
                        <th>✋ Manual/Other</th>
                        <th>📊 مجموع</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>امروز (<?php echo date('Y-m-d'); ?>)</strong></td>
                        <td class="number-cell"><?php echo $api_stats['today']['article_generator']; ?></td>
                        <td class="number-cell"><?php echo $api_stats['today']['process_requests']; ?></td>
                        <td class="number-cell"><?php echo $api_stats['today']['manual']; ?></td>
                        <td class="total-cell"><?php echo $api_stats['today']['total']; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=ai-job-status&action=clear_today_stats'); ?>" 
                               class="button button-small"
                               onclick="return confirm('آیا می‌خواهید آمار امروز را پاک کنید؟')">
                                <span class="dashicons dashicons-trash"></span> پاک
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>۷ روز گذشته</strong></td>
                        <td class="number-cell"><?php echo $api_stats['this_week']['article_generator']; ?></td>
                        <td class="number-cell"><?php echo $api_stats['this_week']['process_requests']; ?></td>
                        <td class="number-cell"><?php echo $api_stats['this_week']['manual']; ?></td>
                        <td class="total-cell"><?php echo $api_stats['this_week']['total']; ?></td>
                        <td>
                            <button class="button button-small" onclick="showWeekDetails()">
                                <span class="dashicons dashicons-visibility"></span> جزئیات
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>۳۰ روز گذشته</strong></td>
                        <td class="number-cell"><?php echo $api_stats['this_month']['article_generator']; ?></td>
                        <td class="number-cell"><?php echo $api_stats['this_month']['process_requests']; ?></td>
                        <td class="number-cell"><?php echo $api_stats['this_month']['manual']; ?></td>
                        <td class="total-cell"><?php echo $api_stats['this_month']['total']; ?></td>
                        <td>
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php echo round($api_stats['this_month']['total'] / 30, 1); ?>/روز
                        </td>
                    </tr>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td><strong>کل آمار</strong></td>
                        <td class="number-cell" style="color: #2196F3;"><?php echo $api_stats['all_time']['article_generator']; ?></td>
                        <td class="number-cell" style="color: #4CAF50;"><?php echo $api_stats['all_time']['process_requests']; ?></td>
                        <td class="number-cell"><?php echo $api_stats['all_time']['manual']; ?></td>
                        <td class="total-cell" style="color: #9C27B0; font-size: 1.2em;"><?php echo $api_stats['all_time']['total']; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=ai-job-status&action=clear_all_stats'); ?>" 
                               class="button button-small button-link-delete"
                               onclick="return confirm('⚠️ آیا مطمئن هستید؟ تمام آمار پاک خواهد شد!')">
                                <span class="dashicons dashicons-warning"></span> پاک کردن همه
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- نمودار -->
        <div class="card full-width">
            <h2><span class="dashicons dashicons-chart-area"></span> نمودار مصرف API (۷ روز گذشته)</h2>
            
            <div style="height: 300px; padding: 20px;">
                <canvas id="apiChart"></canvas>
            </div>
        </div>
        
        <!-- وضعیت Jobها -->
        <div class="card full-width">
            <h2><span class="dashicons dashicons-clock"></span> وضعیت Job ها</h2>
            
            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                    <h3>📝 Article Generator</h3>
                    <p><strong>اجرای بعدی:</strong><br>
                    <?php 
                    if ($article_next) {
                        echo date_i18n('Y-m-d H:i:s', $article_next);
                        echo '<br><small>(' . human_time_diff(time(), $article_next) . ' دیگر)</small>';
                    } else {
                        echo '<span style="color: #dc3232;">❌ تنظیم نشده</span>';
                    }
                    ?>
                    </p>
                    <p><strong>آخرین اجرا:</strong><br>
                    <?php 
                    $last_article = get_option('ai_job_last_article_run', 0);
                    echo $last_article ? date_i18n('Y-m-d H:i:s', $last_article) : 'هرگز';
                    ?>
                    </p>
                    <p><strong>API Calls امروز:</strong><br>
                    <span style="font-size: 24px; color: #2196F3;"><?php echo $api_stats['today']['article_generator']; ?></span>
                    </p>
                </div>
                
                <div style="flex: 1; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                    <h3>🔄 Process Requests</h3>
                    <p><strong>اجرای بعدی:</strong><br>
                    <?php 
                    if ($process_next) {
                        echo date_i18n('Y-m-d H:i:s', $process_next);
                        echo '<br><small>(' . human_time_diff(time(), $process_next) . ' دیگر)</small>';
                    } else {
                        echo '<span style="color: #dc3232;">❌ تنظیم نشده</span>';
                    }
                    ?>
                    </p>
                    <p><strong>آخرین اجرا:</strong><br>
                    <?php 
                    $last_process = get_option('ai_job_last_process_run', 0);
                    echo $last_process ? date_i18n('Y-m-d H:i:s', $last_process) : 'هرگز';
                    ?>
                    </p>
                    <p><strong>API Calls امروز:</strong><br>
                    <span style="font-size: 24px; color: #4CAF50;"><?php echo $api_stats['today']['process_requests']; ?></span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- ابزارهای مدیریت -->
        <div class="card full-width">
            <h2><span class="dashicons dashicons-admin-tools"></span> ابزارهای مدیریت</h2>
            
            <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                <a href="<?php echo admin_url('admin.php?page=ai-job-status&action=force_run_article'); ?>" 
                   class="button button-primary"
                   onclick="return confirm('آیا مطمئن هستید؟ این کار ممکن است چند دقیقه طول بکشد.')">
                    <span class="dashicons dashicons-update"></span> اجرای دستی Article Job
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=ai-job-status&action=force_run_process'); ?>" 
                   class="button"
                   onclick="return confirm('آیا می‌خواهید Job پردازش را دستی اجرا کنید؟')">
                    <span class="dashicons dashicons-migrate"></span> اجرای دستی Process Job
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=ai-job-status&action=reschedule'); ?>" 
                   class="button"
                   onclick="return confirm('آیا می‌خواهید برنامه‌ریزی مجدد شود؟')">
                    <span class="dashicons dashicons-calendar"></span> برنامه‌ریزی مجدد
                </a>
                
                <a href="<?php echo site_url('/?check_ai_schedules=1'); ?>" 
                   target="_blank"
                   class="button">
                    <span class="dashicons dashicons-search"></span> مشاهده Cron Events
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=ai-job-status&action=debug_stats'); ?>" 
                   class="button button-secondary">
                    <span class="dashicons dashicons-editor-code"></span> دیباگ آمار
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=ai-job-status&action=verify_counts'); ?>" 
                   class="button button-secondary"
                   onclick="return confirm('این عملیات ممکن است چند ثانیه طول بکشد.')">
                    <span class="dashicons dashicons-yes-alt"></span> تایید شمارش
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('apiChart').getContext('2d');
        
        // داده‌های واقعی از PHP
        const labels = <?php echo json_encode($chart_data['labels']); ?>;
        const articleData = <?php echo json_encode($chart_data['datasets']['article_generator']); ?>;
        const processData = <?php echo json_encode($chart_data['datasets']['process_requests']); ?>;
        const totalData = <?php echo json_encode($chart_data['datasets']['total']); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '📝 Article Generator',
                        data: articleData,
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '🔄 Process Requests',
                        data: processData,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '📊 مجموع',
                        data: totalData,
                        borderColor: '#9C27B0',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true
                    },
                    title: {
                        display: true,
                        text: 'تعداد فراخوانی‌های API در ۷ روز گذشته',
                        rtl: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'تعداد فراخوانی'
                        }
                    },
                    x: {
                        reverse: true
                    }
                }
            }
        });
    });
    
    function showWeekDetails() {
        alert('جزئیات هفته در نسخه بعدی اضافه خواهد شد.');
    }
    </script>
    
    <style>
    .wrap h1 {
        color: #1d2327;
        border-bottom: 2px solid #0073aa;
        padding-bottom: 10px;
        margin-bottom: 30px;
    }
    
    .ai-stats-cards {
        display: flex;
        gap: 20px;
        margin: 20px 0;
    }
    
    .ai-stats-cards .card {
        flex: 1;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .card-header {
        color: white;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .card-header h3 {
        margin: 0;
        color: white;
    }
    
    .card-body {
        padding: 20px;
        text-align: center;
    }
    
    .card{
        
        max-width: 100%;
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .stat-details {
        font-size: 14px;
        color: #666;
    }
    
    .stat-details div {
        margin: 5px 0;
    }
    
    .full-width {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .wp-list-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .number-cell {
        text-align: center;
        font-weight: bold;
        font-size: 16px;
    }
    
    .total-cell {
        text-align: center;
        font-weight: bold;
        font-size: 18px;
        color: #0073aa;
    }
    
    .button-link-delete {
        color: #dc3232 !important;
        border-color: #dc3232 !important;
    }
    
    .button-link-delete:hover {
        background: #dc3232 !important;
        color: white !important;
    }
    
    @media (max-width: 1200px) {
        .ai-stats-cards {
            flex-direction: column;
        }
    }
    </style>
    <?php
}

// ============================================
// 3. مدیریت Actions (دکمه‌های صفحه)
// ============================================
add_action('admin_init', function() {
    // فقط اگر در صفحه مدیریت AI Job Status هستیم
    if (!isset($_GET['page']) || $_GET['page'] !== 'ai-job-status') {
        return;
    }
    
    // بررسی دسترسی کاربر
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // اگر Action مشخص شده بود
    if (isset($_GET['action'])) {
        $redirect_url = admin_url('admin.php?page=ai-job-status');
        
        switch ($_GET['action']) {
            // اجرای دستی Article Job
            case 'force_run_article':
                if (class_exists('AI_Job_Queue')) {
                    $instance = AI_Job_Queue::get_instance();
                    if (method_exists($instance, 'execute_article_generator_job')) {
                        $instance->execute_article_generator_job();
                        $redirect_url .= '&message=job_executed';
                    }
                }
                break;
                
            // اجرای دستی Process Job
            case 'force_run_process':
                if (class_exists('AI_Job_Queue')) {
                    $instance = AI_Job_Queue::get_instance();
                    if (method_exists($instance, 'execute_process_requests_job')) {
                        $instance->execute_process_requests_job();
                        $redirect_url .= '&message=job_executed';
                    }
                }
                break;
                
            // برنامه‌ریزی مجدد Jobها
            case 'reschedule':
                if (class_exists('AI_Job_Queue')) {
                    $instance = AI_Job_Queue::get_instance();
                    // پاک کردن schedule های قبلی
                    wp_clear_scheduled_hook('ai_cron_process_requests');
                    wp_clear_scheduled_hook('ai_cron_article_generator');
                    // ایجاد schedule جدید
                    $instance->maybe_schedule_jobs();
                    $redirect_url .= '&message=rescheduled';
                }
                break;
                
            // پاک کردن آمار امروز
            case 'clear_today_stats':
                if (class_exists('AI_Job_Queue')) {
                    AI_Job_Queue::reset_api_stats();
                    $redirect_url .= '&message=stats_cleared';
                }
                break;
                
            // پاک کردن تمام آمار
            case 'clear_all_stats':
                if (class_exists('AI_Job_Queue')) {
                    AI_Job_Queue::reset_api_stats('all');
                    $redirect_url .= '&message=stats_cleared';
                }
                break;
                
            // نمایش صفحه دیباگ
            case 'debug_stats':
                display_debug_stats();
                exit;
                break;
                
            // تایید شمارش
            case 'verify_counts':
                verify_api_counts();
                $redirect_url .= '&message=debug_complete';
                break;
        }
        
        // ریدایرکت به صفحه اصلی با پیام مناسب
        wp_redirect($redirect_url);
        exit;
    }
});

// ============================================
// 4. توابع کمکی
// ============================================

/**
 * نمایش صفحه دیباگ آمار
 */
function display_debug_stats() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    ?>
    <div class="wrap">
        <h1>🐛 دیباگ آمار API Calls</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>📊 آمار ذخیره شده در دیتابیس</h2>
            
            <?php
            if (class_exists('AI_Job_Queue')) {
                $stats = AI_Job_Queue::get_api_stats();
                
                echo '<h3>آمار کامل:</h3>';
                echo '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 400px; overflow: auto;">';
                print_r($stats);
                echo '</pre>';
                
                echo '<h3>داده‌های خام:</h3>';
                $raw_data = get_option('ai_api_calls_stats_v2', []);
                echo '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 400px; overflow: auto;">';
                print_r($raw_data);
                echo '</pre>';
            } else {
                echo '<p style="color: red;">کلاس AI_Job_Queue پیدا نشد!</p>';
            }
            ?>
        </div>
        
        <a href="<?php echo admin_url('admin.php?page=ai-job-status'); ?>" class="button button-primary">
            بازگشت به صفحه اصلی
        </a>
    </div>
    <?php
}

/**
 * تایید شمارش API Calls
 */
function verify_api_counts() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    echo '<div class="wrap">';
    echo '<h1>✅ تایید شمارش API Calls</h1>';
    
    // شمارش لاگ‌های امروز
    $log_file = ABSPATH . 'error_log';
    if (file_exists($log_file)) {
        $content = file_get_contents($log_file);
        
        // شمارش بر اساس Job
        $article_calls_today = substr_count($content, 'API call for AI Article Generator');
        $process_calls_today = substr_count($content, 'API call for Process Requests');
        
        echo '<div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        echo '<h3>تعداد API Calls از روی لاگ‌ها:</h3>';
        echo '<p>📝 Article Generator: ' . $article_calls_today . '</p>';
        echo '<p>🔄 Process Requests: ' . $process_calls_today . '</p>';
        echo '<p>📊 مجموع: ' . ($article_calls_today + $process_calls_today) . '</p>';
        echo '</div>';
    }
    
    // نمایش آمار ذخیره شده
    if (class_exists('AI_Job_Queue')) {
        $stats = AI_Job_Queue::get_api_stats();
        
        echo '<div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        echo '<h3>تعداد API Calls ذخیره شده:</h3>';
        echo '<p>📝 Article Generator: ' . $stats['today']['article_generator'] . '</p>';
        echo '<p>🔄 Process Requests: ' . $stats['today']['process_requests'] . '</p>';
        echo '<p>📊 مجموع: ' . $stats['today']['total'] . '</p>';
        echo '</div>';
    }
    
    echo '<p><a href="' . admin_url('admin.php?page=ai-job-status') . '" class="button button-primary">بازگشت</a></p>';
    echo '</div>';
    exit;
}