<?php
/**
 * Notification Table Type Fix - ENUM'ı VARCHAR'a çevir
 */

require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Notification Table Update</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Notification Sistemi Güncelleme</h1>";

try {
    // Mevcut tabloyu kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() === 0) {
        echo "<div class='error'>❌ Notifications tablosu bulunamadı. Önce kurulum yapın.</div>";
        echo "<a href='install-notifications.php' class='btn'>Kurulum Yap</a>";
        echo "</div></body></html>";
        exit;
    }
    
    // Mevcut table structure'ı kontrol et
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $typeColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'type') {
            $typeColumn = $column;
            break;
        }
    }
    
    echo "<h2>Mevcut Tablo Yapısı:</h2>";
    echo "<div class='info'>";
    if ($typeColumn) {
        echo "<strong>Type sütunu:</strong> " . $typeColumn['Type'] . "<br>";
        echo "<strong>Null:</strong> " . $typeColumn['Null'] . "<br>";
        echo "<strong>Default:</strong> " . ($typeColumn['Default'] ?: 'NULL');
    }
    echo "</div>";
    
    // ENUM ise VARCHAR'a çevir
    if (strpos($typeColumn['Type'], 'enum') !== false) {
        echo "<h2>Güncelleme İşlemleri:</h2>";
        
        // 1. Mevcut verileri yedekle
        $stmt = $pdo->query("SELECT DISTINCT type FROM notifications");
        $existingTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<div class='info'>";
        echo "<strong>Mevcut bildirim türleri:</strong><br>";
        foreach ($existingTypes as $type) {
            echo "- " . htmlspecialchars($type) . "<br>";
        }
        echo "</div>";
        
        // 2. Type sütununu VARCHAR olarak değiştir
        $pdo->exec("ALTER TABLE notifications MODIFY COLUMN type VARCHAR(50) NOT NULL");
        echo "<div class='success'>✅ Type sütunu VARCHAR(50) olarak güncellendi</div>";
        
        // 3. Yeni tabloyu kontrol et
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'type') {
                echo "<div class='success'>✅ Yeni type sütunu: " . $column['Type'] . "</div>";
                break;
            }
        }
        
    } else {
        echo "<div class='success'>✅ Type sütunu zaten VARCHAR formatında</div>";
    }
    
    // Test verileri oluştur
    echo "<h2>Test Bildirimleri:</h2>";
    echo "<div class='info'>Artık tüm bildirim türleri destekleniyor.</div>";
    
    echo "<div>";
    echo "<a href='../admin/create-test-notifications.php' class='btn'>Test Bildirimleri Oluştur</a>";
    echo "<a href='../admin/notifications.php' class='btn'>Bildirimler Sayfası</a>";
    echo "<a href='check-notification-system.php' class='btn'>Sistem Durumu</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "    </div>
</body>
</html>";
?>
