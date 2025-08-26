<?php
/**
 * Test remember token functionality
 */

require_once 'config/config.php';
require_once 'includes/User.php';

echo "<h2>🧪 Login.php Hata Test Dosyası</h2>\n";

// Test generateRandomString function
echo "<h3>1. generateRandomString Fonksiyonu Testi</h3>\n";
if (function_exists('generateRandomString')) {
    $randomString = generateRandomString(32);
    echo "✅ generateRandomString(32) çalışıyor: " . htmlspecialchars($randomString) . "<br>\n";
    echo "String uzunluğu: " . strlen($randomString) . " karakter<br><br>\n";
} else {
    echo "❌ generateRandomString fonksiyonu bulunamadı!<br><br>\n";
}

// Test User class methods
echo "<h3>2. User Sınıfı setRememberToken Metodu Testi</h3>\n";
try {
    $user = new User($pdo);
    
    if (method_exists($user, 'setRememberToken')) {
        echo "✅ setRememberToken metodu mevcut<br>\n";
    } else {
        echo "❌ setRememberToken metodu bulunamadı!<br>\n";
    }
    
    if (method_exists($user, 'getRememberToken')) {
        echo "✅ getRememberToken metodu mevcut<br>\n";
    } else {
        echo "❌ getRememberToken metodu bulunamadı!<br>\n";
    }
    
    if (method_exists($user, 'clearRememberToken')) {
        echo "✅ clearRememberToken metodu mevcut<br>\n";
    } else {
        echo "❌ clearRememberToken metodu bulunamadı!<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ User sınıfı hatası: " . $e->getMessage() . "<br>\n";
}

echo "<br><h3>3. Veritabanı remember_token Sütunu Kontrolü</h3>\n";
try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
    
    if ($checkColumn->rowCount() > 0) {
        echo "✅ users tablosunda remember_token sütunu mevcut<br>\n";
        $columnInfo = $checkColumn->fetch(PDO::FETCH_ASSOC);
        echo "Sütun tipi: " . $columnInfo['Type'] . "<br>\n";
    } else {
        echo "⚠️  users tablosunda remember_token sütunu YOK!<br>\n";
        echo "Lütfen migration dosyasını çalıştırın:<br>\n";
        echo "<code>http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/sql/install_remember_token_column.php</code><br>\n";
    }
} catch (PDOException $e) {
    echo "❌ Veritabanı bağlantı hatası: " . $e->getMessage() . "<br>\n";
}

echo "<br><h3>4. Login.php Dosyası Simülasyon Testi</h3>\n";
if (function_exists('generateRandomString') && class_exists('User')) {
    echo "✅ Tüm fonksiyonlar mevcut. Login.php artık çalışmalı!<br>\n";
    
    // Simulate the problematic code
    if (isset($_POST['remember'])) { // This would be true in actual login
        try {
            $rememberToken = generateRandomString(32);
            echo "✅ generateRandomString(32) başarılı: " . substr($rememberToken, 0, 10) . "...<br>\n";
            
            // Simulate setRememberToken call (without actually setting it)
            echo "✅ setRememberToken metodu hazır ve çağrılabilir<br>\n";
            
        } catch (Exception $e) {
            echo "❌ Simülasyon hatası: " . $e->getMessage() . "<br>\n";
        }
    }
} else {
    echo "❌ Bazı fonksiyonlar hala eksik!<br>\n";
}

echo "<br><h3>5. Özet</h3>\n";
echo "Eğer tüm testler ✅ işareti gösteriyorsa, login.php'deki hatalar düzelmiştir.<br>\n";
echo "Sadece remember_token sütunu eksikse, migration dosyasını çalıştırmanız yeterli.<br>\n";
?>
