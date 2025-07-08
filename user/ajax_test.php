<?php
/**
 * AJAX Test Dosyası
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test başladı...\n";

try {
    require_once '../config/config.php';
    echo "Config yüklendi.\n";
    
    require_once '../config/database.php';
    echo "Database yüklendi.\n";
    
    if (!isLoggedIn()) {
        echo "Kullanıcı giriş yapmamış.\n";
    } else {
        echo "Kullanıcı giriş yapmış. User ID: " . $_SESSION['user_id'] . "\n";
    }
    
    echo "PDO bağlantısı: " . ($pdo ? "Başarılı" : "Başarısız") . "\n";
    
    if ($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM credit_transactions LIMIT 1");
        echo "Veritabanı sorgusu: Başarılı\n";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
    echo "Dosya: " . $e->getFile() . "\n";
    echo "Satır: " . $e->getLine() . "\n";
}
?>