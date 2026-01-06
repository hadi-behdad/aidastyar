<?php
/**
 * Temporary PDF Upload Handler with Better File Type Detection
 */

if (!defined('ABSPATH')) {
    exit;
}

function aidastyar_upload_temp_pdf() {
    check_ajax_referer('upload_temp_pdf_nonce', 'security');
    
    if (!isset($_FILES['pdf_file'])) {
        wp_send_json_error(['message' => 'فایلی آپلود نشده است']);
        return;
    }
    
    $file = $_FILES['pdf_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'خطا در آپلود فایل. کد خطا: ' . $file['error']]);
        return;
    }
    
    // ✅ بررسی نوع فایل - روش بهتر
    $filename = $file['name'];
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // لاگ برای debug
    error_log("[PDF Upload Debug] Original name: " . $filename);
    error_log("[PDF Upload Debug] Extension: " . $file_extension);
    error_log("[PDF Upload Debug] MIME type: " . $file['type']);
    
    // ✅ چک کردن extension
    if ($file_extension !== 'pdf') {
        wp_send_json_error([
            'message' => 'فقط فایل‌های PDF مجاز هستند',
            'debug' => [
                'filename' => $filename,
                'extension' => $file_extension,
                'mime' => $file['type']
            ]
        ]);
        return;
    }
    
    // ✅ چک اضافی: بررسی magic bytes (اختیاری ولی امن‌تر)
    $file_content = file_get_contents($file['tmp_name'], false, null, 0, 4);
    if (substr($file_content, 0, 4) !== '%PDF') {
        wp_send_json_error(['message' => 'فایل انتخاب شده یک PDF معتبر نیست']);
        return;
    }
    
    // بررسی حجم
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        wp_send_json_error(['message' => 'حجم فایل نباید بیشتر از 10 مگابایت باشد']);
        return;
    }
    
    // ایجاد فولدر
    $upload_dir = wp_upload_dir();
    $temp_pdf_dir = $upload_dir['basedir'] . '/temp-lab-pdfs';
    
    if (!file_exists($temp_pdf_dir)) {
        wp_mkdir_p($temp_pdf_dir);
        
        $htaccess_content = "# Prevent direct access\n";
        $htaccess_content .= "Order deny,allow\n";
        $htaccess_content .= "Deny from all\n";
        file_put_contents($temp_pdf_dir . '/.htaccess', $htaccess_content);
        file_put_contents($temp_pdf_dir . '/index.php', '<?php // Silence is golden');
    }
    
    // ✅ محاسبه hash
    $file_hash = md5_file($file['tmp_name']);
    $file_size = $file['size'];
    
    error_log("[PDF Upload] Hash: $file_hash, Size: $file_size bytes");
    
    // ✅ چک فایل‌های موجود
    $existing_files = glob($temp_pdf_dir . '/*.pdf');
    $existing_file_to_reuse = null;
    
    foreach ($existing_files as $existing_file) {
        $existing_size = filesize($existing_file);
        
        // ✅ اول حجم چک کن (سریع‌تر)
        if ($existing_size === $file_size) {
            // ✅ بعد hash چک کن (کندتر ولی دقیق‌تر)
            $existing_hash = md5_file($existing_file);
            
            if ($existing_hash === $file_hash) {
                $existing_file_to_reuse = $existing_file;
                error_log("[PDF Upload] Exact duplicate found: " . basename($existing_file));
                break;
            }
        }
    }
    
    // ✅ اگه فایل تکراری پیدا شد
    if ($existing_file_to_reuse) {
        
        $existing_filename = basename($existing_file_to_reuse);
        $existing_json = $temp_pdf_dir . '/' . pathinfo($existing_filename, PATHINFO_FILENAME) . '.json';
        
        // به‌روزرسانی metadata
        $metadata = [];
        if (file_exists($existing_json)) {
            $metadata = json_decode(file_get_contents($existing_json), true);
        }
        
        $metadata['last_accessed'] = current_time('mysql');
        $metadata['access_count'] = isset($metadata['access_count']) ? $metadata['access_count'] + 1 : 2;
        $metadata['last_original_name'] = sanitize_file_name($file['name']);
        
        file_put_contents($existing_json, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        error_log("[PDF Upload] Reusing existing file (accessed {$metadata['access_count']} times)");
        
        wp_send_json_success([
            'message' => 'فایل قبلاً آپلود شده بود - استفاده مجدد',
            'file_path' => $existing_file_to_reuse,
            'filename' => $existing_filename,
            'original_name' => $file['name'],
            'file_size' => $file_size,
            'is_duplicate' => true,
            'access_count' => $metadata['access_count'],
            'upload_date' => $metadata['upload_date'] ?? current_time('Y-m-d H:i:s')
        ]);
        
        return;
    }
    
    // ✅ فایل جدیده - ذخیره کن
    $user_id = get_current_user_id();
    $timestamp = time();
    $random = wp_generate_password(12, false);
    
    $new_filename = sprintf(
        'lab_%s_%s_%s.pdf',
        $user_id > 0 ? $user_id : 'guest',
        $timestamp,
        $random
    );
    
    $destination = $temp_pdf_dir . '/' . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        
        // ذخیره metadata
        $metadata = [
            'original_filename' => sanitize_file_name($file['name']),
            'upload_date' => current_time('mysql'),
            'timestamp' => $timestamp,
            'user_id' => $user_id,
            'file_size' => $file_size,
            'file_hash' => $file_hash,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'access_count' => 1
        ];
        
        $json_filename = pathinfo($new_filename, PATHINFO_FILENAME) . '.json';
        file_put_contents(
            $temp_pdf_dir . '/' . $json_filename,
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        error_log("[PDF Upload] New file saved: $new_filename");
        
        wp_send_json_success([
            'message' => 'فایل با موفقیت آپلود شد',
            'file_path' => $destination,
            'filename' => $new_filename,
            'original_name' => $file['name'],
            'file_size' => $file_size,
            'is_duplicate' => false,
            'upload_date' => current_time('Y-m-d H:i:s')
        ]);
        
    } else {
        wp_send_json_error(['message' => 'خطا در ذخیره فایل روی سرور']);
    }
}

add_action('wp_ajax_upload_temp_pdf', 'aidastyar_upload_temp_pdf');
add_action('wp_ajax_nopriv_upload_temp_pdf', 'aidastyar_upload_temp_pdf');
