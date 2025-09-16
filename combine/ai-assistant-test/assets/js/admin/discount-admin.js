// /assets/js/admin/discount-admin.js

jQuery(document).ready(function($) {
    // تابع برای نمایش/پنهان کردن فیلدهای مرتبط
    function toggleDiscountFields() {
        var scope = $('#scope').val();
        var discountType = $('#discount_type').val();
        
        // پنهان کردن همه بخش‌ها
        $('#coupon_code_row, #user_restriction_row, #service_section, #user_section').hide();
        
        // نمایش فیلدهای بر اساس scope
        if (scope === 'coupon') {
            $('#coupon_code_row').show();
        } else if (scope === 'user_based') {
            $('#user_restriction_row').show();
            var userRestriction = $('#user_restriction').val();
            $('#user_section').toggle(userRestriction === 'specific_users');
        }
        
        // نمایش بخش سرویس‌ها برای scopeهای مربوطه
        if (scope === 'service' || scope === 'coupon' || scope === 'user_based') {
            $('#service_section').show();
        }
        
        // تغییر واحد مقدار تخفیف
        $('.discount-value-suffix').text(discountType === 'percentage' ? '%' : 'تومان');
    }
    
    // تغییرات در selectها
    $('#scope, #discount_type, #user_restriction').change(toggleDiscountFields);
    
    // اجرای اولیه
    toggleDiscountFields();
    
    // اعتبارسنجی فرم
    $('.discount-form').on('submit', function(e) {
        var scope = $('#scope').val();
        var discountValue = $('#discount_value').val();
        
        // اعتبارسنجی مقدار تخفیف
        if (discountValue <= 0) {
            alert('مقدار تخفیف باید بیشتر از صفر باشد.');
            e.preventDefault();
            return false;
        }
        
        // اعتبارسنجی کد تخفیف
        if (scope === 'coupon') {
            var couponCode = $('#coupon_code').val();
            if (!couponCode || couponCode.trim() === '') {
                alert('برای تخفیف نوع "کد تخفیف" باید یک کد وارد کنید.');
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });
});