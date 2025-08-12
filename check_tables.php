<?php
/**
 * Database tablo yapısını kontrol eden script
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Database Tablo Yapı Kontrolü</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .table-name { color: #0066cc; font-weight: bold; font-size: 1.2em; margin-top: 30px; }
    .error { color: red; }
    .success { color: green; }
</style>";

try {
    // Tabloları kontrol et
    $tables = ['file_uploads','users','file_responses', 'revisions', 'revision_files'];
    
    foreach ($tables as $table) {
        echo "<div class='table-name'>TABLO: $table</div>";
        
        try {
            $stmt = $pdo->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll();
            
            if ($columns) {
                echo "<table>";
                echo "<tr><th>Kolon Adı</th><th>Veri Tipi</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Extra</th></tr>";
                
                foreach ($columns as $col) {
                    echo "<tr>";
                    echo "<td>" . $col['Field'] . "</td>";
                    echo "<td>" . $col['Type'] . "</td>";
                    echo "<td>" . $col['Null'] . "</td>";
                    echo "<td>" . $col['Key'] . "</td>";
                    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . $col['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            
                
            } else {
                echo "<p class='error'>Kolon bilgisi alınamadı</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<br><br><div class='table-name'>TABLO SAYILARI</div>";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            echo "<p>$table: <strong>$count</strong> kayıt</p>";
        } catch (Exception $e) {
            echo "<p class='error'>$table: Hata - " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Genel Hata: " . $e->getMessage() . "</p>";
}

?>
