<?php
/**
 * AJAX Test Endpoint
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Output buffer başlat
ob_start();

try {
    header('Content-Type: application/json');
    
    // Config'i test et
    require_once '../../config/config.php';
    error_log('TEST: Config loaded successfully');
    
    // Database'i test et
    require_once '../../config/database.php';
    error_log('TEST: Database loaded successfully');
    
    // Functions'ı test et
    require_once '../../includes/functions.php';
    error_log('TEST: Functions loaded successfully');
    
    // Buffer'ı temizle
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log('TEST Warning: Unexpected output: ' . $output);
    }
    
    // Test verileri
    $result = [
        'success' => true,
        'message' => 'Test successful',
        'data' => [
            'config_loaded' => defined('SITE_NAME'),
            'pdo_available' => isset($pdo),
            'functions_loaded' => function_exists('sanitize'),
            'admin_check' => function_exists('isAdmin'),
            'session_status' => session_status()
        ]
    ];
    
    error_log('TEST: All checks passed');
    echo json_encode($result);
    
} catch (Exception $e) {
    ob_clean();
    error_log('TEST Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Test failed: ' . $e->getMessage()
    ]);
}
?>
