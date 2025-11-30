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
            console.log('Sending acknowledgment...');
        
            jQuery.ajax({
                url: aidastyarTerms.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_terms_acceptance',
                    service_id: 'diet',
                    nonce: aidastyarTerms.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('✅ Proceeding to next step');
                        document.querySelector('.next-step')?.click();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Connection error: ' + error);
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
