<?php
/**
 * Mr ECU - Chat Manager
 * Dosya bazlı chat/mesajlaşma yönetimi
 */

class ChatManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Yeni mesaj gönder
     */
    public function sendMessage($fileId, $fileType, $senderId, $senderType, $message) {
        try {
            // UUID üret
            $messageId = $this->generateUUID();
            
            $sql = "INSERT INTO file_chats (id, file_id, file_type, sender_id, sender_type, message) 
                    VALUES (:id, :file_id, :file_type, :sender_id, :sender_type, :message)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id' => $messageId,
                ':file_id' => $fileId,
                ':file_type' => $fileType,
                ':sender_id' => $senderId,
                ':sender_type' => $senderType,
                ':message' => $message
            ]);
            
            if ($result) {
                // Karşı taraf için okunmamış sayacını artır
                $this->updateUnreadCount($fileId, $senderId, $senderType);
                return $messageId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Chat send message error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Dosyaya ait tüm mesajları getir
     */
    public function getFileMessages($fileId, $fileType = 'upload', $limit = 100) {
        try {
            $sql = "SELECT 
                        fc.*,
                        u.first_name,
                        u.last_name,
                        u.username,
                        u.role
                    FROM file_chats fc
                    JOIN users u ON fc.sender_id = u.id
                    WHERE fc.file_id = :file_id 
                    AND fc.file_type = :file_type
                    ORDER BY fc.created_at ASC
                    LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':file_id', $fileId, PDO::PARAM_STR);
            $stmt->bindParam(':file_type', $fileType, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Chat get messages error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Son mesajdan sonra gelen yeni mesajları getir (polling için)
     */
    public function getNewMessages($fileId, $fileType, $lastMessageId = null) {
        try {
            if ($lastMessageId) {
                $sql = "SELECT 
                            fc.*,
                            u.first_name,
                            u.last_name,
                            u.username,
                            u.role
                        FROM file_chats fc
                        JOIN users u ON fc.sender_id = u.id
                        WHERE fc.file_id = :file_id 
                        AND fc.file_type = :file_type
                        AND fc.created_at > (
                            SELECT created_at FROM file_chats WHERE id = :last_id
                        )
                        ORDER BY fc.created_at ASC";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':file_id', $fileId, PDO::PARAM_STR);
                $stmt->bindParam(':file_type', $fileType, PDO::PARAM_STR);
                $stmt->bindParam(':last_id', $lastMessageId, PDO::PARAM_STR);
            } else {
                // İlk yükleme
                return $this->getFileMessages($fileId, $fileType);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Chat get new messages error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mesajları okundu olarak işaretle
     */
    public function markAsRead($fileId, $userId) {
        try {
            // Kullanıcının bu dosyada ki tüm mesajları okundu yap
            $sql = "UPDATE file_chats 
                    SET is_read = 1 
                    WHERE file_id = :file_id 
                    AND sender_id != :user_id 
                    AND is_read = 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':file_id' => $fileId,
                ':user_id' => $userId
            ]);
            
            // Okunmamış sayacını sıfırla
            $this->resetUnreadCount($fileId, $userId);
            
            return true;
        } catch (Exception $e) {
            error_log('Chat mark as read error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Okunmamış mesaj sayısını getir
     */
    public function getUnreadCount($fileId, $userId) {
        try {
            $sql = "SELECT COUNT(*) as unread_count 
                    FROM file_chats 
                    WHERE file_id = :file_id 
                    AND sender_id != :user_id 
                    AND is_read = 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':file_id' => $fileId,
                ':user_id' => $userId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['unread_count'] : 0;
        } catch (Exception $e) {
            error_log('Chat get unread count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Kullanıcının tüm dosyalardaki okunmamış mesaj sayılarını getir
     */
    public function getAllUnreadCounts($userId) {
        try {
            $sql = "SELECT 
                        file_id, 
                        COUNT(*) as unread_count 
                    FROM file_chats 
                    WHERE sender_id != :user_id 
                    AND is_read = 0 
                    GROUP BY file_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[$row['file_id']] = $row['unread_count'];
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Chat get all unread counts error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Okunmamış sayacını güncelle
     */
    private function updateUnreadCount($fileId, $senderId, $senderType) {
        try {
            // Dosya sahibini bul
            $sql = "SELECT user_id FROM file_uploads WHERE id = :file_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':file_id' => $fileId]);
            $fileOwner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fileOwner) {
                return false;
            }
            
            // Alıcıyı belirle (gönderen admin ise dosya sahibi, değilse admin)
            $recipientId = ($senderType === 'admin') ? $fileOwner['user_id'] : null;
            
            if ($recipientId) {
                // Okunmamış sayacını artır
                $countId = $this->generateUUID();
                $sql = "INSERT INTO chat_unread_counts (id, file_id, user_id, unread_count) 
                        VALUES (:id, :file_id, :user_id, 1)
                        ON DUPLICATE KEY UPDATE 
                        unread_count = unread_count + 1,
                        updated_at = CURRENT_TIMESTAMP";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $countId,
                    ':file_id' => $fileId,
                    ':user_id' => $recipientId
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Chat update unread count error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Okunmamış sayacını sıfırla
     */
    private function resetUnreadCount($fileId, $userId) {
        try {
            $sql = "UPDATE chat_unread_counts 
                    SET unread_count = 0, last_read_at = CURRENT_TIMESTAMP 
                    WHERE file_id = :file_id AND user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':file_id' => $fileId,
                ':user_id' => $userId
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Chat reset unread count error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * UUID üret
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Dosya sahibini kontrol et
     */
    public function isFileOwner($fileId, $userId) {
        try {
            $sql = "SELECT user_id FROM file_uploads WHERE id = :file_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':file_id' => $fileId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['user_id'] === $userId;
        } catch (Exception $e) {
            error_log('Chat check file owner error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Chat geçmişini temizle (admin işlevi)
     */
    public function clearChatHistory($fileId) {
        try {
            $sql = "DELETE FROM file_chats WHERE file_id = :file_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':file_id' => $fileId]);
        } catch (Exception $e) {
            error_log('Chat clear history error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
