// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-steps.js

window.STEPS = {
    GENDER: 1,
    MENSTRUAL_STATUS: 2,
    PERSONAL_INFO: 3,
    GOAL: 4,
    HEIGHT_WEIGHT: 5,
    TARGET_WEIGHT: 6,
    CHRONIC_CONDITIONS: 7,    // ✅ تغییر: 7 → 6
    MEDICATIONS: 8,           // ✅ تغییر: 8 → 7
    DIGESTIVE_CONDITIONS: 9,  // ✅ تغییر: 9 → 8
    SURGERY: 10,               // ✅ تغییر: 10 → 9
    LABTESTUPLOAD: 11,
    WATER_INTAKE: 12,         // ✅ تغییر: 11 → 10
    ACTIVITY: 13,             // ✅ تغییر: 12 → 11
    EXERCISE: 14,             // ✅ تغییر: 13 → 12
    DIET_STYLE: 15,           // ✅ تغییر: 14 → 13
    FOOD_LIMITATIONS: 16,     // ✅ تغییر: 15 → 14
    DIET_TYPE_SELECTION: 17,
    TERMS_AGREEMENT: 18,
    CONFIRMATION: 19
};

// تعداد مراحل اصلی (بدون احتساب دو مرحله آخر)
window.totalSteps = Object.keys(STEPS).length - 3; 

// ==========================================
// Cache for Consultant Data
// ==========================================
window.consultantsCache = window.consultantsCache || null;
window.isFetchingConsultants = window.isFetchingConsultants || false;


window.autoNextTimeout = window.autoNextTimeout || null;

window.setupAutoNavigateOnNoneCheckbox = function(checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    if (!checkbox) return;

    checkbox.addEventListener('change', function () {
        // هر بار قبلی را لغو کن
        if (autoNextTimeout) {
            clearTimeout(autoNextTimeout);
            autoNextTimeout = null;
        }

        if (this.checked) {
            const stepAtSchedule = state.currentStep; // همین لحظه

            autoNextTimeout = setTimeout(() => {
                autoNextTimeout = null;

                // فقط اگر هنوز در همان step هستیم، برو جلو
                if (state.currentStep === stepAtSchedule) {
                    window.handleNextStep();
                }
            }, 300);
        }
    });
};


// ============================================
// Menstrual Status - بدون CSS اضافی
// استفاده از check-icon/checked classes موجود
// ============================================

window.setupMenstrualStatusSelection = function(step) {
    if (step !== window.STEPS.MENSTRUAL_STATUS) return;

    const radioInputs = document.querySelectorAll('input[name="menstrual-status"]');
    const checkboxContainers = document.querySelectorAll('#menstrual-status-selection .checkbox-container');
    const nextButton = document.querySelector('.next-step');

    if (radioInputs.length === 0) {
        console.warn('⚠️ Menstrual status radios not found');
        return;
    }

    // ────────────────────────────────────────────
    // 1️⃣ بازنشانی حالت اولیه
    // ────────────────────────────────────────────
    nextButton.disabled = true;
    checkboxContainers.forEach(container => {
        container.classList.remove('checked');
    });
    radioInputs.forEach(radio => {
        radio.checked = false;
    });

    // ────────────────────────────────────────────
    // 2️⃣ اگر مقدار قبلی وجود داشت، بازنشانی کنید
    // ────────────────────────────────────────────
    if (state.formData.userInfo.menstrualStatus) {
        const prevValue = state.formData.userInfo.menstrualStatus;
        const prevRadio = document.querySelector(
            `input[name="menstrual-status"][value="${prevValue}"]`
        );
        
        if (prevRadio) {
            prevRadio.checked = true;
            const container = prevRadio.closest('.checkbox-container');
            if (container) {
                container.classList.add('checked');
                nextButton.disabled = false;
            }
        }
    }

    // ────────────────────────────────────────────
    // 3️⃣ Event listeners برای radio buttons
    // ────────────────────────────────────────────
    radioInputs.forEach(radio => {
        // جلوگیری از duplicate listeners
        radio.removeEventListener('change', handleMenstrualChange);
        
        // اضافه کردن listener جدید
        radio.addEventListener('change', handleMenstrualChange);
    });

    // ────────────────────────────────────────────
    // 4️⃣ Click on label برای toggle
    // ────────────────────────────────────────────
    checkboxContainers.forEach(container => {
        const label = container.querySelector('.checkbox-label');
        const radio = container.querySelector('input[type="radio"]');
        
        if (label && radio) {
            label.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                radio.click();
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });

    console.log('✅ Menstrual Status Selection Setup Complete');
};

// ────────────────────────────────────────────
// Handler تغییر Radio
// ────────────────────────────────────────────
window.handleMenstrualChange = function(event) {
    const radio = event.target;
    
    if (!radio.checked) return;

    const selectedValue = radio.value;
    const selectedContainer = radio.closest('.checkbox-container');
    const allContainers = document.querySelectorAll('#menstrual-status-selection .checkbox-container');
    const nextButton = document.querySelector('.next-step');

    // ────────────────────────────────────────────
    // حذف 'checked' از تمام containers
    // ────────────────────────────────────────────
    allContainers.forEach(container => {
        container.classList.remove('checked');
    });

    // ────────────────────────────────────────────
    // اضافه کردن 'checked' به container انتخاب شده
    // ────────────────────────────────────────────
    if (selectedContainer) {
        selectedContainer.classList.add('checked');
    }

    // ────────────────────────────────────────────
    // ذخیره مقدار در state
    // ────────────────────────────────────────────
    state.updateFormData('userInfo.menstrualStatus', selectedValue);

    // ────────────────────────────────────────────
    // فعال کردن دکمه Next
    // ────────────────────────────────────────────
    nextButton.disabled = false;

    console.log('✅ Menstrual Status:', selectedValue);
};

// ────────────────────────────────────────────
// Utility Functions
// ────────────────────────────────────────────

window.getMenstrualStatus = function() {
    const checked = document.querySelector('input[name="menstrual-status"]:checked');
    return checked ? checked.value : undefined;
};

window.setMenstrualStatus = function(status) {
    const validStatuses = ['not-set', 'regular', 'irregular', 'menopause', 'pregnancy'];
    
    if (!validStatuses.includes(status)) {
        console.warn(`⚠️ Invalid status: ${status}`);
        return false;
    }

    const radio = document.querySelector(`input[name="menstrual-status"][value="${status}"]`);
    if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }

    return false;
};

window.resetMenstrualStatusSelection = function() {
    document.querySelectorAll('input[name="menstrual-status"]').forEach(radio => {
        radio.checked = false;
    });
    
    document.querySelectorAll('#menstrual-status-selection .checkbox-container').forEach(container => {
        container.classList.remove('checked');
    });
    
    state.updateFormData('userInfo.menstrualStatus', undefined);
    document.querySelector('.next-step').disabled = true;

    console.log('🔄 Menstrual Status Reset');
};

window.setupComplexCheckboxSelection = function(step, config) {
    if (state.currentStep !== step) return;

    const elements = {
        noneCheckbox: document.getElementById(config.noneCheckboxId),
        nextButton: document.querySelector(".next-step")
    };

    // ساختاردهی گزینه‌ها
    config.options.forEach(option => {
        elements[option.key] = document.getElementById(option.id);
    });

    // مدیریت نمایش گزینه‌های زنانه
    if (config.genderDependent) {
        const femaleOnlyOptions = document.querySelectorAll('.female-only');
        if (state.formData.userInfo.gender === 'female') {
            femaleOnlyOptions.forEach(el => el.style.display = 'block');
        } else {
            femaleOnlyOptions.forEach(el => {
                el.style.display = 'none';
                const checkbox = el.querySelector('.real-checkbox');
                if (checkbox) checkbox.checked = false;
            });
        }
    }

    elements.nextButton.disabled = true;

    const validateForm = () => {
        let anyChecked = false;
        
        // بررسی انتخاب‌ها
        config.options.forEach(option => {
            if (elements[option.key]?.checked) {
                anyChecked = true;
            }
        });

        if (elements.noneCheckbox.checked) {
            anyChecked = true;
        }

        elements.nextButton.disabled = !anyChecked;
        
        // به‌روزرسانی state
        const selectedValues = [];
        config.options.forEach(option => {
            if (elements[option.key]?.checked) {
                selectedValues.push(option.key);
            }
        });

        if (elements.noneCheckbox.checked) {
            selectedValues.push('none');
        }

        state.updateFormData(config.dataKey, selectedValues);
    };

    const handleCheckboxChange = (checkbox) => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (label) {
                label.classList.add('checked-animation');
                setTimeout(() => {
                    label.classList.remove('checked-animation');
                    label.classList.toggle('checked', this.checked);
                }, 800);
            }
            validateForm();
        });
    };

    // مدیریت چک‌باکس "هیچکدام"
    elements.noneCheckbox.addEventListener('change', function() {
        if (this.checked) {
            config.options.forEach(option => {
                if (elements[option.key]) {
                    elements[option.key].checked = false;
                    const label = elements[option.key].nextElementSibling;
                    if (label) label.classList.remove('checked');
                }
            });
        }
        validateForm();
    });

    // مدیریت سایر چک‌باکس‌ها
    config.options.forEach(option => {
        if (elements[option.key]) {
            handleCheckboxChange(elements[option.key]);
            elements[option.key].addEventListener('change', function() {
                if (this.checked) {
                    elements.noneCheckbox.checked = false;
                    const label = elements.noneCheckbox.nextElementSibling;
                    if (label) label.classList.remove('checked');
                }
                validateForm();
            });
        }
    });

    validateForm();
};

window.setupActivitySelection = function(currentStep) {
    if (currentStep !== window.STEPS.ACTIVITY) return;

    const activityOptions = document.querySelectorAll('.activity-option');
    
    activityOptions.forEach(option => {
        option.addEventListener('click', function() {
            // حذف انتخاب از همه گزینه‌ها
            activityOptions.forEach(opt => {
                opt.classList.remove('selected');
                opt.style.transform = "";
                opt.style.boxShadow = "";
            });
            
            // انتخاب گزینه کلیک شده
            this.classList.add('selected');
            this.style.transform = "translateY(-3px)";
            this.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
            
            // ذخیره داده در state
            state.updateFormData('userInfo.activity', this.dataset.activity);
            
            // فعال کردن دکمه بعدی
            const nextButton = document.querySelector(".next-step");
            if (nextButton) nextButton.disabled = false;
        });
    });
    
    // اگر قبلاً activity انتخاب شده بود، آن را highlight کن
    if (state.formData.userInfo.activity) {
        const selectedOption = document.querySelector(`.activity-option[data-activity="${state.formData.userInfo.activity}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
            selectedOption.style.transform = "translateY(-3px)";
            selectedOption.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
        }
    }
};

window.setupSurgerySelection = function(currentStep) {
    
    window.setupAutoNavigateOnNoneCheckbox('surgery-none');
    
    if (state.currentStep !== currentStep) return;

    // تنظیم انتخاب‌های اصلی جراحی
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'surgery-none',
        dataKey: 'surgery',
        genderDependent: true,
        options: [
            { key: 'metabolic', id: 'surgery-metabolic' },
            { key: 'gallbladder', id: 'surgery-gallbladder' },
            { key: 'intestine', id: 'surgery-intestine' },
            { key: 'thyroid', id: 'surgery-thyroid' },
            { key: 'pancreas', id: 'surgery-pancreas' },
            { key: 'heart', id: 'surgery-heart' },
            { key: 'kidney', id: 'surgery-kidney' },
            { key: 'liver', id: 'surgery-liver' },
            { key: 'gynecology', id: 'surgery-gynecology' },
            { key: 'cancer', id: 'cancer-history' }
        ]
    });

    // مدیریت جزئیات سرطان
    setupCancerDetails();
};


// REPLACE WITH THIS:
window.setupMedicationsSelection = function(currentStep) {
    window.setupAutoNavigateOnNoneCheckbox('medications-none');

    setupComplexCheckboxSelection(
        currentStep,
        {
            noneCheckboxId: 'medications-none',
            dataKey: 'medications',
            options: [
                // Original medications
                { key: 'diabetesOral', id: 'medication-diabetes-oral' },     // ✅ تغییر
                { key: 'insulin', id: 'medication-insulin' },                 // ✅ جدید
                { key: 'thyroid', id: 'medication-thyroid' },
                { key: 'corticosteroids', id: 'medication-corticosteroids' },
                { key: 'anticoagulants', id: 'medication-anticoagulants' },
                { key: 'hypertension', id: 'medication-hypertension' },
                { key: 'psychiatric', id: 'medication-psychiatric' },
                { key: 'hormonal', id: 'medication-hormonal' },
                { key: 'cardiac', id: 'medication-cardiac' },
                { key: 'gastrointestinal', id: 'medication-gastrointestinal' },
                { key: 'supplements', id: 'medication-supplements' },
                
                // NEW medications
                { key: 'immunosuppressants', id: 'medication-immunosuppressants' },
                { key: 'cancer-oral', id: 'medication-cancer-oral' },
                { key: 'anticonvulsant', id: 'medication-anticonvulsant' },
                { key: 'weight-loss', id: 'medication-weight-loss' }
            ]
        }
    );
};

window.setupChronicConditionsSelection = function(currentStep) {
    window.setupAutoNavigateOnNoneCheckbox('chronic-none');
    
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'chronic-none',
        dataKey: 'chronicConditions',
        genderDependent: true,
        options: [
            { key: 'diabetes', id: 'chronic-diabetes' },
            { key: 'hypertension', id: 'chronic-hypertension' },
            { key: 'cholesterol', id: 'chronic-cholesterol' },
            { key: 'fattyLiver', id: 'chronic-fatty-liver' },
            { key: 'cirrhosis', id: 'chronic-cirrhosis' },           // ✅ جدید
            { key: 'hepatitis', id: 'chronic-hepatitis' },           // ✅ جدید            
            { key: 'insulinResistance', id: 'chronic-insulin-resistance' },
            { key: 'hypothyroidism', id: 'chronic-hypothyroidism' },
            { key: 'hyperthyroidism', id: 'chronic-hyperthyroidism' },
            { key: 'hashimoto', id: 'chronic-hashimoto' },
            { key: 'pcos', id: 'chronic-pcos' },
            { key: 'menopause', id: 'chronic-menopause' },
            { key: 'cortisol', id: 'chronic-cortisol' },
            { key: 'growth', id: 'chronic-growth' },
            { key: 'ibs', id: 'chronic-ibs' },
            { key: 'kidney', id: 'chronic-kidney' },
            { key: 'heart', id: 'chronic-heart' },
            { key: 'autoimmune', id: 'chronic-autoimmune' },
            { key: 'gallbladderStones', id: 'chronic-gallbladder-stones' },
            { key: 'gallbladderInflammation', id: 'chronic-gallbladder-inflammation' },
            { key: 'gallbladderIssues', id: 'chronic-gallbladder-issues' }            
        ]
    });
    
    // اضافه کردن event listener برای کنترل تناقض‌ها
    const conflictCheckboxes = [
        'chronic-hyperthyroidism', 'chronic-hypothyroidism', 'chronic-hashimoto',
        'chronic-gallbladder-stones', 'chronic-gallbladder-inflammation', 'chronic-gallbladder-issues'
    ];
    
    conflictCheckboxes.forEach(checkboxId => {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    handleConflictingConditions(checkboxId);
                }
            });
        }
    });
    
    setupChronicDiabetesDetails();
    setupChronicKidneyDetails();
    
  // ⭐ listener کلی برای kidney checkbox
  const kidneyCheckbox = document.getElementById('chronic-kidney');
  if (kidneyCheckbox) {
    kidneyCheckbox.addEventListener('change', validateChronicKidneyStep);
  }
};

/**
 * اعتبارسنجی مقادیر آزمایش
 */
function validateLabTestValue(testName, value) {
    const numericValue = parseFloat(value);
    
    if (isNaN(numericValue) || numericValue <= 0) {
        return { valid: false, reason: 'مقدار باید عدد مثبت باشد' };
    }
    
    // محدوده‌های منطقی برای آزمایش‌ها
    const ranges = {
        // قند خون
        'fasting blood sugar': { min: 50, max: 400, name: 'قند خون ناشتا' },
        'fbs': { min: 50, max: 400, name: 'قند خون ناشتا' },
        'blood sugar': { min: 50, max: 600, name: 'قند خون' },
        'bs': { min: 50, max: 600, name: 'قند خون' },
        
        // CBC - پارامترهای مهم برای رژیم
        'hemoglobin': { min: 10, max: 20, name: 'هموگلوبین' },
        'hgb': { min: 10, max: 20, name: 'هموگلوبین' },
        'hb': { min: 10, max: 20, name: 'هموگلوبین' },
        'red blood cells': { min: 3.5, max: 6.5, name: 'گلبول قرمز' },
        'rbc': { min: 3.5, max: 6.5, name: 'گلبول قرمز' },
        'mean corpuscular volume': { min: 70, max: 110, name: 'MCV' },
        'mcv': { min: 70, max: 110, name: 'MCV' },
        'white blood cells': { min: 4, max: 11, name: 'گلبول سفید' },
        'wbc': { min: 4, max: 11, name: 'گلبول سفید' },
        
        // انسولین 👈 اضافه شد
        'fasting insulin': { min: 1, max: 50, name: 'انسولین ناشتا' },
        'insulin': { min: 1, max: 50, name: 'انسولین' },
        'serum insulin': { min: 1, max: 50, name: 'انسولین سرم' },
        
        // سایر آزمایش‌ها
        'hba1c': { min: 3, max: 20, name: 'HbA1c' },
        'cholesterol': { min: 100, max: 500, name: 'کلسترول' },
        'triglyceride': { min: 30, max: 1000, name: 'تری‌گلیسیرید' },
        'ldl': { min: 30, max: 300, name: 'LDL' },
        'hdl': { min: 20, max: 150, name: 'HDL' },
        'sgot': { min: 5, max: 500, name: 'SGOT' },
        'sgpt': { min: 5, max: 500, name: 'SGPT' },
        'alt': { min: 5, max: 500, name: 'ALT' },
        'ast': { min: 5, max: 500, name: 'AST' },
        'creatinine': { min: 0.3, max: 15, name: 'کراتینین' },
        'bun': { min: 5, max: 150, name: 'BUN' },
        'urea': { min: 10, max: 300, name: 'اوره' },
        'tsh': { min: 0.1, max: 50, name: 'TSH' },
        't3': { min: 50, max: 300, name: 'T3' },
        't4': { min: 3, max: 25, name: 'T4' }
    };
    
    // تمیز کردن و نرمال‌سازی نام آزمایش
    const normalizedName = testName
        .toLowerCase()
        .trim()
        .replace(/\s+/g, ' ')
        .replace(/[()]/g, '')
        .replace(/\s*-\s*/g, ' ')
        .trim();
    
    console.log(`🔍 Validation برای: "${normalizedName}"`);
    
    let range = null;
    
    // 1. تطبیق دقیق
    if (ranges[normalizedName]) {
        range = ranges[normalizedName];
        console.log(`✅ تطبیق دقیق: ${normalizedName}`);
    } else {
        // 2. جستجوی هوشمند
        for (const [key, value] of Object.entries(ranges)) {
            if (normalizedName.includes(key) || key.includes(normalizedName)) {
                range = value;
                console.log(`✅ تطبیق جزئی: "${key}" در "${normalizedName}"`);
                break;
            }
        }
    }
    
    if (range) {
        if (numericValue < range.min || numericValue > range.max) {
            return {
                valid: false,
                reason: `${range.name} باید بین ${range.min} تا ${range.max} باشد`
            };
        }
    } else {
        console.warn(`⚠️ محدوده برای "${normalizedName}" تعریف نشده`);
        
        // محدوده عمومی برای آزمایش‌های ناشناخته
        if (numericValue > 1000000) {
            return { valid: false, reason: 'مقدار خیلی بزرگ است (حداکثر: 1,000,000)' };
        }
    }
    
    return { valid: true };
}

function setupChronicDiabetesDetails() {
  const diabetesCheckbox = document.getElementById('chronic-diabetes');
  const diabetesDetails = document.getElementById('chronic-diabetes-details');
  const diabetesAdditional = document.getElementById('chronic-diabetes-additional');
  const nextButton = document.querySelector('.next-step');
  
  if (!diabetesCheckbox || !diabetesDetails) return;
  
  diabetesCheckbox.addEventListener('change', function() {
    diabetesDetails.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) {
      state.updateFormData('userInfo.chronicDiabetesType', null);
      state.updateFormData('userInfo.chronicFastingBloodSugar', null);
      state.updateFormData('userInfo.chronicHba1c', null);
      if (diabetesAdditional) diabetesAdditional.style.display = 'none';
      resetChronicDiabetesSelections();
      validateChronicDiabetesStep();
    }
  });
  
  // ⭐ تم kidney برای diabetes-options
  document.querySelectorAll('.diabetes-option').forEach(option => {
    option.style.cssText = `
      cursor: pointer; padding: 8px; border-radius: 4px; 
      border: 1px solid transparent; transition: all 0.2s ease;
      font-size: 14px; display: flex; align-items: center; gap: 8px;
    `;
    
    option.addEventListener('mouseenter', function() {
      if (!this.classList.contains('selected')) {
        this.style.backgroundColor = '#f0f8f0';
        this.style.borderColor = '#a5d6a7';
      }
    });
    
    option.addEventListener('mouseleave', function() {
      if (!this.classList.contains('selected')) {
        this.style.backgroundColor = ''; this.style.borderColor = 'transparent';
      }
    });
    
    option.addEventListener('click', function(e) {
      e.preventDefault(); e.stopPropagation();
      
      document.querySelectorAll('.diabetes-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.style.backgroundColor = ''; opt.style.border = '1px solid transparent';
      });
      
      this.classList.add('selected');
      this.style.backgroundColor = '#e8f5e8';
      this.style.border = '2px solid #4CAF50';
      this.style.boxShadow = '0 2px 4px rgba(76, 175, 80, 0.2)';
      
      const diabetesType = this.dataset.value;
      state.updateFormData('userInfo.chronicDiabetesType', diabetesType);
      
      // ⭐ نمایش/مخفی input fields (عملکرد قبلی)
      if (diabetesType !== 'prediabetes' && diabetesAdditional) {
        diabetesAdditional.style.display = 'block';
      } else if (diabetesAdditional) {
        diabetesAdditional.style.display = 'none';
      }
      
      validateChronicDiabetesStep();
    });
  });
  
  // ⭐ input fields (قند خون + HbA1c) - کامل حفظ شد
  const fastingInput = document.getElementById('chronic-fasting-blood-sugar');
  const hba1cInput = document.getElementById('chronic-hba1c-level');
  
  if (fastingInput) {
    fastingInput.addEventListener('input', function() {
      state.updateFormData('userInfo.chronicFastingBloodSugar', this.value);
    });
    // مقدار قبلی
    if (state.formData.userInfo.chronicFastingBloodSugar) {
      fastingInput.value = state.formData.userInfo.chronicFastingBloodSugar;
    }
  }
  
  if (hba1cInput) {
    hba1cInput.addEventListener('input', function() {
      state.updateFormData('userInfo.chronicHba1c', this.value);
    });
    // مقدار قبلی
    if (state.formData.userInfo.chronicHba1c) {
      hba1cInput.value = state.formData.userInfo.chronicHba1c;
    }
  }
  
  // Highlight انتخاب قبلی
  if (state.formData.userInfo.chronicDiabetesType) {
    const selectedOption = document.querySelector(`.diabetes-option[data-value="${state.formData.userInfo.chronicDiabetesType}"]`);
    if (selectedOption) {
      selectedOption.classList.add('selected');
      selectedOption.style.backgroundColor = '#e8f5e8';
      selectedOption.style.border = '2px solid #4CAF50';
      selectedOption.style.boxShadow = '0 2px 4px rgba(76, 175, 80, 0.2)';
      if (state.formData.userInfo.chronicDiabetesType !== 'prediabetes' && diabetesAdditional) {
        diabetesAdditional.style.display = 'block';
      }
    }
  }
}

function validateChronicDiabetesStep() {
  const nextButton = document.querySelector('.next-step');
  if (!nextButton) return;
  
  const diabetesCheckbox = document.getElementById('chronic-diabetes');
  if (diabetesCheckbox?.checked && !state.formData.userInfo.chronicDiabetesType) {
    nextButton.disabled = true;
  }
}

// جایگزین کامل validateChronicKidneyStep + اضافه کردن به setupChronicConditionsSelection
function validateChronicKidneyStep() {
  const nextButton = document.querySelector('.next-step');
  const kidneyCheckbox = document.getElementById('chronic-kidney');
  const kidneyDetails = document.getElementById('chronic-kidney-details');
  
  if (!nextButton) return;
  
  if (kidneyCheckbox?.checked && !state.formData.userInfo.chronicKidneyStage) {
    // ⭐ Next غیرفعال + Warning
    nextButton.disabled = true;
    nextButton.style.backgroundColor = '#f44336';
    nextButton.textContent = 'لطفاً مرحله بیماری کلیوی را انتخاب کنید';
    
    // Highlight kidney section
    if (kidneyDetails) {
      kidneyDetails.scrollIntoView({ behavior: 'smooth', block: 'center' });
      kidneyDetails.style.border = '2px solid #f44336';
      kidneyDetails.style.boxShadow = '0 0 10px rgba(244, 67, 54, 0.3)';
      setTimeout(() => {
        kidneyDetails.style.border = '';
        kidneyDetails.style.boxShadow = '';
      }, 3000);
    }
  } else {
    // حالت عادی
    nextButton.disabled = false;
    nextButton.style.backgroundColor = '';
    nextButton.textContent = 'ادامه';
  }
}

// اصلاح setupChronicKidneyDetails - فراخوانی مداوم validation
function setupChronicKidneyDetails() {
  const kidneyCheckbox = document.getElementById('chronic-kidney');
  const kidneyDetails = document.getElementById('chronic-kidney-details');
  const nextButton = document.querySelector('.next-step');
  
  if (!kidneyCheckbox || !kidneyDetails) return;
  
  kidneyCheckbox.addEventListener('change', function() {
    kidneyDetails.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) {
      state.updateFormData('userInfo.chronicKidneyStage', null);
    }
    // ⭐ هر بار validation
    validateChronicKidneyStep();
  });
  
  // هر انتخاب kidney-option → validation
  document.querySelectorAll('.kidney-option').forEach(option => {
    option.style.cursor = 'pointer';
    option.style.padding = '8px';
    option.style.borderRadius = '4px';
    option.style.transition = 'all 0.2s';
    
    option.addEventListener('click', function() {
      document.querySelectorAll('.kidney-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.style.backgroundColor = '';
        opt.style.border = '1px solid transparent';
      });
      
      this.classList.add('selected');
      this.style.backgroundColor = '#e8f5e8';
      this.style.border = '2px solid #4CAF50';
      this.style.boxShadow = '0 2px 4px rgba(76, 175, 80, 0.2)';
      
      state.updateFormData('userInfo.chronicKidneyStage', this.dataset.value);
      // ⭐ هر بار validation
      validateChronicKidneyStep();
    });
  });
  
  // Highlight قبلی
  if (state.formData.userInfo.chronicKidneyStage) {
    const selectedOption = document.querySelector(`.kidney-option[data-value="${state.formData.userInfo.chronicKidneyStage}"]`);
    if (selectedOption) {
      selectedOption.classList.add('selected');
      selectedOption.style.backgroundColor = '#e8f5e8';
      selectedOption.style.border = '2px solid #4CAF50';
      selectedOption.style.boxShadow = '0 2px 4px rgba(76, 175, 80, 0.2)';
    }
  }
  
  // ⭐ validation اولیه
  validateChronicKidneyStep();
}

window.setupCancerDetails = function() {
    const cancerCheckbox = document.getElementById('cancer-history');
    const cancerDetails = document.getElementById('cancer-details');
    const nextButton = document.querySelector(".next-step");

    if (!cancerCheckbox || !cancerDetails) return;

    // مدیریت نمایش/مخفی کردن جزئیات سرطان
    cancerCheckbox.addEventListener('change', function() {
        cancerDetails.style.display = this.checked ? 'block' : 'none';
        
        // اگر سرطان انتخاب نشد، اطلاعات سرطان را پاک کنید
        if (!this.checked) {
            state.updateFormData('userInfo.cancerTreatment', '');
            state.updateFormData('userInfo.cancerType', '');
            resetCancerSelections();
        }
        
        // به‌روزرسانی وضعیت دکمه
        validateNextButton();
    });

    // مدیریت انتخاب وضعیت درمان و نوع سرطان
    const cancerOptions = document.querySelectorAll('.cancer-option[data-value]');
    cancerOptions.forEach(option => {
        option.addEventListener('click', function() {
            const category = this.closest('.cancer-options');
            if (!category) return;

            // فقط یک گزینه در هر دسته می‌تواند انتخاب شود
            category.querySelectorAll('.cancer-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            this.classList.add('selected');

            // تشخیص نوع داده (درمان یا نوع سرطان)
            const isTreatment = category.querySelector('.cancer-option[data-value="chemo"]');
            if (isTreatment) {
                state.updateFormData('userInfo.cancerTreatment', this.dataset.value);
            } else {
                state.updateFormData('userInfo.cancerType', this.dataset.value);
            }
            
            // به‌روزرسانی وضعیت دکمه
            validateNextButton();
        });
    });

    function validateNextButton() {
        if (cancerCheckbox.checked) {
            const hasTreatment = state.formData.userInfo.cancerTreatment !== '';
            const hasType = state.formData.userInfo.cancerType !== '';
            
            // اگر سرطان انتخاب شده، باید هر دو فیلد پر شوند
            nextButton.disabled = !(hasTreatment && hasType);
        } else {
            // اگر سرطان انتخاب نشده، وضعیت دکمه توسط تابع اصلی مدیریت می‌شود
            const surgeryConfig = {
                noneCheckboxId: 'surgery-none',
                options: [
                    { key: 'metabolic', id: 'surgery-metabolic' },
                    { key: 'gallbladder', id: 'surgery-gallbladder' },
                    { key: 'intestine', id: 'surgery-intestine' },
                    { key: 'thyroid', id: 'surgery-thyroid' },
                    { key: 'pancreas', id: 'surgery-pancreas' },
                    { key: 'heart', id: 'surgery-heart' },
                    { key: 'kidney', id: 'surgery-kidney' },
                    { key: 'liver', id: 'surgery-liver' },
                    { key: 'gynecology', id: 'surgery-gynecology' },
                    { key: 'cancer', id: 'cancer-history' }
                ]
            };
            
            const noneChecked = document.getElementById(surgeryConfig.noneCheckboxId).checked;
            const anyOtherChecked = surgeryConfig.options.some(option => {
                if (option.key === 'cancer') return false; // سرطان جداگانه بررسی می‌شود
                const element = document.getElementById(option.id);
                return element ? element.checked : false;
            });
            
            nextButton.disabled = !(noneChecked || anyOtherChecked);
        }
    }

    function resetCancerSelections() {
        document.querySelectorAll('.cancer-option.selected').forEach(opt => {
            opt.classList.remove('selected');
        });
    }

    // بررسی اولیه
    validateNextButton();
};

window.setupDigestiveConditionsSelection = function(currentStep) {
    window.setupAutoNavigateOnNoneCheckbox('digestive-none');
    
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'digestive-none',
        dataKey: 'digestiveConditions',
        options: [
            // بیماری‌های ساختاری
            { key: 'ibs', id: 'digestive-ibs' },
            { key: 'ibd', id: 'digestive-ibd' },
            { key: 'gerd', id: 'digestive-gerd' },
            
            // علائم عملکردی
            { key: 'bloating', id: 'digestive-bloating' },
            { key: 'pain', id: 'digestive-pain' },
            { key: 'heartburn', id: 'digestive-heartburn' },
            { key: 'constipation', id: 'digestive-constipation' },
            { key: 'diarrhea', id: 'digestive-diarrhea' },
            { key: 'fullness', id: 'digestive-fullness' },
            { key: 'nausea', id: 'digestive-nausea' },
            { key: 'slow-digestion', id: 'digestive-slow-digestion' },
            { key: 'indigestion', id: 'digestive-indigestion' },
            
            // عفونت‌ها و مشکلات خاص
            { key: 'helicobacter', id: 'digestive-helicobacter' },
        ]
    });
};

window.setupDietStyleSelection = function(currentStep) {
    // فعال‌کردن رفتن خودکار به مرحله بعد روی none
    window.setupAutoNavigateOnNoneCheckbox('diet-style-none');

    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'diet-style-none',
        dataKey: 'dietStyle',
        options: [
            { key: 'vegetarian', id: 'diet-style-vegetarian' },
            { key: 'vegan', id: 'diet-style-vegan' }
        ]
    });
};


window.setupFoodLimitationsSelection = function(currentStep) {
    window.setupAutoNavigateOnNoneCheckbox('limitations-none');

    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'limitations-none',
        dataKey: 'foodLimitations',
        options: [
            // محدودیت‌های پزشکی
            { key: 'celiac', id: 'limitation-celiac' },
            { key: 'lactose', id: 'limitation-lactose' },
            { key: 'seafood-allergy', id: 'limitation-seafood-allergy' },
            { key: 'eggs-allergy', id: 'limitation-eggs-allergy' },
            { key: 'nuts-allergy', id: 'limitation-nuts-allergy' },
            
            // ترجیحات شخصی
            { key: 'no-seafood', id: 'limitation-no-seafood' },
            { key: 'no-redmeat', id: 'limitation-no-redmeat' },
            { key: 'no-dairy', id: 'limitation-no-dairy' }
        ]
    });
};

window.setupWaterIntakeSelection = function(currentStep) {
    if (currentStep !== window.STEPS.WATER_INTAKE) return;

    const waterCups = document.querySelectorAll('.water-cup');
    const waterAmountDisplay = document.getElementById('water-amount');
    const waterLiterDisplay = document.getElementById('water-liter');
    const waterAmountText = document.getElementById('water-amount-text');
    const dontKnowCheckbox = document.getElementById('water-dont-know');
    const dontKnowText = document.getElementById('water-dont-know-text');
    const nextButton = document.querySelector('.next-step');
    
    nextButton.disabled = true;

    const updateNextButtonState = () => {
        const hasSelection = document.querySelector('.water-cup.selected') !== null;
        const isDontKnowChecked = dontKnowCheckbox.checked;
        nextButton.disabled = !(hasSelection || isDontKnowChecked);
    };

    const updateWaterDisplay = (amount, isDontKnow = false) => {
        if (isDontKnow) {
            waterAmountText.style.display = 'none';
            dontKnowText.style.display = 'block';
            state.updateFormData('userInfo.waterIntake', null);
        } else {
            waterAmountDisplay.textContent = amount;
            waterLiterDisplay.textContent = (amount * 0.25).toFixed(1); // محاسبه لیتر (هر لیوان 250 سی‌سی)
            waterAmountText.style.display = 'flex';
            dontKnowText.style.display = 'none';
            state.updateFormData('userInfo.waterIntake', amount);
        }
        updateNextButtonState();
    };

    waterCups.forEach((cup, index) => {
        cup.addEventListener('click', function() {
            dontKnowCheckbox.checked = false;
            document.querySelector('.stand-alone-none .checkbox-label').classList.remove('checked');
            
            const amount = parseInt(this.dataset.amount);
            
            // Reset all cups
            waterCups.forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.water-wave')?.remove();
            });
            
            // Select cups up to clicked amount
            for (let i = 0; i < amount; i++) {
                waterCups[i].classList.add('selected');
            }
            
            updateWaterDisplay(amount);
        });
    });

    dontKnowCheckbox.addEventListener('change', function() {
        const label = this.nextElementSibling;
        if (this.checked) {
            label.classList.add('checked-animation');
            setTimeout(() => {
                label.classList.remove('checked-animation');
                label.classList.add('checked');
            }, 800);
            
            // Reset all cups
            waterCups.forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.water-wave')?.remove();
            });
            
            updateWaterDisplay(0, true);
        } else {
            label.classList.remove('checked');
            updateWaterDisplay(0, false);
            updateNextButtonState();
        }
    });

    // بررسی اولیه وضعیت دکمه
    updateNextButtonState();
};

window.setupTermsAgreement = function(currentStep) {
    if (currentStep !== window.STEPS.TERMS_AGREEMENT) return;

    const nextButton = document.querySelector(".next-step");
    const agreeCheckbox = document.getElementById("agree-terms");
    
    // Reset state
    agreeCheckbox.checked = false;
    nextButton.disabled = true;

    agreeCheckbox.addEventListener("change", function() {
        const label = this.nextElementSibling;
        
        if (this.checked) {
            label.classList.add("checked-animation");
            setTimeout(() => {
                label.classList.remove("checked-animation");
                label.classList.add("checked");
            }, 800);
        } else {
            label.classList.remove("checked");
        }
        
        nextButton.disabled = !this.checked;
    });
}

window.setupConfirmationCheckbox = function(currentStep) {
    const submitButton = document.querySelector(".submit-form");
    const confirmCheckbox = document.getElementById("confirm-info");
    
    if (currentStep !== window.STEPS.CONFIRMATION) return;

    submitButton.disabled = !confirmCheckbox.checked;
    if (confirmCheckbox.checked) {
        confirmCheckbox.nextElementSibling.classList.add("checked");
    }

    const validateForm = () => {
        submitButton.disabled = !confirmCheckbox.checked;
    };

    confirmCheckbox.addEventListener("change", function() {
        const label = this.nextElementSibling;
        
        if (this.checked) {
            label.classList.add("checked-animation");
            setTimeout(() => {
                label.classList.remove("checked-animation");
                label.classList.add("checked");
            }, 800);
        } else {
            label.classList.remove("checked");
        }
        
        validateForm();
    });

    validateForm();
}

// در تابع setupExerciseSelection
window.setupExerciseSelection = function(currentStep) {
    if (currentStep !== window.STEPS.EXERCISE) return;

    const exerciseOptions = document.querySelectorAll('.exercise-option');
    
    // اگر قبلاً ورزشی انتخاب شده بود، آن را highlight کن
    if (state.formData.userInfo.exercise) {
        const selectedOption = document.querySelector(`.exercise-option[data-exercise="${state.formData.userInfo.exercise}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
            selectedOption.style.transform = "translateY(-3px)";
            selectedOption.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
        }
    }
    
    exerciseOptions.forEach(option => {
        option.addEventListener('click', function() {
            // حذف انتخاب از همه گزینه‌ها
            exerciseOptions.forEach(opt => {
                opt.classList.remove('selected');
                opt.style.transform = "";
                opt.style.boxShadow = "";
            });
            
            // انتخاب گزینه کلیک شده
            this.classList.add('selected');
            this.classList.add('selected-with-effect');
            state.updateFormData('userInfo.exercise', this.dataset.exercise);
            // افکت بصری
            setTimeout(() => {
                this.classList.remove('selected-with-effect');
                this.style.transform = "translateY(-3px)";
                this.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
                
                // ذخیره داده
                // state.updateFormData('userInfo.exercise', this.dataset.exercise);
                
            }, 150);
        });
    });
};

// به‌روزرسانی setupHeightWeightInput برای مرحله ترکیبی
window.setupHeightWeightInput = function(currentStep) {
    if (currentStep !== window.STEPS.HEIGHT_WEIGHT) return;
    
    const heightInput = document.getElementById('height-input');
    const weightInput = document.getElementById('weight-input');
    
    // فوکوس روی اولین فیلد خالی
    if (!state.formData.userInfo.height) {
        heightInput.focus();
    } else if (!state.formData.userInfo.weight) {
        weightInput.focus();
    }
    
    // اگر هر دو مقدار از قبل وجود داشته باشد، BMI را محاسبه کن
    if (state.formData.userInfo.height && state.formData.userInfo.weight) {
        calculateBMI(state.formData.userInfo.height, state.formData.userInfo.weight);
    }
    
    // Validate step
    validateHeightWeight();
};

window.setupTargetWeightToggle = function () {
    const toggle    = document.getElementById('enable-target-weight');
    const container = document.querySelector('.target-weight-container');
    const input     = document.getElementById('target-weight-input');
    const display   = document.getElementById('target-weight-display');
    const nextButton = document.querySelector('.next-step');
    if (!toggle || !container || !input || !nextButton || !display) return;

    const originalDisplayText = display.dataset.originalText || display.textContent;

    const resetTargetWeight = () => {
        input.value = '';
        display.textContent = originalDisplayText;
        display.style.color = 'var(--light-text-color)';
        if (window.state && window.state.updateFormData) {
            window.state.updateFormData('targetWeight', null);
        }
    };

    const updateState = () => {
        if (toggle.checked) { // ON
            container.classList.remove('disabled');
            input.disabled = false;

            if (input.value.trim().length === 0) {
                nextButton.disabled = false;
            } else {
                nextButton.disabled = true;
            }

            // این را اضافه کن: فوکوس اتومات روی input
            setTimeout(() => {
                input.focus();
                // اگر خواستی کرسر برود آخر مقدار:
                const len = input.value.length;
                try {
                    input.setSelectionRange(len, len);
                } catch (e) {}
            }, 50);
        } else {              // OFF
            container.classList.add('disabled');
            input.disabled = true;
            resetTargetWeight();
            nextButton.disabled = false;
        }
    };

    toggle.addEventListener('change', updateState);
    updateState();
};


window.showStep = function(step) {

    const stepElements = [
        "",                             // index 0 (unused - padding)
        "gender-selection-step",        // index 1 = step 1
        "menstrual-status-step",        // index 2 = step 2
        "personal-info-step",           // index 3 = step 3
        "goal-selection-step",          // index 4 = step 4
        "height-weight-input-step",     // index 5 = step 5
        "target-weight-step",           // index 6 = step 6
        "chronic-conditions-step",      // index 7 = step 7
        "medications-step",             // index 8 = step 8
        "digestive-conditions-step",    // index 9 = step 9
        "surgery-step",                 // index 10 = step 10
        "lab-test-upload-step",
        "water-intake-step",            // index 11 = step 11
        "activity-selection-step",      // index 12 = step 12
        "exercise-activity-step",       // index 13 = step 13
        "diet-style-step",              // index 14 = step 14
        "food-limitations-step",        // index 15 = step 15
        "diet-type-selection-step",     // index 16 = step 16
        "terms-agreement-step",         // index 17 = step 17
        "confirm-submit-step"           // index 18 = step 18
    ];
    
    document.querySelectorAll(".step").forEach(el => {
        el.classList.remove("active");
        if (el.id === "goal-weight-display") {
            el.style.display = 'none';
            if (!el.classList.contains("active")) {
                el.querySelector('.step7-image-container').innerHTML = '';
            }
        }
    });
    
    const currentStepElement = document.getElementById(stepElements[step]);
    if (currentStepElement) {
        currentStepElement.classList.add("active");
        if (currentStepElement.id === "goal-weight-display") {
            currentStepElement.style.display = 'flex';
        }
    }
    
    // مدیریت نمایش دکمه بعدی
    const nextButtonContainer = document.getElementById("next-button-container");
    if (nextButtonContainer) {
        // مخفی کردن دکمه "گام بعد" در مراحل خاص
        const hideNextButtonSteps = [
            window.STEPS.GENDER, 
            window.STEPS.GOAL,
            window.STEPS.WATER_INTAKE,
            window.STEPS.ACTIVITY, 
            window.STEPS.EXERCISE
        ];
        
        nextButtonContainer.style.display = hideNextButtonSteps.includes(step) ? "none" : "block";
        
        // مخفی کردن دکمه در مرحله آخر اصلی
        if (step === totalSteps) { 
            nextButtonContainer.style.display = "none";
        }
    }

    // مدیریت نمایش دکمه ارسال
    const submitButtonContainer = document.getElementById("submit-button-container");
    if (submitButtonContainer) {
        // نمایش دکمه ارسال فقط در مرحله تأیید نهایی
        submitButtonContainer.style.display = (step === window.STEPS.CONFIRMATION) ? "block" : "none";
    }
    
    // فوکوس خودکار برای input های خاص
    if ([window.STEPS.PERSONAL_INFO].includes(step)) {
        setTimeout(() => {
            let inputElement = null;
            
            if (step === window.STEPS.PERSONAL_INFO) {
                // فوکوس روی first-name-input
                inputElement = document.getElementById('full-name-input');
            } 
            
            if (inputElement) {
                inputElement.focus();
                // اسکرول به input (اختیاری)
                inputElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }
        }, 300);
        
        const nextButton = document.querySelector('.next-step');
        if (nextButton) {
            nextButton.disabled = true;
        }
        validateStep(step);
    }
    
    if (step === window.STEPS.HEIGHT_WEIGHT) {
        setupHeightWeightInput(step);
        document.getElementById('next-button-container').style.display = 'block';
    }
    else if (step === window.STEPS.TARGET_WEIGHT) {
        setupTargetWeightToggle();
    }
    else if (step === window.STEPS.WATER_INTAKE) {
        setupWaterIntakeSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === window.STEPS.DIGESTIVE_CONDITIONS) {
        setupDigestiveConditionsSelection(step);
    }
    else if (step === window.STEPS.SURGERY) {
        setupSurgerySelection(step);
    }
    else if (step === window.STEPS.LABTESTUPLOAD) {
        setupLabTestUpload(step);
        document.getElementById('next-button-container').style.display = 'block';
    }
    else if (step === window.STEPS.EXERCISE) {
        setupExerciseSelection(step);
    }
    else if (step === window.STEPS.DIET_STYLE) {
        setupDietStyleSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === window.STEPS.MENSTRUAL_STATUS) {
        const currentGender = state.formData.userInfo.gender;
        
        if (currentGender !== 'female') {
            // ❌ مردان: redirect خودکار
            navigateToStep(window.STEPS.PERSONAL_INFO);
            return;  // ← خروج! مرحله نمایش نمی‌یابد
        }        
        window.setupMenstrualStatusSelection(step);    
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === window.STEPS.CHRONIC_CONDITIONS) {
        setupChronicConditionsSelection(step);
    } 
    else if (step === window.STEPS.MEDICATIONS) {
        setupMedicationsSelection(step);
    } 
    else if (step === window.STEPS.ACTIVITY) {
        setupActivitySelection(step);
        document.getElementById("next-button-container").style.display = "none";
    }    
    else if (step === window.STEPS.FOOD_LIMITATIONS) {
        setupFoodLimitationsSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === window.STEPS.DIET_TYPE_SELECTION) {
        setupDietTypeSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === window.STEPS.TERMS_AGREEMENT) {
        setupTermsAgreement(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === window.STEPS.CONFIRMATION) {
        showSummary();
        setupConfirmationCheckbox(step);
        document.getElementById("next-button-container").style.display = "none";
        document.getElementById("submit-button-container").style.display = "block";
        
        const confirmCheckbox = document.getElementById("confirm-info");
        const submitButton = document.querySelector(".submit-form");
        if (submitButton) {
            submitButton.disabled = !confirmCheckbox.checked;
        }
    }
}

window.updateStepCounter = function(step) {
    if (step <= totalSteps) {
        document.getElementById("current-step").textContent = step;
        document.getElementById("total-steps").textContent = totalSteps;
    }
    else {
        document.getElementById("current-step").textContent = totalSteps;
        document.getElementById("total-steps").textContent = totalSteps;
    }
}

window.updateProgressBar = function(step) {
    let progress;
    
    if (step <= totalSteps) {
        progress = ((step - 1) / (totalSteps - 1)) * 100;
    }
    else {
        progress = 100;
    }
    
    document.getElementById("progress-bar").style.width = `${progress}%`;
}

window.navigateToStep = function(step) {

    const maxMainStep = totalSteps;
    
    if (step >= 1 && step <= maxMainStep) {
        state.updateStep(step);
        history.pushState({ step: state.currentStep }, "", `#step-${state.currentStep}`);
    }
    
    else if (step > maxMainStep && step <= Object.keys(STEPS).length) {
        state.updateStep(step);
        history.pushState({ step: state.currentStep }, "", `#step-${state.currentStep}`);
    }
}

window.handleNextStep = function() {
    if (state.currentStep === totalSteps) { 
        navigateToStep(window.STEPS.DIET_TYPE_SELECTION); 
    }
    else if (state.currentStep === window.STEPS.DIET_TYPE_SELECTION) {
        navigateToStep(window.STEPS.TERMS_AGREEMENT); 
    }
    else if (state.currentStep === window.STEPS.TERMS_AGREEMENT) {
        navigateToStep(window.STEPS.CONFIRMATION); 
    }
    // در غیر این صورت به مرحله بعدی اصلی برو
    else if (state.currentStep < totalSteps) {
        navigateToStep(state.currentStep + 1);
    }
}

window.handleEnterKey = function(event) {
    // فقط در مراحل عددی (سن، قد، وزن، وزن هدف) و مرحله نهایی اجازه کار با Enter را بده
    const allowedSteps = [
        window.STEPS.PERSONAL_INFO,
        window.STEPS.CONFIRMATION
    ];
    
    if (event.key === "Enter" && 
        allowedSteps.includes(state.currentStep) && 
        (event.target.matches("input[type='text']") || state.currentStep === window.STEPS.CONFIRMATION)) {
        console.log('handleEnterKey: ' + state.currentStep);
        // جلوگیری از رفتار پیش‌فرض Enter
        event.preventDefault();
        
        // در مراحل عددی، رفتن به مرحله بعد
        if (state.currentStep !== window.STEPS.CONFIRMATION) {
            document.querySelector(".next-step").click();
        } 
        // در مرحله نهایی، ارسال فرم
        else {
            const submitButton = document.querySelector(".submit-form:not([disabled])");
            if (submitButton) {
                submitButton.click();
            }
        }
    } else if (event.key === "Enter") {
        // جلوگیری از کار Enter در سایر مراحل
        event.preventDefault();
    }
}

// در تابع setupDietTypeSelection، بعد از انتخاب یک کارت
window.setupDietTypeSelection = function(currentStep) {
    if (currentStep !== window.STEPS.DIET_TYPE_SELECTION) return;

    const dietTypeCards = document.querySelectorAll('.diet-type-card');
    const nextButton = document.querySelector(".next-step");
    
    nextButton.disabled = true;

    // اعمال استایل اولیه بر روی همه کارت‌ها
    dietTypeCards.forEach(card => {
        card.classList.remove('selected');
        updateCardAppearance(card);
    });

    dietTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            // حذف انتخاب از همه کارت‌ها
            dietTypeCards.forEach(c => {
                c.classList.remove('selected');
                updateCardAppearance(c);
            });
            
            // انتخاب کارت کلیک شده
            this.classList.add('selected');
            updateCardAppearance(this);
            
            const dietType = this.dataset.dietType;
            state.updateFormData('serviceSelection.dietType', dietType);
            
            if (dietType === 'ai-only') {
                state.updateFormData('serviceSelection.selectedSpecialist', null);
                nextButton.disabled = false;
            } else if (dietType === 'with-specialist') {
                openSpecialistPopup();
            }
        });
    });
    
    // تابع برای به‌روزرسانی ظاهر کارت
    function updateCardAppearance(card) {
        if (card.classList.contains('selected')) {
            card.style.transform = "translateY(-5px)";
            card.style.opacity = "1";
            card.style.filter = "grayscale(0)";
        } else {
            card.style.transform = "scale(0.95)";
            card.style.opacity = "0.7";
            card.style.filter = "grayscale(0.3)";
        }
    }
    
    // اگر قبلاً نوع رژیم انتخاب شده بود، آن را highlight کن
    if (state.formData.serviceSelection.dietType) {
        const selectedCard = document.querySelector(`.diet-type-card[data-diet-type="${state.formData.serviceSelection.dietType}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
            updateCardAppearance(selectedCard);
        }
    }
};

// توابع جدید برای مدیریت پاپ‌آپ مشاور
window.openSpecialistPopup = function() {
    const popup = document.getElementById('specialist-popup');
    resetSpecialistPopup();
    popup.style.display = 'flex';
    loadNutritionConsultantsPopup();
};

function resetSpecialistPopup() {
    // پاک کردن انتخاب‌های قبلی در پاپ‌آپ
    document.querySelectorAll('.specialist-card-popup').forEach(card => {
        card.classList.remove('selected');
    });
    
    // مخفی کردن و خالی کردن اطلاعات متخصص انتخاب شده
    const specialistInfo = document.getElementById('selected-specialist-info');
    const specialistDetails = document.getElementById('specialist-details');
    
    specialistInfo.style.display = 'none';
    specialistDetails.innerHTML = '';
    
    // غیرفعال کردن دکمه تأیید
    const confirmBtn = document.querySelector('.popup-confirm-btn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
    }
    
    // اگر می‌خواهید state هم ریست شود (اختیاری):
    state.updateFormData('serviceSelection.selectedSpecialist', null);
}

window.closeSpecialistPopup = function() {
    const popup = document.getElementById('specialist-popup');
    popup.style.display = 'none';
    
    // غیرفعال کردن دکمه مرحله بعد
    const nextButton = document.querySelector(".next-step");
    if (!state.formData.serviceSelection.selectedSpecialist) {
        nextButton.disabled = true;
    }
};

window.confirmSpecialistSelection = function() {
    if (state.formData.serviceSelection.selectedSpecialist) {
        closeSpecialistPopup();
        // فعال کردن دکمه مرحله بعد
        const nextButton = document.querySelector(".next-step");
        nextButton.disabled = false;
        
        // به روزرسانی نوع رژیم
        state.updateFormData('serviceSelection.dietType', 'with-specialist');
    } else {
        console.error('No specialist selected');
        alert('لطفاً یک متخصص را انتخاب کنید');
    }
};

function loadNutritionConsultantsPopup() {
    const specialistSelection = document.getElementById('specialist-selection-popup');
    
    resetSpecialistPopup();
    
    // ✅ چک کردن cache
    if (window.consultantsCache) {
        renderConsultantsList(window.consultantsCache);
        return;
    }
    
    // ✅ جلوگیری از درخواست همزمان
    if (window.isFetchingConsultants) {
        return;
    }
    
    // ✅ نمایش Loading
    specialistSelection.innerHTML = `
        <div class="loading-specialists">
            <div class="loading-spinner"></div>
            <p>در حال بارگذاری لیست متخصصین...</p>
        </div>
    `;
    
    window.isFetchingConsultants = true;
    
    fetch(aiAssistantVars.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'get_nutrition_consultants',
            'security': aiAssistantVars.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        window.isFetchingConsultants = false;
        
        if (data.success && data.data.consultants && data.data.consultants.length > 0) {
            // ✅ ذخیره در cache
            window.consultantsCache = data.data.consultants;
            
            // ✅ رندر لیست
            renderConsultantsList(window.consultantsCache);
        } else {
            specialistSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">هیچ متخصص فعالی یافت نشد</div>';
        }
    })
    .catch(error => {
        window.isFetchingConsultants = false;
        console.error('❌ Error loading consultants:', error);
        specialistSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #f44336;">خطا در ارتباط با سرور</div>';
    });
}

// ==========================================
// Render Consultants List
// ==========================================
function renderConsultantsList(consultants) {
    const specialistSelection = document.getElementById('specialist-selection-popup');
    
    if (!consultants || consultants.length === 0) {
        specialistSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">هیچ متخصصی یافت نشد</div>';
        return;
    }
    
    specialistSelection.innerHTML = '';
    
    consultants.forEach(consultant => {
        const specialistCard = document.createElement('div');
        specialistCard.className = 'specialist-card-popup';
        specialistCard.dataset.specialistId = consultant.id;
        specialistCard.innerHTML = `
            <div class="specialist-info-popup">
                <div class="specialist-name-popup">${consultant.name}</div>
                <div class="specialist-specialty-popup">${consultant.specialty}</div>
                <div class="specialist-price-popup">+${new Intl.NumberFormat('fa-IR').format(consultant.consultation_price)} تومان</div>
            </div>
            <button type="button" class="select-specialist-btn-popup" onclick="selectSpecialistInPopup(${consultant.id}, '${consultant.name.replace(/'/g, "\\'")}', '${consultant.specialty.replace(/'/g, "\\'")}', ${consultant.consultation_price})">
                انتخاب
            </button>
        `;
        specialistSelection.appendChild(specialistCard);
    });
    
}


window.selectSpecialistInPopup = function(specialistId, specialistName, specialty, consultationPrice) {
    document.querySelectorAll('.specialist-card-popup').forEach(card => card.classList.remove('selected'));
    
    const selectedCard = document.querySelector(`.specialist-card-popup[data-specialist-id="${specialistId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    state.updateFormData('serviceSelection.selectedSpecialist', {
        id: parseInt(specialistId),
        name: specialistName,
        specialty: specialty,
        consultationprice: parseInt(consultationPrice)
    });
    
    // 🆕 بروزرسانی قیمت نهایی در کارت "رژیم با تأیید متخصص"
    updateSpecialistTotalPrice(parseInt(consultationPrice));
    
    const specialistInfo = document.getElementById('selected-specialist-info');
    const specialistDetails = document.getElementById('specialist-details');
    
    specialistDetails.innerHTML = `
        <div><strong>${specialistName}</strong></div>
        <div style="color: #666; font-size: 0.9em; margin: 5px 0;">${specialty}</div>
        <div style="color: #4CAF50; font-weight: bold; font-size: 0.9em;">
            ${new Intl.NumberFormat('fa-IR').format(consultationPrice)} تومان
        </div>
    `;
    
    specialistInfo.style.display = 'block';
    
    const confirmBtn = document.querySelector('.popup-confirm-btn');
    confirmBtn.disabled = false;
};

/**
 * به‌روزرسانی جزئیات قیمت با نمایش دو سطری
 * @param {number} consultationPrice - قیمت مشاوره متخصص
 */
function updateSpecialistTotalPrice(consultationPrice) {
    const state = window.state;
    const servicePrices = state.formData.servicePrices;
    
    // قیمت‌های سرویس AI
    const aiOnlyFinalPrice = servicePrices.aiOnly || 0; // قیمت نهایی AI
    const aiOnlyOriginalPrice = servicePrices.aiOnlyOriginal || 0; // قیمت اصلی AI
    const hasDiscount = servicePrices.hasDiscount || false; // آیا تخفیف داره؟
    
    // محاسبه تخفیف
    const aiDiscountAmount = aiOnlyOriginalPrice - aiOnlyFinalPrice;
    const aiDiscountPercent = aiOnlyOriginalPrice > 0 
        ? Math.round((aiDiscountAmount / aiOnlyOriginalPrice) * 100) 
        : 0;
    
    // قیمت کل
    const totalPrice = aiOnlyFinalPrice + consultationPrice;
    
    // المان‌های HTML
    const priceBreakdown = document.getElementById('price-breakdown');
    const selectNote = document.getElementById('specialist-select-note');
    const aiServicePrice = document.getElementById('ai-service-price');
    const aiServiceDiscount = document.getElementById('ai-service-discount');
    const consultantPriceEl = document.getElementById('consultant-price');
    const consultantDiscountEl = document.getElementById('consultant-discount');
    const totalPriceEl = document.getElementById('total-price');
    
    if (!priceBreakdown || !selectNote) return;
    
    // مخفی کردن متن انتخاب متخصص
    selectNote.style.display = 'none';
    
    // نمایش جزئیات قیمت
    priceBreakdown.style.display = 'block';
    
    // 1️⃣ قیمت سرویس AI
    if (hasDiscount && aiDiscountAmount > 0) {
        // اگر تخفیف داره
        aiServicePrice.innerHTML = `
            <span class="price-value old-price">${new Intl.NumberFormat('fa-IR').format(aiOnlyOriginalPrice)}</span>
            <span class="price-value">${new Intl.NumberFormat('fa-IR').format(aiOnlyFinalPrice)}</span>
        `;
        aiServiceDiscount.textContent = `${aiDiscountPercent}% تخفیف`;
        aiServiceDiscount.style.display = 'inline-block';
    } else {
        // بدون تخفیف
        aiServicePrice.textContent = new Intl.NumberFormat('fa-IR').format(aiOnlyFinalPrice);
        aiServiceDiscount.style.display = 'none';
    }
    
    // 2️⃣ قیمت مشاور (فعلاً بدون تخفیف - در آینده می‌تونید اضافه کنید)
    consultantPriceEl.textContent = new Intl.NumberFormat('fa-IR').format(consultationPrice);
    consultantDiscountEl.style.display = 'none'; // فعلاً تخفیف برای مشاور نداریم
    
    // 3️⃣ قیمت کل
    totalPriceEl.textContent = new Intl.NumberFormat('fa-IR').format(totalPrice);
    
    // ذخیره در state
    state.formData.servicePrices = {
        ...state.formData.servicePrices,
        withSpecialistTotal: totalPrice,
        consultantFee: consultationPrice,
        aiServiceFinal: aiOnlyFinalPrice,
        aiServiceOriginal: aiOnlyOriginalPrice,
        hasAiDiscount: hasDiscount
    };
    
    console.log('💰 جزئیات قیمت:', {
        aiOriginal: aiOnlyOriginalPrice,
        aiFinal: aiOnlyFinalPrice,
        aiDiscount: aiDiscountAmount,
        consultant: consultationPrice,
        total: totalPrice
    });
}


// ========================================
// توابع پاپ‌آپ
// ========================================

function showLabDataPopup(extractedData, file, onConfirm) {
    const popup = document.getElementById('lab-data-popup');
    const dataList = document.getElementById('lab-data-list');
    
    if (!popup) {
        console.error('lab-data-popup نیست!');
        alert('خطا: المان پاپ‌آپ در HTML پیدا نشد.');
        return;
    }
    
    if (!dataList) {
        console.error('lab-data-list نیست!');
        alert('خطا: المان لیست داده در HTML پیدا نشد.');
        return;
    }
    
    dataList.innerHTML = '';
    
    // 🎯 چک کنیم داده چه شکلیه
    let tests = [];
    
    if (Array.isArray(extractedData)) {
        // اگه آرایه بود
        tests = extractedData;
    } else if (extractedData && typeof extractedData === 'object') {
        // اگه یک آبجکت تکی بود (مثل FBS)
        if (extractedData.found && extractedData.value !== null) {
            tests = [extractedData]; // 👈 تبدیل به آرایه
        } else if (extractedData.keyvalue && Array.isArray(extractedData.keyvalue)) {
            // اگه keyvalue داشت
            tests = extractedData.keyvalue;
        }
    }
    
    console.log('🔍 تعداد تست‌ها:', tests.length);
    
    tests.forEach((test, index) => {
        // 👇 حذف شرط - همه رو نمایش بده
        const isFound = test.found && test.value !== null;
        
        const item = document.createElement('div');
        item.className = 'lab-data-item';
        
        // Checkbox
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = `lab-check-${index}`;
        checkbox.className = 'lab-checkbox';
        checkbox.checked = isFound;  // 👈 فقط اگه پیدا شده تیک بخوره
        checkbox.dataset.testName = test.name;
    
        // Label
        const label = document.createElement('label');
        label.htmlFor = `lab-check-${index}`;
        label.className = 'lab-checkbox-label';
    
        // Test Name
        const key = document.createElement('span');
        key.className = 'lab-data-key';
        key.textContent = test.name;
        
        // اگه پیدا نشده، badge اضافه کن
        if (!isFound) {
            const badge = document.createElement('span');
            badge.className = 'not-found-badge';
            badge.textContent = '⚠️ پیدا نشد';
            badge.style.cssText = `
                display: inline-block;
                background: #ffc107;
                color: #000;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                margin-right: 8px;
            `;
            key.appendChild(badge);
        }
    
        // Value Container
        const valueContainer = document.createElement('div');
        valueContainer.className = 'lab-data-value-container';
    
        const value = document.createElement('span');
        value.className = 'lab-data-value';
        value.textContent = isFound ? `${test.value} ${test.unit}`.trim() : '---';
        value.dataset.originalValue = test.value || '';
        value.dataset.unit = test.unit || '';
    
        valueContainer.appendChild(value);
    
        // Event: Long Press برای ویرایش
        let pressTimer;
        value.addEventListener('mousedown', function(e) {
            if (!checkbox.checked) return;
            pressTimer = setTimeout(() => makeEditable(value, test, checkbox), 500);
        });
        value.addEventListener('mouseup', function() {
            clearTimeout(pressTimer);
        });
        value.addEventListener('mouseleave', function() {
            clearTimeout(pressTimer);
        });
    
        // Touch Events
        value.addEventListener('touchstart', function(e) {
            if (!checkbox.checked) return;
            pressTimer = setTimeout(() => makeEditable(value, test, checkbox), 500);
        });
        value.addEventListener('touchend', function() {
            clearTimeout(pressTimer);
        });
    
        // اضافه کردن به item
        item.appendChild(checkbox);
        item.appendChild(label);
        item.appendChild(key);
        item.appendChild(valueContainer);
    
        // اگه تیک نخورده، کلاس اضافه کن
        if (!isFound) {
            item.classList.add('lab-item-unchecked');
        }
    
        dataList.appendChild(item);
    
        // Event: checkbox تغییر
        checkbox.addEventListener('change', function() {
            item.classList.toggle('lab-item-unchecked', !this.checked);
            
            // اگه تیک خورد و مقدار خالیه، input نشون بده
            if (this.checked && (!test.value || test.value === null)) {
                const valueSpan = item.querySelector('.lab-data-value');
                makeEditable(valueSpan, test, checkbox);
            }
            
            console.log(`${index + 1}. ${test.name}: ${this.checked ? '✅' : '❌'}`);
        });
    });

    
    function makeEditable(valueSpan, test, checkbox) {
        const currentValue = test.value;
        const unit = test.unit || '';
        
        // ساخت input
        const input = document.createElement('input');
        input.type = 'number';
        input.className = 'lab-data-input';
        input.value = currentValue;
        input.min = 0; // 👈 حداقل 0
        input.step = 'any'; // اعشاری مجاز
        input.style.cssText = `
            width: 80px;
            padding: 4px 8px;
            border: 2px solid #00857a;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 700;
            color: #00857a;
            text-align: center;
            background: #f0f8f7;
        `;
        
        // جایگزینی
        const parent = valueSpan.parentElement;
        parent.replaceChild(input, valueSpan);
        
        input.focus();
        input.select();
        
        // 🎯 جلوگیری از ورودی منفی
        input.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
        
        // ذخیره با Enter
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                saveEdit(input, valueSpan, test, unit, parent);
            } else if (e.key === 'Escape') {
                cancelEdit(input, valueSpan, parent);
            }
        });
        
        // ذخیره با blur
        input.addEventListener('blur', function() {
            setTimeout(() => {
                if (input.parentElement) {
                    saveEdit(input, valueSpan, test, unit, parent);
                }
            }, 150);
        });
    }
    
    function saveEdit(input, valueSpan, test, unit, parent) {
        const newValue = parseFloat(input.value);
        
        // 🎯 چک اولیه
        if (isNaN(newValue) || newValue <= 0) {
            showEditError(input, 'لطفاً یک عدد مثبت وارد کنید');
            return;
        }
        
        // 🎯 اعتبارسنجی محدوده
        const validation = validateLabTestValue(test.name, newValue);
        
        if (!validation.valid) {
            showEditError(input, validation.reason);
            console.warn(`❌ مقدار نامعتبر: ${test.name} = ${newValue} (${validation.reason})`);
            return;
        }
        
        // ✅ ذخیره
        test.value = newValue;
        valueSpan.textContent = `${newValue} ${unit}`.trim();
        valueSpan.dataset.originalValue = newValue;
        
        parent.replaceChild(valueSpan, input);
        
        console.log(`✅ ویرایش شد: ${test.name} = ${newValue} ${unit}`);
    }
    
    function showEditError(input, message) {
        // حذف پیام خطای قبلی
        const existingError = input.parentElement.querySelector('.lab-edit-error');
        if (existingError) {
            existingError.remove();
        }
        
        // ساخت پیام جدید
        const errorMsg = document.createElement('div');
        errorMsg.className = 'lab-edit-error';
        errorMsg.textContent = `⚠️ ${message}`;
        errorMsg.style.cssText = `
            position: absolute;
            bottom: -25px;
            left: 0;
            right: 0;
            color: #f44336;
            font-size: 0.75rem;
            text-align: center;
            background: #ffebee;
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
            z-index: 10;
            animation: shake 0.3s ease;
        `;
        
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(errorMsg);
        
        // استایل خطا برای input
        input.style.borderColor = '#f44336';
        input.style.background = '#ffebee';
        
        // حذف خطا بعد از 3 ثانیه
        setTimeout(() => {
            if (errorMsg.parentElement) {
                errorMsg.remove();
            }
            input.style.borderColor = '#00857a';
            input.style.background = '#f0f8f7';
        }, 3000);
        
        input.focus();
        input.select();
    }
    
    function cancelEdit(input, valueSpan, parent) {
        const originalValue = valueSpan.dataset.originalValue;
        console.log(`❌ ویرایش لغو شد. مقدار قبلی: ${originalValue}`);
        parent.replaceChild(valueSpan, input);
    }

    popup.style.display = 'flex';
    
    window._labConfirmCallback = () => {
        let cleanedData;
        let invalidTests = [];
        
        if (Array.isArray(extractedData)) {
            cleanedData = extractedData
                .filter(test => {
                    const checkbox = document.querySelector(`#lab-check-${extractedData.indexOf(test)}`);
                    if (!checkbox || !checkbox.checked || test.value === null || test.value === '') {
                        return false;
                    }
                    
                    // 🎯 اعتبارسنجی
                    const validation = validateLabTestValue(test.name, test.value);
                    
                    if (!validation.valid) {
                        invalidTests.push({
                            name: test.name,
                            value: test.value,
                            reason: validation.reason
                        });
                        console.warn(`⚠️ ${test.name}: ${validation.reason}`);
                        return false;
                    }
                    
                    return true;
                })
                .map(test => ({
                    name: test.name,
                    value: parseFloat(test.value),
                    unit: test.unit || ''
                }));
        } else if (extractedData && typeof extractedData === 'object') {
            if (extractedData.found && extractedData.value !== null) {
                const validation = validateLabTestValue(extractedData.name, extractedData.value);
                
                if (validation.valid) {
                    cleanedData = {
                        name: extractedData.name,
                        value: parseFloat(extractedData.value),
                        unit: extractedData.unit || ''
                    };
                } else {
                    invalidTests.push({
                        name: extractedData.name,
                        value: extractedData.value,
                        reason: validation.reason
                    });
                    console.warn(`⚠️ ${extractedData.name}: ${validation.reason}`);
                    cleanedData = null;
                }
            } else {
                cleanedData = null;
            }
        }
        
        closeLabPopup();
        
        // 🎯 نمایش خطاهای اعتبارسنجی
        if (invalidTests.length > 0) {
            const errorMessages = invalidTests
                .map(t => `• ${t.name}: ${t.value} - ${t.reason}`)
                .join('\n');
            
            const warningLoader = new AiDastyarLoader({
                message: `⚠️ برخی مقادیر نامعتبر بودند:\n${errorMessages}`,
                theme: 'light',
                size: 'medium',
                closable: true,
                overlay: false,
                autoHide: 5000
            });
            warningLoader.show();
        }
        
        if (cleanedData && (Array.isArray(cleanedData) ? cleanedData.length > 0 : true)) {
            onConfirm(cleanedData);
        } else {
            console.warn('⚠️ هیچ داده معتبری برای ذخیره وجود ندارد');
        }
    };

}


/**
 * بستن پاپ‌آپ
 */
window.closeLabPopup = function() {
    const popup = document.getElementById('lab-data-popup');
    const fileInput = document.getElementById('lab-test-file');
    
    if (popup) {
        popup.style.display = 'none';
    }
    
    // 🎯 پاک کردن fileInput تا بتونه دوباره انتخاب بشه
    if (fileInput) {
        fileInput.value = '';
        console.log('🗑️ فایل input پاک شد');
    }
    
    window._labConfirmCallback = null;
};


/**
 * تایید داده‌ها
 */
window.confirmLabData = function() {
    if (typeof window._labConfirmCallback === 'function') {
        window._labConfirmCallback();
    }
    
    // 🎯 پاک کردن callback بعد از اجرا
    window._labConfirmCallback = null;
    
    console.log('✅ فایل تایید شد');
};


/**
 * رد کردن داده‌ها
 */
window.rejectLabData = function() {
    closeLabPopup();
    
    const fileInput = document.getElementById('lab-test-file');
    if (fileInput) {
        fileInput.value = '';
    }
    
    const rejectLoader = new AiDastyarLoader({
        message: '❌ فایل رد شد. می‌توانید فایل دیگری انتخاب کنید.',
        theme: 'light',
        size: 'medium',
        closable: true,
        overlay: false,
        autoHide: 3000
    });
    rejectLoader.show();
    
    console.log('❌ فایل رد شد');
};


// Flag برای جلوگیری از setup مکرر
window._labTestUploadInitialized = false;
window.setupLabTestUpload = function(currentStep) {
    if (currentStep !== window.STEPS.LABTESTUPLOAD) return;

    const fileInput = document.getElementById('lab-test-file');
    const filePreview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');
    const removeFile = document.getElementById('remove-file');
    const skipCheckbox = document.getElementById('skip-lab-test');
    const nextButton = document.querySelector('.next-step');
    const uploadArea = document.querySelector('.file-upload-area');

    // Reset state
    nextButton.disabled = true;

    // بررسی state قبلی
    if (state.formData.userInfo.labTestFile) {
        showFilePreview(state.formData.userInfo.labTestFile);
        nextButton.disabled = false;
    } else if (state.formData.userInfo.skipLabTest) {
        skipCheckbox.checked = true;
        const label = skipCheckbox.nextElementSibling;
        if (label) label.classList.add('checked');
        nextButton.disabled = false;
    }

    // فقط یک بار setup کن
    if (window._labTestUploadInitialized) {
        console.log('⏭️ Lab test upload قبلاً initialize شده');
        return;
    }

    console.log('🔧 Lab test upload در حال initialize...');
    window._labTestUploadInitialized = true;

    // ========== رویداد تغییر فایل ==========
    fileInput.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        
        if (!file) return;
    
        // بررسی نوع فایل
        if (file.type !== 'application/pdf') {
            alert('❌ لطفاً فقط فایل PDF آپلود کنید');
            fileInput.value = '';
            return;
        }
    
        // بررسی حجم فایل (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('❌ حجم فایل نباید بیشتر از 5 مگابایت باشد');
            fileInput.value = '';
            return;
        }
    
        // ✅ بررسی وجود PDFProcessor
        if (!window.PDFProcessor) {
            console.error('❌ PDFProcessor لود نشده است!');
            alert('⚠️ خطا: ماژول پردازش PDF لود نشده. لطفاً صفحه را رفرش کنید.');
            return;
        }
    
        // ✅ بررسی وجود PDF.js
        if (typeof pdfjsLib === 'undefined') {
            console.error('❌ PDF.js لود نشده است!');
            alert('⚠️ خطا: کتابخانه PDF لود نشده. لطفاً صفحه را رفرش کنید.');
            return;
        }
    
        console.log('📎 فایل انتخاب شد:', file.name);
    
        // 🎯 نمایش لودر
        let loader = null;
        if (typeof AiDastyarLoader !== 'undefined') {
            loader = new AiDastyarLoader({
                message: 'در حال خواندن فایل PDF...',
                theme: 'light',
                size: 'medium',
                closable: false,
                overlay: true,
                persistent: true
            });
            loader.show();
        }
    
        try {
            // 🔥 پردازش PDF
            const extractedData = await window.PDFProcessor.processPDF(file);
            
            // 🎯 چاپ JSON در کنسول
            console.log('📊 JSON استخراج شده:');
            console.log(JSON.stringify(extractedData, null, 2));

            if (loader) {
                loader.hide();
            }
    
            // 🎯 اینجا پاپ‌آپ رو نشون بده
            showLabDataPopup(extractedData, file, (confirmedData) => {
                // بعد از تایید ذخیره کن
                state.updateFormData('userInfo.labTestFile', confirmedData);
                state.updateFormData('userInfo.skipLabTest', false);
                
                showFilePreview(confirmedData);
                nextButton.disabled = false;
                
                if (skipCheckbox.checked) {
                    skipCheckbox.checked = false;
                    const label = skipCheckbox.nextElementSibling;
                    if (label) {
                        label.classList.remove('checked');
                    }
                }
        
                const successLoader = new AiDastyarLoader({
                    message: '✅ اطلاعات تایید شد!',
                    theme: 'light',
                    size: 'medium',
                    closable: false,
                    overlay: false,
                    autoHide: 2000
                });
                successLoader.show();
            });
        } catch (error) {
            console.error('❌ خطا:', error);
            
            // ❌ بستن لودر با خطا
            if (loader) {
                // 1️⃣ پنهان کردن لودر فعلی
                loader.hide();
                
                // 2️⃣ نمایش لودر خطا
                const errorLoader = new AiDastyarLoader({
                    message: '❌ خطا در پردازش PDF',
                    theme: 'light',
                    size: 'medium',
                    closable: true,
                    overlay: false,
                    autoHide: 3000  // 👈 خودکار بسته میشه بعد از 3 ثانیه
                });
                errorLoader.show();
            } else {
                alert('⚠️ خطا در پردازش PDF');
            }
            
            fileInput.value = '';
        }
    });


    // ========== رویداد حذف فایل ==========
    if (removeFile) {
        removeFile.addEventListener('click', function() {
            // پاک کردن input
            fileInput.value = '';
            
            // مخفی کردن پیش‌نمایش
            filePreview.style.display = 'none';
            
            // پاک کردن state
            state.updateFormData('userInfo.labTestFile', null);
            
            // 🎯 نمایش دوباره چک‌باکس "فایل ندارم"
            const skipCheckbox = document.getElementById('skip-lab-test');
            const skipContainer = skipCheckbox?.closest('.skip-lab-test-container') || skipCheckbox?.parentElement;
            
            if (skipContainer) {
                skipContainer.style.display = 'block';
                console.log('🔓 چک‌باکس "فایل ندارم" نمایش داده شد');
            }
            
            // غیرفعال کردن دکمه Next اگه skip هم چک نشده
            if (!skipCheckbox?.checked) {
                nextButton.disabled = true;
            }
            
            console.log('🗑️ فایل حذف شد');
        });
    }


    // ========== رویداد checkbox رد کردن ==========
    if (skipCheckbox) {
        skipCheckbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            
            if (this.checked) {
                if (label) {
                    label.classList.add('checked-animation');
                    setTimeout(() => {
                        label.classList.remove('checked-animation');
                        label.classList.add('checked');
                    }, 800);
                }
                
                state.updateFormData('userInfo.skipLabTest', true);
                state.updateFormData('userInfo.labTestFile', null);
                nextButton.disabled = false;
                
                fileInput.value = '';
                filePreview.style.display = 'none';
                
                console.log('⏭️ آزمایش خون رد شد');
            } else {
                if (label) label.classList.remove('checked');
                state.updateFormData('userInfo.skipLabTest', false);
                
                if (!state.formData.userInfo.labTestFile) {
                    nextButton.disabled = true;
                }
            }
        });
    }

    // ========== Drag & Drop ==========
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#00857a';
            this.style.backgroundColor = '#f0f8f7';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }

    function showFilePreview(fileData) {
        const fileName = document.getElementById('file-name');
        const filePreview = document.getElementById('file-preview');
        const skipCheckbox = document.getElementById('skip-lab-test');
        const skipContainer = skipCheckbox?.closest('.skip-lab-test-container') || skipCheckbox?.parentElement;
        
        if (fileName) {
            fileName.textContent = fileData.fileName;
        }
        
        if (filePreview) {
            filePreview.style.display = 'flex';
        }
        
        // 🎯 مخفی کردن چک‌باکس "فایل ندارم"
        if (skipContainer) {
            skipContainer.style.display = 'none';
            console.log('🔒 چک‌باکس "فایل ندارم" مخفی شد');
        }
        
        console.log('✅ فایل نمایش داده شد:', fileData.fileName);
    }


    console.log('✅ Lab test upload با موفقیت initialize شد');
};
