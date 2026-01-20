// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-events.js

   
// انتخاب گزینه‌های سرطان
document.querySelectorAll('.cancer-option').forEach(option => {
    option.addEventListener('click', function() {
        this.classList.toggle('selected');
    });
});

// نمایش/مخفی کردن جزئیات
document.getElementById('cancer-history').addEventListener('change', function() {
    document.getElementById('cancer-details').style.display = this.checked ? 'block' : 'none';
});

function setupScrollIndicator(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // ایجاد نشانگر اسکرول
    const scrollIndicator = document.createElement('div');
    scrollIndicator.className = 'scroll-indicator';
    scrollIndicator.innerHTML = '<div class="scroll-indicator-arrow"></div>';
    container.appendChild(scrollIndicator);

    // بررسی اولیه وضعیت اسکرول
    function checkScrollState() {
        const hasScroll = container.scrollHeight > container.clientHeight;
        const isAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 10;
        
        scrollIndicator.style.display = hasScroll ? 'flex' : 'none';
        
        if (hasScroll && !isAtBottom) {
            scrollIndicator.classList.remove('hidden');
            container.classList.remove('scrolled');
        } else {
            scrollIndicator.classList.add('hidden');
            container.classList.add('scrolled');
        }
    }

    // عملکرد کلیک برای اسکرول
    scrollIndicator.addEventListener('click', () => {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth'
        });
    });

    // رویداد اسکرول با debounce
    let scrollTimeout;
    container.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(checkScrollState, 50);
    });

    // بررسی تغییر اندازه
    const resizeObserver = new ResizeObserver(checkScrollState);
    resizeObserver.observe(container);

    // بررسی اولیه
    checkScrollState();
}

// استفاده از تابع برای تمام کانتینرهای اسکرول
document.addEventListener('DOMContentLoaded', () => {
    setupScrollIndicator('menstrual-status-step');
    setupScrollIndicator('height-weight-input-step');
    setupScrollIndicator('surgery-selection');
    setupScrollIndicator('goal-selection');
    setupScrollIndicator('chronic-conditions-selection');
    setupScrollIndicator('digestive-conditions-selection');
    setupScrollIndicator('activity-selection');
    setupScrollIndicator('exercise-selection'); // اضافه کردن این خط
    setupScrollIndicator('diet-style-selection');
    setupScrollIndicator('food-limitations-selection');
    setupScrollIndicator('medications-selection');
    setupScrollIndicator('lab-test-upload-step');
});

/**
 * تابع جدید: Refresh کردن nonce
 * وقتی nonce منقضی شده، این تابع یک nonce جدید از سرور می‌گیره
 */
async function refreshNonce() {
    const response = await fetch(aiAssistantVars.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'refresh_ajax_nonce'
        })
    });
    
    const data = await response.json();
    if (data.success && data.data.nonce) {
        aiAssistantVars.nonce = data.data.nonce;
        console.log('✅ Nonce با موفقیت refresh شد');
    } else {
        throw new Error('نتوانستیم nonce جدید دریافت کنیم');
    }
}

/**
 * تابع جدید: انجام عملیات اصلی بارگذاری قیمت‌ها
 * این تابع منطق اصلی دریافت قیمت‌ها رو داره
 */
async function performLoadServicePrices() {
    // دریافت قیمت با تخفیف برای سرویس ai-only
    const aiOnlyResponse = await fetch(aiAssistantVars.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_service_price_with_discount',
            service_id: 'diet',
            include_consultant_fee: '0',
            consultant_fee: '0',
            nonce: aiAssistantVars.nonce
        })
    });

    const aiOnlyData = await aiOnlyResponse.json();
    if (!aiOnlyData.success) {
        throw new Error(aiOnlyData.data?.message || 'خطا در دریافت قیمت');
    }

    // دریافت هزینه مشاور از endpoint قبلی
    const consultantResponse = await fetch(aiAssistantVars.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_diet_service_prices',
            security: aiAssistantVars.nonce
        })
    });

    const consultantData = await consultantResponse.json();
    if (!consultantData.success) {
        throw new Error(consultantData.data?.message || 'خطا در دریافت قیمت مشاور');
    }

    const aiOnlyFinalPrice = aiOnlyData.data.final_price;
    const aiOnlyOriginalPrice = aiOnlyData.data.original_price;
    const hasAiOnlyDiscount = aiOnlyData.data.has_discount;
    const consultantFee = consultantData.data.consultant_price;

    // نمایش قیمت ai-only با تخفیف
    const aiOnlyPriceElement = document.getElementById('ai-only-price');
    if (aiOnlyPriceElement) {
        if (hasAiOnlyDiscount) {
            aiOnlyPriceElement.innerHTML = `
                <span style="text-decoration: line-through; color: #999; font-size: 0.9em; margin-left: 8px;">
                    ${new Intl.NumberFormat('fa-IR').format(aiOnlyOriginalPrice)}
                </span>
                <span style="color: #00857a; font-weight: bold;">
                    ${new Intl.NumberFormat('fa-IR').format(aiOnlyFinalPrice)}
                </span>
            `;
        } else {
            aiOnlyPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(aiOnlyFinalPrice);
        }
    }

    // نمایش قیمت with-specialist با تخفیف
    const specialistPriceElement = document.getElementById('with-specialist-price');
    if (specialistPriceElement) {
        const specialistFinalPrice = aiOnlyFinalPrice + consultantFee;
        const specialistOriginalPrice = aiOnlyOriginalPrice + consultantFee;

        if (hasAiOnlyDiscount) {
            specialistPriceElement.innerHTML = `
                <span style="text-decoration: line-through; color: #999; font-size: 0.9em; margin-left: 8px;">
                    ${new Intl.NumberFormat('fa-IR').format(specialistOriginalPrice)}
                </span>
                <span style="color: #00857a; font-weight: bold;">
                    ${new Intl.NumberFormat('fa-IR').format(specialistFinalPrice)}
                </span>
            `;
        } else {
            specialistPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(specialistFinalPrice);
        }
    }

    // ذخیره در state
    if (window.state && window.state.formData) {
        window.state.formData.servicePrices = {
            aiOnly: aiOnlyFinalPrice,
            aiOnlyOriginal: aiOnlyOriginalPrice,
            consultantFee: consultantFee,
            withSpecialist: aiOnlyFinalPrice + consultantFee,
            withSpecialistOriginal: aiOnlyOriginalPrice + consultantFee,
            hasDiscount: hasAiOnlyDiscount,
            loaded: true,
            error: false
        };
        
        console.log('✅ قیمت‌ها با تخفیف بارگذاری شد:', {
            aiOnlyFinal: aiOnlyFinalPrice,
            aiOnlyOriginal: aiOnlyOriginalPrice,
            hasDiscount: hasAiOnlyDiscount,
            consultantFee: consultantFee
        });
    }
}

/**
 * تابع اصلاح شده: بارگذاری قیمت‌های سرویس
 * تغییرات: اضافه شدن منطق retry با nonce جدید در صورت خطا
 */
async function loadServicePrices() {
    try {
        // تلاش اول برای بارگذاری قیمت‌ها
        await performLoadServicePrices();
    } catch (error) {
        console.error('❌ خطا در بارگذاری قیمت‌های سرویس:', error);
        
        // اگر خطای nonce بود، یکبار دیگه با nonce جدید تلاش کن
        if (error.message && error.message.includes('Nonce verification failed')) {
            console.log('🔄 تلاش برای refresh کردن nonce...');
            try {
                await refreshNonce();
                await performLoadServicePrices();
                console.log('✅ قیمت‌ها با nonce جدید بارگذاری شد');
                return; // موفقیت‌آمیز بود، خارج شو
            } catch (retryError) {
                console.error('❌ خطا بعد از refresh nonce:', retryError);
            }
        }
        
        // نمایش خطا به کاربر
        const errorMessage = 'خطا در بارگذاری قیمت‌ها';
        const aiOnlyPriceElement = document.getElementById('ai-only-price');
        const specialistPriceElement = document.getElementById('with-specialist-price');
        
        if (aiOnlyPriceElement) aiOnlyPriceElement.textContent = errorMessage;
        if (specialistPriceElement) specialistPriceElement.textContent = errorMessage;
        
        // ذخیره خطا در state
        if (window.state && window.state.formData) {
            window.state.formData.servicePrices = {
                loaded: false,
                error: true,
                errorMessage: errorMessage
            };
        }
        
        if (typeof showNotification === 'function') {
            showNotification('خطا در بارگذاری قیمت‌ها، لطفاً صفحه را رفرش کنید', 'error');
        } else {
            console.warn(errorMessage);
        }
    }
}


// /assets/js/services/diet/form-events.js
function showNotification(message, type = 'info') {
    // ایجاد یک نوتیفیکیشن ساده
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 20px;
        background: ${type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        border-radius: 4px;
        z-index: 10000;
        font-family: inherit;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // حذف خودکار پس از 5 ثانیه
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Gender Selection Event Handler
document.addEventListener('DOMContentLoaded', function() {
  // Gender Selection
  const genderOptions = document.querySelectorAll('.gender-option');
  genderOptions.forEach(option => {
    option.addEventListener('click', function() {
      const gender = this.dataset.gender;
      state.updateFormData('userInfo.gender', gender);
      
      // اگر خانم انتخاب شد، مرحله MENSTRUAL_STATUS نمایش داده شود
      // اگر آقا انتخاب شد، مرحله MENSTRUAL_STATUS skip شود
      
      // اگر حالا می‌رویم جلو
      setTimeout(() => {
        window.handleNextStep();
      }, 300);
    });
  });

  loadServicePrices();
});


window.handleNextStep = function() {
  if (window.state.currentStep < window.totalSteps) {
    const nextStep = window.state.currentStep + 1;
    const actualStep = getActualNextStep(nextStep);
    window.navigateToStep(actualStep);
  }
};

window.handleBackStep = function() {
  if (state.currentStep > 1) {
    const previousStep = state.currentStep - 1;
    const actualStep = window.getActualPreviousStep(previousStep);
    window.navigateToStep(actualStep);
  }
};

// ============================================================================
// HELPER: Skip MENSTRUAL_STATUS for males
// ============================================================================
window.getActualNextStep = function(requestedStep) {
  // اگر مرد بود، مرحله MENSTRUAL_STATUS (2) رو skip کنید
  if (requestedStep === window.STEPS.MENSTRUAL_STATUS && 
      state.formData.userInfo.gender === 'male') {
    return window.STEPS.PERSONAL_INFO; // برو به مرحله بعدی (3)
  }
  return requestedStep;
};

// ✅ صحیح:
window.getActualPreviousStep = function(requestedStep) {
  // اگر هم اکنون در PERSONAL_INFO هستیم و عقب می‌رویم
  if (state.currentStep === window.STEPS.PERSONAL_INFO) {
    if (state.formData.userInfo.gender === 'male') {
      return window.STEPS.GENDER; // برو به step 1 (skip step 2)
    } else {
      return window.STEPS.MENSTRUAL_STATUS; // برو به step 2
    }
  }
  
  return requestedStep;
};


// window.preloadImages = function() {
//     const images = [
//         'assets/images/webp/img_0_de-min.webp',
//         'assets/images/png/img_1_de-min.png',
//         'assets/images/webp/img_0-min.webp',
//         'assets/images/png/img_1-min.png'
//     ];
    
//     images.forEach(src => {
//         const img = new Image();
//         img.src = src;
//     });
// }

window.showPaymentConfirmation = function(formData, finalPrice) {
    try {
        // محاسبه قیمت نهایی با در نظر گرفتن هزینه مشاور
        let calculatedFinalPrice = finalPrice;
        let consultantFee = 0; // ✅ تعریف پیشفرض
        
        // اگر رژیم با متخصص انتخاب شده
        if (formData.serviceSelection.dietType === 'with-specialist') {
            // ✅ اول بررسی کن اگر مشاور خاص انتخاب شده
            if (formData.serviceSelection.selectedSpecialist && 
                formData.serviceSelection.selectedSpecialist.consultation_price) {
                consultantFee = formData.serviceSelection.selectedSpecialist.consultation_price;
                console.log('✅ استفاده از قیمت مشاور انتخابی:', consultantFee);
            } 
            // ✅ اگر مشاور انتخاب نشده، از قیمت پیشفرض استفاده کن
            else if (window.state?.formData?.servicePrices?.consultantFee) {
                consultantFee = window.state.formData.servicePrices.consultantFee;
                console.log('⚠️ مشاور انتخاب نشده - استفاده از قیمت پیشفرض:', consultantFee);
            }
            // ✅ fallback اگر هیچکدام وجود نداشت
            else {
                consultantFee = 25000; // قیمت پیشفرض هاردکد
                console.warn('⚠️ قیمت مشاور یافت نشد - استفاده از مقدار پیشفرض:', consultantFee);
            }
            
            calculatedFinalPrice += consultantFee;
            
            console.log('💰 قیمت نهایی با هزینه مشاور:', {
                basePrice: finalPrice,
                consultantFee: consultantFee,
                total: calculatedFinalPrice
            });
        }

        const paymentPopup = new PaymentPopup({
            serviceType: 'رژیم غذایی',
            serviceId: 'diet',
            customPrice: calculatedFinalPrice, // استفاده از قیمت محاسبه شده
            ajaxAction: 'get_diet_service_price',
            includeConsultantFee: formData.serviceSelection.dietType === 'with-specialist',
            consultantFee: consultantFee, // ✅ حالا همیشه یک مقدار معتبر داره
            onConfirm: (completeFormData, confirmedFinalPrice, discountDetails) => {
                const completePersianData = window.convertToCompletePersianData(completeFormData);
                completePersianData.finalPrice = confirmedFinalPrice;
                completePersianData.discountDetails = discountDetails;

                console.log('💰 ارسال دادههای تخفیف به سرور:', completePersianData.discountInfo);

                window.dispatchEvent(new CustomEvent('formSubmitted', {
                    detail: {
                        formData: completePersianData,
                        finalPrice: confirmedFinalPrice,
                        discountInfo: completePersianData.discountInfo
                    }
                }));
            },
            onCancel: () => {
                if (window.state && window.state.formData) {
                    window.state.formData.discountInfo = {
                        discountCode: '',
                        discountApplied: false,
                        discountAmount: 0,
                        originalPrice: finalPrice,
                        finalPrice: finalPrice,
                        discountData: null
                    };
                }
                document.getElementById('SubmitBtn').disabled = false;
            }
        });

        paymentPopup.show();
    } catch (error) {
        console.error('Error showing payment popup:', error);
        alert('خطا در نمایش پرداخت. لطفاً صفحه را رفرش کنید.');
    }
};

function handleConflictingConditions(selectedConditionId) {
    const conflictGroups = {
        // تیروئید - فقط یکی قابل انتخاب است
        'chronic-hyperthyroidism': ['chronic-hypothyroidism', 'chronic-hashimoto'],
        'chronic-hypothyroidism': ['chronic-hyperthyroidism'],
        'chronic-hashimoto': ['chronic-hyperthyroidism'],
        
        // کیسه صفرا - فقط یکی قابل انتخاب است
        'chronic-gallbladder-stones': ['chronic-gallbladder-inflammation', 'chronic-gallbladder-issues'],
        'chronic-gallbladder-inflammation': ['chronic-gallbladder-stones', 'chronic-gallbladder-issues'],
        'chronic-gallbladder-issues': ['chronic-gallbladder-stones', 'chronic-gallbladder-inflammation']
    };

    if (conflictGroups[selectedConditionId]) {
        conflictGroups[selectedConditionId].forEach(conflictingId => {
            const conflictingCheckbox = document.getElementById(conflictingId);
            if (conflictingCheckbox && conflictingCheckbox.checked) {
                conflictingCheckbox.checked = false;
                // به روزرسانی state و UI
                conflictingCheckbox.dispatchEvent(new Event('change'));
                
                // حذف از state.formData
                const conditionKey = conflictingId.replace('chronic-', '');
                if (state.formData.chronicConditions) {
                    state.formData.chronicConditions = state.formData.chronicConditions.filter(
                        item => item !== conditionKey
                    );
                }
            }
        });
    }
}

// تابع ریست انتخاب‌های دیابت
function resetChronicDiabetesSelections() {
    document.querySelectorAll('.diabetes-option.selected').forEach(opt => {
        opt.classList.remove('selected');
        opt.style.backgroundColor = '';
        opt.style.padding = '';
    });
    
    const fastingInput = document.getElementById('fasting-blood-sugar');
    const hba1cInput = document.getElementById('hba1c-level');
    
    if (fastingInput) fastingInput.value = '';
    if (hba1cInput) hba1cInput.value = '';
}

window.handleFormSubmit = function(event) {
    event.preventDefault();
    
    // 1. جمع‌آوری ساختارمند تمام داده‌ها
    const formData = {
        userInfo: { ...state.formData.userInfo },
        serviceSelection: { ...state.formData.serviceSelection },
        discountInfo: { ...state.formData.discountInfo }
    };
    
    const discountCodeInput = document.getElementById('discount-code-input');
    if (discountCodeInput && discountCodeInput.value.trim()) {
        formData.discountInfo = {
            ...formData.discountInfo,
            discountCode: discountCodeInput.value.trim(),
            discountApplied: true,
            discountAmount: state.formData.discountInfo?.discountAmount || 0,
            originalPrice: state.formData.discountInfo?.originalPrice || 0,
            finalPrice: state.formData.discountInfo?.finalPrice || 0
        };
    }
    
    const finalPrice = formData.discountInfo.finalPrice || formData.discountInfo.originalPrice;
    
    // غیرفعال کردن دکمه سابمیت
    document.getElementById('SubmitBtn').disabled = true;
    
    // نمایش پاپ‌آپ تأیید پرداخت
    window.showPaymentConfirmation(formData, finalPrice);
    
    return false;
};

window.showSummary = function() {
    const summaryContainer = document.getElementById('summary-container');
    const nextButton = document.querySelector(".next-step");
    const confirmCheckbox = document.getElementById("confirm-info");
    
    nextButton.disabled = true;
    
    const { 
        userInfo,
        serviceSelection,
        servicePrices // 🔥 استفاده از قیمت‌های ذخیره شده در state
    } = state.formData;

    const {
        fullName, gender, age, height, weight, targetWeight, goal,
        activity, exercise, waterIntake, surgery = [],
        digestiveConditions = [], dietStyle = [],
        foodLimitations = [], chronicConditions, medications,
        chronicDiabetesType, chronicFastingBloodSugar, chronicHba1c,
        cancerTreatment, cancerType, menstrualStatus, labTestFile, skipLabTest
    } = userInfo;

    const { dietType, selectedSpecialist } = serviceSelection;

    const personalInfoText = [];
    if (fullName) personalInfoText.push(`نام و نام خانوادگی: ${fullName}`);
    
    // ✅ بعد (متغیر جدید):
    let menstrualStatusText = '';
    
    if (gender === 'female' && menstrualStatus) {
        const menstrualMap = {
            'not-set': 'تنظیم نشده',
            'regular': 'منظم',
            'irregular': 'نامنظم',
            'menopause': 'یائسگی',
            'pregnancy': 'بارداری',
            'skip': 'نمیخوام جواب بدم'
        };
        menstrualStatusText = menstrualMap[menstrualStatus];
    }

    const goalText = { 
        "weight-loss": "کاهش وزن", 
        "weight-gain": "افزایش وزن", 
        "fitness": "حفظ سلامت"
    }[goal];
    
    const activityText = { 
        "very-low": "خیلی کم (بی‌تحرک)", 
        "low": "کم (فعالیت سبک)", 
        "medium": "متوسط (فعالیت متوسط)", 
        "high": "زیاد (فعالیت شدید)" 
    }[activity];
    
    const chronicConditionsText = [];
    if (chronicConditions.includes('diabetes')) {
        const diabetesTypeText = {
            'type1': 'دیابت نوع 1',
            'type2': 'دیابت نوع 2', 
            'gestational': 'دیابت بارداری',
            'prediabetes': 'پیش‌دیابت'
        }[state.formData.chronicDiabetesType];
        
        chronicConditionsText.push(`دیابت (${diabetesTypeText || 'نوع مشخص نشده'})`);
        
        // اضافه کردن اطلاعات تکمیلی اگر موجود باشد
        if (state.formData.chronicFastingBloodSugar) {
            chronicConditionsText.push(`قند ناشتا: ${state.formData.chronicFastingBloodSugar}`);
        }
        if (state.formData.chronicHba1c) {
            chronicConditionsText.push(`HbA1c: ${state.formData.chronicHba1c}%`);
        }
    }
    if (chronicConditions.includes('hypertension')) chronicConditionsText.push('فشار خون بالا');
    if (chronicConditions.includes('cholesterol')) chronicConditionsText.push('کلسترول/تری گلیسیرید بالا');
    if (chronicConditions.includes('fattyLiver')) chronicConditionsText.push('کبد چرب');
    if (chronicConditions.includes('cirrhosis')) chronicConditionsText.push('سیروز کبدی');
    if (chronicConditions.includes('hepatitis')) chronicConditionsText.push('هپاتیت مزمن');
    if (chronicConditions.includes('insulinResistance')) chronicConditionsText.push('مقاومت به انسولین');
    if (chronicConditions.includes('hypothyroidism')) chronicConditionsText.push('کم کاری تیروئید');
    if (chronicConditions.includes('hyperthyroidism')) chronicConditionsText.push('پرکاری تیروئید');
    if (chronicConditions.includes('hashimoto')) chronicConditionsText.push('هاشیموتو');
    if (chronicConditions.includes('pcos')) chronicConditionsText.push('سندرم تخمدان پلی کیستیک');
    if (chronicConditions.includes('menopause')) chronicConditionsText.push('یائسگی/پیش یائسگی');
    if (chronicConditions.includes('cortisol')) chronicConditionsText.push('مشکلات کورتیزول');
    if (chronicConditions.includes('growth')) chronicConditionsText.push('اختلال هورمون رشد');
    
    if (chronicConditions.includes('kidney')) {
        let kidneyText = 'کلیه';
        if (state.formData.userInfo.chronicKidneyStage) {
            const kidneyStageMap = {
                'early': 'کلیه - مرحله اولیه',
                'advanced-no-dialysis': 'کلیه - پیشرفته بدون دیالیز', 
                'dialysis': 'کلیه - دیالیز',
                'transplant-less1year': 'کلیه - پیوند کمتر از 1 سال',
                'transplant-more1year': 'کلیه - پیوند بیش از 1 سال'
            };
            kidneyText = kidneyStageMap[state.formData.userInfo.chronicKidneyStage] || kidneyText;
        }
        chronicConditionsText.push(kidneyText);
    }
    
    if (chronicConditions.includes('heart')) chronicConditionsText.push('بیماری قلبی عروقی');
    if (chronicConditions.includes('autoimmune')) chronicConditionsText.push('بیماری خودایمنی');
    if (chronicConditions.includes('none')) chronicConditionsText.push('ندارم');

    
    const medicationsText = [];
    if (medications.includes('diabetesOral')) medicationsText.push('داروهای خوراکی دیابت');
    if (medications.includes('insulin')) medicationsText.push('انسولین');
    if (medications.includes('thyroid')) medicationsText.push('داروهای تیروئید');
    if (medications.includes('corticosteroids')) medicationsText.push('کورتیکواستروئیدها');
    if (medications.includes('anticoagulants')) medicationsText.push('داروهای ضدانعقاد');
    if (medications.includes('hypertension')) medicationsText.push('داروهای فشارخون');
    if (medications.includes('psychiatric')) medicationsText.push('داروهای روان‌پزشکی');
    if (medications.includes('hormonal')) medicationsText.push('داروهای هورمونی');
    if (medications.includes('cardiac')) medicationsText.push('داروهای قلبی');
    if (medications.includes('gastrointestinal')) medicationsText.push('داروهای گوارشی');
    if (medications.includes('supplements')) medicationsText.push('مکمل‌ها');
    
    // NEW medications
    if (medications.includes('immunosuppressants')) medicationsText.push('داروهای سرکوب ایمنی');
    if (medications.includes('cancer-oral')) medicationsText.push('داروهای ضدسرطان خوراکی');
    if (medications.includes('anticonvulsant')) medicationsText.push('داروهای ضدصرع');
    if (medications.includes('weight-loss')) medicationsText.push('داروهای لاغری');
    
    if (medications.includes('none')) medicationsText.push('بدون داروی منظم');


    // اضافه کردن به تابع showSummary
    const exerciseText = { 
        "none": "هیچ ورزشی نمی‌کنم",
        "light": "سبک (۱-۲ روز در هفته)", 
        "medium": "متوسط (۳-۴ روز در هفته)", 
        "high": "زیاد (۵-۶ روز در هفته)", 
        "professional": "ورزشکار حرفه‌ای" 
    }[exercise];
    
    const waterText = waterIntake === null ? 
        'مشخص نیست' : 
        `${waterIntake} لیوان (≈${(waterIntake * 0.25).toFixed(1)} لیتر)`;

    // در تابع showSummary - بخش surgeryText
    const surgeryText = [];
    if (surgery.includes('metabolic')) surgeryText.push('جراحی متابولیک');
    if (surgery.includes('gallbladder')) surgeryText.push('جراحی کیسه صفرا');
    if (surgery.includes('intestine')) surgeryText.push('جراحی روده');
    if (surgery.includes('thyroid')) surgeryText.push('جراحی تیروئید');
    if (surgery.includes('pancreas')) surgeryText.push('جراحی لوزالمعده');
    if (surgery.includes('heart')) surgeryText.push('جراحی قلب');
    if (surgery.includes('kidney')) surgeryText.push('پیوند کلیه');
    if (surgery.includes('liver')) surgeryText.push('پیوند کبد');
    if (surgery.includes('gynecology')) surgeryText.push('جراحی زنان');
    if (surgery.includes('cancer')) surgeryText.push('سابقه سرطان');
    if (surgery.includes('none')) surgeryText.push('هیچگونه سابقه جراحی ندارم');
    
    // اضافه کردن اطلاعات سرطان اگر انتخاب شده
    if (surgery.includes('cancer')) {
        const cancerTreatmentText = {
            'chemo': 'شیمی درمانی',
            'radio': 'پرتو درمانی', 
            'surgery': 'اخیراً جراحی شده',
            'finished': 'درمان تمام شده'
        }[state.formData.cancerTreatment];
    
        const cancerTypeText = {
            'breast': 'پستان',
            'colon': 'روده',
            'prostate': 'پروستات',
            'lung': 'ریه',
            'blood': 'خون',
            'other': 'سایر'
        }[state.formData.cancerType];
    
        if (cancerTreatmentText) surgeryText.push(`درمان: ${cancerTreatmentText}`);
        if (cancerTypeText) surgeryText.push(`نوع: ${cancerTypeText}`);
    }

    const digestiveConditionsText = [];
    if (digestiveConditions.includes('ibs')) digestiveConditionsText.push('سندرم روده تحریک پذیر');
    if (digestiveConditions.includes('ibd')) digestiveConditionsText.push('بیماری التهابی روده');
    if (digestiveConditions.includes('gerd')) digestiveConditionsText.push('ریفلاکس معده-مروی');
    if (digestiveConditions.includes('bloating')) digestiveConditionsText.push('نفخ یا گاز معده');
    if (digestiveConditions.includes('pain')) digestiveConditionsText.push('درد یا گرفتگی معده');
    if (digestiveConditions.includes('heartburn')) digestiveConditionsText.push('سوزش سر دل');
    if (digestiveConditions.includes('constipation')) digestiveConditionsText.push('یبوست مزمن');
    if (digestiveConditions.includes('diarrhea')) digestiveConditionsText.push('اسهال مزمن');
    if (digestiveConditions.includes('fullness')) digestiveConditionsText.push('سیری زودرس');
    if (digestiveConditions.includes('nausea')) digestiveConditionsText.push('حالت تهوع');
    if (digestiveConditions.includes('slow-digestion')) digestiveConditionsText.push('هضم کند غذا');
    if (digestiveConditions.includes('indigestion')) digestiveConditionsText.push('سوء هاضمه مزمن');
    if (digestiveConditions.includes('helicobacter')) digestiveConditionsText.push('عفونت هلیکوباکتر پیلوری');
    if (digestiveConditions.includes('none')) digestiveConditionsText.push('ندارم');

    // سبک‌های غذایی
    const dietStyleText = [];
    if (dietStyle.includes('vegetarian')) dietStyleText.push('گیاهخواری');
    if (dietStyle.includes('vegan')) dietStyleText.push('وگان');
    if (dietStyle.includes('none')) dietStyleText.push('سبک غذایی خاصی ندارم');
    
    const foodLimitationsText = [];
    if (foodLimitations.includes('celiac')) foodLimitationsText.push('بیماری سلیاک');
    if (foodLimitations.includes('lactose')) foodLimitationsText.push('عدم تحمل لاکتوز');
    if (foodLimitations.includes('seafood-allergy')) foodLimitationsText.push('حساسیت به غذای دریایی');
    if (foodLimitations.includes('eggs-allergy')) foodLimitationsText.push('حساسیت به تخم‌مرغ');
    if (foodLimitations.includes('nuts-allergy')) foodLimitationsText.push('حساسیت به آجیل');
    // ترجیحات شخصی
    if (foodLimitations.includes('no-seafood')) foodLimitationsText.push('عدم مصرف غذای دریایی');
    if (foodLimitations.includes('no-redmeat')) foodLimitationsText.push('عدم مصرف گوشت قرمز');
    if (foodLimitations.includes('no-dairy')) foodLimitationsText.push('عدم مصرف لبنیات');
    
    if (foodLimitations.includes('none')) foodLimitationsText.push('ندارم');

    // 🔥 اصلاح بخش نمایش قیمت - تعریف متغیرهای لازم
    let dietTypeText = '';
    
    // مدیریت نمایش قیمت‌ها با در نظر گرفتن وضعیت خطا
    if (servicePrices && servicePrices.error) {
        // نمایش پیغام خطا
        dietTypeText = `نوع رژیم: ${serviceSelection.dietType === 'ai-only' ? 'رژیم هوش مصنوعی' : 'رژیم با تأیید متخصص'} - ${servicePrices.errorMessage}`;
    } else if (servicePrices && servicePrices.loaded) {
        // استفاده از قیمت‌های واقعی
        const aiOnlyPrice = servicePrices.aiOnly;
        const consultantFee = servicePrices.consultantFee || 25000; // قیمت پیش‌فرض مشاور
        
        // 🔥 تعریف متغیرهای فرمت شده
        const formattedAiOnlyPrice = new Intl.NumberFormat('fa-IR').format(aiOnlyPrice);
        
        if (serviceSelection.dietType === 'ai-only') {
            dietTypeText = `رژیم هوش مصنوعی (${formattedAiOnlyPrice} تومان)`;
        } else if (serviceSelection.dietType === 'with-specialist' && serviceSelection.selectedSpecialist) {
            // استفاده از قیمت مشاور انتخاب شده یا قیمت پیش‌فرض
            const specialistConsultationPrice = serviceSelection.selectedSpecialist.consultation_price || consultantFee;
            const totalPrice = aiOnlyPrice + specialistConsultationPrice;
            const formattedTotalPrice = new Intl.NumberFormat('fa-IR').format(totalPrice);
            
            dietTypeText = `رژیم با تأیید متخصص (${formattedTotalPrice} تومان) - ${serviceSelection.selectedSpecialist.name}`;
        } else if (serviceSelection.dietType === 'with-specialist') {
            // اگر مشاور انتخاب نشده اما نوع رژیم با مشاور است
            const totalPrice = aiOnlyPrice + consultantFee;
            const formattedTotalPrice = new Intl.NumberFormat('fa-IR').format(totalPrice);
            dietTypeText = `رژیم با تأیید متخصص (${formattedTotalPrice} تومان) - متخصص انتخاب نشده`;
        }
    } else {
        // اگر قیمت‌ها هنوز لود نشده‌اند
        dietTypeText = `نوع رژیم: ${serviceSelection.dietType === 'ai-only' ? 'رژیم هوش مصنوعی' : 'رژیم با تأیید متخصص'} - در حال دریافت قیمت...`;
    }
    
    const targetWeightDisplay = targetWeight != null && targetWeight.toString().trim() ? `${targetWeight} کیلوگرم` : 'مشخص نشده';    
    
    
    let labTestText = "";
    if (skipLabTest) {
        labTestText = "رد شده";
    } else if (labTestFile && labTestFile.fileName) {
        labTestText = `آپلود شده: ${labTestFile.fileName}`;
    } else {
        labTestText = "آپلود نشده";
    }
    
    summaryContainer.innerHTML = `
        ${personalInfoText.length > 0 ? `
        <div class="summary-section">
            <h3 class="summary-section-title">اطلاعات شخصی</h3>
            ${personalInfoText.map(item => `
                <div class="summary-item">
                    <span class="summary-label">${item.split(':')[0]}:</span>
                    <span class="summary-value">${item.split(':')[1]}</span>
                </div>
            `).join('')}
        </div>
        ` : ''}    
        <div class="summary-item">
            <span class="summary-label">جنسیت:</span>
            <span class="summary-value">${gender === "male" ? "مرد" : "زن"}</span>
        </div>
        ${gender === "female" ? `
        <div class="summary-item menstrual-item">
            <span class="summary-label">وضعیت دوره‌ای:</span>
            <span class="summary-value">${menstrualStatusText}</span>
        </div>        `:''}
        <div class="summary-item">
            <span class="summary-label">سن:</span>
            <span class="summary-value">${age} سال</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">قد:</span>
            <span class="summary-value">${height} سانتی‌متر</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">وزن فعلی:</span>
            <span class="summary-value">${weight} کیلوگرم</span>
        </div>
        ${targetWeightDisplay !== 'مشخص نشده' ? 
            `<div class="summary-item">
                <span class="summary-label">وزن هدف:</span>
                <span class="summary-value">${targetWeightDisplay}</span>
            </div>` : 
            `<div class="summary-item">
                <span class="summary-label">وزن هدف:</span>
                <span class="summary-value">${targetWeightDisplay}</span>
            </div>`
        }
        <div class="summary-item">
            <span class="summary-label">هدف:</span>
            <span class="summary-value">${goalText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">بیماری‌های مزمن:</span>
            <span class="summary-value">${chronicConditionsText.join('، ') || 'ثبت نشده'}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">داروهای مصرفی:</span>
            <span class="summary-value">${medicationsText.join('، ') || 'ثبت نشده'}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">مشکلات گوارشی:</span>
            <span class="summary-value">${digestiveConditionsText.join('، ') || 'ثبت نشده'}</span>
        </div>         
        <div class="summary-item">
            <span class="summary-label">سابقه جراحی:</span>
            <span class="summary-value">${surgeryText.join('، ') || 'ثبت نشده'}</span>
        </div>  
        <div class="summary-item">
            <span class="summary-label">مصرف آب روزانه:</span>
            <span class="summary-value">${waterText}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">آزمایش آزمایشگاهی:</span>
            <span class="summary-value">${labTestText}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">فعالیت روزانه:</span>
            <span class="summary-value">${activityText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">فعالیت ورزشی:</span>
            <span class="summary-value">${exerciseText}</span>
        </div>             
        <div class="summary-item">
            <span class="summary-label">سبک غذایی:</span>
            <span class="summary-value">${dietStyleText.join('، ') || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">محدودیت‌های غذایی:</span>
            <span class="summary-value">${foodLimitationsText.join('، ') || 'ثبت نشده'}</span>
        </div>  
        <div class="summary-item">
            <span class="summary-label">نوع رژیم:</span>
            <span class="summary-value">${dietTypeText}</span>
        </div>        
        `;
}

// Initialize event listeners
// window.addEventListener('load', preloadImages);