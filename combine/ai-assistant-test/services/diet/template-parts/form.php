<?php
// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/services/diet/template-parts/form.php
$theme_assets = get_stylesheet_directory_uri();
?>
<form id="multi-step-form" class="ai-multistep-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
    <input type="hidden" name="action" value="submit_diet_form">
    <?php wp_nonce_field('diet_form_nonce', 'diet_form_security'); ?>
    
    <div id="header-container">
        <div id="step-counter"><span id="current-step">1</span>/<span id="total-steps">19</span></div>
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
    
        <div id="terms-checkbox" class="checkbox-container terms-combined">
            <input type="checkbox" id="confirm-terms" class="real-checkbox">
            <label for="confirm-terms" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">شرایط و قوانین را می‌پذیرم</span>
            </label>
            <div class="terms-box">
                <ul class="terms-list">
                    <li>اطلاعات سلامت و شخصی من، با حداکثر امنیت و مطابق قوانین محرمانگی، نزد این سامانه محفوظ خواهد ماند.</li>
                    <li>توصیه‌های این سیستم توسط پیشرفته‌ترین الگوریتم‌های هوش مصنوعی ارائه می‌شود، اما جایگزین تشخیص پزشک نیست.</li>
                    <li>مسئولیت نهایی تصمیمات سلامت و استفاده از این توصیه‌ها بر عهده خودم است.</li>
                    <li>متعهد می‌شوم برای مسائل پزشکی مهم، حتماً با پزشک معتمدم مشورت کنم.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Step 2: Personal Information -->
    <div id="personal-info-step" class="step">
        <h2>اطلاعات شخصی</h2>
        
        <!-- First Name Input -->
        <div class="input-container text-input-simple" style="margin-bottom: 15px;">
            <input 
                type="text" 
                id="first-name-input" 
                dir="rtl" 
                maxlength="30"
                lang="fa"
                autocomplete="given-name"
                placeholder="نام">
        </div>
        
        <!-- Last Name Input -->
        <div class="input-container text-input-simple" style="margin-bottom: 15px;">
            <input 
                type="text" 
                id="last-name-input" 
                dir="rtl" 
                maxlength="40"
                lang="fa"
                autocomplete="family-name"
                placeholder="نام خانوادگی">
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
    <div id="height-weight-input-step" class="step">
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



    <!-- Step 6: Target Weight -->
    <div id="target-weight-step" class="step">
        <h2>وزن هدف شما چقدر است؟</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="target-weight-input">
            <span id="target-weight-display">وزن هدف شما</span>
        </div>
        <div id="target-weight-validation-container">
            <p id="targetWeight-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/gain-weight-min.png" width="30" height="30" alt="وزن هدف">
                    <div class="info-text">
                        <span class="first-line">وزن هدف شما را می‌پرسیم تا برنامه مناسب برای رسیدن به آن را طراحی کنیم.</span>
                        <span class="second-line">لطفاً وزن واقع‌بینانه‌ای را وارد کنید که با قد و ساختار بدنی شما تناسب داشته باشد</span>
                    </div>
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
            
            <!-- جزئیات دیابت -->
            <div id="chronic-diabetes-details" style="display: none; margin: 15px 0 20px 25px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-right: 3px solid #4CAF50;">
                <h4 style="margin: 0 0 15px 0; color: #333; font-size: 14px;">نوع دیابت:</h4>
                <div class="diabetes-options" style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="diabetes-option" data-value="type1">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">💉</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">دیابت نوع 1</div>
                    </div>
                    <div class="diabetes-option" data-value="type2">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">🩺</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">دیابت نوع 2</div>
                    </div>
                    <div class="diabetes-option" data-value="gestational">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">🤰</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">دیابت بارداری</div>
                    </div>
                    <div class="diabetes-option" data-value="prediabetes">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">⚠️</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">پیش‌دیابت</div>
                    </div>
                </div>
                
                <!-- اطلاعات تکمیلی -->
                <div id="chronic-diabetes-additional" style="margin-top: 15px; display: none;">
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">میزان قند خون ناشتا (اختیاری):</label>
                        <input type="number" id="chronic-fasting-blood-sugar" placeholder="مثلاً 120" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                        <span style="margin-right: 5px; font-size: 13px;">mg/dL</span>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">HbA1c (اختیاری):</label>
                        <input type="number" id="chronic-hba1c-level" step="0.1" placeholder="مثلاً 6.5" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
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
    
    <!-- Step 10: Medications Selection -->
    <div id="medications-step" class="step checkbox-step-container">
        <h2>داروهای مصرفی</h2>
        <p class="step-description">لطفاً داروهایی که به طور منظم مصرف می‌کنید را انتخاب کنید</p>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="medications-none" class="real-checkbox">
            <label for="medications-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچ داروی خاصی مصرف نمی‌کنم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="medications-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <div class="checkbox-container">
                <input type="checkbox" id="medication-diabetes" class="real-checkbox">
                <label for="medication-diabetes" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای دیابت (متفورمین، انسولین و...)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-thyroid" class="real-checkbox">
                <label for="medication-thyroid" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای تیروئید (لووتیروکسین و...)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-corticosteroids" class="real-checkbox">
                <label for="medication-corticosteroids" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کورتون‌ها (پردنیزولون و...)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-anticoagulants" class="real-checkbox">
                <label for="medication-anticoagulants" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای ضد انعقاد (وارفارین و ...)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-hypertension" class="real-checkbox">
                <label for="medication-hypertension" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای فشار خون</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-psychiatric" class="real-checkbox">
                <label for="medication-psychiatric" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای اعصاب و روان</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-hormonal" class="real-checkbox">
                <label for="medication-hormonal" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای هورمونی (قرص ضد بارداری، هورمون درمانی)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-cardiac" class="real-checkbox">
                <label for="medication-cardiac" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای قلبی و عروقی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-gastrointestinal" class="real-checkbox">
                <label for="medication-gastrointestinal" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">داروهای گوارشی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="medication-supplements" class="real-checkbox">
                <label for="medication-supplements" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مکمل‌ها، ویتامین‌ها و محصولات ورزشی</span>
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
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">🩺 بیماری‌های گوارشی ساختاری</h3>
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
            <h3 class="diffrent-category-titles">🌀 علائم و مشکلات عملکردی</h3>
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
            <h3 class="diffrent-category-titles">🦠 عفونت‌ها و مشکلات خاص</h3>
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
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">🔪 جراحی‌های گوارشی و متابولیک</h3>
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
            <h3 class="diffrent-category-titles">❤️ جراحی‌های عمده و ارگان‌ها</h3>
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
            <h3 class="diffrent-category-titles">🎗️ سرطان و جراحی‌های مرتبط</h3>
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
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">💊</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">شیمی درمانی</div>
                    </div>
                    <div class="cancer-option" data-value="radio">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">☢️</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پرتو درمانی</div>
                    </div>
                    <div class="cancer-option" data-value="surgery">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🔪</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">اخیراً جراحی شده‌ام</div>
                    </div>
                    <div class="cancer-option" data-value="finished">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">✅</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">درمانم تمام شده</div>
                    </div>
                </div>
            
                <!-- نوع سرطان -->
                <h4 style="margin: 20px 0 10px 0; color: #333; font-size: 14px;">نوع سرطان:</h4>
                <div class="cancer-options" style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="cancer-option" data-value="breast">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🎀</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پستان</div>
                    </div>
                    <div class="cancer-option" data-value="colon">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🩸</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">روده</div>
                    </div>
                    <div class="cancer-option" data-value="prostate">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">👨</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پروستات</div>
                    </div>
                    <div class="cancer-option" data-value="lung">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🫁</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">ریه</div>
                    </div>
                    <div class="cancer-option" data-value="blood">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🩸</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">خون</div>
                    </div>
                    <div class="cancer-option" data-value="other">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">❓</div>
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
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🚫</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>هیچ ورزشی نمی‌کنم</h3>
                    <p>بدون فعالیت ورزشی منظم در هفته</p>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="light">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🚶‍♂️</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>سبک</h3>
                    <p>۱-۲ روز در هفته، کمتر از ۳۰ دقیقه</p>
                    <span class="exercise-examples">(پیاده‌روی آرام، یوگا سبک، حرکات کششی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="medium">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🏃‍♂️</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>متوسط</h3>
                    <p>۳-۴ روز در هفته، ۳۰-۶۰ دقیقه</p>
                    <span class="exercise-examples">(دویدن سبک، شنا، بدنسازی متوسط، ورزش‌های هوازی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="high">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">💪</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>زیاد</h3>
                    <p>۵-۶ روز در هفته یا بیشتر، ۶۰+ دقیقه</p>
                    <span class="exercise-examples">(تمرین سنگین، کراس فیت، ورزش‌های رقابتی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="professional">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🏆</span>-->
                <!--</div>-->
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
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">🩺 محدودیت‌های پزشکی</h3>
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
            <h3 class="diffrent-category-titles">🌱 ترجیحات شخصی</h3>
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
        
    <!-- Step 17: Favorite Foods Selection -->
    <div id="favorite-foods-step" class="step checkbox-step-container">
        <h2>غذاهای مورد علاقه خود را انتخاب کنید</h2>
        <p class="step-description">لطفاً غذاهایی که بیشتر دوست دارید و مایلید در برنامه غذایی شما گنجانده شوند را انتخاب کنید</p>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="foods-none" class="real-checkbox">
            <label for="foods-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">ترجیح می‌دهم برنامه بر اساس نیازهای غذایی من تنظیم شود</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="favorite-foods-selection" class="checkbox-selection-container checkbox-list-container scrollable-container two-column-layout">
            <!-- 🥘 غذاهای اصلی ایرانی -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px; grid-column: 1 / span 2;">🥘 غذاهای اصلی ایرانی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="food-gheymeh" class="real-checkbox">
                <label for="food-gheymeh" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">قیمه (کم‌روغن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-ghormeh" class="real-checkbox">
                <label for="food-ghormeh" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">قرمه سبزی (کم‌چرب)</span>                
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-kabab-koobideh" class="real-checkbox">
                <label for="food-kabab-koobideh" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کباب کوبیده (کم‌چرب)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-joojeh-kabab" class="real-checkbox">
                <label for="food-joojeh-kabab" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جوجه کباب</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-kabab-barg" class="real-checkbox">
                <label for="food-kabab-barg" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کباب برگ</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-fesenjan" class="real-checkbox">
                <label for="food-fesenjan" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">فسنجان (کم‌شیرینی)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-bademjan" class="real-checkbox">
                <label for="food-bademjan" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">خورشت بادمجان (کم‌روغن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-karafs" class="real-checkbox">
                <label for="food-karafs" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">خورشت کرفس</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-aloo-esfenaj" class="real-checkbox">
                <label for="food-aloo-esfenaj" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">خورشت آلواسفناج</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-abgoosht" class="real-checkbox">
                <label for="food-abgoosht" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">آبگوشت (کم‌چربی)</span>
                </label>
            </div>
            
            <!-- 🍕 غذاهای بین‌المللی -->
            <h3 class="diffrent-category-titles" style="grid-column: 1 / span 2;">🍕 غذاهای بین‌المللی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="food-pizza" class="real-checkbox">
                <label for="food-pizza" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پیتزا (نسخه سالم)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-burger" class="real-checkbox">
                <label for="food-burger" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">همبرگر (نسخه سالم)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-pasta" class="real-checkbox">
                <label for="food-pasta" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پاستا (غلات کامل)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-sandwich" class="real-checkbox">
                <label for="food-sandwich" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">ساندویچ مرغ گریل</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-salad" class="real-checkbox">
                <label for="food-salad" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سالاد سزار سالم</span>
                </label>
            </div>
    
            <!-- 🍚 برنج‌های سالم -->
            <h3 class="diffrent-category-titles" style="grid-column: 1 / span 2;">🍚 برنج‌های سالم</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="food-chelo" class="real-checkbox">
                <label for="food-chelo" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">چلوی ساده</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-sabzi-polo" class="real-checkbox">
                <label for="food-sabzi-polo" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سبزی پلو</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-adas-polo" class="real-checkbox">
                <label for="food-adas-polo" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدس پلو</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-lobya-polo" class="real-checkbox">
                <label for="food-lobya-polo" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">لوبیا پلو</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-shevid-polo" class="real-checkbox">
                <label for="food-shevid-polo" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">شوید پلو</span>
                </label>
            </div>
    
            <!-- 🥗 پیش‌غذاها و مخلفات -->
            <h3 class="diffrent-category-titles" style="grid-column: 1 / span 2;">🥗 پیش‌غذاها و مخلفات</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="food-salad-shirazi" class="real-checkbox">
                <label for="food-salad-shirazi" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سالاد شیرازی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-mast-o-khiar" class="real-checkbox">
                <label for="food-mast-o-khiar" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">ماست و خیار</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-borani-esfenaj" class="real-checkbox">
                <label for="food-borani-esfenaj" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بورانی اسفناج</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-borani-bademjan" class="real-checkbox">
                <label for="food-borani-bademjan" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بورانی بادمجان</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-nokhod-kishmesh" class="real-checkbox">
                <label for="food-nokhod-kishmesh" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">نخود و کشمش (متعادل)</span>
                </label>
            </div>
    
            <!-- 🍲 غذاهای سنتی -->
            <h3 class="diffrent-category-titles" style="grid-column: 1 / span 2;">🍲 غذاهای سنتی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="food-ash-reshteh" class="real-checkbox">
                <label for="food-ash-reshteh" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">آش رشته (کم‌روغن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-ash-jow" class="real-checkbox">
                <label for="food-ash-jow" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">آش جو</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-halim" class="real-checkbox">
                <label for="food-halim" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حلیم گندم (کم‌شیرینی)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-adas" class="real-checkbox">
                <label for="food-adas" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدسی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-lobya" class="real-checkbox">
                <label for="food-lobya" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">خوراک لوبیا (کم‌روغن)</span>
                </label>
            </div>
    
            <!-- 🥮 غذاهای ساده -->
            <h3 class="diffrent-category-titles" style="grid-column: 1 / span 2;">🥮 غذاهای ساده</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="food-omelet" class="real-checkbox">
                <label for="food-omelet" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">املت (کم‌روغن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-nimroo" class="real-checkbox">
                <label for="food-nimroo" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">نیمرو (کم‌روغن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-egg-tomato" class="real-checkbox">
                <label for="food-egg-tomato" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">خوراک تخم مرغ و گوجه</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-kookoo-sabzi" class="real-checkbox">
                <label for="food-kookoo-sabzi" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کوکو سبزی (فر یا گریل)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="food-kookoo-sibzamini" class="real-checkbox">
                <label for="food-kookoo-sibzamini" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کوکو سیب زمینی (فر یا گریل)</span>
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
                                    <span class="price-icon">🤖</span>
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
                                    <span class="price-icon">👨‍⚕️</span>
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
                                    <span class="price-icon">💰</span>
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

</form>
    
</form>

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
  
  // هدایت به صفحه لاگین با کامپوننت لودینگ
  const loginUrl = '<?php echo wp_login_url(); ?>?redirect_to=' + encodeURIComponent(currentUrl);
  
  const loader = new AiDastyarLoader({
    message: 'در حال انتقال به صفحه ورود',
    theme: 'light',
    size: 'medium',
    position: 'center',
    closable: false,
    overlay: true,
    autoHide: null,
    persistent: false, 
    redirectUrl: loginUrl,
    redirectDelay: 2000, 
    onShow: null,
    onHide: null,
    onRedirect: null        
  });
  loader.show();
}

window.addEventListener('load', function() {
    // پنهان کردن لودینگ در صورت وجود
    if (window.AiDastyarLoader && window.AiDastyarLoader.hide) {
        window.AiDastyarLoader.hide();
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const loggedIn = urlParams.get('logged_in');
    
    if (loggedIn === '1' && sessionStorage.getItem('diet_form_data')) {
        // نمایش loader هنگام بازیابی داده‌ها
        const loader = new AiDastyarLoader({
            message: 'در حال بازیابی اطلاعات',
            theme: 'light',
            size: 'medium',
            position: 'center',
            closable: false,
            overlay: true,
            autoHide: 2000,
            persistent: false, 
            redirectUrl: null,
            redirectDelay: null, 
            onShow: null,
            onHide: null,
            onRedirect: null        
        });
        loader.show();

        // بازیابی داده‌ها
        const savedData = JSON.parse(sessionStorage.getItem('diet_form_data'));
        const savedStep = savedData._currentStep || 1;
        
        if (savedData) {
            const {_timestamp, _currentStep, ...formData} = savedData;
            Object.assign(window.state.formData, formData);
            
            window.navigateToStep(STEPS.TERMS_AGREEMENT);
            
            if (typeof window.state.updateFormElementsFromState === 'function') {
                window.state.updateFormElementsFromState();
            }
        }
        
        // پاک کردن داده‌های ذخیره شده
        sessionStorage.removeItem('diet_form_data');
        sessionStorage.removeItem('diet_form_redirect_url');
        
        // حذف پارامتر logged_in از URL
        if (window.history.replaceState) {
            const newUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, newUrl);
        }
    }
});
</script>