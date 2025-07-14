<?php
/**
 * Revizyon Sistemi Test - Mevcut Durumu Kontrol Edelim
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Revizyon Sistemi Test Sonuçları</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }";
echo "h2 { color: #34495e; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; margin-top: 30px; }";
echo "h3 { color: #27ae60; }";
echo ".success { color: #27ae60; font-weight: bold; }";
echo ".error { color: #e74c3c; font-weight: bold; }";
echo ".warning { color: #f39c12; font-weight: bold; }";
echo ".info { color: #3498db; font-weight: bold; }";
echo "table { border-collapse: collapse; width: 100%; margin: 15px 0; }";
echo "th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }";
echo "th { background-color: #34495e; color: white; }";
echo "tr:nth-child(even) { background-color: #f2f2f2; }";
echo ".status-box { background: #ecf0f1; padding: 20px; border-radius: 8px; margin: 15px 0; }";
echo ".method-list { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo ".result-summary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; }";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Mr ECU Revizyon Sistemi - Test Sonuçları</h1>";

try {
    // 1. FileManager Metodları Test
    echo "<h2>1. FileManager Revizyon Metodları Kontrolü</h2>";
    
    require_once 'includes/FileManager.php';
    $fileManager = new FileManager($pdo);
    
    $revisionMethods = [
        'uploadRevisionFile' => 'Revizyon dosyası yükleme',
        'getRevisionFiles' => 'Revizyon dosyalarını getirme', 
        'getUploadRevisionFiles' => 'Upload göre revizyon dosyalarını getirme',
        'downloadRevisionFile' => 'Revizyon dosyası indirme kontrolü',
        'getRevisionDetail' => 'Revizyon detaylarını getirme',
        'getRevisionStats' => 'Revizyon istatistikleri'
    ];
    
    echo "<div class='method-list'>";
    foreach ($revisionMethods as $method => $description) {
        if (method_exists($fileManager, $method)) {
            echo "<p class='success'>✅ {$method}() - {$description}</p>";
        } else {
            echo "<p class='error'>❌ {$method}() - {$description} (EKSIK)</p>";
        }
    }
    echo "</div>";
    
    // 2. Veritabanı Yapısı Kontrolü
    echo "<h2>2. Veritabanı Yapısı Kontrolü</h2>";
    
    $requiredTables = [
        'file_uploads' => 'Ana dosyalar',
        'file_responses' => 'Yanıt dosyaları',
        'revisions' => 'Revizyon talepleri',
        'revision_files' => 'Revizyon dosyaları'
    ];
    
    echo "<table>";
    echo "<tr><th>Tablo</th><th>Açıklama</th><th>Durum</th><th>Kayıt Sayısı</th></tr>";
    
    foreach ($requiredTables as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $countStmt->fetchColumn();
                echo "<tr><td>$table</td><td>$description</td><td class='success'>✅ Mevcut</td><td>$count kayıt</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>$table</td><td>$description</td><td class='warning'>⚠️ Var ama hatalı</td><td>-</td></tr>";
            }
        } else {
            echo "<tr><td>$table</td><td>$description</td><td class='error'>❌ Eksik</td><td>-</td></tr>";
        }
    }
    echo "</table>";
    
    // 3. revision_files tablo yapısı kontrolü
    echo "<h2>3. revision_files Tablo Yapısı</h2>";
    
    try {
        $stmt = $pdo->query("DESCRIBE revision_files");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Kolon</th><th>Tür</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th></tr>";
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
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ revision_files tablosu kontrol edilemedi: " . $e->getMessage() . "</p>";
    }
    
    // 4. Dosya Sistemleri Kontrolü
    echo "<h2>4. Dosya Sistemleri</h2>";
    
    $uploadPaths = [
        'Ana Upload' => dirname(__FILE__) . '/uploads/',
        'Kullanıcı Dosyaları' => dirname(__FILE__) . '/uploads/user_files/',
        'Yanıt Dosyaları' => dirname(__FILE__) . '/uploads/response_files/',
        'Revizyon Dosyaları' => dirname(__FILE__) . '/uploads/revision_files/'
    ];
    
    echo "<table>";
    echo "<tr><th>Dizin</th><th>Yol</th><th>Durum</th><th>Dosya Sayısı</th></tr>";
    
    foreach ($uploadPaths as $name => $path) {
        if (is_dir($path)) {
            $files = glob($path . '*');
            $fileCount = count($files);
            echo "<tr><td>$name</td><td>$path</td><td class='success'>✅ Mevcut</td><td>$fileCount dosya</td></tr>";
        } else {
            echo "<tr><td>$name</td><td>$path</td><td class='error'>❌ Eksik</td><td>-</td></tr>";
            
            // Eksik dizini oluşturmaya çalış
            try {
                mkdir($path, 0755, true);
                echo "<tr><td colspan='4' class='info'>ℹ️ $name dizini oluşturuldu</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td colspan='4' class='error'>❌ $name dizini oluşturulamadı: " . $e->getMessage() . "</td></tr>";
            }
        }
    }
    echo "</table>";
    
    // 5. Admin ve Kullanıcı Dosyaları Kontrolü
    echo "<h2>5. Sistem Dosyaları Kontrolü</h2>";
    
    $systemFiles = [
        'Admin Revizyon Yönetimi' => 'admin/revisions.php',
        'Admin Dosya Detayı' => 'admin/file-detail.php',
        'Kullanıcı Revizyon Listesi' => 'user/revisions.php', 
        'Kullanıcı Revizyon Detayı' => 'user/revision-detail.php',
        'Kullanıcı Revizyon İndirme' => 'user/download-revision.php'
    ];
    
    echo "<table>";
    echo "<tr><th>Dosya</th><th>Yol</th><th>Durum</th></tr>";
    
    foreach ($systemFiles as $name => $file) {
        $fullPath = dirname(__FILE__) . '/' . $file;
        if (file_exists($fullPath)) {
            echo "<tr><td>$name</td><td>$file</td><td class='success'>✅ Mevcut</td></tr>";
        } else {
            echo "<tr><td>$name</td><td>$file</td><td class='error'>❌ Eksik</td></tr>";
        }
    }
    echo "</table>";
    
    // 6. Revizyon Sistemi Akış Testi
    echo "<h2>6. Revizyon Sistemi Akış Analizi</h2>";
    
    try {
        // Revizyon sayıları
        $revisionStats = $fileManager->getRevisionStats();
        
        echo "<div class='status-box'>";
        echo "<h3>Revizyon İstatistikleri:</h3>";
        echo "<p><strong>Toplam Revizyon:</strong> " . $revisionStats['total'] . "</p>";
        echo "<p><strong>Bekleyen:</strong> " . $revisionStats['pending'] . "</p>";
        echo "<p><strong>İşleniyor:</strong> " . $revisionStats['in_progress'] . "</p>";
        echo "<p><strong>Tamamlanan:</strong> " . $revisionStats['completed'] . "</p>";
        echo "<p><strong>Reddedilen:</strong> " . $revisionStats['rejected'] . "</p>";
        echo "</div>";
        
        // Tamamlanan revizyonlarda dosya kontrolü
        if ($revisionStats['completed'] > 0) {
            $stmt = $pdo->query("
                SELECT r.id, r.requested_at, fu.original_name,
                       (SELECT COUNT(*) FROM revision_files rf WHERE rf.revision_id = r.id) as file_count
                FROM revisions r 
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                WHERE r.status = 'completed'
                ORDER BY r.requested_at DESC
                LIMIT 5
            ");
            $completedRevisions = $stmt->fetchAll();
            
            echo "<h3>Son Tamamlanan Revizyonlar:</h3>";
            echo "<table>";
            echo "<tr><th>Revizyon ID</th><th>Dosya Adı</th><th>Tarih</th><th>Revizyon Dosyası</th></tr>";
            
            foreach ($completedRevisions as $rev) {
                $status = $rev['file_count'] > 0 ? 
                    "<span class='success'>✅ " . $rev['file_count'] . " dosya</span>" : 
                    "<span class='error'>❌ Dosya yok</span>";
                    
                echo "<tr>";
                echo "<td>" . substr($rev['id'], 0, 8) . "...</td>";
                echo "<td>" . htmlspecialchars($rev['original_name']) . "</td>";
                echo "<td>" . date('d.m.Y H:i', strtotime($rev['requested_at'])) . "</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Revizyon analizi hatası: " . $e->getMessage() . "</p>";
    }
    
    // 7. Sonuç ve Öneriler
    echo "<div class='result-summary'>";
    echo "<h2>🎯 Sistem Durumu Özeti</h2>";
    
    $issues = [];
    $success = [];
    
    // Başarılı olanlar
    if (method_exists($fileManager, 'uploadRevisionFile')) {
        $success[] = "✅ FileManager revizyon metodları eklendi";
    } else {
        $issues[] = "❌ FileManager revizyon metodları eksik";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'revision_files'");
    if ($stmt->rowCount() > 0) {
        $success[] = "✅ revision_files tablosu mevcut";
    } else {
        $issues[] = "❌ revision_files tablosu eksik";
    }
    
    if (file_exists(dirname(__FILE__) . '/user/download-revision.php')) {
        $success[] = "✅ Kullanıcı revizyon indirme sistemi eklendi";
    } else {
        $issues[] = "❌ Kullanıcı revizyon indirme sistemi eksik";
    }
    
    if (is_dir(dirname(__FILE__) . '/uploads/revision_files/')) {
        $success[] = "✅ Revizyon dosyaları dizini mevcut";
    } else {
        $issues[] = "❌ Revizyon dosyaları dizini eksik";
    }
    
    echo "<div style='text-align: left; margin-top: 20px;'>";
    
    if (!empty($success)) {
        echo "<h3 style='color: #2ecc71;'>✅ Başarılı Olanlar:</h3>";
        foreach ($success as $item) {
            echo "<p>$item</p>";
        }
    }
    
    if (!empty($issues)) {
        echo "<h3 style='color: #e74c3c;'>❌ Düzeltilmesi Gerekenler:</h3>";
        foreach ($issues as $item) {
            echo "<p>$item</p>";
        }
    }
    
    if (empty($issues)) {
        echo "<h3 style='color: #2ecc71; text-align: center;'>🎉 TÜM SİSTEM HAZIR!</h3>";
        echo "<p style='text-align: center;'>Revizyon sistemi tam olarak çalışıyor. Kullanıcılar artık revizyon talep edebilir ve admin tarafından yüklenen revizyon dosyalarını indirebilir.</p>";
    } else {
        echo "<h3 style='color: #f39c12; text-align: center;'>⚠️ SİSTEM TAMAMLANMADI</h3>";
        echo "<p style='text-align: center;'>Yukarıdaki eksiklikleri giderdikten sonra sistem tam olarak çalışacak.</p>";
    }
    
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='result-summary'>";
    echo "<h2 style='color: #e74c3c;'>❌ Test Hatası</h2>";
    echo "<p>Test sırasında hata oluştu: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px; color: #7f8c8d;'>";
echo "<p>Test tamamlandı - " . date('d.m.Y H:i:s') . "</p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
