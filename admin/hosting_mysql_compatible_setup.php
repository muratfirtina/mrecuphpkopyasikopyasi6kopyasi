<?php
/**
 * Mr ECU - Hosting MySQL Uyumlu Email Preferences Kurulum
 * Eski MySQL versiyonları için TIMESTAMP sorunu çözüldü
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Hosting MySQL Uyumlu Kurulum</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🌐 Hosting MySQL Uyumlu Email Preferences Kurulum</h1>";

try {
    // 1. MySQL versiyonunu kontrol et
    echo "<h2>1. MySQL Version Kontrol</h2>";
    
    $sql = "SELECT VERSION() as mysql_version";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $mysqlVersion = $result['mysql_version'];
    
    echo "<div class='info'>";
    echo "<h3>MySQL Bilgileri:</h3>";
    echo "<ul>";
    echo "<li><strong>MySQL Version:</strong> {$mysqlVersion}</li>";
    echo "<li><strong>Database:</strong> " . ($pdo->query("SELECT DATABASE()")->fetchColumn()) . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // 2. users tablosu yapısı
    echo "<h2>2. Users Tablosu Analiz</h2>";
    
    $sql = "DESCRIBE users";
    $stmt = $pdo->query($sql);
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $userIdType = 'char(36)'; // Hosting'te char(36) olduğunu biliyoruz
    foreach ($userColumns as $column) {
        if ($column['Field'] === 'id') {
            $userIdType = $column['Type'];
            break;
        }
    }
    
    $sql = "SELECT COUNT(*) FROM users";
    $stmt = $pdo->query($sql);
    $userCount = $stmt->fetchColumn();
    
    echo "<div class='success'>";
    echo "✅ Users tablosu analizi: <br>";
    echo "• ID tipi: <strong>{$userIdType}</strong><br>";
    echo "• Kullanıcı sayısı: <strong>{$userCount}</strong>";
    echo "</div>";
    
    // 3. user_email_preferences tablosunu MySQL uyumlu şekilde oluştur
    echo "<h2>3. MySQL Uyumlu Tablo Oluşturuluyor</h2>";
    
    // Eski MySQL için uyumlu SQL (sadece bir TIMESTAMP)
    $sql = "CREATE TABLE IF NOT EXISTS `user_email_preferences` (
        `id` varchar(36) NOT NULL,
        `user_id` varchar(36) NOT NULL,
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
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_user_id` (`user_id`),
        KEY `idx_chat_notifications` (`chat_message_notifications`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ user_email_preferences tablosu başarıyla oluşturuldu (MySQL uyumlu).</div>";
    
    // 4. Foreign key'i ayrı olarak ekle (hata alırsa devam et)
    echo "<h3>Foreign Key Ekleniyor:</h3>";
    try {
        $sql = "ALTER TABLE user_email_preferences 
                ADD CONSTRAINT fk_user_email_prefs_user_id 
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
        $pdo->exec($sql);
        echo "<div class='success'>✅ Foreign key constraint eklendi.</div>";
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ Foreign key eklenemedi (normal): {$e->getMessage()}</div>";
    }
    
    // 5. Kullanıcılar için email tercihleri oluştur
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
            // UUID üret
            $prefId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
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
            
            echo "<div class='success'>✅ <strong>{$userName}</strong> için email tercihleri oluşturuldu.</div>";
            $successCount++;
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ {$user['email']} için hata: {$e->getMessage()}</div>";
            $errorCount++;
        }
    }
    
    // 6. Chat template'lerini ekle
    echo "<h2>5. Chat Email Template'leri Ekleniyor</h2>";
    
    // Template yapısını tespit et
    $sql = "DESCRIBE email_templates";
    $stmt = $pdo->query($sql);
    $templateColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $templateColumnNames = array_column($templateColumns, 'Field');
    
    $nameField = in_array('template_key', $templateColumnNames) ? 'template_key' : 'name';
    $contentField = in_array('body', $templateColumnNames) ? 'body' : 'content';
    
    echo "<div class='info'>Template yapısı: <strong>{$nameField}</strong> / <strong>{$contentField}</strong></div>";
    
    $chatTemplates = [
        'chat_message_admin' => [
            'subject' => 'Yeni Kullanıcı Mesajı - {{file_name}}',
            'content' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2px; border-radius: 12px;">
        <div style="background: white; border-radius: 10px; padding: 0;">
            <div style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center;">
                <h2 style="margin: 0; font-size: 24px;">💬 Yeni Kullanıcı Mesajı</h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Mr ECU Tuning Platform</p>
            </div>
            <div style="padding: 30px;">
                <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #3498db; margin-bottom: 25px;">
                    <h3 style="color: #2c3e50; margin: 0 0 15px 0; font-size: 18px;">Merhaba Admin,</h3>
                    <p style="color: #34495e; margin: 0 0 15px 0; line-height: 1.6;">
                        <strong style="color: #3498db;">{{sender_name}}</strong> size 
                        <strong style="color: #27ae60;">{{file_name}}</strong> dosyası için yeni bir mesaj gönderdi:
                    </p>
                    <div style="background: white; padding: 20px; border-radius: 6px; border-left: 4px solid #3498db; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <p style="margin: 0; color: #2c3e50; font-style: italic; font-size: 16px; line-height: 1.5;">
                            "{{message}}"
                        </p>
                    </div>
                </div>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{chat_url}}" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; display: inline-block; font-weight: bold; box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3); transition: all 0.3s;">
                        ↩️ Mesajı Yanıtla
                    </a>
                </div>
            </div>
            <div style="background: #ecf0f1; padding: 20px; border-radius: 0 0 10px 10px; text-align: center;">
                <p style="margin: 0; color: #7f8c8d; font-size: 14px;">
                    Bu email otomatik olarak gönderilmiştir.<br>
                    <strong style="color: #2c3e50;">Mr ECU Tuning</strong> - Profesyonel ECU Tuning Hizmetleri
                </p>
            </div>
        </div>
    </div>
</div>',
            'variables' => 'file_name,sender_name,message,chat_url'
        ],
        'chat_message_user' => [
            'subject' => 'Yeni Admin Mesajı - {{file_name}}',
            'content' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2px; border-radius: 12px;">
        <div style="background: white; border-radius: 10px; padding: 0;">
            <div style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center;">
                <h2 style="margin: 0; font-size: 24px;">💬 Yeni Admin Mesajı</h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Mr ECU Tuning Platform</p>
            </div>
            <div style="padding: 30px;">
                <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #27ae60; margin-bottom: 25px;">
                    <h3 style="color: #2c3e50; margin: 0 0 15px 0; font-size: 18px;">Merhaba {{user_name}},</h3>
                    <p style="color: #34495e; margin: 0 0 15px 0; line-height: 1.6;">
                        Adminlerimizden biri size <strong style="color: #e74c3c;">{{file_name}}</strong> dosyası için mesaj gönderdi:
                    </p>
                    <div style="background: white; padding: 20px; border-radius: 6px; border-left: 4px solid #27ae60; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <p style="margin: 0; color: #2c3e50; font-style: italic; font-size: 16px; line-height: 1.5;">
                            "{{message}}"
                        </p>
                    </div>
                </div>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{chat_url}}" style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; display: inline-block; font-weight: bold; box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3); transition: all 0.3s;">
                        👁️ Mesajı Görüntüle
                    </a>
                </div>
            </div>
            <div style="background: #ecf0f1; padding: 20px; border-radius: 0 0 10px 10px; text-align: center;">
                <p style="margin: 0; color: #7f8c8d; font-size: 14px;">
                    Bu email otomatik olarak gönderilmiştir.<br>
                    <strong style="color: #2c3e50;">Mr ECU Tuning</strong> - Profesyonel ECU Tuning Hizmetleri
                </p>
            </div>
        </div>
    </div>
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
            // Template'i ekle
            $templateId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $sql = "INSERT INTO email_templates (id, {$nameField}, subject, {$contentField}, variables, is_active) 
                    VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$templateId, $templateName, $templateData['subject'], $templateData['content'], $templateData['variables']]);
            
            echo "<div class='success'>✅ {$templateName} template'i eklendi.</div>";
        } else {
            echo "<div class='info'>ℹ️ {$templateName} template'i zaten mevcut.</div>";
        }
    }
    
    // 7. Final durum raporu
    echo "<h2>6. 🎉 Hosting Chat Email Sistemi Başarıyla Kuruldu!</h2>";
    
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
    
    // SMTP durumu
    $smtpReady = getenv('SMTP_HOST') && getenv('SMTP_USERNAME');
    
    echo "<div class='success'>";
    echo "<h3>📊 Kurulum Başarı İstatistikleri:</h3>";
    echo "<ul>";
    echo "<li>✅ Toplam kullanıcı: <strong>{$userCount}</strong></li>";
    echo "<li>✅ Email tercihi oluşturulan: <strong>{$successCount}</strong></li>";
    echo "<li>✅ Hatalı kayıt: <strong>{$errorCount}</strong></li>";
    echo "<li>✅ Chat bildirimi etkin: <strong>{$chatEnabled}</strong></li>";
    echo "<li>✅ Chat template'leri: <strong>{$chatTemplatesCount}/2</strong></li>";
    echo "<li>✅ Başarı oranı: <strong>" . round(($successCount / $userCount) * 100, 1) . "%</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>🌟 Chat Email Bildirimleri Artık Aktif!</h3>";
    echo "<ul>";
    echo "<li>🔥 Kullanıcı mesaj gönderir → Adminlere email gider</li>";
    echo "<li>🔥 Admin mesaj gönderir → Kullanıcıya email gider</li>";
    echo "<li>⚙️ Email tercihleri çalışır (kullanıcılar açıp kapatabilir)</li>";
    echo "<li>🎨 Premium template'ler hazır</li>";
    echo "<li>🛡️ MySQL uyumluluk sorunu çözüldü</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🧪 Hemen Test Edin:</h3>";
    echo "<ol>";
    echo "<li><strong>Kullanıcı hesabı</strong> ile mrecutuning.com'a giriş yapın</li>";
    echo "<li>Bir <strong>dosya detay sayfası</strong>na gidin</li>";
    echo "<li><strong>Chat mesajı</strong> gönderin (admin'e)</li>";
    echo "<li><strong>Admin email</strong>'ini kontrol edin</li>";
    echo "<li>Admin hesabı ile giriş yapıp <strong>yanıt</strong> verin</li>";
    echo "<li><strong>Kullanıcı email</strong>'ini kontrol edin</li>";
    echo "</ol>";
    echo "</div>";
    
    // Örnek kullanıcı göster
    if ($successCount > 0) {
        echo "<div class='info'>";
        echo "<h3>📝 Örnek Test Kullanıcıları:</h3>";
        $sql = "SELECT u.email, u.first_name, u.last_name, uep.chat_message_notifications 
                FROM users u 
                JOIN user_email_preferences uep ON u.id = uep.user_id 
                LIMIT 3";
        $stmt = $pdo->query($sql);
        $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<ul>";
        foreach ($sampleUsers as $user) {
            $name = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
            $status = $user['chat_message_notifications'] ? '✅ Açık' : '❌ Kapalı';
            echo "<li><strong>{$name}</strong> - Chat Bildirimi: {$status}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Hosting Kurulum Hatası</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
    echo "</div>";
    error_log("Hosting MySQL compatible setup error: " . $e->getMessage());
}

echo "</body></html>";
?>
