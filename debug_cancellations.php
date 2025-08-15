<?php
/**
 * Hızlı Veritabanı Kontrol
 */

require_once 'config/database.php';

echo "<h1>🔍 Veritabanı İptal Talepleri Kontrolü</h1>";

try {
    // 1. Tablo varlığı kontrolü
    echo "<h2>📊 Tablo Kontrolü</h2>";
    $tables = $pdo->query("SHOW TABLES LIKE 'file_cancellations'")->fetchAll();
    if (empty($tables)) {
        echo "<div style='color: red;'>❌ file_cancellations tablosu bulunamadı!</div>";
        echo "<p><a href='sql/install_cancellation_system.php'>👉 Kurulum scriptini çalıştır</a></p>";
    } else {
        echo "<div style='color: green;'>✅ file_cancellations tablosu mevcut</div>";
        
        // 2. Tablo yapısı kontrolü
        echo "<h3>Tablo Yapısı:</h3>";
        $columns = $pdo->query("DESCRIBE file_cancellations")->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // 3. Veri kontrolü
        echo "<h3>Veri Kontrolü:</h3>";
        $count = $pdo->query("SELECT COUNT(*) FROM file_cancellations")->fetchColumn();
        echo "<p>Toplam kayıt: <strong>$count</strong></p>";
        
        if ($count > 0) {
            echo "<h3>Son 5 Kayıt:</h3>";
            $stmt = $pdo->query("SELECT * FROM file_cancellations ORDER BY requested_at DESC LIMIT 5");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr>";
            foreach (array_keys($records[0]) as $header) {
                echo "<th style='padding: 8px; background: #f0f0f0;'>$header</th>";
            }
            echo "</tr>";
            
            foreach ($records as $record) {
                echo "<tr>";
                foreach ($record as $value) {
                    $displayValue = is_null($value) ? 'NULL' : htmlspecialchars($value);
                    echo "<td style='padding: 8px; border: 1px solid #ddd;'>$displayValue</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // 4. Users tablosu ile JOIN testi
        echo "<h3>JOIN Testi:</h3>";
        $joinTest = $pdo->query("
            SELECT fc.id, fc.status, u.username, u.email 
            FROM file_cancellations fc
            LEFT JOIN users u ON fc.user_id = u.id
            LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($joinTest)) {
            echo "<p style='color: green;'>✅ Users tablosu ile JOIN çalışıyor</p>";
            echo "<pre>";
            print_r($joinTest);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ JOIN testi başarısız</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='admin/file-cancellations.php?debug=1'>👉 Admin Sayfası Debug</a></p>";
echo "<p><a href='admin/file-cancellations.php'>👉 Normal Admin Sayfası</a></p>";
?>
