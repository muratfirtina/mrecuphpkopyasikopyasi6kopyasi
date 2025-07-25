<?php
/**
 * Mr ECU - Notification Manager Class
 * Bildirim Yönetimi Sınıfı
 */

class NotificationManager {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    /**
     * UUID oluşturucu
     */
    private function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * Yeni bildirim oluştur
     */
    public function createNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null, $actionUrl = null) {
        try {
            $notificationId = $this->generateUUID();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (id, user_id, type, title, message, related_id, related_type, action_url, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $notificationId,
                $userId,
                $type,
                $title,
                $message,
                $relatedId,
                $relatedType,
                $actionUrl
            ]);
        } catch(Exception $e) {
            error_log('NotificationManager createNotification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Okunmamış bildirim sayısını getir
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch(Exception $e) {
            error_log('NotificationManager getUnreadCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead($notificationId, $userId = null) {
        try {
            $query = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ?";
            $params = [$notificationId];
            
            if ($userId) {
                $query .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch(Exception $e) {
            error_log('NotificationManager markAsRead error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = ? AND is_read = FALSE");
            return $stmt->execute([$userId]);
        } catch(Exception $e) {
            error_log('NotificationManager markAllAsRead error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcı bildirimlerini getir
     */
    public function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
        try {
            $whereClause = "WHERE user_id = ?";
            $params = [$userId];
            
            if ($unreadOnly) {
                $whereClause .= " AND is_read = FALSE";
            }
            
            // LIMIT için integer kontrol
            $limit = intval($limit);
            
            $sql = "
                SELECT * FROM notifications 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT {$limit}
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            error_log('NotificationManager getUserNotifications error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Dosya durum güncelleme bildirimi gönder
     */
    public function notifyFileStatusUpdate($uploadId, $userId, $fileName, $status, $adminNotes = '') {
        $statusTexts = [
            'pending' => 'beklemede',
            'processing' => 'işleniyor',
            'completed' => 'tamamlandı',
            'rejected' => 'reddedildi'
        ];
        
        $statusText = $statusTexts[$status] ?? $status;
        $title = "Dosya durumu güncellendi";
        $message = "{$fileName} dosyanızın durumu '{$statusText}' olarak güncellendi.";
        
        if ($adminNotes) {
            $message .= " Admin notu: {$adminNotes}";
        }
        
        $actionUrl = "../user/files.php?id={$uploadId}";
        
        return $this->createNotification(
            $userId,
            'file_status_update',
            $title,
            $message,
            $uploadId,
            'file_upload',
            $actionUrl
        );
    }
    
    /**
     * Revize yanıtı bildirimi gönder
     */
    public function notifyRevisionResponse($revisionId, $userId, $fileName, $status, $adminResponse = '') {
        $statusTexts = [
            'approved' => 'onaylandı',
            'rejected' => 'reddedildi',
            'in_progress' => 'işleme alındı'
        ];
        
        $statusText = $statusTexts[$status] ?? $status;
        $title = "Revize talebi yanıtlandı";
        $message = "{$fileName} dosyası için revize talebiniz '{$statusText}'.";
        
        if ($adminResponse) {
            $message .= " Admin yanıtı: {$adminResponse}";
        }
        
        $actionUrl = "../user/revisions.php?id={$revisionId}";
        
        return $this->createNotification(
            $userId,
            'revision_response',
            $title,
            $message,
            $revisionId,
            'revision',
            $actionUrl
        );
    }
    
    /**
     * Dosya yükleme durumu güncelle ve bildirim gönder
     */
    public function updateUploadStatus($uploadId, $status, $adminNotes = '') {
        try {
            // Dosya bilgilerini al
            $stmt = $this->pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
            $stmt->execute([$uploadId]);
            $upload = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$upload) return false;
            
            // Dosya durumunu güncelle
            $stmt = $this->pdo->prepare("
                UPDATE file_uploads 
                SET status = ?, admin_notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $adminNotes, $uploadId]);
            
            if ($result) {
                // Kullanıcıya bildirim gönder
                $fileName = $upload['original_name'] ?? $upload['filename'] ?? 'Bilinmeyen dosya';
                $this->notifyFileStatusUpdate(
                    $uploadId, 
                    $upload['user_id'], 
                    $fileName, 
                    $status, 
                    $adminNotes
                );
                
                return true;
            }
            
            return false;
        } catch(Exception $e) {
            error_log('NotificationManager updateUploadStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revize durumu güncelle ve bildirim gönder
     */
    public function updateRevisionStatus($revisionId, $status, $adminResponse = '') {
        try {
            error_log("NotificationManager::updateRevisionStatus called with revisionId: $revisionId, status: $status");
            
            // Revize bilgilerini al
            $stmt = $this->pdo->prepare("SELECT * FROM revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revision) {
                error_log("NotificationManager::updateRevisionStatus - Revision not found: $revisionId");
                return false;
            }
            
            error_log("NotificationManager::updateRevisionStatus - Revision found: " . print_r($revision, true));
            
            // Tabloda hangi sütun var kontrol et
            $stmt = $this->pdo->query("SHOW COLUMNS FROM revisions LIKE 'admin_response'");
            $hasAdminResponse = $stmt->rowCount() > 0;
            
            $stmt = $this->pdo->query("SHOW COLUMNS FROM revisions LIKE 'admin_notes'");
            $hasAdminNotes = $stmt->rowCount() > 0;
            
            // Hangi sütunu kullanacağını belirle
            if ($hasAdminResponse) {
                $columnName = 'admin_response';
                error_log("NotificationManager::updateRevisionStatus - Using admin_response column");
            } elseif ($hasAdminNotes) {
                $columnName = 'admin_notes';
                error_log("NotificationManager::updateRevisionStatus - Using admin_notes column");
            } else {
                error_log("NotificationManager::updateRevisionStatus - Neither admin_response nor admin_notes column found");
                return false;
            }
            
            // Revize durumunu güncelle
            $sql = "UPDATE revisions SET status = ?, {$columnName} = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$status, $adminResponse, $revisionId]);
            
            error_log("NotificationManager::updateRevisionStatus - Update query result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                // Orijinal dosya bilgisini al
                $stmt = $this->pdo->prepare("SELECT original_name, filename FROM file_uploads WHERE id = ?");
                $stmt->execute([$revision['upload_id']]);
                $upload = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Kullanıcıya bildirim gönder
                $fileName = $upload['original_name'] ?? $upload['filename'] ?? 'Bilinmeyen dosya';
                $notificationResult = $this->notifyRevisionResponse(
                    $revisionId, 
                    $revision['user_id'], 
                    $fileName, 
                    $status, 
                    $adminResponse
                );
                
                error_log("NotificationManager::updateRevisionStatus - Notification result: " . ($notificationResult ? 'true' : 'false'));
                
                return true;
            }
            
            return false;
        } catch(Exception $e) {
            error_log('NotificationManager updateRevisionStatus error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Dosya yükleme bildirimi (Admin'e)
     */
    public function notifyFileUpload($uploadId, $userId, $fileName, $vehicleData = []) {
        try {
            // Kullanıcı bilgilerini al
            $user = $this->getUserInfo($userId);
            if (!$user) return false;
            
            // Admin kullanıcılarını bul
            $admins = $this->getAdminUsers();
            
            foreach ($admins as $admin) {
                // Bildirim oluştur
                $title = "Yeni Dosya Yüklendi";
                $message = "{$user['first_name']} {$user['last_name']} tarafından yeni dosya yüklendi: {$fileName}";
                $actionUrl = "uploads.php?id={$uploadId}";
                
                $this->createNotification(
                    $admin['id'], 
                    'file_upload', 
                    $title, 
                    $message, 
                    $uploadId, 
                    'file_upload', 
                    $actionUrl
                );
            }
            
            return true;
        } catch(Exception $e) {
            error_log('NotificationManager notifyFileUpload error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revize talebi bildirimi (Admin'e)
     */
    public function notifyRevisionRequest($revisionId, $userId, $uploadId, $originalFileName, $requestNotes = '') {
        try {
            $user = $this->getUserInfo($userId);
            if (!$user) return false;
            
            $admins = $this->getAdminUsers();
            
            foreach ($admins as $admin) {
                // Bildirim oluştur
                $title = "Yeni Revize Talebi";
                $message = "{$user['first_name']} {$user['last_name']} tarafından '{$originalFileName}' için revize talebi oluşturuldu.";
                $actionUrl = "revisions.php?id={$revisionId}";
                
                $this->createNotification(
                    $admin['id'], 
                    'revision_request', 
                    $title, 
                    $message, 
                    $revisionId, 
                    'revision', 
                    $actionUrl
                );
            }
            
            return true;
        } catch(Exception $e) {
            error_log('NotificationManager notifyRevisionRequest error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcı bilgilerini getir
     */
    private function getUserInfo($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, first_name, last_name 
                FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            error_log('NotificationManager getUserInfo error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Admin kullanıcıları getir
     */
    private function getAdminUsers() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, username, email, first_name, last_name 
                FROM users WHERE role = 'admin' AND status = 'active'
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            error_log('NotificationManager getAdminUsers error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Eski bildirimleri temizle
     */
    public function cleanOldNotifications($days = 90) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            return $stmt->execute([$days]);
        } catch(Exception $e) {
            error_log('NotificationManager cleanOldNotifications error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sistem uyarısı bildirimi gönder
     */
    public function notifySystemWarning($title, $message, $targetUsers = 'all') {
        try {
            if ($targetUsers === 'all') {
                // Tüm aktif kullanıcılara gönder
                $stmt = $this->pdo->query("SELECT id FROM users WHERE status = 'active'");
                $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($targetUsers === 'admins') {
                // Sadece adminlere gönder
                $stmt = $this->pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
                $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                // Belirli kullanıcılara gönder
                $users = is_array($targetUsers) ? $targetUsers : [$targetUsers];
            }
            
            $successCount = 0;
            foreach ($users as $userId) {
                if ($this->createNotification($userId, 'system_warning', $title, $message)) {
                    $successCount++;
                }
            }
            
            return $successCount;
        } catch(Exception $e) {
            error_log('NotificationManager notifySystemWarning error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
