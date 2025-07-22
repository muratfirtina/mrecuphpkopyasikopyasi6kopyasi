<?php
/**
 * Mr ECU - Mark Notification as Read (User)
 * Bildirimi Okundu Olarak İşaretle
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

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek methodu.']);
    exit;
}

try {
    // JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['notification_id'])) {
        echo json_encode(['success' => false, 'message' => 'Bildirim ID gerekli.']);
        exit;
    }
    
    $notificationId = sanitize($input['notification_id']); // GUID olarak işle
    $userId = $_SESSION['user_id'];
    
    // NotificationManager kullan
    $notificationManager = new NotificationManager($pdo);
    $result = $notificationManager->markAsRead($notificationId, $userId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Bildirim okundu olarak işaretlendi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bildirim işaretlenemedi.']);
    }
    
} catch (Exception $e) {
    error_log('User mark notification read error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
