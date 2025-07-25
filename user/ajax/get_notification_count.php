<?php
/**
 * Mr ECU - Get Notification Count (User)
 * Bildirim Sayısını Getir
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/NotificationManager.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // NotificationManager kullan
    $notificationManager = new NotificationManager($pdo);
    $unreadNotificationCount = $notificationManager->getUnreadCount($userId);
    
    // Kullanıcının bekleyen revize talepleri
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingUserRevisions = $stmt->fetchColumn();

    // Tamamlanan dosyalar (henüz bildirilmemiş)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND status = 'completed' AND notified = 0");
    $stmt->execute([$userId]);
    $completedFiles = $stmt->fetchColumn();
    
    // Badge için sadece okunmamış bildirimleri say
    $badgeNotificationCount = $unreadNotificationCount + $pendingUserRevisions + $completedFiles;
    
    echo json_encode([
        'success' => true, 
        'count' => $badgeNotificationCount
    ]);
    
} catch (Exception $e) {
    error_log('User get notification count error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
