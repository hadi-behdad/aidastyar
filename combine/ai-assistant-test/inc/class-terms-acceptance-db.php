<?php
/**
 * Terms Acceptance Database Handler
 * مدیریت ذخیره‌سازی و بازیابی تأییدیه‌های شرایط استفاده
 */

class Terms_Acceptance_DB {
    
    private static $instance;
    private $table_name;
    private $wpdb;
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - ایجاد خودکار جدول
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'terms_acceptance';
        
        // ایجاد جدول در صورت عدم وجود
        $this->maybe_create_tables();
    }
    
    /**
     * ایجاد جدول در دیتابیس (اگر وجود نداشته باشد)
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        // بررسی اینکه آیا جدول وجود دارد
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                service_id varchar(50) DEFAULT NULL,
                service_history_id BIGINT(20) DEFAULT NULL, 
                terms_version varchar(20) NOT NULL,
                terms_hash varchar(64) NOT NULL,
                accepted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                user_ip varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                terms_content_snapshot longtext DEFAULT NULL,
                archive_file_path varchar(255) DEFAULT NULL,
                
                PRIMARY KEY (id),
                INDEX idx_user_id (user_id),
                INDEX idx_service_id (service_id),
                INDEX idx_terms_version (terms_version),
                INDEX idx_accepted_at (accepted_at)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('✅ جدول terms_acceptance با موفقیت ایجاد شد');
        }
    }
    
    /**
     * دریافت نام جدول
     */
    public function get_table_name() {
        return $this->table_name;
    }
    
    /**
     * ذخیره تأییدیه جدید
     */
    public function save_acceptance($user_id, $terms_content, $service_id = 'diet', $service_history_id = null) {
        
        // محاسبه hash از محتوا
        $terms_hash = hash('sha256', $terms_content);
        
        // دریافت نسخه فعلی
        $terms_version = $this->get_current_terms_version();
        
        // دریافت IP و User Agent
        $user_ip = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $data = [
            'user_id' => intval($user_id),
            'service_id' => $service_id,
            'service_history_id' => intval($service_history_id),  
            'terms_version' => $terms_version,
            'terms_hash' => $terms_hash,
            'accepted_at' => current_time('mysql'),
            'user_ip' => $user_ip,
            'user_agent' => $user_agent,
            'terms_content_snapshot' => $terms_content
        ];
        
        $result = $this->wpdb->insert(
            $this->table_name,
            $data,
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result) {
            $acceptance_id = $this->wpdb->insert_id;
            
            // تولید HTML آرشیو
            $html_path = $this->generate_html_archive($acceptance_id, $user_id, $terms_content);
            
            if ($html_path) {
                $this->wpdb->update(
                    $this->table_name,
                    ['archive_file_path' => $html_path],
                    ['id' => $acceptance_id],
                    ['%s'],
                    ['%d']
                );
            }
            
            error_log("✅ تأییدیه #{$acceptance_id} برای کاربر #{$user_id} ذخیره شد");
            
            return $acceptance_id;
        }
        
        error_log('❌ خطا در ذخیره تأییدیه: ' . $this->wpdb->last_error);
        return false;
    }
    
    /**
     * دریافت تأییدیه‌های یک کاربر
     */
    public function get_user_acceptances($user_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY accepted_at DESC",
            $user_id
        );
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * دریافت آخرین تأییدیه یک کاربر
     */
    public function get_latest_acceptance($user_id, $service_id = 'diet') {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND service_id = %s ORDER BY accepted_at DESC LIMIT 1",
            $user_id,
            $service_id
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * دریافت تأییدیه با ID
     */
    public function get_acceptance_by_id($acceptance_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $acceptance_id
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * ✅ دریافت تأییدیه براساس service_history_id
     */
    public function get_acceptance_by_service_history_id($service_history_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE service_history_id = %d LIMIT 1",
            intval($service_history_id)
        );
        
        return $this->wpdb->get_row($sql);
    }    
    
    /**
     * بررسی اینکه آیا کاربر تأییدیه فعلی را پذیرفته
     */
    public function has_accepted_current_version($user_id, $service_id = 'diet') {
        $current_version = $this->get_current_terms_version();
        
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND service_id = %s AND terms_version = %s",
            $user_id,
            $service_id,
            $current_version
        );
        
        return $this->wpdb->get_var($sql) > 0;
    }
    
    /**
     * دریافت نسخه فعلی شرایط
     */
    private function get_current_terms_version() {
        return get_option('aidastyar_terms_version', 'v1.0');
    }
    
    /**
     * دریافت IP واقعی کاربر
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                    'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
    }
    
    /**
     * تولید HTML آرشیو از تأییدیه
     */
    private function generate_html_archive($acceptance_id, $user_id, $terms_content) {
        $upload_dir = wp_upload_dir();
        $terms_dir = $upload_dir['basedir'] . '/terms-archives';
        
        // ایجاد پوشه در صورت عدم وجود
        if (!file_exists($terms_dir)) {
            wp_mkdir_p($terms_dir);
            // محافظت از دایرکتوری
            file_put_contents($terms_dir . '/.htaccess', 'Options -Indexes');
            file_put_contents($terms_dir . '/index.php', '<?php // Silence is golden');
        }
        
        $filename = sprintf('terms_%d_%d_%s.html', $user_id, $acceptance_id, time());
        $filepath = $terms_dir . '/' . $filename;
        
        // آماده‌سازی محتوای HTML کامل
        $user_data = get_userdata($user_id);
        $acceptance_date = current_time('Y-m-d H:i:s');
        $hash = hash('sha256', $terms_content);
        
        $html = $this->prepare_archive_html($terms_content, [
            'user_id' => $user_id,
            'user_name' => $user_data->display_name,
            'acceptance_id' => $acceptance_id,
            'acceptance_date' => $acceptance_date,
            'hash' => $hash,
            'ip' => $this->get_client_ip()
        ]);
        
        file_put_contents($filepath, $html);
        
        return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $filepath);
    }
    
    /**
     * آماده‌سازی HTML برای آرشیو با استایل حرفه‌ای
     */
    private function prepare_archive_html($terms_content, $metadata) {
        $user_id = isset($metadata['user_id']) ? $metadata['user_id'] : 'N/A';
        $user_name = isset($metadata['user_name']) ? $metadata['user_name'] : 'N/A';
        $acceptance_id = isset($metadata['acceptance_id']) ? $metadata['acceptance_id'] : 'N/A';
        $acceptance_date = isset($metadata['acceptance_date']) ? $metadata['acceptance_date'] : 'N/A';
        $ip = isset($metadata['ip']) ? $metadata['ip'] : 'N/A';
        $hash = isset($metadata['hash']) ? $metadata['hash'] : 'N/A';
        
        ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>تأییدیه شرایط استفاده - Aidastyar</title>
        <style>
            :root {
                --primary-color: #00857a;
                --secondary-color: #00665c;
                --background-color: #f5f5f5;
                --text-color: #333;
                --light-text-color: #7e7c7c;
                --border-color: #e0e0e0;
                --success-bg: #e8f5e9;
                --success-border: #4caf50;
                --warning-bg: #fff3e0;
                --warning-border: #ff9800;
            }
    
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: Vazir, Tahoma, sans-serif;
                direction: rtl;
                line-height: 1.8;
                color: var(--text-color);
                background: var(--background-color);
                padding: 20px;
            }
            
            .container {
                max-width: 900px;
                margin: 0 auto;
                background: #fff;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }
            
            .header {
                background: linear-gradient(135deg, var(--primary-color) 0%, #00c9b7 100%);
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
            }
            
            .header h1 {
                font-size: 26px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            
            .header p {
                font-size: 14px;
                opacity: 0.95;
            }
            
            .metadata {
                background: var(--success-bg);
                border: 2px solid var(--success-border);
                padding: 25px 20px;
                margin: 10px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
            }
            
            .metadata h3 {
                color: #2e7d32;
                font-size: 20px;
                margin-bottom: 20px;
                border-bottom: 2px solid #c8e6c9;
                padding-bottom: 10px;
            }
            
            .metadata-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .metadata-item {
                background: rgba(255, 255, 255, 0.8);
                padding: 12px 15px;
                border-radius: 8px;
                border-right: 4px solid var(--success-border);
            }
            
            .metadata-item strong {
                display: block;
                color: #2e7d32;
                font-size: 13px;
                margin-bottom: 5px;
            }
            
            .metadata-item span {
                color: var(--text-color);
                font-size: 14px;
                font-weight: 600;
            }
            
            .hash-section {
                background: #fff;
                padding: 15px;
                border-radius: 10px;
                border: 2px dashed var(--success-border);
            }
            
            .hash-section strong {
                display: block;
                color: #2e7d32;
                font-size: 13px;
                margin-bottom: 10px;
            }
            
            .hash {
                font-family: 'Courier New', monospace;
                font-size: 11px;
                word-break: break-all;
                background: #f5f5f5;
                padding: 12px;
                border-radius: 8px;
                color: #666;
                border: 1px solid var(--border-color);
                line-height: 1.6;
            }
            
            .terms-content {
                padding: 40px 30px;
            }
            
            .terms-section {
                margin-bottom: 30px;
            }
            
            .terms-section h2 {
                color: var(--primary-color);
                font-size: 20px;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #b2ebf2;
            }
            
            .terms-section h3 {
                color: var(--primary-color);
                font-size: 16px;
                margin: 15px 0 10px;
                font-weight: 600;
            }
            
            .terms-section p {
                text-align: justify;
                margin-bottom: 12px;
                line-height: 1.9;
                color: #555;
            }
            
            .terms-section ul {
                padding-right: 25px;
                margin: 15px 0;
            }
            
            .terms-section li {
                margin-bottom: 10px;
                line-height: 1.8;
                color: #666;
            }
            
            .disclaimer-box {
                background: var(--warning-bg);
                border: 3px solid var(--warning-border);
                border-radius: 12px;
                padding: 25px;
                margin: 30px 0;
                box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
                margin-left: auto;
                margin-right: auto;
                max-width: 800px;
            }
            .disclaimer-box strong {
                display: block;
                color: #e65100;
                font-size: 18px;
                margin: 0 0 15px 0;
                text-align: center;
            }
            .disclaimer-box p {
                margin: 0;
                color: #d84315;
                text-align: justify;
                line-height: 1.8;
            }

            
            .disclaimer-box ul {
                padding-right: 25px;
                margin-top: 15px;
            }
            
            .footer {
                background: #f8f9fa;
                padding: 30px;
                text-align: center;
                border-top: 3px solid var(--primary-color);
            }
            
            .footer strong {
                display: block;
                color: var(--primary-color);
                font-size: 16px;
                margin-bottom: 10px;
            }
            
            .footer p {
                color: #666;
                font-size: 13px;
                margin: 5px 0;
            }
            
            @media print {
                body {
                    background: white;
                    padding: 0;
                }
                .container {
                    box-shadow: none;
                    border-radius: 0;
                }
            }
            
            @media (max-width: 768px) {
                .metadata-grid {
                    grid-template-columns: 1fr;
                }
                .header h1 {
                    font-size: 22px;
                }
                .terms-content {
                    padding: 20px 15px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ تأییدیه شرایط و قوانین استفاده از Aidastyar</h1>
                <p>این سند، نسخه آرشیو شده و غیرقابل تغییر از تأییدیه کاربر است</p>
            </div>
            
            <div class="metadata">
                <h3>🔒 اطلاعات تأییدیه</h3>
                
                <div class="metadata-grid">
                    <div class="metadata-item">
                        <strong>👤 شناسه کاربر:</strong>
                        <span><?php echo htmlspecialchars($user_id); ?></span>
                    </div>
                    
                    <div class="metadata-item">
                        <strong>📝 نام کاربر:</strong>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                    
                    <div class="metadata-item">
                        <strong>🔑 شناسه تأییدیه:</strong>
                        <span><?php echo htmlspecialchars($acceptance_id); ?></span>
                    </div>
                    
                    <div class="metadata-item">
                        <strong>📅 تاریخ و ساعت تأیید:</strong>
                        <span><?php echo htmlspecialchars($acceptance_date); ?></span>
                    </div>
                    
                    <div class="metadata-item">
                        <strong>🌐 آدرس IP:</strong>
                        <span><?php echo htmlspecialchars($ip); ?></span>
                    </div>
                </div>
                
                <div class="hash-section">
                    <strong>🔐 امضای دیجیتال (SHA-256):</strong>
                    <div class="hash"><?php echo htmlspecialchars($hash); ?></div>
                </div>
            </div>
            
            <div class="terms-content">
                <?php echo $terms_content; ?>
            </div>
            
            <div class="footer">
                <strong>⚠️ این سند یک نسخه آرشیو شده و غیرقابل تغییر است</strong>
                <p>تولید شده توسط سامانه Aidastyar</p>
                <p>این فایل دارای اعتبار قانونی بوده و هرگونه تغییر در آن غیرمجاز است</p>
                <p style="margin-top: 15px; color: var(--primary-color); font-weight: 600;">
                    <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
        return ob_get_clean();
    }
    
    public function saveAcceptanceInTransaction($user_id, $terms_content, $service_id = 'diet', $service_history_id = null) {
        
        error_log('TermsAcceptanceDB::saveAcceptanceInTransaction - User: ' . $user_id . ', History: ' . ($service_history_id ?? 'NULL'));
        
        if (empty($terms_content)) {
            error_log('Terms content is EMPTY');
            throw new Exception('Terms content is empty');
        }
        
        error_log('Terms content length: ' . strlen($terms_content));
        
        $terms_hash = hash('sha256', $terms_content);
        $terms_version = $this->get_current_terms_version();
        $user_ip = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // ✅ اطمینان دارید service_history_id موجود است
        $data = [
            'user_id' => intval($user_id),
            'service_id' => $service_id,
            'service_history_id' => intval($service_history_id),  // ✅ مهم
            'terms_version' => $terms_version,
            'terms_hash' => $terms_hash,
            'accepted_at' => current_time('mysql'),
            'user_ip' => $user_ip,
            'user_agent' => $user_agent,
            'terms_content_snapshot' => $terms_content
        ];
        
        $result = $this->wpdb->insert(
            $this->table_name,
            $data,
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']  // ✅ 3 %d
        );
        
        if ($result) {
            $acceptance_id = $this->wpdb->insert_id;
            
            // ایجاد archive
            $html_path = $this->generate_html_archive($acceptance_id, $user_id, $terms_content);
            
            if ($html_path) {
                $this->wpdb->update(
                    $this->table_name,
                    ['archive_file_path' => $html_path],
                    ['id' => $acceptance_id],
                    ['%s'],
                    ['%d']
                );
            }
            
            error_log("✅ تأییدیه #$acceptance_id برای کاربر #$user_id با history_id #$service_history_id ذخیره شد");
            
            return $acceptance_id;
        }
        
        error_log('❌ خطا در ذخیره تأییدیه: ' . $this->wpdb->last_error);
        return false;
    }
    
    /**
     * Create HTML archive of accepted terms
     * برای اثبات قانونی
     * 
     * @param int $userid
     * @param string $terms_content
     * @param string $terms_version
     * @return string|null - مسیر فایل یا null
     */
    private function createTermsArchive( $userid, $terms_content, $terms_version ) {
        
        try {
            // دایرکتوری ذخیره‌سازی
            $upload_dir = wp_upload_dir();
            $archive_dir = $upload_dir['basedir'] . '/terms-archives';
            
            // ایجاد دایرکتوری
            if ( ! file_exists( $archive_dir ) ) {
                wp_mkdir_p( $archive_dir );
                
                // محافظت
                file_put_contents( $archive_dir . '/.htaccess', 'Options -Indexes' );
                file_put_contents( $archive_dir . '/index.php', '<?php // Silence is golden' );
            }
            
            // نام فایل: user_id_timestamp_version.html
            $filename = sprintf(
                'terms_%d_%s_%s.html',
                $userid,
                date( 'YmdHis' ),
                sanitize_file_name( $terms_version )
            );
            
            $filepath = $archive_dir . '/' . $filename;
            $relative_path = $upload_dir['baseurl'] . '/terms-archives/' . $filename;
            
            // محتوای HTML
            $html_content = $this->generateTermsHtml( $terms_content, $userid, $terms_version );
            
            // نوشتن فایل
            $result = file_put_contents( $filepath, $html_content );
            
            if ( $result !== false ) {
                error_log( "✅ Archive created: $filepath" );
                // بازگرداندن مسیر نسبی (برای بازیابی آسان‌تر)
                return $relative_path;
            } else {
                error_log( "❌ Failed to create archive at: $filepath" );
                return null;
            }
            
        } catch ( Exception $e ) {
            error_log( "❌ Archive creation error: " . $e->getMessage() );
            return null;
        }
    }    
    

/**
 * Generate HTML for terms archive with Aidastyar styling
 * 
 * @param string $termscontent
 * @param int $userid
 * @param string $termsversion
 * @return string
 */
private function generateTermsHtml($termscontent, $userid, $termsversion) {
    $timestamp = current_time('mysql');
    $userip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'Unknown';
    $termshash = hash('sha256', $termscontent);
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند پذیرش شرایط - AiDASTYAR</title>
    <style>
        :root {
            --primary-color: #00857a;
            --secondary-color: #00665c;
            --background-color: #f5f5f5;
            --text-color: #333;
            --light-text-color: #7e7c7c;
            --border-color: #e0e0e0;
            --success-bg: #e8f5e9;
            --success-border: #4caf50;
            --warning-bg: #fff3e0;
            --warning-border: #ff9800;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Vazir', Tahoma, sans-serif;
            direction: rtl;
            line-height: 1.8;
            color: var(--text-color);
            background: var(--background-color);
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #00c9b7 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.95;
        }
        .metadata {
            background: var(--success-bg);
            border: 2px solid var(--success-border);
            border-radius: 12px;
            padding: 25px 20px;
            margin: 10px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }
        .metadata h3 {
            color: #2e7d32;
            font-size: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #c8e6c9;
            padding-bottom: 10px;
        }
        .metadata-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .metadata-item {
            background: rgba(255, 255, 255, 0.8);
            padding: 12px 15px;
            border-radius: 8px;
            border-right: 4px solid var(--success-border);
        }
        .metadata-item strong {
            display: block;
            color: #2e7d32;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .metadata-item span {
            color: var(--text-color);
            font-size: 14px;
            font-weight: 600;
        }
        .hash-section {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            border: 2px dashed var(--success-border);
        }
        .hash-section strong {
            display: block;
            color: #2e7d32;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .hash {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            word-break: break-all;
            background: #f5f5f5;
            padding: 12px;
            border-radius: 8px;
            color: #666;
            border: 1px solid var(--border-color);
            line-height: 1.4;
        }
        .terms-content {
            padding: 40px 30px;
        }
        .terms-section {
            margin-bottom: 30px;
        }
        .terms-section h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-color);
        }
        .terms-section h3 {
            color: var(--primary-color);
            font-size: 18px;
            margin: 20px 0 15px 0;
            font-weight: 600;
        }
        .terms-section p {
            text-align: justify;
            margin-bottom: 12px;
            line-height: 1.9;
            color: #555;
        }
        .terms-section ul {
            padding-right: 25px;
            margin: 15px 0;
        }
        .terms-section li {
            margin-bottom: 10px;
            line-height: 1.8;
            color: #666;
        }
        .disclaimer-box {
            background: var(--warning-bg);
            border: 3px solid var(--warning-border);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
            margin-left: auto;
            margin-right: auto;
            max-width: 800px;
        }
        .disclaimer-box strong {
            display: block;
            color: #e65100;
            font-size: 18px;
            margin: 0 0 15px 0;
            text-align: center;
        }
        .disclaimer-box p {
            margin: 0;
            color: #d84315;
            text-align: justify;
            line-height: 1.8;
        }

        .footer {
            background: linear-gradient(135deg, var(--primary-color) 0%, #00c9b7 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-top: 3px solid var(--primary-color);
        }
        .footer strong {
            display: block;
            color: white;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .footer p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            margin: 5px 0;
        }
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; border-radius: 0; }
        }
        @media (max-width: 768px) {
            .metadata-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 22px; }
            .terms-content { padding: 20px 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>سند پذیرش شرایط و قوانین AiDASTYAR</h1>
            <p>این سند گواهی پذیرش شرایط استفاده از خدمات است</p>
        </div>
        
        <div class="metadata">
            <h3>اطلاعات پذیرش</h3>
            <div class="metadata-grid">
                <div class="metadata-item">
                    <strong>شناسه کاربر</strong>
                    <span>USERIDPLACEHOLDER</span>
                </div>
                <div class="metadata-item">
                    <strong>تاریخ پذیرش</strong>
                    <span>TIMESTAMPPLACEHOLDER</span>
                </div>
                <div class="metadata-item">
                    <strong>نسخه شرایط</strong>
                    <span>TERMSVERSIONPLACEHOLDER</span>
                </div>
                <div class="metadata-item">
                    <strong>آدرس IP</strong>
                    <span>IPPLACEHOLDER</span>
                </div>
            </div>
            <div class="hash-section">
                <strong>امضای دیجیتال (SHA-256)</strong>
                <div class="hash">HASHPLACEHOLDER</div>
            </div>
        </div>
        
        <div class="disclaimer-box">
            <strong>⚠️ هشدار قانونی</strong>
            <p style="margin-bottom: 0; color: #d84315; text-align: justify;">
                این سند به صورت خودکار تولید شده و دارای اعتبار قانونی است. هرگونه تغییر یا دستکاری در محتوای آن قابل شناسایی است.
            </p>
        </div>
        
        <div class="terms-content">
            TERMSCONTENTPLACEHOLDER
        </div>
        
        <div class="footer">
            <strong>✅ تأیید شده توسط سیستم</strong>
            <p>© AiDASTYAR - تمامی حقوق محفوظ است</p>
            <p style="margin-top: 15px; font-size: 12px; color: rgba(255, 255, 255, 0.8);">
                Generated at: GENERATEDTIMEPLACEHOLDER
            </p>
        </div>
    </div>
</body>
</html>
HTML;

    // جایگزینی placeholder ها
    $html = str_replace('USERIDPLACEHOLDER', htmlspecialchars($userid), $html);
    $html = str_replace('TIMESTAMPPLACEHOLDER', htmlspecialchars($timestamp), $html);
    $html = str_replace('TERMSVERSIONPLACEHOLDER', htmlspecialchars($termsversion), $html);
    $html = str_replace('IPPLACEHOLDER', htmlspecialchars($userip), $html);
    $html = str_replace('HASHPLACEHOLDER', htmlspecialchars($termshash), $html);
    $html = str_replace('GENERATEDTIMEPLACEHOLDER', htmlspecialchars(current_time('Y-m-d H:i:s')), $html);
    
    // ✅ محتوای شرایط - چون HTML معتبر است، escape نمی‌کنیم
    $html = str_replace('TERMSCONTENTPLACEHOLDER', $termscontent, $html);
    
    return $html;
}

}
