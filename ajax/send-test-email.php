<?php
/**
 * Mr ECU - Generic Send Test Email Handler
 */

// AJAX için clean output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü - sadece admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    // Admin için send test email handler'ını çağır
    if (file_exists('../admin/ajax/send_test_email.php')) {
        include '../admin/ajax/send_test_email.php';
    } else {
        echo json_encode(['success' => false, 'message' => 'Test email fonksiyonu bulunamadı.']);
    }
} catch (Exception $e) {
    error_log('Generic send test email error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
