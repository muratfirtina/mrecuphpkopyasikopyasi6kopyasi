<?php
/**
 * Mr ECU - User Panel - Download Legacy File
 * Kullanıcının eski dosyalarını indirme
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/LegacyFilesManager.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(403);
    die('Giriş yapmanız gerekiyor.');
}

$fileId = $_GET['id'] ?? '';
$preview = isset($_GET['preview']) && $_GET['preview'] == '1';

if (empty($fileId) || !isValidUUID($fileId)) {
    http_response_code(400);
    die('Geçersiz dosya ID.');
}

$legacyManager = new LegacyFilesManager($pdo);
$userId = $_SESSION['user_id'];

// Dosyayı kullanıcı için indir
$result = $legacyManager->downloadFile($fileId, $userId);

if (!$result['success']) {
    http_response_code(404);
    die($result['message']);
}

$file = $result['file'];

// Log kaydı
$user = new User($pdo);
$user->logAction($userId, 'legacy_file_download', 
                'Legacy dosya indirildi: ' . $file['original_filename'] . ' (Plaka: ' . $file['plate_number'] . ')');

// Dosya önizleme için (resimler)
if ($preview) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $fileExtension = strtolower(pathinfo($file['original_filename'], PATHINFO_EXTENSION));
    
    if (in_array($fileExtension, $imageExtensions)) {
        header('Content-Type: ' . $file['file_type']);
        header('Content-Length: ' . filesize($file['file_path']));
        header('Cache-Control: public, max-age=3600');
        
        readfile($file['file_path']);
        exit;
    }
}

// Normal dosya indirme
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
header('Content-Length: ' . filesize($file['file_path']));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Dosyayı oku ve çıktıla
readfile($file['file_path']);
exit;
