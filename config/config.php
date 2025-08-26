<?php
/**
 * Mr ECU - Global Configuration
 * Genel sistem ayarları ve güvenlik entegrasyonu
 * 
 * @global SecurityManager|null $security Global security manager instance
 * @global SecureDatabase|null $secureDb Global secure database wrapper
 * @global PDO|null $pdo Global database connection
 */

// Site ayarları
define('SITE_NAME', 'Mr ECU');
define('SITE_URL', 'http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/');
define('BASE_URL', 'http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi'); // Trailing slash olmadan
define('SITE_EMAIL', 'info@mrecu.com');

// SEO ve Meta ayarları
define('DEFAULT_META_TITLE', 'Mr ECU - Profesyonel ECU Programlama ve Chip Tuning');
define('DEFAULT_META_DESCRIPTION', 'ECU programlama, chip tuning ve otomotiv yazılım çözümleri. Profesyonel araçlar ve güvenilir hizmet.');
define('DEFAULT_META_KEYWORDS', 'ecu programlama, chip tuning, autotuner, kess, otomotiv yazılım');

// Ürün sistemi ayarları
define('PRODUCTS_PER_PAGE', 12); // Sayfa başına ürün sayısı
define('RELATED_PRODUCTS_COUNT', 6); // İlgili ürün sayısı
define('FEATURED_PRODUCTS_COUNT', 8); // Öne çıkan ürün sayısı
define('PRODUCT_IMAGE_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('PRODUCT_IMAGES_PER_PRODUCT', 10); // Ürün başına maksimum resim
define('BRAND_LOGO_MAX_SIZE', 5 * 1024 * 1024); // 5MB

// Resim boyutları (otomatik resize için)
define('PRODUCT_IMAGE_SIZES', [
    'thumbnail' => ['width' => 200, 'height' => 200],
    'medium' => ['width' => 600, 'height' => 600],
    'large' => ['width' => 1200, 'height' => 1200]
]);

// İletişim bilgileri
define('CONTACT_PHONE', '+90 XXX XXX XX XX');
define('CONTACT_WHATSAPP', '+90XXXXXXXXXX');
define('CONTACT_ADDRESS', 'İstanbul, Türkiye');
define('COMPANY_NAME', 'Mr ECU Yazılım ve Teknoloji');

// Sosyal medya hesapları (gelecek için)
define('SOCIAL_FACEBOOK', '');
define('SOCIAL_INSTAGRAM', '');
define('SOCIAL_TWITTER', '');
define('SOCIAL_YOUTUBE', '');
define('SOCIAL_LINKEDIN', '');

// Debug modu (geliştirme ortamı için)
define('DEBUG', true); // Production'da false yapın

// Görüntü dosyası formatları
define('IMAGE_EXTENSIONS', ['jpeg', 'jpg', 'png', 'avif', 'webp', 'heic', 'gif', 'bmp', 'svg']);

// Dosya yükleme ayarları
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/'); // FileManager için
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB
define('ALLOWED_EXTENSIONS', []); // Tüm dosya türlerine izin ver

// Email ayarları (SMTP) - mrecu@outlook.com
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'mrecu@outlook.com');
define('SMTP_PASSWORD', ''); // Güvenlik için boş bırakıldı, veritabanından alınacak
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'mrecu@outlook.com');
define('SMTP_FROM_NAME', 'Mr ECU');

// Email test modu (MAMP için)
// true = Email'leri log dosyasına yazar (test)
// false = Gerçek email göndermeye çalışır
define('EMAIL_TEST_MODE', true);

// Güvenlik ayarları
define('SECURE_SALT', 'mr_ecu_2025_secure_salt_key_' . hash('sha256', __DIR__));
define('SESSION_TIMEOUT', 3600); // 1 saat
define('SECURITY_ENABLED', true); // Güvenlik sistemini aktif et
define('CSP_STRICT_MODE', false); // Geliştirme için false, production'da true

// Rate limiting ayarları
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_DURATION', 900); // 15 dakika
define('MAX_REQUESTS_PER_MINUTE', 60);
define('MAX_FILE_UPLOADS_PER_HOUR', 10);

// Kredi sistemi ayarları
define('DEFAULT_CREDITS', 0);
define('FILE_DOWNLOAD_COST', 1); // Dosya indirme maliyeti

// Hata raporlama - Force enable
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session güvenlik ayarları (session başlamadan önce)
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    
    // Session başlat
    session_start();
}

// Timezone ayarla
date_default_timezone_set('Europe/Istanbul');

// Güvenlik sınıflarını dahil et (eğer mevcutsa)
if (file_exists(__DIR__ . '/../security/SecurityManager.php')) {
    require_once __DIR__ . '/../security/SecurityManager.php';
}
if (file_exists(__DIR__ . '/../security/SecureDatabase.php')) {
    require_once __DIR__ . '/../security/SecureDatabase.php';
}
if (file_exists(__DIR__ . '/../security/SecurityHeaders.php')) {
    require_once __DIR__ . '/../security/SecurityHeaders.php';
}

// Autoload fonksiyonu
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/../includes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

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
            !strpos($_SERVER['REQUEST_URI'], '.php') === false) {
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

// Helper fonksiyonları - Güvenlik entegrasyonu ile
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
    // URL güvenlik kontrolü
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        // Güvenli redirect kontrolü
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['host']) && $parsedUrl['host'] !== parse_url(SITE_URL, PHP_URL_HOST)) {
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
        $result = $_SESSION['role'] === 'admin';
        error_log("isAdmin() Debug - Role based: {$_SESSION['role']}, Result: " . ($result ? 'true' : 'false'));
        return $result;
    }
    
    // Fallback: is_admin tabanlı kontrol
    $hasSession = isset($_SESSION['is_admin']);
    $adminValue = $hasSession ? $_SESSION['is_admin'] : null;
    $result = $hasSession && ((int)$_SESSION['is_admin'] === 1);
    
    // Debug logging
    error_log("isAdmin() Debug (Fallback) - Has session: " . ($hasSession ? 'yes' : 'no') . 
              ", Admin value: " . ($adminValue !== null ? $adminValue : 'null') . 
              ", Type: " . gettype($adminValue) . 
              ", Result: " . ($result ? 'true' : 'false'));
    
    return $result;
}

function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

// formatFileSize fonksiyonu dosyanın sonunda tanımlandı

function generateToken() {
    return bin2hex(random_bytes(32));
}

// UUID v4 oluşturma fonksiyonu
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// UUID doğrulama fonksiyonu
function isValidUUID($uuid) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}

function generateCsrfToken() {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        return $security->generateCsrfToken();
    }
    
    // Fallback CSRF token
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
    
    // Fallback CSRF validation
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function checkRateLimit($action, $identifier = null, $limit = 10, $window = 300) {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        return $security->checkRateLimit($action, $identifier, $limit, $window);
    }
    
    return true; // Güvenlik devre dışıysa geç
}

function logSecurityEvent($eventType, $details) {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        $security->logSecurityEvent($eventType, $details, $security->getClientIp());
    }
}

function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
    global $security;
    
    // TÜM DOSYA TÜRLERİNE İZİN VER - GÜVENLİK SİSTEMİNİ BYPASS ET
    // Security sistemi aktif olsa bile dosya türü kontrolü yapma!
    
    // Fallback file validation - SADECE TEMEL KONTROLLER
    $errors = [];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Geçersiz dosya yükleme.';
    }
    
    $maxSize = $maxSize ?: MAX_FILE_SIZE;
    if ($file['size'] > $maxSize) {
        $errors[] = 'Dosya boyutu çok büyük (' . formatFileSize($maxSize) . ' maksimum).';
    }
    
    // DOSYA TÜRÜ KONTROLÜ TAMAMEN KALDIRILDI!
    // Artık tüm dosya türleri kabul ediliyor
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'safe_name' => preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name'])
    ];
}

function sendEmail($to, $subject, $message, $isHTML = true) {
    // Email adreslerini sanitize et
    $to = sanitize($to, 'email');
    $subject = sanitize($subject);
    
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Email gönderme fonksiyonu - PHPMailer ile genişletilebilir
    $headers = "From: " . SITE_EMAIL . "\r\n";
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

function checkBruteForce($identifier) {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        return $security->checkBruteForce($identifier, MAX_LOGIN_ATTEMPTS, LOGIN_BLOCK_DURATION);
    }
    
    return true; // Güvenlik devre dışıysa geç
}

function recordBruteForceAttempt($identifier) {
    global $security;
    
    if ($security && SECURITY_ENABLED) {
        $security->recordBruteForceAttempt($identifier);
    }
}

/**
 * Execute a secure database query with optional security features
 * 
 * @param string $query SQL query string
 * @param array $params Query parameters
 * @return PDOStatement|false Query result
 */
function executeSecureQuery($query, $params = []) {
    global $security, $secureDb;
    
    if ($secureDb && $security && SECURITY_ENABLED) {
        return $security->executeSafeQuery($query, $params);
    }
    
    // Fallback normal PDO
    global $pdo;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

// CSRF token'ı otomatik oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCsrfToken();
}

// Global error handler
if (SECURITY_ENABLED) {
    set_error_handler(function($severity, $message, $file, $line) {
        if (error_reporting() & $severity) {
            logSecurityEvent('php_error', [
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'severity' => $severity
            ]);
        }
        return false; // Normal error handling devam etsin
    });
    
    set_exception_handler(function($exception) {
        logSecurityEvent('php_exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    });
}

// Security headers meta tag helper'ı ekle
function renderSecurityMeta() {
    $nonce = class_exists('SecurityHeaders') ? SecurityHeaders::getNonce() : '';
    echo '<meta name="csrf-token" content="' . ($_SESSION['csrf_token'] ?? '') . '">' . "\n";
    echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    return $nonce;
}

// Security script tag helper'ı
function includeSecurityScript() {
    $nonce = class_exists('SecurityHeaders') ? SecurityHeaders::getNonce() : '';
    if (file_exists(__DIR__ . '/../security/security-guard.js')) {
        echo '<script nonce="' . $nonce . '" src="' . SITE_URL . 'security/security-guard.js"></script>' . "\n";
    }
}

// UUID doğrulama fonksiyonu
if (!function_exists('isValidUUID')) {
    function isValidUUID($uuid) {
        if (!is_string($uuid) || empty($uuid)) {
            return false;
        }
        
        // UUID v4 format pattern: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
}

// Dosya boyutu formatlama fonksiyonu
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes === 0 || $bytes === null) return '0 B';
        
        $bytes = (float) $bytes;
        if ($bytes <= 0) return '0 B';
        
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

// Güvenli number_format fonksiyonu (PHP 8.0+ null value fix)
function safe_number_format($num, $decimals = 0, $decimal_separator = '.', $thousands_separator = ',') {
    if ($num === null || $num === '') {
        return '0';
    }
    return number_format((float)$num, $decimals, $decimal_separator, $thousands_separator);
}

// Türkçe karakter temizleme fonksiyonu
if (!function_exists('turkishToEnglish')) {
    function turkishToEnglish($text) {
        $search = array('Ğ','Ü','Ş','İ','Ö','Ç','ğ','ü','ş','ı','ö','ç');
        $replace = array('G','U','S','I','O','C','g','u','s','i','o','c');
        return str_replace($search, $replace, $text);
    }
}

// URL dostu slug oluşturma fonksiyonu
if (!function_exists('createSlug')) {
    function createSlug($text) {
        $text = turkishToEnglish($text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}

// Görüntü dosyası kontrol fonksiyonu
if (!function_exists('isImageFile')) {
    function isImageFile($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, IMAGE_EXTENSIONS);
    }
}

// Sanitization fonksiyonu
if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Redirect fonksiyonu
if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

// Login kontrol fonksiyonları
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
?>
