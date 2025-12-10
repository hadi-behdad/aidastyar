/**
 * Terms Acceptance Handler
 */
(function($) {
    'use strict';
    
    console.log('🟢 terms-acceptance.js بارگذاری شد');
    
    const TermsAcceptance = {
        
        acceptanceId: null,
        
        init: function() {
            console.log('🟢 TermsAcceptance.init() اجرا شد');
            this.bindEvents();
        },
        
        bindEvents: function() {
            // گوش دادن به همه دکمه‌های next
            $(document).on('click', '.next-step', this.handleNextClick.bind(this));
            
            // گوش دادن به چک‌باکس
            $(document).on('change', '#agree-terms', this.handleTermsCheckbox.bind(this));
        },
        
        handleTermsCheckbox: function(e) {
            const isChecked = $(e.target).is(':checked');
            console.log('🔵 چک‌باکس agree-terms تغییر کرد:', isChecked);
        },
        
        handleNextClick: function(e) {
            // شناسایی مرحله فعلی
            const $activeStep = $('.step.active');
            const currentStepId = $activeStep.attr('id');
            
            console.log('🔵 کلیک روی next-step');
            console.log('🔵 مرحله فعلی:', currentStepId);
            
            // بررسی اینکه آیا در مرحله terms-agreement هستیم
            if (currentStepId === 'terms-agreement-step') {
                console.log('🟡 در مرحله terms-agreement-step هستیم!');
                
                const $termsCheckbox = $('#agree-terms');
                const isChecked = $termsCheckbox.is(':checked');
                
                console.log('🔵 وضعیت چک‌باکس agree-terms:', isChecked);
                
                if (isChecked) {
                    console.log('✅ چک‌باکس تیک خورده - شروع ذخیره تأییدیه...');
                    this.saveAcceptance();
                } else {
                    console.log('⚠️ چک‌باکس تیک نخورده - نیازی به ذخیره نیست');
                }
            }
        },
        
        saveAcceptance: function() {
            console.log('🔵 درخواست AJAX ارسال می‌شود');
            
            jQuery.ajax({
                url: aidastyarTerms.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_terms_acceptance',
                    service_id: 'diet',
                    nonce: aidastyarTerms.nonce
                },
                success: function(response) {
                    console.log('✅ پاسخ دریافت شد:', response);
                    
                    if (response.success) {
                        console.log('✅ موفق - مرحله بعد');
                        // کلیک روی دکمه next
                        document.querySelector('.next-step')?.click();
                    } else {
                        console.error('❌ خطا:', response.data.message);
                        alert('خطا: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ خطای AJAX:', xhr.status, error);
                    console.log('Response:', xhr.responseText);
                    
                    // ✅ نمایش جزئیات خطا
                    if (xhr.status === 401) {
                        alert('لطفاً ابتدا وارد شوید');
                    } else if (xhr.status === 403) {
                        alert('نشست نامعتبر است. صفحه را تازه‌سازی کنید');
                    } else {
                        alert('خطای ارتباطی: ' + xhr.status);
                    }
                }
            });
        }
    };
    
    // راه‌اندازی
    $(document).ready(function() {
        console.log('📄 DOM آماده شد');
        TermsAcceptance.init();
    });
    
    window.TermsAcceptance = TermsAcceptance;
    
})(jQuery);
