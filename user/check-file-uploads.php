<?php
/**
 * File Uploads Tablo Yapısı Kontrolü
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>File Uploads Tablo Kontrolü</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>📁 File Uploads Tablo Kontrol</h1>";

try {
    // 1. Tablo yapısını kontrol et
    echo "<h2>1. Tablo Yapısı</h2>";
    
    $structure = $pdo->query("DESCRIBE file_uploads")->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Örnek veri kontrol
    echo "<h2>2. Örnek Veri</h2>";
    
    $sample = $pdo->query("SELECT * FROM file_uploads LIMIT 3")->fetchAll();
    
    if (!empty($sample)) {
        echo "<table>";
        $first = true;
        foreach ($sample as $row) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                }
                echo "</tr>";
                $first = false;
            }
            
            echo "<tr>";
            foreach ($row as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>Hiç veri bulunamadı</div>";
    }
    
    // 3. Sütun adlarını listele
    echo "<h2>3. Dosya Adı Sütunları</h2>";
    $fileNameColumns = [];
    foreach ($structure as $column) {
        $fieldName = $column['Field'];
        if (strpos($fieldName, 'name') !== false || strpos($fieldName, 'file') !== false) {
            $fileNameColumns[] = $fieldName;
        }
    }
    
    echo "<div class='info'>Dosya adıyla ilgili sütunlar:</div>";
    echo "<ul>";
    foreach ($fileNameColumns as $col) {
        echo "<li><strong>$col</strong></li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<a href='transactions.php'>🔄 Transactions Sayfasına Git</a>";
echo "</body></html>";
?>