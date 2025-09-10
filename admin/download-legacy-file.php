<?php
/**
 * Mr ECU - Admin Panel - Download Legacy File
 * Admin panelinden eski dosya indirme
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/LegacyFilesManager.php';

// Admin kontrolü
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Yetkiniz yok.');
}

$fileId = $_GET['id'] ?? '';

if (empty($fileId) || !isValidUUID($fileId)) {
    http_response_code(400);
    die('Geçersiz dosya ID.');
}

$legacyManager = new LegacyFilesManager($pdo);

// Dosya bilgilerini al
try {
    $stmt = $pdo->prepare("SELECT * FROM legacy_files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        die('Dosya bulunamadı.');
    }

    if (!file_exists($file['file_path'])) {
        http_response_code(404);
        die('Dosya sistemde bulunamadı.');
    }

    // Log kaydı
    $user = new User($pdo);
    $user->logAction($_SESSION['user_id'], 'admin_legacy_download', 
                    'Admin legacy dosya indirdi: ' . $file['original_filename'] . ' (User: ' . $file['user_id'] . ')');

    // Dosyayı indir
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
    header('Content-Length: ' . filesize($file['file_path']));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Dosyayı oku ve çıktıla
    readfile($file['file_path']);
    exit;

} catch (Exception $e) {
    error_log('Admin legacy file download error: ' . $e->getMessage());
    http_response_code(500);
    die('Dosya indirme sırasında hata oluştu.');
}
