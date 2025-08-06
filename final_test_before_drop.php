<?php
/**
 * Upload System Post-Migration Test
 * Kolon silme öncesi son test scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Kolon Silme Öncesi Final Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .test { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
    th { background-color: #f2f2f2; }
    .code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
</style>";

try {
    $fileManager = new FileManager($pdo);
    
    // Test 1: Upload form kontrolü
    echo "<div class='test'>";
    echo "<h3>Test 1: Upload Form Kontrolü</h3>";
    
    $uploadContent = file_get_contents('user/upload.php');
    
    // Silinecek alanların form kodunda olup olmadığını kontrol et
    $obsoleteFields = ['ecu_type', 'engine_code'];
    $foundObsoleteFields = [];
    
    foreach ($obsoleteFields as $field) {
        if (strpos($uploadContent, "name=\"$field\"") !== false) {
            $foundObsoleteFields[] = $field;
        }
    }
    
    if (empty($foundObsoleteFields)) {
        echo "<p class='success'>✅ Upload formunda eski alanlar temizlendi</p>";
    } else {
        echo "<p class='error'>❌ Upload formunda hala bu alanlar var: " . implode(', ', $foundObsoleteFields) . "</p>";
    }
    
    // Gerekli alanların varlığını kontrol et
    $requiredFields = ['brand_id', 'model_id', 'series_id', 'engine_id', 'device_id', 'ecu_id'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (strpos($uploadContent, "name=\"$field\"") === false) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        echo "<p class='success'>✅ Upload formunda tüm gerekli GUID alanları mevcut</p>";
    } else {
        echo "<p class='error'>❌ Upload formunda eksik alanlar: " . implode(', ', $missingFields) . "</p>";
    }
    echo "</div>";
    
    // Test 2: FileManager kodunu kontrol et
    echo "<div class='test'>";
    echo "<h3>Test 2: FileManager Kodu Kontrolü</h3>";
    
    $fileManagerContent = file_get_contents('includes/FileManager.php');
    
    // uploadFile metodunda eski alanların kullanılıp kullanılmadığını kontrol et
    if (strpos($fileManagerContent, "'ecu_type'") === false && 
        strpos($fileManagerContent, "'engine_code'") === false) {
        echo "<p class='success'>✅ FileManager.php'de eski alanlar temizlendi</p>";
    } else {
        echo "<p class='warning'>⚠️ FileManager.php'de hala eski alan referansları olabilir</p>";
    }
    
    // Yeni GUID alanlarının kullanılıp kullanılmadığını kontrol et
    if (strpos($fileManagerContent, 'series_id') !== false && 
        strpos($fileManagerContent, 'engine_id') !== false &&
        strpos($fileManagerContent, 'device_id') !== false &&
        strpos($fileManagerContent, 'ecu_id') !== false) {
        echo "<p class='success'>✅ FileManager.php'de yeni GUID alanları kullanılıyor</p>";
    } else {
        echo "<p class='error'>❌ FileManager.php'de GUID alanları eksik</p>";
    }
    echo "</div>";
    
    // Test 3: Database tablo yapısı kontrolü
    echo "<div class='test'>";
    echo "<h3>Test 3: Database Tablo Yapısı</h3>";
    
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    $existingColumns = array_column($columns, 'Field');
    
    // Yeni kolonlar mevcut mu?
    $newColumns = ['series_id', 'engine_id', 'device_id', 'ecu_id'];
    $missingNewColumns = array_diff($newColumns, $existingColumns);
    
    if (empty($missingNewColumns)) {
        echo "<p class='success'>✅ Yeni GUID kolonları database'de mevcut</p>";
    } else {
        echo "<p class='error'>❌ Database'de eksik kolonlar: " . implode(', ', $missingNewColumns) . "</p>";
    }
    
    // Silinecek kolonlar hala mevcut mu?
    $obsoleteColumns = ['ecu_type', 'device_type', 'engine_code', 'motor', 'type'];
    $existingObsoleteColumns = array_intersect($obsoleteColumns, $existingColumns);
    
    if (!empty($existingObsoleteColumns)) {
        echo "<p class='info'>ℹ️ Silinmeyi bekleyen kolonlar: " . implode(', ', $existingObsoleteColumns) . "</p>";
        echo "<p class='warning'>⚠️ Bu kolonlar silinmeye hazır</p>";
    }
    echo "</div>";
    
    // Test 4: Mevcut data kontrolü
    echo "<div class='test'>";
    echo "<h3>Test 4: Mevcut Veri Kontrolü</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM file_uploads");
    $totalFiles = $stmt->fetch()['total'];
    
    echo "<p class='info'>Toplam dosya sayısı: $totalFiles</p>";
    
    if ($totalFiles > 0) {
        // Yeni alanlarla kaç tane kayıt var?
        $stmt = $pdo->query("
            SELECT 
                COUNT(series_id) as with_series,
                COUNT(engine_id) as with_engine,
                COUNT(device_id) as with_device,
                COUNT(ecu_id) as with_ecu
            FROM file_uploads
        ");
        $newFieldStats = $stmt->fetch();
        
        echo "<p class='info'>Yeni GUID alanlarına sahip kayıtlar:</p>";
        echo "<ul>";
        echo "<li>Series ID: " . $newFieldStats['with_series'] . "</li>";
        echo "<li>Engine ID: " . $newFieldStats['with_engine'] . "</li>";
        echo "<li>Device ID: " . $newFieldStats['with_device'] . "</li>";
        echo "<li>ECU ID: " . $newFieldStats['with_ecu'] . "</li>";
        echo "</ul>";
    }
    echo "</div>";
    
    // Test 5: SQL komutunu hazırla
    echo "<div class='test'>";
    echo "<h3>Test 5: Kolon Silme Komutu</h3>";
    
    if (!empty($existingObsoleteColumns)) {
        $dropColumns = [];
        foreach ($existingObsoleteColumns as $col) {
            $dropColumns[] = "DROP `$col`";
        }
        
        $sqlCommand = "ALTER TABLE `file_uploads` " . implode(', ', $dropColumns) . ";";
        
        echo "<p class='info'>Çalıştıracağınız SQL komutu:</p>";
        echo "<div class='code'>$sqlCommand</div>";
        echo "<p class='warning'>⚠️ Bu komutu çalıştırmadan önce database backup'ı alın!</p>";
    } else {
        echo "<p class='success'>✅ Silinecek kolon bulunamadı</p>";
    }
    echo "</div>";
    
    // Test 6: Upload formu test linki
    echo "<div class='test'>";
    echo "<h3>Test 6: Final Test</h3>";
    echo "<p class='info'>Kolonları silmeden önce upload formunu test edin:</p>";
    echo "<p><a href='user/upload.php' target='_blank' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Upload Formunu Test Et</a></p>";
    echo "<p class='info'>Test senaryosu:</p>";
    echo "<ol>";
    echo "<li>Marka seçimi yapın</li>";
    echo "<li>Model otomatik yüklensin</li>";
    echo "<li>Seri seçimi yapın</li>";
    echo "<li>Motor seçimi yapın</li>";
    echo "<li>ECU seçimi yapın</li>";
    echo "<li>Cihaz seçimi yapın</li>";
    echo "<li>Plaka girin</li>";
    echo "<li>Test dosyası yükleyin</li>";
    echo "</ol>";
    echo "</div>";
    
    // Final sonuç
    echo "<div class='test'>";
    if (empty($foundObsoleteFields) && empty($missingNewColumns)) {
        echo "<h3 class='success'>✅ SİSTEM HAZIR!</h3>";
        echo "<p class='success'>Kodlarda tüm güncellemeler tamamlandı. Artık güvenle şu komutu çalıştırabilirsiniz:</p>";
        if (!empty($existingObsoleteColumns)) {
            $dropColumns = [];
            foreach ($existingObsoleteColumns as $col) {
                $dropColumns[] = "DROP `$col`";
            }
            $sqlCommand = "ALTER TABLE `file_uploads` " . implode(', ', $dropColumns) . ";";
            echo "<div class='code' style='background: #d4edda; border: 1px solid #c3e6cb;'>$sqlCommand</div>";
        }
    } else {
        echo "<h3 class='error'>❌ HATALAR VAR</h3>";
        echo "<p class='error'>Lütfen önce hataları düzeltin, sonra kolonları silin.</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test'>";
    echo "<h3 class='error'>❌ Test Hatası</h3>";
    echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='user/upload.php'>Upload Test</a> | <a href='test_upload_system.php'>Sistem Testi</a> | <a href='admin/'>Admin Panel</a></p>";
?>
