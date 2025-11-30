<?php
/**
 * Terms Manager
 * مدیریت نسخه‌های مختلف شرایط استفاده
 */

class Terms_Manager {
    
    private $terms_dir;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->terms_dir = $upload_dir['basedir'] . '/terms-versions';
        
        // ایجاد دایرکتوری
        if (!file_exists($this->terms_dir)) {
            wp_mkdir_p($this->terms_dir);
            file_put_contents($this->terms_dir . '/.htaccess', 'Options -Indexes');
            file_put_contents($this->terms_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * دریافت محتوای شرایط فعلی
     */
    public function get_current_terms_content() {
        $version = get_option('aidastyar_terms_version', 'v1.0');
        return $this->get_terms_by_version($version);
    }
    
    /**
     * دریافت محتوای شرایط براساس نسخه
     */
    public function get_terms_by_version($version) {
        $filepath = $this->terms_dir . '/' . $version . '.html';
        
        if (file_exists($filepath)) {
            return file_get_contents($filepath);
        }
        
        // اگر فایل نسخه وجود نداشت، از محتوای پیش‌فرض استفاده کن
        return $this->get_default_terms_content();
    }
    
    /**
     * ذخیره نسخه جدید شرایط
     */
    public function save_new_version($version, $content) {
        $filepath = $this->terms_dir . '/' . $version . '.html';
        $result = file_put_contents($filepath, $content);
        
        if ($result) {
            // به‌روزرسانی نسخه فعلی
            update_option('aidastyar_terms_version', $version);
            
            // ثبت لاگ تغییر
            $this->log_version_change($version);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * دریافت لیست تمام نسخه‌ها
     */
    public function get_all_versions() {
        $files = glob($this->terms_dir . '/*.html');
        $versions = [];
        
        foreach ($files as $file) {
            $version = basename($file, '.html');
            $versions[] = [
                'version' => $version,
                'file' => $file,
                'created' => filectime($file),
                'size' => filesize($file)
            ];
        }
        
        usort($versions, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $versions;
    }
    
    /**
     * ثبت لاگ تغییر نسخه
     */
    private function log_version_change($version) {
        $log_file = $this->terms_dir . '/version-log.txt';
        $log_entry = sprintf(
            "[%s] نسخه %s فعال شد\n",
            current_time('Y-m-d H:i:s'),
            $version
        );
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * محتوای پیش‌فرض شرایط
     */
    private function get_default_terms_content() {
        // محتوای شرایطی که قبلاً آماده کردیم
        ob_start();
        include get_template_directory() . '/inc/default-terms-content.php';
        return ob_get_clean();
    }
}
