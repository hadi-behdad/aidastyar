<?php
// /inc/class-discount-manager.php

class AI_Assistant_Discount_Manager {
    private static $instance;
    private $table_name;
    private $table_checked = false;
    private $logger;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'assistant_discounts';
        $this->logger = AI_Assistant_Logger::get_instance();
        
        // اضافه کردن هوک‌های مدیریت
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('admin_init', [$this, 'handle_delete_action']);
        
        // ایجاد جدول در صورت نیاز
        $this->maybe_create_table();
    }
    
    /**
     * ایجاد جدول تخفیف‌ها در صورت عدم وجود
     */
    private function maybe_create_table() {
        global $wpdb;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                discount_type varchar(20) NOT NULL,
                discount_value decimal(10,0) NOT NULL,
                scope varchar(20) NOT NULL,
                service_ids text DEFAULT NULL,
                coupon_code varchar(100) DEFAULT NULL,
                start_date datetime DEFAULT NULL,
                end_date datetime DEFAULT NULL,
                usage_limit int(11) DEFAULT 0,
                usage_count int(11) DEFAULT 0,
                user_restriction varchar(20) DEFAULT NULL,
                user_ids text DEFAULT NULL,
                min_order_amount decimal(15,0) DEFAULT 0,
                active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX (scope),
                INDEX (coupon_code),
                INDEX (active),
                INDEX (start_date),
                INDEX (end_date)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            $this->logger->log('جدول تخفیف‌ها ایجاد شد');
        }
        
        $this->table_checked = true;
    }
    
    /**
     * افزودن منوی مدیریت تخفیف‌ها به پنل ادمین
     */
    public function add_admin_menu() {
        // افزودن منوی اصلی
        add_menu_page(
            'مدیریت تخفیف‌ها',
            'تخفیف‌های AI',
            'manage_options',
            'ai-assistant-discounts',
            [$this, 'render_discounts_page'],
            'dashicons-tickets-alt',
            30
        );
        
        // افزودن زیرمنوها
        add_submenu_page(
            'ai-assistant-discounts',
            'مدیریت تخفیف‌ها',
            'همه تخفیف‌ها',
            'manage_options',
            'ai-assistant-discounts',
            [$this, 'render_discounts_page']
        );
        
        add_submenu_page(
            'ai-assistant-discounts',
            'افزودن تخفیف جدید',
            'افزودن جدید',
            'manage_options',
            'ai-assistant-discounts-add',
            [$this, 'render_add_discount_page']
        );
    }
    
    /**
     * رندر صفحه مدیریت تخفیف‌ها
     */
    public function render_discounts_page() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $discount_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        
        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">مدیریت تخفیف‌ها</h1>';
        echo '<a href="' . admin_url('admin.php?page=ai-assistant-discounts-add') . '" class="page-title-action">افزودن جدید</a>';
        echo '<hr class="wp-header-end">';
        
        switch ($action) {
            case 'edit':
                $this->render_edit_form($discount_id);
                break;
            default:
                $this->render_discounts_list();
                break;
        }
        
        echo '</div>';
    }
    
    /**
     * رندر صفحه افزودن تخفیف جدید
     */
    public function render_add_discount_page() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        echo '<div class="wrap">';
        echo '<h1>افزودن تخفیف جدید</h1>';
        $this->render_edit_form();
        echo '</div>';
    }
    
    /**
     * رندر فرم ویرایش/افزودن تخفیف
     */
    private function render_edit_form($discount_id = 0) {
        $discount = $discount_id ? $this->get_discount($discount_id) : null;
        $service_manager = AI_Assistant_Service_Manager::get_instance();
        $services = $service_manager->get_all_services();
        
        echo '<form method="post" action="">';
        wp_nonce_field('ai_assistant_discount_nonce', 'ai_discount_nonce');
        
        echo '<input type="hidden" name="action" value="' . ($discount_id ? 'edit_discount' : 'add_discount') . '">';
        if ($discount_id) {
            echo '<input type="hidden" name="discount_id" value="' . $discount_id . '">';
        }
        
        echo '<table class="form-table">';
        
        // نام تخفیف
        echo '<tr><th scope="row"><label for="discount_name">نام تخفیف</label></th>';
        echo '<td><input type="text" id="discount_name" name="discount_name" value="' . 
             ($discount ? esc_attr($discount->name) : '') . '" required class="regular-text"></td></tr>';
        
        // نوع تخفیف
        echo '<tr><th scope="row"><label for="discount_type">نوع تخفیف</label></th>';
        echo '<td><select id="discount_type" name="discount_type" required>';
        echo '<option value="percentage" ' . ($discount && $discount->discount_type == 'percentage' ? 'selected' : '') . '>درصدی</option>';
        echo '<option value="fixed_amount" ' . ($discount && $discount->discount_type == 'fixed_amount' ? 'selected' : '') . '>مبلغ ثابت</option>';
        echo '</select></td></tr>';
        
        // مقدار تخفیف
        echo '<tr><th scope="row"><label for="discount_value">مقدار تخفیف</label></th>';
        echo '<td><input type="number" id="discount_value" name="discount_value" value="' . 
             ($discount ? esc_attr($discount->discount_value) : '') . '" step="1" min="0" required class="small-text">';
        echo '<span id="discount_value_suffix">' . ($discount && $discount->discount_type == 'percentage' ? '%' : 'تومان') . '</span></td></tr>';
        
        // دامنه اعمال تخفیف
        echo '<tr><th scope="row"><label for="scope">دامنه اعمال</label></th>';
        echo '<td><select id="scope" name="scope" required>';
        echo '<option value="global" ' . ($discount && $discount->scope == 'global' ? 'selected' : '') . '>تخفیف عمومی (روی همه سرویس‌ها)</option>';
        echo '<option value="service" ' . ($discount && $discount->scope == 'service' ? 'selected' : '') . '>تخفیف روی سرویس‌های خاص</option>';
        echo '<option value="coupon" ' . ($discount && $discount->scope == 'coupon' ? 'selected' : '') . '>کد تخفیف</option>';
        echo '<option value="user_based" ' . ($discount && $discount->scope == 'user_based' ? 'selected' : '') . '>تخفیف کاربرمحور</option>';
        echo '</select></td></tr>';
        
        // سرویس‌های خاص (برای scope=service)
        echo '<tr id="service_ids_row" style="display: none;"><th scope="row"><label for="service_ids">سرویس‌های موردنظر</label></th>';
        echo '<td><select id="service_ids" name="service_ids[]" multiple class="regular-text" style="height: 120px;">';
        foreach ($services as $service_id => $service) {
            $selected = $discount && $discount->scope == 'service' && in_array($service_id, explode(',', $discount->service_ids)) ? 'selected' : '';
            echo '<option value="' . esc_attr($service_id) . '" ' . $selected . '>' . esc_html($service['name']) . '</option>';
        }
        echo '</select><p class="description">برای انتخاب چند سرویس، کلید Ctrl را نگه دارید</p></td></tr>';
        
        // کد تخفیف (برای scope=coupon)
        echo '<tr id="coupon_code_row" style="display: none;"><th scope="row"><label for="coupon_code">کد تخفیف</label></th>';
        echo '<td><input type="text" id="coupon_code" name="coupon_code" value="' . 
             ($discount && $discount->scope == 'coupon' ? esc_attr($discount->coupon_code) : '') . '" class="regular-text">';
        echo '<p class="description">کد منحصربه‌فردی که کاربران باید وارد کنند</p></td></tr>';
        
        // محدودیت کاربر (برای scope=user_based)
        echo '<tr id="user_restriction_row" style="display: none;"><th scope="row"><label for="user_restriction">محدودیت کاربری</label></th>';
        echo '<td><select id="user_restriction" name="user_restriction">';
        echo '<option value="first_time" ' . ($discount && $discount->user_restriction == 'first_time' ? 'selected' : '') . '>فقط اولین استفاده کاربر</option>';
        echo '<option value="specific_users" ' . ($discount && $discount->user_restriction == 'specific_users' ? 'selected' : '') . '>کاربران خاص</option>';
        echo '</select></td></tr>';
        
        // کاربران خاص (برای user_restriction=specific_users)
        echo '<tr id="user_ids_row" style="display: none;"><th scope="row"><label for="user_ids">کاربران موردنظر</label></th>';
        echo '<td>';
        $user_ids = $discount && $discount->user_restriction == 'specific_users' ? explode(',', $discount->user_ids) : [];
        echo '<select id="user_ids" name="user_ids[]" multiple class="regular-text" style="height: 120px;">';
        
        $users = get_users();
        foreach ($users as $user) {
            $selected = in_array($user->ID, $user_ids) ? 'selected' : '';
            echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
        }
        echo '</select><p class="description">برای انتخاب چند کاربر، کلید Ctrl را نگه دارید</p></td></tr>';
        
        // تاریخ شروع
        echo '<tr><th scope="row"><label for="start_date">تاریخ شروع</label></th>';
        echo '<td><input type="datetime-local" id="start_date" name="start_date" value="' . 
             ($discount && $discount->start_date ? date('Y-m-d\TH:i', strtotime($discount->start_date)) : '') . '" class="regular-text">';
        echo '<p class="description">خالی = بلافاصله فعال شود</p></td></tr>';
        
        // تاریخ پایان
        echo '<tr><th scope="row"><label for="end_date">تاریخ پایان</label></th>';
        echo '<td><input type="datetime-local" id="end_date" name="end_date" value="' . 
             ($discount && $discount->end_date ? date('Y-m-d\TH:i', strtotime($discount->end_date)) : '') . '" class="regular-text">';
        echo '<p class="description">خالی = بدون تاریخ انقضا</p></td></tr>';
        
        // محدودیت تعداد استفاده
        echo '<tr><th scope="row"><label for="usage_limit">محدودیت تعداد استفاده</label></th>';
        echo '<td><input type="number" id="usage_limit" name="usage_limit" value="' . 
             ($discount ? esc_attr($discount->usage_limit) : '0') . '" min="0" class="small-text">';
        echo '<p class="description">0 = بدون محدودیت</p></td></tr>';
        
        // حداقل مبلغ سفارش
        echo '<tr><th scope="row"><label for="min_order_amount">حداقل مبلغ سفارش</label></th>';
        echo '<td><input type="number" id="min_order_amount" name="min_order_amount" value="' . 
             ($discount ? esc_attr($discount->min_order_amount) : '0') . '" step="10000" min="0" class="small-text"> تومان</td></tr>';
        
        // وضعیت فعال
        echo '<tr><th scope="row"><label for="active">وضعیت</label></th>';
        echo '<td><input type="checkbox" id="active" name="active" value="1" ' . 
             ($discount ? checked($discount->active, 1, false) : 'checked') . '> فعال</td></tr>';
        
        echo '</table>';
        
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="ذخیره تغییرات">';
        echo ' <a href="' . admin_url('admin.php?page=ai-assistant-discounts') . '" class="button">انصراف</a>';
        echo '</p>';
        
        echo '</form>';
        
        // اسکریپت برای نمایش/پنهان کردن فیلدهای مرتبط
        echo '<script>
        jQuery(document).ready(function($) {
            function toggleFields() {
                var scope = $("#scope").val();
                $("#service_ids_row, #coupon_code_row, #user_restriction_row").hide();
                
                if (scope === "service") {
                    $("#service_ids_row").show();
                } else if (scope === "coupon") {
                    $("#coupon_code_row").show();
                } else if (scope === "user_based") {
                    $("#user_restriction_row").show();
                    var userRestriction = $("#user_restriction").val();
                    $("#user_ids_row").toggle(userRestriction === "specific_users");
                }
                
                // تغییر واحد مقدار تخفیف
                var discountType = $("#discount_type").val();
                $("#discount_value_suffix").text(discountType === "percentage" ? "%" : "تومان");
            }
            
            $("#scope, #discount_type, #user_restriction").change(toggleFields);
            toggleFields();
        });
        </script>';
    }
    
    /**
     * رندر لیست تخفیف‌ها
     */
    private function render_discounts_list() {
        $discounts = $this->get_all_discounts();
        
        if (empty($discounts)) {
            echo '<p>هیچ تخفیفی تعریف نشده است.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>نام</th>';
        echo '<th>نوع</th>';
        echo '<th>مقدار</th>';
        echo '<th>دامنه</th>';
        echo '<th>تاریخ شروع</th>';
        echo '<th>تاریخ پایان</th>';
        echo '<th>استفاده شده</th>';
        echo '<th>وضعیت</th>';
        echo '<th>عملیات</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($discounts as $discount) {
            $scope_label = $this->get_scope_label($discount->scope, $discount);
            $status_label = $discount->active ? '<span style="color:green;">فعال</span>' : '<span style="color:red;">غیرفعال</span>';
            
            echo '<tr>';
            echo '<td>' . esc_html($discount->name) . '</td>';
            echo '<td>' . ($discount->discount_type == 'percentage' ? 'درصدی' : 'مبلغ ثابت') . '</td>';
            echo '<td>' . number_format($discount->discount_value) . ($discount->discount_type == 'percentage' ? '%' : ' تومان') . '</td>';
            echo '<td>' . $scope_label . '</td>';
            echo '<td>' . ($discount->start_date ? date('Y-m-d H:i', strtotime($discount->start_date)) : '-') . '</td>';
            echo '<td>' . ($discount->end_date ? date('Y-m-d H:i', strtotime($discount->end_date)) : '-') . '</td>';
            echo '<td>' . $discount->usage_count . ($discount->usage_limit > 0 ? ' / ' . $discount->usage_limit : '') . '</td>';
            echo '<td>' . $status_label . '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=ai-assistant-discounts&action=edit&id=' . $discount->id) . '">ویرایش</a> | ';
            echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=ai-assistant-discounts&action=delete&id=' . $discount->id), 'delete_discount_' . $discount->id) . '" onclick="return confirm(\'آیا مطمئن هستید؟\')">حذف</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * پردازش حذف تخفیف
     */
    public function handle_delete_action() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete' || !isset($_GET['id'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $discount_id = absint($_GET['id']);
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        
        if (!wp_verify_nonce($nonce, 'delete_discount_' . $discount_id)) {
            wp_die('Nonce verification failed');
        }
        
        if ($this->delete_discount($discount_id)) {
            $this->logger->log('تخفیف با موفقیت حذف شد: ' . $discount_id);
            wp_redirect(admin_url('admin.php?page=ai-assistant-discounts&message=deleted'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=ai-assistant-discounts&message=delete_error'));
            exit;
        }
    }
    
    /**
     * پردازش فرم‌های ارسالی
     */
    public function handle_form_submissions() {
        if (!isset($_POST['ai_discount_nonce']) || !wp_verify_nonce($_POST['ai_discount_nonce'], 'ai_assistant_discount_nonce')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
        
        if ($action === 'add_discount') {
            $this->add_discount($_POST);
        } elseif ($action === 'edit_discount') {
            $discount_id = isset($_POST['discount_id']) ? absint($_POST['discount_id']) : 0;
            if ($discount_id) {
                $this->update_discount($discount_id, $_POST);
            }
        }
        
        // ریدایرکت برای جلوگیری از ارسال مجدد فرم
        wp_redirect(admin_url('admin.php?page=ai-assistant-discounts'));
        exit;
    }
    
    
    /**
     * افزودن تخفیف جدید
     */
    private function add_discount($data) {
        global $wpdb;
        
        $discount_data = $this->sanitize_discount_data($data);
        
        $result = $wpdb->insert(
            $this->table_name,
            $discount_data,
            $this->get_format_for_data($discount_data)
        );
        
        if ($result) {
            $this->logger->log('تخفیف جدید با موفقیت افزوده شد: ' . $discount_data['name']);
            add_settings_error('ai_assistant_discounts', 'discount_added', 'تخفیف جدید با موفقیت افزوده شد.', 'success');
        } else {
            $this->logger->log('خطا در افزودن تخفیف جدید', 'error');
            add_settings_error('ai_assistant_discounts', 'discount_error', 'خطا در افزودن تخفیف جدید.', 'error');
        }
    }
    
    /**
     * به‌روزرسانی تخفیف
     */
    private function update_discount($discount_id, $data) {
        global $wpdb;
        
        $discount_data = $this->sanitize_discount_data($data);
        
        $result = $wpdb->update(
            $this->table_name,
            $discount_data,
            ['id' => $discount_id],
            $this->get_format_for_data($discount_data),
            ['%d']
        );
        
        if ($result !== false) {
            $this->logger->log('تخفیف با موفقیت به‌روزرسانی شد: ' . $discount_data['name']);
            add_settings_error('ai_assistant_discounts', 'discount_updated', 'تخفیف با موفقیت به‌روزرسانی شد.', 'success');
        } else {
            $this->logger->log('خطا در به‌روزرسانی تخفیف', 'error');
            add_settings_error('ai_assistant_discounts', 'discount_error', 'خطا در به‌روزرسانی تخفیف.', 'error');
        }
    }
    
    /**
     * حذف تخفیف
     */
    public function delete_discount($discount_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $discount_id],
            ['%d']
        );
        
        return $result !== false;
    }
        
    
    /**
     * پاکسازی داده‌های تخفیف
     */
    private function sanitize_discount_data($data) {
        $sanitized = [
            'name' => sanitize_text_field($data['discount_name']),
            'discount_type' => sanitize_text_field($data['discount_type']),
            'discount_value' => floatval($data['discount_value']),
            'scope' => sanitize_text_field($data['scope']),
            'start_date' => !empty($data['start_date']) ? sanitize_text_field($data['start_date']) : null,
            'end_date' => !empty($data['end_date']) ? sanitize_text_field($data['end_date']) : null,
            'usage_limit' => intval($data['usage_limit']),
            'min_order_amount' => floatval($data['min_order_amount']),
            'active' => isset($data['active']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        ];
        
        // پردازش فیلدهای خاص بر اساس دامنه
        switch ($sanitized['scope']) {
            case 'service':
                if (!empty($data['service_ids'])) {
                    $service_ids = $this->normalize_service_ids_array($data['service_ids']);
                    // ذخیره با کامای آغاز و پایان برای راحتی جستجو
                    $sanitized['service_ids'] = !empty($service_ids) ? ',' . implode(',', $service_ids) . ',' : '';
                } else {
                    $sanitized['service_ids'] = '';
                }
                $sanitized['coupon_code'] = null;
                $sanitized['user_restriction'] = null;
                $sanitized['user_ids'] = null;
                break;
            
            case 'coupon':
                $sanitized['coupon_code'] = sanitize_text_field($data['coupon_code']);
                if (!empty($data['service_ids'])) {
                    $service_ids = $this->normalize_service_ids_array($data['service_ids']);
                    $sanitized['service_ids'] = !empty($service_ids) ? ',' . implode(',', $service_ids) . ',' : '';
                } else {
                    $sanitized['service_ids'] = '';
                }
                $sanitized['user_restriction'] = null;
                $sanitized['user_ids'] = null;
                break;
            
            case 'user_based':
                $sanitized['user_restriction'] = sanitize_text_field($data['user_restriction']);
                if ($sanitized['user_restriction'] == 'specific_users' && !empty($data['user_ids'])) {
                    // برای user_ids معمولاً از intval استفاده می‌کنیم (اگر numeric هستند)
                    $user_ids = array_map('intval', (array)$data['user_ids']);
                    $user_ids = array_values(array_unique(array_filter($user_ids)));
                    $sanitized['user_ids'] = !empty($user_ids) ? implode(',', $user_ids) : '';
                } else {
                    $sanitized['user_ids'] = null;
                }
                if (!empty($data['service_ids'])) {
                    $service_ids = $this->normalize_service_ids_array($data['service_ids']);
                    $sanitized['service_ids'] = !empty($service_ids) ? ',' . implode(',', $service_ids) . ',' : '';
                } else {
                    $sanitized['service_ids'] = '';
                }
                $sanitized['coupon_code'] = null;
                break;
        }
        
        return $sanitized;
    }
    
    /**
     * دریافت فرمت داده‌ها برای درج در دیتابیس
     */
    private function get_format_for_data($data) {
        $formats = [
            'name' => '%s',
            'discount_type' => '%s',
            'discount_value' => '%f',
            'scope' => '%s',
            'service_ids' => '%s',
            'coupon_code' => '%s',
            'start_date' => '%s',
            'end_date' => '%s',
            'usage_limit' => '%d',
            'usage_count' => '%d',
            'user_restriction' => '%s',
            'user_ids' => '%s',
            'min_order_amount' => '%f',
            'active' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s'
        ];
        
        $result = [];
        foreach ($data as $key => $value) {
            if (isset($formats[$key])) {
                $result[] = $formats[$key];
            }
        }
        
        return $result;
    }
    
    /**
     * دریافت تمام تخفیف‌ها
     */
    public function get_all_discounts() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
    }
    
    /**
     * دریافت یک تخفیف خاص
     */
    public function get_discount($discount_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $discount_id));
    }
    
    /**
     * دریافت برچسب دامنه تخفیف
     */
    private function get_scope_label($scope, $discount) {
        $labels = [
            'global' => 'عمومی',
            'service' => 'سرویس خاص',
            'coupon' => 'کد تخفیف: ' . $discount->coupon_code,
            'user_based' => 'کاربرمحور'
        ];
        
        return isset($labels[$scope]) ? $labels[$scope] : $scope;
    }
    
    /**
     * محاسبه تخفیف برای یک سرویس
     */
    public function calculate_discount($service_id, $original_price, $user_id , $coupon_code ) {
        $discounts = $this->get_applicable_discounts($service_id, $user_id, $coupon_code);
        $best_discount = ['amount' => 0, 'type' => '', 'id' => 0];
        
        foreach ($discounts as $discount) {
            $discount_amount = 0;
            
            if ($discount->discount_type == 'percentage') {
                $discount_amount = $original_price * ($discount->discount_value / 100);
            } else {
                $discount_amount = $discount->discount_value;
            }
            
            // اطمینان از اینکه تخفیف بیشتر از قیمت اصلی نباشد
            $discount_amount = min($discount_amount, $original_price);
            
            if ($discount_amount > $best_discount['amount']) {
                $best_discount = [
                    'amount' => $discount_amount,
                    'type' => $discount->discount_type,
                    'id' => $discount->id,
                    'name' => $discount->name
                ];
            }
        }
        
        return $best_discount;
    }
    
    /**
     * دریافت تخفیف‌های قابل اعمال
     */
    private function get_applicable_discounts($service_id, $user_id , $coupon_code ) {
        global $wpdb;
        $now = current_time('mysql');
        
        // شرط‌های پایه
        $conditions = [
            "active = 1",
            "(start_date IS NULL OR start_date <= '{$now}')",
            "(end_date IS NULL OR end_date >= '{$now}')",
            "(usage_limit = 0 OR usage_count < usage_limit)"
        ];
        
        // شرط‌های اضافی بر اساس نوع تخفیف
        $or_conditions = [];
        
        // تخفیف‌های عمومی
        $or_conditions[] = "scope = 'global'";
        
        // تخفیف‌های سرویس خاص
        $or_conditions[] = $wpdb->prepare("(scope = 'service' AND (service_ids = '' OR service_ids LIKE %s OR service_ids LIKE %s OR service_ids LIKE %s OR service_ids = %s))", 
            "%,{$service_id},%", 
            "{$service_id},%", 
            "%,{$service_id}", 
            "{$service_id}"
        );
        
        // تخفیف‌های کد تخفیف
        if (!empty($coupon_code)) {
            $or_conditions[] = $wpdb->prepare("(scope = 'coupon' AND coupon_code = %s AND (service_ids = '' OR service_ids LIKE %s OR service_ids LIKE %s OR service_ids LIKE %s OR service_ids = %s))", 
                $coupon_code,
                "%,{$service_id},%", 
                "{$service_id},%", 
                "%,{$service_id}", 
                "{$service_id}"
            );
        }
        
        // تخفیف‌های کاربرمحور
        if ($user_id) {
            $user_conditions = [
                $wpdb->prepare("(scope = 'user_based' AND user_restriction = 'first_time' AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}assistant_orders WHERE user_id = %d AND service_id = %d))", $user_id, $service_id)
            ];
            
            if (!empty($user_id)) {
                $user_conditions[] = $wpdb->prepare("(scope = 'user_based' AND user_restriction = 'specific_users' AND (user_ids LIKE %s OR user_ids LIKE %s OR user_ids LIKE %s OR user_ids = %s))", 
                    "%,{$user_id},%", 
                    "{$user_id},%", 
                    "%,{$user_id}", 
                    "{$user_id}"
                );
            }
            
            $or_conditions[] = "(" . implode(" OR ", $user_conditions) . ")";
        }
        
        $conditions[] = "(" . implode(" OR ", $or_conditions) . ")";
        
        $query = "SELECT * FROM {$this->table_name} WHERE " . implode(" AND ", $conditions);
        return $wpdb->get_results($query);
    }
    
    /**
     * افزایش تعداد استفاده از تخفیف
     */
    public function increment_usage($discount_id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} SET usage_count = usage_count + 1, updated_at = %s WHERE id = %d",
            current_time('mysql'),
            $discount_id
        ));
    }
}