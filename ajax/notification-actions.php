<?php
/**
 * Bildirim AJAX İşlemleri
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Session yapılandırması
ini_set('session.cookie_path', '/');
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

// Session name'ini kontrol et - MRECU_SECURE_SESSION varsa onu kullan
if (isset($_COOKIE['MRECU_SECURE_SESSION'])) {
    session_name('MRECU_SECURE_SESSION');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı girişi kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş gerekli']);
    exit;
}

// JSON header
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'mark_all_read':
            // Tüm bildirimleri okundu olarak işaretle
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                $updatedCount = $stmt->rowCount();
                echo json_encode([
                    'success' => true,
                    'message' => 'Tüm bildirimler okundu olarak işaretlendi',
                    'updated_count' => $updatedCount
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Güncelleme başarısız']);
            }
            break;
            
        case 'mark_read':
            // Tek bildirimi okundu olarak işaretle
            $notificationId = $_POST['notification_id'] ?? '';
            
            if (empty($notificationId)) {
                echo json_encode(['success' => false, 'message' => 'Bildirim ID gerekli']);
                break;
            }
            
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$notificationId, $userId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Bildirim okundu olarak işaretlendi'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Bildirim güncellenemedi']);
            }
            break;
            
        case 'get_count':
            // Okunmamış bildirim sayısını getir
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as unread_count
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'unread_count' => $result['unread_count'] ?? 0
            ]);
            break;
            
        case 'get_notifications':
            // Bildirimleri getir
            $limit = $_GET['limit'] ?? 10;
            $unreadOnly = isset($_GET['unread_only']) ? filter_var($_GET['unread_only'], FILTER_VALIDATE_BOOLEAN) : false;
            
            $whereClause = "WHERE user_id = ?";
            $params = [$userId];
            
            if ($unreadOnly) {
                $whereClause .= " AND is_read = 0";
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM notifications 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT " . intval($limit)
            );
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Notification AJAX Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sistem hatası oluştu: ' . $e->getMessage()]);
}
?>
