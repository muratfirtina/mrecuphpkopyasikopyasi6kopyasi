<?php
/**
 * File Uploads Tablosu Yapısını Kontrol Et
 */

require_once __DIR__ . '/../config/database.php';

echo "<h2>File Uploads Tablo Yapısı</h2>";

try {
    // Tablo yapısını kontrol et
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        // Status kolonunu özellikle vurgula
        if ($column['Field'] === 'status') {
            echo "<tr style='background: #fff3e0;'>";
            echo "<td colspan='6'>";
            echo "<strong>Status Kolonu Detayı:</strong><br>";
            echo "Type: <code>" . htmlspecialchars($column['Type']) . "</code><br>";
            
            // Eğer ENUM ise değerleri parse et
            if (preg_match("/^enum\((.+)\)$/i", $column['Type'], $matches)) {
                $values = array_map(function($value) {
                    return trim($value, "'");
                }, explode(',', $matches[1]));
                
                echo "İzin verilen değerler: <code>" . implode(', ', $values) . "</code><br>";
                
                // 'cancelled' var mı kontrol et
                if (in_array('cancelled', $values)) {
                    echo "<span style='color: green;'>✅ 'cancelled' değeri tanımlı</span>";
                } else {
                    echo "<span style='color: red;'>❌ 'cancelled' değeri tanımlı değil!</span><br>";
                    echo "<strong>Çözüm gerekli:</strong> ENUM'a 'cancelled' değeri eklenmelidir.";
                }
            }
            echo "</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // is_cancelled kolonunu kontrol et
    echo "<hr><h3>İptal Kolonları:</h3>";
    $stmt = $pdo->query("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'file_uploads'
        AND (COLUMN_NAME = 'status' OR COLUMN_NAME = 'is_cancelled' OR COLUMN_NAME LIKE '%cancel%')
    ");
    
    $cancelColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f5f5f5;'><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
    foreach ($cancelColumns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['COLUMN_NAME']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['COLUMN_TYPE']) . "</td>";
        echo "<td>" . htmlspecialchars($col['IS_NULLABLE']) . "</td>";
        echo "<td>" . htmlspecialchars($col['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mevcut status değerlerini kontrol et
    echo "<hr><h3>Mevcut Status Değerleri:</h3>";
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM file_uploads 
        GROUP BY status 
        ORDER BY count DESC
    ");
    
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f5f5f5;'><th>Status Değeri</th><th>Adet</th></tr>";
    foreach ($statusCounts as $status) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($status['status'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($status['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // İptal edilmiş dosyaları göster
    echo "<hr><h3>İptal Edilmiş Dosyalar:</h3>";
    $stmt = $pdo->query("
        SELECT id, original_name, status, is_cancelled, cancelled_at 
        FROM file_uploads 
        WHERE is_cancelled = 1 
        LIMIT 10
    ");
    
    $cancelledFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cancelledFiles) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f5f5f5;'><th>ID</th><th>Dosya Adı</th><th>Status</th><th>is_cancelled</th><th>İptal Tarihi</th></tr>";
        foreach ($cancelledFiles as $file) {
            $statusColor = ($file['status'] === 'cancelled') ? 'green' : 'red';
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($file['id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td style='color: {$statusColor}; font-weight: bold;'>" . htmlspecialchars($file['status']) . "</td>";
            echo "<td>" . htmlspecialchars($file['is_cancelled']) . "</td>";
            echo "<td>" . htmlspecialchars($file['cancelled_at'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>İptal edilmiş dosya bulunamadı.</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #ffebee;'>";
    echo "❌ Hata: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
    }
    h2, h3 {
        color: #1976d2;
    }
    table {
        font-size: 14px;
        margin-top: 10px;
    }
    th {
        text-align: left;
    }
    code {
        background: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
    }
</style>
