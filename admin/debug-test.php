<?php
// Debug test sayfası
echo "<!DOCTYPE html><html><head><title>Debug Test</title></head><body>";
echo "<h2>Debug Test</h2>";

echo "<h3>POST Verisi:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    error_log("Debug test POST verisi: " . print_r($_POST, true));
} else {
    echo "<p>POST verisi yok</p>";
}

echo "<h3>Session Verisi:</h3>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Formu:</h3>";
echo '<form method="POST" action="">
    <input type="hidden" name="test_field" value="test_value">
    <input type="text" name="test_input" value="test123">
    <button type="submit">Test Gönder</button>
</form>';

echo "<h3>Error Log Konumu:</h3>";
echo "<p>Error log: " . ini_get('error_log') . "</p>";
echo "<p>Log errors: " . ini_get('log_errors') . "</p>";

echo "</body></html>";
?>
