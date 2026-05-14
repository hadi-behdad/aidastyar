<?php
/**
 * FreeDiet Form Template
 * Service: رژیم غذایی رایگان
 * Note: فعلاً نمایشی — بدون ورودی کاربر
 */
?>

<div id="freediet-fixed-header">
    <div class="freediet-header-row">
        <!-- شمارنده صفحه در سمت راست -->
        <div id="freediet-step-counter">
            <span id="freediet-current-step">1</span>/<span id="freediet-total-steps">2</span>
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

<!-- Main Wrapper -->
<div class="freediet-wrapper" data-freediet-form>
    <div class="freediet-container">

        <!-- Header -->
        <div class="freediet-header">
            <h2 class="freediet-title">رژیم غذایی رایگان</h2>
            <p class="freediet-subtitle">
                چند مرحله ساده تا دریافت برنامه غذایی شخصی‌سازی‌شده
            </p>
        </div>

        <!-- Step Indicator -->
        <div class="freediet-step-indicator">
            <div class="freediet-step-counter">
                <span class="freediet-step-counter-label">مرحله</span>
                <span data-freediet-current-step>1</span>
                <span class="freediet-step-divider">از</span>
                <span data-freediet-total-steps>2</span>
            </div>
        </div>

        <!-- Form -->
        <form data-fd-form>

            <!-- Step 1 -->
            <div class="freediet-step freediet-step--active" data-freediet-step="1">
                <div class="freediet-step-card">
                    <span class="freediet-step-badge">مرحله اول</span>
                    <h3 class="freediet-step-title">اطلاعات اولیه</h3>
                    <p class="freediet-step-desc">
                        این فرم فعلاً به‌صورت نمایشی طراحی شده است و هیچ ورودی‌ای از کاربر دریافت نمی‌شود.
                    </p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="freediet-step" data-freediet-step="2">
                <div class="freediet-step-card">
                    <span class="freediet-step-badge">مرحله دوم</span>
                    <h3 class="freediet-step-title">تأیید و ارسال</h3>
                    <p class="freediet-step-desc">
                        در این مرحله ساختار دیداری فرم تست می‌شود. پس از تأیید، دکمه ثبت نمایش داده می‌شود.
                    </p>
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