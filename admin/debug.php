<?php
/**
 * Admin Panel Debug Sayfası
 * Hata tespiti için
 */

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Panel Debug</h1>";

// 1. Config dosyası kontrolü
echo "<h2>1. Config Dosyası</h2>";
try {
    require_once __DIR__ . '/../config/config.php';
    echo "✅ config.php yüklendi<br>";
    
    // Database bağlantısı kontrol
    if (isset($pdo)) {
        echo "✅ Database bağlantısı var<br>";
    } else {
        echo "❌ Database bağlantısı yok<br>";
    }
} catch (Exception $e) {
    echo "❌ Config hatası: " . $e->getMessage() . "<br>";
}

// 2. TuningModel kontrolü
echo "<h2>2. TuningModel</h2>";
try {
    require_once __DIR__ . '/../includes/TuningModel.php';
    echo "✅ TuningModel.php yüklendi<br>";
    
    $tuning = new TuningModel($pdo);
    echo "✅ TuningModel instance oluşturuldu<br>";
} catch (Exception $e) {
    echo "❌ TuningModel hatası: " . $e->getMessage() . "<br>";
}

// 3. Fonksiyon kontrolleri
echo "<h2>3. Fonksiyon Kontrolleri</h2>";

if (function_exists('isLoggedIn')) {
    echo "✅ isLoggedIn fonksiyonu mevcut<br>";
} else {
    echo "❌ isLoggedIn fonksiyonu yok<br>";
}

if (function_exists('isAdmin')) {
    echo "✅ isAdmin fonksiyonu mevcut<br>";
} else {
    echo "❌ isAdmin fonksiyonu yok<br>";
}

if (function_exists('isValidUUID')) {
    echo "✅ isValidUUID fonksiyonu mevcut<br>";
} else {
    echo "❌ isValidUUID fonksiyonu yok<br>";
}

if (function_exists('sanitize')) {
    echo "✅ sanitize fonksiyonu mevcut<br>";
} else {
    echo "❌ sanitize fonksiyonu yok<br>";
}

// 4. Database tablo kontrolleri
echo "<h2>4. Database Tabloları</h2>";
try {
    $tables = ['brands', 'models', 'series', 'engines', 'stages'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "✅ $table tablosu: $count kayıt<br>";
    }
} catch (Exception $e) {
    echo "❌ Database hatası: " . $e->getMessage() . "<br>";
}

// 5. Session kontrolleri
echo "<h2>5. Session Kontrolleri</h2>";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ Session aktif<br>";
    echo "Session ID: " . session_id() . "<br>";
    if (isset($_SESSION['user_id'])) {
        echo "✅ Kullanıcı girişi var (ID: " . $_SESSION['user_id'] . ")<br>";
    } else {
        echo "❌ Kullanıcı girişi yok<br>";
    }
} else {
    echo "❌ Session aktif değil<br>";
}

// 6. Test sorgusu
echo "<h2>6. Test Sorgusu</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM stages WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "✅ Aktif stage sayısı: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ Sorgu hatası: " . $e->getMessage() . "<br>";
}

echo "<br><a href='tuning-management.php'>← Admin Panel'e Dön</a>";
?>
