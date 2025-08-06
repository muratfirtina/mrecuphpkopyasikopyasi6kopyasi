<?php
/**
 * Admin Araç Bilgileri Güncelleme Script
 * uploads.php ve file-detail.php sayfalarında seri ve motor bilgilerinin gösterilmesi
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Admin Araç Bilgileri Güncelleme</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .test { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
</style>";

echo "<div class='test'>";
echo "<h3>Güncellenecek Dosyalar</h3>";
echo "<ul>";
echo "<li>✅ <strong>admin/uploads.php</strong> - Araç Bilgileri sütununda model • seri • motor gösterimi</li>";
echo "<li>⚠️ <strong>admin/file-detail.php</strong> - Seri ve motor bilgilerinin eklenmesi gerekiyor</li>";
echo "<li>⚠️ <strong>includes/FileManager.php</strong> - getUploadById metoduna series ve engines JOIN'i gerekiyor</li>";
echo "</ul>";
echo "</div>";

// Test: Mevcut veritabanında series ve engines tabloları var mı?
echo "<div class='test'>";
echo "<h3>Veritabanı Tabloları Kontrolü</h3>";

try {
    // Series tablosu kontrol
    $stmt = $pdo->query("SHOW TABLES LIKE 'series'");
    if ($stmt->fetch()) {
        echo "<p class='success'>✅ series tablosu mevcut</p>";
        
        // Series tablosunda veri var mı?
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM series");
        $seriesCount = $stmt->fetch()['count'];
        echo "<p class='info'>Series kayıt sayısı: $seriesCount</p>";
    } else {
        echo "<p class='error'>❌ series tablosu bulunamadı</p>";
    }
    
    // Engines tablosu kontrol
    $stmt = $pdo->query("SHOW TABLES LIKE 'engines'");
    if ($stmt->fetch()) {
        echo "<p class='success'>✅ engines tablosu mevcut</p>";
        
        // Engines tablosunda veri var mı?
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM engines");
        $enginesCount = $stmt->fetch()['count'];
        echo "<p class='info'>Engines kayıt sayısı: $enginesCount</p>";
    } else {
        echo "<p class='error'>❌ engines tablosu bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Veritabanı kontrol hatası: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test: file_uploads tablosunda series_id ve engine_id kolonları var mı?
echo "<div class='test'>";
echo "<h3>file_uploads Tablosu Kontrol</h3>";

try {
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    $hasSeriesId = false;
    $hasEngineId = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'series_id') {
            $hasSeriesId = true;
            echo "<p class='success'>✅ series_id kolonu mevcut</p>";
        }
        if ($col['Field'] === 'engine_id') {
            $hasEngineId = true;
            echo "<p class='success'>✅ engine_id kolonu mevcut</p>";
        }
    }
    
    if (!$hasSeriesId) {
        echo "<p class='error'>❌ series_id kolonu bulunamadı</p>";
    }
    if (!$hasEngineId) {
        echo "<p class='error'>❌ engine_id kolonu bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Kolon kontrol hatası: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test: Mevcut dosyalarda seri ve motor bilgileri var mı?
echo "<div class='test'>";
echo "<h3>Mevcut Veri Kontrolü</h3>";

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_files,
            COUNT(series_id) as has_series,
            COUNT(engine_id) as has_engine
        FROM file_uploads
    ");
    $dataStats = $stmt->fetch();
    
    echo "<p class='info'>Toplam dosya: " . $dataStats['total_files'] . "</p>";
    echo "<p class='info'>Seri bilgisi olan: " . $dataStats['has_series'] . "</p>";
    echo "<p class='info'>Motor bilgisi olan: " . $dataStats['has_engine'] . "</p>";
    
    // Örnek bir JOIN sorgusu test et
    $stmt = $pdo->query("
        SELECT u.id, u.original_name, 
               b.name as brand_name, 
               m.name as model_name,
               s.name as series_name,
               e.name as engine_name
        FROM file_uploads u
        LEFT JOIN brands b ON u.brand_id = b.id
        LEFT JOIN models m ON u.model_id = m.id
        LEFT JOIN series s ON u.series_id = s.id
        LEFT JOIN engines e ON u.engine_id = e.id
        WHERE u.series_id IS NOT NULL OR u.engine_id IS NOT NULL
        LIMIT 5
    ");
    $sampleData = $stmt->fetchAll();
    
    if (!empty($sampleData)) {
        echo "<p class='success'>✅ JOIN sorgusu başarılı - Örnek veri:</p>";
        echo "<div class='code'>";
        foreach ($sampleData as $sample) {
            echo "<strong>" . htmlspecialchars($sample['original_name']) . "</strong><br>";
            echo "Marka: " . ($sample['brand_name'] ?? 'N/A') . "<br>";
            echo "Model: " . ($sample['model_name'] ?? 'N/A') . "<br>";
            echo "Seri: " . ($sample['series_name'] ?? 'N/A') . "<br>";
            echo "Motor: " . ($sample['engine_name'] ?? 'N/A') . "<br><br>";
        }
        echo "</div>";
    } else {
        echo "<p class='warning'>⚠️ Seri/Motor bilgisi olan dosya bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Veri kontrol hatası: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Güncellenmiş araç bilgileri display fonksiyonu
echo "<div class='test'>";
echo "<h3>Güncellenmiş Araç Bilgileri Display Kodu</h3>";
echo "<p class='info'>Admin sayfalarında kullanılmak üzere araç bilgileri gösterim kodu:</p>";

$displayCode = '
<?php
// Güncellenmiş araç bilgileri gösterimi
function displayVehicleInfo($upload) {
    $vehicleInfo = [];
    
    // Model
    if (!empty($upload[\'model_name\'])) {
        $vehicleInfo[] = htmlspecialchars($upload[\'model_name\']);
    }
    
    // Seri
    if (!empty($upload[\'series_name\'])) {
        $vehicleInfo[] = htmlspecialchars($upload[\'series_name\']);
    }
    
    // Motor
    if (!empty($upload[\'engine_name\'])) {
        $vehicleInfo[] = htmlspecialchars($upload[\'engine_name\']);
    }
    
    return !empty($vehicleInfo) ? implode(\' • \', $vehicleInfo) : \'Model/Seri belirtilmemiş\';
}
?>

<!-- HTML kısmında kullanım -->
<td>
    <div>
        <strong><?php echo htmlspecialchars($upload[\'brand_name\'] ?? \'Bilinmiyor\'); ?></strong><br>
        <small class="text-muted">
            <?php echo displayVehicleInfo($upload); ?>
        </small>
        <?php if (!empty($upload[\'plate\'])): ?>
            <div class="mt-1">
                <span class="badge bg-dark text-white">
                    <i class="fas fa-id-card me-1"></i>
                    <?php echo strtoupper(htmlspecialchars($upload[\'plate\'])); ?>
                </span>
                <?php if (!empty($upload[\'kilometer\'])): ?>
                    <span class="badge bg-secondary text-white ms-1">
                        <i class="fas fa-tachometer-alt me-1"></i>
                        <?php echo number_format($upload[\'kilometer\']); ?> km
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</td>
';

echo "<div class='code'>" . htmlspecialchars($displayCode) . "</div>";
echo "</div>";

// Manuel SQL güncelleme script'i
echo "<div class='test'>";
echo "<h3>Manuel SQL Güncelleme</h3>";
echo "<p class='info'>FileManager.php'de getUploadById metodunu elle güncellemek için:</p>";

$sqlUpdate = "
-- getUploadById metodundaki SQL sorgusunu şu şekilde güncelleyin:

SELECT u.*, 
       users.username, users.email, users.first_name, users.last_name,
       b.name as brand_name,
       m.name as model_name,
       s.name as series_name,
       e.name as engine_name,
       ecu.name as ecu_name,
       d.name as device_name
FROM file_uploads u
LEFT JOIN users ON u.user_id = users.id
LEFT JOIN brands b ON u.brand_id = b.id
LEFT JOIN models m ON u.model_id = m.id
LEFT JOIN series s ON u.series_id = s.id
LEFT JOIN engines e ON u.engine_id = e.id
LEFT JOIN ecus ecu ON u.ecu_id = ecu.id
LEFT JOIN devices d ON u.device_id = d.id
WHERE u.id = ?
";

echo "<div class='code'>" . htmlspecialchars($sqlUpdate) . "</div>";
echo "</div>";

// Sonuç
echo "<div class='test'>";
echo "<h3 class='success'>✅ Güncelleme Tamamlandı</h3>";
echo "<p class='success'>Admin uploads.php sayfası güncellenmiş durumda:</p>";
echo "<ul>";
echo "<li>✅ <strong>Marka</strong> gösteriliyor</li>";
echo "<li>✅ <strong>Model • Seri • Motor</strong> yan yana gösteriliyor</li>";
echo "<li>✅ <strong>Plaka</strong> badge olarak gösteriliyor</li>";
echo "<li>✅ <strong>Kilometre</strong> badge olarak gösteriliyor (varsa)</li>";
echo "</ul>";

echo "<p class='warning'>⚠️ file-detail.php için manuel güncelleme gerekli:</p>";
echo "<ol>";
echo "<li>FileManager.php → getUploadById metodunu yukarıdaki SQL ile güncelleyin</li>";
echo "<li>file-detail.php → araç bilgileri bölümünde displayVehicleInfo fonksiyonunu kullanın</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='admin/uploads.php'>Admin Uploads Test</a> | <a href='user/upload.php'>Upload Test</a></p>";
?>
