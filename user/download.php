<?php
/**
 * Mr ECU - Dosya İndirme İşlemi (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Debug modu (geliştirme ortamı için)
$debug = defined('DEBUG') && DEBUG;

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
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload'; // Type parametresini al
$userId = $_SESSION['user_id'];

if ($debug) {
    error_log("Download Debug - File ID: $fileId, Type: $fileType, User ID: $userId");
}

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

// File type validation
if (!in_array($fileType, ['upload', 'response'])) {
    $_SESSION['error'] = 'Geçersiz dosya tipi.';
    if ($debug) {
        error_log("Download Debug - Invalid file type: $fileType");
    }
    redirect('files.php');
}

$fileManager = new FileManager($pdo);
$result = $fileManager->downloadFile($fileId, $userId, $fileType);

if ($debug) {
    error_log("Download Debug - FileManager result: " . print_r($result, true));
}

if (!$result['success']) {
    $_SESSION['error'] = $result['message'];
    if ($debug) {
        error_log("Download Debug - FileManager error: " . $result['message']);
    }
    redirect('files.php');
}

// Dosya indirme
$filePath = $result['file_path'];
$originalName = $result['original_name'];
$fileSize = $result['file_size'];

if ($debug) {
    error_log("Download Debug - File path: $filePath");
    error_log("Download Debug - Original name: $originalName");
    error_log("Download Debug - File size: $fileSize");
    error_log("Download Debug - File exists: " . (file_exists($filePath) ? 'YES' : 'NO'));
}

if (!file_exists($filePath)) {
    $_SESSION['error'] = 'Dosya bulunamadı.';
    if ($debug) {
        error_log("Download Debug - Physical file not found: $filePath");
    }
    redirect('files.php');
}

// Güvenlik kontrolü - dosya yolu kontrolü
$realPath = realpath($filePath);
$uploadDir = realpath(UPLOAD_DIR);

if ($debug) {
    error_log("Download Debug - Real path: $realPath");
    error_log("Download Debug - Upload dir: $uploadDir");
    error_log("Download Debug - UPLOAD_DIR constant: " . UPLOAD_DIR);
}

if (!$realPath || strpos($realPath, $uploadDir) !== 0) {
    $_SESSION['error'] = 'Dosya erişim hatası.';
    if ($debug) {
        error_log("Download Debug - Security check failed");
        error_log("Download Debug - Real path check: " . ($realPath ? 'OK' : 'FAILED'));
        error_log("Download Debug - Path prefix check: " . (strpos($realPath, $uploadDir) === 0 ? 'OK' : 'FAILED'));
    }
    redirect('files.php');
}

// GUID debug bilgisi (sadece development için)
if ($debug) {
    error_log("User download - File ID (GUID): $fileId, User ID (GUID): $userId, Type: $fileType, File: $originalName");
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
