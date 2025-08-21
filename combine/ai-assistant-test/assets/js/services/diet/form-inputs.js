// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-inputs.js
window.setupTextInput = function(inputId, displayId, field) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    const nextButton = document.querySelector(".next-step");

    // تنظیمات اولیه
    input.style.fontFamily = 'Vazir, Tahoma, sans-serif';
    input.style.fontSize = '16px';
    input.style.letterSpacing = '0';
    input.style.direction = 'rtl';
    input.style.textAlign = 'right';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('autocorrect', 'off');
    input.setAttribute('spellcheck', 'false');

    // تنظیم placeholder متناسب با فیلد
    const placeholderText = field === "firstName" ? "نام" : "نام خانوادگی";
    display.textContent = placeholderText;
    display.style.color = "var(--light-text-color)";
    
    // هماهنگ‌سازی عرض input و display
    const syncWidth = () => {
        display.style.width = input.offsetWidth + 'px';
    };
    syncWidth();
    window.addEventListener('resize', syncWidth);

    const updateDisplay = (value) => {
        if (value) {
            display.textContent = value;
            display.style.color = "var(--text-color)";
        } else {
            display.textContent = placeholderText;
            display.style.color = "var(--light-text-color)";
        }
        state.updateFormData(field, value);
        validateStep(state.currentStep);
        
        // هماهنگ‌سازی عرض پس از هر تغییر
        syncWidth();
    };

    let isComposing = false;
    let lastValue = '';

    input.addEventListener('compositionstart', () => {
        isComposing = true;
    });

    input.addEventListener('compositionend', () => {
        isComposing = false;
        if (input.value !== lastValue) {
            updateDisplay(input.value);
            lastValue = input.value;
        }
        setTimeout(() => {
            input.setSelectionRange(input.value.length, input.value.length);
        }, 0);
    });

    input.addEventListener("input", (e) => {
        if (!isComposing) {
            updateDisplay(e.target.value);
            lastValue = e.target.value;
            setTimeout(() => {
                input.setSelectionRange(input.value.length, input.value.length);
            }, 0);
        }
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            e.preventDefault();
            const cursorPos = input.selectionStart;
            const newPos = e.key === 'ArrowLeft' ? Math.min(cursorPos + 1, input.value.length) 
                                              : Math.max(cursorPos - 1, 0);
            input.setSelectionRange(newPos, newPos);
        }
        
        // اضافه کردن مدیریت کلید Enter
        if (e.key === "Enter") {
            e.preventDefault();
            
            if (inputId === "first-name-input") {
                // از فیلد نام به فیلد نام خانوادگی برو
                document.getElementById("last-name-input").focus();
            } else if (inputId === "last-name-input" && input.value.trim()) {
                // از فیلد نام خانوادگی به مرحله بعد برو
                if (nextButton && !nextButton.disabled) {
                    nextButton.click();
                }
            }
        }
    });

    input.addEventListener("focus", () => {
        setTimeout(() => {
            input.setSelectionRange(input.value.length, input.value.length);
        }, 0);
    });

    input.addEventListener("blur", () => {
        if (!input.value.trim()) {
            display.textContent = field === "firstName" ? "نام" : "نام خانوادگی";
            display.style.color = "var(--light-text-color)";
            syncWidth();
        }
    });

    // مقدار اولیه
    updateDisplay(input.value);
};

window.setupInput = function(inputId, displayId, field) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    const nextButton = document.querySelector(".next-step");

    if (nextButton) nextButton.disabled = true;

    const updateDisplay = (value) => {
        display.textContent = value ? 
            `${value} ${field === "age" ? "سال" : field === "height" ? "سانتی‌متر" : "کیلوگرم"}` : 
            `0 ${field === "age" ? "سال" : field === "height" ? "سانتی‌متر" : "کیلوگرم"}`;
        
        // تغییر این خط برای استفاده از متغیرهای CSS
        display.style.color = value ? "var(--text-color)" : "var(--light-text-color)";
        
        state.updateFormData(field, value ? parseInt(value) : null);
        
        if (field === "weight" && state.formData.height && value) {
            calculateBMI(state.formData.height, parseInt(value));
        }
    };

    input.addEventListener("input", () => {
        let value = input.value.replace(/\D/g, "");
        if (field === "age" && value.length > 2) value = value.slice(0, 2);
        else if ((field === "height" || field === "weight") && value.length > 3) value = value.slice(0, 3);
        
        input.value = value;
        updateDisplay(value);
        
        if (input.type === "text") {
            setTimeout(() => input.setSelectionRange(value.length, value.length), 0);
        }
        
        validateStep(state.currentStep);
    });

    input.addEventListener("click", () => {
        const value = input.value.replace(/\D/g, "");
        if (input.type === "text") input.setSelectionRange(value.length, value.length);
    });

    input.addEventListener("blur", () => {
        const value = input.value.trim();
        if (!value) updateDisplay("");
        validateStep(state.currentStep);
    });
}

window.setupOptionSelection = function(selector, key) {
    document.querySelectorAll(selector).forEach(el => {
        el.addEventListener("click", () => {
            const confirmCheckbox = document.getElementById("confirm-terms");
            if (!confirmCheckbox.checked) {
                alert("لطفاً ابتدا شرایط استفاده را تأیید کنید");
                return;
            }

            document.querySelectorAll(selector).forEach(opt => {
                opt.classList.remove("selected");
                opt.style.transform = "";
                opt.style.boxShadow = "";
            });
            
            el.classList.add("selected");
            el.classList.add("selected-with-effect");
            
            setTimeout(() => {
                el.classList.remove("selected-with-effect");
                state.updateFormData(key, el.dataset[key]);
                
                el.style.transform = "translateY(-3px)";
                el.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
                
                setTimeout(() => {
                    navigateToStep(state.currentStep + 1);
                }, 250);
            }, 150);
        });
    });
}

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
    
    setupTextInput("first-name-input", "first-name-display", "firstName");
    setupTextInput("last-name-input", "last-name-display", "lastName");

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
    
    document.querySelectorAll('.real-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            
            if (this.checked) {
                label.classList.add('checked-animation');
                
                // حذف انیمیشن پس از اجرا
                setTimeout(() => {
                    label.classList.remove('checked-animation');
                }, 800);
            }
        });
    });    
});