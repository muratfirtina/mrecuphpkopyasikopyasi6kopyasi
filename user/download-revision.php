<?php
/**
 * Mr ECU - Kullanıcı Revizyon Dosyası İndirme
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

require_once '../includes/FileManager.php';

$fileManager = new FileManager($pdo);
$userId = $_SESSION['user_id'];

// Revizyon dosya ID kontrolü
if (!isset($_GET['id']) || !isValidUUID($_GET['id'])) {
    http_response_code(400);
    exit('Geçersiz dosya ID');
}

$revisionFileId = $_GET['id'];

try {
    // Revizyon dosyası indirme kontrolü
    $result = $fileManager->downloadRevisionFile($revisionFileId, $userId);
    
    if (!$result['success']) {
        http_response_code(404);
        exit($result['message']);
    }
    
    $filePath = $result['file_path'];
    $originalName = $result['original_name'];
    $fileSize = $result['file_size'];
    
    // Dosya varlık kontrolü
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('Dosya bulunamadı');
    }
    
    // İndirme başlıkları
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $originalName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Dosyayı oku ve çıktı ver
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    error_log('Revizyon dosyası indirme hatası: ' . $e->getMessage());
    http_response_code(500);
    exit('Dosya indirme hatası');
}
?>
