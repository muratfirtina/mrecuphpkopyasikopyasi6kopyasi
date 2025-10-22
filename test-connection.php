<?php
/**
 * MR.ECU Tuning - Database Connection Test
 * Veritabanı bağlantı testi
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Bağlantı Testi - MR.ECU Tuning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .test-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .test-pass {
            border-left: 4px solid #28a745;
        }
        .test-fail {
            border-left: 4px solid #dc3545;
        }
        .badge-custom {
            font-size: 14px;
            padding: 8px 15px;
        }
    </style>
</head>
<body>
    <div class="container test-container">
        <div class="card">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0">
                    <i class="fas fa-database me-2"></i>
                    Database Bağlantı Testi
                </h2>
                <p class="mb-0 mt-2">MR.ECU Tuning Sistem Kontrolü</p>
            </div>
            <div class="card-body p-4">
<?php

$testResults = [];
$allPassed = true;

// Test 1: PHP Version
$phpVersion = phpversion();
$phpTest = version_compare($phpVersion, '7.4.0', '>=');
$testResults[] = [
    'title' => 'PHP Version',
    'status' => $phpTest,
    'message' => "PHP $phpVersion " . ($phpTest ? '✓' : '✗ (Minimum PHP 7.4 gerekli)'),
    'details' => "Mevcut: $phpVersion | Minimum: 7.4.0"
];
if (!$phpTest) $allPassed = false;

// Test 2: PDO Extension
$pdoTest = extension_loaded('pdo') && extension_loaded('pdo_mysql');
$testResults[] = [
    'title' => 'PDO Extension',
    'status' => $pdoTest,
    'message' => 'PDO ve PDO_MySQL ' . ($pdoTest ? 'yüklü ✓' : 'yüklü değil ✗'),
    'details' => $pdoTest ? 'PDO ve PDO_MySQL extension aktif' : 'PDO extension gerekli'
];
if (!$pdoTest) $allPassed = false;

// Test 3: Config dosyası
$configTest = file_exists(__DIR__ . '/config/database.php');
$testResults[] = [
    'title' => 'Config Dosyası',
    'status' => $configTest,
    'message' => 'config/database.php ' . ($configTest ? 'mevcut ✓' : 'bulunamadı ✗'),
    'details' => __DIR__ . '/config/database.php'
];
if (!$configTest) $allPassed = false;

// Test 4: Database bağlantısı
$dbTest = false;
$dbMessage = '';
$dbDetails = [];

if ($configTest) {
    try {
        require_once __DIR__ . '/config/database.php';
        
        if (isset($pdo) && $pdo instanceof PDO) {
            $pdo->query('SELECT 1');
            $dbTest = true;
            $dbMessage = 'Database bağlantısı başarılı ✓';
            
            // Database bilgileri
            $dbDetails[] = 'Host: ' . ($_ENV['DB_HOST'] ?? 'N/A');
            $dbDetails[] = 'Port: ' . ($_ENV['DB_PORT'] ?? 'N/A');
            $dbDetails[] = 'Database: ' . ($_ENV['DB_NAME'] ?? 'N/A');
            $dbDetails[] = 'Charset: ' . ($_ENV['DB_CHARSET'] ?? 'N/A');
        }
    } catch (Exception $e) {
        $dbMessage = 'Database bağlantı hatası ✗';
        $dbDetails[] = 'Hata: ' . $e->getMessage();
        $allPassed = false;
    }
} else {
    $dbMessage = 'Config dosyası bulunamadı ✗';
    $allPassed = false;
}

$testResults[] = [
    'title' => 'Database Bağlantısı',
    'status' => $dbTest,
    'message' => $dbMessage,
    'details' => implode('<br>', $dbDetails)
];

// Test 5: Tablolar
if ($dbTest) {
    try {
        $tables = ['users', 'brands', 'models', 'categories', 'file_uploads', 'credit_transactions', 'system_logs'];
        $existingTables = [];
        $missingTables = [];
        
        foreach ($tables as $table) {
            $check = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
            if ($check > 0) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        }
        
        $tableTest = count($missingTables) == 0;
        $tableMessage = count($existingTables) . '/' . count($tables) . ' tablo mevcut';
        $tableDetails = [];
        
        if (count($existingTables) > 0) {
            $tableDetails[] = '<strong>Mevcut:</strong> ' . implode(', ', $existingTables);
        }
        if (count($missingTables) > 0) {
            $tableDetails[] = '<strong class="text-danger">Eksik:</strong> ' . implode(', ', $missingTables);
            $allPassed = false;
        }
        
        $testResults[] = [
            'title' => 'Database Tabloları',
            'status' => $tableTest,
            'message' => $tableMessage . ($tableTest ? ' ✓' : ' ✗'),
            'details' => implode('<br>', $tableDetails)
        ];
    } catch (Exception $e) {
        $testResults[] = [
            'title' => 'Database Tabloları',
            'status' => false,
            'message' => 'Tablo kontrolü başarısız ✗',
            'details' => 'Hata: ' . $e->getMessage()
        ];
        $allPassed = false;
    }
}

// Test 6: İstatistikler
if ($dbTest) {
    try {
        $stats = [];
        $stats[] = 'Kullanıcılar: ' . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats[] = 'Markalar: ' . $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
        $stats[] = 'Modeller: ' . $pdo->query("SELECT COUNT(*) FROM models")->fetchColumn();
        $stats[] = 'Kategoriler: ' . $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        
        $testResults[] = [
            'title' => 'Database İstatistikleri',
            'status' => true,
            'message' => 'İstatistikler alındı ✓',
            'details' => implode(' | ', $stats)
        ];
    } catch (Exception $e) {
        $testResults[] = [
            'title' => 'Database İstatistikleri',
            'status' => false,
            'message' => 'İstatistik hatası ✗',
            'details' => $e->getMessage()
        ];
    }
}

// Test 7: Write Permission
$uploadDir = __DIR__ . '/uploads';
$writeTest = is_dir($uploadDir) && is_writable($uploadDir);
$testResults[] = [
    'title' => 'Upload Klasörü',
    'status' => $writeTest,
    'message' => 'uploads/ klasörü ' . ($writeTest ? 'yazılabilir ✓' : 'yazılamaz ✗'),
    'details' => $uploadDir . ($writeTest ? ' (Permissions OK)' : ' (Permission denied)')
];
if (!$writeTest) $allPassed = false;

// Sonuçları göster
foreach ($testResults as $result) {
    $statusClass = $result['status'] ? 'test-pass' : 'test-fail';
    $icon = $result['status'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
    
    echo "<div class='test-item {$statusClass}'>";
    echo "<div class='d-flex align-items-start'>";
    echo "<i class='fas {$icon} me-3 fs-4'></i>";
    echo "<div class='flex-grow-1'>";
    echo "<h5 class='mb-1'>{$result['title']}</h5>";
    echo "<p class='mb-1'><strong>{$result['message']}</strong></p>";
    if (!empty($result['details'])) {
        echo "<p class='mb-0 text-muted'><small>{$result['details']}</small></p>";
    }
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

// Genel sonuç
if ($allPassed) {
    echo "
    <div class='alert alert-success mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-check-circle me-2'></i>
            Tüm Testler Başarılı!
        </h4>
        <p class='mb-0'>Sistem tam çalışır durumda. MR.ECU Tuning kullanıma hazır.</p>
    </div>
    ";
} else {
    echo "
    <div class='alert alert-warning mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Bazı Testler Başarısız
        </h4>
        <p class='mb-0'>Yukarıdaki hataları düzeltin ve sistemi kullanmaya başlayın.</p>
    </div>
    ";
}

// PHP Info
echo "
<div class='mt-4'>
    <h5 class='mb-3'>Sistem Bilgileri</h5>
    <div class='table-responsive'>
        <table class='table table-sm table-bordered'>
            <tr>
                <td><strong>PHP Version</strong></td>
                <td>" . phpversion() . "</td>
            </tr>
            <tr>
                <td><strong>Server Software</strong></td>
                <td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td><strong>Document Root</strong></td>
                <td>" . $_SERVER['DOCUMENT_ROOT'] . "</td>
            </tr>
            <tr>
                <td><strong>Max Upload Size</strong></td>
                <td>" . ini_get('upload_max_filesize') . "</td>
            </tr>
            <tr>
                <td><strong>Post Max Size</strong></td>
                <td>" . ini_get('post_max_size') . "</td>
            </tr>
            <tr>
                <td><strong>Memory Limit</strong></td>
                <td>" . ini_get('memory_limit') . "</td>
            </tr>
        </table>
    </div>
</div>
";
?>
                <div class="d-grid gap-2 mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Ana Sayfaya Dön
                    </a>
                    <a href="test-connection.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-2"></i>
                        Testi Yenile
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-white">
            <p class="mb-0">
                <i class="fas fa-info-circle me-1"></i>
                MR.ECU Tuning v2.0 - System Test
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
