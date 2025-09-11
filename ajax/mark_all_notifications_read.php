<?php
/**
 * Mr ECU - Mark All Notifications Read (Root Level)
 * Tüm Bildirimleri Okundu Olarak İşaretle
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// NotificationManager'ı dahil et
if (!class_exists('NotificationManager')) {
    require_once '../includes/NotificationManager.php';
}

// JSON response header
header('Content-Type: application/json');

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir.']);
    exit;
}

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // NotificationManager kullan
    if (class_exists('NotificationManager')) {
        $notificationManager = new NotificationManager($pdo);
        $success = $notificationManager->markAllAsRead($userId);
        
        if ($success) {
            // Statik bildirimleri de temizle
            $_SESSION['dismissed_static_notifications'] = [
                'pending_revisions' => true,
                'low_credits' => true,
                'system_warnings' => true
            ];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tüm bildirimler okundu olarak işaretlendi.',
                'cleared_count' => $success
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bildirimler işaretlenemedi.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'NotificationManager bulunamadı.']);
    }
    
} catch (Exception $e) {
    error_log('Mark all notifications read error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
