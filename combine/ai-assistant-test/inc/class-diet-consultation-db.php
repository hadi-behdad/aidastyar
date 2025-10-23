<?php
// /inc/class-diet-consultation-db.php

class AI_Assistant_Diet_Consultation_DB {
    private static $instance;
    private $table_name;
    private $consultants_table;
    private $contracts_table;
    private $commissions_table;
    private $payouts_table;
    private $tables_created = false;

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'diet_consultation_requests';
        $this->consultants_table = $wpdb->prefix . 'diet_consultants';
        $this->contracts_table = $wpdb->prefix . 'consultant_contracts';
        $this->commissions_table = $wpdb->prefix . 'consultant_commissions';
        $this->payouts_table = $wpdb->prefix . 'consultant_payouts';
    }

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ایجاد تمام جداول مورد نیاز
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول درخواست‌های بازبینی (جدول اصلی موجود)
        $sql1 = "CREATE TABLE {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            service_history_id bigint(20) UNSIGNED NOT NULL,
            status enum('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            consultation_price int(11) NOT NULL DEFAULT 0,
            deadline datetime NOT NULL,
            consultant_notes longtext NULL,
            final_diet_data longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at datetime NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY consultant_id (consultant_id),
            KEY service_history_id (service_history_id),
            KEY status (status),
            KEY deadline (deadline)
        ) {$charset_collate};";
        
        // جدول مشاوران
        $sql2 = "CREATE TABLE {$this->consultants_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NULL,
            iban varchar(26) NULL,
            status enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY email (email),
            KEY status (status)
        ) {$charset_collate};";
        
        // جدول قراردادهای مشاوران
        $sql3 = "CREATE TABLE {$this->contracts_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            commission_type enum('percent','fixed') NOT NULL,
            commission_value decimal(10,2) NOT NULL,
            full_payment_hours int NOT NULL DEFAULT 48,
            delay_penalty_factor decimal(4,2) NOT NULL DEFAULT 0.50,
            active_from datetime NOT NULL,
            active_to datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY consultant_id (consultant_id),
            KEY active_from (active_from),
            KEY active_to (active_to)
        ) {$charset_collate};";
        
        // جدول کمیسیون‌های مشاوران
        $sql4 = "CREATE TABLE {$this->commissions_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id bigint(20) UNSIGNED NOT NULL,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            base_amount decimal(10,2) NOT NULL,
            commission_type enum('percent','fixed') NOT NULL,
            commission_value decimal(10,2) NOT NULL,
            approved_at datetime NULL,
            generated_at datetime NOT NULL,
            delay_hours decimal(6,2) NULL,
            penalty_multiplier decimal(6,2) NULL,
            final_commission decimal(10,2) NOT NULL,
            status enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
            payout_id bigint(20) UNSIGNED NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY request_id (request_id),
            KEY consultant_id (consultant_id),
            KEY payout_id (payout_id),
            KEY status (status),
            KEY approved_at (approved_at)
        ) {$charset_collate};";
        
        // جدول پرداخت‌ها
        $sql5 = "CREATE TABLE {$this->payouts_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(12,2) NOT NULL,
            period_start date NOT NULL,
            period_end date NOT NULL,
            payment_method enum('manual','api','bank_transfer') NOT NULL DEFAULT 'manual',
            reference_code varchar(100) NULL,
            status enum('pending','done','failed') NOT NULL DEFAULT 'pending',
            paid_at datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY consultant_id (consultant_id),
            KEY period_start (period_start),
            KEY period_end (period_end),
            KEY status (status)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $results = [];
        $results[] = dbDelta($sql1);
        $results[] = dbDelta($sql2);
        $results[] = dbDelta($sql3);
        $results[] = dbDelta($sql4);
        $results[] = dbDelta($sql5);
        
        // لاگ برای اشکال‌زدایی
        error_log('[Diet Consultation] Tables creation results: ' . print_r($results, true));
        
        return $results;
    }

    /**
     * بررسی وجود جداول و ایجاد در صورت عدم وجود
     */
    private function ensure_tables_exist() {
        if ($this->tables_created) {
            return true;
        }

        global $wpdb;
        
        $tables = [
            $this->table_name,
            $this->consultants_table,
            $this->contracts_table,
            $this->commissions_table,
            $this->payouts_table
        ];
        
        $all_tables_exist = true;
        
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
            if (!$table_exists) {
                $all_tables_exist = false;
                break;
            }
        }
        
        if (!$all_tables_exist) {
            $this->create_tables();
            
            // بررسی مجدد
            $all_tables_exist = true;
            foreach ($tables as $table) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
                if (!$table_exists) {
                    $all_tables_exist = false;
                    error_log('[Diet Consultation] Failed to create table: ' . $table);
                    break;
                }
            }
        }
        
        $this->tables_created = $all_tables_exist;
        return $all_tables_exist;
    }

    // متدهای موجود برای جدول درخواست‌ها (همانند قبل)
    public function add_consultation_request($data) {
        if (!$this->ensure_tables_exist()) {
            error_log('[Diet Consultation] Tables do not exist');
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'consultant_id' => 0,
            'service_history_id' => 0,
            'consultation_price' => 0,
            'deadline' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // اعتبارسنجی داده‌های ضروری
        if (!$data['user_id'] || !$data['consultant_id'] || !$data['service_history_id']) {
            error_log('[Diet Consultation] Missing required fields: ' . print_r($data, true));
            return false;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            ['%d', '%d', '%d', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    // سایر متدهای موجود برای جدول درخواست‌ها (همانند قبل)
    public function update_consultation_request($request_id, $data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        if (isset($data['status']) && in_array($data['status'], ['approved', 'rejected'])) {
            $data['reviewed_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $request_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Update failed: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }

    public function get_consultation_request($request_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $request_id)
        );
    }

    public function get_user_consultation_requests($user_id) {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM {$this->table_name} 
                WHERE user_id = %d 
                ORDER BY created_at DESC
            ", $user_id)
        );
    }

    public function get_consultant_requests($consultant_id, $status = '') {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name} WHERE consultant_id = %d";
        $params = [$consultant_id];
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY 
            CASE 
                WHEN status = 'pending' THEN 1
                WHEN status = 'under_review' THEN 2
                WHEN status = 'approved' THEN 3
                WHEN status = 'rejected' THEN 4
                ELSE 5
            END,
            deadline ASC,
            created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    public function get_request_by_history_id($service_history_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM {$this->table_name} 
                WHERE service_history_id = %d
            ", $service_history_id)
        );
    }

    public function get_consultant_request_counts($consultant_id) {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare("
                SELECT status, COUNT(*) as count 
                FROM {$this->table_name} 
                WHERE consultant_id = %d 
                GROUP BY status
            ", $consultant_id),
            ARRAY_A
        );
        
        $counts = [
            'pending' => 0,
            'under_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
            $counts['total'] += (int)$row['count'];
        }
        
        return $counts;
    }

    // ========== متدهای جدید برای مدیریت مشاوران ==========

    /**
     * افزودن مشاور جدید
     */
    public function add_consultant($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'name' => '',
            'email' => '',
            'phone' => '',
            'iban' => '',
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['user_id'] || !$data['name'] || !$data['email']) {
            error_log('[Diet Consultation] Missing required consultant fields');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->consultants_table,
            $data,
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Consultant insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * به‌روزرسانی اطلاعات مشاور
     */
    public function update_consultant($consultant_id, $data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $result = $wpdb->update(
            $this->consultants_table,
            $data,
            ['id' => $consultant_id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Consultant update failed: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }

    /**
     * دریافت اطلاعات مشاور
     */
    public function get_consultant($consultant_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->consultants_table} WHERE id = %d", $consultant_id)
        );
    }

    /**
     * دریافت مشاور بر اساس user_id
     */
    public function get_consultant_by_user_id($user_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->consultants_table} WHERE user_id = %d", $user_id)
        );
    }

    /**
     * دریافت لیست تمام مشاوران
     */
    public function get_consultants($status = '') {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $query = "SELECT * FROM {$this->consultants_table}";
        $params = [];
        
        if (!empty($status)) {
            $query .= " WHERE status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY name ASC";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            return $wpdb->get_results($query);
        }
    }

    // ========== متدهای جدید برای مدیریت قراردادها ==========

    /**
     * افزودن قرارداد جدید
     */
    public function add_contract($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'consultant_id' => 0,
            'commission_type' => 'percent',
            'commission_value' => 0,
            'full_payment_hours' => 48,
            'delay_penalty_factor' => 0.50,
            'active_from' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['consultant_id']) {
            error_log('[Diet Consultation] Missing consultant_id for contract');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->contracts_table,
            $data,
            ['%d', '%s', '%f', '%d', '%f', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Contract insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * دریافت قرارداد فعال یک مشاور
     */
    public function get_active_contract($consultant_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        $now = current_time('mysql');
        
        return $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM {$this->contracts_table} 
                WHERE consultant_id = %d 
                AND active_from <= %s 
                AND (active_to IS NULL OR active_to >= %s)
                ORDER BY active_from DESC 
                LIMIT 1
            ", $consultant_id, $now, $now)
        );
    }

    // ========== متدهای جدید برای مدیریت کمیسیون‌ها ==========

    /**
     * افزودن رکورد کمیسیون
     */
    public function add_commission($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'request_id' => 0,
            'consultant_id' => 0,
            'base_amount' => 0,
            'commission_type' => 'percent',
            'commission_value' => 0,
            'generated_at' => current_time('mysql'),
            'final_commission' => 0,
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['request_id'] || !$data['consultant_id']) {
            error_log('[Diet Consultation] Missing required commission fields');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->commissions_table,
            $data,
            ['%d', '%d', '%f', '%s', '%f', '%s', '%s', '%f', '%f', '%f', '%s', '%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Commission insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * دریافت کمیسیون‌های یک مشاور
     */
    public function get_consultant_commissions($consultant_id, $status = '') {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $query = "
            SELECT c.*, r.service_history_id 
            FROM {$this->commissions_table} c 
            LEFT JOIN {$this->table_name} r ON c.request_id = r.id 
            WHERE c.consultant_id = %d
        ";
        $params = [$consultant_id];
        
        if (!empty($status)) {
            $query .= " AND c.status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY c.created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    // ========== متدهای جدید برای مدیریت پرداخت‌ها ==========

    /**
     * افزودن رکورد پرداخت
     */
    public function add_payout($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'consultant_id' => 0,
            'amount' => 0,
            'period_start' => '',
            'period_end' => '',
            'payment_method' => 'manual',
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['consultant_id'] || !$data['period_start'] || !$data['period_end']) {
            error_log('[Diet Consultation] Missing required payout fields');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->payouts_table,
            $data,
            ['%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Payout insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * دریافت پرداخت‌های یک مشاور
     */
    public function get_consultant_payouts($consultant_id, $status = '') {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $query = "SELECT * FROM {$this->payouts_table} WHERE consultant_id = %d";
        $params = [$consultant_id];
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * به‌روزرسانی وضعیت پرداخت
     */
    public function update_payout_status($payout_id, $status, $reference_code = '', $paid_at = null) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $data = ['status' => $status];
        
        if (!empty($reference_code)) {
            $data['reference_code'] = $reference_code;
        }
        
        if ($status === 'done' && empty($paid_at)) {
            $data['paid_at'] = current_time('mysql');
        } elseif ($paid_at) {
            $data['paid_at'] = $paid_at;
        }
        
        $result = $wpdb->update(
            $this->payouts_table,
            $data,
            ['id' => $payout_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Payout update failed: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }
}