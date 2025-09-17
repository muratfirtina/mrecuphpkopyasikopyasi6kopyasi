<?php
/**
 * Error Log Viewer
 */

// Memory limit artır
ini_set('memory_limit', '256M');

echo "<h2>PHP Error Logs</h2>";

// MAMP error log paths
$logPaths = [
    '/Applications/MAMP/logs/php_error.log',
    '/Applications/MAMP/logs/apache_error.log',
    ini_get('error_log'),
    dirname(__FILE__) . '/error.log',
    dirname(__FILE__) . '/../error.log'
];

echo "<h3>Log File Locations:</h3>";
foreach ($logPaths as $path) {
    if ($path && file_exists($path)) {
        echo "<p style='color: green;'><strong>Found:</strong> " . htmlspecialchars($path) . "</p>";
        
        // Son 50 satırı göster - optimize edilmiş okuma
        $command = "tail -50 " . escapeshellarg($path);
        $lastLines = explode("\n", shell_exec($command));
        
        if ($lastLines && count($lastLines) > 0) {
            echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; max-height: 300px; overflow-y: scroll; font-family: monospace; font-size: 12px;'>";
            echo "<strong>Last 50 lines (Optimized):</strong><br>";
            foreach ($lastLines as $line) {
                if (empty(trim($line))) continue;
                
                // Highlight slider/upload/session related errors
                if (stripos($line, 'slider') !== false || 
                    stripos($line, 'upload') !== false || 
                    stripos($line, 'ajax') !== false ||
                    stripos($line, 'session') !== false ||
                    stripos($line, 'authorization') !== false) {
                    echo "<span style='background: yellow;'>" . htmlspecialchars($line) . "</span><br>";
                } else {
                    echo htmlspecialchars($line) . "<br>";
                }
            }
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'><strong>Not found:</strong> " . htmlspecialchars($path ?: 'empty') . "</p>";
    }
}

// PHP error reporting durumu
echo "<h3>PHP Error Reporting:</h3>";
echo "<p><strong>Error Reporting Level:</strong> " . error_reporting() . "</p>";
echo "<p><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
echo "<p><strong>Log Errors:</strong> " . (ini_get('log_errors') ? 'On' : 'Off') . "</p>";
echo "<p><strong>Error Log Path:</strong> " . ini_get('error_log') . "</p>";

// Custom error logging test
echo "<h3>Custom Error Log Test:</h3>";
error_log("TEST: Upload debug error log test - " . date('Y-m-d H:i:s'));
echo "<p>Test error logged. Check logs above.</p>";

// Session debugging
echo "<h3>Session Info:</h3>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Recent uploaded files
echo "<h3>Recent Uploaded Files:</h3>";
$uploadDir = '../assets/images/';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $recentFiles = [];
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_file($uploadDir . $file)) {
            $recentFiles[$file] = filemtime($uploadDir . $file);
        }
    }
    
    arsort($recentFiles); // Son değişikliklere göre sırala
    $recentFiles = array_slice($recentFiles, 0, 10, true); // Son 10 dosya
    
    if ($recentFiles) {
        echo "<ul>";
        foreach ($recentFiles as $file => $time) {
            echo "<li><strong>" . htmlspecialchars($file) . "</strong> - " . date('Y-m-d H:i:s', $time) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No uploaded files found.</p>";
    }
} else {
    echo "<p style='color: red;'>Upload directory not found!</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
    h2, h3 { color: #333; }
    .highlight { background: yellow; }
</style>
