<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/templates/wallet-charge.php-->
<?php
/**
 * Template Name: شارژ کیف پول
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$wallet = AI_Assistant_Payment_Handler::get_instance();
$current_credit = $wallet->get_user_credit($user_id);
?>

<div class="ai-wallet-charge-page">
    <div class="ai-wallet-header">
        <h1><span class="dashicons dashicons-wallet"></span> شارژ کیف پول</h1>
        <a href="<?php echo esc_url(home_url('/ai-dashboard')); ?>" class="ai-back-button">
            <span class="dashicons dashicons-arrow-right-alt"></span> بازگشت به داشبورد
        </a>
    </div>

    <div class="ai-wallet-container">
        <div class="ai-wallet-balance">
            <div class="ai-balance-card">
                <span class="ai-balance-label">موجودی فعلی:</span>
                <span class="ai-balance-amount"><?php echo number_format($current_credit); ?> تومان</span>
            </div>
        </div>

        <div class="ai-wallet-form">
            <form method="POST" class="ai-charge-form">
                <div class="ai-form-group">
                    <label class="ai-form-label">مبلغ شارژ (تومان):</label>
                    <div class="ai-amount-presets">
                        <button type="button" class="ai-amount-preset" data-amount="10000">۱۰,۰۰۰ تومان</button>
                        <button type="button" class="ai-amount-preset" data-amount="20000">۲۰,۰۰۰ تومان</button>
                        <button type="button" class="ai-amount-preset" data-amount="50000">۵۰,۰۰۰ تومان</button>
                        <button type="button" class="ai-amount-preset" data-amount="100000">۱۰۰,۰۰۰ تومان</button>
                        <button type="button" class="ai-amount-preset" data-amount="200000">۲۰۰,۰۰۰ تومان</button>
                    </div>
                </div>

                <div class="ai-form-group">
                    <label for="custom_amount" class="ai-form-label">یا مبلغ دلخواه:</label>
                    <div class="ai-custom-amount">
                        <input type="number" 
                               id="custom_amount" 
                               name="custom_amount" 
                               min="1000" 
                               step="1000" 
                               placeholder="مبلغ به تومان"
                               class="ai-form-input" />
                        <span class="ai-currency">تومان</span>
                    </div>
                </div>

                <input type="hidden" name="charge_amount" id="charge_amount" value="" />

                <div class="ai-form-actions">
                    <button type="submit" name="wallet_charge_submit" class="ai-payment-button">
                        <span class="dashicons dashicons-money-alt"></span> پرداخت و شارژ کیف پول
                    </button>
                </div>
            </form>
        </div>

        <div class="ai-wallet-info">
            <div class="ai-info-card">
                <h3><span class="dashicons dashicons-info"></span> نکات مهم:</h3>
                <ul>
                    <li>حداقل مبلغ شارژ ۱,۰۰۰ تومان می‌باشد.</li>
                    <li>پس از پرداخت، مبلغ بلافاصله به کیف پول شما اضافه می‌شود.</li>
                    <li>در صورت بروز مشکل در پرداخت، با پشتیبانی تماس بگیرید.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// پردازش فرم شارژ
if (isset($_POST['wallet_charge_submit']) && !empty($_POST['charge_amount'])) {
    $amount = (int) $_POST['charge_amount'];

    if ($amount >= 1000) {
        $user_id = get_current_user_id();
        $wallet = AI_Assistant_Payment_Handler::get_instance();

        // ساخت شناسه منحصر به‌فرد
        $unique_id = 'wallet_' . $user_id . '_' . time();

        update_user_meta($user_id, 'wallet_charge_pending', [
            'id' => $unique_id,
            'amount' => $amount,
            'status' => 'pending'
        ]);

        // ساخت یا گرفتن محصول
        $product_id = $wallet->create_payment_product_for_wallet($unique_id, $amount);

        if ($product_id) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($product_id);
            wp_redirect(wc_get_checkout_url());
            exit;
        } else {
            echo '<div class="ai-alert ai-alert-error">خطا در ایجاد محصول پرداخت. لطفا مجددا تلاش کنید.</div>';
        }
    } else {
        echo '<div class="ai-alert ai-alert-error">مبلغ وارد شده معتبر نیست. حداقل مبلغ شارژ ۱,۰۰۰ تومان می‌باشد.</div>';
    }
}

get_footer();