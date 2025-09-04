<?php
/**
 * Mr ECU - Development Configuration
 * Local development environment settings
 */

// Environment detection
if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// Site temel ayarları
define('SITE_NAME', 'Mr ECU');
define('SITE_URL', 'http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/');
define('BASE_URL', 'http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi');
define('SITE_EMAIL', 'info@localhost.com');

// Debug ayarları
define('DEBUG', true);
define('ERROR_REPORTING', true);
define('LOG_ERRORS', true);
define('DISPLAY_ERRORS', true);

// Security ayarları
define('SECURITY_ENABLED', true);
define('CSP_STRICT_MODE', false);
define('SESSION_TIMEOUT', 3600);
define('ADMIN_SESSION_TIMEOUT', 1800);
define('CSRF_TOKEN_LIFETIME', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_DURATION', 900);
define('MAX_REQUESTS_PER_MINUTE', 60);
define('MAX_FILE_UPLOADS_PER_HOUR', 10);

// User ayarları
define('DEFAULT_CREDITS', 0);
define('FILE_DOWNLOAD_COST', 1);

// File upload ayarları
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Security salt
define('SECURE_SALT', 'development_salt_change_this_in_production');

// Featured products count
define('FEATURED_PRODUCTS_COUNT', 12);

// Timezone
date_default_timezone_set('Europe/Istanbul');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Development specific configurations
$GLOBALS['current_config_file'] = __FILE__;

error_log('Development configuration loaded successfully');
?>
