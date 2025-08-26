<?php
/**
 * MAMP Error Log Reader
 */

echo "<h1>🔍 MAMP Error Log Analysis</h1>";
echo "<hr>";

// Possible MAMP log locations
$possibleLogPaths = [
    '/Applications/MAMP/logs/apache_error.log',
    '/Applications/MAMP/logs/php_error.log', 
    '/Applications/MAMP/logs/mysql_error.log',
    '/var/log/apache2/error.log',
    '/tmp/php_error.log'
];

echo "<h2>📁 Log Dosyası Arama:</h2>";

$foundLogs = [];
foreach ($possibleLogPaths as $logPath) {
    if (file_exists($logPath) && is_readable($logPath)) {
        $foundLogs[] = $logPath;
        echo "✅ Bulunan log: <code>$logPath</code><br>";
        
        // Son 10 satırı göster
        $logContent = file_get_contents($logPath);
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -10);
        
        echo "<h4>Son 10 hata (son önce):</h4>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
        foreach (array_reverse($lastLines) as $line) {
            if (trim($line)) {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre>";
        
    } else {
        echo "❌ Erişilemiyor: <code>$logPath</code><br>";
    }
}

if (empty($foundLogs)) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24;'>";
    echo "<h3>⚠️ Log Dosyası Bulunamadı</h3>";
    echo "<p>Hatayı bulmak için manual kontrol gerekiyor:</p>";
    echo "<ol>";
    echo "<li>MAMP'ı açın</li>";
    echo "<li>'Open WebStart page' tıklayın</li>";  
    echo "<li>'Tools' > 'Logs' menüsüne gidin</li>";
    echo "<li>Apache Error Log'u kontrol edin</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>🛠️ Alternatif Çözümler:</h2>";
echo "<ol>";
echo "<li><strong>MAMP Yeniden Başlat:</strong> MAMP'ı tamamen kapatıp açın</li>";
echo "<li><strong>Farklı Port:</strong> MAMP'ta farklı port numarası deneyin</li>";
echo "<li><strong>PHP Version:</strong> MAMP'ta farklı PHP versiyonu seçin</li>";
echo "<li><strong>Cache Temizle:</strong> Tarayıcı cache'ini temizleyin</li>";
echo "</ol>";

// PHP ayarlarını göster
echo "<h2>⚙️ PHP Ayarları:</h2>";
echo "<table style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='border: 1px solid #ddd; padding: 8px;'>Ayar</th><th style='border: 1px solid #ddd; padding: 8px;'>Değer</th></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>display_errors</td><td style='border: 1px solid #ddd; padding: 8px;'>" . ini_get('display_errors') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>log_errors</td><td style='border: 1px solid #ddd; padding: 8px;'>" . ini_get('log_errors') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>error_log</td><td style='border: 1px solid #ddd; padding: 8px;'>" . ini_get('error_log') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>memory_limit</td><td style='border: 1px solid #ddd; padding: 8px;'>" . ini_get('memory_limit') . "</td></tr>";
echo "</table>";
?>
