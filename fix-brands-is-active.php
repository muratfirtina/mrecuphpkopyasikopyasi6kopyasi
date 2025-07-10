<?php
/**
 * Mr ECU - Brands ve Models tablolarına is_active sütunu ekleme
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Brands ve Models Tabloları is_active Sütunu Kontrolü</h2>";

try {
    // Brands tablosunu kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'is_active'");
    $brandColumn = $stmt->fetch();
    
    if (!$brandColumn) {
        echo "<p>Brands tablosuna is_active sütunu ekleniyor...</p>";
        $pdo->exec("ALTER TABLE brands ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "<p style='color: green;'>✓ Brands tablosuna is_active sütunu eklendi.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Brands tablosunda is_active sütunu zaten mevcut.</p>";
    }
    
    // Models tablosunu kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM models LIKE 'is_active'");
    $modelColumn = $stmt->fetch();
    
    if (!$modelColumn) {
        echo "<p>Models tablosuna is_active sütunu ekleniyor...</p>";
        $pdo->exec("ALTER TABLE models ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "<p style='color: green;'>✓ Models tablosuna is_active sütunu eklendi.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Models tablosunda is_active sütunu zaten mevcut.</p>";
    }
    
    // Mevcut kayıtları güncelle (NULL olanları 1 yap)
    $pdo->exec("UPDATE brands SET is_active = 1 WHERE is_active IS NULL");
    $pdo->exec("UPDATE models SET is_active = 1 WHERE is_active IS NULL");
    
    echo "<p style='color: green;'>✓ Mevcut kayıtlar güncellendi.</p>";
    
    echo "<h3>Kontrol:</h3>";
    
    // Brands kontrolü
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM brands");
    $brandStats = $stmt->fetch();
    echo "<p>Brands: {$brandStats['total']} toplam, {$brandStats['active']} aktif</p>";
    
    // Models kontrolü
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM models");
    $modelStats = $stmt->fetch();
    echo "<p>Models: {$modelStats['total']} toplam, {$modelStats['active']} aktif</p>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 İşlem tamamlandı! Artık brands.php sayfası hatasız çalışacak.</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
</style>
