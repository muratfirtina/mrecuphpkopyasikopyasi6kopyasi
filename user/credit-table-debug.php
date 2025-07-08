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
    <title>Credit Transactions Table Structure</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>";

echo "<h1>💳 Credit Transactions Tablo Yapısı</h1>";

try {
    // credit_transactions tablo yapısını göster
    $stmt = $pdo->query("DESCRIBE credit_transactions");
    $columns = $stmt->fetchAll();
    
    echo "<h2>Tablo Yapısı:</h2>";
    echo "<table>";
    echo "<tr><th>Sütun Adı</th><th>Veri Tipi</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Sample data
    $stmt = $pdo->query("SELECT * FROM credit_transactions LIMIT 3");
    $samples = $stmt->fetchAll();
    
    if (!empty($samples)) {
        echo "<h2>Örnek Veriler:</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Amount</th><th>Type</th><th>Description</th><th>Created At</th></tr>";
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>" . substr($sample['id'], 0, 8) . "...</td>";
            echo "<td>" . substr($sample['user_id'], 0, 8) . "...</td>";
            echo "<td>{$sample['amount']}</td>";
            echo "<td>{$sample['type']}</td>";
            echo "<td>{$sample['description']}</td>";
            echo "<td>{$sample['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
