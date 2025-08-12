<?php
/**
 * Upload System Test Script
 * Yeni GUID tabanlı upload sistemini test eder
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Upload Sistemi Test Scripti</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .test { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    $fileManager = new FileManager($pdo);
    
    // Test 1: Tabloları kontrol et
    echo "<div class='test'>";
    echo "<h3>Test 1: Database Tabloları</h3>";
    
    $tables = ['brands', 'models', 'series', 'engines', 'devices', 'ecus', 'file_uploads','users'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p class='success'>✅ $table: $count kayıt</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ $table: Hata - " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    // Test 2: file_uploads tablosu yapısını kontrol et
    echo "<div class='test'>";
    echo "<h3>Test 2: file_uploads Tablo Yapısı</h3>";
    
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    $requiredColumns = ['series_id', 'engine_id', 'device_id', 'ecu_id'];
    echo "<table>";
    echo "<tr><th>Kolon</th><th>Tip</th><th>Null</th><th>Status</th></tr>";
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $existingColumns);
        $status = $exists ? '✅ Mevcut' : '❌ Eksik';
        $class = $exists ? 'success' : 'error';
        
        if ($exists) {
            $colInfo = array_filter($columns, fn($c) => $c['Field'] === $col);
            $colInfo = reset($colInfo);
            echo "<tr><td>$col</td><td>" . $colInfo['Type'] . "</td><td>" . $colInfo['Null'] . "</td><td class='$class'>$status</td></tr>";
        } else {
            echo "<tr><td>$col</td><td>-</td><td>-</td><td class='$class'>$status</td></tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Test 3: Markalar ve modeller
    echo "<div class='test'>";
    echo "<h3>Test 3: Marka ve Model Sistemi</h3>";
    
    $brands = $fileManager->getBrands();
    echo "<p class='info'>Toplam marka sayısı: " . count($brands) . "</p>";
    
    if (count($brands) > 0) {
        $sampleBrand = $brands[0];
        echo "<p class='success'>✅ Örnek marka: " . $sampleBrand['name'] . " (ID: " . $sampleBrand['id'] . ")</p>";
        
        // Bu markanın modellerini getir
        $models = $fileManager->getModelsByBrand($sampleBrand['id']);
        echo "<p class='info'>Bu markanın model sayısı: " . count($models) . "</p>";
        
        if (count($models) > 0) {
            $sampleModel = $models[0];
            echo "<p class='success'>✅ Örnek model: " . $sampleModel['name'] . " (ID: " . $sampleModel['id'] . ")</p>";
            
            // Bu modelin serilerini getir
            $series = $fileManager->getSeriesByModel($sampleModel['id']);
            echo "<p class='info'>Bu modelin seri sayısı: " . count($series) . "</p>";
            
            if (count($series) > 0) {
                $sampleSeries = $series[0];
                echo "<p class='success'>✅ Örnek seri: " . $sampleSeries['name'] . " (ID: " . $sampleSeries['id'] . ")</p>";
                
                // Bu serinin motorlarını getir
                $engines = $fileManager->getEnginesBySeries($sampleSeries['id']);
                echo "<p class='info'>Bu serinin motor sayısı: " . count($engines) . "</p>";
                
                if (count($engines) > 0) {
                    $sampleEngine = $engines[0];
                    echo "<p class='success'>✅ Örnek motor: " . $sampleEngine['name'] . " (ID: " . $sampleEngine['id'] . ")</p>";
                }
            }
        }
    }
    echo "</div>";
    
    // Test 4: ECU ve Device verilerini kontrol et
    echo "<div class='test'>";
    echo "<h3>Test 4: ECU ve Device Verileri</h3>";
    
    require_once 'includes/EcuModel.php';
    require_once 'includes/DeviceModel.php';
    
    try {
        $ecuModel = new EcuModel($pdo);
        $ecus = $ecuModel->getAllEcus('name', 'ASC');
        echo "<p class='success'>✅ ECU sayısı: " . count($ecus) . "</p>";
        
        if (count($ecus) > 0) {
            echo "<p class='info'>İlk 5 ECU: " . implode(', ', array_slice(array_column($ecus, 'name'), 0, 5)) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ ECU model hatası: " . $e->getMessage() . "</p>";
    }
    
    try {
        $deviceModel = new DeviceModel($pdo);
        $devices = $deviceModel->getAllDevices('name', 'ASC');
        echo "<p class='success'>✅ Device sayısı: " . count($devices) . "</p>";
        
        if (count($devices) > 0) {
            echo "<p class='info'>İlk 5 Cihaz: " . implode(', ', array_slice(array_column($devices, 'name'), 0, 5)) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Device model hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Test 5: Mevcut dosya kayıtlarını kontrol et
    echo "<div class='test'>";
    echo "<h3>Test 5: Mevcut Dosya Kayıtları</h3>";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               COUNT(series_id) as with_series,
               COUNT(engine_id) as with_engine,
               COUNT(device_id) as with_device,
               COUNT(ecu_id) as with_ecu
        FROM file_uploads
    ");
    $fileStats = $stmt->fetch();
    
    echo "<table>";
    echo "<tr><th>Özellik</th><th>Sayı</th><th>Oran</th></tr>";
    echo "<tr><td>Toplam Dosya</td><td>" . $fileStats['total'] . "</td><td>100%</td></tr>";
    
    if ($fileStats['total'] > 0) {
        $seriesPercent = round(($fileStats['with_series'] / $fileStats['total']) * 100, 1);
        $enginePercent = round(($fileStats['with_engine'] / $fileStats['total']) * 100, 1);
        $devicePercent = round(($fileStats['with_device'] / $fileStats['total']) * 100, 1);
        $ecuPercent = round(($fileStats['with_ecu'] / $fileStats['total']) * 100, 1);
        
        echo "<tr><td>Seri ID'si olan</td><td>" . $fileStats['with_series'] . "</td><td>$seriesPercent%</td></tr>";
        echo "<tr><td>Motor ID'si olan</td><td>" . $fileStats['with_engine'] . "</td><td>$enginePercent%</td></tr>";
        echo "<tr><td>Cihaz ID'si olan</td><td>" . $fileStats['with_device'] . "</td><td>$devicePercent%</td></tr>";
        echo "<tr><td>ECU ID'si olan</td><td>" . $fileStats['with_ecu'] . "</td><td>$ecuPercent%</td></tr>";
    }
    echo "</table>";
    
    echo "<p class='info'>Not: Yeni kayıtlar yeni alanları kullanacak, eski kayıtlar NULL değerlerle kalabilir.</p>";
    echo "</div>";
    
    // Test 6: Upload form test URL'si
    echo "<div class='test'>";
    echo "<h3>Test 6: Upload Form Testi</h3>";
    echo "<p class='info'>Upload formunu test etmek için:</p>";
    echo "<p><a href='user/upload.php' target='_blank' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Upload Sayfasını Aç</a></p>";
    echo "<p class='info'>Form şu alanları içermelidir:</p>";
    echo "<ul>";
    echo "<li>Marka seçimi (brands tablosundan)</li>";
    echo "<li>Model seçimi (models tablosundan, markaya bağlı)</li>";
    echo "<li>Seri seçimi (series tablosundan, modele bağlı)</li>";
    echo "<li>Motor seçimi (engines tablosundan, seriye bağlı)</li>";
    echo "<li>ECU seçimi (ecus tablosundan)</li>";
    echo "<li>Cihaz seçimi (devices tablosundan)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='test'>";
    echo "<h3 class='success'>✅ Test Tamamlandı!</h3>";
    echo "<p>Sistem yeni GUID tabanlı yapıya hazır görünüyor.</p>";
    echo "<p><strong>Sonraki adımlar:</strong></p>";
    echo "<ul>";
    echo "<li>Upload formunu test edin</li>";
    echo "<li>Dosya yükleme işlemini deneyin</li>";
    echo "<li>Admin panelinde dosya detaylarını kontrol edin</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test'>";
    echo "<h3 class='error'>❌ Test Hatası</h3>";
    echo "<p class='error'>Genel Hata: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Stack Trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<p><a href='migrate_file_uploads.php'>Migration Scriptini Çalıştır</a> | <a href='user/upload.php'>Upload Test</a> | <a href='admin/'>Admin Panel</a></p>";
?>
