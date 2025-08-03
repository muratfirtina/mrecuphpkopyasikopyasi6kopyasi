<?php
/**
 * Mr ECU - File Download Handler - WORKING VERSION
 * Basit ve etkili dosya indirme
 */

// Hata raporlamayı kapat (production)
error_reporting(0);
ini_set('display_errors', 0);

// Output buffering'i tamamen temizle
if (ob_get_level()) {
    ob_end_clean();
}

// Session başlat
session_start();

// Basic includes
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Parametreler
$type = $_GET['type'] ?? '';
$uploadId = $_GET['id'] ?? '';
$fileId = $_GET['file_id'] ?? '';

// Temel kontroller
if (empty($uploadId) && empty($fileId)) {
    http_response_code(400);
    exit('Missing parameters');
}

$fullPath = '';
$fileName = '';

try {
    if (!empty($uploadId) && $type === 'original') {
        // Original file download
        if (!isValidUUID($uploadId)) {
            exit('Invalid ID');
        }
        
        $fileManager = new FileManager($pdo);
        $upload = $fileManager->getUploadById($uploadId);
        
        if (!$upload) {
            exit('Upload not found');
        }
        
        if (empty($upload['filename'])) {
            exit('Filename not found');
        }
        
        // Smart path detection
        $paths = [
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'],
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . basename($upload['filename']),
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $upload['filename'],
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . basename($upload['filename'])
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path) && is_readable($path)) {
                $fullPath = $path;
                break;
            }
        }
        
        if (!$fullPath) {
            exit('File not found on disk');
        }
        
        $fileName = $upload['original_name'];
        
    } elseif (!empty($uploadId) && $type === 'revision') {
        // Revision file download
        if (!isValidUUID($uploadId)) {
            exit('Invalid revision file ID');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM revision_files WHERE id = ?");
        $stmt->execute([$uploadId]);
        $revisionFile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$revisionFile) {
            exit('Revision file not found');
        }
        
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/revision_files/' . $revisionFile['filename'];
        $fileName = 'revision_' . ($revisionFile['original_name'] ?? $revisionFile['filename']);
        
        if (!file_exists($fullPath)) {
            exit('Revision file not found on disk');
        }
        
    } elseif (!empty($fileId)) {
        // Response file download
        if (!isValidUUID($fileId)) {
            exit('Invalid file ID');
        }
        
        $stmt = $pdo->prepare("SELECT fr.*, fu.original_name FROM file_responses fr LEFT JOIN file_uploads fu ON fr.upload_id = fu.id WHERE fr.id = ?");
        $stmt->execute([$fileId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$response) {
            exit('Response file not found');
        }
        
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/response_files/' . $response['filename'];
        $fileName = 'processed_' . ($response['original_name'] ?? $response['filename']);
        
        if (!file_exists($fullPath)) {
            exit('Response file not found on disk');
        }
        
    } else {
        exit('Invalid parameters');
    }
    
    // Son kontroller
    if (!$fullPath || !file_exists($fullPath) || !is_readable($fullPath)) {
        exit('File access error');
    }
    
    // File bilgileri
    $fileSize = filesize($fullPath);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // MIME type
    $mimeTypes = [
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'bin' => 'application/octet-stream',
        'hex' => 'application/octet-stream',
        'a2l' => 'application/octet-stream',
        'pdf' => 'application/pdf'
    ];
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    // Log download
    try {
        $user = new User($pdo);
        $user->logAction($_SESSION['user_id'], 'file_download', "Downloaded: {$fileName}");
    } catch (Exception $e) {
        // Log error but continue download
    }
    
    // Headers gönder
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '\"', $fileName) . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    header('Pragma: public');
    
    // Dosyayı gönder
    $handle = fopen($fullPath, 'rb');
    if ($handle) {
        while (!feof($handle) && connection_status() == 0) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        exit('Cannot read file');
    }
    
    exit;
    
} catch (Exception $e) {
    exit('Download error');
}
?>
