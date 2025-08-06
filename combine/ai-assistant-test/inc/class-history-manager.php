
<?php
/**
 * مدیریت تاریخچه سرویس‌های هوش مصنوعی
 */
 
 
class AI_Assistant_History_Manager {
    private static $instance;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_history_cpt']);
        add_action('admin_init', [$this, 'handle_history_deletion']);
    }

    /**
     * ثبت نوع پست سفارشی برای تاریخچه
     */
    public function register_history_cpt() {
        $args = [
            'labels' => [
                'name' => 'تاریخچه سرویس‌ها',
                'singular_name' => 'تاریخچه سرویس',
            ],
            'public' => true,
            'show_ui' => true,
            'publicly_queryable' => true,
            'show_in_nav_menus' => false,
            'exclude_from_search' => true,
            'has_archive' => false,
            'rewrite' => [
                'slug' => 'service-output',
                'with_front' => false 
            ],
            'supports' => ['title', 'editor', 'author'],
            'capability_type' => 'post',
            'query_var' => true 
        ];
    
        register_post_type('ai_service_history', $args);
        flush_rewrite_rules();
    }

    /**
     * ذخیره یک آیتم در تاریخچه
     * @param int $user_id آی دی کاربر
     * @param string $service_name نام سرویس
     * @param string $html_output خروجی HTML
     * @return int|false آی دی پست یا false در صورت خطا
     */
    public function save_history($user_id , $service_id , $service_name, $html_output) {
        // اعتبارسنجی اولیه
        if (!get_user_by('ID', $user_id)) {
            error_log('[AI History] Invalid user ID: ' . $user_id);
            return false;
        }
   
        error_log('[AI History] Attempting to save for user: ' . $user_id . ', service: ' . $service_id);
    
        // ذخیره‌سازی با بررسی خطاها
        $post_data = [
            'post_type'    => 'ai_service_history',
            'post_title'   => 'سرویس: ' . sanitize_text_field($service_name),
            'post_id'   => sanitize_text_field($service_id),
            //'post_content' => wp_kses_post($html_output),
            'post_content' => wp_kses($html_output, $this->get_allowed_html_tags()),
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ];
    
        error_log('[AI History] Post data: ' . print_r($post_data, true));
    
        $post_id = wp_insert_post($post_data, true);
    
        if (is_wp_error($post_id)) {
            error_log('[AI History] WP_Error: ' . $post_id->get_error_message());
            return false;
        }
    
        // ذخیره متادیتا
        update_post_meta($post_id, 'service_id', $service_id);
        error_log('[AI History] Saved successfully. Post ID: ' . $post_id);
        
        return $post_id;
    }
    
    
    private function get_allowed_html_tags() {
        return [
            'html' => [],
            'head' => [],
            'body' => ['style' => true],
            'style' => [],
            'div' => ['id' => true, 'class' => true, 'style' => true],
            'span' => ['class' => true, 'style' => true],
            'p' => ['class' => true, 'style' => true],
            'a' => ['href' => true, 'title' => true, 'style' => true],
            'ul' => ['class' => true, 'style' => true],
            'ol' => ['class' => true, 'style' => true],
            'li' => ['class' => true, 'style' => true],
            'img' => ['src' => true, 'alt' => true, 'style' => true, 'width' => true, 'height' => true],
            'br' => [],
            'h1' => ['style' => true], 'h2' => ['style' => true], 'h3' => ['style' => true],
            'table' => ['class' => true, 'style' => true],
            'thead' => ['style' => true],
            'tbody' => ['style' => true],
            'tr' => ['style' => true],
            'td' => ['style' => true, 'colspan' => true, 'rowspan' => true],
            'th' => ['style' => true, 'colspan' => true],
            // می‌توانید تگ‌های بیشتری اضافه کنید
        ];
    }
    
    /**
     * دریافت تاریخچه کاربر با قابلیت صفحه‌بندی
     * @param int $user_id آی دی کاربر
     * @param int $per_page تعداد آیتم در هر صفحه
     * @return array لیست پست‌ها
     */
    public function get_user_history($user_id, $per_page = 10) {
        $paged = get_query_var('paged') ?: 1;

        return get_posts([
            'post_type' => 'ai_service_history',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'author' => absint($user_id),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }
    
    

    /**
     * مدیریت درخواست‌های حذف
     */
    public function handle_history_deletion() {
        if (!isset($_GET['delete_history'], $_GET['_wpnonce'])) return;

        $post_id = absint($_GET['delete_history']);
        $user_id = get_current_user_id();

        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_history_' . $post_id)) {
            $this->delete_history_item($post_id, $user_id);
            wp_redirect(remove_query_arg(['delete_history', '_wpnonce']));
            exit;
        }
    }

    /**
     * حذف یک آیتم از تاریخچه
     * @param int $post_id آی دی پست
     * @param int $user_id آی دی کاربر
     * @return bool نتیجه عملیات
     */
    public function delete_history_item($post_id, $user_id) {
        if (!$this->is_user_owner($post_id, $user_id)) {
            return false;
        }

        return (bool) wp_delete_post($post_id, true);
    }

    /**
     * بررسی مالکیت آیتم
     * @param int $post_id آی دی پست
     * @param int $user_id آی دی کاربر
     * @return bool نتیجه بررسی
     */
    public function is_user_owner($post_id, $user_id) {
        $post = get_post($post_id);
        return $post && $post->post_type === 'ai_service_history' && $post->post_author == $user_id;
    }
}

// راه‌اندازی کلاس
AI_Assistant_History_Manager::get_instance();