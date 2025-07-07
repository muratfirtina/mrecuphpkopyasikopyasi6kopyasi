<?php
/**
 * Download Fixed - Smart path detection ile dosya indirme
 */

// Output buffering'i temizle
while (ob_get_level()) {
    ob_end_clean();
}

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Unauthorized access');
}

$uploadId = $_GET['id'] ?? null;
$forcePath = $_GET['path'] ?? null;

if (!$uploadId || !isValidUUID($uploadId)) {
    http_response_code(400);
    die('Invalid parameters');
}

try {
    $fileManager = new FileManager($pdo);
    $upload = $fileManager->getUploadById($uploadId);
    
    if (!$upload) {
        http_response_code(404);
        die('Upload not found');
    }
    
    // Smart path detection
    $fullPath = '';
    
    if ($forcePath && file_exists($forcePath)) {
        // Forced path from debug
        $fullPath = $forcePath;
    } else {
        // Try multiple possible paths
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'],
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . basename($upload['filename']),
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $upload['filename'],
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . basename($upload['filename']),
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $fullPath = $path;
                break;
            }
        }
    }
    
    if (!$fullPath || !file_exists($fullPath)) {
        error_log('File not found for upload: ' . $uploadId . ', filename: ' . $upload['filename']);
        http_response_code(404);
        die('File not found on disk');
    }
    
    $fileName = $upload['original_name'];
    $fileSize = filesize($fullPath);
    
    // MIME type
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeTypes = [
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'bin' => 'application/octet-stream',
        'hex' => 'application/octet-stream',
        'pdf' => 'application/pdf',
    ];
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    // Log download
    $user = new User($pdo);
    $user->logAction($_SESSION['user_id'], 'file_download', "Downloaded: {$fileName}");
    
    // Download headers
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    header('Pragma: public');
    
    // Send file
    readfile($fullPath);
    exit;
    
} catch (Exception $e) {
    error_log('Download error: ' . $e->getMessage());
    http_response_code(500);
    die('Download error occurred');
}
?>
