<?php
/**
 * FileManager.php Manuel Güncelleme Scripti
 * getUploadById metodunu seri ve motor bilgileri için güncellemek
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>FileManager.php Manuel Güncelleme</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .test { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .code { background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
    .highlight { background-color: #fff3cd; padding: 2px 5px; border-radius: 3px; }
</style>";

echo "<div class='test'>";
echo "<h3>🔧 Manuel Güncelleme Talimatları</h3>";
echo "<p class='info'>FileManager.php dosyasında getUploadById metodunu manuel olarak güncelleyin:</p>";

echo "<p><strong>1. Dosya Yolu:</strong></p>";
echo "<div class='highlight'>includes/FileManager.php</div>";

echo "<p><strong>2. Bulunacak Metod:</strong></p>";
echo "<div class='code'>public function getUploadById(\$uploadId) {</div>";

echo "<p><strong>3. Mevcut SQL Sorgusu (Değiştirilecek):</strong></p>";
echo "<div class='code'>SELECT u.*, 
       users.username, users.email, users.first_name, users.last_name,
       b.name as brand_name,
       m.name as model_name
FROM file_uploads u
LEFT JOIN users ON u.user_id = users.id
LEFT JOIN brands b ON u.brand_id = b.id
LEFT JOIN models m ON u.model_id = m.id
WHERE u.id = ?</div>";

echo "<p><strong>4. Yeni SQL Sorgusu (Değiştirilmiş):</strong></p>";
echo "<div class='code'>SELECT u.*, 
       users.username, users.email, users.first_name, users.last_name,
       b.name as brand_name,
       m.name as model_name,
       <span class='highlight'>s.name as series_name,
       e.name as engine_name,
       ecu.name as ecu_name,
       d.name as device_name</span>
FROM file_uploads u
LEFT JOIN users ON u.user_id = users.id
LEFT JOIN brands b ON u.brand_id = b.id
LEFT JOIN models m ON u.model_id = m.id
<span class='highlight'>LEFT JOIN series s ON u.series_id = s.id
LEFT JOIN engines e ON u.engine_id = e.id
LEFT JOIN ecus ecu ON u.ecu_id = ecu.id
LEFT JOIN devices d ON u.device_id = d.id</span>
WHERE u.id = ?</div>";

echo "</div>";

// Test: Güncelleme sonrası test sorgusu
echo "<div class='test'>";
echo "<h3>🧪 Test Sorgusu</h3>";
echo "<p class='info'>Güncelleme sonrası bu sorguyu test edin:</p>";

try {
    $stmt = $pdo->query("
        SELECT u.id, u.original_name, 
               b.name as brand_name, 
               m.name as model_name,
               s.name as series_name,
               e.name as engine_name,
               u.plate, u.kilometer
        FROM file_uploads u
        LEFT JOIN brands b ON u.brand_id = b.id
        LEFT JOIN models m ON u.model_id = m.id
        LEFT JOIN series s ON u.series_id = s.id
        LEFT JOIN engines e ON u.engine_id = e.id
        WHERE (u.series_id IS NOT NULL OR u.engine_id IS NOT NULL)
        LIMIT 3
    ");
    $testData = $stmt->fetchAll();
    
    if (!empty($testData)) {
        echo "<p class='success'>✅ Test sorgusu başarılı! Örnek veriler:</p>";
        echo "<div class='code'>";
        foreach ($testData as $test) {
            echo "Dosya: " . htmlspecialchars($test['original_name']) . "\n";
            echo "Marka: " . ($test['brand_name'] ?? 'N/A') . "\n";
            echo "Model: " . ($test['model_name'] ?? 'N/A') . "\n";
            echo "Seri: " . ($test['series_name'] ?? 'N/A') . "\n";
            echo "Motor: " . ($test['engine_name'] ?? 'N/A') . "\n";
            echo "Plaka: " . ($test['plate'] ?? 'N/A') . "\n";
            echo "Kilometre: " . ($test['kilometer'] ? number_format($test['kilometer']) . ' km' : 'N/A') . "\n";
            echo "---\n";
        }
        echo "</div>";
    } else {
        echo "<p class='warning'>⚠️ Test verisi bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Test sorgusu hatası: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Manuel dosya kontrol linki
echo "<div class='test'>";
echo "<h3>📁 Dosya Kontrol</h3>";
echo "<p class='info'>Değişikliklerin kontrolü için:</p>";
echo "<p><a href='admin/uploads.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Admin Uploads Sayfası Test</a></p>";
echo "<p><a href='admin_vehicle_info_update.php' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Araç Bilgileri Güncelleme Kontrol</a></p>";
echo "</div>";

// Summary
echo "<div class='test'>";
echo "<h3 class='success'>✅ Tamamlanan Güncellemeler</h3>";
echo "<ul>";
echo "<li>✅ <strong>admin/uploads.php</strong> - Araç bilgileri gösterimi güncellendi (Marka • Model • Seri • Motor)</li>";
echo "<li>✅ <strong>admin/file-detail.php</strong> - SQL sorguları güncellendi (series ve engines JOIN eklendi)</li>";
echo "<li>⚠️ <strong>includes/FileManager.php</strong> - getUploadById metodu manuel olarak güncellenmelidir</li>";
echo "</ul>";

echo "<p class='info'><strong>Sonuç:</strong> Admin sayfalarında artık Model, Seri ve Motor bilgileri yan yana • işareti ile gösterilecek.</p>";
echo "</div>";

echo "<div class='test'>";
echo "<h3>🎯 Hedeflenen Görünüm</h3>";
echo "<p class='info'>Admin uploads.php sayfasında araç bilgileri şu şekilde görünecek:</p>";
echo "<div style='border: 2px solid #007bff; padding: 15px; border-radius: 5px; background: #f8f9fa;'>";
echo "<strong>BMW</strong><br>";
echo "<small class='text-muted'>3 Series • 320i • N46B20</small><br>";
echo "<span class='badge bg-dark text-white'>34 ABC 123</span> ";
echo "<span class='badge bg-secondary text-white'>85,000 km</span>";
echo "</div>";
echo "</div>";

?>
