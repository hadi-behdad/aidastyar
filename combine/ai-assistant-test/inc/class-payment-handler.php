
<?php
class AI_Assistant_Payment_Handler {
    private static $instance;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get_user_credit($user_id) {
        $credit = get_user_meta($user_id, 'ai_assistant_credit', true);
        return $credit ? (float) $credit : 0;
    }
    
    public function has_enough_credit($user_id, $amount) {
        return $this->get_user_credit($user_id) >= $amount;
    }
    
    public function deduct_credit($user_id, $amount, $description ) {
        $current = $this->get_user_credit($user_id);
        $new_credit = $current - $amount;
        
        if ($new_credit < 0) {
            return false;
        }
        
        update_user_meta($user_id, 'ai_assistant_credit', $new_credit);
        
        // ثبت در تاریخچه کیف پول
        AI_Assistant_Wallet_History_Manager::get_instance()->save_wallet_history(
            $user_id,
            $amount,
            $new_credit,
            'debit',
            $description
        );  
        
        return true;
    }
    
    public function add_credit($user_id, $amount) {
        $current = $this->get_user_credit($user_id);
        $new_credit = $current + $amount;
        update_user_meta($user_id, 'ai_assistant_credit', $new_credit);
        
        $description = 'شارژکیف پول';
        // ثبت در تاریخچه کیف پول
        AI_Assistant_Wallet_History_Manager::get_instance()->save_wallet_history(
            $user_id,
            $amount,
            $new_credit,
            'credit',
            $description
        );        
        
        //ثبت لاگ
        AI_Assistant_Logger::get_instance()->log('کیف پول با موفقیت شارژ شد', [
            'user_id' => $user_id,
            'مبلغ اضافه‌شده' => $amount,
            'اعتبار جدید' => $new_credit
        ]);
    
        return true;
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