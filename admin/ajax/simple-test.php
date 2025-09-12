<?php
error_log('=== SIMPLE TEST FILE ACCESSED ===');
header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'message' => 'Simple test file is working',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
