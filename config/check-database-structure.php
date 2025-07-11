<?php
/**
 * Mr ECU - Database Structure Checker
 * Veritabanı Yapısı Kontrol
 */

require_once 'database.php';

try {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Veritabanı Yapısı</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Veritabanı Yapısı Kontrolü</h1>";

    // Users tablosu yapısını kontrol et
    echo "<h2>Users Tablosu Yapısı</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $usersStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($usersStructure as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td><strong>{$column['Type']}</strong></td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Örnek user verisini kontrol et
    echo "<h2>Örnek User ID Formatı</h2>";
    $stmt = $pdo->query("SELECT id, username FROM users LIMIT 3");
    $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table'>";
    echo "<tr><th>ID</th><th>Username</th><th>ID Uzunluğu</th><th>ID Tipi</th></tr>";
    foreach ($sampleUsers as $user) {
        $idLength = strlen($user['id']);
        $idType = is_numeric($user['id']) ? 'Numeric' : 'String';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>$idLength karakter</td>";
        echo "<td>$idType</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Diğer önemli tabloları kontrol et
    echo "<h2>Diğer Tablolar</h2>";
    $tables = ['file_uploads', 'revisions', 'brands', 'models'];
    
    foreach ($tables as $tableName) {
        try {
            $stmt = $pdo->query("DESCRIBE $tableName");
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>$tableName Tablosu</h3>";
            echo "<table class='table'>";
            echo "<tr><th>Field</th><th>Type</th><th>Key</th></tr>";
            foreach ($structure as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td><strong>{$column['Type']}</strong></td>";
                echo "<td>{$column['Key']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (PDOException $e) {
            echo "<p>$tableName tablosu bulunamadı: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<div class='info'>
        <strong>Sonuç:</strong><br>
        Bu rapor ile mevcut veritabanı yapısını görebilirsiniz. 
        ID türleri arasındaki uyumsuzluk sorunu için uygun çözümü hazırlayacağız.
    </div>";
    
    echo "</div></body></html>";
    
} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Veritabanı Hatası</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .error { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='error'>❌ Veritabanı Hatası</div>
        <p><strong>Hata:</strong> " . $e->getMessage() . "</p>
    </div>
</body>
</html>";
}
?>
