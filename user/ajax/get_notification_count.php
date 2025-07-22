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
    $count = $notificationManager->getUnreadCount($userId);
    
    echo json_encode([
        'success' => true, 
        'count' => $count
    ]);
    
} catch (Exception $e) {
    error_log('User get notification count error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
