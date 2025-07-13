<?php
// Error reporting'i açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Revision Detail Error Debug</h1>";

echo "<h2>1. Temel Testler</h2>";

try {
    echo "<p>✅ PHP çalışıyor</p>";
    
    // Config dosyaları test et
    if (file_exists('../config/config.php')) {
        echo "<p>✅ config.php mevcut</p>";
        require_once '../config/config.php';
        echo "<p>✅ config.php yüklendi</p>";
    } else {
        echo "<p>❌ config.php bulunamadı</p>";
    }
    
    if (file_exists('../config/database.php')) {
        echo "<p>✅ database.php mevcut</p>";
        require_once '../config/database.php';
        echo "<p>✅ database.php yüklendi</p>";
    } else {
        echo "<p>❌ database.php bulunamadı</p>";
    }
    
    // Session başlat ve kontrol et
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "<p>✅ Session başlatıldı</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'YOK') . "</p>";
    
    // Fonksiyon kontrolü
    if (function_exists('isLoggedIn')) {
        echo "<p>✅ isLoggedIn fonksiyonu mevcut</p>";
        if (isLoggedIn()) {
            echo "<p>✅ Kullanıcı giriş yapmış</p>";
        } else {
            echo "<p>❌ Kullanıcı giriş yapmamış!</p>";
        }
    } else {
        echo "<p>❌ isLoggedIn fonksiyonu bulunamadı!</p>";
    }
    
    // Sınıf kontrolü
    if (class_exists('User')) {
        echo "<p>✅ User sınıfı mevcut</p>";
        if (isset($pdo)) {
            $user = new User($pdo);
            echo "<p>✅ User sınıfı oluşturuldu</p>";
        } else {
            echo "<p>❌ PDO bulunamadı!</p>";
        }
    } else {
        echo "<p>❌ User sınıfı bulunamadı!</p>";
    }
    
    if (class_exists('FileManager')) {
        echo "<p>✅ FileManager sınıfı mevcut</p>";
        if (isset($pdo)) {
            $fileManager = new FileManager($pdo);
            echo "<p>✅ FileManager sınıfı oluşturuldu</p>";
        }
    } else {
        echo "<p>❌ FileManager sınıfı bulunamadı!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ HATA: " . $e->getMessage() . "</p>";
    echo "<p>Dosya: " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
}

// Revision detail test
echo "<h2>2. Revision Detail Test</h2>";

$testId = "fd7e1b41-4d49-4b3e-858e-10df474b5074";
echo "<p><a href='revision-detail.php?id=" . $testId . "' style='background: blue; color: white; padding: 10px; text-decoration: none;'>🔗 Revision Detail Test</a></p>";

// Include test
echo "<h2>3. Include Files Test</h2>";
$includes = [
    '../includes/user_header.php',
    '../includes/user_footer.php'
];

foreach ($includes as $file) {
    if (file_exists($file)) {
        echo "<p>✅ " . $file . " mevcut</p>";
    } else {
        echo "<p>❌ " . $file . " bulunamadı!</p>";
    }
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
