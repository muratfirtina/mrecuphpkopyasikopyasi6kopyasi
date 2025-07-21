<?php
/**
 * Mr ECU - Mark Notification as Read (Admin)
 * Bildirimi Okundu Olarak İşaretle
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// NotificationManager'ı dahil et
if (!class_exists('NotificationManager')) {
    require_once '../../includes/NotificationManager.php';
}

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn() || !isAdmin()) {
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
    
    // Statik bildirimler için özel işlem (pending_files, pending_revisions vs.)
    $staticNotifications = ['pending_files', 'pending_revisions', 'system_status', 'low_credits'];
    
    if (in_array($notificationId, $staticNotifications)) {
        // Statik bildirimleri session'da işaretli olarak sakla
        if (!isset($_SESSION['dismissed_static_notifications'])) {
            $_SESSION['dismissed_static_notifications'] = [];
        }
        $_SESSION['dismissed_static_notifications'][$notificationId] = time();
        
        echo json_encode(['success' => true, 'message' => 'Statik bildirim işaretlendi.', 'static' => true]);
    } else {
        // NotificationManager kullan
        $notificationManager = new NotificationManager($pdo);
        $result = $notificationManager->markAsRead($notificationId, $userId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Bildirim okundu olarak işaretlendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bildirim işaretlenemedi.']);
        }
    }
    
} catch (Exception $e) {
    error_log('Admin mark notification read error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
