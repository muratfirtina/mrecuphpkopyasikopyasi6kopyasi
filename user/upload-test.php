<?php
/**
 * Upload.php Debug Test Sayfası
 */

// Force error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Upload.php Debug Test</h2>";
echo "<p>1. Test başladı...</p>";

// Config test
echo "<p>2. Config dosyası test ediliyor...</p>";
try {
    require_once '../config/config.php';
    echo "<p style='color: green;'>✓ Config dosyası başarıyla yüklendi</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Config hatası: " . $e->getMessage() . "</p>";
    exit;
}

// Database test
echo "<p>3. Database dosyası test ediliyor...</p>";
try {
    require_once '../config/database.php';
    echo "<p style='color: green;'>✓ Database dosyası başarıyla yüklendi</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database hatası: " . $e->getMessage() . "</p>";
    exit;
}

// PDO kontrol
echo "<p>4. PDO bağlantısı kontrol ediliyor...</p>";
if (isset($pdo) && $pdo !== null) {
    echo "<p style='color: green;'>✓ PDO bağlantısı mevcut</p>";
    
    // Database connectivity test
    try {
        $stmt = $pdo->query("SELECT 1");
        echo "<p style='color: green;'>✓ Database bağlantısı çalışıyor</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Database bağlantı hatası: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ PDO bağlantısı kurulamadı</p>";
    exit;
}

// Functions test
echo "<p>5. Fonksiyonlar test ediliyor...</p>";
if (function_exists('isLoggedIn')) {
    echo "<p style='color: green;'>✓ isLoggedIn fonksiyonu mevcut</p>";
} else {
    echo "<p style='color: red;'>✗ isLoggedIn fonksiyonu bulunamadı</p>";
}

// Session test
echo "<p>6. Session test ediliyor...</p>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✓ Session aktif</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<p style='color: green;'>✓ User ID session'da mevcut: " . $_SESSION['user_id'] . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ User ID session'da yok (giriş yapmamış)</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Session aktif değil</p>";
}

// Class loading test
echo "<p>7. Sınıflar test ediliyor...</p>";
try {
    $user = new User($pdo);
    echo "<p style='color: green;'>✓ User sınıfı başarıyla oluşturuldu</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ User sınıfı hatası: " . $e->getMessage() . "</p>";
}

try {
    $fileManager = new FileManager($pdo);
    echo "<p style='color: green;'>✓ FileManager sınıfı başarıyla oluşturuldu</p>";
    
    // Brands test
    $brands = $fileManager->getBrands();
    echo "<p style='color: green;'>✓ Markalar getirilebildi, adet: " . count($brands) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ FileManager sınıfı hatası: " . $e->getMessage() . "</p>";
}

// Header test
echo "<p>8. Header dosyası test ediliyor...</p>";
try {
    // Header dosyasını test et ama include etme (çünkü HTML output başlatır)
    if (file_exists('../includes/user_header.php')) {
        echo "<p style='color: green;'>✓ user_header.php dosyası mevcut</p>";
        
        // Syntax check
        $headerContent = file_get_contents('../includes/user_header.php');
        if ($headerContent !== false) {
            echo "<p style='color: green;'>✓ user_header.php dosyası okunabilir</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ user_header.php dosyası bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Header test hatası: " . $e->getMessage() . "</p>";
}

echo "<h3 style='color: blue;'>Test tamamlandı!</h3>";
echo "<p><a href='upload.php'>Upload.php sayfasını tekrar deneyin</a></p>";
?>
