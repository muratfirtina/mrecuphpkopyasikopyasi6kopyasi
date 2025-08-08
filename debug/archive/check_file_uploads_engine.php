<?php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h2>file_uploads Tablosunda Engine İlişkisi Kontrolü</h2>";

try {
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>file_uploads Tablosundaki Tüm Alanlar:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $column) {
        // Engine ile ilgili alanları vurgula
        $highlight = (strpos(strtolower($column['Field']), 'engine') !== false) ? 'style="background-color: yellow;"' : '';
        echo "<tr {$highlight}>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Engine alanı var mı kontrol et
    $engineFields = array_filter($columns, function($col) {
        return strpos(strtolower($col['Field']), 'engine') !== false;
    });
    
    if (!empty($engineFields)) {
        echo "<h3>✅ Bulunan Engine Alanları:</h3>";
        foreach ($engineFields as $field) {
            echo "<strong>" . $field['Field'] . "</strong> (" . $field['Type'] . ")<br>";
        }
    } else {
        echo "<h3>❌ Engine ile ilgili alan bulunamadı</h3>";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
