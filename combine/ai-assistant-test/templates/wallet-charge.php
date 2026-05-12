<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/wallet-charge.php
 * Template Name: شارژ کیف پول
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

if (!defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// nocache_headers(); // تابع داخلی وردپرس

// // در صورت تمایل، دستی هم اضافه کن:
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Cache-Control: post-check=0, pre-check=0", false);
// header("Pragma: no-cache");
// header("Expires: 0");

get_header();

if (isset($_GET['payment'])) {
    $message_class = '';
    $message_icon = '';
    $message_text = '';

    switch ($_GET['payment']) {
        case 'success':
            $message_class = 'wall-chrg-ai-alert-success';
            $message_text = 'پرداخت با موفقیت انجام شد.';
            $message_text .= isset($_GET['ref_id']) ? ' کد پیگیری: <span dir="ltr">' . $_GET['ref_id'] . '</span>' : '';
            $message_text .= '<br><br>🔔 <strong>مرحله بعد:</strong> لطفاً به <strong style="background:#f0f0f0;padding:2px 6px;">تب قبلی مرورگر</strong> (همان صفحه‌ای که رژیم را انتخاب می‌کردید) برگردید.';
            break;
        case 'failed':
            $message_class = 'wall-chrg-ai-alert-error';
            $message_icon = '❌';
            $reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : 'خطای نامشخص';
            $message_text = 'پرداخت ناموفق بود. دلیل: ' . esc_html($reason);
            break;
        case 'cancelled':
            $message_class = 'wall-chrg-ai-alert-warning';
            $message_icon = '⚠️';
            $message_text = 'پرداخت توسط شما لغو شد.';
            break;
        case 'error':
            $message_class = 'wall-chrg-ai-alert-error';
            $message_icon = '🚫';
            $reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : 'خطای نامشخص';
            $message_text = 'خطایی رخ داده است: ' . esc_html($reason);
            break;
    }

    if (!empty($message_text)) {
        echo '<div class="wall-chrg-ai-alert ' . $message_class . '">';
        echo '<span class="wall-chrg-ai-alert-icon">' . $message_icon . '</span>';
        echo '<span class="wall-chrg-ai-alert-message">' . $message_text . '</span>';
        echo '</div>';
    }
}

$user_id = get_current_user_id();
$wallet = AI_Assistant_Payment_Handler::get_instance();
$current_credit = $wallet->get_user_credit($user_id);

$minimum_charge = ai_wallet_get_minimum_charge();
$formatted_minimum = ai_wallet_format_minimum_charge_fa(); // تغییر به تابع فارسی


$needed_amount = isset($_GET['needed_amount']) ? (int)$_GET['needed_amount'] : 0;

// بعد از تعریف $minimum_charge، مبلغ مورد نیاز را بررسی کنید
if ($needed_amount > 0 && $needed_amount >= $minimum_charge) {
    $preselected_amount = $needed_amount;
} else {
    $preselected_amount = 0;
}

?>

<div class="wall-chrg-ai-wallet-charge-page">
    <div class="wall-chrg-ai-wallet-header">
        <div class="wall-chrg-ai-header-content">
            <div class="wall-chrg-ai-header-title">
                <span class="wall-chrg-ai-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                        <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                        <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                    </svg>
                </span>
                <h1>شارژ کیف پول</h1>
            </div>
        </div>
    </div>

    <div class="wall-chrg-ai-wallet-container">
        <div class="wall-chrg-ai-balance-card">
            <div class="wall-chrg-ai-balance-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                    <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                </svg>
            </div>
            <div class="wall-chrg-ai-balance-info">
                <span class="wall-chrg-ai-balance-label">موجودی فعلی شما</span>
                <span class="wall-chrg-ai-balance-amount"><?php echo format_number_fa($current_credit); ?> <span class="wall-chrg-currency">تومان</span></span>
            </div>
        </div>
        
        <?php if ($needed_amount > 0) : ?>
        <div class="ai-notification-box">
            <div class="ai-notification-icon">💡</div>
            <div class="ai-notification-content">
                <h4>شارژ خودکار پیشنهادی</h4>
                <p>برای تکمیل پرداخت سرویس رژیم غذایی، به <?php echo format_number_fa($needed_amount); ?> تومان دیگر نیاز دارید. این مبلغ به صورت خودکار برای شما انتخاب شده است.</p>
            </div>
        </div>    
        
        <style>
        .ai-notification-box {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 1px solid #90caf9;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 2rem;
        }
        
        .ai-notification-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .ai-notification-content h4 {
            margin: 0 0 0.5rem 0;
            color: #1565c0;
            font-size: 1.1rem;
        }
        
        .ai-notification-content p {
            margin: 0;
            color: #37474f;
            line-height: 1.6;
            text-align: justify;
        }
        </style>
        <?php endif; ?>        

        <form method="POST" action="" class="ai-charge-form">
            <div class="wall-chrg-ai-form-section">
                <h3 class="wall-chrg-ai-form-title">مبلغ مورد نظر برای شارژ را انتخاب کنید</h3>
                
                <div class="wall-chrg-ai-amount-presets">
                    <div class="wall-chrg-ai-preset-row">
                        <input type="radio" id="amount_50000" name="preset_amount" value="50000" class="wall-chrg-ai-amount-radio" required>
                        <label for="amount_50000" class="wall-chrg-ai-amount-preset">
                            <span class="wall-chrg-ai-amount-value">۵۰,۰۰۰</span>
                            <span class="wall-chrg-ai-amount-currency">تومان</span>
                        </label>
                        
                        <input type="radio" id="amount_100000" name="preset_amount" value="100000" class="wall-chrg-ai-amount-radio">
                        <label for="amount_100000" class="wall-chrg-ai-amount-preset">
                            <span class="wall-chrg-ai-amount-value">۱۰۰,۰۰۰</span>
                            <span class="wall-chrg-ai-amount-currency">تومان</span>
                        </label>
                        
                        <input type="radio" id="amount_200000" name="preset_amount" value="200000" class="wall-chrg-ai-amount-radio">
                        <label for="amount_200000" class="wall-chrg-ai-amount-preset">
                            <span class="wall-chrg-ai-amount-value">۲۰۰,۰۰۰</span>
                            <span class="wall-chrg-ai-amount-currency">تومان</span>
                        </label>
                    </div>
                    
                    <div class="wall-chrg-ai-preset-row">
                        <input type="radio" id="amount_custom" name="preset_amount" value="custom" class="wall-chrg-ai-amount-radio">
                        <label for="amount_custom" class="wall-chrg-ai-amount-preset wall-chrg-ai-custom-preset">
                            <span class="wall-chrg-ai-amount-value">مبلغ دلخواه</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="wall-chrg-ai-form-section wall-chrg-ai-custom-amount-section" id="custom_amount_section">
                <label for="custom_amount" class="wall-chrg-ai-form-label">مبلغ دلخواه</label>
                <div class="wall-chrg-input-container">
                <input type="number" 
                       id="custom_amount" 
                       name="custom_amount" 
                       min="<?php echo $minimum_charge; ?>" 
                       max="<?php echo ai_wallet_get_maximum_charge(); ?>"
                       step="1" 
                       class="wall-chrg-ai-form-input" />
                    <span class="input-currency-hint">تومان</span>
                </div>
            </div>

            <input type="hidden" name="charge_amount" id="charge_amount" value="" />

            <div class="wall-chrg-ai-important-notes">
                <h4>نکات مهم:</h4>
                <ul>
                    <li>حداقل مبلغ شارژ <?php echo $formatted_minimum; ?> تومان می‌باشد.</li>
                    <li>حداکثر مبلغ شارژ در هر تراکنش <?php echo ai_wallet_format_maximum_charge_fa(); ?> تومان می‌باشد.</li>
                    <li>پس از پرداخت، مبلغ بلافاصله به کیف پول شما اضافه می‌شود.</li>
                    <li>در صورت بروز مشکل در پرداخت، با پشتیبانی تماس بگیرید.</li>
                </ul>
            </div>

            <div class="wall-chrg-ai-form-section">
                <h3 class="wall-chrg-ai-form-title">انتخاب درگاه پرداخت</h3>
            
                <div class="wall-chrg-ai-gateways">
                    <label class="wall-chrg-ai-gateway-card">
                        <input type="radio"
                               name="gateway"
                               value="zibal"
                               class="wall-chrg-ai-gateway-radio"
                               checked>
                        <div class="wall-chrg-ai-gateway-content">
                            <div class="wall-chrg-ai-gateway-header">
                                <span class="wall-chrg-ai-gateway-name">زیبال</span>
                                <span class="wall-chrg-ai-gateway-badge">پیش‌فرض</span>
                            </div>
                            <p class="wall-chrg-ai-gateway-desc">
                                پرداخت سریع و امن از طریق درگاه زیبال.
                            </p>
                        </div>
                    </label>
            
                    <label class="wall-chrg-ai-gateway-card">
                        <input type="radio"
                               name="gateway"
                               value="zarinpal"
                               class="wall-chrg-ai-gateway-radio">
                        <div class="wall-chrg-ai-gateway-content">
                            <div class="wall-chrg-ai-gateway-header">
                                <span class="wall-chrg-ai-gateway-name">زرین‌پال</span>
                            </div>
                            <p class="wall-chrg-ai-gateway-desc">
                                پرداخت از طریق درگاه زرین‌پال.
                            </p>
                        </div>
                    </label>
                </div>
            </div>
            <div class="wall-chrg-ai-form-actions">
                <button type="submit" name="wallet_charge_submit" class="wall-chrg-ai-payment-button">
                    پرداخت و شارژ کیف پول
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* فونت Vazir */
@font-face {
    font-family: 'Vazir';
    src: url('<?php echo get_template_directory_uri(); ?>/assets/fonts/Vazir.woff2') format('woff2'),
         url('<?php echo get_template_directory_uri(); ?>/assets/fonts/Vazir.woff') format('woff');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

:root {
    --primary-light: #f0faf9;
    --primary-accent: #F4C017;
    --primary-medium: #00857a;
    --primary-dark: #00665c;
    --text-color: #1f2937;
    --text-light: #6b7280;
    --background: #f9fafb;
    --card-bg: #ffffff;
    --border-color: #e5e7eb;
    --success-bg: #f0fdf4;
    --success-border: #bbf7d0;
    --success-text: #166534;
}

.wall-chrg-ai-amount-value, .wall-chrg-ai-balance-amount, .wall-chrg-currency {
    font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    direction: ltr; /* برای اعداد */
    unicode-bidi: embed;
}

/* استایل های جدید برای صفحه شارژ کیف پول */
.wall-chrg-ai-wallet-charge-page {
    max-width: 500px; /* محدودیت عرض جدید */
    margin: auto auto; /* مرکز کردن صفحه */
    padding: 1rem;
    font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    direction: rtl;
    background-color: var(--background);
    border-radius: 12px;
}

.wall-chrg-ai-wallet-header {
    margin-bottom: 2rem;
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.wall-chrg-ai-header-content {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    position: relative;
}

.wall-chrg-ai-header-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.wall-chrg-ai-header-icon {
    display: flex;
    color: var(--primary-medium);
    background-color: var(--primary-light);
    padding: 8px;
    border-radius: 8px;
}

.wall-chrg-ai-wallet-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin: 0;
}

.wall-chrg-ai-wallet-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.wall-chrg-ai-wallet-card {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    border: 1px solid var(--border-color);
}

/* اصلاح رنگ‌بندی کارت موجودی */
.wall-chrg-ai-balance-card {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #2c3e50 0%, #4a6580 100%);
    border-radius: 10px;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
}

.wall-chrg-ai-balance-icon {
    display: flex;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
}

.wall-chrg-ai-balance-info {
    display: flex;
    flex-direction: column;
}

.wall-chrg-ai-balance-label {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.wall-chrg-ai-balance-amount {
    font-size: 1.75rem;
    font-weight: 700;
    color: #ffffff;
    direction: rtl;
}

.wall-chrg-currency {
    font-size: 1.75rem;
    font-weight: 500;
    color: #ffffff;
}

.wall-chrg-ai-form-section {
    margin-bottom: 2rem;
}

.wall-chrg-ai-form-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--primary-light);
}

.wall-chrg-ai-amount-presets {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.wall-chrg-ai-preset-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    flex-direction: column;
    
}

.wall-chrg-ai-amount-radio {
    display: none;
}

.wall-chrg-ai-amount-preset {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    min-width: 140px;
    padding: 1rem 1.25rem;
    background: var(--primary-light);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.wall-chrg-ai-amount-preset:hover {
    border-color: var(--primary-medium);
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 101, 92, 0.15);
}

.wall-chrg-ai-amount-radio:checked + .wall-chrg-ai-amount-preset {
    border-color: var(--primary-medium);
    background-color: var(--primary-light);
    color: var(--primary-dark);
    box-shadow: 0 0 0 3px rgba(0, 133, 122, 0.3);
}

.wall-chrg-ai-custom-preset {
    background: var(--primary-light);
    border: 2px dashed var(--primary-medium);
}

.wall-chrg-ai-custom-preset:hover {
    background: rgba(244, 192, 23, 0.1);
    border: 2px dashed var(--primary-accent);
}

.wall-chrg-ai-amount-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-dark);
}

.wall-chrg-ai-amount-currency {
    font-size: 0.8rem;
    opacity: 0.9;
    color: var(--primary-medium);
}

.wall-chrg-ai-custom-amount-section {
    display: none;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

#amount_custom:checked ~ .wall-chrg-ai-custom-amount-section {
    display: block;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.wall-chrg-ai-form-label {
    display: block;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.75rem;
    font-size: 1rem;
}

.wall-chrg-input-container {
    position: relative;
    display:flex;
    margin-bottom: 1rem;
}

.wall-chrg-ai-form-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: var(--primary-light);
    color: var(--text-color);
}

.wall-chrg-ai-form-input:focus {
    outline: none;
    border-color: var(--primary-medium);
    box-shadow: 0 0 0 3px rgba(0, 133, 122, 0.3);
    background: linear-gradient(135deg, #e6f7f5 0%, #ffffff 100%);
}

.wall-chrg-ai-form-input:not(:placeholder-shown) {
    background: linear-gradient(135deg, #e6f7f5 0%, #ffffff 100%);
    border-color: var(--primary-medium);
}

/* اصلاح نکات مهم */
.wall-chrg-ai-important-notes {
    background-color: rgba(244, 192, 23, 0.1);
    border: 1px solid var(--primary-accent);
    border-radius: 10px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.wall-chrg-ai-important-notes h4 {
    margin: 0 0 1rem 0;
    color: var(--primary-dark);
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.wall-chrg-ai-important-notes h4:before {
    content: "⚠️";
}

.wall-chrg-ai-important-notes ul {
    margin: 0;
    padding-right: 1.5rem;
    color: var(--primary-dark);
    text-align: justify; /* افزودن justify */
    line-height: 1.8; /* افزایش فاصله خطوط برای خوانایی بهتر */
}

.wall-chrg-ai-important-notes li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
    position: relative;
}

.wall-chrg-ai-important-notes li:before {
    content: "•";
    color: var(--primary-accent);
    font-weight: bold;
    display: inline-block;
    width: 1em;
    margin-right: -1em;
    margin-left: 0.5em;
}

.wall-chrg-ai-form-actions {
    margin-top: 2rem;
}

.wall-chrg-ai-payment-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1.25rem 2rem;
    background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    /*font-weight: 700;*/
    font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 101, 92, 0.3);
}

.wall-chrg-ai-payment-button:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00544d 100%);
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 101, 92, 0.4);
}

.wall-chrg-ai-payment-button:active {
    transform: translateY(-1px);
}

.wall-chrg-ai-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    margin: 0 auto 2rem auto;
    border-radius: 10px;
    max-width: 500px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: slideInDown 0.5s ease;
    border: 1px solid transparent;
}

.wall-chrg-ai-alert-success {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-color: #bbf7d0;
    color: #166534;
}

.wall-chrg-ai-alert-error {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-color: #fecaca;
    color: #dc2626;
}

.wall-chrg-ai-alert-warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-color: #fde68a;
    color: #d97706;
}

.wall-chrg-ai-alert-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.wall-chrg-ai-alert-message {
    font-weight: 500;
    line-height: 1.5;
}

.wall-chrg-ai-alert-message span {
    font-family: 'Vazir', monospace;
    background: rgba(0, 0, 0, 0.1);
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 700;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* رسپانسیو برای موبایل */
@media (max-width: 768px) {
    .wall-chrg-ai-alert {
        margin: 1.5rem 1rem 1rem 1rem;
        padding: 0.75rem 1rem;
    }
    
    .wall-chrg-ai-alert-icon {
        font-size: 1.1rem;
    }
    
    .wall-chrg-ai-alert-message {
        font-size: 0.9rem;
    }
}
/* رسپانسیو برای موبایل */
@media (max-width: 768px) {
    .wall-chrg-ai-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .wall-chrg-ai-header-title {
        margin-right: 0;
        width: 100%;
        justify-content: center;
    }
    
    .wall-chrg-ai-amount-preset {
        min-width: auto;
    }
    
    .wall-chrg-ai-wallet-card {
        padding: 1.5rem;
    }
    
    .wall-chrg-ai-balance-card {
        flex-direction: column;
        text-align: center;
        padding: 1.25rem;
    }
    
    .wall-chrg-ai-payment-button {
        padding: 1rem 1.5rem;
        font-size: 1rem;
    }
    
    /* اصلاح نکات مهم برای موبایل */
    .wall-chrg-ai-important-notes ul {
        padding-right: 1rem;
    }
    
    .wall-chrg-ai-important-notes li:before {
        margin-left: 0.3em;
    }
}

@media (max-width: 500px) {
    .wall-chrg-ai-wallet-charge-page {
        padding: 0 1.2rem;
    }
}

.input-currency-hint {
    position: absolute;
    left: 3rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    font-size: 0.9rem;
    pointer-events: none;
}

.wall-chrg-ai-form-input {
    width: 100%;
    padding: 1rem 4rem 1rem 3rem; /* فضای بیشتر برای سمت راست */
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: var(--primary-light);
    color: var(--text-color);
    direction: ltr; /* برای نمایش صحیح اعداد */
    text-align: right;
}

/* وقتی input فوکوس شده یا پر است */
.wall-chrg-ai-form-input:focus,
.wall-chrg-ai-form-input:not(:placeholder-shown) {
    padding-right: 1rem;
    padding-left: 4rem;
}

.wall-chrg-ai-form-input:focus + .input-currency-hint,
.wall-chrg-ai-form-input:not(:placeholder-shown) + .input-currency-hint {
    opacity: 1;
}


.wall-chrg-ai-gateways {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.wall-chrg-ai-gateway-card {
    position: relative;
    display: block;
    background: var(--card-bg);
    border-radius: 10px;
    padding: 1rem 1.25rem;
    border: 2px solid var(--border-color);
    cursor: pointer;
    transition: all 0.25s ease;
}

.wall-chrg-ai-gateway-radio {
    display: none;
}

.wall-chrg-ai-gateway-card:hover {
    border-color: var(--primary-medium);
    box-shadow: 0 4px 12px rgba(0, 101, 92, 0.15);
    transform: translateY(-2px);
}

.wall-chrg-ai-gateway-radio:checked + .wall-chrg-ai-gateway-content {
    border-radius: 8px;
    box-shadow: 0 0 0 2px rgba(0, 133, 122, 0.25);
    background: linear-gradient(135deg, #e6f7f5 0, #ffffff 100%);
}

.wall-chrg-ai-gateway-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: .5rem;
}

.wall-chrg-ai-gateway-name {
    font-weight: 700;
    color: var(--primary-dark);
}

.wall-chrg-ai-gateway-badge {
    font-size: 0.75rem;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: var(--primary-medium);
    color: #fff;
}

.wall-chrg-ai-gateway-desc {
    margin: 0;
    font-size: 0.85rem;
    color: var(--text-light);
    line-height: 1.6;
}

/* 1) خنثی کردن استایل انتخاب قبلی روی content */
.wall-chrg-ai-gateway-radio:checked + .wall-chrg-ai-gateway-content {
    box-shadow: none;
    background: transparent;
}

/* 2) استایل حالت انتخاب‌شده روی خود کارت */
.wall-chrg-ai-gateway-radio:checked + .wall-chrg-ai-gateway-content .wall-chrg-ai-gateway-card {
    border-color: var(--primary-medium);
    box-shadow: 0 0 0 2px rgba(0, 133, 122, 0.35);
    background: linear-gradient(135deg, #f0faf9 0, #ffffff 100%);
    transform: translateY(-2px);
}

/* 3) کمی لطیف‌تر کردن کارت‌ها (بدون بزرگ شدن زیاد) */
.wall-chrg-ai-gateway-card {
    padding: 0.85rem 1rem;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

/* 4) هاور کارت‌ها کمی نرم‌تر */
.wall-chrg-ai-gateway-card:hover {
    border-color: var(--primary-medium);
    box-shadow: 0 4px 12px rgba(0, 101, 92, 0.12);
    transform: translateY(-1px);
}

.wall-chrg-ai-gateway-card:has(input[name="gateway"]:checked) {
    box-shadow: 0 4px 16px rgba(0, 101, 92, 0.2);
    border: solid 1px;
    border-color: var(--primary-medium);
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // مدیریت انتخاب مبلغ از پیش تعیین شده
    const presetAmounts = document.querySelectorAll('input[name="preset_amount"]');
    const customAmountSection = document.getElementById('custom_amount_section');
    const customAmountInput = document.getElementById('custom_amount');
    const chargeAmountInput = document.getElementById('charge_amount');
    
    // جلوگیری از تغییر مقدار فیلد با اسکرول موس
    if (customAmountInput) {
        customAmountInput.addEventListener('wheel', function(e) {
            e.preventDefault();
            return false;
        });
    }      
    // بررسی اگر پارامتر needed_amount در URL وجود دارد
    const urlParams = new URLSearchParams(window.location.search);
    const neededAmount = urlParams.get('needed_amount');
    
    if (neededAmount && neededAmount > 0) {
        // انتخاب گزینه مبلغ دلخواه
        document.getElementById('amount_custom').checked = true;
        customAmountSection.style.display = 'block';
        customAmountInput.value = neededAmount;
        chargeAmountInput.value = neededAmount;
        
        // اسکرول به بخش فرم
        setTimeout(() => {
            customAmountInput.focus();
            document.querySelector('.ai-charge-form').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 500);
    }
    
    presetAmounts.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                customAmountSection.style.display = 'block';
                customAmountInput.focus();
            } else {
                customAmountSection.style.display = 'none';
                customAmountInput.value = '';
                chargeAmountInput.value = this.value;
            }
        });
    });
    
    // مدیریت مبلغ دلخواه
    customAmountInput.addEventListener('input', function() {
        if (this.value) {
            document.getElementById('amount_custom').checked = true;
            chargeAmountInput.value = this.value;
        }
    });
    
    // تابع برای تبدیل اعداد انگلیسی به فارسی در JavaScript
    function toPersianNumber(number) {
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return number.toString().replace(/\d/g, function(digit) {
            return persianDigits[digit];
        });
    }

    // اعتبارسنجی فرم قبل از ارسال
    const form = document.querySelector('.ai-charge-form');
    form.addEventListener('submit', function(e) {
        const minAmount = <?php echo $minimum_charge; ?>;
        const maxAmount = <?php echo ai_wallet_get_maximum_charge(); ?>;
        const amount = parseInt(chargeAmountInput.value);
        
        if (!chargeAmountInput.value || amount < minAmount || amount > maxAmount) {
            e.preventDefault();
            alert('لطفاً مبلغی بین ' + toPersianNumber(minAmount) + ' تا ' + toPersianNumber(maxAmount) + ' تومان وارد کنید.');
            return false;
        }
    });
    
    // اعتبارسنجی لحظه‌ای برای input
    customAmountInput.addEventListener('input', function() {
        const maxAmount = <?php echo ai_wallet_get_maximum_charge(); ?>;
        if (this.value && parseInt(this.value) > maxAmount) {
            this.value = maxAmount;
            alert('حداکثر مبلغ مجاز ' + toPersianNumber(maxAmount) + ' تومان است.');
        }
    });
});
</script>

<?php
if ( isset($_POST['wallet_charge_submit']) && ! empty($_POST['charge_amount']) ) {
    $amount         = (int) $_POST['charge_amount'];
    $minimum_charge = ai_wallet_get_minimum_charge();
    $maximum_charge = ai_wallet_get_maximum_charge();

    if ( $amount < $minimum_charge || $amount > $maximum_charge ) {
        echo '<div class="ai-alert ai-alert-error">مبلغ وارد شده معتبر نیست.</div>';
    } else {
        $user_id        = get_current_user_id();
        $gatewaymanager = AI_Payment_Gateway_Manager::get_instance();

        // خواندن درگاه انتخاب‌شده (پیش‌فرض: zibal)
        $selected_gateway = isset($_POST['gateway']) ? sanitize_text_field($_POST['gateway']) : 'zibal';

        // ست‌کردن درگاه فعال بر اساس انتخاب کاربر
        $gatewaymanager->set_active_gateway( $selected_gateway );

        $paymentresult = $gatewaymanager->request_payment(
            $user_id,
            $amount,
            home_url('wallet-charge')
        );

        if ( $paymentresult && ! empty( $paymentresult['status'] ) && $paymentresult['status'] ) {

            if ( ! session_id() && ! headers_sent() ) {
                session_start();
            }
            $_SESSION['wallet_payment_amount']    = $amount;
            $_SESSION['wallet_payment_authority'] = $paymentresult['authority'];

            wp_redirect( $paymentresult['url'] );
            exit;

        } else {
            $reason = isset( $paymentresult['message'] )
                ? $paymentresult['message']
                : 'متأسفانه در اتصال به درگاه پرداخت مشکلی به وجود آمد. لطفاً چند دقیقه دیگر دوباره تلاش کنید.';

            wp_redirect(
                add_query_arg(
                    [
                        'payment' => 'failed',
                        'reason'  => urlencode( $reason ),
                    ],
                    home_url( 'wallet-charge' )
                )
            );
            exit;
        }
    }
}


get_footer();
?>