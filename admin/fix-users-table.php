<?php
/**
 * Users Tablosuna last_login Sütunu Ekleme
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Users Tablosu Güncelleme</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔧 Users Tablosu Güncelleme</h1>";

try {
    // 1. last_login sütunu var mı kontrol et
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    $hasLastLogin = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'last_login') {
            $hasLastLogin = true;
            break;
        }
    }
    
    if (!$hasLastLogin) {
        echo "<div class='info'>last_login sütunu ekleniyor...</div>";
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER updated_at");
        echo "<div class='success'>✅ last_login sütunu başarıyla eklendi!</div>";
    } else {
        echo "<div class='success'>✅ last_login sütunu zaten mevcut</div>";
    }
    
    // 2. Mevcut kullanıcılara varsayılan last_login değeri ata
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE last_login IS NULL");
    $nullCount = $stmt->fetch()['count'];
    
    if ($nullCount > 0) {
        echo "<div class='info'>$nullCount kullanıcı için last_login değeri ayarlanıyor...</div>";
        $pdo->exec("UPDATE users SET last_login = created_at WHERE last_login IS NULL");
        echo "<div class='success'>✅ Varsayılan last_login değerleri ayarlandı!</div>";
    }
    
    // 3. Güncellenmiş tablo yapısını göster
    echo "<h2>📋 Güncellenmiş Users Tablosu</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        $highlight = $column['Field'] === 'last_login' ? 'style="background: yellow;"' : '';
        echo "<tr $highlight>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<p><a href='debug-database.php'>🔍 Database debug sayfasına dön</a></p>";
echo "<p><a href='reports.php'>📊 Reports sayfasını test et</a></p>";
echo "</body></html>";
?>
