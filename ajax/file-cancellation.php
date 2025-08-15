<?php
/**
 * Mr ECU - File Cancellation AJAX Handler
 * Dosya İptal AJAX İşleyicisi
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

// POST request kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

// GUID kontrolü
if (!isValidUUID($userId)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
    exit;
}

// FileCancellationManager'ı yükle
require_once '../includes/FileCancellationManager.php';
$cancellationManager = new FileCancellationManager($pdo);

try {
    switch ($action) {
        case 'request_cancellation':
            $fileId = sanitize($_POST['file_id'] ?? '');
            $fileType = sanitize($_POST['file_type'] ?? '');
            $reason = sanitize($_POST['reason'] ?? '');
            
            if (!isValidUUID($fileId)) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz dosya ID.']);
                exit;
            }
            
            $result = $cancellationManager->requestCancellation($userId, $fileId, $fileType, $reason);
            echo json_encode($result);
            break;
            
        case 'approve_cancellation':
            // Admin kontrolü
            if (!isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
                exit;
            }
            
            $cancellationId = sanitize($_POST['cancellation_id'] ?? '');
            $adminNotes = sanitize($_POST['admin_notes'] ?? '');
            
            if (!isValidUUID($cancellationId)) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz iptal talebi ID.']);
                exit;
            }
            
            $result = $cancellationManager->approveCancellation($cancellationId, $userId, $adminNotes);
            echo json_encode($result);
            break;
            
        case 'reject_cancellation':
            // Admin kontrolü
            if (!isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
                exit;
            }
            
            $cancellationId = sanitize($_POST['cancellation_id'] ?? '');
            $adminNotes = sanitize($_POST['admin_notes'] ?? '');
            
            if (!isValidUUID($cancellationId)) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz iptal talebi ID.']);
                exit;
            }
            
            if (empty(trim($adminNotes))) {
                echo json_encode(['success' => false, 'message' => 'Red sebebi gereklidir.']);
                exit;
            }
            
            $result = $cancellationManager->rejectCancellation($cancellationId, $userId, $adminNotes);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Cancellation AJAX error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sistem hatası oluştu.']);
}
?>
