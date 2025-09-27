<?php
/**
 * Mr ECU - Bildirim Entegrasyon Sistemi
 * Mevcut dosya yükleme, revizyon, chat ve diğer işlemlere bildirim entegrasyonu
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NotificationManager.php';

/**
 * 1. DOSYA YÜKLEME BİLDİRİMİ
 * User dosya yüklediğinde admin'e bildirim gönder
 */
function notifyAdminsForFileUpload($uploadId, $userId, $fileName, $vehicleData = []) {
    global $pdo;
    
    try {
        error_log("notifyAdminsForFileUpload called: uploadId=$uploadId, userId=$userId, fileName=$fileName");
        
        $notificationManager = new NotificationManager($pdo);
        
        // Kullanıcı bilgilerini al
        $stmt = $pdo->prepare("SELECT username, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("User not found for notification: $userId");
            return false;
        }
        
        // Admin kullanıcılarını al
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($admins) . " admin users for notification");
        
        $successCount = 0;
        foreach ($admins as $admin) {
            $title = "Yeni Dosya Yüklendi";
            $message = "{$user['first_name']} {$user['last_name']} ({$user['username']}) tarafından yeni dosya yüklendi: {$fileName}";
            $actionUrl = "file-detail.php?id={$uploadId}";
            
            $result = $notificationManager->createNotification(
                $admin['id'],
                'file_upload',
                $title,
                $message,
                $uploadId,
                'file_upload',
                $actionUrl
            );
            
            if ($result) {
                $successCount++;
                error_log("Notification created for admin: {$admin['username']}");
            } else {
                error_log("Failed to create notification for admin: {$admin['username']}");
            }
        }
        
        error_log("File upload notification completed: $successCount/" . count($admins) . " successful");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log('notifyAdminsForFileUpload error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 2. REVİZYON TALEBİ BİLDİRİMİ
 * User revizyon talebi oluşturduğunda admin'e bildirim gönder
 */
function notifyAdminsForRevisionRequest($revisionId, $userId, $uploadId, $requestNotes = '') {
    global $pdo;
    
    try {
        error_log("notifyAdminsForRevisionRequest called: revisionId=$revisionId, userId=$userId");
        
        $notificationManager = new NotificationManager($pdo);
        
        // Kullanıcı ve dosya bilgilerini al
        $stmt = $pdo->prepare("
            SELECT u.username, u.first_name, u.last_name, f.original_name
            FROM users u, file_uploads f 
            WHERE u.id = ? AND f.id = ?
        ");
        $stmt->execute([$userId, $uploadId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            error_log("User or file not found for revision notification: userId=$userId, uploadId=$uploadId");
            return false;
        }
        
        // Admin kullanıcılarını al
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $successCount = 0;
        foreach ($admins as $admin) {
            $title = "Yeni Revizyon Talebi";
            $message = "{$data['first_name']} {$data['last_name']} ({$data['username']}) tarafından '{$data['original_name']}' dosyası için revizyon talebi oluşturuldu.";
            
            if ($requestNotes) {
                $message .= " Talep notu: " . substr($requestNotes, 0, 100) . (strlen($requestNotes) > 100 ? '...' : '');
            }
            
            $actionUrl = "revision-detail.php?id={$revisionId}";
            
            $result = $notificationManager->createNotification(
                $admin['id'],
                'revision_request',
                $title,
                $message,
                $revisionId,
                'revision',
                $actionUrl
            );
            
            if ($result) {
                $successCount++;
                error_log("Revision notification created for admin: {$admin['username']}");
            } else {
                error_log("Failed to create revision notification for admin: {$admin['username']}");
            }
        }
        
        error_log("Revision request notification completed: $successCount/" . count($admins) . " successful");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log('notifyAdminsForRevisionRequest error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 3. CHAT MESAJI BİLDİRİMİ
 * User chat mesajı gönderdiğinde admin'e bildirim gönder
 */
function notifyAdminsForChatMessage($messageId, $senderId, $uploadId, $messageContent) {
    global $pdo;
    
    try {
        error_log("notifyAdminsForChatMessage called: messageId=$messageId, senderId=$senderId");
        
        $notificationManager = new NotificationManager($pdo);
        
        // Kullanıcı ve dosya bilgilerini al
        $stmt = $pdo->prepare("
            SELECT u.username, u.first_name, u.last_name, f.original_name
            FROM users u, file_uploads f 
            WHERE u.id = ? AND f.id = ?
        ");
        $stmt->execute([$senderId, $uploadId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            error_log("User or file not found for chat notification: senderId=$senderId, uploadId=$uploadId");
            return false;
        }
        
        // Admin kullanıcılarını al
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $successCount = 0;
        foreach ($admins as $admin) {
            $title = "Yeni Chat Mesajı";
            $messagePreview = substr($messageContent, 0, 100) . (strlen($messageContent) > 100 ? '...' : '');
            $message = "{$data['first_name']} {$data['last_name']} ({$data['username']}) '{$data['original_name']}' dosyası için mesaj gönderdi: {$messagePreview}";
            $actionUrl = "file-detail.php?id={$uploadId}#chat";
            
            $result = $notificationManager->createNotification(
                $admin['id'],
                'chat_message',
                $title,
                $message,
                $messageId,
                'chat',
                $actionUrl
            );
            
            if ($result) {
                $successCount++;
                error_log("Chat notification created for admin: {$admin['username']}");
            } else {
                error_log("Failed to create chat notification for admin: {$admin['username']}");
            }
        }
        
        error_log("Chat message notification completed: $successCount/" . count($admins) . " successful");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log('notifyAdminsForChatMessage error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 4. DOSYA İPTAL TALEBİ BİLDİRİMİ
 * User dosya iptal talebi oluşturduğunda admin'e bildirim gönder
 */
function notifyAdminsForCancellationRequest($cancellationId, $userId, $uploadId, $cancelReason = '') {
    global $pdo;
    
    try {
        error_log("notifyAdminsForCancellationRequest called: cancellationId=$cancellationId, userId=$userId");
        
        $notificationManager = new NotificationManager($pdo);
        
        // Kullanıcı ve dosya bilgilerini al
        $stmt = $pdo->prepare("
            SELECT u.username, u.first_name, u.last_name, f.original_name
            FROM users u, file_uploads f 
            WHERE u.id = ? AND f.id = ?
        ");
        $stmt->execute([$userId, $uploadId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            error_log("User or file not found for cancellation notification: userId=$userId, uploadId=$uploadId");
            return false;
        }
        
        // Admin kullanıcılarını al
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $successCount = 0;
        foreach ($admins as $admin) {
            $title = "Dosya İptal Talebi";
            $message = "{$data['first_name']} {$data['last_name']} ({$data['username']}) '{$data['original_name']}' dosyası için iptal talebi oluşturdu.";
            
            if ($cancelReason) {
                $message .= " İptal nedeni: " . substr($cancelReason, 0, 100) . (strlen($cancelReason) > 100 ? '...' : '');
            }
            
            $actionUrl = "file-cancellations.php?id={$cancellationId}";
            
            $result = $notificationManager->createNotification(
                $admin['id'],
                'cancellation_request',
                $title,
                $message,
                $cancellationId,
                'cancellation',
                $actionUrl
            );
            
            if ($result) {
                $successCount++;
                error_log("Cancellation notification created for admin: {$admin['username']}");
            } else {
                error_log("Failed to create cancellation notification for admin: {$admin['username']}");
            }
        }
        
        error_log("Cancellation request notification completed: $successCount/" . count($admins) . " successful");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log('notifyAdminsForCancellationRequest error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 5. EK DOSYA BİLDİRİMİ
 * User ek dosya gönderdiğinde admin'e bildirim gönder
 */
function notifyAdminsForAdditionalFile($additionalFileId, $senderId, $relatedFileId, $fileName, $notes = '') {
    global $pdo;
    
    try {
        error_log("notifyAdminsForAdditionalFile called: additionalFileId=$additionalFileId, senderId=$senderId");
        
        $notificationManager = new NotificationManager($pdo);
        
        // Kullanıcı ve ana dosya bilgilerini al
        $stmt = $pdo->prepare("
            SELECT u.username, u.first_name, u.last_name, f.original_name as related_file_name
            FROM users u, file_uploads f 
            WHERE u.id = ? AND f.id = ?
        ");
        $stmt->execute([$senderId, $relatedFileId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            error_log("User or related file not found for additional file notification: senderId=$senderId, relatedFileId=$relatedFileId");
            return false;
        }
        
        // Admin kullanıcılarını al
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $successCount = 0;
        foreach ($admins as $admin) {
            $title = "Yeni Ek Dosya";
            $message = "{$data['first_name']} {$data['last_name']} ({$data['username']}) '{$data['related_file_name']}' dosyası için ek dosya gönderdi: {$fileName}";
            
            if ($notes) {
                $message .= " Not: " . substr($notes, 0, 100) . (strlen($notes) > 100 ? '...' : '');
            }
            
            $actionUrl = "file-detail.php?id={$relatedFileId}";
            
            $result = $notificationManager->createNotification(
                $admin['id'],
                'additional_file',
                $title,
                $message,
                $additionalFileId,
                'additional_file',
                $actionUrl
            );
            
            if ($result) {
                $successCount++;
                error_log("Additional file notification created for admin: {$admin['username']}");
            } else {
                error_log("Failed to create additional file notification for admin: {$admin['username']}");
            }
        }
        
        error_log("Additional file notification completed: $successCount/" . count($admins) . " successful");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log('notifyAdminsForAdditionalFile error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Notifications tablosunu oluştur (eğer yoksa)
 */
function createNotificationsTableIfNotExists() {
    global $pdo;
    
    try {
        // Tablo var mı kontrol et
        $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            error_log("Creating notifications table...");
            
            $sql = "
            CREATE TABLE notifications (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_id CHAR(36) NULL,
                related_type VARCHAR(50) NULL,
                action_url VARCHAR(500) NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_type (type),
                INDEX idx_is_read (is_read),
                INDEX idx_created_at (created_at)
            )";
            
            $pdo->exec($sql);
            error_log("Notifications table created successfully");
            return true;
        } else {
            error_log("Notifications table already exists");
            return true;
        }
    } catch (Exception $e) {
        error_log('createNotificationsTableIfNotExists error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Test bildirim oluştur
 */
function createTestNotification() {
    global $pdo;
    
    try {
        // Admin kullanıcı bul
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            error_log("No admin user found for test notification");
            return false;
        }
        
        $notificationManager = new NotificationManager($pdo);
        
        $result = $notificationManager->createNotification(
            $admin['id'],
            'system_test',
            'Test Bildirimi',
            'Bu bir test bildirimidir. Bildirim sistemi çalışıyor!',
            null,
            null,
            'notifications.php'
        );
        
        if ($result) {
            error_log("Test notification created successfully for admin: {$admin['username']}");
            return true;
        } else {
            error_log("Failed to create test notification");
            return false;
        }
        
    } catch (Exception $e) {
        error_log('createTestNotification error: ' . $e->getMessage());
        return false;
    }
}

// Eğer bu dosya direkt çalıştırılırsa test yap
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "<!DOCTYPE html><html><head><title>Bildirim Entegrasyon Test</title></head><body>";
    echo "<h1>🔔 Bildirim Entegrasyon Test</h1>";
    
    // Notifications tablosu oluştur
    if (createNotificationsTableIfNotExists()) {
        echo "<p>✅ Notifications tablosu kontrol edildi/oluşturuldu</p>";
    } else {
        echo "<p>❌ Notifications tablosu oluşturulamadı</p>";
    }
    
    // Test bildirim oluştur
    if (createTestNotification()) {
        echo "<p>✅ Test bildirimi oluşturuldu</p>";
    } else {
        echo "<p>❌ Test bildirimi oluşturulamadı</p>";
    }
    
    echo "<p><a href='../debug-notifications.php'>Debug sayfasına git</a></p>";
    echo "</body></html>";
}
?>
