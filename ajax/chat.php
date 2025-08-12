<?php
/**
 * Mr ECU - Chat AJAX Handler
 * Chat mesajlaşma için AJAX endpoint'leri
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/ChatManager.php';

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';
$isAdmin = ($userRole === 'admin');

// Chat Manager instance
$chatManager = new ChatManager($pdo);

// Action kontrolü
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'send_message':
        // Mesaj gönderme
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
            exit;
        }
        
        $fileId = $_POST['file_id'] ?? '';
        $fileType = $_POST['file_type'] ?? 'upload';
        $message = trim($_POST['message'] ?? '');
        
        // Validasyon
        if (empty($fileId) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Dosya ID ve mesaj gerekli']);
            exit;
        }
        
        // Dosya sahibi veya admin kontrolü
        if (!$isAdmin && !$chatManager->isFileOwner($fileId, $userId)) {
            error_log("Chat permission denied - FileID: $fileId, UserID: $userId, IsAdmin: " . ($isAdmin ? 'true' : 'false'));
            echo json_encode(['success' => false, 'message' => 'Bu dosyaya mesaj gönderme yetkiniz yok']);
            exit;
        }
        
        // Mesajı gönder
        $senderType = $isAdmin ? 'admin' : 'user';
        error_log("Sending message - FileID: $fileId, UserID: $userId, SenderType: $senderType, Message: $message");
        
        $messageId = $chatManager->sendMessage($fileId, $fileType, $userId, $senderType, $message);
        
        if ($messageId) {
            error_log("Message sent successfully - MessageID: $messageId");
            // Gönderilen mesajın detaylarını getir
            $sql = "SELECT fc.*, u.first_name, u.last_name, u.username 
                    FROM file_chats fc 
                    JOIN users u ON fc.sender_id = u.id 
                    WHERE fc.id = :message_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':message_id' => $messageId]);
            $messageData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Mesaj gönderildi',
                'data' => $messageData
            ]);
        } else {
            error_log("Failed to send message - FileID: $fileId, UserID: $userId");
            echo json_encode(['success' => false, 'message' => 'Mesaj gönderilemedi']);
        }
        break;
        
    case 'get_messages':
        // Mesajları getir
        $fileId = $_GET['file_id'] ?? '';
        $fileType = $_GET['file_type'] ?? 'upload';
        $lastMessageId = $_GET['last_message_id'] ?? null;
        
        if (empty($fileId)) {
            echo json_encode(['success' => false, 'message' => 'Dosya ID gerekli']);
            exit;
        }
        
        // Dosya sahibi veya admin kontrolü
        if (!$isAdmin && !$chatManager->isFileOwner($fileId, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Bu dosyanın mesajlarını görme yetkiniz yok']);
            exit;
        }
        
        // Mesajları getir
        if ($lastMessageId) {
            $messages = $chatManager->getNewMessages($fileId, $fileType, $lastMessageId);
        } else {
            $messages = $chatManager->getFileMessages($fileId, $fileType);
        }
        
        // Mesajları okundu olarak işaretle
        if (!empty($messages)) {
            $chatManager->markAsRead($fileId, $userId);
        }
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'current_user_id' => $userId,
            'is_admin' => $isAdmin
        ]);
        break;
        
    case 'mark_as_read':
        // Mesajları okundu olarak işaretle
        $fileId = $_POST['file_id'] ?? '';
        
        if (empty($fileId)) {
            echo json_encode(['success' => false, 'message' => 'Dosya ID gerekli']);
            exit;
        }
        
        $result = $chatManager->markAsRead($fileId, $userId);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Mesajlar okundu olarak işaretlendi' : 'İşlem başarısız'
        ]);
        break;
        
    case 'get_unread_count':
        // Okunmamış mesaj sayısını getir
        $fileId = $_GET['file_id'] ?? '';
        
        if (empty($fileId)) {
            // Tüm dosyalardaki okunmamış mesajları getir
            $counts = $chatManager->getAllUnreadCounts($userId);
            echo json_encode([
                'success' => true,
                'counts' => $counts,
                'total' => array_sum($counts)
            ]);
        } else {
            // Belirli dosya için okunmamış mesaj sayısı
            $count = $chatManager->getUnreadCount($fileId, $userId);
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
        }
        break;
        
    case 'clear_history':
        // Chat geçmişini temizle (sadece admin)
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
            exit;
        }
        
        $fileId = $_POST['file_id'] ?? '';
        
        if (empty($fileId)) {
            echo json_encode(['success' => false, 'message' => 'Dosya ID gerekli']);
            exit;
        }
        
        $result = $chatManager->clearChatHistory($fileId);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Chat geçmişi temizlendi' : 'İşlem başarısız'
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}
?>
