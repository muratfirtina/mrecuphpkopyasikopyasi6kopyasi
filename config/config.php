<?php
/**
 * Mr ECU - Environment-aware Configuration Loader
 * Development ve Production ortamını otomatik algılar
 */

// Hata yakalama için try-catch
try {
    // Environment detection
    $isDevelopment = (
        // MAMP local development
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ||
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) ||
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.local') !== false) ||
        // Development file exists
        file_exists(__DIR__ . '/../.development') ||
        // Environment variable
        (getenv('APP_ENV') === 'development')
    );
    
    $isProduction = (
        // Production domain
        (isset($_SERVER['HTTP_HOST']) && 
         ($_SERVER['HTTP_HOST'] === 'mrecutuning.com' || 
          $_SERVER['HTTP_HOST'] === 'www.mrecutuning.com' ||
          $_SERVER['HTTP_HOST'] === 'mrecutuning.com')) ||
        // Production file exists
        file_exists(__DIR__ . '/../.production') ||
        // Environment variable
        (getenv('APP_ENV') === 'production')
    );
    
    // Eğer hiç biri tespit edilemezse, domain'e göre karar ver
    if (!$isDevelopment && !$isProduction) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'mrecutuning.com') !== false) {
            $isProduction = true;
        } else {
            $isDevelopment = true; // Default to development
        }
    }
    
    // Production configuration yükle
    if ($isProduction) {
        if (file_exists(__DIR__ . '/config.production.php')) {
            require_once __DIR__ . '/config.production.php';
            error_log('Mr ECU: Production configuration loaded');
        } else {
            throw new Exception('Production configuration file not found!');
        }
    }
    // Development configuration yükle
    else {
        // Development config dosyası yoksa, mevcut config'i kullan
        if (file_exists(__DIR__ . '/config.development.php')) {
            require_once __DIR__ . '/config.development.php';
        } else {
            // Fallback: Inline development configuration
            loadDevelopmentConfig();
        }
        error_log('Mr ECU: Development configuration loaded');
    }
    
} catch (Exception $e) {
    // Configuration yükleme hatası
    error_log('Mr ECU Configuration Error: ' . $e->getMessage());
    
    // Emergency fallback configuration
    loadEmergencyConfig();
}

// Güvenli number_format fonksiyonu (PHP 8.0+ null value fix)
function safe_number_format($num, $decimals = 0, $decimal_separator = '.', $thousands_separator = ',') {
    if ($num === null || $num === '') {
        return '0';
    }
    return number_format((float)$num, $decimals, $decimal_separator, $thousands_separator);
}

/**
 * Development konfigürasyonu yükle (fallback)
 */
function loadDevelopmentConfig() {
    // .env dosyasını yükle
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    // Helper function
    if (!function_exists('env')) {
        function env($key, $default = null) {
            return $_ENV[$key] ?? getenv($key) ?: $default;
        }
    }
    
    // Basic development constants
    define('SITE_NAME', env('SITE_NAME', 'Mr ECU'));
    define('BASE_URL', env('BASE_URL', 'http://localhost:8888/mrecutuning'));
    define('SITE_URL', env('SITE_URL', BASE_URL . '/'));
    define('SITE_EMAIL', env('SITE_EMAIL', 'info@localhost.com'));
    define('DEBUG', true);
    define('ERROR_REPORTING', true);
    define('LOG_ERRORS', true);
    define('DISPLAY_ERRORS', true);
    define('SECURITY_ENABLED', true);
    define('CSP_STRICT_MODE', false);
    define('SESSION_TIMEOUT', 3600);
    define('ADMIN_SESSION_TIMEOUT', 1800);
    define('CSRF_TOKEN_LIFETIME', 3600);
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOGIN_BLOCK_DURATION', 900);
    define('MAX_REQUESTS_PER_MINUTE', 60);
    define('MAX_FILE_UPLOADS_PER_HOUR', 10);
    define('DEFAULT_CREDITS', 0);
    define('FILE_DOWNLOAD_COST', 1);
    define('MAX_FILE_SIZE', 100 * 1024 * 1024);
    define('UPLOAD_DIR', __DIR__ . '/../uploads/');
    define('SECURE_SALT', env('SECURE_SALT', 'development_salt_change_this'));
    
    // Timezone
    date_default_timezone_set('Europe/Istanbul');
    
    // Error reporting for development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
    
    error_log('Development fallback configuration loaded');
}

/**
 * Acil durum konfigürasyonu
 */
function loadEmergencyConfig() {
    // Minimum required constants
    if (!defined('SITE_NAME')) define('SITE_NAME', 'Mr ECU');
    if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost:8888/mrecutuning');
    if (!defined('SITE_URL')) define('SITE_URL', BASE_URL . '/');
    if (!defined('SITE_EMAIL')) define('SITE_EMAIL', 'mr.ecu@outlook.com');
    if (!defined('DEBUG')) define('DEBUG', false);
    if (!defined('ERROR_REPORTING')) define('ERROR_REPORTING', false);
    if (!defined('LOG_ERRORS')) define('LOG_ERRORS', true);
    if (!defined('DISPLAY_ERRORS')) define('DISPLAY_ERRORS', false);
    if (!defined('SECURITY_ENABLED')) define('SECURITY_ENABLED', false);
    if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 3600);
    if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 10 * 1024 * 1024);
    
    error_log('Emergency configuration loaded - Please check configuration files!');
}

/**
 * Konfigürasyon durumunu kontrol et
 */
function getConfigurationStatus() {
    $status = [
        'environment' => defined('DEBUG') && DEBUG ? 'development' : 'production',
        'site_url' => defined('SITE_URL') ? SITE_URL : 'undefined',
        'security_enabled' => defined('SECURITY_ENABLED') ? SECURITY_ENABLED : false,
        'debug_mode' => defined('DEBUG') ? DEBUG : false,
        'config_file' => $GLOBALS['current_config_file'] ?? 'unknown'
    ];
    
    return $status;
}

// Konfigürasyon yükleme zamanını kaydet
if (!defined('MRECU_CONFIG_LOAD_TIME')) {
    define('MRECU_CONFIG_LOAD_TIME', microtime(true));
}

// Environment bilgisini global değişkende sakla
$GLOBALS['mrecu_environment'] = defined('DEBUG') && DEBUG ? 'development' : 'production';
$GLOBALS['mrecu_config_loaded'] = true;

?>
