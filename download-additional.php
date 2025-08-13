<?php
/**
 * Mr ECU - Additional File Download
 * Ek dosya indirme
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/FileManager.php';

// Session kontrolü
if (!isLoggedIn()) {
    redirect('login.php');
}

$fileManager = new FileManager($pdo);

$userId = $_SESSION['user_id'];
$userType = isAdmin() ? 'admin' : 'user';

// GUID format kontrolü
if (!isValidUUID($userId)) {
    redirect('logout.php');
}

// File ID kontrolü
$fileId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (!isValidUUID($fileId)) {
    $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    redirect($userType === 'admin' ? 'admin/uploads.php' : 'user/files.php');
}

// Dosya indirme
$result = $fileManager->downloadAdditionalFile($fileId, $userId, $userType);

if ($result['success']) {
    // Dosya indirme
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $result['original_name'] . '"');
    header('Content-Length: ' . $result['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($result['file_path']);
    exit;
} else {
    $_SESSION['error'] = $result['message'];
    redirect($userType === 'admin' ? 'admin/uploads.php' : 'user/files.php');
}
?>