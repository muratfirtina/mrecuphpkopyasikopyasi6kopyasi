<?php
/**
 * Revize Verileri Debug Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Test kullanıcı ID'si (debug sayfasından aldığımız)
$userId = '3fbe9c59-53de-4bcd-a83b-21634f467203';

echo "<h2>Revize Verileri Debug - User ID: " . htmlspecialchars($userId) . "</h2>";

try {
    // Kullanıcının tüm revize taleplerini getir
    $stmt = $pdo->prepare("
        SELECT r.id, r.upload_id, r.response_id, r.status, r.requested_at, r.request_notes,
               fu.original_name as main_file_name,
               fr.original_name as response_file_name,
               b.name as brand_name, m.name as model_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN file_responses fr ON r.response_id = fr.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE r.user_id = ?
        ORDER BY r.requested_at DESC
    ");
    
    $stmt->execute([$userId]);
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Bulunan Revize Sayısı: " . count($revisions) . "</h3>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>";
    echo "<th>Revize ID</th>";
    echo "<th>Upload ID</th>";
    echo "<th>Response ID</th>";
    echo "<th>Durum</th>";
    echo "<th>Tarih</th>";
    echo "<th>Ana Dosya</th>";
    echo "<th>Yanıt Dosyası</th>";
    echo "<th>Araç</th>";
    echo "<th>Talep Notları</th>";
    echo "</tr>";
    
    foreach ($revisions as $revision) {
        echo "<tr>";
        echo "<td>" . substr($revision['id'], 0, 8) . "...</td>";
        echo "<td>" . substr($revision['upload_id'], 0, 8) . "...</td>";
        echo "<td>" . ($revision['response_id'] ? substr($revision['response_id'], 0, 8) . "..." : "YOK") . "</td>";
        echo "<td>" . htmlspecialchars($revision['status']) . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($revision['requested_at'])) . "</td>";
        echo "<td>" . htmlspecialchars($revision['main_file_name'] ?? 'YOK') . "</td>";
        echo "<td>" . htmlspecialchars($revision['response_file_name'] ?? 'YOK') . "</td>";
        echo "<td>" . htmlspecialchars($revision['brand_name'] . ' ' . $revision['model_name']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($revision['request_notes'], 0, 50)) . "...</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // File uploads kontrol
    echo "<h3>Kullanıcının Dosyaları:</h3>";
    $stmt = $pdo->prepare("
        SELECT id, original_name, status, upload_date
        FROM file_uploads 
        WHERE user_id = ? 
        ORDER BY upload_date DESC
    ");
    $stmt->execute([$userId]);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Upload ID</th><th>Dosya Adı</th><th>Durum</th><th>Tarih</th></tr>";
    foreach ($uploads as $upload) {
        echo "<tr>";
        echo "<td>" . substr($upload['id'], 0, 8) . "...</td>";
        echo "<td>" . htmlspecialchars($upload['original_name']) . "</td>";
        echo "<td>" . htmlspecialchars($upload['status']) . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($upload['upload_date'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // File responses kontrol
    echo "<h3>Yanıt Dosyaları:</h3>";
    $stmt = $pdo->prepare("
        SELECT fr.id, fr.original_name, fr.upload_date, fu.original_name as main_file
        FROM file_responses fr
        LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
        WHERE fu.user_id = ?
        ORDER BY fr.upload_date DESC
    ");
    $stmt->execute([$userId]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Response ID</th><th>Yanıt Dosyası</th><th>Ana Dosya</th><th>Tarih</th></tr>";
    foreach ($responses as $response) {
        echo "<tr>";
        echo "<td>" . substr($response['id'], 0, 8) . "...</td>";
        echo "<td>" . htmlspecialchars($response['original_name']) . "</td>";
        echo "<td>" . htmlspecialchars($response['main_file']) . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($response['upload_date'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
