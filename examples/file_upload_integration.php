<?php
/**
 * Mr ECU - Dosya Yükleme Entegrasyon Örneği
 * Bu dosya mevcut file upload işlemlerine email bildirimi ekleme örneğidir
 */

// Bu dosya sadece örnek amaçlıdır - mevcut upload sayfanıza entegre edin

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Mevcut dosya yükleme işleminiz...
        // Form verilerini al
        $brandId = sanitize($_POST['brand_id']);
        $modelId = sanitize($_POST['model_id']);
        $seriesId = sanitize($_POST['series_id']);
        $engineId = sanitize($_POST['engine_id']);
        $year = (int)$_POST['year'];
        $fuelType = sanitize($_POST['fuel_type']);
        $gearboxType = sanitize($_POST['gearbox_type']);
        $uploadNotes = sanitize($_POST['upload_notes']);
        
        // Dosya yükleme işlemleri...
        $uploadDir = '../uploads/user_files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadId = generateUUID();
        $fileName = $uploadId . '_' . basename($_FILES['file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            // Veritabanına kaydet
            $stmt = $pdo->prepare("
                INSERT INTO file_uploads (
                    id, user_id, brand_id, model_id, series_id, engine_id,
                    year, fuel_type, gearbox_type, filename, original_name,
                    file_size, file_path, upload_notes, upload_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
            ");
            
            $result = $stmt->execute([
                $uploadId, $userId, $brandId, $modelId, $seriesId, $engineId,
                $year, $fuelType, $gearboxType, $fileName, $_FILES['file']['name'],
                $_FILES['file']['size'], $filePath, $uploadNotes
            ]);
            
            if ($result) {
                // ✅ EMAIL BİLDİRİMİ - BURASI ÖNEMLİ!
                // FileManager ile admin'e email bildirimi gönder
                $fileManager = new FileManager($pdo);
                
                // Upload verilerini hazırla
                $uploadData = [
                    'id' => $uploadId,
                    'user_id' => $userId,
                    'original_name' => $_FILES['file']['name'],
                    'brand_id' => $brandId,
                    'model_id' => $modelId,
                    'series_id' => $seriesId,
                    'engine_id' => $engineId,
                    'year' => $year,
                    'fuel_type' => $fuelType,
                    'gearbox_type' => $gearboxType,
                    'upload_notes' => $uploadNotes
                ];
                
                // Admin'e email bildirim gönder
                $emailSent = $fileManager->sendFileUploadNotificationToAdmin($uploadData);
                
                // Email gönderim durumunu veritabanında işaretle
                if ($emailSent) {
                    $stmt = $pdo->prepare("
                        UPDATE file_uploads 
                        SET admin_notified = 1, admin_notified_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$uploadId]);
                    
                    error_log("Email bildirimi gönderildi - Upload ID: $uploadId");
                } else {
                    error_log("Email bildirimi gönderilemedi - Upload ID: $uploadId");
                }
                
                $success = 'Dosyanız başarıyla yüklendi ve işlenmek üzere kuyruğa alındı.';
                
                // Kredi düşür (eğer kredi sistemi varsa)
                $user = new User($pdo);
                $creditResult = $user->deductCredits($userId, CREDITS_PER_FILE, 'Dosya yükleme - ' . $_FILES['file']['name']);
                
                if (!$creditResult['success']) {
                    error_log('Kredi düşme hatası: ' . $creditResult['message']);
                }
            } else {
                $error = 'Dosya veritabanına kaydedilemedi.';
            }
        } else {
            $error = 'Dosya yüklenemedi.';
        }
        
    } catch (Exception $e) {
        error_log('File upload error: ' . $e->getMessage());
        $error = 'Dosya yükleme sırasında hata oluştu.';
    }
}

// HTML formu (mevcut upload formunuz)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dosya Yükleme - Email Entegrasyonu</title>
</head>
<body>
    <h1>Dosya Yükleme Örneği</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <!-- Mevcut form alanlarınız -->
        <input type="file" name="file" required>
        <input type="text" name="brand_id" placeholder="Marka ID">
        <input type="text" name="model_id" placeholder="Model ID">
        <input type="text" name="series_id" placeholder="Seri ID">
        <input type="text" name="engine_id" placeholder="Motor ID">
        <input type="number" name="year" placeholder="Yıl">
        <select name="fuel_type">
            <option value="Benzin">Benzin</option>
            <option value="Dizel">Dizel</option>
        </select>
        <select name="gearbox_type">
            <option value="Manual">Manuel</option>
            <option value="Automatic">Otomatik</option>
        </select>
        <textarea name="upload_notes" placeholder="Notlar"></textarea>
        <button type="submit">Dosya Yükle</button>
    </form>
</body>
</html>
