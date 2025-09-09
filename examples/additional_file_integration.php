<?php
/**
 * Mr ECU - Ek Dosya Yükleme Entegrasyon Örneği
 * Bu dosya mevcut additional file upload işlemlerine email bildirimi ekleme örneğidir
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
$userType = isAdmin() ? 'admin' : 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $relatedFileId = sanitize($_POST['related_file_id']);
        $relatedFileType = sanitize($_POST['related_file_type']);
        $receiverId = sanitize($_POST['receiver_id']);
        $receiverType = sanitize($_POST['receiver_type']);
        $notes = sanitize($_POST['notes']);
        
        // Dosya yükleme işlemleri...
        $uploadDir = '../uploads/additional_files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $additionalFileId = generateUUID();
        $fileName = $additionalFileId . '_' . basename($_FILES['additional_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['additional_file']['tmp_name'], $filePath)) {
            // Veritabanına kaydet
            $stmt = $pdo->prepare("
                INSERT INTO additional_files (
                    id, related_file_id, related_file_type, sender_id, sender_type,
                    receiver_id, receiver_type, original_name, file_name, file_path,
                    file_size, file_type, notes, upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $additionalFileId, $relatedFileId, $relatedFileType, $userId, $userType,
                $receiverId, $receiverType, $_FILES['additional_file']['name'], $fileName,
                $filePath, $_FILES['additional_file']['size'], $_FILES['additional_file']['type'], $notes
            ]);
            
            if ($result) {
                // ✅ EMAIL BİLDİRİMİ - BURASI ÖNEMLİ!
                // FileManager ile alıcıya email bildirimi gönder
                $fileManager = new FileManager($pdo);
                
                // Additional file verilerini hazırla
                $fileData = [
                    'sender_id' => $userId,
                    'receiver_id' => $receiverId,
                    'original_name' => $_FILES['additional_file']['name'],
                    'notes' => $notes,
                    'related_file_id' => $relatedFileId,
                    'related_file_type' => $relatedFileType
                ];
                
                // Alıcı tipine göre email gönder
                $isToAdmin = ($receiverType === 'admin');
                $emailSent = $fileManager->sendAdditionalFileNotification($fileData, $isToAdmin);
                
                // Email gönderim durumunu veritabanında işaretle
                if ($emailSent) {
                    $stmt = $pdo->prepare("
                        UPDATE additional_files 
                        SET recipient_notified = 1, recipient_notified_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$additionalFileId]);
                    
                    error_log("Ek dosya email bildirimi gönderildi - File ID: $additionalFileId");
                } else {
                    error_log("Ek dosya email bildirimi gönderilemedi - File ID: $additionalFileId");
                }
                
                $success = 'Ek dosyanız başarıyla yüklendi ve alıcıya bildirim gönderildi.';
            } else {
                $error = 'Ek dosya veritabanına kaydedilemedi.';
            }
        } else {
            $error = 'Ek dosya yüklenemedi.';
        }
        
    } catch (Exception $e) {
        error_log('Additional file upload error: ' . $e->getMessage());
        $error = 'Ek dosya yüklenirken hata oluştu.';
    }
}

// İlgili dosyaları getir (kullanıcının kendi dosyaları)
$relatedFiles = [];
$stmt = $pdo->prepare("
    SELECT id, original_name, upload_date, status 
    FROM file_uploads 
    WHERE user_id = ? 
    ORDER BY upload_date DESC
");
$stmt->execute([$userId]);
$relatedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Admin kullanıcıları getir (ek dosya göndermek için)
$adminUsers = [];
$stmt = $pdo->prepare("
    SELECT id, CONCAT(first_name, ' ', last_name) as full_name, email 
    FROM users 
    WHERE role = 'admin' AND status = 'active'
");
$stmt->execute();
$adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ek Dosya Yükleme - Email Entegrasyonu</title>
</head>
<body>
    <h1>Ek Dosya Yükleme Örneği</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>İlgili Ana Dosya:</label>
            <select name="related_file_id" required>
                <option value="">Seçin...</option>
                <?php foreach ($relatedFiles as $file): ?>
                    <option value="<?php echo $file['id']; ?>">
                        <?php echo $file['original_name'] . ' (' . date('d.m.Y', strtotime($file['upload_date'])) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="related_file_type" value="upload">
        </div>
        
        <div class="form-group">
            <label>Alıcı:</label>
            <select name="receiver_id" required>
                <option value="">Admin Seçin...</option>
                <?php foreach ($adminUsers as $admin): ?>
                    <option value="<?php echo $admin['id']; ?>">
                        <?php echo $admin['full_name'] . ' (' . $admin['email'] . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="receiver_type" value="admin">
        </div>
        
        <div class="form-group">
            <label>Ek Dosya:</label>
            <input type="file" name="additional_file" required>
        </div>
        
        <div class="form-group">
            <label>Notlar:</label>
            <textarea name="notes" placeholder="Bu ek dosya hakkında açıklama..."></textarea>
        </div>
        
        <button type="submit">Ek Dosya Yükle ve Email Gönder</button>
    </form>
    
    <div class="info">
        <h3>Ek Dosya Sistemi Bilgileri</h3>
        <ul>
            <li>Ek dosya yüklediğinizde alıcıya otomatik email bildirimi gönderilir</li>
            <li>Dosya yükleme durumu veritabanında takip edilir</li>
            <li>Email gönderim başarısızlığı sistem loglarına kaydedilir</li>
        </ul>
    </div>
</body>
</html>
