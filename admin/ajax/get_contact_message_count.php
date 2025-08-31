<?php
/**
 * AJAX - Contact Message Count
 * İletişim mesaj sayısını döndürür
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Yeni mesaj sayısını al
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
    $stmt->execute();
    $new_count = $stmt->fetchColumn();
    
    // Toplam mesaj sayısını al
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages");
    $stmt->execute();
    $total_count = $stmt->fetchColumn();
    
    // Durum bazlı sayıları al
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status");
    $status_counts = [];
    while ($row = $stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    echo json_encode([
        'success' => true,
        'new_count' => (int)$new_count,
        'total_count' => (int)$total_count,
        'status_counts' => $status_counts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>