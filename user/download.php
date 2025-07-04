<?php
/**
 * Mr ECU - Dosya İndirme İşlemi (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Dosya ID kontrolü
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Geçersiz dosya parametreleri.';
    redirect('files.php');
}

$fileId = sanitize($_GET['id']);
$userId = $_SESSION['user_id'];

// GUID format kontrolü
if (!isValidUUID($fileId)) {
    $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    redirect('files.php');
}

// User ID'nin de GUID olduğunu kontrol et
if (!isValidUUID($userId)) {
    $_SESSION['error'] = 'Geçersiz kullanıcı ID formatı.';
    redirect('../logout.php');
}

$fileManager = new FileManager($pdo);
$result = $fileManager->downloadFile($fileId, $userId, 'response');

if (!$result['success']) {
    $_SESSION['error'] = $result['message'];
    redirect('files.php');
}

// Dosya indirme
$filePath = $result['file_path'];
$originalName = $result['original_name'];
$fileSize = $result['file_size'];

if (!file_exists($filePath)) {
    $_SESSION['error'] = 'Dosya bulunamadı.';
    redirect('files.php');
}

// Güvenlik kontrolü - dosya yolu kontrolü
$realPath = realpath($filePath);
$uploadDir = realpath(UPLOAD_DIR);

if (!$realPath || strpos($realPath, $uploadDir) !== 0) {
    $_SESSION['error'] = 'Dosya erişim hatası.';
    redirect('files.php');
}

// GUID debug bilgisi (sadece development için)
if (defined('DEBUG') && DEBUG) {
    error_log("User download - File ID (GUID): $fileId, User ID (GUID): $userId, File: $originalName");
}

// Dosya indirme headers
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Dosyayı output buffer ile gönder
ob_clean();
flush();
readfile($filePath);
exit;
?>
