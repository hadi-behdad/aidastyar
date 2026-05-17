(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-freediet-form]').forEach(function (wrapper) {
            // Helper: تبدیل اعداد انگلیسی به فارسی (محلی درون حلقه)
            function toPersianDigits(num) {
                var persianDigits = '۰۱۲۳۴۵۶۷۸۹';
                return num.toString().replace(/\d/g, function(d) {
                    return persianDigits[parseInt(d)];
                });
            }

            var currentStep = 1;
            var steps       = wrapper.querySelectorAll('[data-freediet-step]');
            var totalSteps  = steps.length;

            var currentStepEl = wrapper.querySelector('[data-freediet-current-step]');
            var totalStepsEl  = wrapper.querySelector('[data-freediet-total-steps]');
            var progressBar   = wrapper.querySelector('#freediet-progress-bar-container .freediet-progress-bar');
            var backBtn       = wrapper.querySelector('[data-freediet-back]');
            var nextContainer = wrapper.querySelector('#freediet-next-button-container');
            var submitContainer = wrapper.querySelector('#freediet-submit-button-container');
            var nextBtn       = wrapper.querySelector('[data-freediet-next]');
            var submitBtn     = submitContainer ? submitContainer.querySelector('.freediet-btn') : null;
            var topBackBtn    = wrapper.querySelector('#freediet-back-button');
            
            // ذخیره مقادیر انتخاب شده برای هر گام
            var selectedValues = {
                1: null,
                2: null,
                3: null,
                4: null,
                5: null
            };
            
            var stepConfigs = {
                1: { id: 'freediet-wrist-options', inputId: 'freediet-wrist-value', message: 'لطفاً ابتدا اندازه مچ دست خود را انتخاب کنید' },
                2: { id: 'freediet-bodytype-options', inputId: 'freediet-bodytype-value', message: 'لطفاً ابتدا وضعیت وزن و اندام خود را انتخاب کنید' },
                3: { id: 'freediet-overeating-options', inputId: 'freediet-overeating-value', message: 'لطفاً ابتدا واکنش بدن به پرخوری را انتخاب کنید' },
                4: { id: 'freediet-fatpattern-options', inputId: 'freediet-fatpattern-value', message: 'لطفاً ابتدا فرم بدن و الگوی ذخیره چربی را انتخاب کنید' },
                5: { id: 'freediet-musclegain-options', inputId: 'freediet-musclegain-value', message: 'لطفاً ابتدا تجربه عضله‌سازی را انتخاب کنید' }
            };
            
            function updateButtonsState() {
                var isValid = selectedValues[currentStep] !== null && selectedValues[currentStep] !== undefined;
                
                if (nextBtn) {
                    nextBtn.disabled = !isValid;
                    nextBtn.style.opacity = isValid ? '1' : '0.5';
                    nextBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
                }
                
                if (submitBtn && currentStep === totalSteps) {
                    submitBtn.disabled = !isValid;
                    submitBtn.style.opacity = isValid ? '1' : '0.5';
                    submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
                }
            }
            
            function selectOption(stepNumber, value, targetCard) {
                var config = stepConfigs[stepNumber];
                if (!config) return;
                
                selectedValues[stepNumber] = value;
                
                var hiddenInput = wrapper.querySelector('#' + config.inputId);
                if (hiddenInput) hiddenInput.value = value;
                
                var container = wrapper.querySelector('#' + config.id);
                if (container) {
                    var allCards = container.querySelectorAll('.freediet-option-card');
                    allCards.forEach(function(card) {
                        card.classList.remove('selected');
                    });
                }
                
                if (targetCard) targetCard.classList.add('selected');
                updateButtonsState();
            }
            
            function initOptionsForStep(stepNumber) {
                var config = stepConfigs[stepNumber];
                if (!config) return;
                
                var container = wrapper.querySelector('#' + config.id);
                if (!container) return;
                
                var oldHandler = container._listener;
                if (oldHandler) {
                    container.removeEventListener('click', oldHandler);
                }
                
                var clickHandler = function(e) {
                    var card = e.target.closest('.freediet-option-card');
                    if (!card) return;
                    var value = card.getAttribute('data-value');
                    if (value === null) return;
                    if (selectedValues[stepNumber] === value) return;
                    selectOption(stepNumber, value, card);
                };
                
                container.addEventListener('click', clickHandler);
                container._listener = clickHandler;
            }
            
            function initAllOptions() {
                for (var step = 1; step <= totalSteps; step++) {
                    initOptionsForStep(step);
                }
            }
            
            function restoreCurrentStepSelection() {
                var config = stepConfigs[currentStep];
                if (!config) return;
                
                var savedValue = selectedValues[currentStep];
                var container = wrapper.querySelector('#' + config.id);
                if (!container) return;
                
                var allCards = container.querySelectorAll('.freediet-option-card');
                var found = false;
                
                allCards.forEach(function(card) {
                    if (savedValue !== null && card.getAttribute('data-value') === savedValue) {
                        card.classList.add('selected');
                        found = true;
                    } else {
                        card.classList.remove('selected');
                    }
                });
                
                if (savedValue !== null && !found) {
                    selectedValues[currentStep] = null;
                    var hiddenInput = wrapper.querySelector('#' + config.inputId);
                    if (hiddenInput) hiddenInput.value = '';
                }
                
                updateButtonsState();
            }
            
            function renderStep() {
                steps.forEach(function (el) {
                    var step = parseInt(el.getAttribute('data-freediet-step'), 10);
                    var isActive = step === currentStep;
                    el.style.display = isActive ? 'block' : 'none';
                    el.classList.toggle('freediet-step--active', isActive);
                });
            
                if (currentStepEl) currentStepEl.textContent = toPersianDigits(currentStep);
                if (totalStepsEl) totalStepsEl.textContent = toPersianDigits(totalSteps);
                
                var stepCounterCurrent = wrapper.querySelector('#freediet-current-step');
                var stepCounterTotal = wrapper.querySelector('#freediet-total-steps');
                if (stepCounterCurrent) stepCounterCurrent.textContent = toPersianDigits(currentStep);
                if (stepCounterTotal) stepCounterTotal.textContent = toPersianDigits(totalSteps);
            
                if (progressBar) {
                    var percent = totalSteps > 1 ? ((currentStep - 1) / (totalSteps - 1)) * 100 : 100;
                    progressBar.style.width = percent + '%';
                }
            
                if (currentStep < totalSteps) {
                    if (nextContainer) nextContainer.style.display = 'flex';
                    if (submitContainer) submitContainer.style.display = 'none';
                    if (nextBtn) nextBtn.textContent = 'گام بعد';
                } else {
                    if (nextContainer) nextContainer.style.display = 'none';
                    if (submitContainer) submitContainer.style.display = 'flex';
                }
                
                restoreCurrentStepSelection();
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!selectedValues[currentStep]) {
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
                    e.stopPropagation();
            
                    // بررسی اینکه مرحله آخر مقدار انتخاب شده داشته باشد
                    if (!selectedValues[totalSteps]) {
                        alert(stepConfigs[totalSteps]?.message || 'لطفاً ابتدا گزینه مرحله آخر را انتخاب کنید');
                        return;
                    }
            
                    // ارسال رویداد سفارشی حاوی داده‌های کاربر
                    var customEvent = new CustomEvent('freedietFormSubmit', {
                        detail: {
                            selectedValues: selectedValues,  // شیء شامل مقادیر مراحل 1 تا 5
                            totalSteps: totalSteps
                        },
                        bubbles: true,
                        cancelable: true
                    });
                    form.dispatchEvent(customEvent);
                });
            }
            
            initAllOptions();
            if (nextBtn && totalSteps > 1) {
                nextBtn.disabled = true;
                nextBtn.style.opacity = '0.5';
                nextBtn.style.cursor = 'not-allowed';
            }
            
            renderStep();
        });
    });
})();