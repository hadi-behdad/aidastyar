// payment-popup.js - نسخه اصلاح شده
class PaymentPopup {
    constructor(options = {}) {
        this.options = {
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null,
            serviceType: options.serviceType || 'سرویس',
            customPrice: options.customPrice || null,
            ajaxAction: options.ajaxAction,
            serviceId: options.serviceId || '',
            ...options
        };
        
        if (!this.options.serviceId) {
            console.error('PaymentPopup: serviceId is required');
        }        
        this.popup = null;
        this.isOpen = false;
        this.originalPrice = 0;
        this.finalPrice = 0;
        this.discountApplied = false;
        this.hasAutoDiscount = false;
        this.autoDiscount = null;
    }

    async show() {
        if (this.isOpen) return;
        
        try {
            // دریافت قیمت سرویس با اعمال تخفیف‌های خودکار
            const priceData = await this.getServicePriceWithDiscount();
            this.originalPrice = priceData.original_price;
            this.finalPrice = priceData.final_price;
            this.hasAutoDiscount = priceData.has_discount;
            this.autoDiscount = priceData.discount;
            this.priceData = priceData; // ذخیره priceData برای استفاده در createPopupElement
            
            // Create popup
            this.createPopupElement();
            
            // Fetch user balance
            await this.fetchUserBalance(this.finalPrice);
            
            this.isOpen = true;
        } catch (error) {
            console.error('Error showing payment popup:', error);
            alert('خطا در نمایش پرداخت: ' + error.message);
        }
    }

    // اضافه کردن تابع جدید برای دریافت قیمت با تخفیف خودکار
    async getServicePriceWithDiscount() {
        try {
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'get_service_price_with_discount',
                    'service_id': this.options.serviceId,
                    'nonce': this.getNonce()
                })
            });

            const data = await response.json();
            
            if (data.success) {
                console.log('💰 قیمت با تخفیف خودکار:', data.data);
                return data.data;
            } else {
                throw new Error(data.data?.message || 'خطا در دریافت قیمت');
            }
        } catch (error) {
            console.error('Error getting service price with discount:', error);
            // Fallback: دریافت قیمت عادی اگر تابع جدید کار نکرد
            return await this.getServicePriceFallback();
        }
    }

    // تابع fallback برای زمانی که تابع جدید کار نمی‌کند
    async getServicePriceFallback() {
        try {
            const price = this.options.customPrice || await this.getServicePrice();
            return {
                original_price: price,
                final_price: price,
                discount_amount: 0,
                has_discount: false,
                discount: null
            };
        } catch (error) {
            console.error('Error in fallback price:', error);
            throw error;
        }
    }

    // در تابع getServicePrice در payment-popup.js
    async getServicePrice() {
        try {
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': this.options.ajaxAction,
                    'security': aiAssistantVars.nonce
                })
            });
    
            const data = await response.json();
            
            if (data.success) {
                return data.data.price;
            } else {
                throw new Error(data.data?.message || 'خطا در دریافت قیمت');
            }
        } catch (error) {
            console.error('Error getting service price:', error);
            throw error;
        }
    }

    createPopupElement() {
        // استفاده از this.priceData که در تابع show ذخیره شده
        const priceData = this.priceData;
        
        this.popup = document.createElement('div');
        this.popup.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        `;
        
        const formattedOriginalPrice = new Intl.NumberFormat('fa-IR').format(priceData.original_price);
        const formattedFinalPrice = new Intl.NumberFormat('fa-IR').format(priceData.final_price);
        
        this.popup.innerHTML = `
            <div style="
                background: white;
                padding: 20px;
                border-radius: 8px;
                width: 90%;
                max-width: 400px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            ">
                <div style="margin-bottom: 15px;">
                    <h3 style="margin: 0 0 15px 0; color: #333;">تایید پرداخت</h3>
                    
                    <!-- نمایش تخفیف خودکار اگر وجود دارد -->
                    ${priceData.has_discount ? `
                    <div style="background: #e8f5e8; border: 1px solid #4caf50; border-radius: 6px; padding: 10px; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #2e7d32;">
                            <i class="fas fa-tag" style="font-size: 16px;"></i>
                            <strong>تخفیف خودکار اعمال شد!</strong>
                        </div>
                        <div style="font-size: 13px; margin-top: 5px; color: #388e3c;">
                            ${priceData.discount.name} - 
                            ${priceData.discount.type === 'percentage' ? 
                              priceData.discount.amount + '%' : 
                              new Intl.NumberFormat('fa-IR').format(priceData.discount.amount) + ' تومان'}
                        </div>
                    </div>
                    ` : ''}
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>موجودی شما:</span>
                        <span id="current-balance" style="font-weight: bold;">در حال بارگذاری...</span>
                    </div>
                    
                    <!-- بخش کد تخفیف -->
                    <div style="margin-bottom: 15px; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px;">
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <input type="text" 
                                id="discount-code-input" 
                                placeholder="کد تخفیف اضافی (اختیاری)"
                                style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <button id="apply-discount-btn" style="
                                padding: 8px 12px;
                                background: #00857a;
                                color: white;
                                border: none;
                                border-radius: 4px;
                                cursor: pointer;
                                font-size: 12px;
                                white-space: nowrap;
                            ">اعمال کد تخفیف</button>
                        </div>
                        <div id="discount-message" style="font-size: 12px; min-height: 16px;"></div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>مبلغ قابل پرداخت:</span>
                        <span id="final-price" style="font-weight: bold; color: #00857a;">${formattedFinalPrice} تومان</span>
                    </div>
                    
                    ${priceData.has_discount ? `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; color: #666;">
                        <span>قیمت اصلی:</span>
                        <span id="original-price-display" style="text-decoration: line-through;">${formattedOriginalPrice} تومان</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 12px; color: #28a745;">
                        <span>مقدار تخفیف:</span>
                        <span id="auto-discount-amount">${new Intl.NumberFormat('fa-IR').format(priceData.discount_amount)} تومان</span>
                    </div>
                    ` : `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; color: #666;">
                        <span>قیمت اصلی:</span>
                        <span id="original-price-display">${formattedOriginalPrice} تومان</span>
                    </div>
                    `}
                    
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        در صورت تأیید، این مبلغ از حساب شما کسر خواهد شد.
                    </p>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button id="confirm-payment" style="
                        flex: 1;
                        padding: 12px;
                        background: #00857a;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                    " disabled>تأیید پرداخت</button>
                    
                    <button id="cancel-payment" style="
                        flex: 1;
                        padding: 12px;
                        background: #f0f0f0;
                        color: #333;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                    ">انصراف</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.popup);
        this.setupEventListeners();
    }

    setupEventListeners() {
        const cancelBtn = this.popup.querySelector('#cancel-payment');
        const applyDiscountBtn = this.popup.querySelector('#apply-discount-btn');
        const discountInput = this.popup.querySelector('#discount-code-input');

        // رویداد اعمال تخفیف
        applyDiscountBtn.addEventListener('click', () => {
            this.applyDiscount();
        });

        // اعمال تخفیف با کلید Enter
        discountInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.applyDiscount();
            }
        });

        cancelBtn.addEventListener('click', () => {
            if (this.options.onCancel) {
                this.options.onCancel();
            }
            this.hide();
        });

        this.popup.addEventListener('click', (e) => {
            if (e.target === this.popup) {
                e.stopPropagation();
                return;
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                e.preventDefault();
                e.stopPropagation();
                return false;                
            }
        });
    }

    // در تابع applyDiscount، بخش error handler رو به‌روزرسانی کنید:
    async applyDiscount() {
        const discountCode = document.getElementById('discount-code-input').value.trim();
        const messageElement = document.getElementById('discount-message');
        
        if (!discountCode) {
            this.showDiscountMessage('لطفا کد تخفیف را وارد کنید', 'error');
            return;
        }
        
        // ذخیره وضعیت فعلی قبل از اعمال کد تخفیف جدید
        const previousState = {
            finalPrice: this.finalPrice,
            discountApplied: this.discountApplied,
            discountAmount: this.discountAmount,
            hasAutoDiscount: this.hasAutoDiscount,
            autoDiscount: this.autoDiscount
        };
        
        const applyBtn = document.getElementById('apply-discount-btn');
        const originalText = applyBtn.innerHTML;
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
        try {
            const nonce = this.getNonce();
            if (!nonce) {
                throw new Error('خطا در تأیید هویت');
            }
    
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'validate_discount_code',
                    'discount_code': discountCode,
                    'service_id': this.options.serviceId,
                    'nonce': nonce
                })
            });
    
            const responseText = await response.text();
    
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('پاسخ سرور نامعتبر است');
            }
    
            if (data.success) {
                this.handleDiscountSuccess(data.data);
            } else {
                // در صورت خطا، به وضعیت قبلی برگرد
                this.finalPrice = previousState.finalPrice;
                this.discountApplied = previousState.discountApplied;
                this.discountAmount = previousState.discountAmount;
                this.hasAutoDiscount = previousState.hasAutoDiscount;
                this.autoDiscount = previousState.autoDiscount;
                
                this.handleDiscountError(data.data?.message || 'کد تخفیف معتبر نیست');
            }
        } catch (error) {
            console.error('Error applying discount:', error);
            // در صورت خطا، به وضعیت قبلی برگرد
            this.finalPrice = previousState.finalPrice;
            this.discountApplied = previousState.discountApplied;
            this.discountAmount = previousState.discountAmount;
            this.hasAutoDiscount = previousState.hasAutoDiscount;
            this.autoDiscount = previousState.autoDiscount;
            
            this.handleDiscountError(error.message || 'خطا در ارتباط با سرور');
        } finally {
            applyBtn.disabled = false;
            applyBtn.innerHTML = originalText;
        }
    }
    
    // اضافه کردن متدهای کمکی
    getNonce() {
        // اولویت‌بندی برای دریافت nonce
        if (typeof discountFrontendAdminVars !== 'undefined' && discountFrontendAdminVars.nonce) {
            return discountFrontendAdminVars.nonce;
        }
        if (typeof aiAssistantVars !== 'undefined' && aiAssistantVars.nonce) {
            return aiAssistantVars.nonce;
        }
        return null;
    }
    
    isUserLoggedIn() {
        return typeof aiAssistantVars !== 'undefined' && aiAssistantVars.user_id && aiAssistantVars.user_id !== '0';
    }

    handleDiscountSuccess(data) {
        // تبدیل مقادیر به عدد برای اطمینان
        this.finalPrice = parseFloat(data.final_price) || 0;
        this.discountAmount = parseFloat(data.discount_amount) || 0;
        this.originalPrice = parseFloat(data.original_price) || this.originalPrice;
        
        this.discountApplied = true;
        
        // ذخیره اطلاعات تخفیف در state به صورت مستقیم
        if (window.state && window.state.formData) {
            window.state.formData.discountInfo = {
                discountCode: document.getElementById('discount-code-input').value.trim(),
                discountApplied: true,
                discountAmount: this.discountAmount,
                originalPrice: this.originalPrice,
                finalPrice: this.finalPrice,
                discountData: data
            };
        }
        
        // به روزرسانی نمایش قیمت
        this.updatePriceDisplay();
        
        // نمایش جزئیات تخفیف
        this.showDiscountDetails(data);
        
        this.showDiscountMessage(data.message, 'success');
        
        // به روزرسانی بررسی موجودی با قیمت جدید
        this.fetchUserBalance(this.finalPrice);
        
        this.updateOriginalPriceDisplay();
    }

    updateOriginalPriceDisplay() {
        const originalPriceElement = document.getElementById('original-price-display');
        const formattedOriginalPrice = new Intl.NumberFormat('fa-IR').format(this.originalPrice);
        originalPriceElement.textContent = formattedOriginalPrice + ' تومان';
    }

    // در تابع handleDiscountError این تغییرات رو اعمال کنید:
    handleDiscountError(message) {
        this.showDiscountMessage(message, 'error');
        
        // فقط کد تخفیف رو ریست کن، تخفیف عمومی رو حفظ کن
        this.resetCouponOnly();
    }
    
    // تابع جدید برای ریست کردن فقط کد تخفیف (بدن تأثیر روی تخفیف عمومی)
    resetCouponOnly() {
        // فقط اطلاعات کد تخفیف رو پاک کن
        this.discountApplied = false;
        
        // قیمت نهایی رو به حالت قبل از کد تخفیف برگردون (با حفظ تخفیف عمومی)
        if (this.hasAutoDiscount && this.autoDiscount) {
            // اگر تخفیف عمومی فعال بود، قیمت نهایی رو به حالت تخفیف عمومی برگردون
            this.finalPrice = this.priceData.final_price;
        } else {
            // اگر تخفیف عمومی نبود، به قیمت اصلی برگرد
            this.finalPrice = this.originalPrice;
        }
        
        this.discountAmount = 0;
        
        // به‌روزرسانی state
        if (window.state && window.state.formData) {
            if (window.state.formData.discountInfo) {
                window.state.formData.discountInfo.discountCode = '';
                window.state.formData.discountInfo.discountApplied = false;
                window.state.formData.discountInfo.discountAmount = 0;
                window.state.formData.discountInfo.finalPrice = this.finalPrice;
                window.state.formData.discountInfo.discountData = null;
            }
        }
        
        this.updatePriceDisplay();
        
        const discountInput = document.getElementById('discount-code-input');
        if (discountInput) {
            discountInput.value = '';
        }
        
        // به‌روزرسانی بررسی موجودی
        this.fetchUserBalance(this.finalPrice);
        
        this.showDiscountMessage('', 'info');
    }

    showDiscountMessage(message, type) {
        const messageElement = document.getElementById('discount-message');
        messageElement.textContent = message;
        messageElement.style.color = type === 'success' ? '#28a745' : 
                                   type === 'error' ? '#dc3545' : '#6c757d';
    }

    updatePriceDisplay() {
        const finalPriceElement = document.getElementById('final-price');
        const formattedPrice = new Intl.NumberFormat('fa-IR').format(this.finalPrice);
        finalPriceElement.textContent = formattedPrice + ' تومان';
    }

    showDiscountDetails(data) {
        const discountDisplay = document.getElementById('discount-display');
        const discountAmountElement = document.getElementById('discount-amount');
        
        if (discountDisplay && discountAmountElement) {
            const formattedDiscount = new Intl.NumberFormat('fa-IR').format(this.discountAmount);
            discountAmountElement.textContent = formattedDiscount + ' تومان';
            discountDisplay.style.display = 'block';
        }
    }

    resetDiscount() {
        this.finalPrice = this.originalPrice;
        this.discountAmount = 0;
        this.discountApplied = false;
        
        // حذف اطلاعات تخفیف از state
        if (window.state && window.state.formData) {
            window.state.formData.discountInfo = {
                discountCode: '',
                discountApplied: false,
                discountAmount: 0,
                originalPrice: this.originalPrice,
                finalPrice: this.finalPrice,
                discountData: null
            };
        }
        
        this.updatePriceDisplay();
        
        const discountDisplay = document.getElementById('discount-display');
        if (discountDisplay) {
            discountDisplay.style.display = 'none';
        }
        
        const discountInput = document.getElementById('discount-code-input');
        if (discountInput) {
            discountInput.value = '';
        }
        
        // به روزرسانی بررسی موجودی
        this.fetchUserBalance(this.finalPrice);
        
        this.showDiscountMessage('تخفیف حذف شد', 'info');
    }

    async fetchUserBalance(servicePrice) {
        try {
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'get_user_wallet_credit',
                    'security': aiAssistantVars.nonce
                })
            });

            const data = await response.json();

            if (data.success) {
                this.updateBalanceUI(data.data.credit, servicePrice);
            } else {
                throw new Error(data.data?.message || 'خطا در دریافت موجودی');
            }
        } catch (error) {
            console.error('Error fetching balance:', error);
            document.getElementById('current-balance').textContent = 'خطا در دریافت موجودی';
            document.getElementById('confirm-payment').disabled = false;
        }
    }

    updateBalanceUI(balance, servicePrice) {
        const balanceElement = document.getElementById('current-balance');
        const confirmBtn = document.getElementById('confirm-payment');
        
        let formattedBalance = '';
        if (balance !== null && balance !== undefined) {
            formattedBalance = new Intl.NumberFormat('fa-IR').format(balance);
        }        
        balanceElement.textContent = formattedBalance + ' تومان';
        
        if (balance < servicePrice) {
            balanceElement.style.color = 'red';
            confirmBtn.textContent = 'افزایش موجودی';
            confirmBtn.onclick = () => {
                const baseUrl = window.location.origin;
                window.location.href = `${baseUrl}/wallet-charge/`;
            };
        } else {
            // حالت اول: موجودی کافی است
            confirmBtn.textContent = 'تأیید پرداخت';
            // ✅ راه حل: استفاده از arrow function برای حفظ this
            confirmBtn.onclick = () => {
                if (this.options.onConfirm) {
                    // 1. دریافت داده‌های کامل از state
                    const completeFormData = {
                        userInfo: { ...window.state.formData.userInfo },
                        serviceSelection: { ...window.state.formData.serviceSelection },
                        discountInfo: window.state.formData.discountInfo ? { ...window.state.formData.discountInfo } : {}
                    };
                    
                    // 2. اضافه کردن کد تخفیف از input اگر وجود دارد
                    const discountCodeInput = document.getElementById('discount-code-input');
                    if (discountCodeInput && discountCodeInput.value.trim() && completeFormData.discountInfo) {
                        completeFormData.discountInfo.discountCode = discountCodeInput.value.trim();
                    }
                    
                    // 3. ارسال داده‌های کامل
                    this.options.onConfirm(completeFormData, this.finalPrice, {
                        discountApplied: this.discountApplied,
                        finalPrice: this.finalPrice,
                        originalPrice: this.originalPrice
                    });
                }
                this.hide();
            };
        }
        
        confirmBtn.disabled = false;
    }

    hide() {
        this.isOpen = false;
        
        this.resetDiscount();
        
        if (this.popup) {
            document.body.removeChild(this.popup);
            this.popup = null;
            this.isOpen = false;
        }
    }
    
    resetDiscount() {
        this.finalPrice = this.originalPrice;
        this.discountAmount = 0;
        this.discountApplied = false;
        
        // حذف اطلاعات تخفیف از state اصلی
        if (window.state && window.state.formData) {
            window.state.formData.discountInfo = {
                discountCode: '',
                discountApplied: false,
                discountAmount: 0,
                originalPrice: this.originalPrice,
                finalPrice: this.originalPrice, // مهم: برگشت به قیمت اصلی
                discountData: null
            };
        }
        
        this.updatePriceDisplay();
        
        const discountDisplay = document.getElementById('discount-display');
        if (discountDisplay) {
            discountDisplay.style.display = 'none';
        }
        
        const discountInput = document.getElementById('discount-code-input');
        if (discountInput) {
            discountInput.value = '';
        }
        
        if (this.isOpen) {
            this.fetchUserBalance(this.originalPrice);
        }
        
        this.showDiscountMessage('', 'info');
    }    
}

window.PaymentPopup = PaymentPopup;