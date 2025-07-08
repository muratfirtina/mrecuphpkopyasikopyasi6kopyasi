<?php
// Log viewer sayfası
echo "<!DOCTYPE html><html><head><title>Log Viewer</title></head><body>";
echo "<h2>PHP Error Log Viewer</h2>";

// MAMP log dosyası yolları
$logPaths = [
    '/Applications/MAMP/logs/php_error.log',
    '/Applications/MAMP/logs/apache_error.log', 
    ini_get('error_log'),
    'error.log',
    '../error.log'
];

foreach ($logPaths as $logPath) {
    echo "<h3>Log dosyası: $logPath</h3>";
    
    if (file_exists($logPath)) {
        echo "<p style='color: green;'>✅ Dosya mevcut</p>";
        
        // Son 20 satırı göster (memory safe)
        if (filesize($logPath) > 10 * 1024 * 1024) { // 10MB'dan büyükse
            echo "<p style='color: orange;'>⚠️ Log dosyası çok büyük (" . round(filesize($logPath) / 1024 / 1024, 2) . "MB). Son kısmı gösteriliyor...</p>";
            
            // Dosyanın son kısmını oku
            $handle = fopen($logPath, 'r');
            if ($handle) {
                fseek($handle, -50000, SEEK_END); // Son 50KB
                $content = fread($handle, 50000);
                fclose($handle);
                
                $lines = explode("\n", $content);
                $lastLines = array_slice($lines, -30); // Son 30 satır
            } else {
                $lastLines = ['Log dosyası okunamadı.'];
            }
        } else {
            $lines = file($logPath);
            if ($lines && count($lines) > 0) {
                $lastLines = array_slice($lines, -30);
            } else {
                $lastLines = ['Log dosyası boş.'];
            }
        }
        
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; height: 300px; overflow-y: scroll;'>";
        foreach ($lastLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Sadece credits.php ile ilgili logları vurgula
            if (stripos($line, 'credits.php') !== false || stripos($line, 'Credits.php') !== false) {
                echo "<strong style='color: blue;'>" . htmlspecialchars($line) . "</strong>\n";
            } elseif (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                echo "<strong style='color: red;'>" . htmlspecialchars($line) . "</strong>\n";
            } else {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Dosya bulunamadı</p>";
    }
}

echo "<h3>PHP Ayarları</h3>";
echo "<p>Log errors: " . ini_get('log_errors') . "</p>";
echo "<p>Error log: " . ini_get('error_log') . "</p>";
echo "<p>Display errors: " . ini_get('display_errors') . "</p>";

echo "</body></html>";
?>
