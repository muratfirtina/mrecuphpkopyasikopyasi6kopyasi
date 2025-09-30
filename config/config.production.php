<?php
/**
 * Mr ECU - Production-Ready Global Configuration
 * Environment-based güvenli konfigürasyon sistemi - Production için özelleştirildi
 * Domain: mrecutuning.com
 * 
 * @global SecurityManager|null $security Global security manager instance
 * @global SecureDatabase|null $secureDb Global secure database wrapper
 * @global PDO|null $pdo Global database connection
 */

// ==========================================
// 🔧 ENVIRONMENT VARIABLES YÜKLE
// ==========================================

function loadEnvironment() {
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
}

// Environment yükle
loadEnvironment();

// Helper function - Environment değer al
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ==========================================
# 🌐 SİTE AYARLARI - PRODUCTION (mrecutuning.com)
// ==========================================

define('SITE_NAME', env('SITE_NAME', 'Mr.ECU | chiptuning dosya servisi'));
define('SITE_URL', rtrim(env('SITE_URL', 'https://www.mrecutuning.com'), '/') . '/');
define('BASE_URL', rtrim(env('BASE_URL', 'https://www.mrecutuning.com'), '/'));
define('SITE_EMAIL', env('SITE_EMAIL', 'mr.ecu@outlook.com'));
define('ADMIN_EMAIL', env('SITE_EMAIL', 'mr.ecu@outlook.com'));

// ==========================================
# 📊 SEO VE META AYARLARI
// ==========================================

define('DEFAULT_META_TITLE', env('DEFAULT_META_TITLE', 'Mr.ECU - Profesyonel ECU Programlama ve Chip Tuning'));
define('DEFAULT_META_DESCRIPTION', env('DEFAULT_META_DESCRIPTION', 'Mr. ECU Teknoloji, chiptuning dosya servisi, ECU programlama ve arıza tespit cihazları ile servislerin performansını artırır.'));
define('DEFAULT_META_KEYWORDS', env('DEFAULT_META_KEYWORDS', 'ecu programlama, chip tuning, chiptuning dosya servisi, autotuner, kess, otomotiv yazılım, arıza tespit cihazları, mr ecu, mrecutuning'));

// ==========================================
# 🛍️ ÜRÜN SİSTEMİ AYARLARI
// ==========================================

define('PRODUCTS_PER_PAGE', (int)env('PRODUCTS_PER_PAGE', 12));
define('RELATED_PRODUCTS_COUNT', (int)env('RELATED_PRODUCTS_COUNT', 6));
define('FEATURED_PRODUCTS_COUNT', (int)env('FEATURED_PRODUCTS_COUNT', 8));
define('PRODUCT_IMAGE_MAX_SIZE', (int)env('PRODUCT_IMAGE_MAX_SIZE', 10 * 1024 * 1024)); // 10MB
define('PRODUCT_IMAGES_PER_PRODUCT', (int)env('PRODUCT_IMAGES_PER_PRODUCT', 10));
define('BRAND_LOGO_MAX_SIZE', (int)env('BRAND_LOGO_MAX_SIZE', 5 * 1024 * 1024)); // 5MB

// Resim boyutları (otomatik resize için)
define('PRODUCT_IMAGE_SIZES', [
    'thumbnail' => ['width' => 200, 'height' => 200],
    'medium' => ['width' => 600, 'height' => 600],
    'large' => ['width' => 1200, 'height' => 1200]
]);

// ==========================================
# 📞 İLETİŞİM BİLGİLERİ - PRODUCTION
// ==========================================

define('CONTACT_PHONE', env('CONTACT_PHONE', '+90 XXX XXX XX XX'));
define('CONTACT_WHATSAPP', env('CONTACT_WHATSAPP', '+90XXXXXXXXXX'));
define('CONTACT_ADDRESS', env('CONTACT_ADDRESS', 'İstanbul, Türkiye'));
define('COMPANY_NAME', env('COMPANY_NAME', 'Mr ECU Yazılım ve Teknoloji'));

// ==========================================
# 📱 SOSYAL MEDYA HESAPLARI
// ==========================================

define('SOCIAL_FACEBOOK', env('SOCIAL_FACEBOOK', ''));
define('SOCIAL_INSTAGRAM', env('SOCIAL_INSTAGRAM', ''));
define('SOCIAL_TWITTER', env('SOCIAL_TWITTER', ''));
define('SOCIAL_YOUTUBE', env('SOCIAL_YOUTUBE', ''));
define('SOCIAL_LINKEDIN', env('SOCIAL_LINKEDIN', ''));

// ==========================================
# 🐛 DEBUG VE ERROR AYARLARI - PRODUCTION
// ==========================================

// ⚠️ PRODUCTION'DA MUTLAKA FALSE!
define('DEBUG', filter_var(env('DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));
define('ERROR_REPORTING', filter_var(env('ERROR_REPORTING', 'false'), FILTER_VALIDATE_BOOLEAN));
define('LOG_ERRORS', filter_var(env('LOG_ERRORS', 'true'), FILTER_VALIDATE_BOOLEAN));
define('DISPLAY_ERRORS', filter_var(env('DISPLAY_ERRORS', 'false'), FILTER_VALIDATE_BOOLEAN));

// Production error reporting ayarları
if (DEBUG && ERROR_REPORTING) {
    error_reporting(E_ALL);
    ini_set('display_errors', DISPLAY_ERRORS ? 1 : 0);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

ini_set('display_startup_errors', 0);
ini_set('log_errors', LOG_ERRORS ? 1 : 0);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// ==========================================
# 📁 DOSYA YÜKLEME AYARLARI - PRODUCTION
// ==========================================

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 100 * 1024 * 1024)); // 100MB
define('ALLOWED_EXTENSIONS', ['bin', 'hex', 'ori', 'mod', 'edc', 'zip', 'rar', 'pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'webp']); // ECU dosyaları için

// Görüntü dosyası formatları
define('IMAGE_EXTENSIONS', ['jpeg', 'jpg', 'png', 'avif', 'webp', 'heic', 'gif', 'bmp', 'svg']);

// ==========================================
# 📧 EMAIL AYARLARI - PRODUCTION (Outlook)
// ==========================================

define('SMTP_HOST', env('SMTP_HOST', 'smtp-mail.outlook.com'));
define('SMTP_PORT', (int)env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'mr.ecu@outlook.com'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', 'Agucuk93')); // Production'da .env'den alınıyor
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'mr.ecu@outlook.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Mr ECU'));

// Email test modu (Production'da false)
define('EMAIL_TEST_MODE', filter_var(env('EMAIL_TEST_MODE', 'false'), FILTER_VALIDATE_BOOLEAN));

// ==========================================
# 🔒 GÜVENLİK AYARLARI - PRODUCTION
// ==========================================

define('SECURE_SALT', env('SECURE_SALT', 'MrECU_2025_PROD_mrecutuning_com_xyz789_SECURE_SALT_2309Mf1983'));
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600)); // 1 saat
define('ADMIN_SESSION_TIMEOUT', (int)env('ADMIN_SESSION_TIMEOUT', 1800)); // 30 dakika
define('CSRF_TOKEN_LIFETIME', (int)env('CSRF_TOKEN_LIFETIME', 3600));
define('SECURITY_ENABLED', filter_var(env('SECURITY_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN));
define('CSP_STRICT_MODE', filter_var(env('CSP_STRICT_MODE', 'true'), FILTER_VALIDATE_BOOLEAN)); // Production'da true

// ==========================================
# 🚦 RATE LIMİTİNG AYARLARI - PRODUCTION
// ==========================================

define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_BLOCK_DURATION', (int)env('LOGIN_BLOCK_DURATION', 900)); // 15 dakika
define('MAX_REQUESTS_PER_MINUTE', (int)env('MAX_REQUESTS_PER_MINUTE', 60));
define('MAX_FILE_UPLOADS_PER_HOUR', (int)env('MAX_FILE_UPLOADS_PER_HOUR', 10));

// ==========================================
# 💰 KREDİ SİSTEMİ AYARLARI
// ==========================================

define('DEFAULT_CREDITS', (int)env('DEFAULT_CREDITS', 0));
define('FILE_DOWNLOAD_COST', (int)env('FILE_DOWNLOAD_COST', 1));

// ==========================================
# 🌍 BÖLGESEL AYARLAR
// ==========================================

$timezone = env('APP_TIMEZONE', 'Europe/Istanbul');
$locale = env('APP_LOCALE', 'tr');

date_default_timezone_set($timezone);
setlocale(LC_TIME, $locale);

// ==========================================
# 🔐 SESSION GÜVENLİK AYARLARI - PRODUCTION
// ==========================================

if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // HTTPS için true
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    ini_set('session.name', 'MRECU_SECURE_SESSION');
    
    // Session başlat
    session_start();
    
    // Session güvenlik kontrolü
    if (!isset($_SESSION['initialized'])) {
        // AJAX upload işlemlerinde session regeneration'ı skip et
        if (!defined('AJAX_SESSION_LOCK')) {
            session_regenerate_id(true);
        }
        $_SESSION['initialized'] = true;
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    } else {
        // Session hijacking kontrolü (IP değişikliği için sıkı kontrol)
        if (isset($_SESSION['user_agent']) && 
            $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            error_log('Potential session hijacking detected - User Agent changed from: ' . $_SESSION['user_agent'] . ' to: ' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
            session_destroy();
            session_start();
        }
    }
}

// ==========================================
# 📚 AUTOLOAD VE SINIF YÜKLEMESİ
// ==========================================

// Security sınıflarını dahil et (eğer mevcutsa)
$securityFiles = [
    __DIR__ . '/../security/SecurityManager.php',
    __DIR__ . '/../security/SecureDatabase.php',
    __DIR__ . '/../security/SecurityHeaders.php'
];

foreach ($securityFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Autoload fonksiyonu
spl_autoload_register(function ($class_name) {
    $paths = [
        __DIR__ . '/../includes/' . $class_name . '.php',
        __DIR__ . '/../classes/' . $class_name . '.php'
    ];
    
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});

// ==========================================
# 🛡️ GÜVENLİK SİSTEMİ BAŞLATMA
// ==========================================

// Global güvenlik nesneleri
/** @var SecurityManager|null $security */
$security = null;
/** @var SecureDatabase|null $secureDb */
$secureDb = null;

// Güvenlik sistemini başlat (sadece dosyalar mevcutsa)
if (SECURITY_ENABLED && class_exists('SecurityManager')) {
    try {
        // Database bağlantısı
        require_once __DIR__ . '/database.php';
        
        // SecurityManager başlat
        $security = new SecurityManager($pdo);
        
        // Secure Database wrapper
        if (class_exists('SecureDatabase')) {
            $secureDb = new SecureDatabase($pdo, $security);
        }
        
        // Güvenlik başlıkları (sadece HTML sayfalar için)
        if (class_exists('SecurityHeaders') && 
            !isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strpos($_SERVER['REQUEST_URI'], '.php') !== false) {
            SecurityHeaders::setAllHeaders(CSP_STRICT_MODE);
        }
        
    } catch (Exception $e) {
        error_log('Security system initialization failed: ' . $e->getMessage());
        // Güvenlik sistemi başlatılamazsa normal database kullan
        require_once __DIR__ . '/database.php';
    }
} else {
    // Güvenlik sistemi devre dışı - normal database
    require_once __DIR__ . '/database.php';
}

// ==========================================
# 🔧 HELPER FUNCTIONS (Güvenlik Entegrasyonlu) - PRODUCTION
// ==========================================

function sanitize($data, $type = 'general') {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        return $security->sanitizeInput($data, $type);
    }
    
    // Fallback güvenlik temizleme
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect($url) {
    // URL güvenlik kontrolü - production için sıkı
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        // Güvenli redirect kontrolü
        $parsedUrl = parse_url($url);
        $allowedHosts = ['mrecutuning.com', 'www.mrecutuning.com'];
        
        if (isset($parsedUrl['host']) && !in_array($parsedUrl['host'], $allowedHosts)) {
            $security->logSecurityEvent('unsafe_redirect_attempt', $url, $security->getClientIp());
            $url = SITE_URL; // Güvenli URL'e yönlendir
        }
    }
    
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    // Role tabanlı kontrol (öncelik)
    if (isset($_SESSION['role'])) {
        return in_array($_SESSION['role'], ['admin', 'design']);
    }
    
    // Fallback: is_admin tabanlı kontrol
    return isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function isValidUUID($uuid) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}

function generateCsrfToken() {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        return $security->generateCsrfToken();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        return $security->validateCsrfToken($token);
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function checkRateLimit($action, $identifier = null, $limit = 10, $window = 300) {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        return $security->checkRateLimit($action, $identifier, $limit, $window);
    }
    
    return true;
}

function logSecurityEvent($eventType, $details) {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        $security->logSecurityEvent($eventType, $details, $security->getClientIp());
    }
}

function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
    $errors = [];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Geçersiz dosya yükleme.';
    }
    
    $maxSize = $maxSize ?: MAX_FILE_SIZE;
    if ($file['size'] > $maxSize) {
        $errors[] = 'Dosya boyutu çok büyük (' . formatFileSize($maxSize) . ' maksimum).';
    }
    
    $allowedTypes = $allowedTypes ?: ALLOWED_EXTENSIONS;
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        $errors[] = 'Desteklenmeyen dosya formatı. İzin verilen formatlar: ' . implode(', ', $allowedTypes);
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'safe_name' => preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name'])
    ];
}

function sendEmail($to, $subject, $message, $isHTML = true) {
    $to = sanitize($to, 'email');
    $subject = sanitize($subject);
    
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    if (EMAIL_TEST_MODE) {
        error_log("Test Email - To: $to, Subject: $subject");
        return true;
    }
    
    // PHPMailer veya mail() fonksiyonu kullanılabilir
    $headers = "From: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

function formatFileSize($bytes) {
    if ($bytes === 0 || $bytes === null) return '0 B';
    
    $bytes = (float) $bytes;
    if ($bytes <= 0) return '0 B';
    
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

function turkishToEnglish($text) {
    $search = array('Ğ','Ü','Ş','İ','Ö','Ç','ğ','ü','ş','ı','ö','ç');
    $replace = array('G','U','S','I','O','C','g','u','s','i','o','c');
    return str_replace($search, $replace, $text);
}

if (!function_exists('createSlug')) {
    function createSlug($text) {
        $text = turkishToEnglish($text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}

function isImageFile($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, IMAGE_EXTENSIONS);
}

// ==========================================
# 🔄 CSRF TOKEN OLUŞTUR
// ==========================================

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCsrfToken();
}

// ==========================================
# ⚠️ GLOBAL ERROR HANDLER (Production)
// ==========================================

if (SECURITY_ENABLED) {
    set_error_handler(function($severity, $message, $file, $line) {
        if (error_reporting() & $severity) {
            logSecurityEvent('php_error', [
                'message' => $message,
                'file' => basename($file), // Güvenlik için sadece dosya adı
                'line' => $line,
                'severity' => $severity
            ]);
        }
        return false; // Normal error handling devam etsin
    });
    
    set_exception_handler(function($exception) {
        logSecurityEvent('php_exception', [
            'message' => $exception->getMessage(),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine()
        ]);
        
        // Production'da kullanıcıya hata gösterme
        if (!DEBUG) {
            header('HTTP/1.1 500 Internal Server Error');
            include __DIR__ . '/../500.php';
            exit;
        }
    });
}

// ==========================================
# 🎯 UTILITY FUNCTIONS - PRODUCTION
// ==========================================

function renderSecurityMeta() {
    $nonce = class_exists('SecurityHeaders') ? SecurityHeaders::getNonce() : '';
    echo '<meta name="csrf-token" content="' . ($_SESSION['csrf_token'] ?? '') . '">' . "\n";
    echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
    if (!DEBUG) {
        echo '<meta name="robots" content="index, follow">' . "\n";
    }
    return $nonce;
}

function includeSecurityScript() {
    $nonce = class_exists('SecurityHeaders') ? SecurityHeaders::getNonce() : '';
    if (file_exists(__DIR__ . '/../security/security-guard.js')) {
        echo '<script nonce="' . $nonce . '" src="' . SITE_URL . 'security/security-guard.js"></script>' . "\n";
    }
}

// ==========================================
# 🌟 PRODUCTION OPTIMIZATIONS
// ==========================================

// Output buffering for production
if (!DEBUG) {
    ob_start('ob_gzhandler');
}

// Gzip compression
if (!ob_get_level() && extension_loaded('zlib') && !headers_sent()) {
    ini_set('zlib.output_compression', 'On');
}

// ==========================================
# 💻 ENVIRONMENT INFO (Production Log)
// ==========================================

if (DEBUG) {
    error_log('Mr ECU Config Loaded - Environment: Production');
    error_log('Site URL: ' . SITE_URL);
    error_log('Security Enabled: ' . (SECURITY_ENABLED ? 'Yes' : 'No'));
} else {
    // Production'da sadece başlatma kaydı
    error_log('Mr ECU Production Environment Started - ' . date('Y-m-d H:i:s'));
}

// ==========================================
# 🏁 PRODUCTION READY CONFIRMATION
// ==========================================

if (!defined('MRECU_CONFIG_LOADED')) {
    define('MRECU_CONFIG_LOADED', true);
    define('MRECU_ENVIRONMENT', 'production');
    define('MRECU_VERSION', '1.0.0');
    define('MRECU_DOMAIN', 'mrecutuning.com');
}

?>
