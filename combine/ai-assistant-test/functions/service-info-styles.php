<?php
/**
 * بارگذاری استایل‌های اختصاصی صفحه Service Info
 */

function load_service_info_styles() {
    // تنها بر روی صفحه service-info بارگذاری شود
    if (get_query_var('service_info_page')) {
        wp_enqueue_style(
            'service-info-optimized',
            get_stylesheet_directory_uri() . '/assets/css/services/service-info-optimized.css',
            array(),
            '1.0.0',
            'all'
        );
    }
}
add_action('wp_enqueue_scripts', 'load_service_info_styles');
