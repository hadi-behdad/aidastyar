<?php
class AI_Assistant_Payment_Handler {
    private static $instance;
    private $table_name;
    private $history_table;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wallet_balance';
        $this->history_table = $wpdb->prefix . 'wallet_history';
        $this->maybe_create_tables();
    }
    
    private function maybe_create_tables() {
        global $wpdb;
        
        // ایجاد جدول موجودی کیف پول
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                user_id bigint(20) UNSIGNED NOT NULL,
                balance decimal(15,2) NOT NULL DEFAULT 0,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id),
                INDEX (updated_at)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // انتقال داده‌های موجود از user_meta به جدول جدید
            $users = get_users([
                'meta_key' => 'ai_assistant_credit',
                'meta_compare' => 'EXISTS'
            ]);
            
            foreach ($users as $user) {
                $credit = get_user_meta($user->ID, 'ai_assistant_credit', true);
                $wpdb->replace($this->table_name, [
                    'user_id' => $user->ID,
                    'balance' => $credit
                ]);
            }
        }
        
        // ایجاد جدول تاریخچه تراکنش‌ها
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->history_table}'") != $this->history_table) {
            $charset_collate = $wpdb->get_charset_collate();
            
            // استفاده از ساختار جایگزین برای enum
            $sql = "CREATE TABLE {$this->history_table} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                amount decimal(15,2) NOT NULL,
                new_balance decimal(15,2) NOT NULL,
                type varchar(20) NOT NULL, -- تغییر از enum به varchar
                description varchar(255) NOT NULL,
                reference_id varchar(100) DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX (user_id),
                INDEX (type),
                INDEX (created_at),
                INDEX (reference_id)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // اضافه کردن محدودیت مقدار برای فیلد type
            $wpdb->query(
                "ALTER TABLE {$this->history_table} 
                 ADD CONSTRAINT chk_type CHECK (type IN ('credit', 'debit'))"
            );
        }
    }
    
    public function get_user_credit($user_id) {
        global $wpdb;
        
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        return $balance !== null ? (float) $balance : 0;
    }
    
    public function has_enough_credit($user_id, $amount) {
        return $this->get_user_credit($user_id) >= $amount;
    }
    
    public function deduct_credit($user_id, $amount, $description, $reference_id = null) {
        global $wpdb;
        
        $current = $this->get_user_credit($user_id);
        $new_balance = $current - $amount;
        
        if ($new_balance < 0) {
            return false;
        }
        
        $wpdb->replace($this->table_name, [
            'user_id' => $user_id,
            'balance' => $new_balance
        ], ['%d', '%f']);
        
        $this->save_wallet_history(
            $user_id,
            $amount,
            $new_balance,
            'debit',
            $description,
            $reference_id
        );
        
        return true;
    }
    
    public function add_credit($user_id, $amount, $description = 'شارژ کیف پول', $reference_id = null) {
        global $wpdb;
        
        $current = $this->get_user_credit($user_id);
        $new_balance = $current + $amount;
        
        $wpdb->replace($this->table_name, [
            'user_id' => $user_id,
            'balance' => $new_balance
        ], ['%d', '%f']);
        
        $this->save_wallet_history(
            $user_id,
            $amount,
            $new_balance,
            'credit',
            $description,
            $reference_id
        );
        
        AI_Assistant_Logger::get_instance()->log('کیف پول با موفقیت شارژ شد', [
            'user_id' => $user_id,
            'amount' => $amount,
            'new_balance' => $new_balance
        ]);
        
        return true;
    }
    
    private function save_wallet_history($user_id, $amount, $new_balance, $type, $description, $reference_id = null) {
        global $wpdb;
        
        $wpdb->insert($this->history_table, [
            'user_id' => $user_id,
            'amount' => $amount,
            'new_balance' => $new_balance,
            'type' => $type,
            'description' => $description,
            'reference_id' => $reference_id
        ], [
            '%d', '%f', '%f', '%s', '%s', '%s'
        ]);
    }
    
    public function get_transaction_history($user_id, $per_page = 10, $page = 1) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->history_table}
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT %d, %d",
            $user_id, $offset, $per_page
        ));
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->history_table} WHERE user_id = %d",
            $user_id
        ));
        
        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ];
    }
    
    public function delete_transaction($transaction_id, $user_id) {
        global $wpdb;
        
        // ابتدا تراکنش را پیدا می‌کنیم
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->history_table} WHERE id = %d AND user_id = %d",
            $transaction_id, $user_id
        ));
        
        if (!$transaction) {
            return false;
        }
        
        // حذف تراکنش
        $deleted = $wpdb->delete($this->history_table, [
            'id' => $transaction_id,
            'user_id' => $user_id
        ], ['%d', '%d']);
        
        return (bool) $deleted;
    }
    
    
    public function create_payment_link($service_id, $price) {
        
        if (!function_exists('WC')) {
            AI_Assistant_Logger::get_instance()->log_error('عملیات ایجاد لینک پرداخت: ووکامرس فعال نیست');
            return false;
        }
        
        $product_id = $this->get_or_create_product($service_id, $price);
        
        if (!$product_id) {
            AI_Assistant_Logger::get_instance()->log_error('ایجاد محصول برای سرویس ناموفق بود', [
                'service_id' => $service_id,
                'price' => $price
            ]);
            return false;
        }   
        
        $link = add_query_arg([
            'add-to-cart' => $product_id,
            'quantity' => 1
        ], wc_get_page_permalink('cart'));
    
        AI_Assistant_Logger::get_instance()->log('لینک پرداخت ایجاد شد', [
            'service_id' => $service_id,
            'price' => $price,
            'product_id' => $product_id,
            'link' => $link
        ]);
    
        return $link;
    }
    
    private function get_or_create_product($service_id, $price) {
       // $service = AI_Assistant_Service_Manager::get_instance()->get_service($service_id);
        
        $service = (class_exists('AI_Assistant_Service_Manager')) 
            ? AI_Assistant_Service_Manager::get_instance()->get_service($service_id) 
            : ['name' => $service_id];
                
        if (!$service) return false;
        
        $product_id = get_option('ai_assistant_product_' . $service_id);
        
        if ($product_id && wc_get_product($product_id)) {
            return $product_id;
        }
        
        $product = new WC_Product_Simple();
    //  $product->set_name($service['name'] . ' (AI Assistant)');
        
        $product_name = is_array($service) ? $service['name'] : $service;
        $product->set_name($product_name . ' (AI Assistant)');
        
        $product->set_regular_price($price);
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product_id = $product->save();
        
        if ($product_id) {
            update_option('ai_assistant_product_' . $service_id, $product_id);
            return $product_id;
        }
        
        return false;
    }
    
    public function create_payment_product_for_wallet($unique_id, $amount) {
        if (!function_exists('WC')) {
            AI_Assistant_Logger::get_instance()->log_error('ایجاد محصول شارژ کیف پول: ووکامرس فعال نیست');
            return false;
        }
    
        $existing_product_id = get_option('wallet_product_' . $unique_id);
        if ($existing_product_id && wc_get_product($existing_product_id)) {
            AI_Assistant_Logger::get_instance()->log('محصول شارژ کیف پول از قبل وجود دارد', [
                'unique_id' => $unique_id,
                'product_id' => $existing_product_id
            ]);
            return $existing_product_id;
        }
    
        $product = new WC_Product_Simple();
        $product->set_name('شارژ کیف پول - ' . $unique_id);
        $product->set_regular_price($amount);
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product_id = $product->save();
    
        if ($product_id) {
            update_option('wallet_product_' . $unique_id, $product_id);
            AI_Assistant_Logger::get_instance()->log('محصول جدید شارژ کیف پول ایجاد شد', [
                'unique_id' => $unique_id,
                'product_id' => $product_id,
                'amount' => $amount
            ]);
            return $product_id;
        }
    
        AI_Assistant_Logger::get_instance()->log_error('ایجاد محصول شارژ کیف پول ناموفق بود', [
            'unique_id' => $unique_id,
            'amount' => $amount
        ]);
        return false;
    }
}