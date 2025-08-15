<?php
/**
 * Debug Credit Transactions Table Structure
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Credit Transactions Table Debug</h1>";

try {
    // Tablo yapısını incele
    echo "<h2>Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE credit_transactions");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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
    
    // Mevcut transaction_type değerlerini kontrol et
    echo "<h2>Existing Transaction Types</h2>";
    $stmt = $pdo->query("SELECT DISTINCT transaction_type FROM credit_transactions ORDER BY transaction_type");
    $existingTypes = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($existingTypes as $type) {
        echo "<li><strong>" . htmlspecialchars($type['transaction_type']) . "</strong></li>";
    }
    echo "</ul>";
    
    // ENUM değerlerini kontrol et (eğer ENUM ise)
    $stmt = $pdo->query("SHOW COLUMNS FROM credit_transactions LIKE 'transaction_type'");
    $columnInfo = $stmt->fetch();
    echo "<h2>Transaction Type Column Details</h2>";
    echo "<pre>";
    print_r($columnInfo);
    echo "</pre>";
    
    // Type sütununun değerlerini de kontrol et
    echo "<h2>Existing Type Values</h2>";
    $stmt = $pdo->query("SELECT DISTINCT type FROM credit_transactions ORDER BY type");
    $existingTypeValues = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($existingTypeValues as $type) {
        echo "<li><strong>" . htmlspecialchars($type['type']) . "</strong></li>";
    }
    echo "</ul>";
    
    // Son 10 kaydı göster
    echo "<h2>Recent Records (Last 10)</h2>";
    $stmt = $pdo->query("SELECT * FROM credit_transactions ORDER BY created_at DESC LIMIT 10");
    $recentRecords = $stmt->fetchAll();
    
    if (count($recentRecords) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        foreach (array_keys($recentRecords[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($recentRecords as $record) {
            echo "<tr>";
            foreach ($record as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No records found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
