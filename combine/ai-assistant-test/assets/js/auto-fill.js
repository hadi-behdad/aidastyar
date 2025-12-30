// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/auto-fill.js

document.addEventListener('DOMContentLoaded', function() {
    // تعریف متغیر سراسری برای delay
    const MUL_VALUE = 3;
    const NEXT_BUTTON_DELAY = 300;
    const LONG_DELAY = 1000 * MUL_VALUE;
    const SHORT_DELAY = 300 * MUL_VALUE;
    const STEPS = window.STEPS;
    const state = window.state;    
    const testData = {
        userInfo: {
            fullName: "ملیحه محمدی",
            gender: 'female',
            menstrualStatus: 'regular',
            goal: 'weight-loss',
            age: 40,
            height: 174,
            weight: 73,
            targetWeight: 71,
            activity: 'medium',
            exercise: 'medium',
            waterIntake: 14,
            surgery: ['none'],
            labTestFile: null, // یا یک آبجکت شبیه { fileName: 'test.pdf', fileUrl: '...' }
            skipLabTest: true, // true یا false            
            digestiveConditions: ['none'],
            dietStyle: ['none'],
            foodLimitations: ['none'],
            chronicConditions: ['fattyLiver', 'cirrhosis'],  // یا هر ترکیبی که میخوای تست کنی
            medications: ['diabetes', 'thyroid', 'immunosuppressants', 'weight-loss']
        },
        serviceSelection: {
            dietType: "ai-only",
            selectedSpecialist: null
        }
    };    
    // فقط در محیط تست اجرا شود
    if ((window.location.pathname.includes('service/diet')) && (window.location.hostname.includes('test.') || 
        (typeof aiAssistantVars !== 'undefined' && aiAssistantVars.env === 'sandbox'))) {
        
        // تابع اصلی برای پر کردن خودکار فرم
        function autoFillForm() {
            console.log('Current Step:', state.currentStep);
            
            // بررسی وجود فرم
            if (!document.getElementById('multi-step-form')) {
                console.error('⚠️ فرم مورد نظر یافت نشد');
                return;
            }
            
            window.removeEventListener('stateUpdated', handleStateChange);
            
            function handleStateChange() {
                console.trace();
                fillStepBasedOnCurrentState();
            }
            
            // سایر توابع موجود بدون تغییر (فقط ترتیب فراخوانی به‌روز می‌شود)
            function fillGenderStep() {
                if (state.currentStep !== STEPS.GENDER) return;

                const termsCheckbox = document.getElementById('confirm-terms');
                if (termsCheckbox && !termsCheckbox.checked) {
                    termsCheckbox.checked = true;
                    termsCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                }

                setTimeout(() => {
                    const genderOption = document.querySelector(`.gender-option[data-gender="${testData.userInfo.gender}"]`);
                    if (genderOption) {
                        genderOption.click();
                        state.updateFormData('userInfo.gender', testData.userInfo.gender);
                    }
                    clickNextButton(LONG_DELAY);
                }, SHORT_DELAY);
                
            }
                        
            function fillMenstrualStatusStep() {
                if (state.currentStep !== STEPS.MENSTRUAL_STATUS) return;
            
                console.log('🎯 Filling Menstrual Status:', testData.userInfo.menstrualStatus);
            
                setTimeout(() => {
                    const radio = document.querySelector(
                        `input[name="menstrual-status"][value="${testData.userInfo.menstrualStatus}"]`
                    );
                    
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change', { bubbles: true }));
                        state.updateFormData('userInfo.menstrualStatus', testData.userInfo.menstrualStatus);
                        console.log('✅ Menstrual Status selected:', testData.userInfo.menstrualStatus);
                    }
                    
                    clickNextButton(NEXT_BUTTON_DELAY);
                }, SHORT_DELAY);
            }

            function fillPersonalInfoStep() {
                const fullNameInput = document.getElementById("full-name-input");
                const ageInput = document.getElementById("age-input");
            
                if (fullNameInput) fullNameInput.value = testData.userInfo.fullName;
                if (ageInput) ageInput.value =  testData.userInfo.age;
            
                // Trigger events
                fullNameInput?.dispatchEvent(new Event("input", { bubbles: true }));
                ageInput?.dispatchEvent(new Event("input", { bubbles: true }));
            }

            function fillGoalStep() {
                if (state.currentStep === STEPS.GOAL) {
                    const goalOption = document.querySelector(`.goal-option[data-goal="${testData.userInfo.goal}"]`);
                    if (goalOption) {
                        goalOption.click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }

            function fillNumberSteps() {
                const fieldMap = {
                    [STEPS.TARGET_WEIGHT]: {id: 'target-weight-input', value: testData.userInfo.targetWeight, name: 'وزن هدف'}
                };

                if (fieldMap[state.currentStep]) {
                    const {id, value, name} = fieldMap[state.currentStep];
                    const input = document.getElementById(id);
                    
                    if (input) {
                        input.value = value;
                        input.dispatchEvent(new Event('input'));
                        
                        if (state.currentStep === STEPS.TARGET_WEIGHT) {
                            setTimeout(() => {
                                const errorElement = document.getElementById('targetWeight-error');
                                if (!errorElement || errorElement.classList.contains('valid')) {
                                    clickNextButton(SHORT_DELAY);
                                } else {
                                    console.warn('⚠️ خطا در اعتبارسنجی وزن هدف');
                                }
                            }, NEXT_BUTTON_DELAY);
                        } else {
                            clickNextButton(NEXT_BUTTON_DELAY);
                        }
                    }
                }
            }

            function fillActivityStep() {
                if (state.currentStep === STEPS.ACTIVITY) {
                    const activityOption = document.querySelector(`.activity-option[data-activity="${testData.userInfo.activity}"]`);
                    if (activityOption) {
                        activityOption.click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }
                        
            function fillExerciseStep() {
                if (state.currentStep === STEPS.EXERCISE) {
                    const exerciseOption = document.querySelector(`.exercise-option[data-exercise="${testData.userInfo.exercise}"]`);
                    if (exerciseOption) {
                        exerciseOption.click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }     
            
            function fillWaterStep() {
                if (state.currentStep === STEPS.WATER_INTAKE) {
                    const waterCups = document.querySelectorAll('.water-cup');
                    if (waterCups.length >= testData.userInfo.waterIntake) {
                        waterCups[testData.userInfo.waterIntake - 1].click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }
            
            function fillLabTestStep() {
                if (state.currentStep === STEPS.LABTESTUPLOAD) {
                    const skipCheckbox = document.getElementById('skip-lab-test');
                    
                    if (skipCheckbox && testData.userInfo.skipLabTest) {
                        skipCheckbox.checked = true;
                        skipCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    
                    clickNextButton(NEXT_BUTTON_DELAY);
                }
            }
            
            // به‌روزرسانی توابع چک‌باکسی برای مراحل جدید
            function fillCheckboxSteps() {
                const stepMap = {
                    [STEPS.SURGERY]: {prefix: 'surgery', name: 'جراحی‌ها'},
                    [STEPS.DIET_STYLE]: {prefix: 'diet-style', name: 'سبک رژیم'},
                    [STEPS.FOOD_LIMITATIONS]: {prefix: 'limitations', name: 'محدودیت‌های غذایی'},
                    [STEPS.CHRONIC_CONDITIONS]: {prefix: 'chronic', name: 'بیماری‌های مزمن'},
                    [STEPS.MEDICATIONS]: {prefix: 'medications', name: 'داروهای مصرفی'},
                    [STEPS.DIGESTIVE_CONDITIONS]: {prefix: 'digestive', name: 'مشکلات گوارشی'}
                };
                
                if (!stepMap[state.currentStep]) return;

                const {prefix, name} = stepMap[state.currentStep];
                const noneCheckbox = document.getElementById(`${prefix}-none`);
                
                if (noneCheckbox) {
                    noneCheckbox.checked = true;
                    noneCheckbox.dispatchEvent(new Event('change'));
                    clickNextButton(NEXT_BUTTON_DELAY);
                }
                
            }
            
            function fillDietTypeStep() {
                if (state.currentStep === STEPS.DIET_TYPE_SELECTION) {
                    const aiOnlyOption = document.querySelector('.diet-type-card[data-diet-type="ai-only"]');
                    if (aiOnlyOption) {
                        aiOnlyOption.click();
                        // فعال کردن دکمه بعدی
                        const nextButton = document.querySelector('.next-step');
                        if (nextButton) {
                            nextButton.disabled = false;
                        }
                    }
                }
            }
            
            function fillTermsStep() {
                if (state.currentStep === STEPS.TERMS_AGREEMENT) {
                    const agreeCheckbox = document.getElementById('agree-terms');
                    if (agreeCheckbox) {
                        agreeCheckbox.checked = true;
                        agreeCheckbox.dispatchEvent(new Event('change'));
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }

            function fillConfirmationStep() {
                if (state.currentStep === STEPS.CONFIRMATION) {
                    const confirmCheckbox = document.getElementById('confirm-info');
                    if (confirmCheckbox) {
                        confirmCheckbox.checked = true;
                        confirmCheckbox.dispatchEvent(new Event('change'));
                        
                        setTimeout(() => {
                            const submitButton = document.querySelector('.final-submit');
                            if (submitButton) {
                                
                                submitButton.disabled = true;
                                submitButton.textContent = aiAssistantVars.i18n.loading;
                                
                                const formData = state.formData;
                                const formSubmittedEvent = new CustomEvent('formSubmitted', {
                                    detail: {
                                        formData: formData
                                    }
                                });
                                window.dispatchEvent(formSubmittedEvent);
                                
                            }
                        }, LONG_DELAY);
                    }
                }
            }

            // تابع اصلی برای پر کردن بر اساس مرحله فعلی
            function fillStepBasedOnCurrentState() {
                switch(state.currentStep) {
                    case STEPS.GENDER:
                        fillGenderStep();
                        break;
                    case STEPS.MENSTRUAL_STATUS:
                        fillMenstrualStatusStep();
                        break;
                    case STEPS.PERSONAL_INFO:
                        fillPersonalInfoStep();
                        break;
                    case STEPS.GOAL:
                        fillGoalStep();
                        break;
                    case STEPS.HEIGHT_WEIGHT:  // ✅ تغییر: به جای STEPS.HEIGHT
                        fillHeightWeightStep();  // ✅ تغییر: تابع جدید
                        break;
                    case STEPS.TARGET_WEIGHT:
                        fillNumberSteps();
                        break;
                    case STEPS.ACTIVITY:
                        fillActivityStep();
                        break;
                    case STEPS.EXERCISE:
                        fillExerciseStep();
                        break;
                    case STEPS.WATER_INTAKE:
                        fillWaterStep();
                        break;
                    case STEPS.SURGERY:
                        fillCheckboxSteps();
                        break;
                    case STEPS.LABTESTUPLOAD:
                        fillLabTestStep();
                        break;
                    case STEPS.DIGESTIVE_CONDITIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.DIET_STYLE:
                        fillCheckboxSteps();
                        break;
                    case STEPS.FOOD_LIMITATIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.CHRONIC_CONDITIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.MEDICATIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.DIET_TYPE_SELECTION:
                        fillDietTypeStep();
                        break;                        
                    case STEPS.TERMS_AGREEMENT:
                        fillTermsStep();
                        break;
                    case STEPS.CONFIRMATION:
                        fillConfirmationStep();
                        break;
                    default:
                        console.warn('مرحله ناشناخته:', state.currentStep);
                }
            }
            
            fillStepBasedOnCurrentState();
        }

        // پر کردن مرحله ترکیبی قد و وزن
        function fillHeightWeightStep() {
            if (state.currentStep !== STEPS.HEIGHT_WEIGHT) return;
            
            console.log('📝 Filling Height & Weight step...');
            
            // پر کردن قد
            const heightInput = document.getElementById('height-input');
            if (heightInput) {
                heightInput.value = testData.userInfo.height;
                heightInput.dispatchEvent(new Event('input', { bubbles: true }));
                console.log('✅ Height set:', testData.userInfo.height);
            }
            
            // تاخیر کوتاه قبل از وزن
            setTimeout(() => {
                const weightInput = document.getElementById('weight-input');
                if (weightInput) {
                    weightInput.value = testData.userInfo.weight;
                    weightInput.dispatchEvent(new Event('input', { bubbles: true }));
                    console.log('✅ Weight set:', testData.userInfo.weight);
                }
                
                // تاخیر برای validation
                setTimeout(() => {
                    // اجرای validation دستی
                    if (typeof validateHeightWeight === 'function') {
                        validateHeightWeight();
                    }
                    
                    // کلیک Next
                    setTimeout(() => {
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }, 300);
                }, 400);
            }, 300);
        }



        // تابع کمکی برای کلیک روی دکمه بعدی
        function clickNextButton(delay) {
            setTimeout(() => {
                const nextButton = document.querySelector('.next-step:not([disabled])');
                if (nextButton) {
                    nextButton.click();
                }
            }, delay);
        }
        
        // ایجاد دکمه پر کردن خودکار (بدون تغییر)
        function createAutoFillButton() {
            if (document.getElementById('dev-auto-fill-btn')) return;

            const btn = document.createElement('button');
            btn.id = 'dev-auto-fill-btn';
            btn.innerHTML = 'پر کردن خودکار فرم (تست)';
            btn.style.position = 'fixed';
            btn.style.bottom = '20px';
            btn.style.right = '20px';
            btn.style.zIndex = '9999';
            btn.style.padding = '10px 15px';
            btn.style.backgroundColor = '#4CAF50';
            btn.style.color = 'white';
            btn.style.border = 'none';
            btn.style.borderRadius = '4px';
            btn.style.cursor = 'pointer';
            btn.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';

            btn.addEventListener('click', function() {
                
                // بازنشانی به مرحله اول
                if (state && typeof state.updateStep === 'function') {
                    state.updateStep(1);
                }

                // شروع پر کردن خودکار پس از تاخیر
                setTimeout(() => {
                    autoFillForm();
                    
                    const stateChangeHandler = function() {
                        autoFillForm();
                    };

                    window.removeEventListener('stateUpdated', stateChangeHandler);
                    window.addEventListener('stateUpdated', stateChangeHandler);
                    
                    setTimeout(() => {
                        window.removeEventListener('stateUpdated', stateChangeHandler);
                    }, 5000); 
                }, NEXT_BUTTON_DELAY);
            });

            document.body.appendChild(btn);
        }

        // ایجاد دکمه پس از لود کامل صفحه
        setTimeout(createAutoFillButton, LONG_DELAY);
    }
});