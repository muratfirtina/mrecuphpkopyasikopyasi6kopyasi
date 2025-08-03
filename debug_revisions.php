<?php
/**
 * Debug: Revize işlenen dosyaları kontrol et
 */
require_once 'config/config.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    die("Giriş yapmanız gerekiyor.");
}

$userId = $_SESSION['user_id'];

echo "<h2>Debug: Revize İşlenen Dosyalar Kontrolü</h2>";
echo "<p>Kullanıcı ID: <strong>$userId</strong></p>";

try {
    // 1. Tüm revize taleplerini listele
    echo "<h3>1. Tüm Revize Talepleri:</h3>";
    $stmt = $pdo->prepare("
        SELECT r.id, r.status, r.requested_at, fu.original_name, fu.status as file_status
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        WHERE r.user_id = ?
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute([$userId]);
    $allRevisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Revizyon ID</th><th>Dosya Adı</th><th>Dosya Durumu</th><th>Revize Durumu</th><th>Tarih</th></tr>";
    foreach ($allRevisions as $rev) {
        echo "<tr>";
        echo "<td>" . substr($rev['id'], 0, 8) . "...</td>";
        echo "<td>" . htmlspecialchars($rev['original_name']) . "</td>";
        echo "<td>" . $rev['file_status'] . "</td>";
        echo "<td><strong>" . $rev['status'] . "</strong></td>";
        echo "<td>" . $rev['requested_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. İşleme alınan revize taleplerini listele
    echo "<h3>2. İşleme Alınan (in_progress) Revize Talepleri:</h3>";
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
    
    echo "<p><strong>Bulunan dosya sayısı: " . count($revisionFiles) . "</strong></p>";
    
    if (count($revisionFiles) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Dosya ID</th><th>Dosya Adı</th><th>Dosya Durumu</th><th>Revize Durumu</th><th>Revize Tarihi</th></tr>";
        foreach ($revisionFiles as $file) {
            echo "<tr>";
            echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td>" . $file['status'] . "</td>";
            echo "<td><strong>" . $file['revision_status'] . "</strong></td>";
            echo "<td>" . $file['revision_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>Hiç revize işlenen dosya bulunamadı!</strong></p>";
        echo "<p>Bu durumda files.php?status=processing sayfasında görünmeyecektir.</p>";
    }
    
    // 3. Processing durumundaki normal dosyalar
    echo "<h3>3. Normal Processing Dosyaları:</h3>";
    $stmt = $pdo->prepare("
        SELECT fu.*, b.name as brand_name, m.name as model_name
        FROM file_uploads fu
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.user_id = ? AND fu.status = 'processing'
        ORDER BY fu.upload_date DESC
    ");
    $stmt->execute([$userId]);
    $processingFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Bulunan dosya sayısı: " . count($processingFiles) . "</strong></p>";
    
    if (count($processingFiles) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Dosya ID</th><th>Dosya Adı</th><th>Durum</th><th>Marka</th><th>Model</th></tr>";
        foreach ($processingFiles as $file) {
            echo "<tr>";
            echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td>" . $file['status'] . "</td>";
            echo "<td>" . htmlspecialchars($file['brand_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($file['model_name'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Öneriler
    echo "<h3>4. Test Adımları:</h3>";
    echo "<ol>";
    echo "<li>Eğer 'İşleme Alınan Revize Talepleri' listesinde dosya varsa, bu dosyalar <strong>files.php?status=processing</strong> sayfasında görünmeli.</li>";
    echo "<li>Eğer hiç revize işlenen dosya yoksa, önce admin panelinde bir revize talebini 'işleme al' yapın.</li>";
    echo "<li>Sonra bu debug sayfasını yenileyin ve kontrol edin.</li>";
    echo "<li>Son olarak <a href='user/files.php?status=processing' target='_blank'>files.php?status=processing</a> sayfasına gidin.</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
