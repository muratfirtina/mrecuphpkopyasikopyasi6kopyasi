<?php
/**
 * Debug script - Class loading test
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Debug Script - Class Loading Test ===\n<br>";

// 1. Config dosyasını test et
echo "1. Config dosyası yükleniyor...\n<br>";
try {
    require_once 'config/config.php';
    echo "✅ Config dosyası başarıyla yüklendi\n<br>";
    echo "Site adı: " . SITE_NAME . "\n<br>";
} catch (Exception $e) {
    echo "❌ Config hatası: " . $e->getMessage() . "\n<br>";
    die();
}

// 2. Database bağlantısını test et
echo "\n<br>2. Veritabanı bağlantısı test ediliyor...\n<br>";
try {
    require_once 'config/database.php';
    if ($pdo) {
        echo "✅ Veritabanı bağlantısı başarılı\n<br>";
        
        // Database version test
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "MySQL Version: " . $version['version'] . "\n<br>";
    } else {
        echo "❌ PDO nesnesi oluşturulamadı\n<br>";
    }
} catch (Exception $e) {
    echo "❌ Veritabanı hatası: " . $e->getMessage() . "\n<br>";
}

// 3. User sınıfını test et
echo "\n<br>3. User sınıfı test ediliyor...\n<br>";
try {
    if (class_exists('User')) {
        echo "✅ User sınıfı mevcut\n<br>";
        $user = new User($pdo);
        echo "✅ User nesnesi oluşturuldu\n<br>";
        
        // User count test
        $userCount = $user->getUserCount();
        echo "Toplam kullanıcı sayısı: " . $userCount . "\n<br>";
    } else {
        echo "❌ User sınıfı bulunamadı\n<br>";
    }
} catch (Exception $e) {
    echo "❌ User sınıfı hatası: " . $e->getMessage() . "\n<br>";
}

// 4. FileManager sınıfını test et
echo "\n<br>4. FileManager sınıfı test ediliyor...\n<br>";
try {
    if (class_exists('FileManager')) {
        echo "✅ FileManager sınıfı mevcut\n<br>";
        $fileManager = new FileManager($pdo);
        echo "✅ FileManager nesnesi oluşturuldu\n<br>";
        
        // File stats test
        $stats = $fileManager->getFileStats();
        echo "Dosya istatistikleri: " . print_r($stats, true) . "\n<br>";
    } else {
        echo "❌ FileManager sınıfı bulunamadı\n<br>";
    }
} catch (Exception $e) {
    echo "❌ FileManager sınıfı hatası: " . $e->getMessage() . "\n<br>";
}

// 5. Functions test et
echo "\n<br>5. Functions test ediliyor...\n<br>";
if (function_exists('isLoggedIn')) {
    echo "✅ isLoggedIn fonksiyonu mevcut\n<br>";
} else {
    echo "❌ isLoggedIn fonksiyonu bulunamadı\n<br>";
}

if (function_exists('isAdmin')) {
    echo "✅ isAdmin fonksiyonu mevcut\n<br>";
} else {
    echo "❌ isAdmin fonksiyonu bulunamadı\n<br>";
}

if (function_exists('formatFileSize')) {
    echo "✅ formatFileSize fonksiyonu mevcut\n<br>";
} else {
    echo "❌ formatFileSize fonksiyonu bulunamadı\n<br>";
}

// 6. Session test et
echo "\n<br>6. Session test ediliyor...\n<br>";
echo "Session durum: " . session_status() . "\n<br>";
echo "Session ID: " . session_id() . "\n<br>";
echo "Session değişkenleri: " . print_r($_SESSION, true) . "\n<br>";

echo "\n<br>=== Debug Tamamlandı ===\n<br>";
?>
