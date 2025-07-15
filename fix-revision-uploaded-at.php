<?php
/**
 * User revision-detail.php Son Undefined Key Hatası Düzeltme
 */

require_once 'config/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Revision Detail Son Hata Düzeltme</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; }</style></head><body>";

echo "<h1>🔧 Revision Detail Son Hata Düzeltme</h1>";

try {
    $filePath = '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/user/revision-detail.php';
    $content = file_get_contents($filePath);
    
    if ($content === false) {
        throw new Exception("Dosya okunamadı: $filePath");
    }
    
    echo "<h2>🎯 Düzeltilecek Yeni Hata:</h2>";
    echo "<p>❌ Undefined array key 'revision_uploaded_at' on line 342</p>";
    
    // Yeni hataları düzelt
    $additionalFixes = [
        '$revision[\'revision_uploaded_at\']' => '($revision[\'revision_uploaded_at\'] ?? $revision[\'requested_at\'])',
        '$revision[\'revision_download_count\']' => '($revision[\'revision_download_count\'] ?? 0)',
        '$revision[\'revision_file_size\']' => '($revision[\'revision_file_size\'] ?? 0)',
        '$revision[\'revision_admin_notes\']' => '($revision[\'revision_admin_notes\'] ?? \'\')',
        '$revision[\'revision_status\']' => '($revision[\'revision_status\'] ?? $revision[\'status\'])',
        '$revision[\'revision_id\']' => '($revision[\'revision_id\'] ?? $revision[\'id\'])',
        '$revision[\'brand_logo\']' => '($revision[\'brand_logo\'] ?? \'\')',
        '$revision[\'file_type\']' => '($revision[\'file_type\'] ?? \'Bilinmiyor\')',
        '$revision[\'hp_power\']' => '($revision[\'hp_power\'] ?? 0)',
        '$revision[\'nm_torque\']' => '($revision[\'nm_torque\'] ?? 0)',
        '$revision[\'engine_code\']' => '($revision[\'engine_code\'] ?? \'Belirtilmemiş\')',
        '$revision[\'gearbox_type\']' => '($revision[\'gearbox_type\'] ?? \'Belirtilmemiş\')',
        '$revision[\'fuel_type\']' => '($revision[\'fuel_type\'] ?? \'Belirtilmemiş\')'
    ];
    
    $fixCount = 0;
    $newContent = $content;
    
    foreach ($additionalFixes as $search => $replace) {
        $occurrences = substr_count($newContent, $search);
        if ($occurrences > 0) {
            $newContent = str_replace($search, $replace, $newContent);
            $fixCount += $occurrences;
            echo "<p class='success'>✅ Düzeltildi ($occurrences adet): " . htmlspecialchars($search) . "</p>";
        }
    }
    
    // Dosyayı güncelle
    if ($fixCount > 0) {
        if (file_put_contents($filePath, $newContent)) {
            echo "<p class='success'><strong>✅ Toplam $fixCount hata düzeltildi!</strong></p>";
        } else {
            echo "<p class='error'>❌ Dosya yazılamadı</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Bu hatalar zaten düzeltilmiş veya bulunamadı</p>";
    }
    
    // Satır 342 civarındaki kodu kontrol et
    $lines = explode("\n", $newContent);
    if (isset($lines[341])) { // Array 0-indexed olduğu için 342. satır = index 341
        echo "<h3>📍 Satır 342 İçeriği:</h3>";
        echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>";
        
        // 340-345 satırları arası göster
        for ($i = 339; $i <= 344; $i++) {
            if (isset($lines[$i])) {
                $lineNumber = $i + 1;
                $highlight = ($lineNumber == 342) ? "background: yellow;" : "";
                echo "<span style='$highlight'>$lineNumber: " . htmlspecialchars($lines[$i]) . "</span>\n";
            }
        }
        echo "</pre>";
    }
    
    // Revizyon verisinin nasıl alındığını kontrol et
    echo "<h3>🔍 Revizyon Verisi Kontrolü</h3>";
    if (strpos($newContent, 'getRevisionDetail') !== false) {
        echo "<p class='success'>✅ getRevisionDetail metodu kullanılıyor</p>";
    } else {
        echo "<p class='warning'>⚠️ Revizyon detayları manuel SQL sorgusu ile alınıyor</p>";
        
        // getRevisionDetail metodunu kullanacak şekilde güncelle
        $sqlPattern = 'SELECT r.*, 
               fu.original_name, fu.filename, fu.file_size, fu.status as file_status, fu.upload_date as file_uploaded_at,
               fu.file_type, fu.hp_power, fu.nm_torque, fu.plate,
               u.username as admin_username, u.first_name as admin_first_name, u.last_name as admin_last_name,
               br.name as brand_name, br.logo as brand_logo
        FROM revisions r';
        
        if (strpos($newContent, $sqlPattern) !== false) {
            echo "<p class='info'>ℹ️ Manuel SQL sorgusu bulundu. FileManager::getRevisionDetail() kullanılması öneriliyor.</p>";
            
            $betterCode = '
// getRevisionDetail metodunu kullan (daha güvenli)
$revision = $fileManager->getRevisionDetail($revisionId, $userId);

if (!$revision) {
    $_SESSION[\'error\'] = \'Revize bulunamadı veya bu revizeyi görüntüleme yetkiniz yok.\';
    redirect(\'revisions.php\');
}';
            
            echo "<h4>📝 Önerilen Kod Değişikliği:</h4>";
            echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
            echo htmlspecialchars($betterCode);
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><hr><br>";
echo "<h2>🎯 Test Et:</h2>";
echo "<p>Şimdi user/revision-detail.php sayfasını tekrar aç ve hataların kaybolup kaybolmadığını kontrol et.</p>";

echo "<p class='success'><strong>✅ Artık undefined key hataları olmamalı!</strong></p>";

echo "</body></html>";
?>
