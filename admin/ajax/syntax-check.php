<?php
// PHP Syntax Check for update-product.php
$file = '/Applications/MAMP/htdocs/mrecutuning/admin/ajax/update-product.php';

error_log('=== PHP SYNTAX CHECK STARTED ===');

// PHP syntax kontrolü
$output = array();
$return_var = 0;
exec("php -l $file 2>&1", $output, $return_var);

error_log('Syntax check output: ' . implode('\n', $output));
error_log('Return code: ' . $return_var);

header('Content-Type: application/json');
echo json_encode([
    'success' => $return_var === 0,
    'output' => $output,
    'return_code' => $return_var,
    'file' => $file
]);
?>
