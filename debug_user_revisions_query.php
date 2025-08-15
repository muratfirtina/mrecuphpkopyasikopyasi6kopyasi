<?php
/**
 * User Revisions Debug - Sorgunun Tam Sonucunu Görme
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Test kullanıcı ID'si
$userId = '3fbe9c59-53de-4bcd-a83b-21634f467203';

echo "<h2>User Revisions Sayfasının Döndürdüğü Veri</h2>";
echo "<p>User ID: " . htmlspecialchars($userId) . "</p>";

try {
    // User/revisions.php'deki tam sorguyu çalıştıralım
    $whereClause = "WHERE r.user_id = ?";
    $params = [$userId];
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               fu.original_name, fu.filename, fu.file_size, fu.plate, fu.year, fu.upload_date,
               b.name as brand_name, m.name as model_name,
               s.name as series_name, e.name as engine_name,
               a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
               fr.original_name as response_original_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        LEFT JOIN series s ON fu.series_id = s.id
        LEFT JOIN engines e ON fu.engine_id = e.id
        LEFT JOIN users a ON r.admin_id = a.id
        LEFT JOIN file_responses fr ON r.response_id = fr.id
        $whereClause
        ORDER BY r.requested_at DESC
    ");
    
    $stmt->execute($params);
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sorgu Sonucu: " . count($revisions) . " kayıt</h3>";
    
    foreach ($revisions as $i => $revision) {
        echo "<div style='border: 2px solid #ccc; margin: 10px; padding: 15px;'>";
        echo "<h4>Revize " . ($i + 1) . "</h4>";
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><td><strong>Revize ID:</strong></td><td>" . substr($revision['id'], 0, 8) . "...</td></tr>";
        echo "<tr><td><strong>Upload ID:</strong></td><td>" . substr($revision['upload_id'], 0, 8) . "...</td></tr>";
        echo "<tr><td><strong>Response ID:</strong></td><td>" . ($revision['response_id'] ? substr($revision['response_id'], 0, 8) . "..." : "YOK") . "</td></tr>";
        echo "<tr><td><strong>Durum:</strong></td><td>" . htmlspecialchars($revision['status']) . "</td></tr>";
        echo "<tr><td><strong>Tarih:</strong></td><td>" . date('d.m.Y H:i', strtotime($revision['requested_at'])) . "</td></tr>";
        echo "<tr><td><strong>Ana Dosya:</strong></td><td>" . htmlspecialchars($revision['original_name'] ?? 'YOK') . "</td></tr>";
        echo "<tr><td><strong>Yanıt Dosyası:</strong></td><td>" . htmlspecialchars($revision['response_original_name'] ?? 'YOK') . "</td></tr>";
        echo "<tr><td><strong>Talep Notları:</strong></td><td>" . htmlspecialchars($revision['request_notes']) . "</td></tr>";
        echo "</table>";
        
        // Target file belirleme mantığını test et
        $targetFileName = 'Ana Dosya';
        $targetFileType = 'Orijinal Yüklenen Dosya';
        $targetFileColor = 'success';
        $targetFileIcon = 'file-alt';
        
        if ($revision['response_id']) {
            // Yanıt dosyasına revize talebi
            $targetFileName = $revision['response_original_name'] ?? 'Yanıt Dosyası';
            $targetFileType = 'Yanıt Dosyası';
            $targetFileColor = 'primary';
            $targetFileIcon = 'reply';
        } else {
            // Ana dosya veya revizyon dosyasına revize talebi
            // Önceki revizyon dosyaları var mı kontrol et
            try {
                $stmt2 = $pdo->prepare("
                    SELECT rf.original_name 
                    FROM revisions r1
                    JOIN revision_files rf ON r1.id = rf.revision_id
                    WHERE r1.upload_id = ? 
                    AND r1.status = 'completed'
                    AND r1.requested_at < ?
                    ORDER BY r1.requested_at DESC 
                    LIMIT 1
                ");
                $stmt2->execute([$revision['upload_id'], $revision['requested_at']]);
                $previousRevisionFile = $stmt2->fetch(PDO::FETCH_ASSOC);
                
                if ($previousRevisionFile) {
                    $targetFileName = $previousRevisionFile['original_name'];
                    $targetFileType = 'Revizyon Dosyası';
                    $targetFileColor = 'warning';
                    $targetFileIcon = 'edit';
                } else {
                    $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
                }
            } catch (Exception $e) {
                error_log('Previous revision file query error: ' . $e->getMessage());
                $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
            }
        }
        
        echo "<div style='background: #f0f0f0; padding: 10px; margin-top: 10px;'>";
        echo "<strong>Belirlenen Hedef Dosya:</strong><br>";
        echo "Tip: " . htmlspecialchars($targetFileType) . "<br>";
        echo "İsim: " . htmlspecialchars($targetFileName) . "<br>";
        echo "Renk: " . htmlspecialchars($targetFileColor) . "<br>";
        echo "İkon: " . htmlspecialchars($targetFileIcon) . "<br>";
        echo "</div>";
        
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
