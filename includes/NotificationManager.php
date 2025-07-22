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
                // Kullanıcıya bildirim gönder (original_name kolonu kullan)
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
            // Revize bilgilerini al
            $stmt = $this->pdo->prepare("SELECT * FROM revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revision) return false;
            
            // Revize durumunu güncelle
            $stmt = $this->pdo->prepare("
                UPDATE revisions 
                SET status = ?, admin_response = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $adminResponse, $revisionId]);
            
            if ($result) {
                // Orijinal dosya bilgisini al
                $stmt = $this->pdo->prepare("SELECT original_name, filename FROM file_uploads WHERE id = ?");
                $stmt->execute([$revision['upload_id']]);
                $upload = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Kullanıcıya bildirim gönder (original_name kolonu kullan)
                $fileName = $upload['original_name'] ?? $upload['filename'] ?? 'Bilinmeyen dosya';
                $this->notifyRevisionResponse(
                    $revisionId, 
                    $revision['user_id'], 
                    $fileName, 
                    $status, 
                    $adminResponse
                );
                
                return true;
            }
            
            return false;
        } catch(Exception $e) {
            error_log('NotificationManager updateRevisionStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bildirim oluştur
     */
    public function createNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null, $actionUrl = null) {
        try {
            $notificationId = generateUUID(); // UUID oluştur
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (id, user_id, type, title, message, related_id, related_type, action_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([$notificationId, $userId, $type, $title, $message, $relatedId, $relatedType, $actionUrl]);
        } catch(PDOException $e) {
            error_log('NotificationManager createNotification error: ' . $e->getMessage());
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
            
            // LIMIT için parametre kullanma sorunu çözümü
            $limit = intval($limit); // Integer'a çevir
            
            $sql = "
                SELECT * FROM notifications 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT {$limit}
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('NotificationManager getUserNotifications error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Okunmamış bildirim sayısını getir
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] ?? 0;
        } catch(PDOException $e) {
            error_log('NotificationManager getUnreadCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead($notificationId, $userId = null) {
        try {
            $sql = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ?";
            $params = [$notificationId];
            
            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            error_log('NotificationManager markAsRead error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = TRUE, read_at = NOW() 
                WHERE user_id = ? AND is_read = FALSE
            ");
            return $stmt->execute([$userId]);
        } catch(PDOException $e) {
            error_log('NotificationManager markAllAsRead error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Dosya yükleme bildirimi (Admin'e)
     */
    public function notifyFileUpload($uploadId, $userId, $fileName, $vehicleData) {
        try {
            // Kullanıcı bilgilerini al
            $user = $this->getUserInfo($userId);
            if (!$user) return false;
            
            // Araç bilgilerini al
            $vehicleInfo = $this->getVehicleInfo($vehicleData['brand_id'], $vehicleData['model_id']);
            
            // Admin kullanıcılarını bul
            $admins = $this->getAdminUsers();
            
            foreach ($admins as $admin) {
                // Bildirim oluştur
                $title = "Yeni Dosya Yüklendi";
                $message = "{$user['first_name']} {$user['last_name']} tarafından yeni dosya yüklendi: {$fileName}";
                $actionUrl = SITE_URL . "admin/uploads.php?id={$uploadId}";
                
                $this->createNotification(
                    $admin['id'], 
                    'file_upload', 
                    $title, 
                    $message, 
                    $uploadId, 
                    'file_upload', 
                    $actionUrl
                );
                
                // Email gönder
                $this->sendEmailNotification(
                    $admin['email'],
                    'file_upload_admin',
                    [
                        'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                        'user_email' => $user['email'],
                        'file_name' => $fileName,
                        'brand_name' => $vehicleInfo['brand_name'] ?? '',
                        'model_name' => $vehicleInfo['model_name'] ?? '',
                        'year' => $vehicleData['year'] ?? '',
                        'plate' => $vehicleData['plate'] ?? 'Belirtilmedi',
                        'ecu_type' => $vehicleData['ecu_type'] ?? 'Belirtilmedi',
                        'admin_url' => $actionUrl
                    ]
                );
            }
            
            return true;
        } catch(Exception $e) {
            error_log('NotificationManager notifyFileUpload error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Dosya durum güncellemesi bildirimi (Kullanıcıya)
     */
    public function notifyFileStatusUpdate($uploadId, $userId, $fileName, $status, $adminNotes = '') {
        try {
            $user = $this->getUserInfo($userId);
            if (!$user) return false;
            
            $statusTexts = [
                'pending' => 'İnceleniyor',
                'processing' => 'İşleniyor',
                'completed' => 'Tamamlandı',
                'rejected' => 'Reddedildi'
            ];
            
            $statusText = $statusTexts[$status] ?? $status;
            
            // Bildirim oluştur
            $title = "Dosya Durumu Güncellendi";
            $message = "'{$fileName}' dosyanızın durumu '{$statusText}' olarak güncellendi.";
            if ($adminNotes) {
                $message .= " Admin notu: {$adminNotes}";
            }
            $actionUrl = SITE_URL . "user/files.php?id={$uploadId}";
            
            $this->createNotification(
                $userId, 
                'file_status_update', 
                $title, 
                $message, 
                $uploadId, 
                'file_upload', 
                $actionUrl
            );
            
            // Email gönder
            $this->sendEmailNotification(
                $user['email'],
                'file_status_update_user',
                [
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'file_name' => $fileName,
                    'status' => $statusText,
                    'admin_notes' => $adminNotes ?: 'Henüz admin notu yok.',
                    'user_url' => $actionUrl
                ]
            );
            
            return true;
        } catch(Exception $e) {
            error_log('NotificationManager notifyFileStatusUpdate error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revize talebi bildirimi (Admin'e)
     */
    public function notifyRevisionRequest($revisionId, $userId, $uploadId, $originalFileName, $requestNotes) {
        try {
            $user = $this->getUserInfo($userId);
            if (!$user) return false;
            
            $admins = $this->getAdminUsers();
            
            foreach ($admins as $admin) {
                // Bildirim oluştur
                $title = "Yeni Revize Talebi";
                $message = "{$user['first_name']} {$user['last_name']} tarafından '{$originalFileName}' için revize talebi oluşturuldu.";
                $actionUrl = SITE_URL . "admin/revisions.php?id={$revisionId}";
                
                $this->createNotification(
                    $admin['id'], 
                    'revision_request', 
                    $title, 
                    $message, 
                    $revisionId, 
                    'revision', 
                    $actionUrl
                );
                
                // Email gönder
                $this->sendEmailNotification(
                    $admin['email'],
                    'revision_request_admin',
                    [
                        'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                        'user_email' => $user['email'],
                        'original_file' => $originalFileName,
                        'request_notes' => $requestNotes ?: 'Talep notu yok.',
                        'admin_url' => $actionUrl
                    ]
                );
            }
            
            return true;
        } catch(Exception $e) {
            error_log('NotificationManager notifyRevisionRequest error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revize yanıtı bildirimi (Kullanıcıya)
     */
    public function notifyRevisionResponse($revisionId, $userId, $originalFileName, $status, $adminResponse = '') {
        try {
            $user = $this->getUserInfo($userId);
            if (!$user) return false;
            
            $statusTexts = [
                'approved' => 'Onaylandı',
                'completed' => 'Tamamlandı',
                'rejected' => 'Reddedildi'
            ];
            
            $statusText = $statusTexts[$status] ?? $status;
            
            // Bildirim oluştur
            $title = "Revize Talebiniz Yanıtlandı";
            $message = "'{$originalFileName}' dosyası için revize talebiniz '{$statusText}' olarak işleme alındı.";
            if ($adminResponse) {
                $message .= " Admin yanıtı: {$adminResponse}";
            }
            $actionUrl = SITE_URL . "user/revisions.php?id={$revisionId}";
            
            $this->createNotification(
                $userId, 
                'revision_response', 
                $title, 
                $message, 
                $revisionId, 
                'revision', 
                $actionUrl
            );
            
            // Email gönder
            $this->sendEmailNotification(
                $user['email'],
                'revision_response_user',
                [
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'original_file' => $originalFileName,
                    'status' => $statusText,
                    'admin_response' => $adminResponse ?: 'Henüz admin yanıtı yok.',
                    'user_url' => $actionUrl
                ]
            );
            
            return true;
        } catch(Exception $e) {
            error_log('NotificationManager notifyRevisionResponse error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Email bildirimi gönder
     */
    private function sendEmailNotification($toEmail, $templateKey, $variables = []) {
        try {
            // Email şablonunu al
            $template = $this->getEmailTemplate($templateKey);
            if (!$template) return false;
            
            // Değişkenleri değiştir
            $subject = $template['subject'];
            $body = $template['body'];
            
            foreach ($variables as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $subject = str_replace($placeholder, $value, $subject);
                $body = str_replace($placeholder, $value, $body);
            }
            
            // Email kuyruğuna ekle
            $stmt = $this->pdo->prepare("
                INSERT INTO email_queue (to_email, subject, body, priority) 
                VALUES (?, ?, ?, 'normal')
            ");
            
            return $stmt->execute([$toEmail, $subject, $body]);
        } catch(Exception $e) {
            error_log('NotificationManager sendEmailNotification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Email şablonunu getir
     */
    private function getEmailTemplate($templateKey) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM email_templates 
                WHERE template_key = ? AND is_active = TRUE
            ");
            $stmt->execute([$templateKey]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('NotificationManager getEmailTemplate error: ' . $e->getMessage());
            return null;
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
        } catch(PDOException $e) {
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
        } catch(PDOException $e) {
            error_log('NotificationManager getAdminUsers error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Araç bilgilerini getir
     */
    private function getVehicleInfo($brandId, $modelId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.name as brand_name, m.name as model_name
                FROM brands b
                LEFT JOIN models m ON m.brand_id = b.id
                WHERE b.id = ? AND m.id = ?
            ");
            $stmt->execute([$brandId, $modelId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('NotificationManager getVehicleInfo error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Eski bildirimleri temizle (30 günden eski)
     */
    public function cleanOldNotifications() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log('NotificationManager cleanOldNotifications error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
