
<?php
/**
 * مدیریت تاریخچه کیف پول
 */
class AI_Assistant_Wallet_History_Manager {
    private static $instance;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_wallet_history_cpt']);
        add_action('admin_init', [$this, 'handle_history_deletion']);
    }

    /**
     * ثبت نوع پست سفارشی برای تاریخچه کیف پول
     */
    public function register_wallet_history_cpt() {
        $args = [
            'labels' => [
                'name' => 'تاریخچه کیف پول',
                'singular_name' => 'تراکنش کیف پول',
            ],
            'public' => false,
            'show_ui' => true,
            'publicly_queryable' => true,
            'show_in_nav_menus' => false,
            'exclude_from_search' => true,
            'has_archive' => false,
            'rewrite' => [
                'slug' => 'wallet-transaction',
                'with_front' => false 
            ],
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
            'query_var' => true 
        ];
    
        register_post_type('ai_wallet_history', $args);
        flush_rewrite_rules();
    }

    /**
     * ذخیره یک تراکنش در تاریخچه کیف پول
     * @param int $user_id آی دی کاربر
     * @param float $amount مبلغ تراکنش
     * @param float $balance موجودی جدید پس از تراکنش
     * @param string $type نوع تراکنش (credit/debit)
     * @param string $description توضیحات تراکنش
     * @return int|false آی دی پست یا false در صورت خطا
     */
    public function save_wallet_history($user_id, $amount, $balance, $type, $description ) {
        // اعتبارسنجی اولیه
        if (!get_user_by('ID', $user_id)) {
            error_log('[Wallet History] Invalid user ID: ' . $user_id);
            return false;
        }

        $type = ($type === 'credit') ? 'credit' : 'debit';
        $title = ($type === 'credit') 
            ? sprintf('واریز %s تومان', number_format($amount))
            : sprintf('برداشت %s تومان', number_format($amount));

        $post_data = [
            'post_type'    => 'ai_wallet_history',
            'post_title'   => $title,
            'post_content' => wp_kses_post($description),
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            error_log('[Wallet History] WP_Error: ' . $post_id->get_error_message());
            return false;
        }

        // ذخیره متادیتا
        update_post_meta($post_id, 'wallet_amount', $amount);
        update_post_meta($post_id, 'wallet_balance', $balance);
        update_post_meta($post_id, 'wallet_type', $type);
        update_post_meta($post_id, 'wallet_description', $description);
        
        return $post_id;
    }

    /**
     * دریافت تاریخچه کیف پول کاربر با قابلیت صفحه‌بندی
     * @param int $user_id آی دی کاربر
     * @param int $per_page تعداد آیتم در هر صفحه
     * @return array لیست پست‌ها با متادیتا
     */
    public function get_user_wallet_history($user_id, $per_page = 10) {
        $paged = get_query_var('paged') ?: 1;

        $query = new WP_Query([
            'post_type' => 'ai_wallet_history',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'author' => absint($user_id),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $history = [];
        foreach ($query->posts as $post) {
            $history[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => $post->post_date,
                'amount' => get_post_meta($post->ID, 'wallet_amount', true),
                'balance' => get_post_meta($post->ID, 'wallet_balance', true),
                'type' => get_post_meta($post->ID, 'wallet_type', true),
                'description' => get_post_meta($post->ID, 'wallet_description', true),
            ];
        }

        return [
            'items' => $history,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ];
    }

    /**
     * مدیریت درخواست‌های حذف
     */
    public function handle_history_deletion() {
        if (!isset($_GET['delete_wallet_history'], $_GET['_wpnonce'])) return;

        $post_id = absint($_GET['delete_wallet_history']);
        $user_id = get_current_user_id();

        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_wallet_history_' . $post_id)) {
            $this->delete_wallet_history_item($post_id, $user_id);
            wp_redirect(remove_query_arg(['delete_wallet_history', '_wpnonce']));
            exit;
        }
    }

    /**
     * حذف یک آیتم از تاریخچه کیف پول
     * @param int $post_id آی دی پست
     * @param int $user_id آی دی کاربر
     * @return bool نتیجه عملیات
     */
    public function delete_wallet_history_item($post_id, $user_id) {
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
        return $post && $post->post_type === 'ai_wallet_history' && $post->post_author == $user_id;
    }
}

// راه‌اندازی کلاس
AI_Assistant_Wallet_History_Manager::get_instance();