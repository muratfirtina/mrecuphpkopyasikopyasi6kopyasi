<?php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Debug</title></head><body>";
echo "<h1>Database Debug</h1>";

try {
    // file_uploads tablosu yapısını kontrol et
    echo "<h2>file_uploads Tablosu Yapısı:</h2>";
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Sample data
    echo "<h2>Sample Data:</h2>";
    $stmt = $pdo->query("SELECT * FROM file_uploads LIMIT 3");
    $data = $stmt->fetchAll();
    
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    // file_responses tablosu var mı kontrol et
    echo "<h2>file_responses Tablosu:</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE file_responses");
        $responseColumns = $stmt->fetchAll();
        
        echo "<table border='1'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($responseColumns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "file_responses tablosu bulunamadı: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}

echo "</body></html>";
?>
