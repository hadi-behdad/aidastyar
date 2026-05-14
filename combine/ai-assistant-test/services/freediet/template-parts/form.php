<?php
/**
 * FreeDiet Form Template
 * Service: رژیم غذایی رایگان
 * Note: فعلاً نمایشی — بدون ورودی کاربر
 */
?>

<!-- Main Wrapper -->
<div class="freediet-wrapper" data-freediet-form>
    
    <!-- Fixed Header (حالا داخل wrapper است) -->
    <div id="freediet-fixed-header">
        <div class="freediet-header-row">
            <!-- شمارنده صفحه در سمت راست -->
            <div id="freediet-step-counter">
                <span id="freediet-current-step">1</span>/<span id="freediet-total-steps">5</span>
            </div>
            
            <!-- لوگو -->
            <div id="freediet-logo" onclick="window.location.href='<?php echo esc_url( home_url( '/' ) ); ?>'">
                AiDASTYAR
            </div>
            
            <!-- دکمه Back در سمت چپ -->
            <button type="button" id="freediet-back-button">›</button>
        </div>
        <div id="freediet-progress-bar-container">
            <div class="freediet-progress-bar" data-freediet-progress-bar style="width: 0%;"></div>
        </div>
    </div>

    <div class="freediet-container">

        <!-- Header -->
        <div class="freediet-header">
            <h2 class="freediet-title">رژیم غذایی رایگان</h2>
        </div>

        <!-- Form -->
        <form data-fd-form>

            <!-- Step 1 -->
            <div class="freediet-step freediet-step--active" data-freediet-step="1">
                <div class="freediet-step-card">
                    <span class="freediet-step-badge">مرحله اول</span>
                    <h3 class="freediet-step-title">تست مچ دست</h3>
                    <p class="freediet-step-desc">
                        لطفاً عددی را که با اندازه مچ دست شما مطابقت دارد انتخاب کنید
                    </p>
                    
                    <div class="freediet-options-grid" id="freediet-wrist-options">
                        <div class="freediet-option-card" data-value="1">
                            <span class="freediet-option-number">1</span>
                            <span class="freediet-option-label">کوچک</span>
                        </div>
                        <div class="freediet-option-card" data-value="2">
                            <span class="freediet-option-number">2</span>
                            <span class="freediet-option-label">متوسط</span>
                        </div>
                        <div class="freediet-option-card" data-value="3">
                            <span class="freediet-option-number">3</span>
                            <span class="freediet-option-label">بزرگ</span>
                        </div>
                        <div class="freediet-option-card" data-value="4">
                            <span class="freediet-option-number">4</span>
                            <span class="freediet-option-label">بسیار بزرگ</span>
                        </div>
                    </div>
                    
                    <input type="hidden" id="freediet-wrist-value" name="wrist_size" value="">
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="freediet-step" data-freediet-step="2">
                <div class="freediet-step-card">
                    <span class="freediet-step-badge">مرحله دوم</span>
                    <h3 class="freediet-step-title">الگوی وزن و اندامت در دبیرستان</h3>
                    <p class="freediet-step-desc">
                        لطفاً وضعیت وزن و اندام خود را در دوران دبیرستان انتخاب کنید
                    </p>
                    
                    <div class="freediet-options-grid" id="freediet-bodytype-options">
                        <div class="freediet-option-card" data-value="1">
                            <span class="freediet-option-number">1</span>
                            <span class="freediet-option-label">لاغر</span>
                        </div>
                        <div class="freediet-option-card" data-value="2">
                            <span class="freediet-option-number">2</span>
                            <span class="freediet-option-label">نرمال</span>
                        </div>
                        <div class="freediet-option-card" data-value="3">
                            <span class="freediet-option-number">3</span>
                            <span class="freediet-option-label">عضلانی</span>
                        </div>
                        <div class="freediet-option-card" data-value="4">
                            <span class="freediet-option-number">4</span>
                            <span class="freediet-option-label">چاق</span>
                        </div>
                    </div>
                    
                    <input type="hidden" id="freediet-bodytype-value" name="body_type" value="">
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="freediet-step" data-freediet-step="3">
                <div class="freediet-step-card">
                    <span class="freediet-step-badge">مرحله سوم</span>
                    <h3 class="freediet-step-title">واکنش بدنت به ۲ هفته پرخوری و تعطیلات</h3>
                    <p class="freediet-step-desc">
                        لطفاً واکنش بدن خود را به دو هفته پرخوری در تعطیلات انتخاب کنید
                    </p>
                    
                    <div class="freediet-options-grid" id="freediet-overeating-options">
                        <div class="freediet-option-card" data-value="1">
                            <span class="freediet-option-number">1</span>
                            <span class="freediet-option-label">افزایش وزن کم</span>
                        </div>
                        <div class="freediet-option-card" data-value="2">
                            <span class="freediet-option-number">2</span>
                            <span class="freediet-option-label">افزایش وزن متوسط</span>
                        </div>
                        <div class="freediet-option-card" data-value="3">
                            <span class="freediet-option-number">3</span>
                            <span class="freediet-option-label">افزایش وزن زیاد</span>
                        </div>
                        <div class="freediet-option-card" data-value="4">
                            <span class="freediet-option-number">4</span>
                            <span class="freediet-option-label">افزایش وزن خیلی زیاد</span>
                        </div>
                    </div>
                    
                    <input type="hidden" id="freediet-overeating-value" name="overeating_reaction" value="">
                </div>
            </div>
            
            <!-- Step 4 -->
            <div class="freediet-step" data-freediet-step="4">
                <div class="freediet-step-card">
                    <span class="freediet-step-badge">مرحله چهارم</span>
                    <h3 class="freediet-step-title">فرم بدن و الگوی ذخیره چربی‌ات</h3>
                    <p class="freediet-step-desc">
                        لطفاً فرم بدن و الگوی ذخیره چربی خود را انتخاب کنید
                    </p>
                    
                    <div class="freediet-options-grid" id="freediet-fatpattern-options">
                        <div class="freediet-option-card" data-value="1">
                            <span class="freediet-option-number">1</span>
                            <span class="freediet-option-label">سیب (شکم)</span>
                        </div>
                        <div class="freediet-option-card" data-value="2">
                            <span class="freediet-option-number">2</span>
                            <span class="freediet-option-label">گلابی (پایین تنه)</span>
                        </div>
                        <div class="freediet-option-card" data-value="3">
                            <span class="freediet-option-number">3</span>
                            <span class="freediet-option-label">مستطیل (متوازن)</span>
                        </div>
                        <div class="freediet-option-card" data-value="4">
                            <span class="freediet-option-number">4</span>
                            <span class="freediet-option-label">ساعت شنی</span>
                        </div>
                    </div>
                    
                    <input type="hidden" id="freediet-fatpattern-value" name="fat_pattern" value="">
                </div>
            </div>
            
            <!-- Step 5 -->
            <div class="freediet-step" data-freediet-step="5">
                <div class="freediet-step-card">
                    <span class="freediet-step-badge">مرحله پنجم</span>
                    <h3 class="freediet-step-title">تجربه (یا پیش‌بینی) تو از عضله‌سازی و ورزش</h3>
                    <p class="freediet-step-desc">
                        لطفاً تجربه یا پیش‌بینی خود را از عضله‌سازی و ورزش انتخاب کنید
                    </p>
                    
                    <div class="freediet-options-grid" id="freediet-musclegain-options">
                        <div class="freediet-option-card" data-value="1">
                            <span class="freediet-option-number">1</span>
                            <span class="freediet-option-label">عضله‌سازی سخت</span>
                        </div>
                        <div class="freediet-option-card" data-value="2">
                            <span class="freediet-option-number">2</span>
                            <span class="freediet-option-label">عضله‌سازی متوسط</span>
                        </div>
                        <div class="freediet-option-card" data-value="3">
                            <span class="freediet-option-number">3</span>
                            <span class="freediet-option-label">عضله‌سازی آسان</span>
                        </div>
                        <div class="freediet-option-card" data-value="4">
                            <span class="freediet-option-number">4</span>
                            <span class="freediet-option-label">عضله‌سازی خیلی آسان</span>
                        </div>
                    </div>
                    
                    <input type="hidden" id="freediet-musclegain-value" name="muscle_gain" value="">
                </div>
            </div>

            <!-- دکمه‌های ثابت پایین صفحه -->
            <div id="freediet-next-button-container" style="display: flex;">
                <button type="button" class="freediet-btn freediet-btn--primary next-step" data-freediet-next>
                    گام بعد
                </button>
            </div>
            
            <div id="freediet-submit-button-container" style="display: none;">
                <button type="submit" class="freediet-btn freediet-btn--primary submit-step">
                    ثبت نهایی
                </button>
            </div>
            
            <!-- دکمه Back مخفی (برای منطق JS) -->
            <button type="button" data-freediet-back style="display: none;">گام قبل</button>

        </form>

    </div>
</div>