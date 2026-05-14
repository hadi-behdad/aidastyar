document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-freediet-form]').forEach(function (wrapper) {
        var currentStep = 1;
        var steps       = wrapper.querySelectorAll('[data-freediet-step]');
        var totalSteps  = steps.length;

        var currentStepEl = wrapper.querySelector('[data-freediet-current-step]');
        var totalStepsEl  = wrapper.querySelector('[data-freediet-total-steps]');
        var progressBar   = document.querySelector('#freediet-progress-bar-container .freediet-progress-bar');
        var backBtn       = wrapper.querySelector('[data-freediet-back]');
        var nextContainer = document.getElementById('freediet-next-button-container');
        var submitContainer = document.getElementById('freediet-submit-button-container');
        var nextBtn       = document.querySelector('[data-freediet-next]');
        var topBackBtn    = document.getElementById('freediet-back-button');
        
        // متغیرهای ذخیره انتخاب کاربر برای هر گام
        var selectedValues = {
            1: null, // مچ دست
            2: null, // اندام دبیرستان
            3: null, // واکنش به پرخوری
            4: null, // الگوی ذخیره چربی
            5: null  // عضله‌سازی
        };
        
        // تنظیمات هر گام
        var stepConfigs = {
            1: { id: 'freediet-wrist-options', inputId: 'freediet-wrist-value', message: 'لطفاً ابتدا اندازه مچ دست خود را انتخاب کنید' },
            2: { id: 'freediet-bodytype-options', inputId: 'freediet-bodytype-value', message: 'لطفاً ابتدا وضعیت وزن و اندام خود را انتخاب کنید' },
            3: { id: 'freediet-overeating-options', inputId: 'freediet-overeating-value', message: 'لطفاً ابتدا واکنش بدن به پرخوری را انتخاب کنید' },
            4: { id: 'freediet-fatpattern-options', inputId: 'freediet-fatpattern-value', message: 'لطفاً ابتدا فرم بدن و الگوی ذخیره چربی را انتخاب کنید' },
            5: { id: 'freediet-musclegain-options', inputId: 'freediet-musclegain-value', message: 'لطفاً ابتدا تجربه عضله‌سازی را انتخاب کنید' }
        };
        
        // تابع عمومی برای مدیریت انتخاب گزینه‌ها در هر گام
        function initOptionsForStep(stepNumber) {
            var config = stepConfigs[stepNumber];
            if (!config) return;
            
            var options = document.querySelectorAll('#' + config.id + ' .freediet-option-card');
            var hiddenInput = document.getElementById(config.inputId);
            
            if (!options.length) return;
            
            options.forEach(function(option) {
                // حذف رویدادهای قبلی
                var newOption = option.cloneNode(true);
                option.parentNode.replaceChild(newOption, option);
                
                // بازیابی وضعیت انتخاب شده
                if (selectedValues[stepNumber] && newOption.getAttribute('data-value') === selectedValues[stepNumber]) {
                    newOption.classList.add('selected');
                }
                
                newOption.addEventListener('click', function() {
                    var value = this.getAttribute('data-value');
                    
                    options.forEach(function(opt) {
                        opt.classList.remove('selected');
                    });
                    
                    this.classList.add('selected');
                    selectedValues[stepNumber] = value;
                    if (hiddenInput) hiddenInput.value = value;
                    
                    updateNextButtonState();
                });
            });
        }
        
        // مقداردهی اولیه همه گام‌ها
        function initAllOptions() {
            for (var step = 1; step <= totalSteps; step++) {
                initOptionsForStep(step);
            }
        }
        
        // تابع بررسی اعتبار گام فعلی
        function isCurrentStepValid() {
            return selectedValues[currentStep] !== null && selectedValues[currentStep] !== undefined;
        }
        
        // به‌روزرسانی وضعیت دکمه Next بر اساس اعتبار گام
        function updateNextButtonState() {
            if (!nextBtn) return;
            var isValid = isCurrentStepValid();
            nextBtn.disabled = !isValid;
            nextBtn.style.opacity = isValid ? '1' : '0.5';
            nextBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }
        
        // بازیابی مقادیر ذخیره شده برای گام فعلی
        function restoreCurrentStepSelection() {
            var config = stepConfigs[currentStep];
            if (!config) return;
            
            var options = document.querySelectorAll('#' + config.id + ' .freediet-option-card');
            var savedValue = selectedValues[currentStep];
            
            options.forEach(function(opt) {
                if (savedValue && opt.getAttribute('data-value') === savedValue) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        }

        function renderStep() {
            steps.forEach(function (el) {
                var step = parseInt(el.getAttribute('data-freediet-step'), 10);
                var isActive = step === currentStep;
                el.style.display = isActive ? 'block' : 'none';
                el.classList.toggle('freediet-step--active', isActive);
            });
        
            if (currentStepEl) currentStepEl.textContent = currentStep;
            if (totalStepsEl)  totalStepsEl.textContent  = totalSteps;
            
            var stepCounterCurrent = document.getElementById('freediet-current-step');
            var stepCounterTotal = document.getElementById('freediet-total-steps');
            if (stepCounterCurrent) stepCounterCurrent.textContent = currentStep;
            if (stepCounterTotal) stepCounterTotal.textContent = totalSteps;
        
            if (progressBar) {
                var percent = totalSteps > 1 ? ((currentStep - 1) / (totalSteps - 1)) * 100 : 100;
                progressBar.style.width = percent + '%';
            }
        
            if (currentStep < totalSteps) {
                nextContainer.style.display = 'flex';
                submitContainer.style.display = 'none';
                if (nextBtn) nextBtn.textContent = 'گام بعد';
                updateNextButtonState();
            } else {
                nextContainer.style.display = 'none';
                submitContainer.style.display = 'flex';
            }
            
            // بازیابی انتخاب‌ها در گام فعلی
            restoreCurrentStepSelection();
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!isCurrentStepValid()) {
                    var message = stepConfigs[currentStep]?.message || 'لطفاً ابتدا گزینه مورد نظر را انتخاب کنید';
                    alert(message);
                    return;
                }
                if (currentStep < totalSteps) {
                    currentStep++;
                    renderStep();
                }
            });
        }

        if (backBtn) {
            backBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (currentStep > 1) {
                    currentStep--;
                    renderStep();
                }
            });
        }

        if (topBackBtn && backBtn) {
            var newTopBackBtn = topBackBtn.cloneNode(true);
            topBackBtn.parentNode.replaceChild(newTopBackBtn, topBackBtn);
            
            newTopBackBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (backBtn && currentStep > 1) {
                    backBtn.click();
                }
            });
        }

        var form = wrapper.querySelector('[data-fd-form]');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('مقادیر انتخاب شده:', selectedValues);
                alert('فرم FreeDiet با موفقیت ثبت شد.\n' +
                    'مچ دست: ' + (selectedValues[1] || 'انتخاب نشده') + '\n' +
                    'اندام دبیرستان: ' + (selectedValues[2] || 'انتخاب نشده') + '\n' +
                    'واکنش به پرخوری: ' + (selectedValues[3] || 'انتخاب نشده') + '\n' +
                    'الگوی ذخیره چربی: ' + (selectedValues[4] || 'انتخاب نشده') + '\n' +
                    'عضله‌سازی: ' + (selectedValues[5] || 'انتخاب نشده')
                );
            });
        }
        
        // مقداردهی اولیه همه گزینه‌ها
        initAllOptions();
        
        // غیرفعال کردن دکمه Next در ابتدا
        if (nextBtn && totalSteps > 1) {
            nextBtn.disabled = true;
            nextBtn.style.opacity = '0.5';
            nextBtn.style.cursor = 'not-allowed';
        }

        renderStep();
    });
});