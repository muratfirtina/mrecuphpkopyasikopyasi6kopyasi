<?php
/**
 * Mr ECU - Bildirim Sistemi Kurulum Script
 * Notification System Installation Script
 */

require_once 'database.php';

// UUID fonksiyonu
if (!function_exists('generateUUID')) {
    function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

try {
    // Bildirimler tablosu oluştur
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS notifications (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NOT NULL,
        type ENUM('file_upload', 'file_status_update', 'revision_request', 'revision_response', 'system', 'credit') NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        related_id CHAR(36) NULL COMMENT 'İlgili dosya/revize ID',
        related_type ENUM('file_upload', 'revision', 'credit_transaction') NULL,
        action_url VARCHAR(500) NULL,
        is_read BOOLEAN DEFAULT FALSE,
        is_email_sent BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Email şablonları tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS email_templates (
        id CHAR(36) PRIMARY KEY,
        template_key VARCHAR(100) UNIQUE NOT NULL,
        subject VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        variables TEXT COMMENT 'JSON format - kullanılabilir değişkenler',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Email kuyruğu tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS email_queue (
        id CHAR(36) PRIMARY KEY,
        to_email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        priority ENUM('high', 'normal', 'low') DEFAULT 'normal',
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 3,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL
    )");

    // Varsayılan email şablonları ekle
    $templates = [
        [
            'key' => 'file_upload_admin',
            'subject' => 'Yeni Dosya Yüklendi - Mr ECU',
            'body' => '<h2>Yeni Dosya Yüklendi</h2>
<p>Merhaba,</p>
<p>Sistemde yeni bir dosya yüklendi:</p>
<ul>
<li><strong>Kullanıcı:</strong> {{user_name}} ({{user_email}})</li>
<li><strong>Dosya:</strong> {{file_name}}</li>
<li><strong>Araç:</strong> {{brand_name}} {{model_name}} ({{year}})</li>
<li><strong>Plaka:</strong> {{plate}}</li>
<li><strong>ECU Tipi:</strong> {{ecu_type}}</li>
</ul>
<p><a href="{{admin_url}}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Dosyayı İncele</a></p>',
            'variables' => '["user_name", "user_email", "file_name", "brand_name", "model_name", "year", "plate", "ecu_type", "admin_url"]'
        ],
        [
            'key' => 'file_status_update_user',
            'subject' => 'Dosya Durumu Güncellendi - Mr ECU',
            'body' => '<h2>Dosya Durumu Güncellendi</h2>
<p>Merhaba {{user_name}},</p>
<p>Yüklediğiniz dosyanın durumu güncellendi:</p>
<ul>
<li><strong>Dosya:</strong> {{file_name}}</li>
<li><strong>Yeni Durum:</strong> {{status}}</li>
<li><strong>Admin Notu:</strong> {{admin_notes}}</li>
</ul>
<p><a href="{{user_url}}" style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Dosyalarımı Gör</a></p>',
            'variables' => '["user_name", "file_name", "status", "admin_notes", "user_url"]'
        ],
        [
            'key' => 'revision_request_admin',
            'subject' => 'Yeni Revize Talebi - Mr ECU',
            'body' => '<h2>Yeni Revize Talebi</h2>
<p>Merhaba,</p>
<p>Sistemde yeni bir revize talebi oluşturuldu:</p>
<ul>
<li><strong>Kullanıcı:</strong> {{user_name}} ({{user_email}})</li>
<li><strong>Orijinal Dosya:</strong> {{original_file}}</li>
<li><strong>Talep Notu:</strong> {{request_notes}}</li>
</ul>
<p><a href="{{admin_url}}" style="background-color: #ffc107; color: #212529; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Revize Talebini İncele</a></p>',
            'variables' => '["user_name", "user_email", "original_file", "request_notes", "admin_url"]'
        ],
        [
            'key' => 'revision_response_user',
            'subject' => 'Revize Talebiniz Yanıtlandı - Mr ECU',
            'body' => '<h2>Revize Talebiniz Yanıtlandı</h2>
<p>Merhaba {{user_name}},</p>
<p>Revize talebiniz işleme alındı:</p>
<ul>
<li><strong>Orijinal Dosya:</strong> {{original_file}}</li>
<li><strong>Durum:</strong> {{status}}</li>
<li><strong>Admin Yanıtı:</strong> {{admin_response}}</li>
</ul>
<p><a href="{{user_url}}" style="background-color: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Revize Taleplerim</a></p>',
            'variables' => '["user_name", "original_file", "status", "admin_response", "user_url"]'
        ]
    ];

    foreach ($templates as $template) {
        $templateId = generateUUID(); // UUID oluştur
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO email_templates (id, template_key, subject, body, variables) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $templateId,
            $template['key'],
            $template['subject'],
            $template['body'],
            $template['variables']
        ]);
    }

    echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Bildirim Sistemi Kuruldu</title>
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
        <div class='success'>✅ Bildirim Sistemi Başarıyla Kuruldu!</div>
        
        <div class='info'>
            <strong>Oluşturulan Tablolar:</strong><br>
            - notifications (bildirimler)<br>
            - email_templates (email şablonları)<br>
            - email_queue (email kuyruğu)<br><br>
            
            <strong>Email Şablonları:</strong><br>
            - Dosya yükleme bildirimi (admin)<br>
            - Dosya durum güncelleme (kullanıcı)<br>
            - Revize talebi (admin)<br>
            - Revize yanıtı (kullanıcı)
        </div>
        
        <div>
            <a href='../admin/' class='btn'>Admin Paneline Git</a>
            <a href='../index.php' class='btn'>Ana Sayfaya Git</a>
        </div>
    </div>
</body>
</html>";

} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Bildirim Sistemi Kurulum Hatası</title>
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
