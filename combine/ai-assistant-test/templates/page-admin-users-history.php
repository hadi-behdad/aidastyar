<?php
/**
 * Template Name: مدیریت تاریخچه کاربران
 * صفحه مخصوص ادمین برای مشاهده تمام رکوردهای history
 */

// Check admin access
if (!current_user_can('administrator')) {
    wp_die('دسترسی غیرمجاز');
}

if (!defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

get_header();

global $wpdb;
$table_name = $wpdb->prefix . 'service_history';

// دریافت پارامترهای فیلتر
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$service_filter = isset($_GET['service_id']) ? sanitize_text_field($_GET['service_id']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// ساخت کوئری
$where_clauses = [];
$where_values = [];

if ($search_term) {
    $where_clauses[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR sh.service_name LIKE %s OR sh.id = %d)";
    $search_like = '%' . $wpdb->esc_like($search_term) . '%';
    $where_values[] = $search_like;
    $where_values[] = $search_like;
    $where_values[] = $search_like;
    $where_values[] = is_numeric($search_term) ? intval($search_term) : 0;
}

if ($service_filter) {
    $where_clauses[] = "sh.service_id = %s";
    $where_values[] = $service_filter;
}

if ($status_filter) {
    $where_clauses[] = "sh.status = %s";
    $where_values[] = $status_filter;
}

if ($date_from) {
    $where_clauses[] = "DATE(sh.created_at) >= %s";
    $where_values[] = $date_from;
}

if ($date_to) {
    $where_clauses[] = "DATE(sh.created_at) <= %s";
    $where_values[] = $date_to;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// دریافت تعداد کل
$count_query = "SELECT COUNT(*) 
                FROM {$table_name} sh 
                LEFT JOIN {$wpdb->users} u ON sh.user_id = u.ID 
                {$where_sql}";
if (!empty($where_values)) {
    $count_query = $wpdb->prepare($count_query, $where_values);
}
$total_items = $wpdb->get_var($count_query);

// دریافت داده‌ها
$offset = ($paged - 1) * $per_page;
$query = "SELECT sh.*, u.display_name, u.user_email 
          FROM {$table_name} sh 
          LEFT JOIN {$wpdb->users} u ON sh.user_id = u.ID 
          {$where_sql} 
          ORDER BY sh.created_at DESC 
          LIMIT %d OFFSET %d";

if (!empty($where_values)) {
    $query = $wpdb->prepare($query, array_merge($where_values, [$per_page, $offset]));
} else {
    $query = $wpdb->prepare($query, $per_page, $offset);
}

$history_items = $wpdb->get_results($query);

// دریافت لیست سرویس‌های موجود برای فیلتر
$services_query = "SELECT DISTINCT service_id, service_name FROM {$table_name} ORDER BY service_name";
$available_services = $wpdb->get_results($services_query);

// لیست وضعیت‌ها
$statuses = [
    'queued' => 'در صف انتظار',
    'processing' => 'در حال پردازش',
    'completed' => 'تکمیل شده',
    'consultant_queue' => 'در انتظار مشاور',
    'under_review' => 'در حال بازبینی',
    'draft' => 'پیشنویس',
    'approved' => 'تایید شده',
    'error' => 'خطا در پردازش'
];

$status_colors = [
    'queued' => '#ffc107',
    'processing' => '#17a2b8',
    'completed' => '#28a745',
    'consultant_queue' => '#6f42c1',
    'under_review' => '#fd7e14',
    'draft' => '#6c757d',
    'approved' => '#20c997',
    'error' => '#dc3545'
];
?>

<div class="admin-history-page" style="max-width: 1400px; margin: 2rem auto; padding: 0 1rem; direction: rtl;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="margin: 0; color: #2c3e50;">
            <span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span>
            مدیریت تاریخچه کاربران
        </h1>
        
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?php echo home_url('/ai-dashboard'); ?>" class="button" style="text-decoration: none;">
                <span class="dashicons dashicons-arrow-right-alt"></span>
                بازگشت
            </a>
            <button onclick="window.print()" class="button">
                <span class="dashicons dashicons-printer"></span>
                چاپ
            </button>
            <button onclick="exportToCSV()" class="button">
                <span class="dashicons dashicons-download"></span>
                خروجی CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem;">
        <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem;">جستجو:</label>
                <input type="text" name="search" value="<?php echo esc_attr($search_term); ?>" 
                       placeholder="نام کاربر، ایمیل، سرویس یا ID" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem;">سرویس:</label>
                <select name="service_id" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">همه سرویس‌ها</option>
                    <?php foreach ($available_services as $service): ?>
                        <option value="<?php echo esc_attr($service->service_id); ?>" 
                                <?php selected($service_filter, $service->service_id); ?>>
                            <?php echo esc_html($service->service_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem;">وضعیت:</label>
                <select name="status" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">همه وضعیت‌ها</option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" 
                                <?php selected($status_filter, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem;">از تاریخ:</label>
                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem;">تا تاریخ:</label>
                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem;">تعداد در صفحه:</label>
                <select name="per_page" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="10" <?php selected($per_page, 10); ?>>10</option>
                    <option value="20" <?php selected($per_page, 20); ?>>20</option>
                    <option value="50" <?php selected($per_page, 50); ?>>50</option>
                    <option value="100" <?php selected($per_page, 100); ?>>100</option>
                </select>
            </div>
            
            <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                <button type="submit" class="button button-primary" style="flex: 1;">
                    <span class="dashicons dashicons-filter"></span>
                    اعمال فیلتر
                </button>
                <a href="<?php echo remove_query_arg(['search', 'service_id', 'status', 'date_from', 'date_to', 'paged']); ?>" 
                   class="button" style="text-decoration: none;">
                    <span class="dashicons dashicons-dismiss"></span>
                    حذف فیلتر
                </a>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;"><?php echo number_format($total_items); ?></h3>
            <p style="margin: 0; opacity: 0.9;">کل رکوردها</p>
        </div>
        
        <?php
        // محاسبه آمار
        $stats_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                        SUM(CASE WHEN status IN ('queued', 'processing') THEN 1 ELSE 0 END) as pending,
                        COUNT(DISTINCT user_id) as unique_users
                       FROM {$table_name} sh
                       {$where_sql}";
        if (!empty($where_values)) {
            $stats_query = $wpdb->prepare($stats_query, $where_values);
        }
        $stats = $wpdb->get_row($stats_query);
        ?>
        
        <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;"><?php echo $stats->completed ?? 0; ?></h3>
            <p style="margin: 0; opacity: 0.9;">تکمیل شده</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;"><?php echo $stats->pending ?? 0; ?></h3>
            <p style="margin: 0; opacity: 0.9;">در انتظار</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;"><?php echo $stats->unique_users ?? 0; ?></h3>
            <p style="margin: 0; opacity: 0.9;">کاربران یکتا</p>
        </div>
    </div>

    <!-- Main Table -->
    <div style="background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 1rem; text-align: right;">ID</th>
                    <th style="padding: 1rem; text-align: right;">کاربر</th>
                    <th style="padding: 1rem; text-align: right;">سرویس</th>
                    <th style="padding: 1rem; text-align: right;">تاریخ</th>
                    <th style="padding: 1rem; text-align: right;">وضعیت</th>
                    <th style="padding: 1rem; text-align: center;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($history_items)): ?>
                    <?php foreach ($history_items as $item): 
                        $user_info = get_userdata($item->user_id);
                        $avatar = get_avatar($item->user_id, 32);
                    ?>
                    <tr style="border-bottom: 1px solid #eee; transition: background-color 0.3s;" 
                        onmouseover="this.style.backgroundColor='#f8f9fa'" 
                        onmouseout="this.style.backgroundColor='transparent'">
                        <td style="padding: 1rem;">
                            <strong>#<?php echo esc_html($item->id); ?></strong>
                        </td>
                        
                        <td style="padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <?php echo $avatar; ?>
                                <div>
                                    <strong><?php echo esc_html($item->display_name); ?></strong>
                                    <br>
                                    <small style="color: #6c757d;"><?php echo esc_html($item->user_email); ?></small>
                                    <br>
                                    <small style="color: #6c757d;">User ID: <?php echo $item->user_id; ?></small>
                                </div>
                            </div>
                        </td>
                        
                        <td style="padding: 1rem;">
                            <?php echo esc_html($item->service_name); ?>
                            <br>
                            <small style="color: #6c757d;"><?php echo esc_html($item->service_id); ?></small>
                        </td>
                        
                        <td style="padding: 1rem;">
                            <?php echo date_i18n('Y/m/d', strtotime($item->created_at)); ?>
                            <br>
                            <small style="color: #6c757d;"><?php echo date_i18n('H:i', strtotime($item->created_at)); ?></small>
                        </td>
                        
                        <td style="padding: 1rem;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; 
                                         background-color: <?php echo $status_colors[$item->status] ?? '#6c757d'; ?>; 
                                         color: white; font-size: 0.85rem;">
                                <?php echo $statuses[$item->status] ?? $item->status; ?>
                            </span>
                        </td>
                        
                        <td style="padding: 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <a href="<?php echo home_url('/service-output/?history_id=' . $item->id . '&user_id=' . $item->user_id . '&admin_view=true'); ?>"   
                                   target="_blank"
                                   class="button button-small"
                                   style="text-decoration: none;"
                                   title="مشاهده خروجی">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                                
                                <a href="<?php echo get_edit_user_link($item->user_id); ?>" 
                                   target="_blank"
                                   class="button button-small"
                                   style="text-decoration: none;"
                                   title="پروفایل کاربر">
                                    <span class="dashicons dashicons-admin-users"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 2rem; text-align: center; color: #6c757d;">
                            <span class="dashicons dashicons-info" style="font-size: 2rem;"></span>
                            <p>موردی یافت نشد</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_items > $per_page): 
        $total_pages = ceil($total_items / $per_page);
        $current_url = remove_query_arg('paged');
    ?>
    <div style="margin-top: 2rem; text-align: center;">
        <div style="display: inline-flex; gap: 0.5rem; background: white; padding: 0.5rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <?php if ($paged > 1): ?>
                <a href="<?php echo add_query_arg('paged', $paged - 1, $current_url); ?>" 
                   class="button" style="text-decoration: none;">« قبلی</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $paged - 2);
            $end = min($total_pages, $paged + 2);
            
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="<?php echo add_query_arg('paged', $i, $current_url); ?>" 
                   class="button <?php echo $i === $paged ? 'button-primary' : ''; ?>"
                   style="text-decoration: none; min-width: 40px;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($paged < $total_pages): ?>
                <a href="<?php echo add_query_arg('paged', $paged + 1, $current_url); ?>" 
                   class="button" style="text-decoration: none;">بعدی »</a>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 0.5rem; color: #6c757d; font-size: 0.9rem;">
            صفحه <?php echo $paged; ?> از <?php echo $total_pages; ?> | 
            نمایش <?php echo count($history_items); ?> از <?php echo number_format($total_items); ?> رکورد
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function exportToCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export_csv', '1');
    window.location.href = '?' + params.toString();
}
</script>

<?php
// Handle CSV export
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="history_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8
    
    // Headers
    fputcsv($output, ['ID', 'کاربر', 'ایمیل', 'سرویس', 'تاریخ', 'وضعیت']);
    
    $all_items = $wpdb->get_results($query);
    foreach ($all_items as $item) {
        fputcsv($output, [
            $item->id,
            $item->display_name,
            $item->user_email,
            $item->service_name,
            $item->created_at,
            $statuses[$item->status] ?? $item->status
        ]);
    }
    
    fclose($output);
    exit;
}

get_footer();
?>