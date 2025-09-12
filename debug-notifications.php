<?php
/**
 * Bildirim Sistemi Debug Script
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/NotificationManager.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Bildirim Sistemi Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .code { background: #f4f4f4; padding: 10px; border-left: 3px solid #007bff; font-family: monospace; }
    </style>
</head>
<body>";

echo "<h1>🔍 Bildirim Sistemi Debug</h1>";
echo "<p>Admin kullanıcı: <strong>{$_SESSION['username']}</strong> (ID: {$_SESSION['user_id']})</p>";

// 1. Notifications tablosu kontrol
echo "<div class='section'>";
echo "<h2>1. Notifications Tablosu Kontrolü</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p class='success'>✅ notifications tablosu mevcut</p>";
        
        // Tablo yapısını göster
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Tablo Yapısı:</h3>";
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Toplam kayıt sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
        $total = $stmt->fetch()['total'];
        echo "<p>Toplam bildirim sayısı: <strong>$total</strong></p>";
        
        // Okunmamış bildirim sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as unread FROM notifications WHERE is_read = FALSE");
        $unread = $stmt->fetch()['unread'];
        echo "<p>Okunmamış bildirim sayısı: <strong>$unread</strong></p>";
        
        // Admin kullanıcıları için okunmamış bildirimler
        $stmt = $pdo->prepare("
            SELECT u.username, u.role, COUNT(n.id) as unread_count 
            FROM users u 
            LEFT JOIN notifications n ON u.id = n.user_id AND n.is_read = FALSE 
            WHERE u.role = 'admin' AND u.status = 'active'
            GROUP BY u.id, u.username, u.role
        ");
        $stmt->execute();
        $adminNotifications = $stmt->fetchAll();
        
        echo "<h3>Admin Kullanıcıları için Okunmamış Bildirimler:</h3>";
        echo "<table>";
        echo "<tr><th>Admin Kullanıcı</th><th>Rol</th><th>Okunmamış Bildirim</th></tr>";
        foreach ($adminNotifications as $admin) {
            $highlight = ($admin['username'] === $_SESSION['username']) ? ' style="background-color: #fff3cd;"' : '';
            echo "<tr$highlight>";
            echo "<td>{$admin['username']}</td>";
            echo "<td>{$admin['role']}</td>";
            echo "<td>{$admin['unread_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p class='error'>❌ notifications tablosu mevcut değil!</p>";
        echo "<div class='code'>";
        echo "Notifications tablosunu oluşturmak için şu SQL'i çalıştırın:<br><br>";
        echo "CREATE TABLE notifications (<br>";
        echo "&nbsp;&nbsp;id CHAR(36) PRIMARY KEY,<br>";
        echo "&nbsp;&nbsp;user_id CHAR(36) NOT NULL,<br>";
        echo "&nbsp;&nbsp;type VARCHAR(50) NOT NULL,<br>";
        echo "&nbsp;&nbsp;title VARCHAR(255) NOT NULL,<br>";
        echo "&nbsp;&nbsp;message TEXT NOT NULL,<br>";
        echo "&nbsp;&nbsp;related_id CHAR(36) NULL,<br>";
        echo "&nbsp;&nbsp;related_type VARCHAR(50) NULL,<br>";
        echo "&nbsp;&nbsp;action_url VARCHAR(500) NULL,<br>";
        echo "&nbsp;&nbsp;is_read BOOLEAN DEFAULT FALSE,<br>";
        echo "&nbsp;&nbsp;created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,<br>";
        echo "&nbsp;&nbsp;read_at TIMESTAMP NULL,<br>";
        echo "&nbsp;&nbsp;FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE<br>";
        echo ");";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 2. NotificationManager Test
echo "<div class='section'>";
echo "<h2>2. NotificationManager Testi</h2>";
try {
    $notificationManager = new NotificationManager($pdo);
    echo "<p class='success'>✅ NotificationManager başarıyla oluşturuldu</p>";
    
    // Mevcut kullanıcı için okunmamış bildirim sayısı
    $unreadCount = $notificationManager->getUnreadCount($_SESSION['user_id']);
    echo "<p>Mevcut admin kullanıcısı ({$_SESSION['username']}) için okunmamış bildirim sayısı: <strong>$unreadCount</strong></p>";
    
    // Son bildirimleri getir
    $notifications = $notificationManager->getUserNotifications($_SESSION['user_id'], 5);
    echo "<h3>Son 5 Bildirim:</h3>";
    if (!empty($notifications)) {
        echo "<table>";
        echo "<tr><th>Tip</th><th>Başlık</th><th>Mesaj</th><th>Okundu</th><th>Tarih</th></tr>";
        foreach ($notifications as $notification) {
            $readStatus = $notification['is_read'] ? '✅ Okundu' : '❌ Okunmadı';
            echo "<tr>";
            echo "<td>{$notification['type']}</td>";
            echo "<td>{$notification['title']}</td>";
            echo "<td>" . substr($notification['message'], 0, 100) . "...</td>";
            echo "<td>$readStatus</td>";
            echo "<td>{$notification['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Bu kullanıcı için bildirim bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ NotificationManager hatası: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. Admin Sidebar PHP Kodu Test
echo "<div class='section'>";
echo "<h2>3. Admin Sidebar Bildirim Hesaplama Testi</h2>";
try {
    // Admin sidebar'daki kodu simüle et
    $totalNotificationCount = 0;
    
    // NotificationManager'ı dahil et
    if (!class_exists('NotificationManager')) {
        require_once __DIR__ . '/../includes/NotificationManager.php';
    }
    
    if (isset($_SESSION['user_id']) && class_exists('NotificationManager')) {
        $notificationManager = new NotificationManager($pdo);
        $managerCount = $notificationManager->getUnreadCount($_SESSION['user_id']);
        $totalNotificationCount += $managerCount;
        echo "<p>NotificationManager sayısı: <strong>$managerCount</strong></p>";
    }
    
    // Bekleyen işlemler de eklenebilir (opsiyonel)
    $pendingProcessStmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE status = 'pending'");
    $pendingProcessStmt->execute();
    $pendingProcessCount = $pendingProcessStmt->fetchColumn();
    $totalNotificationCount += $pendingProcessCount;
    echo "<p>Bekleyen dosya işlem sayısı: <strong>$pendingProcessCount</strong></p>";
    
    echo "<p class='success'>📊 TOPLAM BİLDİRİM SAYISI: <strong>$totalNotificationCount</strong></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Sidebar hesaplama hatası: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. AJAX Dosyaları Kontrol
echo "<div class='section'>";
echo "<h2>4. AJAX Dosyaları Kontrolü</h2>";
$ajaxFiles = [
    'admin/ajax/get_notification_count.php',
    'admin/ajax/mark_notification_read.php',
    'admin/ajax/mark_all_notifications_read.php'
];

foreach ($ajaxFiles as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ " . basename($file) . " mevcut</p>";
    } else {
        echo "<p class='error'>❌ " . basename($file) . " eksik!</p>";
    }
}
echo "</div>";

// 5. Son File Uploads Kontrol
echo "<div class='section'>";
echo "<h2>5. Son Dosya Yüklemeleri Kontrolü</h2>";
try {
    $stmt = $pdo->query("
        SELECT fu.id, fu.original_name, fu.status, fu.upload_date, u.username 
        FROM file_uploads fu 
        LEFT JOIN users u ON fu.user_id = u.id 
        ORDER BY fu.upload_date DESC 
        LIMIT 5
    ");
    $uploads = $stmt->fetchAll();
    
    if (!empty($uploads)) {
        echo "<table>";
        echo "<tr><th>Dosya Adı</th><th>Kullanıcı</th><th>Durum</th><th>Tarih</th></tr>";
        foreach ($uploads as $upload) {
            echo "<tr>";
            echo "<td>{$upload['original_name']}</td>";
            echo "<td>{$upload['username']}</td>";
            echo "<td>{$upload['status']}</td>";
            echo "<td>{$upload['upload_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Hiç dosya yüklemesi bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Dosya yükleme kontrol hatası: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "</body></html>";
?>
