// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/modules/otp/otp-assets/otp.js
jQuery(document).ready(function($) {

    $('#otp-request-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var mobile = $('#mobile').val().trim();
        var referralCode = $('#referral-code').val().trim(); // ✅ دریافت کد معرف        
        
        // اعتبارسنجی اولیه
        if(!mobile) {
            showMessage('لطفاً شماره موبایل را وارد کنید', 'error');
            return;
        }
        
        if(!/^09\d{9}$/.test(mobile)) {
            showMessage('شماره موبایل معتبر نیست (09123456789)', 'error');
            return;
        }
        
        // نمایش وضعیت بارگذاری
        toggleFormLoading($form, true);
        
        $.ajax({
            url: otp_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'send_otp',
                mobile: mobile,
                referral_code: referralCode // ✅ ارسال کد معرف
            },
            success: function(response) {
                if(response.success) {
                    handleOtpSuccess(response, mobile);
                } else {
                    showMessage(response.data || 'خطا در ارسال کد', 'error');
                }
            },
            error: function(xhr) {
                var errorMsg = 'خطا در ارتباط با سرور';
                
                // تحلیل دقیق‌تر خطا
                if(xhr.status === 0) {
                    errorMsg = 'اتصال به اینترنت قطع است';
                } else if(xhr.status === 500) {
                    errorMsg = 'خطای سرور داخلی';
                } else if(xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                }
                
                showMessage(errorMsg, 'error');
                console.error('Error details:', xhr);
            },
            complete: function() {
                toggleFormLoading($form, false);
            }
        });
    });
    
    function toggleFormLoading($form, isLoading) {
        $form.find('.btn-text').text(isLoading ? 'در حال ارسال...' : 'دریافت کد تایید');
        $form.find('.btn-loader').toggle(isLoading);
        $form.find('button').prop('disabled', isLoading);
    }
    
    function handleOtpSuccess(response, mobile) {
        $('#step1').hide();
        $('#step2').show();
        $('#verify-mobile').val(mobile);
        $('#mobile-display').text(mobile);
        
        // 🎯 شروع Web OTP API
        initializeWebOTP();
        
        startCountdown(120);
        
        if((otp_vars.is_sandbox || otp_vars.is_bypass) && response.data && response.data.debug_code) {
            const debugCode = response.data.debug_code;
            $('#otp-code').val(debugCode);
            showMessage(`کد آزمایشی: ${debugCode}`, 'success');
        } else {
            showMessage(response.data?.message || 'کد تایید ارسال شد', 'success');
        }
    }
    
    // تایید OTP - نسخه اصلاح‌شده
    $('#otp-verify-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var otp_code = $('#otp-code').val().trim();
        var mobile = $('#verify-mobile').val();
        var referralCode = $('#referral-code').val().trim(); // ✅ دریافت کد معرف
        
        if(!otp_code || otp_code.length !== 5) {
            showMessage('لطفا کد تایید ۵ رقمی را وارد کنید', 'error');
            return;
        }
        
        $form.find('.btn-text').text('در حال بررسی...');
        $form.find('.btn-loader').show();
        $form.find('button').prop('disabled', true);
        $('#otp-code').prop('disabled', true); // ✅ غیر فعال کردن فیلد
        $('#message').hide();
        
        $.ajax({
            url: otp_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'verify_otp',
                mobile: mobile,
                otp_code: otp_code,
                referral_code: referralCode // ✅ ارسال کد معرف
                
            },
            success: function(response) {
                // در قسمت موفقیت‌آمیز بودن ورود OTP
                if(response.success) {
                    showMessage('ورود موفقیت‌آمیز! در حال انتقال...', 'success');
                    
                    // ✅ اضافه کردن flag برای جلوگیری از فعال کردن دوباره
                    $form.data('success', true);
                    
                    // بررسی وجود redirect_url در sessionStorage
                    let redirectUrl = sessionStorage.getItem('diet_form_redirect_url') || otp_vars.home_url;
                    
                    // اگر redirectUrl برابر با home_url است، پارامتر logged_in را اضافه نکن
                    if (redirectUrl === otp_vars.home_url) {
                        // فقط به صفحه اصلی بدون پارامتر ریدایرکت شود
                        redirectUrl = otp_vars.home_url;
                    } else {
                        // حذف پارامترهای موجود از URL و اضافه کردن logged_in
                        redirectUrl = redirectUrl.split('?')[0];
                        redirectUrl += (redirectUrl.includes('?') ? '&' : '?') + 'logged_in=1';
                    }
                    
                    // اضافه کردن هش مرحله ذخیره شده
                    const savedStep = sessionStorage.getItem('diet_form_current_step');
                    if (savedStep) {
                        redirectUrl += `#step-${savedStep}`;
                    }
                    
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1500);
                } else {
                    showMessage(response.data || 'کد تایید نادرست است', 'error');
                }
            },
            error: function(xhr) {
                var errorMsg = 'خطا در ارتباط با سرور';
                if(xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                }
                showMessage(errorMsg, 'error');
            },
            complete: function() {
                // ✅ فقط اگر موفق نبود، دکمه را دوباره فعال کن
                if (!$form.data('success')) {
                    $form.find('.btn-text').text('تایید و ورود');
                    $form.find('.btn-loader').hide();
                    $form.find('button').prop('disabled', false);
                    $('#otp-code').prop('disabled', false); // ✅ فیلد را فعال کن
                }
            }
        });
    });
    // نمایش پیام
    function showMessage(text, type) {
        var $message = $('#message');
        $message.css('font-family', 'Vazir, IRANSans, sans-serif');
        
        // پاکسازی متن برای جلوگیری از XSS
        text = $('<div/>').text(text).html();
        
        $message.html(text)
            .removeClass('success error')
            .addClass(type)
            .fadeIn();
        
        if(type === 'error') {
            setTimeout(function() {
                $message.fadeOut();
            }, 5000);
        }
    }
    
    // تایمر معکوس
    function startCountdown(duration) {
        var timer = duration;
        var $countdown = $('.countdown-text');
        var $circleFill = $('.circle-fill');
        var $resendBtn = $('#resend-otp');
        
        // محاسبه محیط دایره (2πr)
        var circumference = 2 * Math.PI * 15.9155;
        $circleFill.css('stroke-dasharray', circumference);
        
        var countdownInterval = setInterval(function() {
            var minutes = parseInt(timer / 60, 10);
            var seconds = parseInt(timer % 60, 10);
            
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
            
            $countdown.text(minutes + ":" + seconds);
            
            // محاسبه پیشرفت تایمر برای انیمیشن دایره
            var progress = timer / duration;
            $circleFill.css('stroke-dashoffset', circumference * progress);
            
            if (--timer < 0) {
                clearInterval(countdownInterval);
                $('.countdown-timer').hide();
                $resendBtn.fadeIn();
            }
        }, 1000);
        
        $resendBtn.off('click').on('click', function() {
            $('#otp-request-form').trigger('submit');
            $(this).hide();
            $('.countdown-timer').show();
            clearInterval(countdownInterval);
            startCountdown(120);
        });
    }

    
    // 🎯 تابع Web OTP API - نسخه بهبود یافته
    function initializeWebOTP() {
        // بررسی پشتیبانی مرورگر
        if (!navigator.credentials) {
            console.log('ℹ️ Web OTP API پشتیبانی نشده است');
            return;
        }
    
        // درخواست OTP از مرورگر
        navigator.credentials.get({
            otp: { 
                transport: ['sms'] 
            },
            signal: AbortSignal.timeout(10 * 60 * 1000) // ۱۰ دقیقه timeout
        })
        .then(result => {
            // اگر کاربر اجازه داد و کد دریافت شد
            if (result) {
                console.log('✅ Web OTP دریافت شد:', result.code);
                
                // کد را در فیلد بگذار
                $('#otp-code').val(result.code);
                
                // ✅ غیر فعال کردن فیلد OTP
                $('#otp-code').prop('disabled', true);
                
                // ✅ غیر فعال کردن دکمه تایید
                const $submitBtn = $('#otp-verify-form button[type="submit"]');
                $submitBtn.prop('disabled', true);
                
                // نمایش پیام موفقیت
                showMessage(`کد تایید خودکار وارد شد: ${result.code}`, 'success');
                
                // ✅ ارسال خودکار فرم بعد از کمی تاخیر
                setTimeout(() => {
                    console.log('🚀 ارسال خودکار فرم OTP...');
                    $('#otp-verify-form').trigger('submit');
                }, 300);
            }
        })
        .catch(err => {
            // خطاهای معمولی (کاربر reject کرد، timeout، etc)
            console.log('ℹ️ Web OTP خطا یا لغو شد:', err.name);
            // کد ادامه دهد - کاربر می‌تواند دستی وارد کند
        });
    }

    
    $(document).on('click', 'a[href*="action=logout"], .logout-link', function(e) {
        e.preventDefault();
        
        // نمایش وضعیت بارگذاری
        $(this).addClass('logging-out').text('در حال خروج...');
        
        $.post(otp_vars.ajax_url, {
            action: 'force_logout',
            security: otp_vars.nonce
        }).done(function(response) {
            // ریدایرکت اجباری حتی اگر کش شده باشد
            window.location.href = response.data.redirect + '?logout=' + Math.random().toString(36).substring(7);
        }).fail(function() {
            window.location.href = otp_vars.home_url + '?force_logout=1';
        });
    });  
});
