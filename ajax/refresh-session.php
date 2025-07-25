<?php
/**
 * Mr ECU - Session Refresh Handler
 * Session'ı yenileme endpoint'i
 */

// AJAX için clean output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı.']);
    exit;
}

try {
    // Session'ı yenile - sadece last activity'yi güncelle
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Oturum yenilendi.',
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log('Session refresh error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Oturum yenilenemedi.',
        'error' => $e->getMessage()
    ]);
}
?>
