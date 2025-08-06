// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-inputs.js
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