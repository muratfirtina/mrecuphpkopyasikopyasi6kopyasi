<?php
/**
 * Mevcut Revizyon Talebini "in_progress" Durumuna Getir
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Revizyon Durumu Güncelleme</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; }</style></head><body>";

echo "<h1>🔄 Revizyon Durumu Güncelleme</h1>";

try {
    // Mevcut revizyonları listele
    $stmt = $pdo->query("
        SELECT r.*, fu.original_name, u.username, u.first_name, u.last_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.requested_at DESC
    ");
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📋 Mevcut Revizyon Talepleri:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Kullanıcı</th><th>Dosya</th><th>Durum</th><th>Tarih</th><th>İşlem</th>";
    echo "</tr>";
    
    foreach ($revisions as $revision) {
        $statusColor = [
            'pending' => '#ffc107',
            'in_progress' => '#17a2b8', 
            'completed' => '#28a745',
            'rejected' => '#dc3545'
        ];
        $color = $statusColor[$revision['status']] ?? '#6c757d';
        
        echo "<tr>";
        echo "<td>" . substr($revision['id'], 0, 8) . "...</td>";
        echo "<td>" . htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($revision['original_name']) . "</td>";
        echo "<td style='background: {$color}; color: white; text-align: center;'>" . $revision['status'] . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($revision['requested_at'])) . "</td>";
        
        if ($revision['status'] === 'pending') {
            echo "<td>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='revision_id' value='" . $revision['id'] . "'>";
            echo "<button type='submit' name='make_in_progress' style='background: #17a2b8; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>İşleme Al</button>";
            echo "</form>";
            echo "</td>";
        } else {
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // POST işlemi - Revizyon durumunu güncelle
    if (isset($_POST['make_in_progress']) && isset($_POST['revision_id'])) {
        $revisionId = $_POST['revision_id'];
        
        // Admin ID'yi session'dan al (test için varsayılan kullanıcı)
        $adminId = $_SESSION['user_id'] ?? '123e4567-e89b-12d3-a456-426614174000'; // Test admin ID
        
        $stmt = $pdo->prepare("
            UPDATE revisions 
            SET status = 'in_progress', 
                admin_id = ?, 
                admin_notes = 'Revizyon talebi işleme alındı - Admin panel üzerinden',
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$adminId, $revisionId])) {
            echo "<div class='success'>";
            echo "<h3>✅ Başarılı!</h3>";
            echo "<p>Revizyon talebi 'in_progress' durumuna getirildi.</p>";
            echo "<p><strong>Şimdi yapılacaklar:</strong></p>";
            echo "<ol>";
            echo "<li>Admin file-detail.php sayfasına git</li>";
            echo "<li>Bu dosyayı aç</li>";
            echo "<li>Revizyon dosyası yükleme formu görünecek</li>";
            echo "<li>Revizyon dosyasını yükle</li>";
            echo "</ol>";
            echo "</div>";
            
            // Sayfa yenile
            echo "<script>setTimeout(function(){ location.reload(); }, 3000);</script>";
        } else {
            echo "<p class='error'>❌ Revizyon durumu güncellenemedi.</p>";
        }
    }
    
    // Henüz pending durumdaki revizyon yoksa test verisi oluştur
    $pendingCount = 0;
    foreach ($revisions as $revision) {
        if ($revision['status'] === 'pending') {
            $pendingCount++;
        }
    }
    
    if ($pendingCount === 0 && count($revisions) === 0) {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ Test Verisi Oluşturulsun mu?</h3>";
        echo "<p>Henüz revizyon talebi yok. Test için örnek veri oluşturmak ister misiniz?</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='create_test_revision' style='background: #ffc107; border: none; padding: 10px 20px; border-radius: 5px;'>Test Revizyon Talebi Oluştur</button>";
        echo "</form>";
        echo "</div>";
    }
    
    // Test revizyon talebi oluştur
    if (isset($_POST['create_test_revision'])) {
        // Mevcut bir file_upload'ı bul
        $stmt = $pdo->query("SELECT id, user_id, original_name FROM file_uploads WHERE status = 'completed' LIMIT 1");
        $upload = $stmt->fetch();
        
        if ($upload) {
            $revisionId = 'test-' . uniqid();
            $stmt = $pdo->prepare("
                INSERT INTO revisions (id, upload_id, user_id, request_notes, status, requested_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $revisionId,
                $upload['id'],
                $upload['user_id'],
                'Test revizyon talebi - Dosyada küçük düzeltmeler yapılması gerekiyor. Lütfen X parametresini Y değerine güncelleyin.'
            ]);
            
            if ($result) {
                echo "<p class='success'>✅ Test revizyon talebi oluşturuldu!</p>";
                echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
            }
        } else {
            echo "<p class='error'>❌ Test için uygun dosya bulunamadı.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><hr><br>";
echo "<h2>📋 Sonraki Adımlar:</h2>";
echo "<ol>";
echo "<li>Yukarıdaki tablodan bir revizyon talebini 'İşleme Al' butonuna tıklayarak in_progress durumuna getirin</li>";
echo "<li><a href='admin/file-detail.php?id=[UPLOAD_ID]' target='_blank'>Admin dosya detay sayfasına</a> gidin</li>";
echo "<li>Revizyon dosyası yükleme formu görünecek</li>";
echo "<li>Dosya yükleyip test edin</li>";
echo "</ol>";

echo "</body></html>";
?>
