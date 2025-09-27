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
     * Bildirim gönder (createNotification'a yönlendirme)
     * @param string $userId - Kullanıcı ID
     * @param string $userType - Kullanıcı tipi (geriye uyumluluk için, kullanılmıyor)
     * @param string $type - Bildirim tipi
     * @param string $message - Bildirim mesajı
     * @param string $relatedId - İlgili öğe ID
     * @param string $relatedType - İlgili öğe tipi
     * @param string $actionUrl - Eylem URL'i
     * @return bool - Başarı durumu
     */
    public function sendNotification($userId, $userType, $type, $message, $relatedId = null, $relatedType = null, $actionUrl = null) {
        // userType parametresi geriye uyumluluk için kabul ediliyor ama kullanılmıyor
        
        // Title'i type'a göre belirle
        $titles = [
            'additional_file' => 'Yeni Ek Dosya',
            'additional_file_admin' => 'Yeni Ek Dosya (Admin)',
            'additional_file_user' => 'Yeni Ek Dosya',
            'file_upload' => 'Yeni Dosya Yüklendi',
            'file_response' => 'Dosyanız Hazır',
            'revision_request' => 'Revizyon Talebi',
            'status_update' => 'Durum Güncellendi'
        ];
        
        $title = $titles[$type] ?? 'Bildirim';
        
        return $this->createNotification($userId, $type, $title, $message, $relatedId, $relatedType, $actionUrl);
    }
    
    /**
     * UUID oluşturucu - Public metod
     */
    public function generateUUID() {
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
            
            error_log("NotificationManager::createNotification - Başlatılıyor: UserId: $userId, Type: $type, Title: $title");
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (id, user_id, type, title, message, related_id, related_type, action_url, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $notificationId,
                $userId,
                $type,
                $title,
                $message,
                $relatedId,
                $relatedType,
                $actionUrl
            ]);
            
            if ($result) {
                error_log("NotificationManager::createNotification - Başarılı: NotificationId: $notificationId");
            } else {
                error_log("NotificationManager::createNotification - Başarısız: Execute failed");
                $errorInfo = $stmt->errorInfo();
                error_log("NotificationManager::createNotification - SQL Error: " . implode(' - ', $errorInfo));
            }
            
            return $result;
            
        } catch(Exception $e) {
            error_log('NotificationManager createNotification error: ' . $e->getMessage());
            error_log('SQL State: ' . $e->getCode());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Okunmamış bildirim sayısını getir
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
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
            $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?";
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
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
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
                $whereClause .= " AND is_read = 0";
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
        
        $actionUrl = "files.php?id={$uploadId}";
        
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
        
        $actionUrl = "revisions.php?id={$revisionId}";
        
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
                $actionUrl = "file-detail.php?id={$uploadId}";
                
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
            error_log("NotificationManager::notifyRevisionRequest başlatıldı - RevisionId: $revisionId, UserId: $userId");
            
            $user = $this->getUserInfo($userId);
            if (!$user) {
                error_log("NotificationManager::notifyRevisionRequest - Kullanıcı bulunamadı: $userId");
                return false;
            }
            error_log("NotificationManager::notifyRevisionRequest - Kullanıcı bulundu: {$user['username']}");
            
            $admins = $this->getAdminUsers();
            if (empty($admins)) {
                error_log("NotificationManager::notifyRevisionRequest - Hiç aktif admin kullanıcısı bulunamadı");
                return false;
            }
            error_log("NotificationManager::notifyRevisionRequest - " . count($admins) . " admin kullanıcısı bulundu");
            
            $successCount = 0;
            foreach ($admins as $admin) {
                // Bildirim oluştur
                $title = "Yeni Revize Talebi";
                $message = "{$user['first_name']} {$user['last_name']} tarafından '{$originalFileName}' için revize talebi oluşturuldu.";
                $actionUrl = "revision-detail.php?id={$revisionId}";
                
                $notificationResult = $this->createNotification(
                    $admin['id'], 
                    'revision_request', 
                    $title, 
                    $message, 
                    $revisionId, 
                    'revision', 
                    $actionUrl
                );
                
                if ($notificationResult) {
                    $successCount++;
                    error_log("NotificationManager::notifyRevisionRequest - Admin {$admin['username']} için bildirim oluşturuldu");
                    
                    // E-posta bildirimi gönder (opsiyonel)
                    $this->sendRevisionRequestEmail($admin, $user, $originalFileName, $revisionId, $requestNotes);
                } else {
                    error_log("NotificationManager::notifyRevisionRequest - Admin {$admin['username']} için bildirim oluşturulamadı");
                }
            }
            
            error_log("NotificationManager::notifyRevisionRequest tamamlandı - $successCount/" . count($admins) . " bildirim başarılı");
            return $successCount > 0;
            
        } catch(Exception $e) {
            error_log('NotificationManager notifyRevisionRequest error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
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
    
    /**
     * Ek dosya bildirimi gönder (Kullanıcıdan Admin'e)
     * @param string $additionalFileId - Ek dosya ID
     * @param string $senderId - Gönderen kullanıcı ID
     * @param string $fileName - Dosya adı
     * @param string $notes - Notlar
     * @param string $relatedFileName - İlgili dosya adı
     * @param string $relatedFileId - İlgili dosya ID (ana dosya ID'si)
     * @return bool - Başarı durumu
     */
    public function notifyAdditionalFileToAdmin($additionalFileId, $senderId, $fileName, $notes = '', $relatedFileName = '', $relatedFileId = '') {
        try {
            error_log("NotificationManager::notifyAdditionalFileToAdmin - Başlatıldı");
            error_log("NotificationManager::notifyAdditionalFileToAdmin - Params: FileId=$additionalFileId, SenderId=$senderId, FileName=$fileName");
            
            $user = $this->getUserInfo($senderId);
            if (!$user) {
                error_log("NotificationManager::notifyAdditionalFileToAdmin - Kullanıcı bulunamadı: $senderId");
                return false;
            }
            error_log("NotificationManager::notifyAdditionalFileToAdmin - Kullanıcı bulundu: {$user['username']}");
            
            $admins = $this->getAdminUsers();
            if (empty($admins)) {
                error_log("NotificationManager::notifyAdditionalFileToAdmin - Hiç aktif admin kullanıcısı bulunamadı");
                return false;
            }
            error_log("NotificationManager::notifyAdditionalFileToAdmin - " . count($admins) . " admin kullanıcısı bulundu");
            
            // Eğer relatedFileId parametresi verilmemişse, additional_files tablosundan al
            if (empty($relatedFileId)) {
                try {
                    $stmt = $this->pdo->prepare("SELECT related_file_id FROM additional_files WHERE id = ?");
                    $stmt->execute([$additionalFileId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $relatedFileId = $result['related_file_id'];
                    }
                } catch(Exception $e) {
                    error_log("NotificationManager::notifyAdditionalFileToAdmin - Related file ID alamadı: " . $e->getMessage());
                }
            }
            
            // Admin için action URL'yi ana dosyanın admin detay sayfası olarak ayarla
            if (!empty($relatedFileId)) {
                $actionUrl = "file-detail.php?id={$relatedFileId}";
            } else {
                $actionUrl = "additional-files.php"; // Fallback
            }
            
            $successCount = 0;
            foreach ($admins as $admin) {
                $title = "Yeni Ek Dosya";
                $message = "{$user['first_name']} {$user['last_name']} tarafından yeni ek dosya gönderildi: {$fileName}";
                
                if ($relatedFileName) {
                    $message .= " (İlgili dosya: {$relatedFileName})";
                }
                
                if ($notes) {
                    $message .= " - Not: {$notes}";
                }
                
                error_log("NotificationManager::notifyAdditionalFileToAdmin - Creating notification for admin: {$admin['username']}");
                error_log("NotificationManager::notifyAdditionalFileToAdmin - Action URL: $actionUrl");
                
                $notificationResult = $this->createNotification(
                    $admin['id'],
                    'additional_file_admin',
                    $title,
                    $message,
                    $additionalFileId,
                    'additional_file',
                    $actionUrl
                );
                
                if ($notificationResult) {
                    $successCount++;
                    error_log("NotificationManager::notifyAdditionalFileToAdmin - Admin {$admin['username']} için bildirim oluşturuldu");
                } else {
                    error_log("NotificationManager::notifyAdditionalFileToAdmin - Admin {$admin['username']} için bildirim oluşturulamadı");
                }
            }
            
            error_log("NotificationManager::notifyAdditionalFileToAdmin tamamlandı - $successCount/" . count($admins) . " bildirim başarılı");
            return $successCount > 0;
            
        } catch(Exception $e) {
            error_log('NotificationManager notifyAdditionalFileToAdmin error: ' . $e->getMessage());
            error_log('NotificationManager notifyAdditionalFileToAdmin stack: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Ek dosya bildirimi gönder (Admin'den Kullanıcıya)
     * @param string $additionalFileId - Ek dosya ID
     * @param string $receiverId - Alıcı kullanıcı ID
     * @param string $fileName - Dosya adı
     * @param string $notes - Notlar
     * @param float $credits - Ücret
     * @param string $relatedFileName - İlgili dosya adı
     * @param string $relatedFileId - İlgili dosya ID (ana dosya ID'si)
     * @return bool - Başarı durumu
     */
    public function notifyAdditionalFileToUser($additionalFileId, $receiverId, $fileName, $notes = '', $credits = 0, $relatedFileName = '', $relatedFileId = '') {
        try {
            error_log("NotificationManager::notifyAdditionalFileToUser - Başlatıldı");
            error_log("NotificationManager::notifyAdditionalFileToUser - Params: FileId=$additionalFileId, ReceiverId=$receiverId, FileName=$fileName, Credits=$credits");
            
            $title = "Yeni Ek Dosya";
            $message = "Size yeni ek dosya gönderildi: {$fileName}";
            
            if ($relatedFileName) {
                $message .= " (İlgili dosya: {$relatedFileName})";
            }
            
            if ($notes) {
                $message .= " - Not: {$notes}";
            }
            
            if ($credits > 0) {
                $message .= " (Ücret: {$credits} kredi düşüldü)";
            }
            
            // Eğer relatedFileId parametresi verilmemişse, additional_files tablosundan al
            if (empty($relatedFileId)) {
                try {
                    $stmt = $this->pdo->prepare("SELECT related_file_id FROM additional_files WHERE id = ?");
                    $stmt->execute([$additionalFileId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $relatedFileId = $result['related_file_id'];
                    }
                } catch(Exception $e) {
                    error_log("NotificationManager::notifyAdditionalFileToUser - Related file ID alamadı: " . $e->getMessage());
                }
            }
            
            // Action URL'yi ana dosyanın detay sayfası olarak ayarla
            if (!empty($relatedFileId)) {
                $actionUrl = "file-detail.php?id={$relatedFileId}";
            } else {
                $actionUrl = "additional-files.php"; // Fallback
            }
            
            error_log("NotificationManager::notifyAdditionalFileToUser - Creating notification with message: $message");
            error_log("NotificationManager::notifyAdditionalFileToUser - Action URL: $actionUrl");
            
            $notificationResult = $this->createNotification(
                $receiverId,
                'additional_file_user',
                $title,
                $message,
                $additionalFileId,
                'additional_file',
                $actionUrl
            );
            
            if ($notificationResult) {
                error_log("NotificationManager::notifyAdditionalFileToUser - Kullanıcı {$receiverId} için bildirim oluşturuldu");
                return true;
            } else {
                error_log("NotificationManager::notifyAdditionalFileToUser - Kullanıcı {$receiverId} için bildirim oluşturulamadı");
                return false;
            }
            
        } catch(Exception $e) {
            error_log('NotificationManager notifyAdditionalFileToUser error: ' . $e->getMessage());
            error_log('NotificationManager notifyAdditionalFileToUser stack: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Revizyon talebi e-posta bildirimi gönder
     */
    private function sendRevisionRequestEmail($admin, $user, $fileName, $revisionId, $requestNotes) {
        try {
            // E-posta gönderiminin aktif olup olmadığını kontrol et
            if (defined('EMAIL_TEST_MODE') && EMAIL_TEST_MODE) {
                // Test modunda - sadece log dosyasına yaz
                $logMessage = "E-posta Test Modu:\n";
                $logMessage .= "To: {$admin['email']}\n";
                $logMessage .= "Subject: Yeni Revizyon Talebi - Mr ECU\n";
                $logMessage .= "Admin: {$admin['first_name']} {$admin['last_name']}\n";
                $logMessage .= "Kullanıcı: {$user['first_name']} {$user['last_name']}\n";
                $logMessage .= "Dosya: $fileName\n";
                $logMessage .= "Revizyon ID: $revisionId\n";
                $logMessage .= "Not: $requestNotes\n";
                $logMessage .= "Tarih: " . date('Y-m-d H:i:s') . "\n";
                $logMessage .= str_repeat('-', 50) . "\n";
                
                error_log($logMessage, 3, __DIR__ . '/../logs/email_test.log');
                return true;
            }
            
            // Gerçek e-posta gönderimi (gelecekte PHPMailer ile geliştirilebilir)
            $subject = "Yeni Revizyon Talebi - Mr ECU";
            $message = "<html><body>";
            $message .= "<h2>Yeni Revizyon Talebi</h2>";
            $message .= "<p>Sayın {$admin['first_name']} {$admin['last_name']},</p>";
            $message .= "<p>{$user['first_name']} {$user['last_name']} tarafından yeni bir revizyon talebi oluşturulmuştur.</p>";
            $message .= "<hr>";
            $message .= "<p><strong>Dosya:</strong> $fileName</p>";
            $message .= "<p><strong>Kullanıcı:</strong> {$user['first_name']} {$user['last_name']} ({$user['email']})</p>";
            $message .= "<p><strong>Talep Notu:</strong></p>";
            $message .= "<p style='background: #f8f9fa; padding: 10px; border-left: 3px solid #007bff;'>" . nl2br(htmlspecialchars($requestNotes)) . "</p>";
            $message .= "<hr>";
            $message .= "<p><a href='" . SITE_URL . "admin/revisions.php?id=$revisionId' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Revizyon Talebi Görüntüle</a></p>";
            $message .= "<p><small>Bu e-posta otomatik olarak oluşturulmuştur. Mr ECU Sistemi</small></p>";
            $message .= "</body></html>";
            
            // Basit mail fonksiyonu (daha sonra PHPMailer ile değiştirilebilir)
            $headers = "From: " . SITE_EMAIL . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: Mr ECU System\r\n";
            
            return mail($admin['email'], $subject, $message, $headers);
            
        } catch (Exception $e) {
            error_log('NotificationManager sendRevisionRequestEmail error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
