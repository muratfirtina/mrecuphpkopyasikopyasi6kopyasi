<?php
/**
 * Security & Logs Test Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

echo "<h1>Security Dashboard & Logs Test</h1>";

try {
    // 1. security_logs tablosu kontrolü
    echo "<h2>1. Security Logs Tablosu Kontrolü</h2>";
    
    $table_check = $pdo->query("SHOW TABLES LIKE 'security_logs'");
    $table_exists = $table_check->fetch() ? true : false;
    
    if ($table_exists) {
        echo "<p style='color:green;'>✅ security_logs tablosu mevcut</p>";
        
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM security_logs");
        $count = $count_stmt->fetch()['count'];
        echo "<p>📊 Toplam log sayısı: <strong>$count</strong></p>";
        
        // Son 5 log
        $logs_stmt = $pdo->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 5");
        $logs = $logs_stmt->fetchAll();
        
        if (count($logs) > 0) {
            echo "<h3>Son 5 Log:</h3>";
            echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
            echo "<tr><th>ID</th><th>Event Type</th><th>IP</th><th>Created At</th></tr>";
            foreach ($logs as $log) {
                echo "<tr>";
                echo "<td>" . $log['id'] . "</td>";
                echo "<td>" . htmlspecialchars($log['event_type']) . "</td>";
                echo "<td>" . htmlspecialchars($log['ip_address']) . "</td>";
                echo "<td>" . $log['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange;'>⚠️ Tabloda log yok, örnek veriler ekleniyor...</p>";
            
            // Örnek log verileri ekle
            $sample_logs = [
                ['page_access', '192.168.1.100', 1, '{"page":"users.php","method":"GET"}', 'Mozilla/5.0'],
                ['failed_login', '192.168.1.101', null, '{"username":"admin","attempts":3}', 'Chrome/91.0'],
                ['file_upload', '192.168.1.100', 1, '{"filename":"test.ecu","size":1024}', 'Mozilla/5.0'],
                ['sql_injection_attempt', '10.0.0.50', null, '{"query":"SELECT * FROM users WHERE id=1 OR 1=1","blocked":true}', 'BadBot/1.0'],
                ['brute_force_detected', '203.0.113.1', null, '{"attempts":15,"timeframe":"5min","blocked":true}', 'Mozilla/5.0']
            ];
            
            $insert_stmt = $pdo->prepare("INSERT INTO security_logs (event_type, ip_address, user_id, details, user_agent) VALUES (?, ?, ?, ?, ?)");
            foreach ($sample_logs as $log) {
                $insert_stmt->execute($log);
            }
            
            echo "<p style='color:green;'>✅ Örnek loglar eklendi!</p>";
        }
        
    } else {
        echo "<p style='color:red;'>❌ security_logs tablosu yok</p>";
        echo "<p>Tablo otomatik olarak oluşturulacak...</p>";
    }
    
    // 2. Sayfa erişim testleri
    echo "<h2>2. Sayfa Erişim Testleri</h2>";
    
    echo "<div style='background:#f0f0f0; padding:15px; margin:10px 0;'>";
    echo "<h3>Test URL'leri:</h3>";
    echo "<p><a href='security-dashboard.php' target='_blank'>Security Dashboard</a> - Güvenlik olayları ve istatistikler</p>";
    echo "<p><a href='logs.php' target='_blank'>Logs</a> - Sistem logları</p>";
    echo "<p><a href='security-dashboard.php?filter=sql_injection_attempt' target='_blank'>Security Dashboard (SQL Injection Filter)</a></p>";
    echo "<p><a href='logs.php?filter=critical' target='_blank'>Logs (Critical Filter)</a></p>";
    echo "</div>";
    
    // 3. Fonksiyon testleri
    echo "<h2>3. Fonksiyon Testleri</h2>";
    
    // formatDate fonksiyonu test
    $test_date = date('Y-m-d H:i:s');
    echo "<p>formatDate test: " . formatDate($test_date) . "</p>";
    
    // JSON test
    $test_json = '{"test":"value","number":123}';
    $decoded = json_decode($test_json, true);
    echo "<p>JSON decode test: ";
    if (is_array($decoded)) {
        echo "✅ Başarılı";
    } else {
        echo "❌ Hatalı";
    }
    echo "</p>";
    
    // 4. Veritabanı performans testi
    echo "<h2>4. Performans Testi</h2>";
    
    $start_time = microtime(true);
    
    // Security stats query
    $stats_stmt = $pdo->query("
        SELECT 
            event_type,
            COUNT(*) as count
        FROM security_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY event_type
    ");
    $stats = $stats_stmt->fetchAll();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // milliseconds
    
    echo "<p>Stats sorgusu: " . number_format($execution_time, 2) . " ms</p>";
    echo "<p>Bulunan event türleri: " . count($stats) . "</p>";
    
    if (count($stats) > 0) {
        echo "<ul>";
        foreach ($stats as $stat) {
            echo "<li>" . htmlspecialchars($stat['event_type']) . ": " . $stat['count'] . " olay</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Test hatası: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><hr><br>";
echo "<h2>Sonuç</h2>";
echo "<p>Eğer yukarıdaki testler başarılıysa, her iki sayfa da düzgün çalışmalıdır.</p>";
echo "<p><strong>Sorun devam ediyorsa:</strong></p>";
echo "<ul>";
echo "<li>Browser cache'ini temizleyin (Ctrl+F5)</li>";
echo "<li>Error log'ları kontrol edin</li>";
echo "<li>JavaScript console'da hata var mı bakın (F12)</li>";
echo "</ul>";

echo "<br><br>";
echo "<a href='index.php'>← Admin Panel'e dön</a>";
?>
