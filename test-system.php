<?php
/**
 * MR.ECU Tuning - Complete System Test
 * Kapsamlı dosya sistemi ve fonksiyon testleri
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Testi - MR.ECU Tuning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .test-category {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .test-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #ccc;
        }
        .test-pass {
            border-left-color: #28a745;
        }
        .test-fail {
            border-left-color: #dc3545;
        }
        .test-warning {
            border-left-color: #ffc107;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container test-container">
        <div class="card">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>
                    MR.ECU Tuning Sistem Testi
                </h2>
                <p class="mb-0 mt-2">Kapsamlı Sistem Kontrolü</p>
            </div>
            <div class="card-body p-4">

<?php
$allTests = [];
$passCount = 0;
$failCount = 0;
$warningCount = 0;

// KATEGORİ 1: Dosya Sistemi Testleri
echo "<div class='test-category'>";
echo "<h4 class='mb-3'><i class='fas fa-folder me-2'></i>Dosya Sistemi Testleri</h4>";

$fileTests = [
    ['file' => 'config/database.php', 'required' => true],
    ['file' => 'includes/User.php', 'required' => true],
    ['file' => 'includes/functions.php', 'required' => true],
    ['file' => 'includes/FileManager.php', 'required' => false],
    ['file' => 'includes/SecurityManager.php', 'required' => false],
    ['file' => 'login.php', 'required' => true],
    ['file' => 'register.php', 'required' => true],
    ['file' => 'index.php', 'required' => true],
];

foreach ($fileTests as $test) {
    $filePath = __DIR__ . '/' . $test['file'];
    $exists = file_exists($filePath);
    $status = $exists ? 'pass' : ($test['required'] ? 'fail' : 'warning');
    
    if ($status === 'pass') $passCount++;
    elseif ($status === 'fail') $failCount++;
    else $warningCount++;
    
    $icon = $exists ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
    $cssClass = $exists ? 'test-pass' : ($test['required'] ? 'test-fail' : 'test-warning');
    
    echo "<div class='test-item {$cssClass}'>";
    echo "<i class='fas {$icon} me-2'></i>";
    echo "<strong>{$test['file']}</strong> ";
    echo $exists ? '✓' : ($test['required'] ? '✗ (Gerekli)' : '⚠ (Opsiyonel)');
    echo "</div>";
}

echo "</div>";

// KATEGORİ 2: Klasör Testleri
echo "<div class='test-category'>";
echo "<h4 class='mb-3'><i class='fas fa-folder-open me-2'></i>Klasör Testleri</h4>";

$folderTests = [
    ['folder' => 'uploads', 'writable' => true],
    ['folder' => 'logs', 'writable' => true],
    ['folder' => 'cache', 'writable' => true],
    ['folder' => 'config', 'writable' => false],
    ['folder' => 'includes', 'writable' => false],
];

foreach ($folderTests as $test) {
    $folderPath = __DIR__ . '/' . $test['folder'];
    $exists = is_dir($folderPath);
    $writable = $exists && is_writable($folderPath);
    
    $status = 'pass';
    if (!$exists) {
        $status = 'fail';
        $failCount++;
    } elseif ($test['writable'] && !$writable) {
        $status = 'warning';
        $warningCount++;
    } else {
        $passCount++;
    }
    
    $message = '';
    if (!$exists) $message = ' ✗ Bulunamadı';
    elseif ($test['writable'] && !$writable) $message = ' ⚠ Yazılamaz';
    else $message = ' ✓ ' . ($test['writable'] ? 'Yazılabilir' : 'Okunabilir');
    
    $cssClass = $status === 'pass' ? 'test-pass' : ($status === 'fail' ? 'test-fail' : 'test-warning');
    
    echo "<div class='test-item {$cssClass}'>";
    echo "<i class='fas fa-folder me-2'></i>";
    echo "<strong>{$test['folder']}/</strong>";
    echo $message;
    echo "</div>";
}

echo "</div>";

// KATEGORİ 3: PHP Fonksiyon Testleri
echo "<div class='test-category'>";
echo "<h4 class='mb-3'><i class='fas fa-code me-2'></i>PHP Fonksiyon Testleri</h4>";

if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

$functionTests = [
    'generateUUID',
    'isValidUUID',
    'sanitizeInput',
    'formatFileSize',
    'logSecurityEvent',
];

foreach ($functionTests as $funcName) {
    $exists = function_exists($funcName);
    
    if ($exists) $passCount++;
    else $failCount++;
    
    $icon = $exists ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
    $cssClass = $exists ? 'test-pass' : 'test-fail';
    
    echo "<div class='test-item {$cssClass}'>";
    echo "<i class='fas {$icon} me-2'></i>";
    echo "<code>{$funcName}()</code> ";
    echo $exists ? '✓ Mevcut' : '✗ Bulunamadı';
    echo "</div>";
}

echo "</div>";

// KATEGORİ 4: Database Testi
echo "<div class='test-category'>";
echo "<h4 class='mb-3'><i class='fas fa-database me-2'></i>Database Testleri</h4>";

try {
    // Bağlantı testi
    $pdo->query('SELECT 1');
    $passCount++;
    echo "<div class='test-item test-pass'>";
    echo "<i class='fas fa-check-circle text-success me-2'></i>";
    echo "<strong>Database Bağlantısı</strong> ✓ Başarılı";
    echo "</div>";
    
    // Tablo testleri
    $requiredTables = ['users', 'brands', 'models', 'categories', 'file_uploads', 'credit_transactions', 'system_logs'];
    
    foreach ($requiredTables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
        
        if ($check > 0) {
            $passCount++;
            echo "<div class='test-item test-pass'>";
            echo "<i class='fas fa-table text-success me-2'></i>";
            echo "<strong>Tablo: $table</strong> ✓ Mevcut";
            
            // Kayıt sayısı
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo " <span class='badge bg-info'>$count kayıt</span>";
            echo "</div>";
        } else {
            $failCount++;
            echo "<div class='test-item test-fail'>";
            echo "<i class='fas fa-times-circle text-danger me-2'></i>";
            echo "<strong>Tablo: $table</strong> ✗ Bulunamadı";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    $failCount++;
    echo "<div class='test-item test-fail'>";
    echo "<i class='fas fa-times-circle text-danger me-2'></i>";
    echo "<strong>Database Hatası:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";

// KATEGORİ 5: Class Testleri
echo "<div class='test-category'>";
echo "<h4 class='mb-3'><i class='fas fa-cube me-2'></i>PHP Class Testleri</h4>";

$classTests = [
    ['file' => 'includes/User.php', 'class' => 'User'],
    ['file' => 'includes/FileManager.php', 'class' => 'FileManager'],
    ['file' => 'includes/SecurityManager.php', 'class' => 'SecurityManager'],
];

foreach ($classTests as $test) {
    $filePath = __DIR__ . '/' . $test['file'];
    
    if (file_exists($filePath)) {
        require_once $filePath;
        $exists = class_exists($test['class']);
        
        if ($exists) {
            $passCount++;
            $cssClass = 'test-pass';
            $icon = 'fa-check-circle text-success';
            $message = '✓ Yüklenebilir';
        } else {
            $warningCount++;
            $cssClass = 'test-warning';
            $icon = 'fa-exclamation-triangle text-warning';
            $message = '⚠ Class tanımlı değil';
        }
    } else {
        $warningCount++;
        $cssClass = 'test-warning';
        $icon = 'fa-exclamation-triangle text-warning';
        $message = '⚠ Dosya bulunamadı';
    }
    
    echo "<div class='test-item {$cssClass}'>";
    echo "<i class='fas {$icon} me-2'></i>";
    echo "<strong>{$test['class']}</strong> $message";
    echo "</div>";
}

echo "</div>";

// KATEGORİ 6: İstatistikler
echo "<div class='test-category'>";
echo "<h4 class='mb-3'><i class='fas fa-chart-bar me-2'></i>Sistem İstatistikleri</h4>";

echo "<div class='row'>";

try {
    // Kullanıcı sayısı
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    
    echo "<div class='col-md-3'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-value'>{$userCount}</div>";
    echo "<div class='text-muted'>Toplam Kullanıcı</div>";
    echo "<small class='text-muted'>{$adminCount} Admin</small>";
    echo "</div>";
    echo "</div>";
    
    // Marka sayısı
    $brandCount = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
    
    echo "<div class='col-md-3'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-value'>{$brandCount}</div>";
    echo "<div class='text-muted'>Markalar</div>";
    echo "</div>";
    echo "</div>";
    
    // Model sayısı
    $modelCount = $pdo->query("SELECT COUNT(*) FROM models")->fetchColumn();
    
    echo "<div class='col-md-3'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-value'>{$modelCount}</div>";
    echo "<div class='text-muted'>Modeller</div>";
    echo "</div>";
    echo "</div>";
    
    // Dosya sayısı
    $fileCount = $pdo->query("SELECT COUNT(*) FROM file_uploads")->fetchColumn();
    
    echo "<div class='col-md-3'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-value'>{$fileCount}</div>";
    echo "<div class='text-muted'>Dosyalar</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='col-12'>";
    echo "<div class='alert alert-warning'>";
    echo "İstatistikler alınamadı: " . $e->getMessage();
    echo "</div>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// GENEL SONUÇ
$totalTests = $passCount + $failCount + $warningCount;
$passPercentage = round(($passCount / $totalTests) * 100, 1);

echo "<div class='mt-4'>";
echo "<div class='row'>";

echo "<div class='col-md-4'>";
echo "<div class='stat-box bg-success text-white'>";
echo "<div class='stat-value'>{$passCount}</div>";
echo "<div>Başarılı Test</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='stat-box bg-danger text-white'>";
echo "<div class='stat-value'>{$failCount}</div>";
echo "<div>Başarısız Test</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='stat-box bg-warning text-white'>";
echo "<div class='stat-value'>{$warningCount}</div>";
echo "<div>Uyarı</div>";
echo "</div>";
echo "</div>";

echo "</div>";
echo "</div>";

// Özet
if ($failCount === 0) {
    echo "
    <div class='alert alert-success mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-check-circle me-2'></i>
            Tüm Testler Başarılı! ({$passPercentage}%)
        </h4>
        <p class='mb-0'>Sistem tam çalışır durumda ve kullanıma hazır.</p>
    </div>
    ";
} elseif ($failCount < 5) {
    echo "
    <div class='alert alert-warning mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Bazı Testler Başarısız ({$passPercentage}% Başarılı)
        </h4>
        <p class='mb-0'>Sistem çalışabilir ancak bazı özellikler eksik olabilir.</p>
    </div>
    ";
} else {
    echo "
    <div class='alert alert-danger mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-times-circle me-2'></i>
            Kritik Hatalar Mevcut ({$passPercentage}% Başarılı)
        </h4>
        <p class='mb-0'>Sistem düzgün çalışmayabilir. Hataları düzeltin.</p>
    </div>
    ";
}
?>

                <div class="d-grid gap-2 mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Ana Sayfaya Dön
                    </a>
                    <a href="test-system.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-2"></i>
                        Testi Yenile
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-white">
            <p class="mb-0">
                <i class="fas fa-info-circle me-1"></i>
                MR.ECU Tuning v2.0 - Complete System Test
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
