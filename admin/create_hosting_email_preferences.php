<?php
/**
 * Mr ECU - Hosting user_email_preferences Tablo Kurulum
 * Hosting ortamında eksik user_email_preferences tablosunu oluşturur
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Hosting Email Preferences Kurulum</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🌐 Hosting Email Preferences Kurulum</h1>";

try {
    // 1. Hosting ortamı doğrulama
    echo "<h2>1. Hosting Ortamı Doğrulama</h2>";
    
    $sql = "SELECT DATABASE() as db_name";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbName = $result['db_name'];
    
    echo "<div class='success'>✅ Hosting veritabanı: <strong>{$dbName}</strong></div>";
    
    // 2. users tablosu yapısını kontrol et
    echo "<h2>2. Users Tablosu Analiz</h2>";
    
    $sql = "DESCRIBE users";
    $stmt = $pdo->query($sql);
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $userIdType = 'int';
    foreach ($userColumns as $column) {
        if ($column['Field'] === 'id') {
            $userIdType = $column['Type'];
            break;
        }
    }
    
    echo "<div class='info'>";
    echo "<h3>Users Tablo Yapısı:</h3>";
    echo "<ul>";
    echo "<li><strong>users.id tipi:</strong> {$userIdType}</li>";
    echo "<li><strong>Toplam alan sayısı:</strong> " . count($userColumns) . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // 3. Kullanıcı sayısını kontrol et
    $sql = "SELECT COUNT(*) as user_count FROM users";
    $stmt = $pdo->query($sql);
    $userCount = $stmt->fetchColumn();
    
    echo "<div class='info'>📊 Toplam kullanıcı sayısı: <strong>{$userCount}</strong></div>";
    
    // 4. user_email_preferences tablosunu oluştur
    echo "<h2>3. user_email_preferences Tablosu Oluşturuluyor</h2>";
    
    // users.id tipine göre uyumlu tablo oluştur
    $userIdColumn = (strpos($userIdType, 'char') !== false || strpos($userIdType, 'varchar') !== false) 
        ? 'varchar(36)' : 'int(11)';
    
    $sql = "CREATE TABLE IF NOT EXISTS `user_email_preferences` (
        `id` {$userIdColumn} NOT NULL,
        `user_id` {$userIdColumn} NOT NULL,
        `email_notifications` tinyint(1) DEFAULT 1,
        `file_upload_notifications` tinyint(1) DEFAULT 1,
        `file_ready_notifications` tinyint(1) DEFAULT 1,
        `revision_notifications` tinyint(1) DEFAULT 1,
        `additional_file_notifications` tinyint(1) DEFAULT 1,
        `chat_message_notifications` tinyint(1) DEFAULT 1,
        `marketing_emails` tinyint(1) DEFAULT 0,
        `promotional_emails` tinyint(1) DEFAULT 0,
        `security_notifications` tinyint(1) DEFAULT 1,
        `email_frequency` enum('immediate','daily','weekly') DEFAULT 'immediate',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_user_id` (`user_id`),
        KEY `idx_chat_notifications` (`chat_message_notifications`),
        CONSTRAINT `fk_user_email_prefs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ user_email_preferences tablosu başarıyla oluşturuldu.</div>";
    
    // 5. Tüm kullanıcılar için email tercihleri oluştur
    echo "<h2>4. Kullanıcı Email Tercihleri Oluşturuluyor</h2>";
    
    // Kullanıcıları al
    $sql = "SELECT id, email, first_name, last_name FROM users ORDER BY created_at";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Toplam {$userCount} kullanıcı için email tercihleri oluşturuluyor...</div>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($users as $user) {
        try {
            // ID tipine göre uygun ID üret
            if (strpos($userIdType, 'char') !== false || strpos($userIdType, 'varchar') !== false) {
                // UUID üret
                $prefId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            } else {
                // AUTO_INCREMENT için NULL
                $prefId = null;
            }
            
            $sql = "INSERT INTO user_email_preferences 
                    (id, user_id, email_notifications, file_upload_notifications, 
                     file_ready_notifications, revision_notifications, 
                     additional_file_notifications, chat_message_notifications, 
                     marketing_emails, promotional_emails, security_notifications) 
                    VALUES (?, ?, 1, 1, 1, 1, 1, 1, 0, 0, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$prefId, $user['id']]);
            
            $userName = trim($user['first_name'] . ' ' . $user['last_name']);
            if (empty($userName)) $userName = $user['email'];
            
            echo "<div class='success'>✅ <strong>{$userName}</strong> ({$user['email']}) için email tercihleri oluşturuldu.</div>";
            $successCount++;
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ {$user['email']} için hata: {$e->getMessage()}</div>";
            $errorCount++;
        }
    }
    
    // 6. Chat email template'lerini kontrol et ve ekle
    echo "<h2>5. Chat Email Template'leri Kuruluyor</h2>";
    
    // Template yapısını tespit et
    $sql = "DESCRIBE email_templates";
    $stmt = $pdo->query($sql);
    $templateColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $templateColumnNames = array_column($templateColumns, 'Field');
    
    $nameField = in_array('template_key', $templateColumnNames) ? 'template_key' : 'name';
    $contentField = in_array('body', $templateColumnNames) ? 'body' : 'content';
    
    echo "<div class='info'>Template yapısı: <strong>{$nameField}</strong> / <strong>{$contentField}</strong> alanları kullanılacak.</div>";
    
    $chatTemplates = [
        'chat_message_admin' => [
            'subject' => 'Yeni Kullanıcı Mesajı - {{file_name}}',
            'content' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #3498db; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
        💬 Yeni Kullanıcı Mesajı
    </h2>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="color: #2c3e50; margin-top: 0;">Merhaba Admin,</h3>
        <p><strong>{{sender_name}}</strong> size {{file_name}} dosyası için mesaj gönderdi:</p>
        <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #3498db; margin: 15px 0;">
            <p style="margin: 0; color: #2c3e50; font-style: italic;">"{{message}}"</p>
        </div>
    </div>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{chat_url}}" style="background: #3498db; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Mesajı Yanıtla
        </a>
    </div>
    <p style="color: #7f8c8d; font-size: 12px; margin-top: 30px;">
        Bu email otomatik olarak gönderilmiştir.<br>
        <strong>Mr ECU Tuning</strong> - Profesyonel ECU Tuning Hizmetleri
    </p>
</div>',
            'variables' => 'file_name,sender_name,message,chat_url'
        ],
        'chat_message_user' => [
            'subject' => 'Yeni Admin Mesajı - {{file_name}}',
            'content' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #27ae60; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">
        💬 Yeni Admin Mesajı
    </h2>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="color: #2c3e50; margin-top: 0;">Merhaba {{user_name}},</h3>
        <p>Admin size <strong>{{file_name}}</strong> dosyası için mesaj gönderdi:</p>
        <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #27ae60; margin: 15px 0;">
            <p style="margin: 0; color: #2c3e50; font-style: italic;">"{{message}}"</p>
        </div>
    </div>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{chat_url}}" style="background: #27ae60; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Mesajı Görüntüle
        </a>
    </div>
    <p style="color: #7f8c8d; font-size: 12px; margin-top: 30px;">
        Bu email otomatik olarak gönderilmiştir.<br>
        <strong>Mr ECU Tuning</strong> - Profesyonel ECU Tuning Hizmetleri
    </p>
</div>',
            'variables' => 'user_name,file_name,message,chat_url'
        ]
    ];
    
    foreach ($chatTemplates as $templateName => $templateData) {
        // Template var mı kontrol et
        $sql = "SELECT COUNT(*) FROM email_templates WHERE {$nameField} = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$templateName]);
        
        if ($stmt->fetchColumn() == 0) {
            // Template'i ekle - hosting yapısına göre
            if (in_array('id', $templateColumnNames)) {
                $templateId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $sql = "INSERT INTO email_templates (id, {$nameField}, subject, {$contentField}, variables, is_active) VALUES (?, ?, ?, ?, ?, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$templateId, $templateName, $templateData['subject'], $templateData['content'], $templateData['variables']]);
            } else {
                $sql = "INSERT INTO email_templates ({$nameField}, subject, {$contentField}, variables, is_active) VALUES (?, ?, ?, ?, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$templateName, $templateData['subject'], $templateData['content'], $templateData['variables']]);
            }
            
            echo "<div class='success'>✅ {$templateName} template'i eklendi.</div>";
        } else {
            echo "<div class='info'>ℹ️ {$templateName} template'i zaten mevcut.</div>";
        }
    }
    
    // 7. Final durum raporu
    echo "<h2>6. 🎉 Hosting Chat Email Sistemi Kurulumu Tamamlandı</h2>";
    
    // İstatistikler
    $sql = "SELECT COUNT(*) FROM user_email_preferences";
    $stmt = $pdo->query($sql);
    $totalPrefs = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM user_email_preferences WHERE chat_message_notifications = 1";
    $stmt = $pdo->query($sql);
    $chatEnabled = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM email_templates WHERE {$nameField} LIKE 'chat_message_%'";
    $stmt = $pdo->query($sql);
    $chatTemplatesCount = $stmt->fetchColumn();
    
    echo "<div class='success'>";
    echo "<h3>📊 Kurulum Başarı İstatistikleri:</h3>";
    echo "<ul>";
    echo "<li>Toplam kullanıcı: <strong>{$userCount}</strong></li>";
    echo "<li>Email tercihi oluşturulan: <strong>{$successCount}</strong></li>";
    echo "<li>Hatalı kayıt: <strong>{$errorCount}</strong></li>";
    echo "<li>Chat bildirimi etkin: <strong>{$chatEnabled}</strong></li>";
    echo "<li>Chat template'leri: <strong>{$chatTemplatesCount}/2</strong></li>";
    echo "<li>Başarı oranı: <strong>" . round(($successCount / $userCount) * 100, 1) . "%</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // SMTP kontrolü
    $smtpReady = getenv('SMTP_HOST') && getenv('SMTP_USERNAME');
    
    echo "<div class='" . ($smtpReady ? 'success' : 'warning') . "'>";
    echo "<h3>📧 Email Sistemi Durum:</h3>";
    echo "<ul>";
    echo "<li>" . ($smtpReady ? '✅' : '❌') . " SMTP ayarları: " . ($smtpReady ? 'Hazır' : 'Eksik') . "</li>";
    echo "<li>✅ Email template'leri: Hazır</li>";
    echo "<li>✅ Chat sistemi: Hazır</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($successCount == $userCount && $chatTemplatesCount == 2 && $smtpReady) {
        echo "<div class='success'>";
        echo "<h2>🎉 Hosting Chat Email Bildirimleri TAM HAZIR!</h2>";
        echo "<p><strong>Artık chat email bildirimleri çalışacak:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Kullanıcı mesaj gönderir → Adminlere email gider</li>";
        echo "<li>✅ Admin mesaj gönderir → Kullanıcıya email gider</li>";
        echo "<li>✅ Email tercihleri çalışır</li>";
        echo "<li>✅ Template'ler hazır</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>🧪 Test Adımları:</h3>";
        echo "<ol>";
        echo "<li>Kullanıcı hesabı ile giriş yapın</li>";
        echo "<li>Bir dosya detay sayfasına gidin</li>";
        echo "<li>Chat mesajı gönderin</li>";
        echo "<li>Admin email adresini kontrol edin</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Eksik Adımlar</h3>";
        if (!$smtpReady) echo "<p>• SMTP ayarları eksik</p>";
        if ($chatTemplatesCount < 2) echo "<p>• Chat template'leri eksik</p>";
        if ($errorCount > 0) echo "<p>• {$errorCount} kullanıcı için email tercihi oluşturulamadı</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Hosting Kurulum Hatası</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
    echo "</div>";
    error_log("Hosting email preferences setup error: " . $e->getMessage());
}

echo "</body></html>";
?>
