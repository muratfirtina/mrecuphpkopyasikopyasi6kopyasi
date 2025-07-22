<?php
/**
 * Notification System Database Check
 */

require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Bildirim Sistemi Kontrol</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Bildirim Sistemi Veritabanı Kontrol</h1>";

try {
    // Bildirimler tablosunu kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $notificationsExists = $stmt->rowCount() > 0;
    
    // Email şablonları tablosunu kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_templates'");
    $emailTemplatesExists = $stmt->rowCount() > 0;
    
    // Email kuyruğu tablosunu kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_queue'");
    $emailQueueExists = $stmt->rowCount() > 0;
    
    echo "<h2>Tablo Durumu:</h2>";
    echo "<table>";
    echo "<tr><th>Tablo Adı</th><th>Durum</th><th>Açıklama</th></tr>";
    
    echo "<tr><td>notifications</td><td>" . 
         ($notificationsExists ? "<span class='success'>✅ Mevcut</span>" : "<span class='error'>❌ Eksik</span>") . 
         "</td><td>Ana bildirimler tablosu</td></tr>";
         
    echo "<tr><td>email_templates</td><td>" . 
         ($emailTemplatesExists ? "<span class='success'>✅ Mevcut</span>" : "<span class='error'>❌ Eksik</span>") . 
         "</td><td>Email şablonları</td></tr>";
         
    echo "<tr><td>email_queue</td><td>" . 
         ($emailQueueExists ? "<span class='success'>✅ Mevcut</span>" : "<span class='error'>❌ Eksik</span>") . 
         "</td><td>Email gönderim kuyruğu</td></tr>";
         
    echo "</table>";
    
    $allTablesExist = $notificationsExists && $emailTemplatesExists && $emailQueueExists;
    
    if ($allTablesExist) {
        echo "<div class='info'>
            <span class='success'><strong>✅ Tüm tablolar mevcut!</strong></span><br>
            Bildirim sistemi kullanıma hazır.
        </div>";
        
        // Tablo detaylarını göster
        if ($notificationsExists) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
            $notificationCount = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
            $unreadCount = $stmt->fetchColumn();
            
            echo "<h3>Bildirim İstatistikleri:</h3>";
            echo "<ul>";
            echo "<li>Toplam bildirim: <strong>{$notificationCount}</strong></li>";
            echo "<li>Okunmamış: <strong>{$unreadCount}</strong></li>";
            echo "</ul>";
        }
        
        if ($emailTemplatesExists) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_templates WHERE is_active = 1");
            $templateCount = $stmt->fetchColumn();
            
            echo "<h3>Email Şablonları:</h3>";
            echo "<ul><li>Aktif şablon sayısı: <strong>{$templateCount}</strong></li></ul>";
        }
        
        if ($emailQueueExists) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'");
            $pendingEmailCount = $stmt->fetchColumn();
            
            echo "<h3>Email Kuyruğu:</h3>";
            echo "<ul><li>Bekleyen email: <strong>{$pendingEmailCount}</strong></li></ul>";
        }
        
    } else {
        echo "<div class='info'>
            <span class='error'><strong>❌ Bazı tablolar eksik!</strong></span><br>
            Bildirim sistemini kullanabilmek için kurulum scriptini çalıştırın.
        </div>";
        
        echo "<a href='install-notifications.php' class='btn btn-success'>Bildirim Sistemini Kur</a>";
    }
    
    echo "<br><br>";
    echo "<a href='../admin/notifications.php' class='btn'>Bildirimler Sayfası</a>";
    echo "<a href='../admin/' class='btn'>Admin Panel</a>";
    
} catch(PDOException $e) {
    echo "<div class='info'>
        <span class='error'><strong>❌ Veritabanı Hatası:</strong></span><br>
        " . $e->getMessage() . "
    </div>";
}

echo "    </div>
</body>
</html>";
?>
