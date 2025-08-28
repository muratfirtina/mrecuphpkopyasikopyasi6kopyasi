<?php
/**
 * Services tablosuna detailed_content kolonu ekleme scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Services Tablosu Güncelleme</h2>";

try {
    // Önce kolonu kontrol et
    $stmt = $pdo->query("DESCRIBE services");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'detailed_content') {
            $columnExists = true;
            break;
        }
    }
    
    if (!$columnExists) {
        echo "<p>detailed_content kolonu ekleniyor...</p>";
        
        // Kolonu ekle
        $pdo->exec("ALTER TABLE `services` 
                   ADD COLUMN `detailed_content` LONGTEXT DEFAULT NULL COMMENT 'HTML destekli detaylı açıklama içeriği' 
                   AFTER `description`");
        
        echo "<p style='color: green;'>✅ detailed_content kolonu başarıyla eklendi!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ detailed_content kolonu zaten mevcut.</p>";
    }
    
    // Tablo yapısını göster
    echo "<h3>Güncel Tablo Yapısı:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $stmt = $pdo->query("DESCRIBE services");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . $column['Field'] . "</strong></td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Örnek Güncelleme:</h3>";
    
    // İlk hizmete örnek detailed_content ekleyelim
    $stmt = $pdo->prepare("SELECT id, name, detailed_content FROM services ORDER BY id LIMIT 1");
    $stmt->execute();
    $firstService = $stmt->fetch();
    
    if ($firstService && empty($firstService['detailed_content'])) {
        $sampleContent = "
        <h4>Profesyonel ECU Optimizasyonu</h4>
        <p>Aracınızın ECU yazılımını en son teknoloji ile optimize ediyoruz. Bu işlem sayesinde:</p>
        
        <ul>
            <li><strong>Performans Artışı:</strong> Motor gücünde %15-30 artış</li>
            <li><strong>Yakıt Tasarrufu:</strong> Ortalama %10-20 yakıt ekonomisi</li>
            <li><strong>Motor Ömrü:</strong> Daha verimli çalışma ile uzun ömür</li>
        </ul>
        
        <div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
            <h5 style='color: #1976d2; margin-top: 0;'>🔧 Teknik Özellikler</h5>
            <p>Yazılım optimizasyonumuz aşağıdaki parametreleri kapsar:</p>
            <ul style='margin-bottom: 0;'>
                <li>Enjeksiyon haritası optimizasyonu</li>
                <li>Turbo basınç ayarları</li>
                <li>Ateşleme zamanlaması</li>
                <li>Lambda sensör kalibrasyonu</li>
            </ul>
        </div>
        
        <h5>🎯 Hangi Araçlarda Uygulanır?</h5>
        <p>Tüm marka ve modellerde uygulama yapabilmekteyiz:</p>
        <div style='display: flex; flex-wrap: wrap; gap: 10px;'>
            <span style='background: #ffecb3; padding: 5px 10px; border-radius: 15px;'>🚗 Volkswagen</span>
            <span style='background: #ffecb3; padding: 5px 10px; border-radius: 15px;'>🚙 BMW</span>
            <span style='background: #ffecb3; padding: 5px 10px; border-radius: 15px;'>🚐 Mercedes</span>
            <span style='background: #ffecb3; padding: 5px 10px; border-radius: 15px;'>🏎️ Audi</span>
        </div>
        ";
        
        $updateStmt = $pdo->prepare("UPDATE services SET detailed_content = ? WHERE id = ?");
        $updateStmt->execute([$sampleContent, $firstService['id']]);
        
        echo "<p style='color: green;'>✅ Örnek detailed_content içeriği '" . $firstService['name'] . "' hizmetine eklendi!</p>";
    } else {
        echo "<p>İlk hizmette detailed_content zaten mevcut veya hizmet bulunamadı.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Test Tamamlandı!</h3>";
    echo "<p><a href='services.php'>➡️ Hizmetler sayfasını kontrol et</a></p>";
    echo "<p><a href='design/services-edit.php?id=" . ($firstService['id'] ?? 1) . "'>✏️ Admin panelde düzenle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}
?>
