<?php
/**
 * Payment Gateway Interface
 * 
 * تعریف ساختار استاندارد برای تمام درگاه‌های پرداخت
 * هر درگاه جدید باید این Interface را implement کند
 * 
 * @package AI_Assistant
 * @subpackage Payment_Gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface Payment Gateway
 * 
 * تعریف متدهای الزامی برای هر درگاه پرداخت
 */
interface AI_Payment_Gateway_Interface {

    /**
     * ارسال درخواست پرداخت به درگاه
     * 
     * @param int    $user_id    شناسه کاربر
     * @param float  $amount     مبلغ پرداخت (به تومان)
     * @param string $return_url URL برای بازگشت بعد از پرداخت
     * @param array  $extra_data اطلاعات اضافی (مثل شناسه سفارش)
     * 
     * @return array {
     *     @type bool   $status  true اگر درخواست موفق، false در غیر این
     *     @type string $url     URL درگاه پرداخت (برای ریدایرکت)
     *     @type string $message پیام خطا (در صورت شکست)
     *     @type string $authority شناسه یکتای تراکنش (مثل authority در ZarinPal)
     * }
     */
    public function request_payment($user_id, $amount, $return_url, $extra_data = array());

    /**
     * تأیید پرداخت
     * 
     * @param string $authority شناسه تراکنش
     * @param float  $amount    مبلغ (برای بررسی مجدد)
     * 
     * @return array {
     *     @type bool   $status      true اگر پرداخت موفق، false در غیر این
     *     @type string $ref_id      شناسه پیگیری نهایی (Ref ID)
     *     @type string $message     پیام خطا (در صورت شکست)
     *     @type string $gateway_id  شناسه درگاه
     * }
     */
    public function verify_payment($authority, $amount);

    /**
     * دریافت شناسه منحصربه‌فرد درگاه
     * 
     * @return string شناسه درگاه (مثل 'zarinpal', 'stripe', 'idpay')
     */
    public function get_gateway_id();

    /**
     * دریافت نام درگاه به طور انسان‌خوانا
     * 
     * @return string نام درگاه (مثل 'درگاه زرین‌پال')
     */
    public function get_gateway_name();

    /**
     * بررسی فعال‌بودن درگاه
     * 
     * @return bool true اگر درگاه فعال است
     */
    public function is_enabled();
}