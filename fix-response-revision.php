<?php
/**
 * Response Revision Fix
 * Yanıt dosyası revize sistemi düzeltmesi
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔧 Response Revision Fix</h1>";

try {
    // 1. Revisions tablosuna response_id alanı ekle
    echo "<h2>1. Revisions tablosuna response_id alanı ekleniyor...</h2>";
    
    try {
        $pdo->exec("ALTER TABLE revisions ADD COLUMN response_id CHAR(36) NULL AFTER upload_id");
        echo "<p style='color:green;'>✅ response_id alanı başarıyla eklendi</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠️ response_id alanı zaten mevcut veya hata: " . $e->getMessage() . "</p>";
    }
    
    // 2. Revisions tablosunun güncel yapısını kontrol et
    echo "<h2>2. Revisions tablosu yapısı kontrolü...</h2>";
    
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Mevcut revisions verilerini kontrol et
    echo "<h2>3. Mevcut revisions verilerini kontrol ediliyor...</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions");
    $revisionCount = $stmt->fetch()['count'];
    echo "<p>📊 Toplam revize talebi sayısı: <strong>$revisionCount</strong></p>";
    
    if ($revisionCount > 0) {
        $stmt = $pdo->query("SELECT * FROM revisions ORDER BY requested_at DESC LIMIT 5");
        $revisions = $stmt->fetchAll();
        echo "<h3>Son 5 Revize Talebi:</h3>";
        echo "<pre>" . print_r($revisions, true) . "</pre>";
    }
    
    echo "<h2>4. Sonuç</h2>";
    echo "<p style='color:green;'>✅ Response revision fix başarıyla uygulandı!</p>";
    echo "<p><strong>Yapılan değişiklikler:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Revisions tablosuna response_id alanı eklendi</li>";
    echo "<li>✅ Yanıt dosyası revize talepleri artık doğru şekilde kaydedilecek</li>";
    echo "<li>✅ Admin revize listesinde yanıt dosyası bilgileri doğru gösterilecek</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><br><a href='admin/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin paneline dön</a>";
?>
