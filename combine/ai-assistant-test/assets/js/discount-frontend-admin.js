jQuery(document).ready(function($) {
    let currentFilters = {
        status: 'all',
        type: 'all',
        search: ''
    };

    // بارگذاری اولیه لیست تخفیف‌ها
    loadDiscounts();

    // مدیریت ایجاد تخفیف جدید
    $('#create-discount-btn').on('click', function() {
        resetDiscountForm();
        $('#discount-modal-title').text('ایجاد کد تخفیف جدید');
        $('#discount-modal').show();
    });

    // بستن modal
    $('.discount-close-modal').on('click', function() {
        $('#discount-modal, #discount-details-modal').hide();
    });

    // تولید کد تخفیف تصادفی
    $('#generate-code').on('click', function() {
        const code = generateRandomCode();
        $('#discount-code').val(code);
    });

    // تغییر حوزه اعتبار
    $('#discount-scope').on('change', function() {
        toggleScopeSections();
    });

    // جستجو
    $('#search-discounts').on('click', function() {
        currentFilters.search = $('#discount-search').val();
        loadDiscounts();
    });

    $('#discount-search').on('keypress', function(e) {
        if (e.which === 13) {
            currentFilters.search = $(this).val();
            loadDiscounts();
        }
    });

    // فیلتر وضعیت
    $('#discount-status-filter').on('change', function() {
        currentFilters.status = $(this).val();
        loadDiscounts();
    });

    // فیلتر نوع
    $('#discount-type-filter').on('change', function() {
        currentFilters.type = $(this).val();
        loadDiscounts();
    });

    // ثبت فرم تخفیف
    $('#discount-form').on('submit', function(e) {
        e.preventDefault();
        saveDiscount();
    });

    // انصراف از ایجاد/ویرایش
    $('#cancel-discount').on('click', function() {
        $('#discount-modal').hide();
    });

    function loadDiscounts() {
        const listElement = $('#discounts-list');
        listElement.html('<div class="discounts-loading">' + discountFrontendAdminVars.i18n.loading + '</div>');

        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_discounts_list',
                ...currentFilters,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    listElement.html(response.data.html);
                    updateStats(response.data.stats);
                    bindDiscountActions();
                } else {
                    listElement.html('<div class="discount-error">خطا در بارگذاری کدهای تخفیف</div>');
                }
            },
            error: function() {
                listElement.html('<div class="discount-error">خطا در ارتباط با سرور</div>');
            }
        });
    }

    function updateStats(stats) {
        $('#active-count').text(stats.active);
        $('#inactive-count').text(stats.inactive);
        $('#total-count').text(stats.total);
    }

    function bindDiscountActions() {
        // مشاهده جزئیات
        $('.discount-view-details').on('click', function() {
            const discountId = $(this).data('discount-id');
            showDiscountDetails(discountId);
        });

        // ویرایش تخفیف
        $('.discount-edit').on('click', function() {
            const discountId = $(this).data('discount-id');
            editDiscount(discountId);
        });

        // تغییر وضعیت
        $('.discount-toggle-status').on('click', function() {
            const discountId = $(this).data('discount-id');
            const action = $(this).find('.fa-play').length ? 'فعال' : 'غیرفعال';
            
            if (!confirm(discountFrontendAdminVars.i18n.confirm_deactivate)) {
                return;
            }
            
            toggleDiscountStatus(discountId);
        });

        // حذف تخفیف
        $('.discount-delete').on('click', function() {
            if (!confirm(discountFrontendAdminVars.i18n.confirm_delete)) {
                return;
            }
            
            const discountId = $(this).data('discount-id');
            deleteDiscount(discountId);
        });

        // کپی کردن کد
        $('.discount-copy-code').on('click', function() {
            const code = $(this).data('code');
            copyToClipboard(code);
        });
    }

    function generateRandomCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    }

    function toggleScopeSections() {
        const scope = $('#discount-scope').val();
        
        // مخفی کردن همه بخش‌ها
        $('#services-section, #user-restriction-section').hide();
        
        // نمایش بخش‌های مربوطه
        if (scope === 'service') {
            $('#services-section').show();
        } else if (scope === 'user_based') {
            $('#user-restriction-section').show();
        }
    }

    function resetDiscountForm() {
        $('#discount-form')[0].reset();
        $('#discount-id').val('');
        $('input[name="services[]"]').prop('checked', false);
        toggleScopeSections();
    }

    function saveDiscount() {
        const formData = new FormData($('#discount-form')[0]);
        formData.append('action', $('#discount-id').val() ? 'update_discount_code' : 'create_discount_code');
        formData.append('nonce', discountFrontendAdminVars.nonce);
    
        const submitBtn = $('#discount-form button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...');
    
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // اصلاح این خط - دسترسی به response.data.message
                    showMessage(response.data.message || 'عملیات با موفقیت انجام شد', 'success');
                    $('#discount-modal').hide();
                    loadDiscounts();
                } else {
                    // اصلاح این خط - دسترسی به response.data
                    showMessage(response.data || discountFrontendAdminVars.i18n.error, 'error');
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    function showDiscountDetails(discountId) {
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_discount_details',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#discount-details-content').html(response.data.html);
                    $('#discount-details-modal').show();
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            }
        });
    }
    
    function editDiscount(discountId) {
        // دریافت اطلاعات تخفیف از سرور
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_discount_details',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce,
                for_edit: true // پرچم برای نشان دادن که برای ویرایش هست
            },
            success: function(response) {
                if (response.success) {
                    fillEditForm(response.data.discount);
                    $('#discount-modal-title').text('ویرایش کد تخفیف');
                    $('#discount-modal').show();
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            }
        });
    }
    
    function fillEditForm(discount) {
        // پر کردن فرم با داده‌های تخفیف
        $('#discount-id').val(discount.id);
        $('#discount-name').val(discount.name);
        $('#discount-code').val(discount.code);
        $('#discount-type').val(discount.type);
        $('#discount-amount').val(discount.amount);
        $('#discount-scope').val(discount.scope);
        $('#discount-usage-limit').val(discount.usage_limit);
        $('#discount-user-restriction').val(discount.user_restriction || '');
        
        // تاریخ‌ها
        if (discount.start_date) {
            $('#discount-start-date').val(discount.start_date.replace(' ', 'T').substr(0, 16));
        }
        if (discount.end_date) {
            $('#discount-end-date').val(discount.end_date.replace(' ', 'T').substr(0, 16));
        }
        
        // سرویس‌های مرتبط
        $('input[name="services[]"]').prop('checked', false);
        if (discount.services && discount.services.length > 0) {
            discount.services.forEach(function(serviceId) {
                $('input[name="services[]"][value="' + serviceId + '"]').prop('checked', true);
            });
        }
        
        // نمایش بخش‌های مربوطه
        toggleScopeSections();
    }

    function toggleDiscountStatus(discountId) {
        const button = $(`.discount-toggle-status[data-discount-id="${discountId}"]`);
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ...');
    
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_discount_status',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    // اصلاح این خط
                    showMessage(response.data.message || 'وضعیت با موفقیت تغییر کرد', 'success');
                    loadDiscounts();
                } else {
                    // اصلاح این خط
                    showMessage(response.data || discountFrontendAdminVars.i18n.error, 'error');
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
                button.prop('disabled', false).html(originalText);
            }
        });
    }

    function deleteDiscount(discountId) {
        const discountItem = $(`.discount-item[data-discount-id="${discountId}"]`);
        
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_discount_code',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    // اصلاح این خط
                    showMessage(response.data.message || 'کد تخفیف حذف شد', 'success');
                    discountItem.fadeOut(300, function() {
                        $(this).remove();
                        loadDiscounts();
                    });
                } else {
                    // اصلاح این خط
                    showMessage(response.data || discountFrontendAdminVars.i18n.error, 'error');
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            }
        });
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            showMessage(discountFrontendAdminVars.i18n.copy_success, 'success');
        }, function() {
            // Fallback برای مرورگرهای قدیمی
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showMessage(discountFrontendAdminVars.i18n.copy_success, 'success');
        });
    }

    function showMessage(message, type) {
        // حذف پیام قبلی اگر وجود دارد
        $('.discount-admin-message').remove();
        
        const messageClass = `discount-admin-message ${type}`;
        const messageHtml = `<div class="${messageClass}">${message}</div>`;
        
        $('.discount-admin-panel-header').after(messageHtml);
        
        // حذف خودکار پیام بعد از 5 ثانیه
        setTimeout(function() {
            $('.discount-admin-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // بستن modal با کلیک خارج از آن
    $(window).on('click', function(event) {
        if ($(event.target).is('.discount-modal')) {
            $('.discount-modal').hide();
        }
    });
});