<?php
/**
 * Mr ECU - Chat Notifications Handler
 * Chat mesajları için bildirim yönetimi
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

try {
    $userId = $_SESSION['user_id'];
    $action = $_GET['action'] ?? $_POST['action'] ?? 'count';
    
    // NotificationManager'ı dahil et
    if (!class_exists('NotificationManager')) {
        require_once '../includes/NotificationManager.php';
    }
    
    $notificationManager = new NotificationManager($pdo);
    
    switch ($action) {
        case 'count':
            // Chat mesajları için okunmamış bildirim sayısını getir
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM notifications 
                WHERE user_id = ? 
                AND type = 'chat_message' 
                AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            $chatNotificationCount = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'count' => $chatNotificationCount
            ]);
            break;
            
        case 'list':
            // Chat mesajları bildirimlerini listele
            $limit = intval($_GET['limit'] ?? 10);
            
            $stmt = $pdo->prepare("
                SELECT * 
                FROM notifications 
                WHERE user_id = ? 
                AND type = 'chat_message'
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            $chatNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'notifications' => $chatNotifications
            ]);
            break;
            
        case 'mark_read':
            // Chat bildirimini okundu olarak işaretle
            $notificationId = $_POST['notification_id'] ?? '';
            
            if (empty($notificationId)) {
                echo json_encode(['success' => false, 'message' => 'Bildirim ID gerekli.']);
                exit;
            }
            
            $result = $notificationManager->markAsRead($notificationId, $userId);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Bildirim okundu olarak işaretlendi.' : 'İşlem başarısız.'
            ]);
            break;
            
        case 'mark_all_chat_read':
            // Tüm chat bildirimlerini okundu olarak işaretle
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE, read_at = NOW() 
                WHERE user_id = ? 
                AND type = 'chat_message' 
                AND is_read = FALSE
            ");
            $result = $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Tüm chat bildirimleri okundu olarak işaretlendi.' : 'İşlem başarısız.'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Chat notifications error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
