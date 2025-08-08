<?php
// Debug için basit test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug test başladı...<br>";

try {
    require_once 'config/config.php';
    echo "Config yüklendi...<br>";
    
    require_once 'config/database.php';
    echo "Database yüklendi...<br>";
    
    echo "Test tamamlandı!";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
} catch (ParseError $e) {
    echo "Syntax Hatası: " . $e->getMessage();
}
?>
