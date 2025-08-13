<?php
/**
 * Mr ECU - Additional Files AJAX Handler
 * Ek dosya işlemleri için AJAX handler
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';
require_once '../includes/NotificationManager.php';

// Session kontrolü
if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']));
}

header('Content-Type: application/json');

$fileManager = new FileManager($pdo);
$notificationManager = new NotificationManager($pdo);

$userId = $_SESSION['user_id'];
$userType = isAdmin() ? 'admin' : 'user';

// GUID format kontrolü
if (!isValidUUID($userId)) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID formatı.']));
}

$action = isset($_POST['action']) ? sanitize($_POST['action']) : (isset($_GET['action']) ? sanitize($_GET['action']) : '');

switch($action) {
    case 'upload_additional_file':
        // Dosya yükleme işlemi
        if (!isset($_FILES['additional_file']) || $_FILES['additional_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Dosya çok büyük (php.ini limit)',
                UPLOAD_ERR_FORM_SIZE => 'Dosya çok büyük (form limit)',
                UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
                UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin yok',
                UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı',
                UPLOAD_ERR_EXTENSION => 'Uzantı yüklemeyi durdurdu'
            ];
            
            $fileError = $_FILES['additional_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            die(json_encode([
                'success' => false, 
                'message' => $errorMessages[$fileError] ?? 'Bilinmeyen dosya yükleme hatası (' . $fileError . ')'
            ]));
        }
        
        // Parametreleri al
        $relatedFileId = sanitize($_POST['related_file_id'] ?? '');
        $relatedFileType = sanitize($_POST['related_file_type'] ?? 'upload');
        $receiverId = sanitize($_POST['receiver_id'] ?? '');
        $receiverType = sanitize($_POST['receiver_type'] ?? 'user');
        $notes = sanitize($_POST['notes'] ?? '');
        $credits = floatval($_POST['credits'] ?? 0);
        
        // Validasyon
        if (!isValidUUID($relatedFileId)) {
            die(json_encode(['success' => false, 'message' => 'Geçersiz dosya ID formatı.']));
        }
        
        if (!isValidUUID($receiverId)) {
            die(json_encode(['success' => false, 'message' => 'Geçersiz alıcı ID formatı.']));
        }
        
        if ($credits < 0) {
            die(json_encode(['success' => false, 'message' => 'Kredi miktarı negatif olamaz.']));
        }
        
        // Dosya bilgilerini hazırla
        $fileData = [
            'name' => $_FILES['additional_file']['name'],
            'type' => $_FILES['additional_file']['type'],
            'tmp_name' => $_FILES['additional_file']['tmp_name'],
            'error' => $_FILES['additional_file']['error'],
            'size' => $_FILES['additional_file']['size']
        ];
        
        // Dosyayı yükle
        $result = $fileManager->uploadAdditionalFile(
            $relatedFileId,
            $relatedFileType,
            $userId,
            $userType,
            $receiverId,
            $receiverType,
            $fileData,
            $notes,
            $credits
        );
        
        echo json_encode($result);
        break;
        
    case 'get_additional_files':
        // Ek dosyaları getir
        $relatedFileId = sanitize($_GET['file_id'] ?? '');
        
        if (!isValidUUID($relatedFileId)) {
            die(json_encode(['success' => false, 'message' => 'Geçersiz dosya ID formatı.']));
        }
        
        $files = $fileManager->getAdditionalFiles($relatedFileId, $userId, $userType);
        
        echo json_encode([
            'success' => true,
            'files' => $files,
            'count' => count($files)
        ]);
        break;
        
    case 'get_unread_count':
        // Okunmamış dosya sayısını getir
        $count = $fileManager->getUnreadAdditionalFilesCount($userId, $userType);
        
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        break;
        
    case 'mark_as_read':
        // Dosyayı okundu olarak işaretle
        $fileId = sanitize($_POST['file_id'] ?? '');
        
        if (!isValidUUID($fileId)) {
            die(json_encode(['success' => false, 'message' => 'Geçersiz dosya ID formatı.']));
        }
        
        try {
            $stmt = $pdo->prepare("
                UPDATE additional_files 
                SET is_read = 1, read_date = NOW() 
                WHERE id = ? AND receiver_id = ? AND receiver_type = ?
            ");
            $stmt->execute([$fileId, $userId, $userType]);
            
            echo json_encode(['success' => true, 'message' => 'Dosya okundu olarak işaretlendi.']);
        } catch(PDOException $e) {
            error_log('Mark as read error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'İşlem başarısız.']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
        break;
}
?>