<?php
if (!defined('ABSPATH')) exit;

function ai_assistant_register_freediet_service() {
    $service_manager = AI_Assistant_Service_Manager::get_instance();

    if (!$service_manager->get_service('freediet')) {
        $service_manager->register_service([
            'id'          => 'freediet',
            'name'        => __('رژیم غذایی رایگان', 'ai-assistant'),
            'description' => __('فرم دو مرحله‌ای ساده برای رژیم غذایی رایگان', 'ai-assistant'),
            'price'       => 0,
            'icon'        => 'dashicons-carrot',
            'template'    => get_template_directory() . '/services/freediet/template-parts/form.php',
            'active'      => true,
        ]);
    }
}
add_action('after_setup_theme', 'ai_assistant_register_freediet_service');

function ai_assistant_enqueue_freediet_assets() {
    wp_enqueue_style(
        'ai-assistant-freediet',
        get_template_directory_uri() . '/assets/css/services/freediet.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'ai-assistant-freediet-steps',
        get_template_directory_uri() . '/assets/js/services/freediet/freediet-steps.js',
        [],
        '1.0.0',
        true
    );

    wp_enqueue_script(
        'ai-assistant-freediet',
        get_template_directory_uri() . '/assets/js/services/freediet/freediet.js',
        ['ai-assistant-freediet-steps'],
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'ai_assistant_enqueue_freediet_assets');