<?php
/**
 * Mr ECU - Revizyon Dosyası İçin Revize Talebi Oluşturma AJAX
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Sadece AJAX POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir.']);
    exit;
}

// Content-Type kontrolü
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekli.']);
    exit;
}

try {
    $fileManager = new FileManager($pdo);
    $userId = $_SESSION['user_id'];
    
    // Parametre kontrolü
    $revisionFileId = $input['revision_file_id'] ?? '';
    $revisionNotes = trim($input['revision_notes'] ?? '');
    
    if (empty($revisionFileId)) {
        echo json_encode(['success' => false, 'message' => 'Revizyon dosya ID belirtilmedi.']);
        exit;
    }
    
    if (empty($revisionNotes)) {
        echo json_encode(['success' => false, 'message' => 'Revize talebi notları gereklidir.']);
        exit;
    }
    
    // UUID format kontrolü
    if (!isValidUUID($revisionFileId)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz revizyon dosya ID formatı.']);
        exit;
    }
    
    if (!isValidUUID($userId)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID formatı.']);
        exit;
    }
    
    // Revizyon dosyası için revize talebi oluştur
    $result = $fileManager->requestRevisionFileRevision($revisionFileId, $userId, $revisionNotes);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true, 
            'message' => $result['message']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log('Create revision file revision AJAX error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}
?>
