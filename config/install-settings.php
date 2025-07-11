<?php
/**
 * Mr ECU - Settings Table Installation
 * Settings Tablosu Kurulum Script
 */

require_once 'database.php';

try {
    // Settings tablosu oluştur
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Varsayılan ayarları ekle
    $defaultSettings = [
        ['smtp_password', '', 'SMTP Email şifresi'],
        ['admin_test_email', 'mrecu@outlook.com', 'Admin test email adresi'],
        ['email_test_mode', '1', 'Email test modu (1=aktif, 0=pasif)'],
        ['notification_enabled', '1', 'Bildirim sistemi durumu'],
        ['email_queue_enabled', '1', 'Email kuyruk sistemi durumu']
    ];

    foreach ($defaultSettings as $setting) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO settings (setting_key, setting_value, description) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute($setting);
    }

    echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Settings Tablosu Kuruldu</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='success'>✅ Settings Tablosu Başarıyla Kuruldu!</div>
        
        <div class='info'>
            <strong>Oluşturulan Tablo:</strong><br>
            - settings (sistem ayarları)<br><br>
            
            <strong>Varsayılan Ayarlar:</strong><br>
            - SMTP şifre alanı<br>
            - Admin test email: mrecu@outlook.com<br>
            - Email test modu: Aktif<br>
            - Bildirim sistemi: Aktif<br>
            - Email kuyruk sistemi: Aktif
        </div>
        
        <div>
            <a href='../admin/email-settings.php' class='btn'>Email Ayarları</a>
            <a href='../admin/' class='btn'>Admin Panel</a>
        </div>
    </div>
</body>
</html>";

} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Settings Tablosu Kurulum Hatası</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .error { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
        .details { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='error'>❌ Kurulum Hatası</div>
        <div class='details'>
            <strong>Hata:</strong> " . $e->getMessage() . "
        </div>
    </div>
</body>
</html>";
}
?>
