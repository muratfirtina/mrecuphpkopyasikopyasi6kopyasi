<?php
/**
 * Mr ECU - Mark Notification Read (Admin Level)
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

// DEBUG: Geçici olarak session kontrolünü kapat
$debug_mode = true; // Test için true yap

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir.']);
    exit;
}

// Giriş kontrolü
if (!$debug_mode && !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    $notificationId = $input['notification_id'] ?? null;
    
    if (!$notificationId) {
        echo json_encode(['success' => false, 'message' => 'Bildirim ID gerekli.']);
        exit;
    }
    
    // DEBUG: Test için hardcode user ID kullan
    if ($debug_mode) {
        $userId = 'e3685b9a-e4ce-4f5e-a6d8-8c02d50dbe43'; // maildeneme admin user
    } else {
        $userId = $_SESSION['user_id'];
    }
    
    // NotificationManager kullan
    if (class_exists('NotificationManager')) {
        $notificationManager = new NotificationManager($pdo);
        $success = $notificationManager->markAsRead($notificationId, $userId);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Bildirim okundu olarak işaretlendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bildirim işaretlenemedi.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'NotificationManager bulunamadı.']);
    }
    
} catch (Exception $e) {
    error_log('Mark notification read error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
