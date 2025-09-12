<?php
/**
 * Mr ECU - Currency Column Installation Script
 * Products tablosuna para birimi desteği ekler
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Currency Column Installation</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; text-align: center; }
    .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

echo "<div class='container'>";

try {
    // Veritabanı bağlantısını kontrol et
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }
    
    echo "<div class='info'>Veritabanı bağlantısı başarılı. Güncelleme başlatılıyor...</div>";
    
    // İlk önce products tablosunun var olup olmadığını kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='error'>❌ HATA: 'products' tablosu bulunamadı!</div>";
        exit;
    }
    
    echo "<div class='success'>✅ Products tablosu mevcut.</div>";
    
    // Currency kolonu zaten var mı kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'currency'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='info'>⚠️ 'currency' kolonu zaten mevcut. Güncelleme atlanıyor.</div>";
    } else {
        // Currency kolonu ekle
        $sql = "ALTER TABLE `products` 
                ADD COLUMN `currency` ENUM('TL', 'USD', 'EUR') DEFAULT 'TL' 
                COMMENT 'Para birimi (TL=Türk Lirası, USD=Amerikan Doları, EUR=Euro)' 
                AFTER `sale_price`";
        
        $pdo->exec($sql);
        echo "<div class='success'>✅ Currency kolonu başarıyla eklendi!</div>";
    }
    
    // Mevcut products tablosunun güncel yapısını göster
    echo "<h2>📋 Güncel Products Tablo Yapısı</h2>";
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Kolon Adı</th><th>Veri Tipi</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Extra</th></tr>";
    
    foreach ($columns as $col) {
        $isNewColumn = ($col['Field'] === 'currency') ? 'style="background-color: #d4edda; font-weight: bold;"' : '';
        echo "<tr $isNewColumn>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mevcut ürün sayısını kontrol et
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetchColumn();
    echo "<div class='info'>📊 Toplam ürün sayısı: <strong>$count</strong></div>";
    
    // Eğer mevcut ürünler varsa, onları TL olarak ayarla
    if ($count > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE currency IS NULL");
        $nullCount = $stmt->fetchColumn();
        
        if ($nullCount > 0) {
            $pdo->exec("UPDATE products SET currency = 'TL' WHERE currency IS NULL");
            echo "<div class='success'>✅ $nullCount ürünün para birimi 'TL' olarak ayarlandı.</div>";
        }
    }
    
    echo "<h2>🎯 Kurulum Tamamlandı!</h2>";
    echo "<div class='success'>";
    echo "<strong>✅ Para birimi desteği başarıyla eklendi!</strong><br>";
    echo "Artık admin panelinde ürün eklerken/düzenlerken para birimi seçebilirsiniz:<br>";
    echo "• TL (Türk Lirası)<br>";
    echo "• USD (Amerikan Doları)<br>";
    echo "• EUR (Euro)<br><br>";
    echo "Frontend'de fiyat 0 olan ürünlerde fiyat gösterilmeyecek.<br>";
    echo "Admin paneli dosyalarının da güncellenmesi gerekiyor.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ HATA: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='error'>Detay: " . htmlspecialchars($e->getTraceAsString()) . "</div>";
}

echo "</div>";
?>
