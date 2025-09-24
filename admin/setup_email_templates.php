<?php
/**
 * Mr ECU - Email Templates Tablosu Kontrol ve Onarım
 * Bu script, email_templates tablosunu kontrol eder ve gerekiyorsa oluşturur
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Email Templates Tablo Kontrol</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>📧 Email Templates Tablo Kontrol</h1>";

try {
    // 1. email_templates tablosu var mı kontrol et
    echo "<h2>1. Email Templates Tablosu Kontrol Ediliyor</h2>";
    
    $sql = "SHOW TABLES LIKE 'email_templates'";
    $stmt = $pdo->query($sql);
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<div class='info'>✅ email_templates tablosu mevcut.</div>";
        
        // Tablo yapısını kontrol et
        $sql = "DESCRIBE email_templates";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>";
        echo "<h3>Mevcut Tablo Yapısı:</h3><ul>";
        foreach ($columns as $column) {
            echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} - Default: " . ($column['Default'] ?? 'NULL') . "</li>";
        }
        echo "</ul></div>";
        
        // Mevcut template'leri listele
        $sql = "SELECT * FROM email_templates";
        $stmt = $pdo->query($sql);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>";
        echo "<h3>Mevcut Template'ler (" . count($templates) . "):</h3>";
        if (!empty($templates)) {
            echo "<ul>";
            foreach ($templates as $template) {
                $nameField = $template['name'] ?? $template['template_name'] ?? 'Unknown';
                $subjectField = $template['subject'] ?? $template['template_subject'] ?? 'No Subject';
                echo "<li><strong>{$nameField}</strong> - {$subjectField}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Hiç template bulunamadı.</p>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='warning'>⚠️ email_templates tablosu bulunamadı!</div>";
        
        // 2. Tabloyu oluştur
        echo "<h2>2. Email Templates Tablosu Oluşturuluyor</h2>";
        
        $sql = "CREATE TABLE `email_templates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `subject` varchar(255) NOT NULL,
            `content` longtext NOT NULL,
            `variables` json DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_name` (`name`),
            KEY `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>✅ email_templates tablosu başarıyla oluşturuldu.</div>";
    }
    
    // 3. Gerekli template'lerin varlığını kontrol et
    echo "<h2>3. Chat Email Template'leri Kontrol Ediliyor</h2>";
    
    $requiredTemplates = ['chat_message_admin', 'chat_message_user'];
    $missingTemplates = [];
    
    foreach ($requiredTemplates as $templateName) {
        $sql = "SELECT COUNT(*) FROM email_templates WHERE name = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$templateName]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo "<div class='success'>✅ {$templateName} template'i mevcut.</div>";
        } else {
            echo "<div class='warning'>⚠️ {$templateName} template'i eksik!</div>";
            $missingTemplates[] = $templateName;
        }
    }
    
    // 4. Eksik template'leri ekle
    if (!empty($missingTemplates)) {
        echo "<h2>4. Eksik Template'ler Ekleniyor</h2>";
        
        $templates = [
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
        <strong>Mr ECU</strong> - Profesyonel ECU Tuning Hizmetleri
    </p>
</div>',
                'variables' => '["file_name", "sender_name", "message", "chat_url"]'
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
        <strong>Mr ECU</strong> - Profesyonel ECU Tuning Hizmetleri
    </p>
</div>',
                'variables' => '["user_name", "file_name", "message", "chat_url"]'
            ]
        ];
        
        foreach ($missingTemplates as $templateName) {
            if (isset($templates[$templateName])) {
                $template = $templates[$templateName];
                
                $sql = "INSERT INTO email_templates (name, subject, content, variables, is_active) VALUES (?, ?, ?, ?, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $templateName,
                    $template['subject'],
                    $template['content'],
                    $template['variables']
                ]);
                
                echo "<div class='success'>✅ {$templateName} template'i başarıyla eklendi.</div>";
            }
        }
    }
    
    // 5. Diğer yararlı template'leri kontrol et ve ekle
    echo "<h2>5. Diğer Email Template'leri Kontrol Ediliyor</h2>";
    
    $additionalTemplates = [
        'verification' => [
            'subject' => 'Email Adresinizi Doğrulayın - {{site_name}}',
            'content' => '<h2>Email Adresinizi Doğrulayın</h2><p>Merhaba {{user_name}},</p><p>Email adresinizi doğrulamak için aşağıdaki linke tıklayın:</p><a href="{{verification_link}}">Email Adresimi Doğrula</a>',
            'variables' => '["site_name", "user_name", "verification_link"]'
        ],
        'password_reset' => [
            'subject' => 'Şifre Sıfırlama - {{site_name}}',
            'content' => '<h2>Şifre Sıfırlama</h2><p>Merhaba {{user_name}},</p><p>Şifre sıfırlama kodunuz: <strong>{{reset_code}}</strong></p>',
            'variables' => '["site_name", "user_name", "reset_code"]'
        ],
        'file_upload_admin' => [
            'subject' => 'Yeni Dosya Yüklendi - {{site_name}}',
            'content' => '<h2>Yeni Dosya Yüklendi</h2><p>{{user_name}} tarafından yeni bir dosya yüklendi.</p><p>Dosya: {{file_name}}</p>',
            'variables' => '["site_name", "user_name", "file_name"]'
        ]
    ];
    
    foreach ($additionalTemplates as $templateName => $template) {
        $sql = "SELECT COUNT(*) FROM email_templates WHERE name = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$templateName]);
        $exists = $stmt->fetchColumn() > 0;
        
        if (!$exists) {
            $sql = "INSERT INTO email_templates (name, subject, content, variables, is_active) VALUES (?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $templateName,
                $template['subject'],
                $template['content'],
                $template['variables']
            ]);
            echo "<div class='info'>➕ {$templateName} template'i eklendi.</div>";
        }
    }
    
    // 6. Final durum raporu
    echo "<h2>6. 📊 Final Durum Raporu</h2>";
    
    $sql = "SELECT COUNT(*) FROM email_templates";
    $stmt = $pdo->query($sql);
    $totalTemplates = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM email_templates WHERE name LIKE 'chat_message_%'";
    $stmt = $pdo->query($sql);
    $chatTemplates = $stmt->fetchColumn();
    
    $sql = "SELECT name, subject, is_active FROM email_templates ORDER BY name";
    $stmt = $pdo->query($sql);
    $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>";
    echo "<h3>✅ Template Sistemi Hazır!</h3>";
    echo "<ul>";
    echo "<li>Toplam template sayısı: <strong>{$totalTemplates}</strong></li>";
    echo "<li>Chat message template'leri: <strong>{$chatTemplates}</strong></li>";
    echo "<li>Aktif template'ler: <strong>" . count(array_filter($allTemplates, function($t) { return $t['is_active']; })) . "</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📋 Tüm Template'ler:</h3>";
    echo "<table style='width:100%;border-collapse:collapse;'>";
    echo "<tr style='background:#f8f9fa;'><th style='border:1px solid #ddd;padding:8px;'>Template</th><th style='border:1px solid #ddd;padding:8px;'>Subject</th><th style='border:1px solid #ddd;padding:8px;'>Durum</th></tr>";
    foreach ($allTemplates as $template) {
        $status = $template['is_active'] ? '<span style="color:green;">✅ Aktif</span>' : '<span style="color:red;">❌ Pasif</span>';
        echo "<tr><td style='border:1px solid #ddd;padding:8px;'><strong>{$template['name']}</strong></td><td style='border:1px solid #ddd;padding:8px;'>{$template['subject']}</td><td style='border:1px solid #ddd;padding:8px;'>{$status}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔄 Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li><a href='setup_chat_email_notifications.php'>Chat Email Bildirimleri Setup</a> script'ini tekrar çalıştırın</li>";
    echo "<li><a href='test_chat_email_notifications.php'>Test Script</a>'ini çalıştırın</li>";
    echo "<li>Gerçek chat mesajı testine geçin</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Kritik Hata</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
    echo "</div>";
    error_log("Email templates setup error: " . $e->getMessage());
}

echo "</body></html>";
?>
