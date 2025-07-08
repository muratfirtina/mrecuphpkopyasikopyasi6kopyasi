<?php
/**
 * Mr ECU - Revize Dosyası İndirme İşlemi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Revize ID kontrolü
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Geçersiz revize parametreleri.';
    redirect('revisions.php');
}

$revisionId = sanitize($_GET['id']);
$userId = $_SESSION['user_id'];

// GUID format kontrolü
if (!isValidUUID($revisionId)) {
    $_SESSION['error'] = 'Geçersiz revize ID formatı.';
    redirect('revisions.php');
}

try {
    // Revize detaylarını al
    $stmt = $pdo->prepare("
        SELECT r.*, fu.original_name, fu.filename, fu.user_id as file_owner_id
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        WHERE r.id = ? AND r.user_id = ? AND r.status = 'completed'
    ");
    $stmt->execute([$revisionId, $userId]);
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$revision) {
        $_SESSION['error'] = 'Revize bulunamadı veya henüz tamamlanmamış.';
        redirect('revisions.php');
    }
    
    // Revize dosyası var mı kontrol et (revision_files tablosu)
    $stmt = $pdo->prepare("
        SELECT * FROM revision_files 
        WHERE revision_id = ? 
        ORDER BY upload_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$revisionId]);
    $revisionFile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($revisionFile) {
        // Revize dosyası var - revize dosyasını indir
        $filePath = UPLOAD_PATH . 'revision_files/' . $revisionFile['filename'];
        $originalName = $revisionFile['original_name'] ?? $revision['original_name'];
        $fileSize = $revisionFile['file_size'] ?? 0;
    } else {
        // Revize dosyası yok - orijinal dosyayı indir
        $filePath = UPLOAD_PATH . 'user_files/' . $revision['filename'];
        $originalName = $revision['original_name'];
        $fileSize = filesize($filePath);
    }
    
    if (!file_exists($filePath)) {
        $_SESSION['error'] = 'Revize dosyası bulunamadı.';
        redirect('revisions.php');
    }
    
    // Güvenlik kontrolü - dosya yolu kontrolü
    $realPath = realpath($filePath);
    $uploadDir = realpath(UPLOAD_PATH);
    
    if (!$realPath || strpos($realPath, $uploadDir) !== 0) {
        $_SESSION['error'] = 'Dosya erişim hatası.';
        redirect('revisions.php');
    }
    
    // Debug log
    if (defined('DEBUG') && DEBUG) {
        error_log("Revision download - Revision ID: $revisionId, User ID: $userId, File: $originalName");
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
    
} catch(PDOException $e) {
    error_log('Download revision error: ' . $e->getMessage());
    $_SESSION['error'] = 'Veritabanı hatası oluştu.';
    redirect('revisions.php');
} catch(Exception $e) {
    error_log('Download revision error: ' . $e->getMessage());
    $_SESSION['error'] = 'Dosya indirme hatası oluştu.';
    redirect('revisions.php');
}
?>
