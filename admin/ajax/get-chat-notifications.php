<?php
/**
 * Mr ECU - Get Chat Notifications (Admin Level)
 * Chat Bildirimlerini Getir
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'count':
            // Chat bildirimleri sayısını getir
            try {
                // Okunmamış chat mesajları sayısını hesapla
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM chat_messages 
                    WHERE is_read = 0 
                    AND sender_id != ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $unreadCount = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'count' => (int)$unreadCount
                ]);
                
            } catch(PDOException $e) {
                // Chat tablosu yoksa 0 döndür
                error_log('Chat table not found or error: ' . $e->getMessage());
                echo json_encode([
                    'success' => true,
                    'count' => 0,
                    'note' => 'Chat system not available'
                ]);
            }
            break;
            
        case 'list':
            // Chat bildirimlerini listele
            try {
                $stmt = $pdo->prepare("
                    SELECT cm.*, u.username, u.email 
                    FROM chat_messages cm
                    LEFT JOIN users u ON cm.sender_id = u.id
                    WHERE cm.is_read = 0 
                    AND cm.sender_id != ?
                    ORDER BY cm.created_at DESC 
                    LIMIT 10
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'messages' => $messages
                ]);
                
            } catch(PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Chat mesajları alınamadı.',
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz işlem.'
            ]);
    }
    
} catch (Exception $e) {
    error_log('Admin chat notifications error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
