<?php
/**
 * Admin Debug: Tüm revize işlenen dosyaları kontrol et
 */
require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die("Admin yetkisi gerekiyor.");
}

echo "<h2>Admin Debug: Tüm Revize İşlenen Dosyalar</h2>";

try {
    // 1. Tüm in_progress revize taleplerini listele
    echo "<h3>1. Tüm İşleme Alınan (in_progress) Revize Talepleri:</h3>";
    $stmt = $pdo->prepare("
        SELECT r.id, r.status, r.requested_at, r.user_id, 
               fu.original_name, fu.status as file_status,
               u.username, u.first_name, u.last_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status = 'in_progress'
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute();
    $inProgressRevisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Bulunan in_progress revize sayısı: " . count($inProgressRevisions) . "</strong></p>";
    
    if (count($inProgressRevisions) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Revizyon ID</th><th>Kullanıcı</th><th>User ID</th><th>Dosya Adı</th><th>Dosya Durumu</th><th>Revize Durumu</th><th>Tarih</th></tr>";
        foreach ($inProgressRevisions as $rev) {
            echo "<tr style='background-color: #e8f5e8;'>";
            echo "<td>" . substr($rev['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($rev['username'] . ' (' . $rev['first_name'] . ' ' . $rev['last_name'] . ')') . "</td>";
            echo "<td style='font-size: 10px;'>" . $rev['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($rev['original_name']) . "</td>";
            echo "<td><strong>" . $rev['file_status'] . "</strong></td>";
            echo "<td><strong style='color: blue;'>" . $rev['status'] . "</strong></td>";
            echo "<td>" . $rev['requested_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Her kullanıcı için ayrı kontrol
        echo "<h3>2. Kullanıcı Bazında Kontrol:</h3>";
        $userIds = array_unique(array_column($inProgressRevisions, 'user_id'));
        
        foreach ($userIds as $userId) {
            $userInfo = array_filter($inProgressRevisions, function($rev) use ($userId) {
                return $rev['user_id'] === $userId;
            });
            $userInfo = array_values($userInfo)[0]; // İlk kaydı al
            
            echo "<h4>Kullanıcı: " . htmlspecialchars($userInfo['username']) . " (ID: " . substr($userId, 0, 8) . "...)</h4>";
            
            // Bu kullanıcı için revize işlenen dosyaları sorgula
            $stmt = $pdo->prepare("
                SELECT DISTINCT fu.*, r.status as revision_status, r.requested_at as revision_date
                FROM file_uploads fu
                INNER JOIN revisions r ON fu.id = r.upload_id
                WHERE fu.user_id = ? 
                AND fu.status = 'completed' 
                AND r.status = 'in_progress'
                AND r.user_id = ?
                ORDER BY fu.upload_date DESC
            ");
            $stmt->execute([$userId, $userId]);
            $revisionFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Bu kullanıcının revize işlenen dosya sayısı: <strong>" . count($revisionFiles) . "</strong></p>";
            
            if (count($revisionFiles) > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
                echo "<tr><th>Dosya ID</th><th>Dosya Adı</th><th>Dosya Durumu</th><th>Revize Durumu</th><th>Revize Tarihi</th></tr>";
                foreach ($revisionFiles as $file) {
                    echo "<tr style='background-color: #fff3cd;'>";
                    echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
                    echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
                    echo "<td>" . $file['status'] . "</td>";
                    echo "<td><strong>" . $file['revision_status'] . "</strong></td>";
                    echo "<td>" . $file['revision_date'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Bu kullanıcı için files.php?status=processing linkini oluştur
                echo "<p><strong>Test bağlantısı:</strong> <a href='../debug_user_files.php?user_id=" . urlencode($userId) . "' target='_blank'>Bu kullanıcının processing dosyalarını gör</a></p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'><strong>Hiç in_progress durumunda revize talebi bulunamadı!</strong></p>";
        echo "<p>Admin panelinde bir revize talebini 'işleme al' yapmanız gerekiyor.</p>";
    }
    
    // 3. Tüm revize durumlarını göster
    echo "<h3>3. Tüm Revize Talepleri Durum Özeti:</h3>";
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM revisions 
        GROUP BY status
    ");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Durum</th><th>Sayı</th></tr>";
    foreach ($statusCounts as $sc) {
        echo "<tr>";
        echo "<td>" . $sc['status'] . "</td>";
        echo "<td>" . $sc['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
