<?php
/**
 * Mr ECU - Send Test Email (Admin)
 * Test Email Gönder
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek methodu.']);
    exit;
}

try {
    // JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['to_email']) || empty($input['to_email'])) {
        echo json_encode(['success' => false, 'message' => 'Email adresi gerekli.']);
        exit;
    }
    
    $toEmail = sanitize($input['to_email'], 'email');
    
    // Email formatı kontrolü
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz email formatı.']);
        exit;
    }
    
    // EmailManager kullan
    $emailManager = new EmailManager($pdo);
    $result = $emailManager->sendTestEmail($toEmail);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Test email başarıyla gönderildi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Test email gönderilemedi. Email ayarlarını kontrol edin.']);
    }
    
} catch (Exception $e) {
    error_log('Admin send test email error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu: ' . $e->getMessage()]);
}
?>
