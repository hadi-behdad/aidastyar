(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-freediet-form]').forEach(function (wrapper) {
            var form = wrapper.querySelector('[data-fd-form]');
            if (!form) return;

            form.addEventListener('freedietFormSubmit', function (event) {
                var userData = event.detail.selectedValues;

                var loader = new AiDastyarLoader({
                    message: 'در حال پردازش درخواست...',
                    theme: 'light',
                    size: 'medium',
                    position: 'center',
                    closable: false,
                    overlay: true
                });
                loader.show();

                jQuery.ajax({
                    url: aiAssistantVars.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'ai_assistant_process',
                        userData: JSON.stringify(userData),
                        security: aiAssistantVars.nonce,
                        service_id: 'freediet',
                        price: 0
                    },
                    success: function(response) {
                        loader.hide();
                        if (response.success) {
                            var successLoader = new AiDastyarLoader({
                                message: 'نتیجه رژیم غذایی شما حداکثر تا ۱۵ دقیقه دیگر در تاریخچه سرویس‌ها قابل مشاهده است.',
                                theme: 'light',
                                size: 'medium',
                                position: 'center',
                                closable: true,
                                overlay: true,
                                autoHide: 8000,
                                redirectOnClose: window.location.origin + '/page-user-history/'
                            });
                            successLoader.show();
                        } else {
                            // در اینجا نیز یک لودر جدید می‌سازیم (به جای setRedirectOnClose)
                            var errorLoader = new AiDastyarLoader({
                                message: response.data?.message || 'خطا در پردازش درخواست. لطفاً مجدداً تلاش کنید.',
                                theme: 'light',
                                size: 'medium',
                                position: 'center',
                                closable: true,
                                overlay: true,
                                autoHide: 5000,
                                redirectOnClose: window.location.href
                            });
                            errorLoader.show();
                        }
                    },
                    error: function(xhr) {
                        loader.hide();
                        var errorMsg = 'خطا در ارتباط با سرور';
                        if (xhr.responseText) {
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                errorMsg = resp.data?.message || errorMsg;
                            } catch(e) {
                                errorMsg = xhr.responseText || errorMsg;
                            }
                        }
                        var errorLoader = new AiDastyarLoader({
                            message: errorMsg,
                            theme: 'light',
                            size: 'medium',
                            position: 'center',
                            closable: true,
                            overlay: true,
                            autoHide: 5000,
                            redirectOnClose: window.location.href
                        });
                        errorLoader.show();
                    }
                });
            });
        });
    });
})();