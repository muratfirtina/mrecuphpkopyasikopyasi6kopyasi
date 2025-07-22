<?php
/**
 * Mr ECU - Mark Notification as Read AJAX
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// JSON header
header('Content-Type: application/json');

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// POST ve AJAX kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bildirim ID gerekli']);
    exit;
}

$notificationId = $input['notification_id'];

// UUID formatını kontrol et
if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $notificationId)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz bildirim ID formatı']);
    exit;
}

try {
    // NotificationManager'ı dahil et
    require_once '../../includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Bildirimi okundu olarak işaretle
    $success = $notificationManager->markAsRead($notificationId, $_SESSION['user_id']);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Bildirim okundu olarak işaretlendi'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Bildirim işaretlenemedi'
        ]);
    }
    
} catch(Exception $e) {
    error_log('Mark notification read error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu'
    ]);
}
?>
