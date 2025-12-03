<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/farsi-num-functions.php
 * Functions for AI Assistant Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// اضافه کردن اسکریپت تبدیل اعداد (نسخه بهینه‌تر)
function add_persian_numbers_script() {
    ?>
    <script>
    (function($) {
        // تابع تبدیل اعداد به فارسی
        function convertToPersianNumbers(input) {
            var persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return input.replace(/\d/g, function(m) {
                return persianNumbers[parseInt(m)];
            });
        }
        
        function convertAllNumbers() {
            // انتخاب تمام عناصر به جز اسکریپت‌ها، استایل‌ها و کلاس‌های خاص
            jQuery('body').find('*:not(#step-counter, #step-counter *, script, style, noscript, .step-counter, .no-convert)').contents().each(function() {
                
                // بررسی اینکه نود متنی باشد و خالی نباشد
                if (this.nodeType === 3 && this.textContent.trim() !== '') {
                    
                    // اطمینان حاصل کنید که والد این متن اسکریپت یا استایل نیست
                    var parentTag = this.parentNode.nodeName.toUpperCase();
                    if (parentTag !== 'SCRIPT' && parentTag !== 'STYLE' && parentTag !== 'NOSCRIPT') {
                        this.textContent = convertToPersianNumbers(this.textContent);
                    }
                }
            });
        }

        
        // اجرا پس از لود کامل صفحه
        $(document).ready(function() {
            convertAllNumbers();
        });
        
        // برای محتوای پویا (مثلاً در AJAX)
        $(document).ajaxComplete(function() {
            setTimeout(convertAllNumbers, 100);
        });
        
    })(jQuery);
    </script>
    <?php
}
add_action('wp_footer', 'add_persian_numbers_script');