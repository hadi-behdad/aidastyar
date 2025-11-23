<?php
/**
 * Template Name: Referral Dashboard
 * داشبورد معرف
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/otp-login'));
    exit;
}

get_header();

$user_id = get_current_user_id();
$referral_system = AI_Assistant_Referral_System::get_instance();
$stats = $referral_system->get_referral_stats($user_id);
$referral_link = $referral_system->get_referral_link($user_id);
?>

<div class="referral-dashboard-container">
    <div class="container">
        <h1 class="page-title">داشبورد معرف</h1>
        
        <!-- لینک اشتراک‌گذاری -->
        <div class="referral-link-box">
            <h3>لینک معرف شما</h3>
            <div class="link-copy-wrapper">
                <input type="text" id="referral-link" value="<?php echo esc_url($referral_link); ?>" readonly>
                <button class="btn-copy" onclick="copyReferralLink()">
                    <i class="dashicons dashicons-admin-page"></i> کپی
                </button>
            </div>
            <p class="hint">این لینک را با دوستان خود به اشتراک بگذارید</p>
        </div>
        
        <!-- آمار کلی -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['total_referrals']); ?></div>
                <div class="stat-label">کل معرفی‌ها</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo number_format($stats['completed_purchases']); ?></div>
                <div class="stat-label">خریدهای تکمیل شده</div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo number_format($stats['pending_purchases']); ?></div>
                <div class="stat-label">در انتظار خرید</div>
            </div>
            
            <div class="stat-card primary">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?php echo number_format($stats['total_earned']); ?> تومان</div>
                <div class="stat-label">کل درآمد</div>
            </div>
        </div>
        
        <!-- جدول آخرین معرفی‌ها -->
        <div class="recent-referrals">
            <h3>آخرین معرفی‌ها</h3>
            <?php if (!empty($stats['recent_referrals'])): ?>
                <table class="referral-table">
                    <thead>
                        <tr>
                            <th>شماره موبایل</th>
                            <th>تاریخ ثبت‌نام</th>
                            <th>وضعیت</th>
                            <th>پاداش</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_referrals'] as $ref): ?>
                            <tr>
                                <td><?php echo esc_html($ref->referred_mobile); ?></td>
                                <td><?php echo jdate('Y/m/d H:i', strtotime($ref->created_at)); ?></td>
                                <td>
                                    <?php if ($ref->first_purchase_completed): ?>
                                        <span class="badge badge-success">خرید انجام شده</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">در انتظار خرید</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ref->reward_amount > 0): ?>
                                        <strong><?php echo number_format($ref->reward_amount); ?> تومان</strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">هنوز کسی را معرفی نکرده‌اید</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.referral-dashboard-container {
    padding: 40px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-title {
    font-size: 32px;
    margin-bottom: 30px;
    text-align: center;
}

.referral-link-box {
    background: var(--color-surface);
    padding: 25px;
    border-radius: var(--radius-lg);
    margin-bottom: 30px;
    box-shadow: var(--shadow-sm);
}

.referral-link-box h3 {
    margin-bottom: 15px;
    font-size: 20px;
}

.link-copy-wrapper {
    display: flex;
    gap: 10px;
}

.link-copy-wrapper input {
    flex: 1;
    padding: 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-base);
    font-family: monospace;
}

.btn-copy {
    padding: 12px 24px;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: var(--radius-base);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-copy:hover {
    background: var(--color-primary-hover);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--color-surface);
    padding: 25px;
    border-radius: var(--radius-lg);
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: var(--color-text);
    margin-bottom: 5px;
}

.stat-label {
    color: var(--color-text-secondary);
    font-size: 14px;
}

.recent-referrals {
    background: var(--color-surface);
    padding: 25px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

.recent-referrals h3 {
    margin-bottom: 20px;
    font-size: 20px;
}

.referral-table {
    width: 100%;
    border-collapse: collapse;
}

.referral-table th,
.referral-table td {
    padding: 12px;
    text-align: right;
    border-bottom: 1px solid var(--color-border);
}

.referral-table th {
    background: var(--color-secondary);
    font-weight: 600;
}

.badge {
    padding: 5px 12px;
    border-radius: var(--radius-full);
    font-size: 12px;
    font-weight: 500;
}

.badge-success {
    background: rgba(var(--color-success-rgb), 0.15);
    color: var(--color-success);
}

.badge-warning {
    background: rgba(var(--color-warning-rgb), 0.15);
    color: var(--color-warning);
}

.no-data {
    text-align: center;
    padding: 40px;
    color: var(--color-text-secondary);
}
</style>

<script>
function copyReferralLink() {
    const input = document.getElementById('referral-link');
    input.select();
    document.execCommand('copy');
    
    alert('لینک کپی شد!');
}
</script>

<?php get_footer(); ?>
