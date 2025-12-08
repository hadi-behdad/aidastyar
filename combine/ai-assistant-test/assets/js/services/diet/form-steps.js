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
    WATER_INTAKE: 11,         // ✅ تغییر: 11 → 10
    ACTIVITY: 12,             // ✅ تغییر: 12 → 11
    EXERCISE: 13,             // ✅ تغییر: 13 → 12
    DIET_STYLE: 14,           // ✅ تغییر: 14 → 13
    FOOD_LIMITATIONS: 15,     // ✅ تغییر: 15 → 14
    DIET_TYPE_SELECTION: 16,
    TERMS_AGREEMENT: 17,
    CONFIRMATION: 18
};

// تعداد مراحل اصلی (بدون احتساب دو مرحله آخر)
window.totalSteps = Object.keys(STEPS).length - 3; 

// ==========================================
// Cache for Consultant Data
// ==========================================
window.consultantsCache = window.consultantsCache || null;
window.isFetchingConsultants = window.isFetchingConsultants || false;

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

// ============================================================================
// SETUP COMPLEX CHECKBOX SELECTION (Original - Modified for gender dependency)
// ============================================================================
window.setupComplexCheckboxSelection = function(step, config) {
  if (state.currentStep !== step) return;

  const elements = {
    noneCheckbox: document.getElementById(config.noneCheckboxId),
    nextButton: document.querySelector('.next-step')
  };

  config.options.forEach(option => {
    elements[option.key] = document.getElementById(option.id);
  });

  // Handle gender-dependent options (show/hide for females only)
  if (config.genderDependent) {
    const femaleOnlyOptions = document.querySelectorAll('.female-only');
    
    if (state.formData.userInfo.gender === 'female') {
      femaleOnlyOptions.forEach(el => el.style.display = 'block');
    } else {
      femaleOnlyOptions.forEach(el => el.style.display = 'none');
      // Uncheck female-only options if gender changed
      femaleOnlyOptions.forEach(el => {
        const checkbox = el.querySelector('.real-checkbox');
        if (checkbox) checkbox.checked = false;
      });
    }
  }

  // Disable next button initially
  elements.nextButton.disabled = true;

  // Validation function
  const validateForm = () => {
    let anyChecked = false;
    
    config.options.forEach(option => {
      if (elements[option.key]?.checked) {
        anyChecked = true;
      }
    });

    if (elements.noneCheckbox.checked) {
      anyChecked = true;
    }

    elements.nextButton.disabled = !anyChecked;

    // Update state with selected values
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

  // Handle individual checkbox changes with animation
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

  // "None" checkbox logic
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

  // Set up event listeners for all checkboxes
  config.options.forEach(option => {
    if (elements[option.key]) {
      handleCheckboxChange(elements[option.key]);
    }
  });

  // Load previously selected values
  if (state.formData[config.dataKey] && Array.isArray(state.formData[config.dataKey])) {
    state.formData[config.dataKey].forEach(value => {
      if (value === 'none') {
        if (elements.noneCheckbox) {
          elements.noneCheckbox.checked = true;
          const label = elements.noneCheckbox.nextElementSibling;
          if (label) label.classList.add('checked');
        }
      } else {
        const option = config.options.find(opt => opt.key === value);
        if (option && elements[option.key]) {
          elements[option.key].checked = true;
          const label = elements[option.key].nextElementSibling;
          if (label) label.classList.add('checked');
        }
      }
    });
  }

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
    setupComplexCheckboxSelection(
        currentStep,
        {
            noneCheckboxId: 'medications-none',
            dataKey: 'medications',
            options: [
                // Original medications
                { key: 'diabetes', id: 'medication-diabetes' },
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
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'chronic-none',
        dataKey: 'chronicConditions',
        genderDependent: true,
        options: [
            { key: 'diabetes', id: 'chronic-diabetes' },
            { key: 'hypertension', id: 'chronic-hypertension' },
            { key: 'cholesterol', id: 'chronic-cholesterol' },
            { key: 'fattyLiver', id: 'chronic-fatty-liver' },
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
};

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
    if ([window.STEPS.PERSONAL_INFO, window.STEPS.TARGET_WEIGHT].includes(step)) {
        setTimeout(() => {
            let inputElement = null;
            
            if (step === window.STEPS.PERSONAL_INFO) {
                // فوکوس روی first-name-input
                inputElement = document.getElementById('full-name-input');
            } else if (step === window.STEPS.TARGET_WEIGHT) {
                // فوکوس روی target-weight-input
                inputElement = document.getElementById('target-weight-input');
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

// window.handleBackStep = function() {
//     if (state.currentStep > 1) navigateToStep(state.currentStep - 1);
// }

window.handleEnterKey = function(event) {
    // فقط در مراحل عددی (سن، قد، وزن، وزن هدف) و مرحله نهایی اجازه کار با Enter را بده
    const allowedSteps = [
        window.STEPS.HEIGHT_WEIGHT,
        window.STEPS.TARGET_WEIGHT,
        window.STEPS.PERSONAL_INFO,
        window.STEPS.CONFIRMATION
    ];
    
    if (event.key === "Enter" && 
        allowedSteps.includes(state.currentStep) && 
        (event.target.matches("input[type='text']") || state.currentStep === window.STEPS.CONFIRMATION)) {
        
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
