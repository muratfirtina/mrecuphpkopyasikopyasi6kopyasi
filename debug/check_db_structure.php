<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Tablo yapılarını kontrol et
echo "<h2>Veritabanı Tablo Yapıları</h2>";

$tables = ['series', 'devices', 'ecus', 'engines'];

foreach ($tables as $table) {
    echo "<h3>{$table} tablosu:</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE {$table}");
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
    } catch (Exception $e) {
        echo "Hata: " . $e->getMessage() . "<br><br>";
    }
}
?>
