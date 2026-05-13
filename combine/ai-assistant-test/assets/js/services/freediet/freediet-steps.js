document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-freediet-form]').forEach(function (wrapper) {
    var currentStep = 1;
    var steps = wrapper.querySelectorAll('.fd-step');
    var totalSteps = steps.length;

    var currentStepEl = wrapper.querySelector('[data-fd-current-step]');
    var totalStepsEl = wrapper.querySelector('[data-fd-total-steps]');
    var progressBar = wrapper.querySelector('[data-fd-progress-bar]');
    var backBtn = wrapper.querySelector('[data-fd-back]');
    var nextWrap = wrapper.querySelector('[data-fd-next-wrap]');
    var submitWrap = wrapper.querySelector('[data-fd-submit-wrap]');
    var nextBtn = wrapper.querySelector('[data-fd-next]');

    function renderStep() {
      steps.forEach(function (el) {
        var step = parseInt(el.getAttribute('data-step'), 10);
        var isActive = step === currentStep;

        el.style.display = isActive ? 'block' : 'none';
        el.classList.toggle('fd-step--active', isActive);
      });

      if (currentStepEl) {
        currentStepEl.textContent = currentStep;
      }

      if (totalStepsEl) {
        totalStepsEl.textContent = totalSteps;
      }

      if (progressBar) {
        var percent = totalSteps > 1 ? ((currentStep - 1) / (totalSteps - 1)) * 100 : 100;
        progressBar.style.width = percent + '%';
      }

      if (backBtn) {
        backBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
      }

      if (nextBtn) {
        nextBtn.textContent = currentStep < totalSteps ? 'گام بعد' : 'پایان';
      }

      if (nextWrap) {
        nextWrap.style.display = 'flex';
      }

      if (submitWrap) {
        submitWrap.style.display = 'none';
      }
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (currentStep < totalSteps) {
          currentStep++;
          renderStep();
        } else {
          if (nextWrap) {
            nextWrap.style.display = 'none';
          }

          if (submitWrap) {
            submitWrap.style.display = 'flex';
          }
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

    renderStep();
  });
});