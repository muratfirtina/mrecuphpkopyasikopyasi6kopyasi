<?php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Database Kolon Kontrolü</h1>";

// file_uploads tablosu kolonları
echo "<h2>file_uploads Tablosu Kolonları:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Kolon Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = '';
        if (strpos($col['Field'], 'creat') !== false || strpos($col['Field'], 'date') !== false || strpos($col['Field'], 'time') !== false) {
            $highlight = 'style="background-color: yellow;"';
        }
        echo "<tr $highlight>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}

// revisions tablosu kolonları da kontrol edelim
echo "<h2>revisions Tablosu Kolonları:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Kolon Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = '';
        if (strpos($col['Field'], 'creat') !== false || strpos($col['Field'], 'date') !== false || strpos($col['Field'], 'time') !== false) {
            $highlight = 'style="background-color: yellow;"';
        }
        echo "<tr $highlight>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}

?>

<style>
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
