<?php
/**
 * Mr ECU - Mark All Notifications as Read (Admin)
 * Tüm Bildirimleri Okundu Olarak İşaretle
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
    $userId = $_SESSION['user_id'];
    
    // NotificationManager kullan
    $notificationManager = new NotificationManager($pdo);
    $result = $notificationManager->markAllAsRead($userId);
    
    // Tüm statik bildirimleri de işaretlе
    $staticNotifications = ['pending_files', 'pending_revisions', 'system_status', 'low_credits'];
    
    if (!isset($_SESSION['dismissed_static_notifications'])) {
        $_SESSION['dismissed_static_notifications'] = [];
    }
    
    foreach ($staticNotifications as $staticId) {
        $_SESSION['dismissed_static_notifications'][$staticId] = time();
    }
    
    if ($result || count($staticNotifications) > 0) {
        echo json_encode(['success' => true, 'message' => 'Tüm bildirimler okundu olarak işaretlendi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bildirimler işaretlenemedi.']);
    }
    
} catch (Exception $e) {
    error_log('Admin mark all notifications read error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
