<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    die('Lütfen giriş yapın');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Table Structure Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>";

echo "<h1>📋 file_uploads Tablo Yapısı</h1>";

try {
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Sütun Adı</th><th>Veri Tipi</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>📊 Mevcut Veri Örneği</h2>";
    $stmt = $pdo->query("SELECT * FROM file_uploads LIMIT 1");
    $sample = $stmt->fetch();
    
    if ($sample) {
        echo "<table>";
        echo "<tr><th>Sütun</th><th>Değer</th></tr>";
        foreach ($sample as $key => $value) {
            if (!is_numeric($key)) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
        }
        echo "</table>";
    } else {
        echo "<p>Tabloda veri yok.</p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
