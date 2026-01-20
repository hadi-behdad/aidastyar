<?php
// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/services/diet/template-parts/form.php
$theme_assets = get_stylesheet_directory_uri();
?>
<form id="multi-step-form" class="ai-multistep-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
    <input type="hidden" name="action" value="submit_diet_form">
    <?php wp_nonce_field('diet_form_nonce', 'diet_form_security'); ?>
    
    <div id="header-container">
        <div id="step-counter"><span id="current-step">1</span>/<span id="total-steps">20</span></div>
        <button type="button" id="back-button">›</button>
        
        <div id="header-logo" onclick="window.location.href='<?php echo home_url(); ?>'">
            AiDASTYAR
        </div>
    </div>
    
    <div id="progress-bar-container">
        <div id="progress-bar"></div>
    </div>

    <!-- Step 1: Gender Selection -->
    <div id="gender-selection-step" class="step active">
        <h1 id="form-title">سیستم هوشمند رژیم غذایی هوش مصنوعی</h1>
        <h2>جنسیت خود را انتخاب کنید</h2>
        <div id="gender-selection">
            <div class="gender-option" data-gender="male"><img src="<?php echo $theme_assets; ?>/assets/images/webp/male.webp" alt="مرد"></div>
            <div class="gender-option" data-gender="female"><img src="<?php echo $theme_assets; ?>/assets/images/webp/female.webp" alt="زن"></div>
        </div>
    </div>
    
    <!-- شبیه: chronic-conditions-step ولی برای Radio -->
    <div id="menstrual-status-step" class="step checkbox-list-container scrollable-container" style="max-height:75vh">
        <h2>لطفاً وضعیت چرخه قاعدگی خود را مشخص کنید:</h2>
        <p class="step-description">این اطلاعات به ما کمک می‌کند تا برنامه غذایی شخصی‌شده‌تری برای شما ایجاد کنیم</p>
        
        <div id="menstrual-status-selection" class="checkbox-selection-container">
            <!-- Option 1 -->
            <div class="checkbox-container">
                <input type="radio" id="menstrual-not-set" name="menstrual-status" value="not-set" class="real-checkbox">
                <label for="menstrual-not-set" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">تنظیم نشده / نمی‌دانم</span>
                </label>
            </div>
            
            <!-- Option 2 -->
            <div class="checkbox-container">
                <input type="radio" id="menstrual-regular" name="menstrual-status" value="regular" class="real-checkbox">
                <label for="menstrual-regular" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">منظم</span>
                </label>
            </div>
            
            <!-- Option 3 -->
            <div class="checkbox-container">
                <input type="radio" id="menstrual-irregular" name="menstrual-status" value="irregular" class="real-checkbox">
                <label for="menstrual-irregular" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">نامنظم</span>
                </label>
            </div>
            
            <!-- Option 4 -->
            <div class="checkbox-container">
                <input type="radio" id="menstrual-menopause" name="menstrual-status" value="menopause" class="real-checkbox">
                <label for="menstrual-menopause" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">یائسگی رسیده‌ام</span>
                </label>
            </div>
            
            <!-- Option 5 -->
            <div class="checkbox-container">
                <input type="radio" id="menstrual-pregnancy" name="menstrual-status" value="pregnancy" class="real-checkbox">
                <label for="menstrual-pregnancy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">باردار هستم</span>
                </label>
            </div>
            
            <!-- نیست Option - Skip -->
            <div class="checkbox-container stand-alone-skip">
                <input type="radio" id="menstrual-skip" name="menstrual-status" value="skip" class="real-checkbox">
                <label for="menstrual-skip" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">نمیخوام جواب بدم</span>
                </label>
            </div>
            
        </div>
        
        <div class="separator-dotted"></div>
        <div class="info-box">
            <div class="info-content">
                <div class="info-text">
                    <span class="first-line">چرا این سوال مهم است؟</span>
                    <span class="second-line">وضعیت دوره‌ای و هورمونی بر متابولیسم، تقاضای کالری، نیاز به تغذیه و انتخاب غذاها تاثیر می‌گذارد.</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Step 2: Personal Information -->
    <div id="personal-info-step" class="step">
        <h2>اطلاعات شخصی</h2>
        
        <!-- Full Name Input (Combined) -->
        <div class="input-container text-input-simple" style="margin-bottom: 15px;">
            <input 
                type="text" 
                id="full-name-input" 
                dir="rtl" 
                maxlength="70"
                lang="fa"
                autocomplete="name"
                placeholder="نام و نام خانوادگی">
        </div>
        
        <!-- Age Input -->
        <div class="input-container">
            <input type="text" inputmode="numeric" id="age-input">
            <span id="age-display">سن شما</span>
        </div>

        
        <div id="age-validation-container">
            <p id="age-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/age-min.png" width="30" height="30" alt="سن">
                    <div class="info-text">
                        <span class="first-line">محاسبه سن شما</span>
                        <span class="second-line">سن شما را می‌پرسیم تا برنامه شخصی شما را ایجاد کنیم.</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Animated Illustration Container -->
        <div class="name-input-illustration">
            <div class="floating-pen"></div>
            <div class="floating-document"></div>
        </div>
    </div>


    <!-- Step 2: Goal Selection -->
    <div id="goal-selection-step" class="step">
        <h2>هدف شما از این برنامه غذایی چیست؟</h2>
        <p class="step-description">لطفاً هدف اصلی خود را از دنبال کردن این رژیم انتخاب کنید</p>
        
        <div id="goal-selection">
            <div class="goal-option" data-goal="weight-loss">
                <div class="goal-icon" data-meals="2">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/lose-weight-min.png" alt="کاهش وزن">
                </div>
                <div class="goal-details">
                    <h3>کاهش وزن</h3>
                    <p>برنامه‌ای برای رسیدن به وزن ایده‌آل و سالم</p>
                </div>
            </div>
            
            <div class="goal-option" data-goal="weight-gain">
                <div class="goal-icon" data-meals="2">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/gain-weight-min.png" alt="افزایش وزن">
                </div>
                <div class="goal-details">
                    <h3>افزایش وزن سالم</h3>
                    <p>برنامه‌ای برای افزایش وزن اصولی و عضله‌سازی</p>
                </div>
            </div>
            
            <div class="goal-option" data-goal="fitness">
                <div class="goal-icon" data-meals="2">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/stay-fit-min.png" alt="حفظ سلامت">
                </div>
                <div class="goal-details">
                    <h3>حفظ سلامت و تناسب</h3>
                    <p>برنامه‌ای برای حفظ وزن فعلی و بهبود سلامت عمومی</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Height & Weight Input (Combined) -->
    <div id="height-weight-input-step" class="step checkbox-list-container scrollable-container" style="max-height:75vh">
        <h2>قد و وزن شما چقدر است؟</h2>
        
        <!-- Height Input -->
        <div class="input-container" style="margin-bottom: 15px;">
            <input type="text" inputmode="numeric" id="height-input">
            <span id="height-display">قد شما</span>
        </div>
        
        <!-- Weight Input -->
        <div class="input-container" style="margin-bottom: 15px;">
            <input type="text" inputmode="numeric" id="weight-input">
            <span id="weight-display">وزن شما</span>
        </div>
        
        <div id="height-weight-validation-container">
            <!-- ✅ فقط یک error-message -->
            <p id="height-weight-error" class="error-message"></p>
            
            <div class="separator-dotted"></div>
            
            <!-- BMI Result Container -->
            <div id="bmi-result-container">
                <div class="bmi-info">
                    <h3>شاخص توده بدنی (BMI) شما: <span id="bmi-value">0</span></h3>
                    <p id="bmi-category" class="bmi-category"></p>
                </div>
                <div class="bmi-scale-container">
                    <div class="bmi-scale-labels">
                        <span>کمبود وزن</span>
                        <span>نرمال</span>
                        <span>اضافه وزن</span>
                        <span>چاق</span>
                        <span>چاقی شدید</span>
                    </div>
                    <div class="bmi-scale">
                        <div id="bmi-indicator" class="bmi-indicator"></div>
                    </div>
                </div>
            </div>
            
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/height-min.png" width="30" height="30" alt="قد و وزن">
                    <div class="info-text">
                        <span class="first-line">محاسبه شاخص توده بدنی شما</span>
                        <span class="second-line">شاخص توده بدنی (BMI) به طور گسترده به عنوان یک معیار برای سنجش خطر ابتلا یا شیوع برخی مشکلات سلامتی مورد استفاده قرار می‌گیرد</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="target-weight-step" class="step">
        <h2>وزن هدف</h2>
    
        <div class="target-weight-toggle">
            <label class="toggle-label">
                <input type="checkbox" id="enable-target-weight" class="real-checkbox">
                <span class="toggle-slider"></span>
                <span class="toggle-text">می‌خواهم خودم وزن هدف را مشخص کنم</span>
            </label>
        </div>
    
        <div class="input-container target-weight-container disabled">
            <input
                type="text"
                inputmode="numeric"
                id="target-weight-input"
                disabled
            >
            <span id="target-weight-display">وزن هدف (اختیاری)</span>
        </div>
    
        <div id="target-weight-validation-container">
            <p id="targetWeight-error" class="error-message"></p>
        </div>
    
        <div class="separator-dotted"></div>
    
        <div class="info-box">
            <div class="info-content">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/gain-weight-min.png" width="30" height="30" alt="">
                <div class="info-text">
                    <span class="first-line">اگر مطمئن نیستید، این قسمت را خالی بگذارید</span>
                    <span class="second-line">Aidastyar با توجه به قد، وزن و هدف شما، وزن هدف منطقی را پیشنهاد می‌دهد</span>
                </div>
            </div>
        </div>
    </div>


    <!-- Step 9: Chronic Conditions (بیماری‌های مزمن اصلی) -->
    <div id="chronic-conditions-step" class="step checkbox-step-container">
        <h2>بیماری‌های مزمن و زمینه‌ای</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="chronic-none" class="real-checkbox">
            <label for="chronic-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچگونه بیماری مزمن یا زمینه‌ای ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="chronic-conditions-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- اختلالات متابولیک -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">اختلالات متابولیک</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-diabetes" class="real-checkbox">
                <label for="chronic-diabetes" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">دیابت</span>
                </label>
            </div>
            
        <!-- جایگزین chronic-diabetes-details -->
        <div id="chronic-diabetes-details" style="display: none; margin: 15px 0 20px 25px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-right: 3px solid #4CAF50;">
          <h4 style="margin: 0 0 15px 0; color: #333; font-size: 14px;">نوع دیابت</h4>
          <div class="diabetes-options" style="display: flex; flex-direction: column; gap: 10px;">
            <div class="diabetes-option" data-value="type1">
              <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">۱</div>
              <div class="diabetes-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">نوع ۱</div>
            </div>
            <div class="diabetes-option" data-value="type2">
              <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">۲</div>
              <div class="diabetes-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">نوع ۲</div>
            </div>
            <div class="diabetes-option" data-value="gestational">
              <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">G</div>
              <div class="diabetes-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">حاملگی</div>
            </div>
            <div class="diabetes-option" data-value="prediabetes">
              <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">P</div>
              <div class="diabetes-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">پیش‌دیابت</div>
            </div>
          </div>
          
          <!-- ⭐ قند خون ناشتا + HbA1c (بدون تغییر) -->
          <div id="chronic-diabetes-additional" style="margin-top: 15px; display: none;">
            <div style="margin-bottom: 10px;">
              <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">قند خون ناشتا</label>
              <input type="number" id="chronic-fasting-blood-sugar" placeholder="120" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
              <span style="margin-right: 5px; font-size: 13px;">mg/dL</span>
            </div>
            <div>
              <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">HbA1c</label>
              <input type="number" id="chronic-hba1c-level" step="0.1" placeholder="6.5" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
              <span style="margin-right: 5px; font-size: 13px;">%</span>
            </div>
          </div>
        </div>

    
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hypertension" class="real-checkbox">
                <label for="chronic-hypertension" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">فشار خون بالا</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-cholesterol" class="real-checkbox">
                <label for="chronic-cholesterol" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کلسترول یا تری گلیسیرید بالا</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-fatty-liver" class="real-checkbox">
                <label for="chronic-fatty-liver" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کبد چرب</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-insulin-resistance" class="real-checkbox">
                <label for="chronic-insulin-resistance" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مقاومت به انسولین</span>
                </label>
            </div>
    
            <!-- بیماری‌های کبدی -->
            <h3 class="diffrent-category-titles">بیماری‌های کبدی</h3>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-fatty-liver" class="real-checkbox">
                <label for="chronic-fatty-liver" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کبد چرب</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-cirrhosis" class="real-checkbox">
                <label for="chronic-cirrhosis" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سیروز کبدی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hepatitis" class="real-checkbox">
                <label for="chronic-hepatitis" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">هپاتیت مزمن</span>
                </label>
            </div>

            <!-- اختلالات هورمونی -->
            <h3 class="diffrent-category-titles">اختلالات هورمونی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hypothyroidism" class="real-checkbox">
                <label for="chronic-hypothyroidism" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کم کاری تیروئید (هیپوتیروئیدی)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hyperthyroidism" class="real-checkbox">
                <label for="chronic-hyperthyroidism" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پرکاری تیروئید (هیپرتیروئیدی)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hashimoto" class="real-checkbox">
                <label for="chronic-hashimoto" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">هاشیموتو (التهاب خودایمنی تیروئید)
                        <span class="tooltip">توضیح بیماری
                            <span class="tooltiptext">
                                یک بیماری خودایمنی که در آن سیستم ایمنی بدن به غده تیروئید حمله می‌کند. 
                                این بیماری معمولاً منجر به کم‌کاری تیروئید می‌شود.
                            </span>
                        </span>                    
                    </span>
                </label>
            </div>
            
            <div class="checkbox-container female-only">
                <input type="checkbox" id="chronic-pcos" class="real-checkbox">
                <label for="chronic-pcos" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سندرم تخمدان پلی کیستیک (PCOS)</span>
                </label>
            </div>
            
            <div class="checkbox-container female-only">
                <input type="checkbox" id="chronic-menopause" class="real-checkbox">
                <label for="chronic-menopause" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">یائسگی یا پیش یائسگی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-cortisol" class="real-checkbox">
                <label for="chronic-cortisol" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مشکلات کورتیزول (استرس مزمن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-growth" class="real-checkbox">
                <label for="chronic-growth" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">اختلال هورمون رشد</span>
                </label>
            </div>
    
            <!-- سایر بیماری‌های مزمن -->
            <h3 class="diffrent-category-titles">سایر بیماری‌های مزمن</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-kidney" class="real-checkbox">
                <label for="chronic-kidney" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری کلیوی مزمن</span>
                </label>
                <!-- جایگزین کامل chronic-kidney-details -->
                <div id="chronic-kidney-details" style="display: none; margin: 15px 0 20px 25px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-right: 3px solid #ff9800;">
                    <h4 style="margin: 0 0 15px 0; color: #333; font-size: 14px;">مرحله بیماری کلیوی</h4>
                    <div class="kidney-options" style="display: flex; flex-direction: column; gap: 10px;">
                        <div class="kidney-option" data-value="early">
                            <div class="kidney-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">۱</div>
                            <div class="kidney-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">مراحل اولیه</div>
                        </div>
                        <div class="kidney-option" data-value="advanced-no-dialysis">
                            <div class="kidney-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">۳-۴</div>
                            <div class="kidney-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">مراحل پیشرفته (بدون دیالیز)</div>
                        </div>
                        <div class="kidney-option" data-value="dialysis">
                            <div class="kidney-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">۵</div>
                            <div class="kidney-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">دیالیز</div>
                        </div>
                        <div class="kidney-option" data-value="transplant-less1year">
                            <div class="kidney-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">T</div>
                            <div class="kidney-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">پیوند کمتر از ۱ سال</div>
                        </div>
                        <div class="kidney-option" data-value="transplant-more1year">
                            <div class="kidney-icon" style="display: inline-block; width: 24px; text-align: center; font-weight: bold; color: #4CAF50;">T+</div>
                            <div class="kidney-text" style="display: inline-block; margin-right: 8px; font-size: 14px;">پیوند بیشتر از ۱ سال</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-heart" class="real-checkbox">
                <label for="chronic-heart" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری قلبی عروقی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-autoimmune" class="real-checkbox">
                <label for="chronic-autoimmune" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری خودایمنی</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Step 10 Medications Selection -->
    <div id="medications-step" class="step checkbox-step-container">
        <h2>داروهای منظم</h2>
        <p class="step-description">داروهای منظمی که به طور مستمر مصرف می‌کنید را انتخاب کنید</p>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="medications-none" class="real-checkbox">
            <label for="medications-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچ‌کدام (داروی منظم مصرف نمی‌کنم)</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="medications-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            
            <!-- Original Medications -->
            <div class="checkbox-container">
                <input type="checkbox" id="medication-diabetes-oral" class="real-checkbox">
                <label for="medication-diabetes-oral" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای خوراکی دیابت</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-insulin" class="real-checkbox">
                <label for="medication-insulin" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">انسولین</span>
                </label>
            </div>

    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-thyroid" class="real-checkbox">
                <label for="medication-thyroid" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای تیروئید</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-corticosteroids" class="real-checkbox">
                <label for="medication-corticosteroids" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کورتیکواستروئیدها (کورتون)</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-anticoagulants" class="real-checkbox">
                <label for="medication-anticoagulants" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای ضدانعقاد</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-hypertension" class="real-checkbox">
                <label for="medication-hypertension" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای فشارخون</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-psychiatric" class="real-checkbox">
                <label for="medication-psychiatric" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای روان‌پزشکی</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-hormonal" class="real-checkbox">
                <label for="medication-hormonal" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای هورمونی</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-cardiac" class="real-checkbox">
                <label for="medication-cardiac" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای قلبی</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-gastrointestinal" class="real-checkbox">
                <label for="medication-gastrointestinal" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای گوارشی</span>
                </label>
            </div>
            
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">داروهای خاص</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="medication-immunosuppressants" class="real-checkbox">
                <label for="medication-immunosuppressants" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای سرکوب ایمنی (پیوند، روماتولوژی)</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-cancer-oral" class="real-checkbox">
                <label for="medication-cancer-oral" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای ضدسرطان خوراکی / تارگت‌تراپی</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-anticonvulsant" class="real-checkbox">
                <label for="medication-anticonvulsant" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای ضدصرع / عصبی</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-weight-loss" class="real-checkbox">
                <label for="medication-weight-loss" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای لاغری / GLP-1</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="medication-supplements" class="real-checkbox">
                <label for="medication-supplements" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مکمل‌های ویتامین و معادن</span>
                </label>
            </div>
    
        </div>
    </div>

    <!-- Step 10: Digestive Conditions (مرحله جدید - ادغام مشکلات گوارشی و عدم تحمل‌ها) -->
    <div id="digestive-conditions-step" class="step checkbox-step-container">
        <h2>مشکلات گوارشی و عدم تحمل‌های غذایی</h2>
        <p class="step-description">لطفاً مشکلات گوارشی و عدم تحمل‌های غذایی خود را انتخاب کنید</p>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="digestive-none" class="real-checkbox">
            <label for="digestive-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچگونه مشکل گوارشی یا عدم تحمل غذایی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="digestive-conditions-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- بیماری‌های گوارشی ساختاری -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">بیماری‌های گوارشی ساختاری</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-ibs" class="real-checkbox">
                <label for="digestive-ibs" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سندرم روده تحریک‌پذیر (IBS)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-ibd" class="real-checkbox">
                <label for="digestive-ibd" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری التهابی روده (کرون یا کولیت اولسراتیو)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-gerd" class="real-checkbox">
                <label for="digestive-gerd" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">ریفلاکس معده-مروی (GERD)</span>
                </label>
            </div>
    
            <!-- علائم و مشکلات عملکردی -->
            <h3 class="diffrent-category-titles">علائم و مشکلات عملکردی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-bloating" class="real-checkbox">
                <label for="digestive-bloating" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">نفخ یا گاز معده</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-pain" class="real-checkbox">
                <label for="digestive-pain" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">درد یا گرفتگی معده</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-heartburn" class="real-checkbox">
                <label for="digestive-heartburn" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سوزش سر دل یا ترش کردن</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-constipation" class="real-checkbox">
                <label for="digestive-constipation" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">یبوست مزمن</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-diarrhea" class="real-checkbox">
                <label for="digestive-diarrhea" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">اسهال مزمن</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-fullness" class="real-checkbox">
                <label for="digestive-fullness" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سیری زودرس</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-nausea" class="real-checkbox">
                <label for="digestive-nausea" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حالت تهوع</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-slow-digestion" class="real-checkbox">
                <label for="digestive-slow-digestion" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">هضم کند غذا</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-indigestion" class="real-checkbox">
                <label for="digestive-indigestion" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سوء هاضمه مزمن</span>
                </label>
            </div>
    
            <!-- عفونت‌ها و مشکلات خاص -->
            <h3 class="diffrent-category-titles">عفونت‌ها و مشکلات خاص</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-helicobacter" class="real-checkbox">
                <label for="digestive-helicobacter" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عفونت هلیکوباکتر پیلوری (H. Pylori)</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Step 10: Surgery History -->
    <div id="surgery-step" class="step checkbox-step-container">
        <h2>سابقه جراحی و اقدامات پزشکی</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="surgery-none" class="real-checkbox">
            <label for="surgery-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچگونه سابقه جراحی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="surgery-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- 🔪 جراحی‌های گوارشی و متابولیک -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">جراحی‌های گوارشی و متابولیک</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-metabolic" class="real-checkbox">
                <label for="surgery-metabolic" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی متابولیک (بایپس معده، اسلیو)</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-gallbladder" class="real-checkbox">
                <label for="surgery-gallbladder" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی کیسه صفرا</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-intestine" class="real-checkbox">
                <label for="surgery-intestine" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی روده</span>
                </label>
            </div>
            
            <!-- ❤️ جراحی‌های عمده و ارگان‌ها -->
            <h3 class="diffrent-category-titles">جراحی‌های عمده و ارگان‌ها</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-thyroid" class="real-checkbox">
                <label for="surgery-thyroid" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی تیروئید/پاراتیروئید</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-pancreas" class="real-checkbox">
                <label for="surgery-pancreas" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی لوزالمعده (پانکراس)</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-heart" class="real-checkbox">
                <label for="surgery-heart" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی قلب</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-kidney" class="real-checkbox">
                <label for="surgery-kidney" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پیوند کلیه</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-liver" class="real-checkbox">
                <label for="surgery-liver" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پیوند کبد</span>
                </label>
            </div>
            
            <!-- 🎗️ سرطان و جراحی‌های مرتبط -->
            <h3 class="diffrent-category-titles">سرطان و جراحی‌های مرتبط</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="cancer-history" class="real-checkbox">
                <label for="cancer-history" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سابقه سرطان (همراه جزئیات نوع و درمان)</span>
                </label>
            </div>
            
            <!-- جزئیات سرطان -->
            <div id="cancer-details" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-right: 3px solid #e91e63;">
                <!-- وضعیت درمان -->
                <h4 style="margin: 0 0 15px 0; color: #333; font-size: 14px;">وضعیت درمان:</h4>
                <div class="cancer-options" style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="cancer-option" data-value="chemo">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">شیمی درمانی</div>
                    </div>
                    <div class="cancer-option" data-value="radio">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پرتو درمانی</div>
                    </div>
                    <div class="cancer-option" data-value="surgery">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">اخیراً جراحی شده‌ام</div>
                    </div>
                    <div class="cancer-option" data-value="finished">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">درمانم تمام شده</div>
                    </div>
                </div>
            
                <!-- نوع سرطان -->
                <h4 style="margin: 20px 0 10px 0; color: #333; font-size: 14px;">نوع سرطان:</h4>
                <div class="cancer-options" style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="cancer-option" data-value="breast">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پستان</div>
                    </div>
                    <div class="cancer-option" data-value="colon">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">روده</div>
                    </div>
                    <div class="cancer-option" data-value="prostate">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پروستات</div>
                    </div>
                    <div class="cancer-option" data-value="lung">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">ریه</div>
                    </div>
                    <div class="cancer-option" data-value="blood">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">خون</div>
                    </div>
                    <div class="cancer-option" data-value="other">
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">سایر</div>
                    </div>
                </div>
            </div>
    
            <!-- جراحی‌های زنان -->
            <h3 class="diffrent-category-titles female-only">جراحی‌های زنان</h3>
            <div class="checkbox-container female-only">
                <input type="checkbox" id="surgery-gynecology" class="real-checkbox">
                <label for="surgery-gynecology" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی‌های زنان</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Step 11: Lab Test Upload -->
    <div id="lab-test-upload-step" class="step checkbox-list-container scrollable-container" style="max-height:70vh">
        <h2>آپلود فایل آزمایش (اختیاری)</h2>
        <p class="step-description">در صورتی که آزمایش خون اخیر دارید، می‌توانید فایل PDF آن را آپلود کنید تا برنامه غذایی دقیق‌تری برای شما تهیه شود.</p>
    
        <div class="file-upload-container">
            <div class="file-upload-area" onclick="document.getElementById('lab-test-file').click()">
                <div class="upload-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#00857a" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                </div>
                <h3>فایل PDF آزمایش خود را اینجا بکشید</h3>
                <p>یا برای انتخاب فایل کلیک کنید</p>
                <input type="file" id="lab-test-file" accept="application/pdf" style="display: none;">
            </div>
    
            <div id="file-preview" style="display: none;">
                <div class="file-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#d32f2f">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    <div>
                        <div style="font-size: 15px; color: #d32f2f; font-weight: bolder; margin-bottom: 4px;"> فایل آپلود شده:</div>
                        <span id="file-name">test.pdf</span>
                    </div>
                </div>
                <button type="button" id="remove-file" class="remove-file-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    حذف
                </button>
            </div>


            <div class="checkbox-container first-option stand-alone-none">
                <input type="checkbox" id="skip-lab-test" class="real-checkbox">
                <label for="skip-lab-test" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">فایل آزمایش ندارم</span>
                </label>
            </div>
    
            <div class="info-box">
                <div class="info-content">
                    <div class="info-text">
                        <span class="first-line">آزمایشات قابل استخراج خودکار</span>
                        <span class="second-line" style="line-height: 1.8;">
                            <div style="margin-bottom: 8px;">
                                <strong>قند و متابولیسم:</strong> FBS, HbA1c, Insulin
                            </div>
                            <div style="margin-bottom: 8px;">
                                <strong>چربی خون:</strong> Cholesterol, TG, LDL, HDL, VLDL
                            </div>
                            <div style="margin-bottom: 8px;">
                                <strong>کبد:</strong> SGOT (AST), SGPT (ALT), ALP
                            </div>
                            <div style="margin-bottom: 8px;">
                                <strong>کلیه:</strong> BUN, Creatinine, Uric Acid
                            </div>
                            <div style="margin-bottom: 8px;">
                                <strong>ویتامین‌ها:</strong> Vit D, B12, Ferritin, Mg, Zn, Cu
                            </div>
                            <div style="margin-bottom: 8px;">
                                <strong>تیروئید:</strong> TSH, T3, T4
                            </div>
                            <div style="margin-bottom: 8px;">
                                <strong>التهاب:</strong> CRP, ESR
                            </div>
                            <div>
                                <strong>CBC:</strong> WBC, RBC, Hb, HCT, MCV, MCH, MCHC, PLT, RDW
                            </div>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #ddd; color: #ff9800;">
                                <strong>نکته:</strong> برای بهترین نتیجه، PDF باید شامل جدول کامل نتایج باشد.
                            </div>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Step 11: Water Intake -->
    <div id="water-intake-step" class="step">
        <h2>به طور میانگین چه مقدار آب در روز می‌نوشید؟</h2>
        <div id="water-intake-container">
            <div class="water-cups-container">
                <!-- ردیف 1 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="1"></div>
                    <div class="water-cup" data-amount="2"></div>
                    <div class="water-cup" data-amount="3"></div>
                    <div class="water-cup" data-amount="4"></div>
                    <div class="water-cup" data-amount="5"></div>
                    <div class="water-cup" data-amount="6"></div>
                </div>
                <!-- ردیف 2 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="7"></div>
                    <div class="water-cup" data-amount="8"></div>
                    <div class="water-cup" data-amount="9"></div>
                    <div class="water-cup" data-amount="10"></div>
                    <div class="water-cup" data-amount="11"></div>
                    <div class="water-cup" data-amount="12"></div>
                </div>
                <!-- ردیف 3 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="13"></div>
                    <div class="water-cup" data-amount="14"></div>
                    <div class="water-cup" data-amount="15"></div>
                    <div class="water-cup" data-amount="16"></div>
                    <div class="water-cup" data-amount="17"></div>
                    <div class="water-cup" data-amount="18"></div>
                </div>
                <!-- ردیف 4 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="19"></div>
                    <div class="water-cup" data-amount="20"></div>
                    <div class="water-cup" data-amount="21"></div>
                    <div class="water-cup" data-amount="22"></div>
                    <div class="water-cup" data-amount="23"></div>
                    <div class="water-cup" data-amount="24"></div>
                </div>
                <!-- ردیف 5 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="25"></div>
                    <div class="water-cup" data-amount="26"></div>
                    <div class="water-cup" data-amount="27"></div>
                    <div class="water-cup" data-amount="28"></div>
                    <div class="water-cup" data-amount="29"></div>
                    <div class="water-cup" data-amount="30"></div>
                </div>
            </div>

            <div class="water-amount-display">
                <div id="water-amount-text" class="water-amount-text">
                    <span id="water-amount">0</span> لیوان در روز 
                    <span class="water-liter">(≈<span id="water-liter">0</span> لیتر)</span>
                </div>
                <div id="water-dont-know-text" class="dont-know-text" style="display:none;">
                    مقدار آب مصرفی مشخص نیست
                </div>
            </div>
            
            <div class="dont-know-container">
                <div class="checkbox-container first-option stand-alone-none">
                    <input type="checkbox" id="water-dont-know" class="real-checkbox">
                    <label for="water-dont-know" class="checkbox-label">
                        <span class="check-icon"></span>
                        <span class="label-text">نمی‌دانم / مطمئن نیستم</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <div id="activity-selection-step" class="step">
        <h2>میزان فعالیت روزانه شما چقدر است؟</h2>
        <p class="step-description">لطفاً سطح فعالیت روزمره خود را بر اساس شغل و فعالیت‌های معمول روزانه انتخاب کنید</p>
        
        <div id="activity-selection">
            <div class="activity-option" data-activity="very-low">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/without-activity-min.png" alt="فعالیت خیلی کم">
                </div>
                <div class="activity-details">
                    <h3>خیلی کم (بی‌تحرک)</h3>
                    <p>بیشتر وقت پشت میز یا در خانه، تحرک بسیار کم</p>
                    <span class="activity-examples">(پشت میز نشینی، کارمند اداری، خانه‌دار با تحرک کم)</span>
                </div>
            </div>
            
            <div class="activity-option" data-activity="low">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/alittle-activity-min.png" alt="فعالیت کم">
                </div>
                <div class="activity-details">
                    <h3>کم (فعالیت سبک)</h3>
                    <p>کارهای سبک خانه، پیاده‌روی کوتاه، ایستادن متوسط</p>
                    <span class="activity-examples">(معلم، منشی، فروشنده با تحرک محدود)</span>
                </div>
            </div>
            
            <div class="activity-option" data-activity="medium">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/middle-activity-min.png" alt="فعالیت متوسط">
                </div>
                <div class="activity-details">
                    <h3>متوسط (فعالیت متوسط)</h3>
                    <p>کارهایی که نیاز به راه رفتن و حرکت مداوم دارد</p>
                    <span class="activity-examples">(فروشندگی، پرستاری، راننده تاکسی، خدمات رسانی)</span>
                </div>
            </div>
            
            <div class="activity-option" data-activity="high">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/alot-activity-min.png" alt="فعالیت زیاد">
                </div>
                <div class="activity-details">
                    <h3>زیاد (فعالیت شدید)</h3>
                    <p>کار فیزیکی سخت که بیشتر روز نیاز به فعالیت بدنی دارد</p>
                    <span class="activity-examples">(کارگر ساختمانی، کشاورز، مکانیک، باربری)</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Step 13: Weekly Exercise Activity - نسخه با آیکون فونت -->
    <div id="exercise-activity-step" class="step">
        <h2>فعالیت ورزشی هفتگی شما چقدر است؟</h2>
        <p class="step-description">لطفاً میزان و شدت فعالیت ورزشی منظم خود در هفته را انتخاب کنید</p>
        
        <div id="exercise-selection">
            <div class="exercise-option" data-exercise="none">
                <div class="exercise-details">
                    <h3>هیچ ورزشی نمی‌کنم</h3>
                    <p>بدون فعالیت ورزشی منظم در هفته</p>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="light">
                <div class="exercise-details">
                    <h3>سبک</h3>
                    <p>۱-۲ روز در هفته، کمتر از ۳۰ دقیقه</p>
                    <span class="exercise-examples">(پیاده‌روی آرام، یوگا سبک، حرکات کششی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="medium">
                <div class="exercise-details">
                    <h3>متوسط</h3>
                    <p>۳-۴ روز در هفته، ۳۰-۶۰ دقیقه</p>
                    <span class="exercise-examples">(دویدن سبک، شنا، بدنسازی متوسط، ورزش‌های هوازی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="high">
                <div class="exercise-details">
                    <h3>زیاد</h3>
                    <p>۵-۶ روز در هفته یا بیشتر، ۶۰+ دقیقه</p>
                    <span class="exercise-examples">(تمرین سنگین، کراس فیت، ورزش‌های رقابتی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="professional">
                <div class="exercise-details">
                    <h3>ورزشکار حرفه‌ای</h3>
                    <p>تمرین سنگین روزانه و برنامه‌ریزی شده</p>
                    <span class="exercise-examples">(ورزشکاران حرفه‌ای، بدنسازان، ورزش‌های قهرمانی)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 15: Diet Style Selection -->
    <div id="diet-style-step" class="step checkbox-step-container">
        <h2>سبک غذایی اصلی شما چیست؟</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="diet-style-none" class="real-checkbox">
            <label for="diet-style-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">سبک غذایی خاصی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="diet-style-selection" class="checkbox-selection-container checkbox-list-container">
            <div class="checkbox-container">
                <input type="checkbox" id="diet-style-vegetarian" class="real-checkbox">
                <label for="diet-style-vegetarian" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">گیاهخواری (Vegetarian)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="diet-style-vegan" class="real-checkbox">
                <label for="diet-style-vegan" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">وگان (Vegan - بدون هیچ محصول حیوانی)</span>
                </label>
            </div>
        </div>
    </div>

    <div id="food-limitations-step" class="step checkbox-step-container">
        <h2>چه محدودیت‌های غذایی دارید؟</h2>
        <p class="step-description">شامل محدودیت‌های پزشکی (حساسیت، عدم تحمل) و ترجیحات شخصی</p>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="limitations-none" class="real-checkbox">
            <label for="limitations-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچ محدودیت غذایی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="food-limitations-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- محدودیت‌های پزشکی -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">محدودیت‌های پزشکی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-celiac" class="real-checkbox">
                <label for="limitation-celiac" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری سلیاک (حساسیت به گلوتن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-lactose" class="real-checkbox">
                <label for="limitation-lactose" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم تحمل لاکتوز</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-seafood-allergy" class="real-checkbox">
                <label for="limitation-seafood-allergy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حساسیت به غذاهای دریایی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-eggs-allergy" class="real-checkbox">
                <label for="limitation-eggs-allergy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حساسیت به تخم‌مرغ</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-nuts-allergy" class="real-checkbox">
                <label for="limitation-nuts-allergy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حساسیت به آجیل و مغزها</span>
                </label>
            </div>
    
            <!-- ترجیحات شخصی -->
            <h3 class="diffrent-category-titles">ترجیحات شخصی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-no-seafood" class="real-checkbox">
                <label for="limitation-no-seafood" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم مصرف غذاهای دریایی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-no-redmeat" class="real-checkbox">
                <label for="limitation-no-redmeat" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم مصرف گوشت قرمز</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-no-dairy" class="real-checkbox">
                <label for="limitation-no-dairy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم مصرف لبنیات</span>
                </label>
            </div>
        </div>
    </div>
                
    <!-- Step 19: Diet Type Selection - نسخه اصلاح شده -->
    <div id="diet-type-selection-step" class="step">
        <h2>نوع رژیم مورد نظر خود را انتخاب کنید</h2>
        
        <div class="step-content-container">
            <div id="diet-type-selection" class="diet-type-grid scrollable-container">
                <!-- کارت رژیم هوش مصنوعی -->
                <div class="diet-type-card" data-diet-type="ai-only">
                    <div class="card-header">
                        <div class="card-badge">پیشنهادی</div>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">رژیم هوش مصنوعی</h3>
                    </div>
                    
                    <div class="card-footer">
                        <div class="price-section">
                            <div class="price-amount" id="ai-only-price">در حال دریافت قیمت...</div>
                            <div class="price-currency">تومان</div>
                        </div>
                    </div>
                </div>
                
                <!-- کارت رژیم با تأیید متخصص -->
                <div class="diet-type-card premium" data-diet-type="with-specialist">
                    <div class="card-header">
                        <div class="card-badge premium-badge">ویژه</div>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">رژیم با تأیید متخصص</h3>
                    </div>
                    
                    <div class="card-footer">
                        <!-- متن پیش‌فرض قبل از انتخاب متخصص -->
                        <p class="specialist-price-note" id="specialist-select-note">
                            قیمت نهایی پس از انتخاب مشاور مشخص می‌شود
                        </p>
                        
                        <!-- جزئیات قیمت بعد از انتخاب متخصص -->
                        <div class="price-breakdown" id="price-breakdown" style="display: none;">
                            <!-- قیمت سرویس AI -->
                            <div class="price-row">
                                <div class="price-row-label">
                                    <span>سرویس هوش مصنوعی</span>
                                </div>
                                <div class="price-row-value">
                                    <span id="ai-service-price" class="price-value">0</span>
                                    <span class="price-currency-small">تومان</span>
                                    <span id="ai-service-discount" class="discount-badge" style="display: none;"></span>
                                </div>
                            </div>
                            
                            <!-- قیمت مشاور -->
                            <div class="price-row">
                                <div class="price-row-label">
                                    <span>مشاوره متخصص</span>
                                </div>
                                <div class="price-row-value">
                                    <span id="consultant-price" class="price-value">0</span>
                                    <span class="price-currency-small">تومان</span>
                                    <span id="consultant-discount" class="discount-badge" style="display: none;"></span>
                                </div>
                            </div>
                            
                            <!-- خط جداکننده -->
                            <div class="price-divider"></div>
                            
                            <!-- قیمت کل -->
                            <div class="price-row total-price-row">
                                <div class="price-row-label">
                                    <span class="total-label">جمع کل</span>
                                </div>
                                <div class="price-row-value">
                                    <span id="total-price" class="price-value total">0</span>
                                    <span class="price-currency-small">تومان</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <!-- پاپ‌آپ انتخاب مشاور -->
    <div id="specialist-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <div class="popup-header">
                <h3>انتخاب متخصص تغذیه</h3>
                <button type="button" class="popup-close" onclick="closeSpecialistPopup()">×</button>
            </div>
            <div class="popup-body">
                <p class="popup-description">لطفاً متخصص تغذیه مورد نظر خود را انتخاب کنید</p>
                
                <div id="specialist-selection-popup" class="specialist-selection-popup">
                    <!-- لیست مشاورین از طریق AJAX پر می‌شود -->
                    <div class="loading-specialists">
                        <div class="loading-spinner"></div>
                        <p>در حال بارگذاری لیست متخصصین...</p>
                    </div>
                </div>
                
                <div id="selected-specialist-info" class="selected-specialist-info" style="display: none;">
                    <h4>متخصص انتخاب شده:</h4>
                    <div id="specialist-details"></div>
                </div>
            </div>
            <div class="popup-footer">
                <button type="button" class="popup-confirm-btn" onclick="confirmSpecialistSelection()" disabled>تأیید و ادامه</button>
                <button type="button" class="popup-cancel-btn" onclick="closeSpecialistPopup()">انصراف</button>
            </div>
        </div>
    </div>

    <!-- Step 18: Terms Agreement -->
    <div id="terms-agreement-step" class="step">
        <h2>شرایط و قوانین استفاده از خدمات Aidastyar</h2>
        <div id="terms-agreement-container">
            <div class="terms-agreement-content">
                <?php 
                // ✅ استفاده از تابع مرکزی به جای HTML استاتیک
                echo aidastyar_get_terms_content(); 
                ?>
            </div>
            
            <!-- Checkbox -->
            <div id="terms-agreement-checkbox" class="checkbox-container">
                <input type="checkbox" id="agree-terms" class="real-checkbox">
                <label for="agree-terms" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">تمامی شرایط و قوانین فوق را مطالعه کرده و می‌پذیرم. Aidastyar را از هرگونه مسئولیت قانونی مبرا می‌دانم.</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Step 19: Confirmation -->
    <div id="confirm-submit-step" class="step">
        <h2>خلاصه اطلاعات شما</h2>
        <div id="summary-container"></div>
        <div id="confirmation-checkbox" class="checkbox-container">
            <input type="checkbox" id="confirm-info" class="real-checkbox">
            <label for="confirm-info" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">اطلاعات وارد شده را تأیید می‌کنم</span>
            </label>
        </div>
        <div id="submit-button-container">
            <?php if (is_user_logged_in()): ?>
                <button type="submit" id="SubmitBtn" class="submit-form">ثبت نهایی</button>
            <?php else: ?>
                <button type="button" id="SubmitBtn" class="submit-form" onclick="saveFormAndRedirect()">ورود و ثبت نهایی</button>
            <?php endif; ?>                
        </div>
    
        <div id="ai-diet-result" style="display:none;">
            <div class="ai-response-content"></div>
            <button id="downloadPdf" style="display:none">دانلود PDF</button>
        </div>
    </div>
    
    <div id="next-button-container">
        <button type="button" class="next-step">گام بعد</button>
    </div>
        
    <script>
    // اضافه کردن مدیریت تم به آبجکت state
    window.state = {
        ...window.state,
        toggleTheme: function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('diet-theme', isDark ? 'dark' : 'light');
        }
    };

    // بارگذاری تم ذخیره شده
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('diet-theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
        
        // ایجاد دکمه تغییر تم
        const themeToggle = document.createElement('button');
        themeToggle.className = 'theme-toggle';
        themeToggle.title = 'تغییر تم تاریک/روشن';
        
        themeToggle.addEventListener('click', state.toggleTheme);
        document.body.appendChild(themeToggle);
    });
    </script>  
    <script>
    const aidastyarTerms = {
        nonce: '<?php echo wp_create_nonce("aidastyar_terms_nonce"); ?>',
        ajaxurl: '<?php echo admin_url("admin-ajax.php"); ?>'
    };
    </script>

    <script>
    const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    const pdfUploadNonce = '<?php echo wp_create_nonce('upload_temp_pdf_nonce'); ?>';
    </script>
    

    <script src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/libs/pdf.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = '<?php echo get_stylesheet_directory_uri(); ?>/assets/js/libs/pdf.worker.min.js';</script>
    <script src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/libs/tesseract.min.js"></script>
    <script src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/services/diet/pdf-processor.js"></script>
</form>


<!-- Lab Data Confirmation Popup - Simple Key:Value -->
<div id="lab-data-popup" class="lab-popup-overlay" style="display: none;">
    <div class="lab-popup-box">
        <div class="lab-popup-header">
            <h3>📋 اطلاعات استخراج شده</h3>
            <button type="button" class="lab-popup-close-btn" onclick="closeLabPopup()">×</button>
        </div>
        
        <div class="lab-popup-body">
            <p class="lab-popup-desc">
                <strong>🔍 دقت کنید:</strong> رژیم شما دقیقاً بر اساس این اطلاعات طراحی می‌شود.<br>
                لطفاً صحت داده‌های زیر را بررسی کنید:
            </p>
            <div id="lab-data-list" class="lab-data-items"></div>
        </div>
        
        <div class="lab-popup-footer">
            <button type="button" class="lab-btn lab-btn-reject" onclick="rejectLabData()">رد</button>
            <button type="button" class="lab-btn lab-btn-confirm" onclick="confirmLabData()">تایید</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?php echo $theme_assets; ?>/assets/css/components/payment-popup.css">
<script src="<?php echo $theme_assets; ?>/assets/js/components/payment-popup.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/chart.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/script.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/diet.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-events.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-validation.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-steps.js"></script>

<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-inputs.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/terms-acceptance.js"></script>

<script>
function saveFormAndRedirect() {
  // ذخیره داده‌های فرم
  sessionStorage.setItem('diet_form_data', JSON.stringify({
    ...window.state.formData,
    _timestamp: Date.now(),
    _currentStep: window.state.currentStep
  }));
  
  // ذخیره URL فعلی
  const currentUrl = window.location.href.split('#')[0];
  sessionStorage.setItem('diet_form_redirect_url', currentUrl);
  
  // هدایت به صفحه لاگین
  const loginUrl = '<?php echo wp_login_url(); ?>?redirect_to=' + encodeURIComponent(currentUrl);
  
  // ایجاد loader و هدایت
  const loader = new AiDastyarLoader({
    message: 'در حال انتقال به صفحه ورود',
    theme: 'light',
    size: 'medium',
    position: 'center',
    closable: false,
    overlay: true,
    persistent: true, // تغییر به true
    redirectUrl: loginUrl,
    redirectDelay: 1000
  });
  loader.show();
}

window.addEventListener('load', function() {
    // پنهان کردن لودرهای دیگر
    if (window.AiDastyarLoader && window.AiDastyarLoader.hide) {
        window.AiDastyarLoader.hide();
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const loggedIn = urlParams.get('logged_in');
    
    if (loggedIn === '1' && sessionStorage.getItem('diet_form_data')) {
        // 1. ابتدا فرم را مخفی می‌کنیم
        const formElement = document.getElementById('multi-step-form');
        if (formElement) {
            formElement.style.opacity = '0';
            formElement.style.visibility = 'hidden';
        }
        
        // 2. نمایش لودر با پیام مناسب
        const loader = new AiDastyarLoader({
            message: 'در حال بارگذاری اطلاعات فرم شما...',
            theme: 'light',
            size: 'medium',
            position: 'center',
            closable: false,
            overlay: true,
            persistent: true,
            onShow: function() {
                console.log('Loader shown for form restoration');
            },
            onHide: function() {
                // پس از بستن لودر، فرم را نشان می‌دهیم
                if (formElement) {
                    formElement.style.opacity = '1';
                    formElement.style.visibility = 'visible';
                    formElement.style.transition = 'opacity 0.5s ease';
                }
            }
        });
        loader.show();
        
        // 3. تاخیر برای نمایش لودر
        setTimeout(() => {
            try {
                // بازیابی داده‌ها
                const savedData = JSON.parse(sessionStorage.getItem('diet_form_data'));
                
                if (savedData) {
                    const {_timestamp, _currentStep, ...formData} = savedData;
                    
                    // اعمال داده‌ها به state
                    if (window.state && window.state.formData) {
                        Object.assign(window.state.formData, formData);
                    }
                    
                    // برو به مرحله ذخیره شده
                    if (window.navigateToStep && window.STEPS) {
                        window.navigateToStep(window.STEPS.TERMS_AGREEMENT);
                    }
                    
                    // پر کردن عناصر فرم
                    if (typeof window.state.updateFormElementsFromState === 'function') {
                        // به‌روزرسانی پیام لودر
                        loader.update('در حال پر کردن فرم با اطلاعات شما...');
                        
                        // تاخیر کوتاه برای اطمینان از رندر شدن
                        setTimeout(() => {
                            window.state.updateFormElementsFromState();
                            
                            // پیام نهایی و بستن لودر
                            loader.update('اطلاعات شما با موفقیت بارگذاری شد!');
                            
                            setTimeout(() => {
                                loader.hide();
                            }, 1000);
                        }, 300);
                    } else {
                        // اگر تابع updateFormElementsFromState وجود ندارد
                        loader.update('اطلاعات بازیابی شد!');
                        setTimeout(() => loader.hide(), 1000);
                    }
                }
                
                // پاک کردن storage
                sessionStorage.removeItem('diet_form_data');
                sessionStorage.removeItem('diet_form_redirect_url');
                
                // حذف پارامتر از URL
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('logged_in');
                    window.history.replaceState({}, document.title, url.toString());
                }
                
            } catch (error) {
                console.error('Error restoring form:', error);
                loader.update('⚠️ خطا در بازیابی اطلاعات');
                setTimeout(() => {
                    loader.hide();
                    // نشان دادن فرم حتی اگر خطا داشت
                    if (formElement) {
                        formElement.style.opacity = '1';
                        formElement.style.visibility = 'visible';
                    }
                }, 2000);
            }
        }, 500); // تاخیر 500ms برای نمایش لودر
    }
});
</script>