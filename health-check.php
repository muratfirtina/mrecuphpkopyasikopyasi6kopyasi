<?php
/**
 * Mr ECU - Production Health Check
 * Sistem sağlığı ve konfigürasyon kontrolü
 */

require_once 'config/config.php';

// JSON response için header
header('Content-Type: application/json; charset=utf-8');

// Health check sonuçları
$health = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => defined('DEBUG') && DEBUG ? 'development' : 'production',
    'checks' => []
];

// 1. Veritabanı bağlantısı kontrolü
try {
    if (isset($pdo)) {
        $stmt = $pdo->query('SELECT 1');
        $health['checks']['database'] = [
            'status' => 'OK',
            'message' => 'Database connection successful'
        ];
    } else {
        throw new Exception('PDO not initialized');
    }
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'ERROR',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
    $health['status'] = 'ERROR';
}

// 2. Environment konfigürasyonu
$health['checks']['environment'] = [
    'status' => 'OK',
    'debug_mode' => defined('DEBUG') ? DEBUG : 'undefined',
    'security_enabled' => defined('SECURITY_ENABLED') ? SECURITY_ENABLED : 'undefined',
    'site_url' => defined('SITE_URL') ? SITE_URL : 'undefined'
];

// 3. Güvenlik sistemi kontrolü
if (defined('SECURITY_ENABLED') && SECURITY_ENABLED) {
    try {
        if (class_exists('SecurityManager') && isset($security)) {
            $health['checks']['security'] = [
                'status' => 'OK',
                'message' => 'Security system active'
            ];
        } else {
            $health['checks']['security'] = [
                'status' => 'WARNING',
                'message' => 'Security system not fully loaded'
            ];
        }
    } catch (Exception $e) {
        $health['checks']['security'] = [
            'status' => 'ERROR',
            'message' => 'Security system error: ' . $e->getMessage()
        ];
    }
} else {
    $health['checks']['security'] = [
        'status' => 'WARNING', 
        'message' => 'Security system disabled'
    ];
}

// 4. Upload klasörü kontrolü
$uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/uploads/';
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    $health['checks']['uploads'] = [
        'status' => 'OK',
        'message' => 'Upload directory is writable',
        'path' => $uploadDir
    ];
} else {
    $health['checks']['uploads'] = [
        'status' => 'WARNING',
        'message' => 'Upload directory not writable or missing',
        'path' => $uploadDir
    ];
}

// 5. Logs klasörü kontrolü
$logsDir = __DIR__ . '/logs/';
if (is_dir($logsDir) && is_writable($logsDir)) {
    $health['checks']['logs'] = [
        'status' => 'OK',
        'message' => 'Logs directory is writable'
    ];
} else {
    $health['checks']['logs'] = [
        'status' => 'WARNING',
        'message' => 'Logs directory not writable or missing'
    ];
}

// 6. Session kontrolü
if (session_status() === PHP_SESSION_ACTIVE) {
    $health['checks']['session'] = [
        'status' => 'OK',
        'message' => 'Session system active'
    ];
} else {
    $health['checks']['session'] = [
        'status' => 'WARNING',
        'message' => 'Session not active'
    ];
}

// 7. Email konfigürasyonu kontrolü
if (defined('SMTP_HOST') && defined('SMTP_USERNAME')) {
    $health['checks']['email'] = [
        'status' => 'OK',
        'message' => 'Email configuration present',
        'smtp_host' => SMTP_HOST,
        'test_mode' => defined('EMAIL_TEST_MODE') ? EMAIL_TEST_MODE : false
    ];
} else {
    $health['checks']['email'] = [
        'status' => 'WARNING',
        'message' => 'Email configuration incomplete'
    ];
}

// 8. SSL/HTTPS kontrolü
$isHTTPS = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           $_SERVER['SERVER_PORT'] == 443 ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

$health['checks']['ssl'] = [
    'status' => $isHTTPS ? 'OK' : 'WARNING',
    'message' => $isHTTPS ? 'HTTPS active' : 'HTTP only - SSL recommended for production',
    'https' => $isHTTPS
];

// 9. Kritik dosya varlığı kontrolü
$criticalFiles = [
    '.env',
    '.htaccess', 
    'config/config.php',
    'config/database.php',
    'index.php'
];

$missingFiles = [];
foreach ($criticalFiles as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    $health['checks']['files'] = [
        'status' => 'OK',
        'message' => 'All critical files present'
    ];
} else {
    $health['checks']['files'] = [
        'status' => 'ERROR',
        'message' => 'Missing critical files: ' . implode(', ', $missingFiles),
        'missing_files' => $missingFiles
    ];
    $health['status'] = 'ERROR';
}

// 10. PHP konfigürasyonu kontrolü
$health['checks']['php'] = [
    'status' => 'OK',
    'version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'curl' => extension_loaded('curl'),
        'mbstring' => extension_loaded('mbstring'),
        'json' => extension_loaded('json')
    ]
];

// Gerekli PHP uzantıları kontrolü
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'json'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    $health['checks']['php']['status'] = 'ERROR';
    $health['checks']['php']['missing_extensions'] = $missingExtensions;
    $health['status'] = 'ERROR';
}

// 11. Sistem performans metrikleri
$health['metrics'] = [
    'memory_usage' => [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => ini_get('memory_limit')
    ],
    'disk_space' => [
        'total' => disk_total_space('.'),
        'free' => disk_free_space('.')
    ],
    'server_load' => function_exists('sys_getloadavg') ? sys_getloadavg() : null
];

// 12. Güvenlik kontrolleri
$securityIssues = [];

// .env dosyası public erişime açık mı?
$testEnvAccess = @file_get_contents(SITE_URL . '.env');
if ($testEnvAccess !== false && strlen($testEnvAccess) > 10) {
    $securityIssues[] = '.env file publicly accessible';
}

// config/ klasörü erişilebilir mi?
$testConfigAccess = @file_get_contents(SITE_URL . 'config/config.php');
if ($testConfigAccess !== false && strlen($testConfigAccess) > 10) {
    $securityIssues[] = 'config/ directory publicly accessible';
}

if (empty($securityIssues)) {
    $health['checks']['security_config'] = [
        'status' => 'OK',
        'message' => 'Security configuration appears correct'
    ];
} else {
    $health['checks']['security_config'] = [
        'status' => 'ERROR',
        'message' => 'Security vulnerabilities detected',
        'issues' => $securityIssues
    ];
    $health['status'] = 'ERROR';
}

// Overall health status belirleme
$errorCount = 0;
$warningCount = 0;

foreach ($health['checks'] as $check) {
    if ($check['status'] === 'ERROR') {
        $errorCount++;
    } elseif ($check['status'] === 'WARNING') {
        $warningCount++;
    }
}

if ($errorCount > 0) {
    $health['status'] = 'ERROR';
    $health['message'] = "System has {$errorCount} error(s) and {$warningCount} warning(s)";
} elseif ($warningCount > 0) {
    $health['status'] = 'WARNING';
    $health['message'] = "System has {$warningCount} warning(s)";
} else {
    $health['status'] = 'OK';
    $health['message'] = 'All systems operational';
}

// Response gönder
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
