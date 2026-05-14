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

        function renderStep() {
            steps.forEach(function (el) {
                var step = parseInt(el.getAttribute('data-freediet-step'), 10);
                var isActive = step === currentStep;
                el.style.display = isActive ? 'block' : 'none';
                el.classList.toggle('freediet-step--active', isActive);
            });
        
            // به‌روزرسانی شمارنده مرحله
            if (currentStepEl) currentStepEl.textContent = currentStep;
            if (totalStepsEl)  totalStepsEl.textContent  = totalSteps;
            
            // به‌روزرسانی شمارنده بالای صفحه
            var stepCounterCurrent = document.getElementById('freediet-current-step');
            var stepCounterTotal = document.getElementById('freediet-total-steps');
            if (stepCounterCurrent) stepCounterCurrent.textContent = currentStep;
            if (stepCounterTotal) stepCounterTotal.textContent = totalSteps;
        
            if (progressBar) {
                var percent = totalSteps > 1 ? ((currentStep - 1) / (totalSteps - 1)) * 100 : 100;
                progressBar.style.width = percent + '%';
            }
        
            // نمایش/مخفی کردن دکمه‌های پایین صفحه
            if (currentStep < totalSteps) {
                nextContainer.style.display = 'flex';
                submitContainer.style.display = 'none';
                if (nextBtn) nextBtn.textContent = 'گام بعد';
            } else {
                nextContainer.style.display = 'none';
                submitContainer.style.display = 'flex';
            }
        }

        // رویداد دکمه Next
        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (currentStep < totalSteps) {
                    currentStep++;
                    renderStep();
                }
            });
        }

        // رویداد دکمه Back مخفی
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

        // اتصال دکمه جدید بالای صفحه
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

        // رویداد ثبت نهایی فرم
        var form = wrapper.querySelector('[data-fd-form]');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                alert('فرم FreeDiet با موفقیت ثبت شد.');
            });
        }

        renderStep();
    });
});