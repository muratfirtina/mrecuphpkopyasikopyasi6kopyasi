<?php
/**
 * Mr ECU - Admin Dosya İndirme İşlemi (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Dosya ID ve tip kontrolü
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    $_SESSION['error'] = 'Geçersiz dosya parametreleri.';
    redirect('uploads.php');
}

$fileId = sanitize($_GET['id']);
$type = sanitize($_GET['type']);

// GUID format kontrolü
if (!isValidUUID($fileId)) {
    $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    redirect('uploads.php');
}

$fileManager = new FileManager($pdo);
$user = new User($pdo);

if ($type === 'original') {
    // Orijinal dosya indirme
    $upload = $fileManager->getUploadById($fileId);
    
    if (!$upload) {
        $_SESSION['error'] = 'Dosya bulunamadı.';
        redirect('uploads.php');
    }
    
    $filePath = UPLOAD_DIR . 'user_files/' . $upload['filename'];
    $originalName = $upload['original_name'];
    
    if (!file_exists($filePath)) {
        $_SESSION['error'] = 'Dosya sistemde bulunamadı.';
        redirect('uploads.php');
    }
    
    // Log kaydı
    $user->logAction($_SESSION['user_id'], 'admin_download', 'Orijinal dosya indirildi: ' . $originalName . ' (ID: ' . $fileId . ')');
    
} elseif ($type === 'admin') {
    // Admin yanıt dosyası indirme
    try {
        $stmt = $pdo->prepare("SELECT * FROM file_responses WHERE id = ?");
        $stmt->execute([$fileId]);
        $response = $stmt->fetch();
        
        if (!$response) {
            $_SESSION['error'] = 'Yanıt dosyası bulunamadı.';
            redirect('uploads.php');
        }
        
        $filePath = UPLOAD_DIR . 'response_files/' . $response['filename'];
        $originalName = $response['original_name'];
        
        if (!file_exists($filePath)) {
            $_SESSION['error'] = 'Yanıt dosyası sistemde bulunamadı.';
            redirect('uploads.php');
        }
        
        // Log kaydı
        $user->logAction($_SESSION['user_id'], 'admin_download', 'Yanıt dosyası indirildi: ' . $originalName . ' (ID: ' . $fileId . ')');
        
    } catch(PDOException $e) {
        error_log('Admin download error: ' . $e->getMessage());
        $_SESSION['error'] = 'Veritabanı hatası.';
        redirect('uploads.php');
    }
} else {
    $_SESSION['error'] = 'Geçersiz dosya tipi.';
    redirect('uploads.php');
}

// Güvenlik kontrolü - dosya yolu kontrolü
$realPath = realpath($filePath);
$uploadDir = realpath(UPLOAD_DIR);

if (!$realPath || strpos($realPath, $uploadDir) !== 0) {
    $_SESSION['error'] = 'Dosya erişim hatası.';
    redirect('uploads.php');
}

$fileSize = filesize($filePath);

// Dosya indirme headers
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: must-revalidate');
header('Pragma: public');

// GUID debug bilgisi (sadece development için)
if (defined('DEBUG') && DEBUG) {
    error_log("Admin download - File ID (GUID): $fileId, Type: $type, File: $originalName");
}

// Dosyayı output buffer ile gönder
ob_clean();
flush();
readfile($filePath);
exit;
?>
