<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/discount-core-functions.php
 */ 
if (!defined('ABSPATH')) exit;

class AI_Assistant_Discount_Manager {
    
    /**
     * اعتبارسنجی و یافتن بهترین تخفیف برای سرویس و کاربر
     */
    public static function find_best_discount($service_id, $user_id, $coupon_code = '') {
        $discount_db = AI_Assistant_Discount_DB::get_instance();
        $all_discounts = $discount_db->get_all_discounts();
        $now = current_time('mysql');
        
        $applicable_discounts = [];
        
        foreach ($all_discounts as $discount) {
            if ($discount->active != 1) continue;
            
            // بررسی تاریخ‌ها
            if ($discount->start_date && $discount->start_date > $now) continue;
            if ($discount->end_date && $discount->end_date < $now) continue;
            
            // بررسی محدودیت استفاده
            if ($discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit) continue;
            
            // بررسی حوزه اعتبار
            if (self::is_discount_applicable($discount, $service_id, $user_id, $coupon_code)) {
                $applicable_discounts[] = $discount;
            }
        }
        
        // یافتن بهترین تخفیف (بیشترین مقدار)
        $best_discount = null;
        foreach ($applicable_discounts as $discount) {
            if (!$best_discount || $discount->amount > $best_discount->amount) {
                $best_discount = $discount;
            }
        }
        
        return $best_discount;
    }
    
    /**
     * بررسی اعتبار تخفیف برای سرویس و کاربر
     */
    private static function is_discount_applicable($discount, $service_id, $user_id, $coupon_code) {
        $discount_db = AI_Assistant_Discount_DB::get_instance();
        
        switch ($discount->scope) {
            case 'global':
                // تخفیف عمومی برای همه سرویس‌ها و کاربران
                error_log("💰 تخفیف عمومی اعمال شد: {$discount->name} - {$discount->amount}");
                return true;
                
            case 'service':
                // تخفیف مخصوص سرویس
                $services = $discount_db->get_discount_services($discount->id);
                $is_applicable = in_array($service_id, $services);
                if ($is_applicable) {
                    error_log("💰 تخفیف سرویس اعمال شد: {$discount->name} - برای سرویس: {$service_id}");
                }
                return $is_applicable;
                
            case 'coupon':
                // تخفیف کد کوپن
                if ($discount->code === $coupon_code) {
                    $services = $discount_db->get_discount_services($discount->id);
                    // اگر سرویس خاصی تعریف نشده یا سرویس مطابقت دارد
                    $is_applicable = empty($services) || in_array($service_id, $services);
                    if ($is_applicable) {
                        error_log("💰 تخفیف کد کوپن اعمال شد: {$discount->name} - کد: {$coupon_code}");
                    }
                    return $is_applicable;
                }
                return false;
                
            case 'user_based':
                // تخفیف مبتنی بر کاربر
                if ($discount->user_restriction === 'specific_users') {
                    $users = $discount_db->get_discount_users($discount->id);
                    $is_applicable = in_array($user_id, $users);
                    if ($is_applicable) {
                        error_log("💰 تخفیف کاربری اعمال شد: {$discount->name} - برای کاربر: {$user_id}");
                    }
                    return $is_applicable;
                }
                // برای first_time نیاز به بررسی تاریخچه خرید کاربر دارد
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * اعتبارسنجی کد تخفیف (برای استفاده در AJAX)
     */
    public static function validate_discount($discount_code, $service_id, $user_id) {
        $discount_db = AI_Assistant_Discount_DB::get_instance();
        $all_discounts = $discount_db->get_all_discounts();
        $now = current_time('mysql');
        
        foreach ($all_discounts as $discount) {
            if ($discount->code === $discount_code && 
                $discount->active == 1 &&
                self::is_discount_valid($discount, $now) &&
                self::is_discount_applicable($discount, $service_id, $user_id, $discount_code)) {
                
                return [
                    'valid' => true,
                    'discount' => $discount,
                    'message' => 'کد تخفیف اعمال شد'
                ];
            }
        }
        
        return ['valid' => false, 'message' => 'کد تخفیف معتبر نیست'];
    }
    
    /**
     * محاسبه قیمت نهایی با اعمال تخفیف
     */
    public static function calculate_final_price($service_id, $user_id, $coupon_code = '') {
        if (!class_exists('AI_Assistant_Service_Manager')) {
            error_log("❌ خطا: کلاس Service Manager موجود نیست");
            return 0;
        }
        
        $service_manager = AI_Assistant_Service_Manager::get_instance();
        $original_price = $service_manager->get_service_price($service_id);
        
        if ($original_price === false) {
            error_log("❌ خطا: قیمت سرویس {$service_id} یافت نشد");
            return 0;
        }
        
        error_log("💰 محاسبه قیمت نهایی - سرویس: {$service_id}, کاربر: {$user_id}, قیمت اصلی: {$original_price}");
        
        // یافتن بهترین تخفیف قابل اعمال
        $best_discount = self::find_best_discount($service_id, $user_id, $coupon_code);
        
        if ($best_discount) {
            $final_price = self::calculate_discounted_price($original_price, $best_discount);
            $discount_amount = $original_price - $final_price;
            
            error_log("✅ تخفیف اعمال شد: {$best_discount->name} - نوع: {$best_discount->type} - مقدار: {$best_discount->amount}");
            error_log("💰 قیمت اصلی: {$original_price} - تخفیف: {$discount_amount} - قیمت نهایی: {$final_price}");
            
            return [
                'original_price' => floatval($original_price),
                'final_price' => floatval($final_price),
                'discount_amount' => floatval($discount_amount),
                'discount' => $best_discount,
                'has_discount' => true
            ];
        }
        
        error_log("ℹ️ هیچ تخفیفی اعمال نشد - قیمت نهایی: {$original_price}");
        return [
            'original_price' => floatval($original_price),
            'final_price' => floatval($original_price),
            'discount_amount' => 0,
            'discount' => null,
            'has_discount' => false
        ];
    }
    
    private static function is_discount_valid($discount, $now) {
        if ($discount->start_date && $discount->start_date > $now) return false;
        if ($discount->end_date && $discount->end_date < $now) return false;
        if ($discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit) return false;
        return true;
    }
        
    public static function calculate_discounted_price($original_price, $discount) {
        error_log("🔢 محاسبه تخفیف - اصلی: {$original_price}, نوع: {$discount->type}, مقدار: {$discount->amount}");
        
        if ($discount->type === 'percentage') {
            $discounted = $original_price - ($original_price * ($discount->amount / 100));
        } else {
            $discounted = max(0, $original_price - $discount->amount);
        }
        
        error_log("🔢 قیمت پس از تخفیف: {$discounted}");
        return $discounted;
    }
}  