<?php
/**
 * Mr ECU - Generic Mark Notification Read Handler
 */

// AJAX için clean output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$notificationId = $input['notification_id'] ?? null;

if (empty($notificationId)) {
    echo json_encode(['success' => false, 'message' => 'Bildirim ID gereklidir.']);
    exit;
}

try {
    // NotificationManager'ı dahil et
    if (!class_exists('NotificationManager')) {
        require_once '../includes/NotificationManager.php';
    }
    
    if (class_exists('NotificationManager')) {
        $userId = $_SESSION['user_id'];
        $notificationManager = new NotificationManager($pdo);
        
        $result = $notificationManager->markAsRead($notificationId, $userId);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Bildirim okundu olarak işaretlendi.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Bildirim işaretlenemedi.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Bildirim sistemi bulunamadı.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Generic mark notification read error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
