
<?php
class AI_Assistant_Service_Manager {
    private static $instance;
    private $services = [];
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->services = get_option('ai_assistant_services', []);
    }    
    

    // افزودن/ویرایش سرویس
     public function update_service($service_id, $data) {
        if (!isset($this->services[$service_id])) {
            return false;
        }
        $current_service = $this->services[$service_id] ?? [];
        // حفظ مقادیر موجود برای فیلدهایی که در فرم نیستند
        $this->services[$service_id] = [
            'name' => sanitize_text_field($data['name']),
            'price' => absint($data['price']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
          //  'system_prompt' => sanitize_textarea_field($data['system_prompt'] ?? ''),
            'system_prompt' => $data['system_prompt'],
           // 'system_prompt' => wp_kses($data['system_prompt'] ?? '', $this->get_allowed_html_tags()),
            'icon' => sanitize_text_field($data['icon']),
            'active' => isset($data['active']) ? true : false ,
            
            'active' => isset($data['active']) ? (bool)$data['active'] : ($current_service['active'] ?? false),            
            'template' => $this->services[$service_id]['template'] ?? '' // حفظ مسیر قالب موجود
        ];
        
    
        
        return $this->save_services();
    }
    
  
    public function register_service($service_data) {
        $defaults = [
            'id' => '',
            'name' => '',
            'description' => '',
            'system_prompt' => '',
            'price' => 0,
            'icon' => 'dashicons-admin-generic',
            'template' => '',
            'active' => true
        ];
        
        $service = wp_parse_args($service_data, $defaults);
        
        if (empty($service['id'])) {
            return false;
        }
        
        $this->services[$service['id']] = $service;
        return true;
    }
    
    
    public function add_new_service() {
        $new_id = 'service_' . uniqid();
        $this->services[$new_id] = [
            'name' => 'سرویس جدید',
            'price' => 0,
            'description' => '',
            'system_prompt' => '',
            'icon' => 'dashicons-admin-generic',
            'active' => true,
            'template' => ''
        ];
        $this->save_services();
        return $new_id;
    }    
    
   
 
    // دریافت اطلاعات سرویس
    public function get_service($service_id) {
        return $this->services[$service_id] ?? false;
    }
    
    

    // دریافت همه سرویس‌های فعال
    
    public function get_active_services() {
        return array_filter($this->services, function($service) {
            return !empty($service['active']); // استفاده از !empty برای اطمینان از مقدار true
        });
    }   
    
    
    // دریافت همه سرویس‌ها (فعال و غیرفعال)
    public function get_all_services() {
        return $this->services; // مستقیماً آرایه سرویس‌ها را برگردانید
    }

/*
    // دریافت قیمت سرویس
    public function get_service_price($service_id) {
        return $this->services[$service_id]['price'] ?? 0;
    }
*/
    // دریافت قیمت سرویس
    public function get_service_price($service_id) {
        if (isset($this->services[$service_id]['price'])) {
            return (int)$this->services[$service_id]['price'];
        }
        return 0;
    }    
        
    
 //-----------------------------------------------------------------
 
 private function save_services() {
    update_option('ai_assistant_services', $this->services);
}

    
    
}