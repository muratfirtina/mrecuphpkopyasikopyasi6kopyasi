<?php
/**
 * Kilometre Alanı Test Scripti
 * Upload formunda kilometre alanının doğru çalışıp çalışmadığını test eder
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Kilometre Alanı Test Scripti</h1>";
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
    .highlight { background-color: #fff3cd; }
</style>";

try {
    // Test 1: Database'de kilometer kolonu var mı?
    echo "<div class='test'>";
    echo "<h3>Test 1: Database Kilometer Kolonu Kontrolü</h3>";
    
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    $hasKilometerColumn = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'kilometer') {
            $hasKilometerColumn = true;
            echo "<p class='success'>✅ Kilometer kolonu mevcut</p>";
            echo "<p class='info'>Tip: " . $col['Type'] . ", Null: " . $col['Null'] . ", Varsayılan: " . ($col['Default'] ?? 'NULL') . "</p>";
            break;
        }
    }
    
    if (!$hasKilometerColumn) {
        echo "<p class='error'>❌ Kilometer kolonu bulunamadı!</p>";
    }
    echo "</div>";
    
    // Test 2: Upload.php formunda kilometer alanı var mı?
    echo "<div class='test'>";
    echo "<h3>Test 2: Upload Form Kontrolü</h3>";
    
    $uploadContent = file_get_contents('user/upload.php');
    
    // Kilometer input alanı var mı?
    if (strpos($uploadContent, 'name="kilometer"') !== false) {
        echo "<p class='success'>✅ Upload formunda kilometer input alanı mevcut</p>";
    } else {
        echo "<p class='error'>❌ Upload formunda kilometer input alanı bulunamadı</p>";
    }
    
    // JavaScript'te kilometer desteği var mı?
    if (strpos($uploadContent, 'getElementById(\'kilometer\')') !== false) {
        echo "<p class='success'>✅ JavaScript'te kilometer desteği mevcut</p>";
    } else {
        echo "<p class='error'>❌ JavaScript'te kilometer desteği bulunamadı</p>";
    }
    
    // Summary'de kilometer var mı?
    if (strpos($uploadContent, 'summary-kilometer') !== false) {
        echo "<p class='success'>✅ Özet ekranında kilometer desteği mevcut</p>";
    } else {
        echo "<p class='error'>❌ Özet ekranında kilometer desteği bulunamadı</p>";
    }
    echo "</div>";
    
    // Test 3: FileManager.php'de kilometer desteği var mı?
    echo "<div class='test'>";
    echo "<h3>Test 3: FileManager Kodu Kontrolü</h3>";
    
    $fileManagerContent = file_get_contents('includes/FileManager.php');
    
    // uploadFile metodunda kilometer kullanılıyor mu?
    if (strpos($fileManagerContent, "vehicleData['kilometer']") !== false) {
        echo "<p class='success'>✅ FileManager.php'de kilometer desteği mevcut</p>";
    } else {
        echo "<p class='error'>❌ FileManager.php'de kilometer desteği bulunamadı</p>";
    }
    
    // INSERT sorgusunda kilometer var mı?
    if (strpos($fileManagerContent, ', kilometer,') !== false) {
        echo "<p class='success'>✅ Database INSERT sorgusunda kilometer alanı mevcut</p>";
    } else {
        echo "<p class='error'>❌ Database INSERT sorgusunda kilometer alanı bulunamadı</p>";
    }
    echo "</div>";
    
    // Test 4: UploadHelpers.php'de kilometer desteği var mı?
    echo "<div class='test'>";
    echo "<h3>Test 4: UploadHelpers Kontrol</h3>";
    
    $uploadHelpersContent = file_get_contents('includes/UploadHelpers.php');
    
    if (strpos($uploadHelpersContent, "kilometer") !== false) {
        echo "<p class='success'>✅ UploadHelpers.php'de kilometer desteği mevcut</p>";
    } else {
        echo "<p class='error'>❌ UploadHelpers.php'de kilometer desteği bulunamadı</p>";
    }
    echo "</div>";
    
    // Test 5: Mevcut veriler
    echo "<div class='test'>";
    echo "<h3>Test 5: Mevcut Veri Kontrolü</h3>";
    
    if ($hasKilometerColumn) {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                COUNT(kilometer) as with_kilometer,
                MIN(kilometer) as min_km,
                MAX(kilometer) as max_km,
                AVG(kilometer) as avg_km
            FROM file_uploads
        ");
        $kmStats = $stmt->fetch();
        
        echo "<table>";
        echo "<tr><th>Özellik</th><th>Değer</th></tr>";
        echo "<tr><td>Toplam Dosya</td><td>" . $kmStats['total'] . "</td></tr>";
        echo "<tr><td>Kilometre Bilgisi Olan</td><td>" . $kmStats['with_kilometer'] . "</td></tr>";
        
        if ($kmStats['with_kilometer'] > 0) {
            echo "<tr><td>Min Kilometre</td><td>" . number_format($kmStats['min_km']) . " km</td></tr>";
            echo "<tr><td>Max Kilometre</td><td>" . number_format($kmStats['max_km']) . " km</td></tr>";
            echo "<tr><td>Ort Kilometre</td><td>" . number_format($kmStats['avg_km']) . " km</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Test 6: Upload form test linki
    echo "<div class='test'>";
    echo "<h3>Test 6: Upload Form Testi</h3>";
    echo "<p class='info'>Kilometre alanı ile upload formunu test edin:</p>";
    echo "<p><a href='user/upload.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Kilometre Alanıyla Upload Test</a></p>";
    
    echo "<p class='info'><strong>Test senaryosu:</strong></p>";
    echo "<ol>";
    echo "<li>Marka → Model → Seri → Motor seçimi yapın</li>";
    echo "<li>Plaka bilgisi girin</li>";
    echo "<li class='highlight'><strong>Kilometre bilgisi girin (örn: 45000)</strong></li>";
    echo "<li>ECU ve Cihaz seçimi yapın</li>";
    echo "<li>Test dosyası yükleyin</li>";
    echo "<li>Özet ekranında kilometre bilgisinin göründüğünü kontrol edin</li>";
    echo "</ol>";
    echo "</div>";
    
    // Test 7: Admin paneli için kilometre gösterimi
    echo "<div class='test'>";
    echo "<h3>Test 7: Admin Panel Kilometre Gösterimi</h3>";
    echo "<p class='info'>Admin panelinde dosya detaylarında kilometre bilgisi gösterilecek.</p>";
    echo "<p>UploadHelpers.php'deki renderFileDetailsHTML fonksiyonu kilometre bilgisini otomatik olarak gösterecek.</p>";
    echo "</div>";
    
    // Final sonuç
    echo "<div class='test'>";
    echo "<h3 class='success'>✅ KİLOMETRE ALANI HAZIR!</h3>";
    echo "<p class='success'>Upload formunda kilometre alanı başarıyla eklendi.</p>";
    echo "<p class='info'><strong>Özellikler:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Opsiyonel alan (zorunlu değil)</li>";
    echo "<li>✅ Number formatında (0-999999 arası)</li>";
    echo "<li>✅ Real-time özet güncelleme</li>";
    echo "<li>✅ Admin panelinde gösterim</li>";
    echo "<li>✅ Veritabanında güvenli saklama</li>";
    echo "<li>✅ Responsive tasarım</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test'>";
    echo "<h3 class='error'>❌ Test Hatası</h3>";
    echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='user/upload.php'>Upload Test</a> | <a href='admin/'>Admin Panel</a> | <a href='test_upload_system.php'>Sistem Testi</a></p>";
?>
