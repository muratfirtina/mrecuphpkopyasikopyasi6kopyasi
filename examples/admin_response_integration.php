<?php
/**
 * Mr ECU - Admin Dosya Yanıtlama Entegrasyon Örneği
 * Bu dosya mevcut admin response işlemlerine email bildirimi ekleme örneğidir
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';
$uploadId = $_GET['upload_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uploadId = sanitize($_POST['upload_id']);
        $adminNotes = sanitize($_POST['admin_notes']);
        $adminId = $_SESSION['user_id'];
        
        // Dosya yükleme işlemleri...
        $uploadDir = '../uploads/admin_responses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $responseId = generateUUID();
        $fileName = $responseId . '_' . basename($_FILES['response_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['response_file']['tmp_name'], $filePath)) {
            // Veritabanına yanıt dosyasını kaydet
            $stmt = $pdo->prepare("
                INSERT INTO file_responses (
                    id, upload_id, admin_id, filename, original_name,
                    file_size, file_type, admin_notes, upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $responseId, $uploadId, $adminId, $fileName, $_FILES['response_file']['name'],
                $_FILES['response_file']['size'], $_FILES['response_file']['type'], $adminNotes
            ]);
            
            if ($result) {
                // Orijinal dosya durumunu güncelle
                $stmt = $pdo->prepare("
                    UPDATE file_uploads 
                    SET status = 'completed', completed_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$uploadId]);
                
                // ✅ EMAIL BİLDİRİMİ - BURASI ÖNEMLİ!
                // FileManager ile kullanıcıya email bildirimi gönder
                $fileManager = new FileManager($pdo);
                
                // Response verilerini hazırla
                $responseData = [
                    'id' => $responseId,
                    'upload_id' => $uploadId,
                    'original_name' => $_FILES['response_file']['name'],
                    'admin_notes' => $adminNotes
                ];
                
                // Kullanıcıya email bildirim gönder
                $emailSent = $fileManager->sendFileResponseNotificationToUser($responseData);
                
                // Email gönderim durumunu veritabanında işaretle
                if ($emailSent) {
                    $stmt = $pdo->prepare("
                        UPDATE file_responses 
                        SET user_notified = 1, user_notified_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$responseId]);
                    
                    error_log("Kullanıcıya email bildirimi gönderildi - Response ID: $responseId");
                } else {
                    error_log("Kullanıcıya email bildirimi gönderilemedi - Response ID: $responseId");
                }
                
                $success = 'Yanıt dosyası başarıyla yüklendi ve kullanıcıya bildirim gönderildi.';
            } else {
                $error = 'Yanıt dosyası veritabanına kaydedilemedi.';
            }
        } else {
            $error = 'Yanıt dosyası yüklenemedi.';
        }
        
    } catch (Exception $e) {
        error_log('Admin response error: ' . $e->getMessage());
        $error = 'Yanıt dosyası yüklenirken hata oluştu.';
    }
}

// Orijinal dosya bilgilerini getir
$uploadInfo = null;
if ($uploadId) {
    $stmt = $pdo->prepare("
        SELECT fu.*, u.first_name, u.last_name, u.email,
               b.name as brand_name, m.name as model_name
        FROM file_uploads fu
        JOIN users u ON fu.user_id = u.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.id = ?
    ");
    $stmt->execute([$uploadId]);
    $uploadInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Yanıt Dosyası - Email Entegrasyonu</title>
</head>
<body>
    <h1>Admin Yanıt Dosyası Yükleme Örneği</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($uploadInfo): ?>
        <div class="upload-info">
            <h3>Orijinal Dosya Bilgileri</h3>
            <p><strong>Kullanıcı:</strong> <?php echo $uploadInfo['first_name'] . ' ' . $uploadInfo['last_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $uploadInfo['email']; ?></p>
            <p><strong>Dosya:</strong> <?php echo $uploadInfo['original_name']; ?></p>
            <p><strong>Araç:</strong> <?php echo $uploadInfo['brand_name'] . ' ' . $uploadInfo['model_name'] . ' (' . $uploadInfo['year'] . ')'; ?></p>
            <p><strong>Yükleme Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($uploadInfo['upload_date'])); ?></p>
            <?php if ($uploadInfo['upload_notes']): ?>
                <p><strong>Kullanıcı Notları:</strong> <?php echo $uploadInfo['upload_notes']; ?></p>
            <?php endif; ?>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_id" value="<?php echo $uploadId; ?>">
            
            <div class="form-group">
                <label>Yanıt Dosyası:</label>
                <input type="file" name="response_file" required>
            </div>
            
            <div class="form-group">
                <label>Admin Notları:</label>
                <textarea name="admin_notes" placeholder="Kullanıcıya gönderilecek notlar..."></textarea>
            </div>
            
            <button type="submit">Yanıt Dosyasını Yükle ve Email Gönder</button>
        </form>
    <?php else: ?>
        <p>Geçersiz upload ID.</p>
    <?php endif; ?>
</body>
</html>
