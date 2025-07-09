<?php
/**
 * Revisions Tablo Yapısı Debug
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Revisions Table Debug</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 Revisions Tablo Yapısı Debug</h1>";

try {
    // Revisions tablosu yapısını göster
    echo "<h2>1. Revisions Tablo Yapısı</h2>";
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        $highlight = '';
        if ($column['Field'] === 'upload_id' && $column['Null'] === 'NO' && $column['Default'] === null) {
            $highlight = 'style="background-color: #ffe6e6;"'; // Problematik alan
        }
        
        echo "<tr $highlight>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Problem analizi
    $uploadIdCol = array_filter($columns, function($col) { return $col['Field'] === 'upload_id'; });
    $uploadIdCol = reset($uploadIdCol);
    
    if ($uploadIdCol && $uploadIdCol['Null'] === 'NO' && $uploadIdCol['Default'] === null) {
        echo "<div class='error'>
        <h3>🚨 PROBLEM TESPİT EDİLDİ</h3>
        <p><strong>upload_id</strong> alanı:</p>
        <ul>
            <li>❌ NOT NULL (zorunlu alan)</li>
            <li>❌ DEFAULT değeri yok</li>
            <li>❌ requestResponseRevision metodunda bu alana değer gönderilmiyor</li>
        </ul>
        <p><strong>Çözüm:</strong> Yanıt dosyası revize taleplerinde upload_id değeri de gönderilmeli</p>
        </div>";
    }
    
    // Mevcut revize taleplerini göster
    echo "<h2>2. Mevcut Revize Talepleri</h2>";
    $stmt = $pdo->query("SELECT * FROM revisions ORDER BY requested_at DESC LIMIT 5");
    $revisions = $stmt->fetchAll();
    
    if (!empty($revisions)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Upload ID</th><th>User ID</th><th>Status</th><th>Request Type</th><th>Requested At</th></tr>";
        foreach ($revisions as $revision) {
            echo "<tr>";
            echo "<td>" . substr($revision['id'], 0, 8) . "...</td>";
            echo "<td>" . substr($revision['upload_id'] ?? 'NULL', 0, 8) . "...</td>";
            echo "<td>" . substr($revision['user_id'], 0, 8) . "...</td>";
            echo "<td>{$revision['status']}</td>";
            echo "<td>" . ($revision['request_type'] ?? 'upload') . "</td>";
            echo "<td>{$revision['requested_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Henüz revize talebi yok</p>";
    }
    
    // File responses tablosu kontrol
    echo "<h2>3. File Responses Kontrolü</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_responses");
    $responseCount = $stmt->fetch()['count'];
    echo "<p>📁 Yanıt dosyası sayısı: <strong>$responseCount</strong></p>";
    
    if ($responseCount > 0) {
        $stmt = $pdo->query("SELECT id, upload_id, original_name FROM file_responses LIMIT 3");
        $responses = $stmt->fetchAll();
        
        echo "<h4>Sample Yanıt Dosyaları:</h4>";
        echo "<table>";
        echo "<tr><th>Response ID</th><th>Upload ID</th><th>Original Name</th></tr>";
        foreach ($responses as $response) {
            echo "<tr>";
            echo "<td>" . substr($response['id'], 0, 8) . "...</td>";
            echo "<td>" . substr($response['upload_id'], 0, 8) . "...</td>";
            echo "<td>{$response['original_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>