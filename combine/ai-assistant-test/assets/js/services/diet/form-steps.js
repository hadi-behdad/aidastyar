// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-steps.js
window.showStep = function(step) {
    const stepElements = [
        "gender-selection-step",
        "goal-selection-step",
        "age-input-step", 
        "height-input-step",
        "weight-input-step",
        "target-weight-step",
        "goal-weight-display",
        "surgery-step",
        "hormonal-disorders-step",
        "stomach-discomfort-step",
        "water-intake-step",
        "activity-selection-step",
        "meal-selection-step",
        "additional-info-step",
        "diet-style-step",       // مرحله 15 جدید
        "food-limitations-step", // مرحله 16 جدید
        "food-preferences-step", // مرحله 17 جدید
        "terms-agreement-step",  // مرحله 18 جدید
        "confirm-submit-step"    // مرحله 19 جدید
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
    
    const currentStepElement = document.getElementById(stepElements[step - 1]);
    if (currentStepElement) {
        currentStepElement.classList.add("active");
        if (currentStepElement.id === "goal-weight-display") {
            currentStepElement.style.display = 'flex';
        }
    }

    if (step === STEPS.GOAL_DISPLAY) {
        const goalTitleElement = document.getElementById('goal-title-text');
        if (goalTitleElement) {
            const goalText = {
                "weight-loss": "هدف: کاهش وزن",
                "weight-gain": "هدف: افزایش وزن", 
                "fitness": "هدف: حفظ سلامت"
            }[state.formData.goal];
            
            goalTitleElement.textContent = goalText || "هدف: مشخص نشده";
        }
    
        const imageContainer = document.querySelector('#goal-weight-display .step7-image-container');
        let svgFile = '';
        
        if (state.formData.goal === 'weight-loss') {
            svgFile = wpVars.themeBasePath + '/assets/images/svg/weight-loss.svg';
        } else if (state.formData.goal === 'weight-gain' || state.formData.goal === 'fitness') {
            svgFile = wpVars.themeBasePath + '/assets/images/svg/weight-gain.svg';
        }
        
        imageContainer.innerHTML = `
            <div class="goal-title-container">
                <h2 class="goal-title" id="goal-title-text">
                    ${state.formData.goal === 'weight-loss' ? 'کاهش وزن' : 
                      state.formData.goal === 'weight-gain' ? 'افزایش وزن' : 
                      'حفظ سلامت'}
                </h2>
            </div>
            <object type="image/svg+xml" data="${svgFile}" class="goal-svg"></object>
            <div class="weight-display-container">
                <div class="weight-display-box target-weight">
                    <div class="weight-value">${state.formData.targetWeight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن هدف</div>
                </div>
                <div class="weight-display-box current-weight">
                    <div class="weight-value">${state.formData.weight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن فعلی</div>
                </div>
            </div>
        `;
    }
    
    const nextButtonContainer = document.getElementById("next-button-container");
    if (nextButtonContainer) {
        nextButtonContainer.style.display = [
            STEPS.GENDER, 
            STEPS.GOAL,
            STEPS.WATER_INTAKE, // اضافه شده
            STEPS.ACTIVITY, 
            STEPS.MEALS
        ].includes(step) ? "none" : "block";
    }

    if ([STEPS.AGE, STEPS.HEIGHT, STEPS.WEIGHT, STEPS.TARGET_WEIGHT, STEPS.WATER_INTAKE].includes(step)) {
        const inputId = `${["age", "height", "weight", "target-weight"][step - 3]}-input`;
        const inputElement = document.getElementById(inputId);
        if (inputElement) inputElement.focus();
        
        const nextButton = document.querySelector(".next-step");
        if (nextButton) nextButton.disabled = true;
        
        validateStep(step);
    }
    
    if (step === STEPS.WATER_INTAKE) {
        setupWaterIntakeSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.STOMACH) {
        setupStomachDiscomfortSelection(step);
    } 
    else if (step === STEPS.HORMONAL) {
        setupHormonalSelection(step);
    } 
    else if (step === STEPS.SURGERY) {
        setupSurgerySelection(step);
    } 
    else if (step === STEPS.ADDITIONAL_INFO) {
        setupAdditionalInfoSelection(step);
    } 
    else if (step === STEPS.DIET_STYLE) {
        setupDietStyleSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.FOOD_LIMITATIONS) {
        setupFoodLimitationsSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.FOOD_PREFERENCES) {
        setupFoodPreferencesSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.TERMS_AGREEMENT) {
        setupTermsAgreement(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.CONFIRMATION) {
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
    document.getElementById("current-step").textContent = step;
    document.getElementById("total-steps").textContent = totalSteps;
}

window.updateProgressBar = function(step) {
    const progress = ((step - 1) / (totalSteps - 1)) * 100;
    document.getElementById("progress-bar").style.width = `${progress}%`;
}

window.navigateToStep = function(step) {
    if (step >= 1 && step <= totalSteps) {
        state.updateStep(step);
        history.pushState({ step: state.currentStep }, "", `#step-${state.currentStep}`);
    }
}

window.handleNextStep = function() {
    if (state.currentStep < totalSteps) navigateToStep(state.currentStep + 1);
}

window.handleBackStep = function() {
    if (state.currentStep > 1) navigateToStep(state.currentStep - 1);
}

window.handleEnterKey = function(event) {
    // فقط در مراحل عددی (سن، قد، وزن، وزن هدف) و مرحله نهایی اجازه کار با Enter را بده
    const allowedSteps = [
        STEPS.AGE, 
        STEPS.HEIGHT, 
        STEPS.WEIGHT, 
        STEPS.TARGET_WEIGHT,
        STEPS.CONFIRMATION
    ];
    
    if (event.key === "Enter" && 
        allowedSteps.includes(state.currentStep) && 
        (event.target.matches("input[type='text']") || state.currentStep === STEPS.CONFIRMATION)) {
        
        // جلوگیری از رفتار پیش‌فرض Enter
        event.preventDefault();
        
        // در مراحل عددی، رفتن به مرحله بعد
        if (state.currentStep !== STEPS.CONFIRMATION) {
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