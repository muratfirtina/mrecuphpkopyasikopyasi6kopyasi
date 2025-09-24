<?php
/**
 * Mr ECU - Chat Manager
 * Dosya bazlı chat/mesajlaşma yönetimi
 */

class ChatManager {
    private $pdo;
    private $notificationManager;
    private $emailManager;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // NotificationManager'ı dahil et
        if (!class_exists('NotificationManager')) {
            require_once __DIR__ . '/NotificationManager.php';
        }
        $this->notificationManager = new NotificationManager($pdo);
        
        // EmailManager'ı dahil et
        if (!class_exists('EmailManager')) {
            require_once __DIR__ . '/EmailManager.php';
        }
        $this->emailManager = new EmailManager($pdo);
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
                
                // Karşı tarafa bildirim gönder
                $this->sendChatNotification($fileId, $senderId, $senderType, $message);
                
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
    
    /**
     * Chat mesajı için bildirim gönder (Sistem içi + Email)
     */
    private function sendChatNotification($fileId, $senderId, $senderType, $message) {
        try {
            // Dosya bilgilerini al
            $sql = "SELECT * FROM file_uploads WHERE id = :file_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':file_id' => $fileId]);
            $fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fileInfo) {
                error_log('Chat notification: File not found - ' . $fileId);
                return false;
            }
            
            // Gönderen kullanıcı bilgilerini al
            $sql = "SELECT * FROM users WHERE id = :sender_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':sender_id' => $senderId]);
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sender) {
                error_log('Chat notification: Sender not found - ' . $senderId);
                return false;
            }
            
            $fileName = $fileInfo['original_name'] ?? $fileInfo['filename'] ?? 'Bilinmeyen dosya';
            $senderName = $sender['first_name'] . ' ' . $sender['last_name'];
            
            // Site URL'i al
            $siteUrl = getenv('SITE_URL') ?: 'http://localhost';
            
            // Alıcıları belirle ve hem sistem içi hem email bildirimi gönder
            if ($senderType === 'admin') {
                // Admin mesaj göndermiş, dosya sahibine bildirim gönder
                $recipientId = $fileInfo['user_id'];
                $title = "Yeni Mesaj - Admin";
                $notificationMessage = "Adminlerden {$senderName} size '{$fileName}' dosyası için mesaj gönderdi.";
                $actionUrl = "../user/file-detail.php?id={$fileId}";
                
                // Sistem içi bildirim
                $this->notificationManager->createNotification(
                    $recipientId,
                    'chat_message',
                    $title,
                    $notificationMessage,
                    $fileId,
                    'file_upload',
                    $actionUrl
                );
                
                // Email bildirimini gönder (kullanıcının email tercihleri kontrol edilir)
                $this->sendChatEmailNotification($recipientId, $fileName, $senderName, $message, 'user', $fileId);
                
                error_log("Chat notification sent: Admin {$senderName} -> User {$recipientId} for file {$fileName}");
                
            } else {
                // Kullanıcı mesaj göndermiş, tüm adminlere bildirim gönder
                $sql = "SELECT id FROM users WHERE role = 'admin' AND status = 'active'";
                $stmt = $this->pdo->query($sql);
                $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $title = "Yeni Mesaj - Kullanıcı";
                $notificationMessage = "{$senderName} size '{$fileName}' dosyası için mesaj gönderdi.";
                $actionUrl = "file-detail.php?id={$fileId}";
                
                foreach ($admins as $adminId) {
                    // Sistem içi bildirim
                    $this->notificationManager->createNotification(
                        $adminId,
                        'chat_message',
                        $title,
                        $notificationMessage,
                        $fileId,
                        'file_upload',
                        $actionUrl
                    );
                    
                    // Email bildirimini gönder (adminin email tercihleri kontrol edilir)
                    $this->sendChatEmailNotification($adminId, $fileName, $senderName, $message, 'admin', $fileId);
                }
                
                error_log("Chat notification sent: User {$senderName} -> " . count($admins) . " admins for file {$fileName}");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Chat notification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Chat mesajı için email bildirimi gönder
     */
    private function sendChatEmailNotification($recipientId, $fileName, $senderName, $message, $recipientType, $fileId) {
        try {
            // Alıcının bilgilerini al
            $sql = "SELECT u.*, COALESCE(uep.chat_message_notifications, 1) as chat_notifications 
                    FROM users u 
                    LEFT JOIN user_email_preferences uep ON u.id = uep.user_id 
                    WHERE u.id = :recipient_id AND u.email_verified = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':recipient_id' => $recipientId]);
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recipient || !$recipient['chat_notifications']) {
                error_log("Chat email not sent - recipient not found or notifications disabled: {$recipientId}");
                return false;
            }
            
            $recipientEmail = $recipient['email'];
            $recipientName = $recipient['first_name'] . ' ' . $recipient['last_name'];
            
            // Site URL'i al
            $siteUrl = getenv('SITE_URL') ?: 'http://localhost';
            
            // URL'leri oluştur
            if ($recipientType === 'admin') {
                $chatUrl = $siteUrl . '/admin/file-detail.php?id=' . $fileId;
                $subject = 'Yeni Kullanıcı Mesajı - ' . $fileName;
                $templateKey = 'chat_message_admin';
            } else {
                $chatUrl = $siteUrl . '/user/file-detail.php?id=' . $fileId;
                $subject = 'Yeni Admin Mesajı - ' . $fileName;
                $templateKey = 'chat_message_user';
            }
            
            // Mesajı güvenli hale getir ve kısalt
            $safeMessage = htmlspecialchars($message);
            if (strlen($safeMessage) > 200) {
                $safeMessage = substr($safeMessage, 0, 200) . '...';
            }
            
            // Email template'ini al (mevcut yapıya uygun)
            $sql = "SELECT subject, body FROM email_templates WHERE template_key = ? AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                error_log("Chat email template not found: {$templateKey}");
                // Template yoksa basit email gönder
                $emailBody = $this->buildChatEmailTemplate(
                    $recipientName,
                    $senderName,
                    $fileName,
                    $safeMessage,
                    $chatUrl,
                    $recipientType
                );
            } else {
                // Template'i kullan ve değişkenleri değiştir
                $emailSubject = $template['subject'];
                $emailBody = $template['body'];
                
                // Değişkenleri değiştir
                $variables = [
                    '{{file_name}}' => $fileName,
                    '{{sender_name}}' => $senderName,
                    '{{user_name}}' => $recipientName,
                    '{{message}}' => $safeMessage,
                    '{{chat_url}}' => $chatUrl
                ];
                
                foreach ($variables as $placeholder => $value) {
                    $emailSubject = str_replace($placeholder, $value, $emailSubject);
                    $emailBody = str_replace($placeholder, $value, $emailBody);
                }
                
                $subject = $emailSubject;
            }
            
            // Email gönder
            $emailResult = $this->emailManager->sendEmail($recipientEmail, $subject, $emailBody, true);
            
            if ($emailResult) {
                error_log("Chat email sent successfully: {$senderName} -> {$recipientEmail} for file {$fileName}");
                return true;
            } else {
                error_log("Chat email failed: {$senderName} -> {$recipientEmail} for file {$fileName}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log('Chat email notification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Chat email template oluştur
     */
    private function buildChatEmailTemplate($recipientName, $senderName, $fileName, $message, $chatUrl, $recipientType) {
        $color = ($recipientType === 'admin') ? '#3498db' : '#27ae60';
        $icon = ($recipientType === 'admin') ? '💬' : '💬';
        $title = ($recipientType === 'admin') ? 'Yeni Kullanıcı Mesajı' : 'Yeni Admin Mesajı';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: {$color}; border-bottom: 2px solid {$color}; padding-bottom: 10px;'>
                {$icon} {$title}
            </h2>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='color: #2c3e50; margin-top: 0;'>Merhaba {$recipientName},</h3>
                <p><strong>{$senderName}</strong> size <strong>{$fileName}</strong> dosyası için mesaj gönderdi:</p>
                
                <div style='background: white; padding: 15px; border-radius: 5px; border-left: 4px solid {$color}; margin: 15px 0;'>
                    <p style='margin: 0; color: #2c3e50; font-style: italic;'>\"{$message}\"</p>
                </div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$chatUrl}' 
                   style='background: {$color}; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Mesajı Yanıtla
                </a>
            </div>
            
            <div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                <p style='margin: 0; color: #856404; font-size: 14px;'>
                    <strong>💡 İpucu:</strong> Bu email bildirimlerini kapatmak için hesap ayarlarınızdan email tercihlerinizi değiştirebilirsiniz.
                </p>
            </div>
            
            <p style='color: #7f8c8d; font-size: 12px; margin-top: 30px;'>
                Bu email otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.<br>
                <strong>Mr ECU</strong> - Profesyonel ECU Tuning Hizmetleri
            </p>
        </div>
        ";
    }
}
?>
