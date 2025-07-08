<?php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html><html><head><title>Database Debug</title></head><body>";
echo "<h2>Database Structure Debug</h2>";

try {
    echo "<h3>System Logs Tablosu Kontrolü:</h3>";
    
    // System_logs tablosunu kontrol et
    $checkTable = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    if ($checkTable->rowCount() > 0) {
        echo "<p style='color: green;'>✅ system_logs tablosu mevcut</p>";
        
        // Tablo yapısını göster
        $structure = $pdo->query("DESCRIBE system_logs");
        echo "<h4>Tablo Yapısı:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ system_logs tablosu bulunamadı</p>";
        echo "<h4>Tablo Oluşturuluyor...</h4>";
        
        $createTable = "
        CREATE TABLE system_logs (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            action VARCHAR(255) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        if ($pdo->exec($createTable)) {
            echo "<p style='color: green;'>✅ system_logs tablosu oluşturuldu!</p>";
        } else {
            echo "<p style='color: red;'>❌ Tablo oluşturulamadı!</p>";
        }
    }
    
    echo "<h3>Test Log Kaydı:</h3>";
    
    // Test log kaydı
    $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO system_logs (id, user_id, action, description, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $testResult = $stmt->execute([
        $logId,
        '6b22b5fc-a2c9-422e-9f94-4ed4c8e58030', // Admin user ID
        'test_action',
        'Test log kaydı - Database debug',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    if ($testResult) {
        echo "<p style='color: green;'>✅ Test log kaydı başarılı!</p>";
        echo "<p>Log ID: " . $logId . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Test log kaydı başarısız!</p>";
        $errorInfo = $stmt->errorInfo();
        echo "<pre>" . print_r($errorInfo, true) . "</pre>";
    }
    
    echo "<h3>Son 5 Log Kaydı:</h3>";
    $logs = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5");
    if ($logs) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Action</th><th>Description</th><th>Created At</th></tr>";
        while ($log = $logs->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($log['id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars(substr($log['user_id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars($log['action']) . "</td>";
            echo "<td>" . htmlspecialchars($log['description']) . "</td>";
            echo "<td>" . htmlspecialchars($log['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Links:</h3>";
echo "<a href='credits.php'>Credits.php'ye git</a><br>";
echo "<a href='log-viewer.php'>Log Viewer</a><br>";
echo "<a href='database-debug.php'>Bu sayfayı yenile</a>";

echo "</body></html>";
?>
