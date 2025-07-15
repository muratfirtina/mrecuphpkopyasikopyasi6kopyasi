<?php
/**
 * Mr ECU - File Download Handler
 * Response dosyaları dahil dosya indirme işleyici
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    die('Yetkisiz erişim.');
}

// Parametreleri kontrol et
if (!isset($_GET['id']) || !isValidUUID($_GET['id'])) {
    http_response_code(400);
    die('Geçersiz dosya ID.');
}

$fileId = $_GET['id'];
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload';

try {
    if ($fileType === 'response') {
        // Response dosyası indirme
        $stmt = $pdo->prepare("
            SELECT fr.*, fu.original_name as original_upload_name
            FROM file_responses fr
            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
            WHERE fr.id = ?
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            http_response_code(404);
            die('Dosya bulunamadı.');
        }
        
        $filePath = '../uploads/response_files/' . $file['filename'];
        $downloadName = $file['original_name'];
        
    } elseif ($fileType === 'revision') {
        // Revizyon dosyası indirme
        $stmt = $pdo->prepare("
            SELECT rf.*, r.id as revision_id, fu.original_name as original_upload_name
            FROM revision_files rf
            LEFT JOIN revisions r ON rf.revision_id = r.id
            LEFT JOIN file_uploads fu ON r.upload_id = fu.id
            WHERE r.id = ?
            ORDER BY rf.upload_date DESC
            LIMIT 1
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            // Alternatif sorgu - revision_files ID'si ile deneme
            $stmt = $pdo->prepare("
                SELECT rf.*, r.id as revision_id, fu.original_name as original_upload_name
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                WHERE rf.id = ?
                ORDER BY rf.upload_date DESC
                LIMIT 1
            ");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$file) {
            error_log("Revision file not found for ID: $fileId");
            http_response_code(404);
            die('Revizyon dosyası bulunamadı.');
        }
        
        $filePath = '../uploads/revision_files/' . $file['filename'];
        $downloadName = $file['original_name'];
        
        // İndirme kaydını güncelle
        try {
            $updateStmt = $pdo->prepare("
                UPDATE revision_files 
                SET downloaded = 1, download_date = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$file['id']]);
        } catch (Exception $e) {
            error_log('Download tracking error: ' . $e->getMessage());
        }
        
    } else {
        // Normal upload dosyası indirme
        $stmt = $pdo->prepare("
            SELECT * FROM file_uploads 
            WHERE id = ?
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            http_response_code(404);
            die('Dosya bulunamadı.');
        }
        
        $filePath = '../uploads/user_files/' . $file['filename'];
        $downloadName = $file['original_name'];
    }
    
    // Dosya path'ini tam path'e çevir
    $fullPath = realpath($filePath);
    
    if (!$fullPath || !file_exists($fullPath)) {
        http_response_code(404);
        die('Fiziksel dosya bulunamadı.');
    }
    
    // Dosya indirme headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));
    
    // Dosyayı chunks halinde oku ve output et
    readfile($fullPath);
    
} catch (Exception $e) {
    error_log('Download error: ' . $e->getMessage());
    http_response_code(500);
    die('Dosya indirme hatası.');
}
?>
