<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/templates/user-wallet-history.php-->
<?php
/**
 * Template Name: تاریخچه تراکنش‌های کیف پول
 */



if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}



get_header();

$current_user_id = get_current_user_id();
$wallet_history_manager = AI_Assistant_Wallet_History_Manager::get_instance();
$wallet_history = $wallet_history_manager->get_user_wallet_history($current_user_id, 10);


?>






<div class="ai-history-page">
    <div class="ai-history-header">
        <h1><span class="dashicons dashicons-money-alt"></span> تاریخچه تراکنش‌های کیف پول</h1>
        <div class="ai-history-actions">
            <a href="<?php echo esc_url(home_url('/ai-dashboard')); ?>" class="ai-back-button">
                <span class="dashicons dashicons-arrow-right-alt"></span> بازگشت به داشبورد
            </a>
        </div>
    </div>

    <div class="ai-history-container">
        <?php if (!empty($wallet_history)) : ?>
            <div class="ai-history-table-responsive">
                <table class="ai-history-table">
                    <thead>
                        <tr>
                            <th>نوع تراکنش</th>
                            <th>تاریخ</th>
                            <th>مبلغ (تومان)</th>
                            <th>موجودی </th>
                            <th>توضیحات</th>

                        </tr>
                    </thead>
                    
                    <tbody>
                        

                        <?php foreach ($wallet_history['items'] as $item) : 
                            $delete_url = add_query_arg([
                                'delete_wallet_history' => $item['id'],
                                '_wpnonce' => wp_create_nonce('delete_wallet_history_' . $item['id']),
                            ], get_permalink());
                        ?>
                            <tr data-history-id="<?php echo esc_attr($item['id']); ?>">
                                <td>
                                    <?php if ($item['type'] == 'credit') : ?>
                                        <span class="ai-status-badge ai-status-success">واریز</span>
                                    <?php else : ?>
                                        <span class="ai-status-badge ai-status-warning">برداشت</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date_i18n('j F Y - H:i', strtotime($item['date'])); ?></td>

                                <td>
                                    <?php if ($item['type'] == 'credit') : ?>
                                        <span style="color:green; vertical-align:middle;">
                                            <?php echo number_format($item['amount']); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="color:red; vertical-align:middle;">
                                            <?php echo number_format($item['amount']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>                                
                                
                                <td><?php echo number_format($item['balance']); ?></td>
                                <td><?php echo esc_html($item['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ai-history-pagination">
                <?php echo paginate_links([
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $wallet_history['pages'],
                    'prev_text' => __('&laquo; قبلی'),
                    'next_text' => __('بعدی &raquo;'),
                ]); ?>
            </div>
        <?php else : ?>
            <div class="ai-history-empty">
                <div class="ai-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <h3>تاریخچه کیف پول شما خالی است</h3>
                    <p>هنوز هیچ تراکنشی در کیف پول شما ثبت نشده است.</p>
                    <a href="<?php echo esc_url(home_url('/wallet-charge')); ?>" class="ai-button">
                        شارژ کیف پول
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php


get_footer();