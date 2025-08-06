// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/diet.js
jQuery(document).ready(function($) {
    const form = $('#multi-step-form');
  //  const steps = form.find('.step');
  //  let currentStep = 0;

    const resultDiv = $('#ai-diet-result');
    const responseContent = resultDiv.find('.ai-response-content');
    const submitBtn = form.find('.final-submit');    

    window.addEventListener('formSubmitted', function(event) {
        // گرفتن جیسون از جاوااسکریپت
        const receivedUserData = event.detail.formData;
        console.log('Form data received in second script:', receivedUserData);

        submitBtn.prop('disabled', true).text(aiAssistantVars.i18n.loading);
        resultDiv.hide();
        
        $.ajax({
            url: aiAssistantVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ai_assistant_process',
                userData: JSON.stringify(receivedUserData), // تبدیل شیء/آرایه به JSON,
                security: aiAssistantVars.nonce,
                service_id: 'diet',
                price: 10000000
            },
            success: function(response) {
                console.log('response.success:', response.success);
                if (response.success) {
                    
                    console.log('response.success:', response.success);
                    document.getElementById('summary-container').style.display = 'none';
                    document.getElementById('confirmation-checkbox').style.display = 'none';
                    document.getElementById('submit-button-container').style.display = 'none';
                    document.getElementById('SubmitBtn').style.display = 'none';
                    document.getElementById('downloadPdf').style.display = 'inline-block';
                    const apiResponse = response.data.response;
                    responseContent.html(apiResponse);
                  //  const safeResponse = $('<div/>').text(apiResponse).html();
                  //  responseContent.html(safeResponse.replace(/\n/g, '<br>'));
                    
                    

                    resultDiv.hide().fadeIn(400);
                } else {
                    alert(aiAssistantVars.i18n.error + ': ' + response.data);
                }
            },
            error: function(xhr) {
                let errorMsg = 'خطا در ارتباط با سرور';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.data?.message || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.responseText || errorMsg;
                    }
                }
                alert(aiAssistantVars.i18n.error + ': ' + errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('ارسال درخواست');
            }
        });        
    });
    
    
    document.getElementById('downloadPdf').addEventListener('click', function () {
        const element = document.querySelector('.ai-response-content');
    
        // ذخیره استایل اصلی برای برگردوندن بعد از تولید PDF
        const originalOverflow = element.style.overflow;
        const originalHeight = element.style.height;
        const originalMaxHeight = element.style.maxHeight;
    
        // حذف محدودیت‌ها برای نمایش کل محتوا
        element.style.overflow = 'visible';
        element.style.height = 'auto';
        element.style.maxHeight = 'none';
    
        const opt = {
            margin:       0.5,
            filename:     'برنامه-تغذیه.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
    
        html2pdf().set(opt).from(element).save().then(() => {
            // برگردوندن به حالت اولیه
            element.style.overflow = originalOverflow;
            element.style.height = originalHeight;
            element.style.maxHeight = originalMaxHeight;
        });
    });
});