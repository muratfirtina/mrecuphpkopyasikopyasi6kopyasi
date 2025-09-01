<?php
/**
 * Mr ECU - Hızlı Proje Başlatma Kontrol Dosyası
 * Projeyi başlatmadan önce tüm kontrolleri yapar
 */

// Start output buffering to prevent header issues
ob_start();

// Include config BEFORE any HTML output
try {
    require_once 'config/config.php';
    require_once 'config/database.php';
} catch (Exception $e) {
    // Config loading error - will handle below
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Mr ECU - Proje Başlatma Kontrol</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        .status-card { 
            margin: 15px 0; 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .success { background: linear-gradient(135deg, #d4edda, #c3e6cb); border-left: 5px solid #28a745; }
        .error { background: linear-gradient(135deg, #f8d7da, #f5c6cb); border-left: 5px solid #dc3545; }
        .warning { background: linear-gradient(135deg, #fff3cd, #ffeaa7); border-left: 5px solid #ffc107; }
        .header-box {
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        .action-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            margin: 5px;
        }
        .step-box {
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <div class='header-box'>
        <h1><i class='bi bi-rocket me-3'></i>Mr ECU Proje Başlatma Kontrol</h1>
        <p class='lead mb-0'>Sisteminizi başlatmadan önce tüm kontrolleri yapıyoruz...</p>
    </div>";

$checks = [];
$allPassed = true;

// 1. PHP Extensions Check
echo "<div class='step-box'>";
echo "<h4><i class='bi bi-code me-2'></i>1. PHP Extensions Kontrolü</h4>";

$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='status-card success p-3'>";
        echo "<i class='bi bi-check-circle text-success me-2'></i>";
        echo "<strong>✅ $ext</strong> - Yüklü";
        echo "</div>";
    } else {
        echo "<div class='status-card error p-3'>";
        echo "<i class='bi bi-clock-history text-danger me-2'></i>";
        echo "<strong>❌ $ext</strong> - Eksik";
        echo "</div>";
        $allPassed = false;
    }
}
echo "</div>";

// 2. Config Files Check
echo "<div class='step-box'>";
echo "<h4><i class='bi bi-cog me-2'></i>2. Konfigürasyon Dosyaları</h4>";

$config_files = [
    'config/config.php' => 'Ana konfigürasyon',
    'config/database.php' => 'Veritabanı ayarları',
    'includes/User.php' => 'User sınıfı',
    'includes/FileManager.php' => 'FileManager sınıfı'
];

foreach ($config_files as $file => $desc) {
    if (file_exists($file)) {
        echo "<div class='status-card success p-3'>";
        echo "<i class='bi bi-check-circle text-success me-2'></i>";
        echo "<strong>✅ $desc</strong> - $file mevcut";
        echo "</div>";
    } else {
        echo "<div class='status-card error p-3'>";
        echo "<i class='bi bi-clock-history text-danger me-2'></i>";
        echo "<strong>❌ $desc</strong> - $file eksik";
        echo "</div>";
        $allPassed = false;
    }
}
echo "</div>";

// 3. Database Connection Test
echo "<div class='step-box'>";
echo "<h4><i class='bi bi-database me-2'></i>3. Veritabanı Bağlantı Testi</h4>";

try {
    // Database should be already included above
    if (isset($pdo) && $pdo && $pdo instanceof PDO) {
        echo "<div class='status-card success p-3'>";
        echo "<i class='bi bi-check-circle text-success me-2'></i>";
        echo "<strong>✅ Veritabanı Bağlantısı</strong> - Başarılı";
        
        // Database name check
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        $dbName = $result['db_name'];
        echo "<br><small>Veritabanı: <strong>$dbName</strong></small>";
        echo "</div>";
        
        // Check if it's GUID database
        if ($dbName === 'mrecu_db_guid') {
            echo "<div class='status-card success p-3'>";
            echo "<i class='bi bi-shield-alt text-success me-2'></i>";
            echo "<strong>✅ GUID Veritabanı</strong> - Doğru veritabanına bağlı";
            echo "</div>";
        } else {
            echo "<div class='status-card warning p-3'>";
            echo "<i class='bi bi-exclamation-triangle text-warning me-2'></i>";
            echo "<strong>⚠️ Veritabanı Uyarısı</strong> - '$dbName' kullanılıyor, 'mrecu_db_guid' bekleniyor";
            echo "</div>";
        }
        
    } else {
        // Try to connect manually if not connected
        if (!isset($pdo)) {
            require_once 'config/database.php';
        }
        
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "<div class='status-card success p-3'>";
            echo "<i class='bi bi-check-circle text-success me-2'></i>";
            echo "<strong>✅ Veritabanı Bağlantısı</strong> - Başarılı (Manuel bağlantı)";
            echo "</div>";
        } else {
            throw new Exception("PDO bağlantısı başarısız");
        }
    }
} catch (Exception $e) {
    echo "<div class='status-card error p-3'>";
    echo "<i class='bi bi-clock-history text-danger me-2'></i>";
    echo "<strong>❌ Veritabanı Hatası:</strong> " . $e->getMessage();
    echo "</div>";
    $allPassed = false;
}
echo "</div>";

// 4. Tables Check
if (isset($pdo) && $pdo) {
    echo "<div class='step-box'>";
    echo "<h4><i class='bi bi-table me-2'></i>4. GUID Tabloları Kontrolü</h4>";
    
    $required_tables = ['users', 'brands', 'models', 'file_uploads', 'file_responses', 'revisions'];
    $table_count = 0;
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table LIMIT 1");
            $count = $stmt->fetch()['count'];
            
            echo "<div class='status-card success p-3'>";
            echo "<i class='bi bi-check-circle text-success me-2'></i>";
            echo "<strong>✅ $table</strong> - $count kayıt";
            echo "</div>";
            $table_count++;
        } catch (Exception $e) {
            echo "<div class='status-card error p-3'>";
            echo "<i class='bi bi-clock-history text-danger me-2'></i>";
            echo "<strong>❌ $table</strong> - Tablo bulunamadı";
            echo "</div>";
            $allPassed = false;
        }
    }
    echo "</div>";
}

// 5. GUID Functions Check
echo "<div class='step-box'>";
echo "<h4><i class='bi bi-key me-2'></i>5. GUID Fonksiyonları</h4>";

try {
    require_once 'config/config.php';
    
    if (function_exists('generateUUID') && function_exists('isValidUUID')) {
        $testUuid = generateUUID();
        if (isValidUUID($testUuid)) {
            echo "<div class='status-card success p-3'>";
            echo "<i class='bi bi-check-circle text-success me-2'></i>";
            echo "<strong>✅ GUID Fonksiyonları</strong> - Çalışıyor";
            echo "<br><small>Test UUID: <code>$testUuid</code></small>";
            echo "</div>";
        } else {
            throw new Exception("GUID validation failed");
        }
    } else {
        throw new Exception("GUID functions not found");
    }
} catch (Exception $e) {
    echo "<div class='status-card error p-3'>";
    echo "<i class='bi bi-clock-history text-danger me-2'></i>";
    echo "<strong>❌ GUID Fonksiyonları:</strong> " . $e->getMessage();
    echo "</div>";
    $allPassed = false;
}
echo "</div>";

// Final Status and Actions
echo "<div class='step-box text-center'>";
if ($allPassed) {
    echo "<div class='alert alert-success'>";
    echo "<h3><i class='bi bi-rocket me-2'></i>🎉 Sistem Hazır!</h3>";
    echo "<p class='mb-3'>Tüm kontroller başarılı. Projenizi başlatabilirsiniz!</p>";
    
    echo "<h5>🚀 Hızlı Başlatma Linkleri:</h5>";
    echo "<div class='d-flex flex-wrap justify-content-center'>";
    echo "<a href='index.php' class='btn btn-success action-btn'><i class='bi bi-home me-2'></i>Ana Sayfa</a>";
    echo "<a href='login.php' class='btn btn-primary action-btn'><i class='bi bi-sign-in-alt me-2'></i>Admin Girişi</a>";
    echo "<a href='register.php' class='btn btn-info action-btn'><i class='bi bi-user-plus me-2'></i>Kayıt Ol</a>";
    echo "<a href='admin/' class='btn btn-warning action-btn'><i class='bi bi-cog me-2'></i>Admin Panel</a>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h6>🔧 Test ve Kontrol Araçları:</h6>";
    echo "<div class='d-flex flex-wrap justify-content-center'>";
    echo "<a href='final-guid-migration-complete.php' class='btn btn-outline-primary action-btn'><i class='bi bi-clipboard-check me-2'></i>GUID Test</a>";
    echo "<a href='test-guid-system.php' class='btn btn-outline-info action-btn'><i class='bi bi-vial me-2'></i>Sistem Test</a>";
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<div class='alert alert-danger'>";
    echo "<h3><i class='bi bi-exclamation-triangle me-2'></i>❌ Sorunlar Var!</h3>";
    echo "<p class='mb-3'>Bazı kontroller başarısız. Lütfen hataları düzeltin.</p>";
    
    echo "<h6>🔧 Olası Çözümler:</h6>";
    echo "<div class='d-flex flex-wrap justify-content-center'>";
    echo "<a href='config/install-guid.php' class='btn btn-danger action-btn'><i class='bi bi-database me-2'></i>GUID DB Kur</a>";
    echo "<a href='config/install.php' class='btn btn-warning action-btn'><i class='bi bi-wrench me-2'></i>Temel Kurulum</a>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

echo "<div class='text-center mt-4 mb-5'>";
echo "<small class='text-muted'>Son kontrol: " . date('Y-m-d H:i:s') . "</small><br>";
echo "<a href='project-startup-check.php' class='btn btn-sm btn-outline-secondary mt-2'><i class='bi bi-sync me-1'></i>Kontrolleri Yenile</a>";
echo "</div>";

echo "</div>";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body></html>";
?>
