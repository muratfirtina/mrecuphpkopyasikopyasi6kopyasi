<?php
/**
 * Mr ECU - Revizyon Talebi Entegrasyon Örneği
 * Bu dosya mevcut revision request işlemlerine email bildirimi ekleme örneğidir
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

// Kullanıcı girişi kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

$success = '';
$error = '';
$userId = $_SESSION['user_id'];
$uploadId = $_GET['upload_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uploadId = sanitize($_POST['upload_id']);
        $requestNotes = sanitize($_POST['request_notes']);
        
        // Revizyon kaydı oluştur
        $revisionId = generateUUID();
        
        $stmt = $pdo->prepare("
            INSERT INTO revisions (
                id, upload_id, user_id, request_notes, 
                requested_at, status
            ) VALUES (?, ?, ?, ?, NOW(), 'pending')
        ");
        
        $result = $stmt->execute([$revisionId, $uploadId, $userId, $requestNotes]);
        
        if ($result) {
            // Orijinal dosya durumunu güncelle
            $stmt = $pdo->prepare("
                UPDATE file_uploads 
                SET status = 'revision_requested' 
                WHERE id = ?
            ");
            $stmt->execute([$uploadId]);
            
            // ✅ EMAIL BİLDİRİMİ - BURASI ÖNEMLİ!
            // FileManager ile admin'e email bildirimi gönder
            $fileManager = new FileManager($pdo);
            
            // Revision verilerini hazırla
            $revisionData = [
                'id' => $revisionId,
                'upload_id' => $uploadId,
                'request_notes' => $requestNotes
            ];
            
            // Admin'e email bildirim gönder
            $emailSent = $fileManager->sendRevisionRequestNotificationToAdmin($revisionData);
            
            // Email gönderim durumunu veritabanında işaretle
            if ($emailSent) {
                $stmt = $pdo->prepare("
                    UPDATE revisions 
                    SET admin_notified = 1, admin_notified_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$revisionId]);
                
                error_log("Revizyon talebi email bildirimi gönderildi - Revision ID: $revisionId");
            } else {
                error_log("Revizyon talebi email bildirimi gönderilemedi - Revision ID: $revisionId");
            }
            
            $success = 'Revizyon talebiniz kaydedildi ve admin ekibimize bildirim gönderildi.';
        } else {
            $error = 'Revizyon talebi kaydedilemedi.';
        }
        
    } catch (Exception $e) {
        error_log('Revision request error: ' . $e->getMessage());
        $error = 'Revizyon talebi oluşturulurken hata oluştu.';
    }
}

// Dosya bilgilerini getir
$fileInfo = null;
if ($uploadId) {
    $stmt = $pdo->prepare("
        SELECT * FROM file_uploads 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$uploadId, $userId]);
    $fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Revizyon Talebi - Email Entegrasyonu</title>
</head>
<body>
    <h1>Revizyon Talebi Örneği</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($fileInfo): ?>
        <div class="file-info">
            <h3>Dosya Bilgileri</h3>
            <p><strong>Dosya:</strong> <?php echo $fileInfo['original_name']; ?></p>
            <p><strong>Durum:</strong> <?php echo $fileInfo['status']; ?></p>
            <p><strong>Yükleme Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($fileInfo['upload_date'])); ?></p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="upload_id" value="<?php echo $uploadId; ?>">
            
            <div class="form-group">
                <label>Revizyon Talep Notları:</label>
                <textarea name="request_notes" rows="5" placeholder="Lütfen revizyon talebinizi detaylı olarak açıklayın..." required></textarea>
            </div>
            
            <button type="submit">Revizyon Talebi Gönder</button>
        </form>
    <?php else: ?>
        <p>Dosya bulunamadı veya size ait değil.</p>
    <?php endif; ?>
</body>
</html>
