<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Assistant_Logger {

    const LEVEL_DEBUG   = 'DEBUG';
    const LEVEL_INFO    = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR   = 'ERROR';

    private static $instance = null;
    
    /**
     * سطح پیش‌فرض لاگ در کل سیستم
     * می‌تونی با define('AI_ASSISTANT_LOG_LEVEL', 'WARNING') در wp-config.php عوضش کنی
     *
     * ترتیب شدت:
     * DEBUG < INFO < WARNING < ERROR
     */
    private $level_order = array(
        self::LEVEL_DEBUG   => 0,
        self::LEVEL_INFO    => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR   => 3,
    );

    private $log_dir;
    private $log_file;
    private $min_level;

    public function __construct() {
        $this->log_dir  = WP_CONTENT_DIR . '/ai-assistant-logs/';
        $this->log_file = $this->log_dir . 'ai-assistant.log';

        // حداقل سطح لاگ از کانستنت (در صورت تعریف) یا پیش‌فرض INFO
        $this->min_level = defined( 'AI_ASSISTANT_LOG_LEVEL' )
            && isset( $this->level_order[ AI_ASSISTANT_LOG_LEVEL ] )
            ? AI_ASSISTANT_LOG_LEVEL
            : self::LEVEL_INFO;

        // ایجاد پوشه اگر وجود ندارد
        if ( ! file_exists( $this->log_dir ) ) {
            wp_mkdir_p( $this->log_dir );

            // اضافه کردن فایل امنیتی .htaccess
            $htaccess = $this->log_dir . '.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, "Order deny,allow\nDeny from all" );
            }

            // اضافه کردن فایل index خالی برای امنیت
            $index = $this->log_dir . 'index.php';
            if ( ! file_exists( $index ) ) {
                file_put_contents( $index, "<?php\n// Silence is golden.\n" );
            }
        }

        // ایجاد فایل لاگ اگر وجود ندارد
        if ( ! file_exists( $this->log_file ) ) {
            file_put_contents( $this->log_file, '' );
        }

        // بررسی دسترسی نوشتن
        $this->check_permissions();
    }

    /**
     * بررسی دسترسیهای فایل و پوشه
     */
    private function check_permissions() {
        // بررسی قابل نوشتن بودن پوشه
        if ( ! is_writable( $this->log_dir ) ) {
            error_log( 'AI Assistant: Log directory is not writable: ' . $this->log_dir );
            throw new Exception( 'پوشه لاگ قابل نوشتن نیست' );
        }

        // بررسی قابل نوشتن بودن فایل
        if ( file_exists( $this->log_file ) && ! is_writable( $this->log_file ) ) {
            error_log( 'AI Assistant: Log file is not writable: ' . $this->log_file );
            throw new Exception( 'فایل لاگ قابل نوشتن نیست' );
        }
    }

    /**
     * بررسی امنیتی قبل از نوشتن لاگ
     */
    private function security_check() {
        // بررسی مسیر برای جلوگیری از حملات Directory Traversal
        $real_log_dir  = realpath( $this->log_dir );
        $real_file_dir = realpath( dirname( $this->log_file ) );

        if ( $real_log_dir === false || $real_file_dir === false || $real_file_dir !== $real_log_dir ) {
            error_log( 'AI Assistant: Potential directory traversal attempt detected' );
            throw new Exception( 'مسیر فایل لاگ نامعتبر است' );
        }
    }

    /**
     * بررسی این‌که سطح فعلی باید لاگ شود یا نه
     */
    private function should_log( $level ) {
        if ( ! isset( $this->level_order[ $level ] ) ) {
            return false;
        }
        $current = $this->level_order[ $level ];
        $min     = $this->level_order[ $this->min_level ];
        return $current >= $min;
    }

    /**
     * متد داخلی عمومی برای نوشتن لاگ
     */
    private function write_log( $level, $message, $context = array() ) {
        if ( ! $this->should_log( $level ) ) {
            return;
        }

        try {
            $this->security_check();

            $timestamp   = current_time( 'mysql' );
            $level       = strtoupper( $level );
            $log_message = "[{$timestamp}] {$level}: {$message}";

            if ( ! empty( $context ) ) {
                // حذف اطلاعات خیلی حساس در صورت نیاز (مثال: token)
                // if ( isset( $context['token'] ) ) {
                //     $context['token'] = '***';
                // }

                $log_message .= ' - ' . json_encode( $context, JSON_UNESCAPED_UNICODE );
            }

            file_put_contents( $this->log_file, $log_message . PHP_EOL, FILE_APPEND | LOCK_EX );

            // در محیط توسعه لاگ را به error_log هم بفرست
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( $log_message );
            }

        } catch ( Exception $e ) {
            error_log( 'AI Assistant Log Error: ' . $e->getMessage() );
        }
    }

    /**
     * متد قبلی log (سازگار با کد موجود)
     * معادل سطح INFO
     */
    public function log( $message, $context = array() ) {
        $this->write_log( self::LEVEL_INFO, $message, $context );
    }

    /**
     * متد قبلی log_error (سازگار با کد موجود)
     * معادل سطح ERROR
     */
    public function log_error( $message, $context = array() ) {
        $this->write_log( self::LEVEL_ERROR, $message, $context );
    }

    /**
     * متد جدید برای debug
     */
    public function log_debug( $message, $context = array() ) {
        $this->write_log( self::LEVEL_DEBUG, $message, $context );
    }

    /**
     * متد جدید برای warning
     */
    public function log_warning( $message, $context = array() ) {
        $this->write_log( self::LEVEL_WARNING, $message, $context );
    }

    /**
     * گرفتن آخرین لاگ‌ها
     */
    public function get_logs( $limit = 100 ) {
        if ( ! file_exists( $this->log_file ) ) {
            return array();
        }

        $logs = file( $this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        return array_slice( array_reverse( $logs ), 0, $limit );
    }
    
        /**
     * Singleton instance getter
     *
     * @return AI_Assistant_Logger
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}
