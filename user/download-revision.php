<?php
/**
 * Mr ECU - Revize Dosyası İndirme (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

$fileManager = new FileManager($pdo);
$userId = $_SESSION['user_id'];

// GUID format kontrolü - User ID
if (!isValidUUID($userId)) {
    redirect('../logout.php');
}

// Admin kontrolü (eğer admin parametresi varsa)
$isAdminDownload = isset($_GET['admin']) && $_GET['admin'] == '1';
if ($isAdminDownload && !isAdmin()) {
    $_SESSION['error'] = 'Bu işlem için admin yetkisi gereklidir.';
    redirect('../admin/revisions.php');
}

// Dosya ID kontrolü
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Geçersiz dosya parametreleri.';
    redirect('files.php');
}

$fileId = sanitize($_GET['id']);

// GUID format kontrolü
if (!isValidUUID($fileId)) {
    $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    redirect('files.php');
}

// Dosyayı indir
if ($isAdminDownload) {
    // Admin için özel indirme fonksiyonu
    $result = $fileManager->downloadRevisionFileAdmin($fileId, $userId);
} else {
    // Normal kullanıcı indirmesi
    $result = $fileManager->downloadRevisionFile($fileId, $userId);
}

if (!$result['success']) {
    $_SESSION['error'] = $result['message'];
    if ($isAdminDownload) {
        redirect('../admin/revisions.php');
    } else {
        redirect('files.php');
    }
}

// Dosyayı indir
$filePath = $result['file_path'];
$originalName = $result['original_name'];
$fileSize = $result['file_size'];

// Dosya var mı kontrol et
if (!file_exists($filePath)) {
    $_SESSION['error'] = 'Dosya bulunamadı.';
    if ($isAdminDownload) {
        redirect('../admin/revisions.php');
    } else {
        redirect('files.php');
    }
}

// Güvenlik kontrolü - dosya yolu kontrolü
$realPath = realpath($filePath);
$uploadDir = realpath(UPLOAD_DIR);

if (!$realPath || strpos($realPath, $uploadDir) !== 0) {
    $_SESSION['error'] = 'Dosya erişim hatası.';
    if ($isAdminDownload) {
        redirect('../admin/revisions.php');
    } else {
        redirect('files.php');
    }
}

// GUID debug bilgisi (sadece development için)
if (defined('DEBUG') && DEBUG) {
    error_log("Revision download - File ID (GUID): $fileId, User ID (GUID): $userId, File: $originalName");
}

// Dosya indirme başlıkları
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
header('Content-Length: ' . $fileSize);
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Çıktı tamponlamasını temizle
if (ob_get_level()) {
    ob_end_clean();
}

// Dosyayı oku ve çıktıla
readfile($filePath);
exit;
?>
