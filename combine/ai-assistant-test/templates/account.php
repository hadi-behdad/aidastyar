<?php
/**
 * Template Name: ویرایش حساب کاربری
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/account')));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// دریافت اطلاعات کاربر
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$user_phone = get_user_meta($user_id, 'billing_phone', true);
$user_email = $current_user->user_email;
$display_name = $current_user->display_name;

// پردازش فرم ارسال شده
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nonce = $_POST['_wpnonce'] ?? '';
    
    if (wp_verify_nonce($nonce, 'update_user_profile')) {
        // به روزرسانی اطلاعات پایه
        $new_first_name = sanitize_text_field($_POST['first_name']);
        $new_last_name = sanitize_text_field($_POST['last_name']);
        $new_phone = sanitize_text_field($_POST['phone']);
        
        update_user_meta($user_id, 'first_name', $new_first_name);
        update_user_meta($user_id, 'last_name', $new_last_name);
        update_user_meta($user_id, 'billing_phone', $new_phone);
        
        // به روزرسانی display name اگر نام کامل تغییر کرد
        if ($new_first_name && $new_last_name) {
            $new_display_name = $new_first_name . ' ' . $new_last_name;
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $new_display_name
            ]);
        }
        
        $success_message = 'اطلاعات با موفقیت به روزرسانی شد.';
        
        // رفرش اطلاعات
        $first_name = $new_first_name;
        $last_name = $new_last_name;
        $user_phone = $new_phone;
        $display_name = $new_display_name ?? $display_name;
    } else {
        $error_message = 'خطای امنیتی! لطفا دوباره تلاش کنید.';
    }
}

get_header();
?>

<div class="acc-account-main-container">
    <div class="acc-account-container">
        <div class="acc-account-header">
            <div class="acc-account-avatar">
                <?php echo get_avatar($user_id, 80); ?>
            </div>
            <h1>ویرایش اطلاعات حساب کاربری</h1>
            <a href="<?php echo home_url('/profile'); ?>" class="acc-back-to-profile">
                <span class="dashicons dashicons-arrow-right-alt"></span>
                بازگشت به پروفایل
            </a>
        </div>

        <div class="acc-account-content">
            <?php if (isset($success_message)): ?>
                <div class="acc-alert acc-alert-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php echo esc_html($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="acc-alert acc-alert-error">
                    <span class="dashicons dashicons-warning"></span>
                    <?php echo esc_html($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="acc-profile-form">
                <?php wp_nonce_field('update_user_profile'); ?>
                
                <div class="acc-form-section">
                    <h2>
                        <span class="dashicons dashicons-admin-users"></span>
                        اطلاعات شخصی
                    </h2>
                    
                    <div class="acc-form-grid">
                        <div class="acc-form-group">
                            <label for="first_name">نام</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo esc_attr($first_name); ?>" 
                                   class="acc-form-input">
                        </div>
                        
                        <div class="acc-form-group">
                            <label for="last_name">نام خانوادگی</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo esc_attr($last_name); ?>" 
                                   class="acc-form-input">
                        </div>
                    </div>
                    
                    <div class="acc-form-group">
                        <label for="phone">شماره تماس</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo esc_attr($user_phone); ?>" 
                               class="acc-form-input" 
                               placeholder="09xxxxxxxxx">
                    </div>
                    
                    <div class="acc-form-group">
                        <label for="email">ایمیل (غیرقابل تغییر)</label>
                        <input type="email" id="email" 
                               value="<?php echo esc_attr($user_email); ?>" 
                               class="acc-form-input" disabled>
                        <small class="acc-form-help">برای تغییر ایمیل با پشتیبانی تماس بگیرید</small>
                    </div>
                    
                    <div class="acc-form-group">
                        <label for="display_name">نام نمایشی</label>
                        <input type="text" id="display_name" 
                               value="<?php echo esc_attr($display_name); ?>" 
                               class="acc-form-input" disabled>
                    </div>
                </div>
                
                <div class="acc-form-actions">
                    <button type="submit" name="update_profile" class="acc-submit-btn">
                        <span class="dashicons dashicons-update"></span>
                        به روزرسانی اطلاعات
                    </button>
                    
                    <button type="button" onclick="window.location.href='<?php echo home_url('/profile'); ?>'" class="acc-cancel-btn">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* استایل‌های اصلی */
.acc-account-main-container {
    display: flex;
    justify-content: center;
    min-height: calc(100vh - 200px);
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-family: 'Vazir', 'Tahoma', sans-serif;
}

.acc-account-container {
    max-width: 500px;
    width: 100%;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin: 20px 0;
}

.acc-account-header {
    background: linear-gradient(135deg, #00857a 0%, #00c9b7 100%);
    color: white;
    padding: 30px 25px;
    text-align: center;
    position: relative;
}

.acc-account-avatar {
    margin-bottom: 15px;
}

.acc-account-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.acc-account-header h1 {
    margin: 0 0 20px 0;
    font-size: 24px;
    font-weight: 700;
}

.acc-back-to-profile {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: white;
    text-decoration: none;
    font-size: 14px;
    opacity: 0.9;
    transition: all 0.3s ease;
}

.acc-back-to-profile:hover {
    opacity: 1;
    gap: 10px;
}

.acc-back-to-profile .dashicons {
    font-size: 16px;
    transform: rotate(180deg);
}

.acc-account-content {
    padding: 25px;
}

/* استایل‌های آلرت */
.acc-alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.acc-alert-success {
    background: #f0fff4;
    color: #2d7d32;
    border: 1px solid #c8e6c9;
}

.acc-alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.acc-alert .dashicons {
    font-size: 20px;
}

/* استایل‌های فرم */
.acc-profile-form {
    direction: rtl;
}

.acc-form-section {
    background: #f8fafc;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    border: 1px solid #e2e8f0;
    box-sizing: border-box;
}

.acc-form-section h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #00857a;
    margin: 0 0 25px 0;
    font-size: 18px;
    font-weight: 600;
}

.acc-form-section h2 .dashicons {
    font-size: 20px;
}

.acc-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.acc-form-group {
    margin-bottom: 20px;
    width: 100%;
    box-sizing: border-box;
}

.acc-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.acc-form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
    font-family: 'Vazir', 'Tahoma', sans-serif;
    box-sizing: border-box;
    max-width: 100%;
}

.acc-form-input:focus {
    outline: none;
    border-color: #00857a;
    box-shadow: 0 0 0 3px rgba(0, 133, 122, 0.1);
}

.acc-form-input:disabled {
    background: #f8fafc;
    color: #64748b;
    cursor: not-allowed;
}

.acc-form-help {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #64748b;
}

/* استایل‌های دکمه‌ها - بهبود یافته برای دسکتاپ */
.acc-form-actions {
    display: flex;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
}

.acc-submit-btn,
.acc-cancel-btn {
    width: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Vazir', 'Tahoma', sans-serif;
    min-width: 140px;
    height: 46px;
    box-sizing: border-box;
}

.acc-submit-btn {
    background: linear-gradient(135deg, #00857a 0%, #00c9b7 100%);
    color: white;
}

.acc-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 133, 122, 0.4);
}

.acc-cancel-btn {
    background: white;
    color: #64748b;
    border: 2px solid #e2e8f0;
}

.acc-cancel-btn:hover {
    background: #f8fafc;
    color: #374151;
    border-color: #cbd5e0;
    transform: translateY(-1px);
}

/* رسپانسیو - بهبود یافته */
@media (max-width: 768px) {
    .acc-account-main-container {
        padding: 10px;
    }
    
    .acc-account-container {
        margin: 10px 0;
        border-radius: 15px;
    }
    
    .acc-account-header {
        padding: 25px 20px;
    }
    
    .acc-account-content {
        padding: 20px;
    }
    
    .acc-form-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .acc-form-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .acc-submit-btn,
    .acc-cancel-btn {
        width: 100%;
        justify-content: center;
        margin: 0;
        min-width: auto;
    }
    
    .acc-form-section {
        padding: 20px;
    }
    
    .acc-form-input {
        padding: 14px 16px;
        font-size: 16px; /* جلوگیری از زوم در iOS */
    }
}

@media (max-width: 480px) {
    .acc-account-content {
        padding: 15px;
    }
    
    .acc-form-section {
        padding: 15px;
    }
    
    .acc-form-actions {
        gap: 10px;
    }
}

/* انیمیشن‌ها */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.acc-account-container {
    animation: fadeInUp 0.6s ease-out;
}

.acc-form-section {
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

/* رفع مشکل overflow برای موبایل */
.acc-form-group {
    overflow: hidden;
}

.acc-form-input {
    max-width: 100%;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}
</style>

<?php
get_footer();