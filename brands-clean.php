<?php
/**
 * Brands Temizleme Script
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die("Bu sayfaya erişim izniniz yok!");
}

$action = $_GET['action'] ?? '';
$confirmed = $_GET['confirm'] ?? '';

echo "<h1>🧹 Brands Temizleme Script</h1>";

if ($action === 'clean' && $confirmed === 'yes') {
    echo "<h2>⚠️ Temizleme İşlemi Başlatılıyor...</h2>";
    
    try {
        $pdo->beginTransaction();
        
        // Çift kayıtları bul
        $stmt = $pdo->query("
            SELECT name, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids 
            FROM brands 
            GROUP BY name 
            HAVING COUNT(*) > 1
            ORDER BY name
        ");
        $duplicates = $stmt->fetchAll();
        
        $totalDeleted = 0;
        
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['ids']);
            $keepId = min($ids); // En küçük ID'yi koru
            $deleteIds = array_filter($ids, function($id) use ($keepId) {
                return $id != $keepId;
            });
            
            if (!empty($deleteIds)) {
                echo "<p>🔄 {$dup['name']} markası için temizleme...</p>";
                echo "<p>• Korunan ID: $keepId</p>";
                echo "<p>• Silinen ID'ler: " . implode(', ', $deleteIds) . "</p>";
                
                // Models tablosundaki referansları güncelle
                foreach ($deleteIds as $deleteId) {
                    $updateStmt = $pdo->prepare("UPDATE models SET brand_id = ? WHERE brand_id = ?");
                    $updateStmt->execute([intval($keepId), intval($deleteId)]);
                    $updatedModels = $updateStmt->rowCount();
                    
                    if ($updatedModels > 0) {
                        echo "<p>• $updatedModels model kaydı ID $deleteId'den ID $keepId'ye aktarıldı</p>";
                    }
                }
                
                // file_uploads tablosundaki referansları güncelle
                foreach ($deleteIds as $deleteId) {
                    $updateStmt = $pdo->prepare("UPDATE file_uploads SET brand_id = ? WHERE brand_id = ?");
                    $updateStmt->execute([intval($keepId), intval($deleteId)]);
                    $updatedUploads = $updateStmt->rowCount();
                    
                    if ($updatedUploads > 0) {
                        echo "<p>• $updatedUploads dosya kaydı ID $deleteId'den ID $keepId'ye aktarıldı</p>";
                    }
                }
                
                // Çift kayıtları tek tek sil
                foreach ($deleteIds as $deleteId) {
                    $deleteStmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
                    $deleteStmt->execute([intval($deleteId)]);
                    $deletedCount = $deleteStmt->rowCount();
                    
                    if ($deletedCount > 0) {
                        echo "<p style='color:green;'>✅ ID $deleteId silindi</p>";
                        $totalDeleted += $deletedCount;
                    }
                }
                echo "<hr>";
            }
        }
        
        $pdo->commit();
        
        echo "<h3 style='color:green;'>🎉 Temizleme Tamamlandı!</h3>";
        echo "<p>Toplam silinen kayıt: <strong>$totalDeleted</strong></p>";
        
        if ($totalDeleted > 0) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>✅ Yapılan İşlemler:</h4>";
            echo "<ul>";
            echo "<li>Çift marka kayıtları silindi</li>";
            echo "<li>Model referansları en küçük ID'ye aktarıldı</li>";
            echo "<li>Dosya upload referansları güncellendi</li>";
            echo "<li>Veritabanı tutarlılığı korundu</li>";
            echo "</ul>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p style='color:red;'>❌ Hata oluştu: " . $e->getMessage() . "</p>";
    }
    
} else {
    // Önce durumu kontrol et
    try {
        $stmt = $pdo->query("
            SELECT name, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids 
            FROM brands 
            GROUP BY name 
            HAVING COUNT(*) > 1
            ORDER BY name
        ");
        $duplicates = $stmt->fetchAll();
        
        if (empty($duplicates)) {
            echo "<p style='color:green;'>✅ Çift kayıt bulunamadı! Temizleme gerekmiyor.</p>";
        } else {
            echo "<h2>⚠️ Bulunan Çift Kayıtlar:</h2>";
            echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
            echo "<tr><th>Marka Adı</th><th>Kayıt Sayısı</th><th>ID'ler</th><th>İşlem</th></tr>";
            
            foreach ($duplicates as $dup) {
                $ids = explode(',', $dup['ids']);
                $keepId = min($ids);
                $deleteIds = array_filter($ids, function($id) use ($keepId) {
                    return $id != $keepId;
                });
                
                echo "<tr>";
                echo "<td><strong>{$dup['name']}</strong></td>";
                echo "<td style='text-align:center;'>{$dup['count']}</td>";
                echo "<td>{$dup['ids']}</td>";
                echo "<td>Koru: $keepId, Sil: " . implode(',', $deleteIds) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<br>";
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>⚠️ Dikkat!</h4>";
            echo "<p>Bu işlem geri alınamaz! Çift kayıtlar silinecek ve referanslar güncellencek.</p>";
            echo "<ul>";
            echo "<li>En küçük ID'li kayıtlar korunacak</li>";
            echo "<li>Model ve dosya referansları otomatik güncellenecek</li>";
            echo "<li>Veritabanı tutarlılığı korunacak</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<p>";
            echo "<a href='?action=clean&confirm=yes' onclick='return confirm(\"Çift kayıtları silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!\")' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🗑️ Çift Kayıtları Temizle</a>";
            echo "<a href='brands-debug.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Debug Sayfasına Dön</a>";
            echo "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
    }
}

echo "<br><br>";
echo "<a href='admin/brands.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Brands Yönetimine Dön</a>";
?>
