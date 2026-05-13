<?php
if (!defined('ABSPATH')) exit;
?>

<div class="diet-form-wrapper free-diet-form-wrapper" data-freediet-form>
    <div class="diet-form-container">
        <div class="diet-form-header">
            <h2 class="diet-form-title">فرم رژیم رایگان FreeDiet</h2>
            <p class="diet-form-subtitle">
                این فرم فعلاً به‌صورت نمایشی و فقط در دو مرحله طراحی شده است.
            </p>
        </div>

        <div class="fd-step-indicator-wrapper">
            <div class="fd-step-counter-box">
                <span class="fd-step-counter-label">مرحله</span>
                <span data-fd-current-step>1</span>
                <span class="fd-step-divider">از</span>
                <span data-fd-total-steps>2</span>
            </div>

            <div class="fd-progress-bar-wrapper">
                <div class="fd-progress-bar" data-fd-progress-bar></div>
            </div>
        </div>

        <form class="diet-multi-step-form fd-form" data-fd-form novalidate>
            <div class="fd-step fd-step--active" data-step="1">
                <div class="fd-step-content-card">
                    <div class="fd-step-badge">مرحله اول</div>
                    <h3 class="fd-step-title">FreeDiet</h3>
                    <p class="fd-step-description">
                        این بخش صرفاً برای تست ظاهر فرم ساخته شده و فعلاً هیچ ورودی‌ای از کاربر دریافت نمی‌شود.
                    </p>
                </div>
            </div>

            <div class="fd-step" data-step="2">
                <div class="fd-step-content-card">
                    <div class="fd-step-badge">مرحله دوم</div>
                    <h3 class="fd-step-title">آماده ثبت نهایی</h3>
                    <p class="fd-step-description">
                        در این مرحله هم فقط ساختار دیداری فرم تست می‌شود و بعد از آن دکمه ثبت نمایش داده خواهد شد.
                    </p>
                </div>
            </div>

            <div class="fd-step-actions" data-fd-next-wrap>
                <button type="button" class="fd-step-action-btn fd-btn-secondary" data-fd-back style="display: none;">
                    مرحله قبل
                </button>

                <button type="button" class="fd-step-action-btn fd-btn-primary" data-fd-next>
                    گام بعد
                </button>
            </div>

            <div class="fd-step-actions" data-fd-submit-wrap style="display: none;">
                <button type="submit" class="fd-step-action-btn fd-btn-primary">
                    ثبت فرم
                </button>
            </div>
        </form>
    </div>
</div>