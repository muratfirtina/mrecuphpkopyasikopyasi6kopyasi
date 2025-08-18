<?php
/**
 * Error Log Viewer for Debugging
 */

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Error Log Viewer</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .log-entry { margin: 5px 0; padding: 5px; border-left: 3px solid #007bff; background: #f8f9fa; }
        .error { border-left-color: #dc3545; }
        .info { border-left-color: #28a745; }
        .debug { border-left-color: #ffc107; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔍 Real-time Error Logs</h1>";

// PHP error log dosyasını bul
$errorLogPaths = [
    '/Applications/MAMP/logs/php_error.log',
    ini_get('error_log'),
    '/var/log/php_error.log',
    '/tmp/php_error.log'
];

$errorLogFile = null;
foreach ($errorLogPaths as $path) {
    if (file_exists($path)) {
        $errorLogFile = $path;
        break;
    }
}

if ($errorLogFile) {
    echo "<p><strong>Error Log File:</strong> $errorLogFile</p>";
    
    // Son 50 satır
    $lines = file($errorLogFile);
    $recentLines = array_slice($lines, -50);
    
    echo "<h2>Son 50 Log Kaydı:</h2>";
    echo "<div style='background: #000; color: #0f0; padding: 10px; border-radius: 4px; overflow-x: auto; max-height: 400px; overflow-y: auto;'>";
    
    foreach (array_reverse($recentLines) as $line) {
        $line = htmlspecialchars(trim($line));
        
        if (stripos($line, 'getUserCancellations') !== false) {
            echo "<div class='debug'><strong>🎯 CANCELLATION DEBUG:</strong> $line</div>";
        } else if (stripos($line, 'error') !== false) {
            echo "<div class='error'>❌ $line</div>";
        } else if (stripos($line, 'debug') !== false) {
            echo "<div class='info'>🔍 $line</div>";
        } else {
            echo "<div>$line</div>";
        }
    }
    
    echo "</div>";
} else {
    echo "<p>❌ Error log dosyası bulunamadı.</p>";
    echo "<p>Kontrol edilen yollar:</p><ul>";
    foreach ($errorLogPaths as $path) {
        echo "<li>$path</li>";
    }
    echo "</ul>";
}

// Şimdi debug script'ini otomatik çalıştır
echo "<h2>🔄 Debug Script Sonucu:</h2>";
echo "<iframe src='debug_cancellations.php' width='100%' height='600' style='border: 1px solid #ddd; border-radius: 4px;'></iframe>";

echo "</div>";
echo "</body></html>";
?>
