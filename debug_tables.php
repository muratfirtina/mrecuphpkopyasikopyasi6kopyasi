<?php
/**
 * Debug: Veritabanı tablolarını ve revize dosyalarını kontrol et
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Debug: Veritabanı Tabloları ve Revize Sistemi</h2>";

try {
    // 1. Tüm tabloları listele
    echo "<h3>1. Veritabanındaki Tüm Tablolar:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    // 2. Revisions tablosundaki response_id'lerin ne olduğunu kontrol et
    echo "<h3>2. Revisions Tablosundaki response_id Analizi:</h3>";
    $stmt = $pdo->query("
        SELECT response_id, COUNT(*) as count, 
               GROUP_CONCAT(DISTINCT status) as statuses
        FROM revisions 
        WHERE response_id IS NOT NULL
        GROUP BY response_id
        LIMIT 10
    ");
    $responseIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($responseIds) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Response ID</th><th>Kullanım Sayısı</th><th>Durumlar</th></tr>";
        foreach ($responseIds as $rid) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rid['response_id']) . "</td>";
            echo "<td>" . $rid['count'] . "</td>";
            echo "<td>" . $rid['statuses'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>response_id'si dolu olan revizyon bulunamadı.</p>";
    }
    
    // 3. file_uploads tablosunda response_id'ler var mı kontrol et
    echo "<h3>3. file_uploads Tablosunda Response ID Kontrol:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads");
    $fileUploadsColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>file_uploads sütunları:</strong></p>";
    echo "<ul>";
    foreach ($fileUploadsColumns as $col) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // 4. Örnek response_id'yi file_uploads'ta ara
    if (count($responseIds) > 0) {
        $sampleResponseId = $responseIds[0]['response_id'];
        echo "<h3>4. Örnek Response ID ile Dosya Arama:</h3>";
        echo "<p>Aranan ID: <strong>$sampleResponseId</strong></p>";
        
        $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
        $stmt->execute([$sampleResponseId]);
        $responseFile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($responseFile) {
            echo "<p style='color: green;'><strong>✅ Response dosyası file_uploads tablosunda bulundu!</strong></p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Sütun</th><th>Değer</th></tr>";
            foreach ($responseFile as $key => $value) {
                echo "<tr>";
                echo "<td><strong>$key</strong></td>";
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'><strong>❌ Response dosyası file_uploads tablosunda bulunamadı!</strong></p>";
        }
    }
    
    // 5. Diğer olası tabloları kontrol et
    echo "<h3>5. Diğer Olası Revize Tabloları:</h3>";
    $possibleTables = ['responses', 'revision_files', 'admin_responses', 'processed_files'];
    
    foreach ($possibleTables as $tableName) {
        if (in_array($tableName, $tables)) {
            echo "<p style='color: green;'><strong>✅ $tableName tablosu mevcut!</strong></p>";
            
            $stmt = $pdo->query("SHOW COLUMNS FROM $tableName");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<ul>";
            foreach ($columns as $col) {
                echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
            }
            echo "</ul>";
            
            // Birkaç örnek kayıt göster
            $stmt = $pdo->query("SELECT * FROM $tableName LIMIT 3");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($samples) > 0) {
                echo "<p><strong>Örnek kayıtlar:</strong></p>";
                echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
                $first = true;
                foreach ($samples as $sample) {
                    if ($first) {
                        echo "<tr>";
                        foreach (array_keys($sample) as $key) {
                            echo "<th>" . $key . "</th>";
                        }
                        echo "</tr>";
                        $first = false;
                    }
                    echo "<tr>";
                    foreach ($sample as $value) {
                        echo "<td>" . htmlspecialchars(substr($value ?? 'NULL', 0, 30)) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color: gray;'>❌ $tableName tablosu yok</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
