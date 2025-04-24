// /home/aidastya/public_html/wp-content/themes/woodmart-child/assets/js/script.js
const setupAdditionalInfoSelection = (currentStep) => {
    const checkboxes = document.querySelectorAll('#additional-info-selection .real-checkbox');
    const nextButton = document.querySelector('.next-step');
    const noneCheckbox = document.getElementById('info-none');
    
    if (currentStep !== 10) return;

    nextButton.disabled = true;

    const validateForm = () => {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        nextButton.disabled = !anyChecked;
    };

    checkboxes.forEach(checkbox => {
        const handleCheckboxChange = function() {
            if (this === noneCheckbox && this.checked) {
                checkboxes.forEach(cb => {
                    if (cb !== noneCheckbox) {
                        cb.checked = false;
                        cb.nextElementSibling.classList.remove('checked');
                    }
                });
            } else if (this.checked) {
                noneCheckbox.checked = false;
                noneCheckbox.nextElementSibling.classList.remove('checked');
            }
            
            const label = this.nextElementSibling;
            label.classList.add('checked-animation');
            
            setTimeout(() => {
                label.classList.remove('checked-animation');
                if (this.checked) {
                    label.classList.add('checked');
                } else {
                    label.classList.remove('checked');
                }
            }, 800);
            
            validateForm();
        };
        
        checkbox.removeEventListener('change', handleCheckboxChange);
        checkbox.addEventListener('change', handleCheckboxChange);
    });

    validateForm();
};

const setupFoodRestrictionSelection = (currentStep) => {
    const checkboxes = document.querySelectorAll('#food-restriction-selection .real-checkbox');
    const nextButton = document.querySelector('.next-step');
    const noneCheckbox = document.getElementById('restriction-none');
    
    if (currentStep !== 11) return;

    nextButton.disabled = true;

    const validateForm = () => {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        nextButton.disabled = !anyChecked;
    };

    checkboxes.forEach(checkbox => {
        const handleCheckboxChange = function() {
            if (this === noneCheckbox && this.checked) {
                checkboxes.forEach(cb => {
                    if (cb !== noneCheckbox) {
                        cb.checked = false;
                        cb.nextElementSibling.classList.remove('checked');
                    }
                });
            } else if (this.checked) {
                noneCheckbox.checked = false;
                noneCheckbox.nextElementSibling.classList.remove('checked');
            }
            
            const label = this.nextElementSibling;
            label.classList.add('checked-animation');
            
            setTimeout(() => {
                label.classList.remove('checked-animation');
                if (this.checked) {
                    label.classList.add('checked');
                } else {
                    label.classList.remove('checked');
                }
            }, 800);
            
            validateForm();
        };
        
        checkbox.removeEventListener('change', handleCheckboxChange);
        checkbox.addEventListener('change', handleCheckboxChange);
    });

    validateForm();
};

const setupConfirmationCheckbox = (currentStep) => {
    const nextButton = document.querySelector('.next-step');
    const confirmCheckbox = document.getElementById('confirm-info');
    
    if (currentStep !== 12) return;

    // Check if checkbox is already checked when entering the step
    nextButton.disabled = !confirmCheckbox.checked;

    const validateForm = () => {
        nextButton.disabled = !confirmCheckbox.checked;
    };

    confirmCheckbox.addEventListener('change', function() {
        const label = this.nextElementSibling;
        label.classList.add('checked-animation');
        
        setTimeout(() => {
            label.classList.remove('checked-animation');
            if (this.checked) {
                label.classList.add('checked');
            } else {
                label.classList.remove('checked');
            }
        }, 800);
        
        validateForm();
    });

    validateForm();
};

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
    
    const totalSteps = document.querySelectorAll(".step").length;
    const state = {
        currentStep: 1,
        formData: {},
        updateStep(step) {
            this.currentStep = step;
            showStep(step);
            updateStepCounter(step);
            updateProgressBar(step);
        },
        updateFormData(key, value) {
            this.formData[key] = value;
            validateStep(this.currentStep);
        }
    };
    
    const showStep = (step) => {
        const stepElements = [
            "gender-selection-step",
            "goal-selection-step",
            "age-input-step", 
            "height-input-step",
            "weight-input-step",
            "target-weight-step",
            "empty-step-after-6",
            "activity-selection-step",
            "meal-selection-step",
            "additional-info-step",
            "food-restriction-step",
            "new-step-before-final",
            "final-step"
        ];
        
        document.querySelectorAll(".step").forEach(el => el.classList.remove("active"));
        const currentStepElement = document.getElementById(stepElements[step - 1]);
        if (currentStepElement) currentStepElement.classList.add("active");

if (step === 7) {
    const goal = state.formData.goal;
    const ctx = document.getElementById('goal-chart').getContext('2d');
    const messageEl = document.querySelector('.chart-message');
    const currentWeight = state.formData.weight;
    const targetWeight = state.formData.targetWeight;
    
    if (window.goalChart) {
        window.goalChart.destroy();
    }

    // Create 5 intermediate points for smoother curve
    const pointsCount = 7; // Start + 5 intermediate + End
    const dataPoints = [];
    const labels = Array(pointsCount).fill('');

    if (goal === 'weight-loss') {
        const totalLoss = currentWeight - targetWeight;
        for (let i = 0; i < pointsCount; i++) {
            const progress = i / (pointsCount - 1);
            const easedProgress = 1 - Math.pow(1 - progress, 1.5);
            dataPoints.push(currentWeight - (totalLoss * easedProgress));
        }
    } else if (goal === 'weight-gain') {
        const totalGain = targetWeight - currentWeight;
        for (let i = 0; i < pointsCount; i++) {
            const progress = i / (pointsCount - 1);
            const easedProgress = Math.pow(progress, 1.5);
            dataPoints.push(currentWeight + (totalGain * easedProgress));
        }
    } else { // fitness
        const totalChange = Math.abs(targetWeight - currentWeight);
        for (let i = 0; i < pointsCount; i++) {
            const progress = i / (pointsCount - 1);
            const easedProgress = progress < 0.5 
                ? 2 * Math.pow(progress, 1.5)
                : 1 - Math.pow(-2 * progress + 2, 1.5) / 2;
            dataPoints.push(
                currentWeight > targetWeight 
                    ? currentWeight - (totalChange * easedProgress)
                    : currentWeight + (totalChange * easedProgress)
            );
        }
    }

    // Calculate dynamic height based on viewport
    const isMobile = window.innerWidth <= 768;
    const chartHeight = isMobile ? Math.min(window.innerHeight * 0.5, 400) : 400;

    const chartConfigs = {
        'weight-loss': {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'روند تغییر وزن',
                    data: dataPoints,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: ['#ff6b6b', ...Array(pointsCount-2).fill('#ff6b6b55'), '#ff6b6b'],
                    pointBorderColor: ['#ff6b6b', ...Array(pointsCount-2).fill('#ff6b6b55'), '#ff6b6b'],
                    pointRadius: [5, ...Array(pointsCount-2).fill(3), 5],
                    pointHoverRadius: [7, ...Array(pointsCount-2).fill(5), 7],
                    pointHoverBorderWidth: [2, ...Array(pointsCount-2).fill(1), 2]
                }]
            },
            options: getChartOptions('روند کاهش وزن شما', chartHeight),
            message: `وزن فعلی: ${currentWeight} کیلوگرم | وزن هدف: ${targetWeight} کیلوگرم`
        },
        'weight-gain': {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'روند تغییر وزن',
                    data: dataPoints,
                    borderColor: '#66bb6a',
                    backgroundColor: 'rgba(102, 187, 106, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: ['#66bb6a', ...Array(pointsCount-2).fill('#66bb6a55'), '#66bb6a'],
                    pointBorderColor: ['#66bb6a', ...Array(pointsCount-2).fill('#66bb6a55'), '#66bb6a'],
                    pointRadius: [5, ...Array(pointsCount-2).fill(3), 5],
                    pointHoverRadius: [7, ...Array(pointsCount-2).fill(5), 7],
                    pointHoverBorderWidth: [2, ...Array(pointsCount-2).fill(1), 2]
                }]
            },
            options: getChartOptions('روند افزایش وزن شما', chartHeight),
            message: `وزن فعلی: ${currentWeight} کیلوگرم | وزن هدف: ${targetWeight} کیلوگرم`
        },
        'fitness': {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'روند تغییر وزن',
                    data: dataPoints,
                    borderColor: '#ffee58',
                    backgroundColor: 'rgba(255, 238, 88, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: ['#ffee58', ...Array(pointsCount-2).fill('#ffee5855'), '#ffee58'],
                    pointBorderColor: ['#ffee58', ...Array(pointsCount-2).fill('#ffee5855'), '#ffee58'],
                    pointRadius: [5, ...Array(pointsCount-2).fill(3), 5],
                    pointHoverRadius: [7, ...Array(pointsCount-2).fill(5), 7],
                    pointHoverBorderWidth: [2, ...Array(pointsCount-2).fill(1), 2]
                }]
            },
            options: getChartOptions('روند تناسب اندام شما', chartHeight),
            message: `وزن فعلی: ${currentWeight} کیلوگرم | وزن هدف: ${targetWeight} کیلوگرم`
        }
    };

    function getChartOptions(title, height) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: title,
                    font: {
                        family: 'Vazir',
                        size: isMobile ? 16 : 18
                    },
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataIndex === 0) {
                                return `وزن فعلی: ${context.raw.toFixed(1)} کیلوگرم`;
                            } else if (context.dataIndex === context.dataset.data.length - 1) {
                                return `وزن هدف: ${context.raw.toFixed(1)} کیلوگرم`;
                            }
                            return '';
                        }
                    },
                    titleFont: {
                        family: 'Vazir',
                        size: isMobile ? 12 : 14
                    },
                    bodyFont: {
                        family: 'Vazir',
                        size: isMobile ? 12 : 14
                    },
                    footerFont: {
                        family: 'Vazir'
                    },
                    displayColors: false,
                    padding: isMobile ? 8 : 12
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: Math.min(currentWeight, targetWeight) - 5,
                    max: Math.max(currentWeight, targetWeight) + 5,
                    ticks: {
                        callback: function(value) {
                            return value + ' کیلوگرم';
                        },
                        font: {
                            size: isMobile ? 12 : 14
                        },
                        padding: isMobile ? 5 : 10
                    },
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: isMobile ? 12 : 14
                        }
                    }
                }
            },
            elements: {
                line: {
                    cubicInterpolationMode: 'monotone'
                },
                point: {
                    hitRadius: 10
                }
            },
            layout: {
                padding: {
                    left: isMobile ? 5 : 10,
                    right: isMobile ? 5 : 10,
                    top: isMobile ? 5 : 10,
                    bottom: isMobile ? 5 : 10
                }
            }
        };
    }
    
    // Set dynamic height for chart container
    const chartContainer = document.querySelector('.chart-container');
    chartContainer.style.height = `${chartHeight}px`;
    chartContainer.style.minHeight = '300px'; // Minimum height for very small screens
    
    window.goalChart = new Chart(ctx, chartConfigs[goal]);
    messageEl.textContent = chartConfigs[goal].message;
    
    chartContainer.classList.add('animate-chart');
}
        
        const nextButtonContainer = document.getElementById("next-button-container");
        if (nextButtonContainer) nextButtonContainer.style.display = [1, 2, 8, 9].includes(step) ? "none" : "block";
    
        if ([3, 4, 5, 6, 7].includes(step)) {
            const inputId = `${["age", "height", "weight", "target-weight"][step - 3]}-input`;
            const inputElement = document.getElementById(inputId);
            if (inputElement) inputElement.focus();
            validateStep(step);
        }
        
        if (step === 10) {
            setupAdditionalInfoSelection(step);
        } else if (step === 11) {
            setupFoodRestrictionSelection(step);
        } else if (step === 12) {
            showSummary();
            setupConfirmationCheckbox(step);
        }
    };
    
    const validateStep = (step) => {
        const nextButton = document.querySelector(".next-step");
        const errorMessages = {
            3: { field: "age", min: 5, max: 100, unit: "سال", label: "سن", errorId: "age-error" },
            4: { field: "height", min: 100, max: 250, unit: "سانتی‌متر", label: "قد", errorId: "height-error" },
            5: { field: "weight", min: 30, max: 300, unit: "کیلوگرم", label: "وزن", errorId: "weight-error" },
            6: { field: "targetWeight", min: 30, max: 300, unit: "کیلوگرم", label: "وزن هدف", errorId: "targetWeight-error" }
        };
    
        if (errorMessages[step]) {
            const { field, min, max, unit, label, errorId } = errorMessages[step];
            const value = state.formData[field];
            const errorElement = document.getElementById(errorId);
            
            if (!errorElement) {
                console.error(`Element with id ${errorId} not found`);
                return;
            }
    
            if (value >= min && value <= max) {
                errorElement.innerHTML = `<span class="tick-icon"></span> مقدار وارد شده معتبر است.`;
                errorElement.classList.add("valid");
                nextButton.disabled = false;
            } else {
                errorElement.textContent = `${label} باید بین ${min} تا ${max} ${unit} وارد شود`;
                errorElement.classList.remove("valid");
                nextButton.disabled = true;
            }
        }
    };

    const updateStepCounter = (step) => {
        document.getElementById("current-step").textContent = step;
        document.getElementById("total-steps").textContent = totalSteps;
    };

    const updateProgressBar = (step) => {
        const progress = ((step - 1) / (totalSteps - 1)) * 100;
        document.getElementById("progress-bar").style.width = `${progress}%`;
    };

    const navigateToStep = (step) => {
        if (step >= 1 && step <= totalSteps) {
            state.updateStep(step);
            history.pushState({ step: state.currentStep }, "", `#step-${state.currentStep}`);
        }
    };

    const handleNextStep = () => {
        if (state.currentStep < totalSteps) navigateToStep(state.currentStep + 1);
        else if (state.currentStep === totalSteps) showSummary();
    };

    const handleBackStep = () => {
        if (state.currentStep > 1) navigateToStep(state.currentStep - 1);
    };

    const handleEnterKey = (event) => {
        if (event.key === "Enter" && event.target.matches("input[type='text']")) {
            event.preventDefault();
            document.querySelector(".next-step").click();
        }
    };

    const showSummary = () => {
        const summaryContainer = document.getElementById('summary-container');
        const nextButton = document.querySelector('.next-step');
        const confirmCheckbox = document.getElementById('confirm-info');
        
        nextButton.disabled = true;
        
        confirmCheckbox.addEventListener('change', function() {
            nextButton.disabled = !this.checked;
        });
    
        const { gender, age, height, weight, targetWeight, goal, activity, meals } = state.formData;
        const goalText = { "weight-loss": "کاهش وزن", "weight-gain": "افزایش وزن", "fitness": "تناسب اندام"}[goal];
        const activityText = { "very-low": "خیلی کم (کمتر از 1 ساعت)", "low": "کم (1 تا 2 ساعت)", "medium": "متوسط (2 تا 4 ساعت)", "high": "زیاد (بیشتر از 4 ساعت)" }[activity];
        const mealsText = { "2": "۲ وعده", "3": "۳ وعده", "4": "۴ وعده", "more": "بیشتر" }[meals];
        
        // جمع آوری اطلاعات additional-info
        const additionalInfo = [];
        if (document.getElementById('info-diabetes').checked) additionalInfo.push('دیابت');
        if (document.getElementById('info-pressure').checked) additionalInfo.push('فشار خون');
        if (document.getElementById('info-thyroid').checked) additionalInfo.push('مشکلات تیروئید');
        if (document.getElementById('info-allergy').checked) additionalInfo.push('حساسیت غذایی');
        if (document.getElementById('info-none').checked) additionalInfo.push('هیچکدام');
        
        // جمع آوری اطلاعات food-restriction
        const foodRestrictions = [];
        if (document.getElementById('restriction-vegetarian').checked) foodRestrictions.push('گیاهخواری');
        if (document.getElementById('restriction-no-seafood').checked) foodRestrictions.push('عدم مصرف غذای دریایی');
        if (document.getElementById('restriction-none').checked) foodRestrictions.push('بدون محدودیت');
        
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
                <span class="summary-label">فعالیت روزانه:</span>
                <span class="summary-value">${activityText}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">تعداد وعده‌های غذایی:</span>
                <span class="summary-value">${mealsText}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">اطلاعات تکمیلی:</span>
                <span class="summary-value">${additionalInfo.join('، ') || 'ثبت نشده'}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">محدودیت‌های غذایی:</span>
                <span class="summary-value">${foodRestrictions.join('، ') || 'ثبت نشده'}</span>
            </div>
        `;
    };

    const setupInput = (inputId, displayId, field) => {
        const input = document.getElementById(inputId);
        const display = document.getElementById(displayId);

		const updateDisplay = (value) => {
			display.textContent = value ? `${value} ${field === "age" ? "سال" : field === "height" ? "سانتی‌متر" : "کیلوگرم"}` : `0 ${field === "age" ? "سال" : field === "height" ? "سانتی‌متر" : "کیلوگرم"}`;
			display.style.color = value ? "#000" : "#999";
			state.updateFormData(field, value ? parseInt(value) : null);
			
			// Calculate BMI when both height and weight are available
			if (field === "weight" && state.formData.height && value) {
				calculateBMI(state.formData.height, parseInt(value));
			}
		};

        if (field === "weight" && state.formData.height && value) {
            calculateBMI(state.formData.height, parseInt(value));
        }
		
        input.addEventListener("input", () => {
            let value = input.value.replace(/\D/g, "");
            if (field === "age" && value.length > 2) value = value.slice(0, 2);
            else if ((field === "height" || field === "weight") && value.length > 3) value = value.slice(0, 3);
            input.value = value;
            updateDisplay(value);
            if (input.type === "text") setTimeout(() => input.setSelectionRange(value.length, value.length), 0);
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
    };

    const calculateBMI = (height, weight) => {
        const heightInMeters = height / 100;
        const bmiValue = document.getElementById('bmi-value');
        const bmiCategory = document.getElementById('bmi-category');
        const bmiIndicator = document.getElementById('bmi-indicator');
        const bmiContainer = document.getElementById('bmi-result-container');
        
        // Hide BMI section if weight is 0 or not entered
        if (!weight || weight === 0) {
            bmiContainer.style.opacity = '0.5';
            bmiIndicator.style.display = 'none';
            bmiValue.textContent = '0';
            bmiCategory.textContent = '';
            return;
        } else {
            bmiContainer.style.opacity = '1';
            bmiIndicator.style.display = 'block';
        }
        
        const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(1);
        bmiValue.textContent = bmi;
        
        // Set BMI category
        const categories = [
            { max: 18.5, text: 'کمبود وزن', color: '#4fc3f7' },
            { max: 25, text: 'وزن نرمال', color: '#66bb6a' },
            { max: 30, text: 'اضافه وزن', color: '#ffee58' },
            { max: 35, text: 'چاق', color: '#ffa726' },
            { max: Infinity, text: 'چاقی شدید', color: '#ef5350' }
        ];
        
        const category = categories.find(c => bmi < c.max);
        bmiCategory.textContent = category.text;
        bmiCategory.style.color = category.color;
        
        // Calculate indicator position
        let position;
        if (bmi < 18.5) {
            position = (bmi / 18.5) * 20;
        } else if (bmi < 25) {
            position = 20 + ((bmi - 18.5) / 6.5) * 20;
        } else if (bmi < 30) {
            position = 40 + ((bmi - 25) / 5) * 20;
        } else if (bmi < 35) {
            position = 60 + ((bmi - 30) / 5) * 20;
        } else {
            position = 80 + ((Math.min(bmi, 50) - 35) / 15) * 20;
        }
        
        // Smooth animation
        bmiIndicator.classList.add('animate-indicator');
        setTimeout(() => {
            bmiIndicator.style.left = `${Math.min(position, 100)}%`;
            bmiIndicator.style.transform = 'translateX(-50%)';
        }, 10);
        
        setTimeout(() => {
            bmiIndicator.classList.remove('animate-indicator');
        }, 800);
    };

    const setupOptionSelection = (selector, key) => {
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
                    }, 250); // زمان انتظار برای رفتن به مرحله بعد نصف شد
                }, 150); // زمان انیمیشن انتخاب نصف شد
            });
        });
    };

    navigateToStep(state.currentStep);
    document.querySelector(".next-step").addEventListener("click", handleNextStep);
    document.getElementById("back-button").addEventListener("click", handleBackStep);
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

    document.addEventListener("keydown", handleEnterKey);
});