<?php
/**
 * User Revisions - Basit HTML Debug
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Test kullanıcı ID'si
$userId = '3fbe9c59-53de-4bcd-a83b-21634f467203';

echo "<h2>User Revisions - HTML Table Test</h2>";

// User revisions sorgusu (user/revisions.php'deki aynı sorgu)
try {
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
    
    echo "<h3>Bulunan Revize Sayısı: " . count($revisions) . "</h3>";
    
    // User/revisions.php'deki target file mantığını uygula
    foreach ($revisions as &$revision) {
        // Hedef dosya bilgisini belirle (Admin sayfasındaki mantık)
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
        
        // Target file bilgilerini revision'a ekle
        $revision['target_file'] = [
            'type' => $targetFileType,
            'name' => $targetFileName,
            'color' => $targetFileColor,
            'icon' => $targetFileIcon,
            'is_found' => true
        ];
    }
    
    echo "<h3>User/Revisions.php Mantığına Göre HTML Table:</h3>";
    
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
    echo '<thead style="background-color: #f0f0f0;">';
    echo '<tr>';
    echo '<th>Revize ID</th>';
    echo '<th>Target File Type</th>';
    echo '<th>Target File Name</th>';
    echo '<th>Target File Color</th>';
    echo '<th>Request Notes</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($revisions as $index => $revision) {
        $bgColor = $index % 2 == 0 ? '#f9f9f9' : '#ffffff';
        echo '<tr style="background-color: ' . $bgColor . ';" data-revision-id="' . htmlspecialchars($revision['id']) . '">';
        echo '<td>' . substr($revision['id'], 0, 8) . '...</td>';
        echo '<td><strong style="color: ' . ($revision['target_file']['color'] == 'primary' ? 'blue' : ($revision['target_file']['color'] == 'warning' ? 'orange' : 'green')) . ';">' . htmlspecialchars($revision['target_file']['type']) . '</strong></td>';
        echo '<td>' . htmlspecialchars($revision['target_file']['name']) . '</td>';
        echo '<td>' . htmlspecialchars($revision['target_file']['color']) . '</td>';
        echo '<td>' . htmlspecialchars($revision['request_notes']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>Test Sonucu:</h3>";
echo "<p><strong>Beklenen:</strong> 2 farklı satır, farklı target file type'ları</p>";
echo "<p><strong>Satır 1:</strong> Revizyon Dosyası (orange) - ecedekor kartvizit (1) (1).zip</p>";
echo "<p><strong>Satır 2:</strong> Yanıt Dosyası (blue) - MAMP-MAMP-PRO-Downloader (1).zip</p>";

?>

<script>
console.log('=== USER REVISIONS HTML DEBUG ===');
const tableRows = document.querySelectorAll('tr[data-revision-id]');
console.log('Total table rows found:', tableRows.length);

tableRows.forEach((row, index) => {
    const revisionId = row.getAttribute('data-revision-id');
    const targetType = row.cells[1].textContent.trim();
    const targetName = row.cells[2].textContent.trim();
    console.log(`Row ${index + 1}:`, {
        revisionId: revisionId.substring(0, 8) + '...',
        targetType: targetType,
        targetName: targetName
    });
});
</script>
