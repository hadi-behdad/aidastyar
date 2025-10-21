// /home/aidastya/public_html/wp-content/themes/ai-assistant/assets/js/main.js
/**
 * AI Assistant Main JavaScript File
 */

jQuery(document).ready(function($) {
    // 1. مدیریت کلیک روی کارت‌های سرویس
    $(document).on('click', '.ai-service-card', function(e) {
        if (!$(e.target).closest('a').length) {
            window.location = $(this).find('a').attr('href');
        }
    });

    // 2. نمایش نوتیفیکیشن
    window.showAINotice = function(message, type = 'success') {
        const notice = $(`
            <div class="ai-notice ai-notice-${type}">
                ${message}
                <span class="ai-notice-close">&times;</span>
            </div>
        `);
        
        $('.ai-container').prepend(notice);
        
        // بستن دستی نوتیفیکیشن
        notice.find('.ai-notice-close').on('click', function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // بستن خودکار پس از 5 ثانیه
        setTimeout(() => {
            notice.fadeOut(500, () => notice.remove());
        }, 5000);
    };

    


    // انتخاب مبلغ از پیش تعیین شده
    $('.ai-amount-preset').click(function() {
        $('.ai-amount-preset').removeClass('active');
        $(this).addClass('active');
        $('#custom_amount').val('');
        $('#charge_amount').val($(this).data('amount'));
    });
    
    // تغییر مبلغ دلخواه
    $('#custom_amount').on('input', function() {
        if ($(this).val() !== '') {
            $('.ai-amount-preset').removeClass('active');
            $('#charge_amount').val($(this).val());
        }
    });
    
    // اعتبارسنجی فرم قبل از ارسال
    $('.ai-charge-form').submit(function(e) {
        var amount = $('#charge_amount').val();
        
        if (!amount || amount < 1000) {
            e.preventDefault();
            alert('لطفا مبلغ معتبری وارد کنید (حداقل ۱,۰۰۰ تومان)');
            return false;
        }
    });
    
    
    
    // 4. لاگ وضعیت اولیه
    newConsole.log('AI Assistant: Main script loaded', aiAssistantVars);
});


window.newConsole = (function() {
    'use strict';
    
    function getCallerInfo() {
        const e = new Error();
        if (!e.stack) return { file: 'unknown', line: '?' };
        
        const stack = e.stack.split('\n');
        if (stack.length < 4) return { file: 'unknown', line: '?' };
        
        const callerLine = stack[3];
        
        // گرفتن نام فایل
        let fileName = 'unknown';
        const filePatterns = [
            /(?:http|https|file):\/\/.*\/([^\/?:]+\.js)/,
            /([^\/\\]+\.js)(?::\d+){0,2}/,
            /@.*\/([^\/]+\.js)/
        ];
        
        for (const pattern of filePatterns) {
            const match = callerLine.match(pattern);
            if (match && match[1]) {
                fileName = match[1];
                break;
            }
        }
        
        // گرفتن شماره خط
        let lineNumber = '?';
        const lineMatch = callerLine.match(/:(\d+):/);
        if (lineMatch) {
            lineNumber = lineMatch[1];
        }
        
        return { file: fileName, line: lineNumber };
    }
    
    function createLogger(type) {
        return function(...args) {
            if (type === 'error' || (typeof siteEnv !== 'undefined' && siteEnv.isSandbox)) {
                const callerInfo = getCallerInfo();
                const consoleMethod = type === 'log' ? console.log : 
                                    type === 'warn' ? console.warn :
                                    type === 'error' ? console.error :
                                    type === 'info' ? console.info : console.debug;
                
                consoleMethod(`[${type.toUpperCase()} - ${callerInfo.file}:${callerInfo.line}]`, ...args);
            }
        };
    }
    
    return {
        log: createLogger('log'),
        warn: createLogger('warn'),
        error: createLogger('error'),
        info: createLogger('info'),
        debug: createLogger('debug')
    };
})();