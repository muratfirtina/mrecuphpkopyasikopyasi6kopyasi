<?php
/**
 * User Revisions - Tam Debug (Admin mantığı ile)
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Test kullanıcı ID'si
$userId = '3fbe9c59-53de-4bcd-a83b-21634f467203';

echo "<h2>User Revisions Debug - Admin Mantığı Test</h2>";
echo "<p>User ID: " . htmlspecialchars($userId) . "</p>";

try {
    // User/revisions.php'deki TAM SORGUYU çalıştır
    $whereClause = "WHERE r.user_id = ?";
    $params = [$userId];
    $limit = 10;
    $page = 1;
    $offset = ($page - 1) * $limit;
    
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
        LIMIT $limit OFFSET $offset
    ");
    
    $stmt->execute($params);
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sorgu Sonucu: " . count($revisions) . " kayıt</h3>";
    
    // Her revizyon için admin mantığını uygula
    foreach ($revisions as $i => $revision) {
        echo "<div style='border: 2px solid #" . ($i == 0 ? "00aa00" : "0000aa") . "; margin: 15px; padding: 20px; background: #f9f9f9;'>";
        echo "<h4 style='color: #" . ($i == 0 ? "00aa00" : "0000aa") . ";'>Revize " . ($i + 1) . " - ID: " . substr($revision['id'], 0, 8) . "...</h4>";
        
        // Raw data
        echo "<div style='background: #eeeeee; padding: 10px; margin: 10px 0;'>";
        echo "<strong>RAW DATA:</strong><br>";
        echo "Upload ID: " . ($revision['upload_id'] ? substr($revision['upload_id'], 0, 8) . "..." : "YOK") . "<br>";
        echo "Response ID: " . ($revision['response_id'] ? substr($revision['response_id'], 0, 8) . "..." : "YOK") . "<br>";
        echo "Original Name: " . htmlspecialchars($revision['original_name'] ?? 'YOK') . "<br>";
        echo "Response Original Name: " . htmlspecialchars($revision['response_original_name'] ?? 'YOK') . "<br>";
        echo "Request Notes: " . htmlspecialchars($revision['request_notes']) . "<br>";
        echo "</div>";
        
        // Admin mantığını uygula - AYNEN user/revisions.php'den
        $targetFileName = 'Ana Dosya';
        $targetFileType = 'Orijinal Yüklenen Dosya';
        $targetFileColor = 'success';
        $targetFileIcon = 'file-alt';
        
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
        echo "<strong>TARGET FILE LOGIC (Admin Style):</strong><br>";
        
        if ($revision['response_id']) {
            // Yanıt dosyasına revize talebi
            $targetFileName = $revision['response_original_name'] ?? 'Yanıt Dosyası';
            $targetFileType = 'Yanıt Dosyası';
            $targetFileColor = 'primary';
            $targetFileIcon = 'reply';
            echo "✅ Response ID var → Yanıt Dosyası<br>";
            echo "Target FileName: " . htmlspecialchars($targetFileName) . "<br>";
        } else {
            echo "❌ Response ID yok → Ana dosya veya revizyon kontrol<br>";
            
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
                    echo "✅ Önceki revizyon bulundu: " . htmlspecialchars($targetFileName) . "<br>";
                } else {
                    $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
                    echo "❌ Önceki revizyon yok → Ana dosya: " . htmlspecialchars($targetFileName) . "<br>";
                }
            } catch (Exception $e) {
                echo "⚠️ Revizyon kontrol hatası: " . $e->getMessage() . "<br>";
                $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
            }
        }
        
        echo "</div>";
        
        echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>FINAL TARGET FILE:</strong><br>";
        echo "Tip: <strong style='color: " . ($targetFileColor == 'primary' ? 'blue' : ($targetFileColor == 'warning' ? 'orange' : 'green')) . ";'>" . htmlspecialchars($targetFileType) . "</strong><br>";
        echo "İsim: <strong>" . htmlspecialchars($targetFileName) . "</strong><br>";
        echo "Renk: " . htmlspecialchars($targetFileColor) . "<br>";
        echo "İkon: " . htmlspecialchars($targetFileIcon) . "<br>";
        echo "</div>";
        
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
