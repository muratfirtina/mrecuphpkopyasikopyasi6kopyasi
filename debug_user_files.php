<?php
/**
 * Debug: Belirli kullanıcının processing dosyalarını kontrol et
 */
require_once 'config/config.php';
require_once 'config/database.php';

$targetUserId = $_GET['user_id'] ?? '';

if (!$targetUserId || !isValidUUID($targetUserId)) {
    die("Geçerli bir user_id parametresi gerekiyor.");
}

echo "<h2>Debug: Kullanıcının Processing Dosyaları</h2>";
echo "<p>Kontrol edilen kullanıcı ID: <strong>$targetUserId</strong></p>";

try {
    // Kullanıcı bilgilerini al
    $stmt = $pdo->prepare("SELECT username, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userInfo) {
        echo "<p>Kullanıcı: <strong>" . htmlspecialchars($userInfo['username'] . ' (' . $userInfo['first_name'] . ' ' . $userInfo['last_name'] . ')') . "</strong></p>";
    }

    // files.php kodundaki mantığı taklit et
    $search = '';
    $page = 1;
    $limit = 15;
    
    // FileManager'ı kullan
    $fileManager = new FileManager($pdo);
    
    echo "<h3>Normal Processing Dosyaları:</h3>";
    $normalFiles = $fileManager->getUserUploads($targetUserId, $page, $limit, 'processing', $search);
    $normalCount = $fileManager->getUserUploadCount($targetUserId, 'processing', $search);
    echo "<p>Bulunan normal processing dosya sayısı: <strong>" . count($normalFiles) . "</strong> (toplam: $normalCount)</p>";
    
    if (count($normalFiles) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Dosya ID</th><th>Dosya Adı</th><th>Durum</th><th>Yükleme Tarihi</th></tr>";
        foreach ($normalFiles as $file) {
            echo "<tr>";
            echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td>" . $file['status'] . "</td>";
            echo "<td>" . $file['upload_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Revize İşlenen Dosyaları:</h3>";
    
    // Revize işlenen dosyaları kontrol et
    $stmt = $pdo->prepare("
        SELECT DISTINCT fu.*, b.name as brand_name, m.name as model_name,
               r.response_id, r.request_notes, r.requested_at as revision_date, r.id as revision_id,
               rf.original_name as revision_filename
        FROM file_uploads fu
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        INNER JOIN revisions r ON fu.id = r.upload_id
        LEFT JOIN revision_files rf ON r.response_id = rf.id
        WHERE fu.user_id = ? 
        AND fu.status = 'completed' 
        AND r.status = 'in_progress'
        AND r.user_id = ?
        ORDER BY fu.upload_date DESC
        LIMIT " . intval($limit)
    );
    
    $params = [$targetUserId, $targetUserId];
    $stmt->execute($params);
    $revisionFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Her dosya için hedef dosyayı hesapla
    foreach ($revisionFiles as &$file) {
        $file['target_file_name'] = $file['original_name']; // Varsayılan
        $file['target_file_type'] = 'Orijinal Dosya';
        
        if (isset($file['revision_id']) && isset($file['revision_date'])) {
            try {
                $targetStmt = $pdo->prepare("
                    SELECT rf.original_name
                    FROM revisions r
                    JOIN revision_files rf ON r.id = rf.revision_id
                    WHERE r.upload_id = ? AND r.status = 'completed' AND r.requested_at < ?
                    ORDER BY r.completed_at DESC
                    LIMIT 1
                ");
                $targetStmt->execute([$file['id'], $file['revision_date']]);
                $previousRevisionFile = $targetStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($previousRevisionFile) {
                    $file['target_file_name'] = $previousRevisionFile['original_name'];
                    $file['target_file_type'] = 'Önceki Revizyon Dosyası';
                }
            } catch (Exception $e) {
                error_log('Debug hedef dosya bulma hatası: ' . $e->getMessage());
            }
        }
    }
    
    echo "<p>Bulunan revize işlenen dosya sayısı: <strong>" . count($revisionFiles) . "</strong></p>";
    
    if (count($revisionFiles) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Dosya ID</th><th>Ana Dosya</th><th>Revize Edilmesi İstenen</th><th>Yeni Revize Dosyası</th><th>Dosya Durumu</th><th>Marka</th><th>Model</th><th>Plaka</th><th>Revize Notları</th><th>Yükleme Tarihi</th></tr>";
        foreach ($revisionFiles as $file) {
            echo "<tr style='background-color: #e8f5e8;'>";
            echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            
            // Revize edilmesi istenen dosya
            echo "<td>";
            echo "<strong>" . htmlspecialchars($file['target_file_name']) . "</strong>";
            if ($file['target_file_type'] !== 'Orijinal Dosya') {
                echo "<br><small style='color: orange;'>" . $file['target_file_type'] . "</small>";
            }
            echo "</td>";
            
            // Yeni revize dosyası durumu
            if (!empty($file['revision_filename'])) {
                echo "<td><strong style='color: green;'>" . htmlspecialchars($file['revision_filename']) . "</strong></td>";
            } else {
                echo "<td><span style='color: orange;'><i>Hazırlanıyor...</i></span></td>";
            }
            
            echo "<td><strong>" . $file['status'] . "</strong></td>";
            echo "<td>" . htmlspecialchars($file['brand_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($file['model_name'] ?? 'N/A') . "</td>";
            echo "<td><strong>" . htmlspecialchars(strtoupper($file['plate'] ?? 'N/A')) . "</strong></td>";
            echo "<td>" . htmlspecialchars(substr($file['request_notes'] ?? 'N/A', 0, 50)) . "</td>";
            echo "<td>" . $file['upload_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Birleştirilmiş Liste (files.php mantığı):</h3>";
    
    // İki listeyi birleştir
    $allFiles = array_merge($normalFiles, $revisionFiles);
    echo "<p>Birleştirilen toplam dosya sayısı: <strong>" . count($allFiles) . "</strong></p>";
    
    // Tarihe göre sırala
    usort($allFiles, function($a, $b) {
        return strtotime($b['upload_date']) - strtotime($a['upload_date']);
    });
    
    // Sadece limit kadar al
    $userFiles = array_slice($allFiles, 0, $limit);
    echo "<p>Final liste dosya sayısı: <strong>" . count($userFiles) . "</strong></p>";
    
    if (count($userFiles) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Dosya ID</th><th>Dosya Adı</th><th>Durum</th><th>Yükleme Tarihi</th><th>Kaynak</th></tr>";
        foreach ($userFiles as $file) {
            $isRevisionFile = ($file['status'] === 'completed' && 
                isset($revisionFiles) && 
                in_array($file['id'], array_column($revisionFiles, 'id')));
            
            echo "<tr style='" . ($isRevisionFile ? "background-color: #e8f5e8;" : "") . "'>";
            echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td>" . $file['status'] . "</td>";
            echo "<td>" . $file['upload_date'] . "</td>";
            echo "<td>" . ($isRevisionFile ? "Revize İşlenen" : "Normal Processing") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Sonuç:</h3>";
    if (count($userFiles) > 0) {
        echo "<p style='color: green;'><strong>✅ Bu kullanıcı için files.php?status=processing sayfasında " . count($userFiles) . " dosya görünmeli!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Bu kullanıcı için files.php?status=processing sayfasında hiç dosya görünmeyecek!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
