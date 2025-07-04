<?php
/**
 * Brands Unique Constraint Ekleme
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die("Bu sayfaya erişim izniniz yok!");
}

echo "<h1>🔧 Brands Unique Constraint Ekleme</h1>";

try {
    // Önce mevcut constraint'leri kontrol et
    $stmt = $pdo->query("SHOW INDEX FROM brands WHERE Key_name != 'PRIMARY'");
    $indexes = $stmt->fetchAll();
    
    echo "<h2>1. Mevcut Index'ler:</h2>";
    if (empty($indexes)) {
        echo "<p>Hiç unique index bulunamadı.</p>";
    } else {
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Index Adı</th><th>Kolon</th><th>Unique</th></tr>";
        foreach ($indexes as $index) {
            echo "<tr>";
            echo "<td>{$index['Key_name']}</td>";
            echo "<td>{$index['Column_name']}</td>";
            echo "<td>" . ($index['Non_unique'] == 0 ? 'Evet' : 'Hayır') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Unique constraint ekle
    echo "<h2>2. Unique Constraint Ekleme:</h2>";
    
    // Önce çift kayıt var mı kontrol et
    $stmt = $pdo->query("
        SELECT name, COUNT(*) as count 
        FROM brands 
        GROUP BY name 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (!empty($duplicates)) {
        echo "<p style='color:red;'>❌ Çift kayıtlar mevcut! Önce temizleme yapılmalı:</p>";
        foreach ($duplicates as $dup) {
            echo "<p>• {$dup['name']} ({$dup['count']} adet)</p>";
        }
        echo "<p><a href='brands-clean.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Önce Temizle</a></p>";
    } else {
        echo "<p style='color:green;'>✅ Çift kayıt yok, unique constraint eklenebilir.</p>";
        
        // Unique constraint ekle
        try {
            $pdo->exec("ALTER TABLE brands ADD UNIQUE KEY unique_brand_name (name)");
            echo "<p style='color:green;'>✅ Unique constraint başarıyla eklendi!</p>";
            
            // Test et
            echo "<h3>3. Test:</h3>";
            try {
                $pdo->prepare("INSERT INTO brands (name) VALUES ('TEST_DUPLICATE')")->execute();
                $pdo->prepare("INSERT INTO brands (name) VALUES ('TEST_DUPLICATE')")->execute(); // Bu hata vermeli
                echo "<p style='color:red;'>❌ Test başarısız - çift kayıt eklenebildi!</p>";
            } catch (PDOException $e) {
                echo "<p style='color:green;'>✅ Test başarılı - çift kayıt engellendiği:</p>";
                echo "<p><code>" . $e->getMessage() . "</code></p>";
                
                // Test kaydını temizle
                $pdo->prepare("DELETE FROM brands WHERE name = 'TEST_DUPLICATE'")->execute();
                echo "<p>Test kaydı silindi.</p>";
            }
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color:orange;'>⚠️ Unique constraint zaten mevcut!</p>";
            } else {
                echo "<p style='color:red;'>❌ Constraint ekleme hatası: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Son durum kontrolü
    echo "<h2>4. Final Durum:</h2>";
    $stmt = $pdo->query("SHOW INDEX FROM brands WHERE Key_name != 'PRIMARY'");
    $finalIndexes = $stmt->fetchAll();
    
    if (empty($finalIndexes)) {
        echo "<p>Hiç index bulunamadı.</p>";
    } else {
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Index Adı</th><th>Kolon</th><th>Unique</th></tr>";
        foreach ($finalIndexes as $index) {
            echo "<tr>";
            echo "<td>{$index['Key_name']}</td>";
            echo "<td>{$index['Column_name']}</td>";
            echo "<td>" . ($index['Non_unique'] == 0 ? '✅ Evet' : '❌ Hayır') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='admin/brands.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Brands Yönetimine Dön</a>";
?>
