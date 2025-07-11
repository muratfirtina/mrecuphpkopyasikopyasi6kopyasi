<?php
/**
 * Mr ECU - Get Notification Count (Admin)
 * Bildirim Sayısını Getir
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn() || !isAdmin()) {
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
    error_log('Admin get notification count error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
