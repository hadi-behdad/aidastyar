// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-events.js
console.log('form-events.js : 0917')
function setupCheckboxSelection(config) {
    const {
        step, // STEP مربوطه
        noneCheckboxId, // ID چک‌باکس "هیچکدام"
        optionIds, // لیست IDهای گزینه‌ها
        fieldName, // نام فیلد در state
        categoryClass = null // کلاس دسته‌بندی اختیاری
    } = config;

    // فقط در مرحله مورد نظر اجرا شود
    if (state.currentStep !== step) return;

    const noneCheckbox = document.getElementById(noneCheckboxId);
    const nextButton = document.querySelector(".next-step");
    const options = optionIds.map(id => document.getElementById(id));

    // اعتبارسنجی عناصر
    if (!noneCheckbox || options.some(opt => !opt) || !nextButton) {
        console.error("عناصر چک‌باکس یافت نشدند");
        return;
    }

    nextButton.disabled = true;

    // --- توابع کمکی ---
    const updateLabelStyle = (checkbox) => {
        const label = checkbox.nextElementSibling;
        if (!label) return;

        if (checkbox.checked) {
            label.classList.add("checked-animation");
            setTimeout(() => {
                label.classList.remove("checked-animation");
                label.classList.add("checked");
            }, 800);
        } else {
            label.classList.remove("checked");
        }
    };

    const validateForm = () => {
        const anyChecked = options.some(opt => opt.checked) || noneCheckbox.checked;
        nextButton.disabled = !anyChecked;

        const selectedValues = [];
        if (noneCheckbox.checked) {
            selectedValues.push("none");
        } else {
            options.forEach(opt => {
                if (opt.checked) selectedValues.push(opt.value);
            });
        }

        state.updateFormData(fieldName, selectedValues);
    };

    // --- مدیریت رویدادها ---
    // رویداد برای گزینه "هیچکدام"
    noneCheckbox.addEventListener("change", function() {
        updateLabelStyle(this);
        
        if (this.checked) {
            options.forEach(opt => {
                opt.checked = false;
                updateLabelStyle(opt);
            });
        }
        
        validateForm();
    });

    // رویداد برای گزینه‌های معمولی
    options.forEach(option => {
        option.addEventListener("change", function() {
            updateLabelStyle(this);
            
            if (this.checked) {
                noneCheckbox.checked = false;
                updateLabelStyle(noneCheckbox);
            }
            
            validateForm();
        });
    });

    // مدیریت دسته‌بندی‌های خاص (مثلاً فقط برای زنان)
    if (categoryClass) {
        const categoryElements = document.querySelectorAll(`.${categoryClass}`);
        if (state.formData.gender !== "female") {
            categoryElements.forEach(el => {
                el.style.display = "none";
                const checkbox = el.querySelector(".real-checkbox");
                if (checkbox) checkbox.checked = false;
            });
        }
    }

    // اعتبارسنجی اولیه
    validateForm();
}

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
    setupScrollIndicator('surgery-selection');
    setupScrollIndicator('stomach-selection');
    setupScrollIndicator('hormonal-selection');
    setupScrollIndicator('additional-info-selection');
    setupScrollIndicator('food-restriction-selection');
    setupScrollIndicator('goal-selection');
    setupScrollIndicator('activity-selection');
    setupScrollIndicator('meal-selection');
    setupScrollIndicator('diet-style-selection');
    setupScrollIndicator('food-limitations-selection');
    setupScrollIndicator('food-preferences-selection');
});

window.handleNextStep = function() {
    if (window.state.currentStep < window.totalSteps) {
        window.navigateToStep(window.state.currentStep + 1);
    }
};

window.preloadImages = function() {
    const images = [
        'assets/images/webp/img_0_de-min.webp',
        'assets/images/png/img_1_de-min.png',
        'assets/images/webp/img_0-min.webp',
        'assets/images/png/img_1-min.png'
    ];
    
    images.forEach(src => {
        const img = new Image();
        img.src = src;
    });
}

window.setupTermsAgreement = function(currentStep) {
    if (currentStep !== STEPS.TERMS_AGREEMENT) return;

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

// 5. سبک غذایی
window.setupDietStyleSelection = function(currentStep) {
    setupCheckboxSelection({
        step: STEPS.DIET_STYLE,
        noneCheckboxId: "diet-style-none",
        optionIds: [
            "diet-style-vegetarian", "diet-style-vegan", "diet-style-halal"
        ],
        fieldName: "dietStyle"
    });
};

// 6. محدودیت‌های غذایی
window.setupFoodLimitationsSelection = function(currentStep) {
    setupCheckboxSelection({
        step: STEPS.FOOD_LIMITATIONS,
        noneCheckboxId: "limitations-none",
        optionIds: [
            "limitation-no-seafood", "limitation-no-redmeat", "limitation-no-pork",
            "limitation-no-gluten", "limitation-no-dairy", "limitation-no-eggs",
            "limitation-no-nuts"
        ],
        fieldName: "foodLimitations"
    });
};

// 7. ترجیحات غذایی
window.setupFoodPreferencesSelection = function(currentStep) {
    setupCheckboxSelection({
        step: STEPS.FOOD_PREFERENCES,
        noneCheckboxId: "preferences-none",
        optionIds: [
            "preference-lowcarb", "preference-lowfat",
            "preference-highprotein", "preference-organic"
        ],
        fieldName: "foodPreferences"
    });
};

window.setupWaterIntakeSelection = function(currentStep) {
    if (currentStep !== STEPS.WATER_INTAKE) return;

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
            state.updateFormData('waterIntake', null);
        } else {
            waterAmountDisplay.textContent = amount;
            waterLiterDisplay.textContent = (amount * 0.25).toFixed(1); // محاسبه لیتر (هر لیوان 250 سی‌سی)
            waterAmountText.style.display = 'flex';
            dontKnowText.style.display = 'none';
            state.updateFormData('waterIntake', amount);
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

window.setupStomachDiscomfortSelection = function(currentStep) {
    setupCheckboxSelection({
        step: STEPS.STOMACH,
        noneCheckboxId: "stomach-none",
        optionIds: [
            "stomach-bloating", "stomach-pain", "stomach-heartburn",
            "stomach-nausea", "stomach-indigestion", "stomach-constipation",
            "stomach-diarrhea", "stomach-food-intolerance", "stomach-acid-reflux",
            "stomach-slow-digestion", "stomach-fullness"
        ],
        fieldName: "stomachDiscomfort"
    });
};

// 2. اختلالات هورمونی
window.setupHormonalSelection = function(currentStep) {
    setupCheckboxSelection({
        step: STEPS.HORMONAL,
        noneCheckboxId: "hormonal-none",
        optionIds: [
            "hormonal-hypothyroidism", "hormonal-hyperthyroidism",
            "hormonal-diabetes", "hormonal-insulin-resistance",
            "hormonal-pcos", "hormonal-menopause",
            "hormonal-cortisol", "hormonal-growth"
        ],
        fieldName: "hormonal",
        categoryClass: "female-only"
    });
};

// 1. جراحی‌ها
window.setupSurgerySelection = function(currentStep) {
    setupCheckboxSelection({
        step: STEPS.SURGERY,
        noneCheckboxId: "surgery-none",
        optionIds: [
            "surgery-metabolic", "surgery-gallbladder", "surgery-intestine",
            "surgery-thyroid", "surgery-pancreas", "surgery-gynecology",
            "surgery-kidney", "surgery-liver", "surgery-heart"
        ],
        fieldName: "surgery",
        categoryClass: "female-only"
    });
};

// 4. اطلاعات تکمیلی سلامت
window.setupAdditionalInfoSelection = function(currentStep) {
    setupCheckboxSelection({
        step: STEPS.ADDITIONAL_INFO,
        noneCheckboxId: "info-none",
        optionIds: [
            "info-diabetes", "info-hypertension", "info-cholesterol",
            "info-ibs", "info-celiac", "info-lactose", "info-food-allergy"
        ],
        fieldName: "additionalInfo"
    });
};

window.setupFoodRestrictionSelection = function(currentStep) {
    try {
        if (currentStep !== STEPS.FOOD_RESTRICTIONS) return;

        const elements = {
            noneCheckbox: document.getElementById('restriction-none'),
            vegetarian: document.getElementById('restriction-vegetarian'),
            vegan: document.getElementById('restriction-vegan'),
            halal: document.getElementById('restriction-halal'),
            noSeafood: document.getElementById('restriction-no-seafood'),
            noRedmeat: document.getElementById('restriction-no-redmeat'),
            noPork: document.getElementById('restriction-no-pork'),
            lowcarb: document.getElementById('restriction-lowcarb'),
            lowfat: document.getElementById('restriction-lowfat'),
            nextButton: document.querySelector('.next-step')
        };

        if (Object.values(elements).some(el => !el)) {
            console.error('Some required elements for food restriction step are missing');
            return;
        }

        elements.nextButton.disabled = true;

        const validateForm = () => {
            const anyChecked = [
                elements.vegetarian,
                elements.vegan,
                elements.halal,
                elements.noSeafood,
                elements.noRedmeat,
                elements.noPork,
                elements.lowcarb,
                elements.lowfat
            ].some(option => option.checked) || elements.noneCheckbox.checked;
            
            elements.nextButton.disabled = !anyChecked;
            
            const foodRestrictions = [];
            if (elements.vegetarian.checked) foodRestrictions.push('vegetarian');
            if (elements.vegan.checked) foodRestrictions.push('vegan');
            if (elements.halal.checked) foodRestrictions.push('halal');
            if (elements.noSeafood.checked) foodRestrictions.push('no-seafood');
            if (elements.noRedmeat.checked) foodRestrictions.push('no-redmeat');
            if (elements.noPork.checked) foodRestrictions.push('no-pork');
            if (elements.lowcarb.checked) foodRestrictions.push('low-carb');
            if (elements.lowfat.checked) foodRestrictions.push('low-fat');
            if (elements.noneCheckbox.checked) foodRestrictions.push('none');
            
            state.updateFormData('foodRestrictions', foodRestrictions);
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

        elements.noneCheckbox.addEventListener('change', function() {
            if (this.checked) {
                [
                    elements.vegetarian,
                    elements.vegan,
                    elements.halal,
                    elements.noSeafood,
                    elements.noRedmeat,
                    elements.noPork,
                    elements.lowcarb,
                    elements.lowfat
                ].forEach(option => {
                    option.checked = false;
                    const label = option.nextElementSibling;
                    if (label) label.classList.remove('checked');
                });
            }
            validateForm();
        });

        [
            elements.vegetarian,
            elements.vegan,
            elements.halal,
            elements.noSeafood,
            elements.noRedmeat,
            elements.noPork,
            elements.lowcarb,
            elements.lowfat
        ].forEach(option => {
            handleCheckboxChange(option);
            option.addEventListener('change', function() {
                if (this.checked) {
                    elements.noneCheckbox.checked = false;
                    const label = elements.noneCheckbox.nextElementSibling;
                    if (label) label.classList.remove('checked');
                }
                validateForm();
            });
        });

        validateForm();
    } catch (error) {
        console.error('Error in food restriction selection step:', error);
    }
};

window.setupConfirmationCheckbox = function(currentStep) {
    const submitButton = document.querySelector(".submit-form");
    const confirmCheckbox = document.getElementById("confirm-info");
    
    if (currentStep !== STEPS.CONFIRMATION) return;

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

window.handleFormSubmit = function(event) {
    event.preventDefault();
    
    // 1. جمع‌آوری ساختارمند تمام داده‌ها
    const formData = {
        ...state.formData,
        // اطلاعات پایه
        gender: state.formData.gender,
        age: state.formData.age,
        height: state.formData.height,
        weight: state.formData.weight,
        targetWeight: state.formData.targetWeight,
        goal: state.formData.goal,
        activity: state.formData.activity,
        meals: state.formData.meals,
        waterIntake: state.formData.waterIntake,
        // اطلاعات پزشکی
        surgery: state.formData.surgery || [],
        hormonal: state.formData.hormonal || [],
        stomachDiscomfort: state.formData.stomachDiscomfort || [],
        
        // اطلاعات تکمیلی (به صورت جداگانه)
        diabetes: state.formData.additionalInfo?.includes('diabetes') || false,
        pressure: state.formData.additionalInfo?.includes('pressure') || false,
        thyroid: state.formData.additionalInfo?.includes('thyroid') || false,
        allergy: state.formData.additionalInfo?.includes('allergy') || false,
        vegetarian: state.formData.foodRestrictions?.includes('vegetarian') || false,
        noSeafood: state.formData.foodRestrictions?.includes('no-seafood') || false,
    };

    console.log('Form submitted:', formData);
    
    document.getElementById('SubmitBtn').innerHTML = 'در حال ارسال درخواست ...';
    document.getElementById('SubmitBtn').disabled = true;
    // ایجاد و ارسال یک رویداد سفارشی
    const formSubmittedEvent = new CustomEvent('formSubmitted', {
        detail: { formData }
    });
    window.dispatchEvent(formSubmittedEvent);
   
};

window.showSummary = function() {
    const summaryContainer = document.getElementById('summary-container');
    const nextButton = document.querySelector(".next-step");
    const confirmCheckbox = document.getElementById("confirm-info");
    
    nextButton.disabled = true;
    
    const { 
        gender, age, height, weight, targetWeight, goal, 
        activity, meals, waterIntake, surgery = [], hormonal = [],
        stomachDiscomfort = [], additionalInfo = [], dietStyle = [],
        foodLimitations = [], foodPreferences = []
    } = state.formData;

    const goalText = { 
        "weight-loss": "کاهش وزن", 
        "weight-gain": "افزایش وزن", 
        "fitness": "حفظ سلامت"
    }[goal];
    
    const activityText = { 
        "very-low": "خیلی کم (کمتر از 1 ساعت)", 
        "low": "کم (1 تا 2 ساعت)", 
        "medium": "متوسط (2 تا 4 ساعت)", 
        "high": "زیاد (بیشتر از 4 ساعت)" 
    }[activity];
    
    const mealsText = { 
        "2": "۲ وعده", 
        "3": "۳ وعده", 
        "4": "۴ وعده", 
        "5": "۵ وعده یا بیشتر",
        "irregular": "وعده‌های نامنظم" 
    }[meals];
    
    const waterText = waterIntake === null ? 
        'مشخص نیست' : 
        `${waterIntake} لیوان (≈${(waterIntake * 0.25).toFixed(1)} لیتر)`;

    // اطلاعات جراحی
    const surgeryText = [];
    if (surgery.includes('metabolic')) surgeryText.push('جراحی متابولیک');
    if (surgery.includes('gallbladder')) surgeryText.push('جراحی کیسه صفرا');
    if (surgery.includes('intestine')) surgeryText.push('جراحی روده');
    if (surgery.includes('thyroid')) surgeryText.push('جراحی تیروئید');
    if (surgery.includes('pancreas')) surgeryText.push('جراحی لوزالمعده');
    if (surgery.includes('gynecology')) surgeryText.push('جراحی زنان');
    if (surgery.includes('kidney')) surgeryText.push('پیوند کلیه');
    if (surgery.includes('liver')) surgeryText.push('پیوند کبد');
    if (surgery.includes('heart')) surgeryText.push('جراحی قلب');
    if (surgery.includes('none')) surgeryText.push('هیچکدام');

    // اطلاعات هورمونی
    const hormonalText = [];
    if (hormonal.includes('hypothyroidism')) hormonalText.push('کم کاری تیروئید');
    if (hormonal.includes('hyperthyroidism')) hormonalText.push('پرکاری تیروئید');
    if (hormonal.includes('diabetes')) hormonalText.push('دیابت');
    if (hormonal.includes('insulin-resistance')) hormonalText.push('مقاومت به انسولین');
    if (hormonal.includes('pcos')) hormonalText.push('سندرم تخمدان پلی کیستیک');
    if (hormonal.includes('menopause')) hormonalText.push('یائسگی/پیش یائسگی');
    if (hormonal.includes('cortisol')) hormonalText.push('مشکلات کورتیزول');
    if (hormonal.includes('growth')) hormonalText.push('اختلال هورمون رشد');
    if (hormonal.includes('none')) hormonalText.push('هیچکدام');

    // مشکلات معده
    const stomachDiscomfortText = [];
    if (stomachDiscomfort.includes('bloating')) stomachDiscomfortText.push('نفخ یا گاز معده');
    if (stomachDiscomfort.includes('pain')) stomachDiscomfortText.push('درد یا گرفتگی معده');
    if (stomachDiscomfort.includes('heartburn')) stomachDiscomfortText.push('سوزش سر دل');
    if (stomachDiscomfort.includes('nausea')) stomachDiscomfortText.push('حالت تهوع');
    if (stomachDiscomfort.includes('indigestion')) stomachDiscomfortText.push('سوء هاضمه مزمن');
    if (stomachDiscomfort.includes('constipation')) stomachDiscomfortText.push('یبوست');
    if (stomachDiscomfort.includes('diarrhea')) stomachDiscomfortText.push('اسهال');
    if (stomachDiscomfort.includes('food-intolerance')) stomachDiscomfortText.push('عدم تحمل غذایی');
    if (stomachDiscomfort.includes('acid-reflux')) stomachDiscomfortText.push('رفلاکس اسید معده');
    if (stomachDiscomfort.includes('slow-digestion')) stomachDiscomfortText.push('هضم کند غذا');
    if (stomachDiscomfort.includes('fullness')) stomachDiscomfortText.push('سیری زودرس');
    if (stomachDiscomfort.includes('none')) stomachDiscomfortText.push('هیچکدام');

    // اطلاعات تکمیلی سلامت
    const additionalInfoText = [];
    if (additionalInfo.includes('diabetes')) additionalInfoText.push('دیابت');
    if (additionalInfo.includes('hypertension')) additionalInfoText.push('فشار خون بالا');
    if (additionalInfo.includes('cholesterol')) additionalInfoText.push('کلسترول/تری گلیسیرید بالا');
    if (additionalInfo.includes('ibs')) additionalInfoText.push('سندرم روده تحریک پذیر');
    if (additionalInfo.includes('celiac')) additionalInfoText.push('بیماری سلیاک');
    if (additionalInfo.includes('lactose')) additionalInfoText.push('عدم تحمل لاکتوز');
    if (additionalInfo.includes('food-allergy')) additionalInfoText.push('حساسیت غذایی');
    if (additionalInfo.includes('none')) additionalInfoText.push('هیچکدام');

    // سبک‌های غذایی
    const dietStyleText = [];
    if (dietStyle.includes('vegetarian')) dietStyleText.push('گیاهخواری');
    if (dietStyle.includes('vegan')) dietStyleText.push('وگان');
    if (dietStyle.includes('halal')) dietStyleText.push('حلال');
    if (dietStyle.includes('none')) dietStyleText.push('هیچکدام');

    // محدودیت‌های غذایی
    const foodLimitationsText = [];
    if (foodLimitations.includes('no-seafood')) foodLimitationsText.push('عدم مصرف غذای دریایی');
    if (foodLimitations.includes('no-redmeat')) foodLimitationsText.push('عدم مصرف گوشت قرمز');
    if (foodLimitations.includes('no-pork')) foodLimitationsText.push('عدم مصرف گوشت خوک');
    if (foodLimitations.includes('no-gluten')) foodLimitationsText.push('عدم مصرف گلوتن');
    if (foodLimitations.includes('no-dairy')) foodLimitationsText.push('عدم مصرف لبنیات');
    if (foodLimitations.includes('no-eggs')) foodLimitationsText.push('عدم مصرف تخم‌مرغ');
    if (foodLimitations.includes('no-nuts')) foodLimitationsText.push('عدم مصرف آجیل و مغزها');
    if (foodLimitations.includes('none')) foodLimitationsText.push('هیچکدام');

    // ترجیحات غذایی
    const foodPreferencesText = [];
    if (foodPreferences.includes('low-carb')) foodPreferencesText.push('رژیم کم کربوهیدرات');
    if (foodPreferences.includes('low-fat')) foodPreferencesText.push('رژیم کم چربی');
    if (foodPreferences.includes('high-protein')) foodPreferencesText.push('رژیم پرپروتئین');
    if (foodPreferences.includes('organic')) foodPreferencesText.push('مواد غذایی ارگانیک');
    if (foodPreferences.includes('none')) foodPreferencesText.push('هیچکدام');

    summaryContainer.innerHTML = `
        <div class="summary-item">
            <span class="summary-label">جنسیت:</span>
            <span class="summary-value">${gender === "male" ? "مرد" : "زن"}</span>
        </div>
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
        <div class="summary-item">
            <span class="summary-label">وزن هدف:</span>
            <span class="summary-value">${targetWeight || 'ثبت نشده'} کیلوگرم</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">هدف:</span>
            <span class="summary-value">${goalText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">سابقه جراحی:</span>
            <span class="summary-value">${surgeryText.join('، ') || 'ثبت نشده'}</span>
        </div>  
        <div class="summary-item">
            <span class="summary-label">اختلالات هورمونی:</span>
            <span class="summary-value">${hormonalText.join('، ') || 'ثبت نشده'}</span>
        </div>    
        <div class="summary-item">
            <span class="summary-label">علائم معده:</span>
            <span class="summary-value">${stomachDiscomfortText.join('، ') || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">مصرف آب روزانه:</span>
            <span class="summary-value">${waterText}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">فعالیت روزانه:</span>
            <span class="summary-value">${activityText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">تعداد وعده‌های غذایی:</span>
            <span class="summary-value">${mealsText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">وضعیت سلامت:</span>
            <span class="summary-value">${additionalInfoText.join('، ') || 'ثبت نشده'}</span>
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
            <span class="summary-label">ترجیحات غذایی:</span>
            <span class="summary-value">${foodPreferencesText.join('، ') || 'ثبت نشده'}</span>
        </div>
    `;
}

// Initialize event listeners
window.addEventListener('load', preloadImages);

document.addEventListener("DOMContentLoaded", () => {
    const confirmCheckbox = document.getElementById("confirm-terms");
    const genderOptions = document.querySelectorAll(".gender-option");
    
    const updateGenderOptionsState = () => {
        genderOptions.forEach(option => {
            if (confirmCheckbox.checked) {
                option.style.opacity = "1";
                option.style.pointerEvents = "auto";
                option.style.filter = "none";
            } else {
                option.style.opacity = "0.5";
                option.style.pointerEvents = "none";
                option.style.filter = "grayscale(80%)";
            }
        });
    };
    
    confirmCheckbox.addEventListener("change", updateGenderOptionsState);
    updateGenderOptionsState();
    
    navigateToStep(state.currentStep);
    document.querySelector(".next-step").addEventListener("click", handleNextStep);
    document.getElementById("back-button").addEventListener("click", handleBackStep);
    document.getElementById("multi-step-form").addEventListener("submit", handleFormSubmit);
    window.addEventListener("popstate", (event) => {
        if (event.state?.step) state.updateStep(event.state.step);
        else navigateToStep(1);
    });

    setupInput("age-input", "age-display", "age");
    setupInput("height-input", "height-display", "height");
    setupInput("weight-input", "weight-display", "weight");
    setupInput("target-weight-input", "target-weight-display", "targetWeight");

    setupOptionSelection(".gender-option", "gender");
    setupOptionSelection(".goal-option", "goal");
    setupOptionSelection(".activity-option", "activity");
    setupOptionSelection(".meal-option", "meals");

    document.getElementById("multi-step-form").addEventListener("keydown", function(event) {
        // فقط اجازه کار Enter در مراحل خاص
        if (event.key === "Enter") {
            const allowedSteps = [
                STEPS.AGE, 
                STEPS.HEIGHT, 
                STEPS.WEIGHT, 
                STEPS.TARGET_WEIGHT,
                STEPS.CONFIRMATION
            ];
            
            if (!allowedSteps.includes(state.currentStep)) {
                event.preventDefault();
                return false;
            }
        }
    });
    
    document.addEventListener("keydown", handleEnterKey);
});