
<?php
// فعال‌سازی اولیه سرویس‌ها
function ai_assistant_activate_services() {
    
    $default_services = [
        'chat' => [ // تغییر service_id به 'chat' برای یکپارچگی
            'name' => 'چت هوشمند',
            'price' => 10000, // قیمت پیش‌فرض
            'description' => 'سرویس گفتگوی هوش مصنوعی',
            'system_prompt' => '',
            'icon' => 'dashicons-format-chat',
            'active' => true,
            'template' => get_template_directory() . '/services/chat/template-parts/form.php'
        ],
        'diet' => [
            'name' => 'رژیم غذایی',
            'price' => 15000,
            'description' => 'طراحی برنامه غذایی شخصی‌سازی شده',
            'system_prompt' => '',
            'icon' => 'dashicons-carrot',
            'active' => true,
            'template' => get_template_directory() . '/services/diet/template-parts/form.php'
        ],
        'workout' => [
            'name' => 'برنامه بدنسازی',
            'price' => 15000,
            'description' => 'طراحی برنامه تمرینی شخصی‌سازی شده',
            'system_prompt' => '',
            'icon' => 'dashicons-universal-access-alt',
            'active' => true,
            'template' => get_template_directory() . '/services/workout/template-parts/form.php'
        ]
    ];
    
    if (!get_option('ai_assistant_services')) {
        update_option('ai_assistant_services', $default_services);
    }
}

// ثبت هوک فعال‌سازی
register_activation_hook(__FILE__, 'ai_assistant_activate_services');