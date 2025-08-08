<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Engines tablosunu kontrol et
echo "<h2>Engines Tablosu Yapısı</h2>";

try {
    $stmt = $pdo->query("DESCRIBE engines");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Örnek data da gösterelim
    echo "<h3>Örnek Engines Verileri (ilk 5 kayıt):</h3>";
    $stmt = $pdo->query("SELECT * FROM engines LIMIT 5");
    $engines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($engines)) {
        echo "<table border='1'>";
        $headers = array_keys($engines[0]);
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th>{$header}</th>";
        }
        echo "</tr>";
        foreach ($engines as $engine) {
            echo "<tr>";
            foreach ($engine as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "<br><br>";
}

// Ayrıca file_uploads tablosunda engine_id alanı var mı kontrol edelim
echo "<h3>file_uploads Tablosunda Engine İlişkisi:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $engineFields = array_filter($columns, function($col) {
        return strpos(strtolower($col['Field']), 'engine') !== false;
    });
    
    if (!empty($engineFields)) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($engineFields as $field) {
            echo "<tr>";
            echo "<td>{$field['Field']}</td>";
            echo "<td>{$field['Type']}</td>";
            echo "<td>{$field['Null']}</td>";
            echo "<td>{$field['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "file_uploads tablosunda engine ile ilgili alan bulunamadı.";
    }
} catch (Exception $e) {
    echo "file_uploads kontrol hatası: " . $e->getMessage();
}
?>
