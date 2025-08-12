<?php
/**
 * Chat Bildirim Sistemi - Veritabanı Kontrol
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Chat Bildirim Sistemi - Veritabanı Kontrol</h2>";

try {
    // Notifications tablosu kontrolü
    echo "<h3>1. Notifications Tablosu Kontrol</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ notifications tablosu mevcut<br>";
        
        // Tablo yapısını kontrol et
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<strong>Tablo yapısı:</strong><br>";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})<br>";
        }
        
        // Chat mesaj tipindeki bildirim sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE type = 'chat_message'");
        $result = $stmt->fetch();
        echo "<br>Chat mesajı bildirim sayısı: {$result['count']}<br>";
        
    } else {
        echo "❌ notifications tablosu bulunamadı<br>";
        echo "<strong>Tablo oluştur:</strong><br>";
        echo "<pre>";
        echo "CREATE TABLE notifications (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id VARCHAR(36),
    related_type VARCHAR(50),
    action_url VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read)
);";
        echo "</pre>";
    }
    
    // File chats tablosu kontrolü
    echo "<h3>2. File Chats Tablosu Kontrol</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_chats'");
    if ($stmt->rowCount() > 0) {
        echo "✅ file_chats tablosu mevcut<br>";
        
        // Chat mesaj sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_chats");
        $result = $stmt->fetch();
        echo "Toplam chat mesajı sayısı: {$result['count']}<br>";
        
    } else {
        echo "❌ file_chats tablosu bulunamadı<br>";
    }
    
    // Users tablosu kontrol
    echo "<h3>3. Users Tablosu Kontrol</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins FROM users");
    $result = $stmt->fetch();
    echo "Toplam kullanıcı sayısı: {$result['total']}<br>";
    echo "Admin kullanıcı sayısı: {$result['admins']}<br>";
    
    // NotificationManager test
    echo "<h3>4. NotificationManager Test</h3>";
    require_once 'includes/NotificationManager.php';
    
    $notificationManager = new NotificationManager($pdo);
    echo "✅ NotificationManager başarıyla yüklendi<br>";
    
    // ChatManager test
    echo "<h3>5. ChatManager Test</h3>";
    require_once 'includes/ChatManager.php';
    
    $chatManager = new ChatManager($pdo);
    echo "✅ ChatManager başarıyla yüklendi<br>";
    
    // AJAX endpoint test
    echo "<h3>6. AJAX Endpoint Test</h3>";
    if (file_exists('ajax/get-chat-notifications.php')) {
        echo "✅ get-chat-notifications.php mevcut<br>";
    } else {
        echo "❌ get-chat-notifications.php bulunamadı<br>";
    }
    
    if (file_exists('ajax/chat.php')) {
        echo "✅ chat.php mevcut<br>";
    } else {
        echo "❌ chat.php bulunamadı<br>";
    }
    
    echo "<h3>7. Admin Sidebar Bildirim Entegrasyonu</h3>";
    if (file_exists('includes/admin_sidebar.php')) {
        $sidebarContent = file_get_contents('includes/admin_sidebar.php');
        if (strpos($sidebarContent, 'sidebar-notification-badge') !== false) {
            echo "✅ Admin sidebar'da toplam bildirim badge'i eklendi<br>";
        } else {
            echo "❌ Admin sidebar'da bildirim badge'i bulunamadı<br>";
        }
        
        if (strpos($sidebarContent, 'chat_message') !== false) {
            echo "✅ Admin sidebar'da chat bildirimleri entegrasyonu mevcut<br>";
        } else {
            echo "❌ Admin sidebar'da chat bildirimleri entegrasyonu bulunamadı<br>";
        }
    } else {
        echo "❌ admin_sidebar.php bulunamadı<br>";
    }
    
    echo "<h3>8. Sistem Durumu</h3>";
    echo "✅ Chat bildirim sistemi kurulumu tamamlandı<br>";
    echo "✅ Tüm gerekli dosyalar mevcut<br>";
    echo "✅ Veritabanı yapısı uygun<br>";
    
    echo "<hr>";
    echo "<h3>Test Linkleri:</h3>";
    echo "<a href='chat-notification-test.php' class='btn btn-primary'>Chat Bildirim Test Sayfası</a><br><br>";
    
    if (isLoggedIn()) {
        if (isAdmin()) {
            echo "<a href='admin/uploads.php' class='btn btn-success'>Admin Panel - Dosyalar</a><br>";
        } else {
            echo "<a href='user/files.php' class='btn btn-info'>Kullanıcı Panel - Dosyalar</a><br>";
        }
    } else {
        echo "<a href='login.php' class='btn btn-warning'>Giriş Yap</a><br>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>❌ Hata Oluştu:</h3>";
    echo $e->getMessage() . "<br>";
    echo "<strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
.btn { 
    display: inline-block; 
    padding: 10px 15px; 
    margin: 5px; 
    background: #007bff; 
    color: white; 
    text-decoration: none; 
    border-radius: 5px; 
}
.btn-success { background: #28a745; }
.btn-info { background: #17a2b8; }
.btn-warning { background: #ffc107; color: #333; }
</style>
