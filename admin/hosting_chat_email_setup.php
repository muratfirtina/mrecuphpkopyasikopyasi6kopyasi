<?php
/**
 * Mr ECU - Hosting Ortamı Chat Email Kurulum
 * Hosting ortamındaki mevcut yapıya göre chat email bildirimlerini kurar
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Hosting Chat Email Kurulum</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🌐 Hosting Chat Email Kurulum</h1>";

try {
    // 1. Hosting ortamı bilgilerini al
    echo "<h2>1. Hosting Ortamı Kontrol</h2>";
    
    echo "<div class='info'>";
    echo "<h3>Ortam Bilgileri:</h3>";
    echo "<ul>";
    echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
    echo "<li><strong>Server:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</li>";
    echo "<li><strong>Database Host:</strong> " . (getenv('DB_HOST') ?: 'localhost') . "</li>";
    echo "<li><strong>Database Name:</strong> " . (getenv('DB_NAME') ?: 'Unknown') . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // 2. Veritabanı bağlantısını test et
    echo "<h2>2. Veritabanı Bağlantısı Test</h2>";
    
    if (!isset($pdo)) {
        throw new Exception("PDO bağlantısı bulunamadı!");
    }
    
    // Mevcut veritabanını göster
    $sql = "SELECT DATABASE() as current_db";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentDb = $result['current_db'];
    
    echo "<div class='success'>✅ Veritabanı bağlantısı başarılı: <strong>{$currentDb}</strong></div>";
    
    // 3. Tüm tabloları listele
    echo "<h2>3. Mevcut Tablolar</h2>";
    
    $sql = "SHOW TABLES";
    $stmt = $pdo->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'>";
    echo "<h3>Toplam " . count($tables) . " tablo bulundu:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $isImportant = in_array($table, ['users', 'user_email_preferences', 'email_templates', 'file_chats']);
        $marker = $isImportant ? "🔑 " : "";
        echo "<li>{$marker}<strong>{$table}</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 4. user_email_preferences tablosunu kontrol et
    if (in_array('user_email_preferences', $tables)) {
        echo "<h2>4. ✅ user_email_preferences Tablosu Mevcut</h2>";
        
        // Tablo yapısı
        $sql = "DESCRIBE user_email_preferences";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>";
        echo "<h3>Tablo Yapısı:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            $isImportant = in_array($column['Field'], ['id', 'user_id', 'chat_message_notifications']);
            $marker = $isImportant ? "🔑 " : "";
            echo "<li>{$marker}<strong>{$column['Field']}</strong> - {$column['Type']} - Default: " . ($column['Default'] ?? 'NULL') . "</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        // Kayıt sayıları
        $sql = "SELECT COUNT(*) as total FROM user_email_preferences";
        $stmt = $pdo->query($sql);
        $total = $stmt->fetchColumn();
        
        $sql = "SELECT COUNT(*) as chat_enabled FROM user_email_preferences WHERE chat_message_notifications = 1";
        $stmt = $pdo->query($sql);
        $chatEnabled = $stmt->fetchColumn();
        
        echo "<div class='info'>";
        echo "<h3>📊 Email Tercihleri İstatistikleri:</h3>";
        echo "<ul>";
        echo "<li>Toplam kayıt: <strong>{$total}</strong></li>";
        echo "<li>Chat bildirimi açık: <strong>{$chatEnabled}</strong></li>";
        echo "<li>Kapsama oranı: <strong>" . round(($chatEnabled/$total)*100, 1) . "%</strong></li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        throw new Exception("user_email_preferences tablosu bulunamadı!");
    }
    
    // 5. email_templates tablosunu kontrol et
    if (in_array('email_templates', $tables)) {
        echo "<h2>5. ✅ email_templates Tablosu Mevcut</h2>";
        
        // Template yapısını tespit et
        $sql = "DESCRIBE email_templates";
        $stmt = $pdo->query($sql);
        $templateColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $templateColumnNames = array_column($templateColumns, 'Field');
        
        $nameField = in_array('template_key', $templateColumnNames) ? 'template_key' : 'name';
        $contentField = in_array('body', $templateColumnNames) ? 'body' : 'content';
        
        echo "<div class='info'>";
        echo "<h3>Template Yapısı:</h3>";
        echo "<ul>";
        echo "<li>Ad alanı: <strong>{$nameField}</strong></li>";
        echo "<li>İçerik alanı: <strong>{$contentField}</strong></li>";
        echo "</ul>";
        echo "</div>";
        
        // Mevcut template'leri kontrol et
        $sql = "SELECT {$nameField}, subject FROM email_templates";
        $stmt = $pdo->query($sql);
        $existingTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>";
        echo "<h3>Mevcut Template'ler (" . count($existingTemplates) . "):</h3>";
        echo "<ul>";
        foreach ($existingTemplates as $template) {
            $templateName = $template[$nameField];
            $isChat = strpos($templateName, 'chat_message_') === 0;
            $marker = $isChat ? "💬 " : "";
            echo "<li>{$marker}<strong>{$templateName}</strong> - {$template['subject']}</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        // Chat template'leri var mı kontrol et
        $sql = "SELECT COUNT(*) FROM email_templates WHERE {$nameField} LIKE 'chat_message_%'";
        $stmt = $pdo->query($sql);
        $chatTemplatesCount = $stmt->fetchColumn();
        
        if ($chatTemplatesCount >= 2) {
            echo "<div class='success'>✅ Chat template'leri mevcut ({$chatTemplatesCount}/2)</div>";
        } else {
            echo "<div class='warning'>⚠️ Chat template'leri eksik! ({$chatTemplatesCount}/2)</div>";
            
            // Eksik template'leri ekle
            echo "<h3>Chat Template'leri Ekleniyor:</h3>";
            
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
        <strong>Mr ECU</strong> - Profesyonel ECU Tuning Hizmetleri
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
        <strong>Mr ECU</strong> - Profesyonel ECU Tuning Hizmetleri
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
                    // Template'i ekle
                    if ($nameField === 'template_key') {
                        // Hosting yapısına uygun (GUID ID ile)
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
                        // Standart yapı
                        $sql = "INSERT INTO email_templates ({$nameField}, subject, {$contentField}, variables, is_active) VALUES (?, ?, ?, ?, 1)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$templateName, $templateData['subject'], $templateData['content'], $templateData['variables']]);
                    }
                    
                    echo "<div class='success'>✅ {$templateName} template'i eklendi.</div>";
                } else {
                    echo "<div class='info'>ℹ️ {$templateName} template'i zaten mevcut.</div>";
                }
            }
        }
        
    } else {
        echo "<div class='error'>❌ email_templates tablosu bulunamadı!</div>";
    }
    
    // 6. file_chats tablosunu kontrol et
    echo "<h2>6. Chat Sistemi Kontrol</h2>";
    
    if (in_array('file_chats', $tables)) {
        $sql = "SELECT COUNT(*) FROM file_chats";
        $stmt = $pdo->query($sql);
        $chatCount = $stmt->fetchColumn();
        
        echo "<div class='success'>✅ file_chats tablosu mevcut - {$chatCount} mesaj kayıtlı</div>";
    } else {
        echo "<div class='warning'>⚠️ file_chats tablosu bulunamadı - Chat sistemi çalışmayabilir</div>";
    }
    
    // 7. SMTP ayarlarını kontrol et
    echo "<h2>7. Email Sistemi Kontrol</h2>";
    
    $smtpConfig = [
        'SMTP_HOST' => getenv('SMTP_HOST') ?: 'Tanımlı değil',
        'SMTP_PORT' => getenv('SMTP_PORT') ?: 'Tanımlı değil',
        'SMTP_USERNAME' => getenv('SMTP_USERNAME') ?: 'Tanımlı değil',
        'SMTP_FROM_EMAIL' => getenv('SMTP_FROM_EMAIL') ?: 'Tanımlı değil'
    ];
    
    echo "<div class='info'>";
    echo "<h3>SMTP Ayarları:</h3>";
    echo "<ul>";
    foreach ($smtpConfig as $key => $value) {
        $status = $value !== 'Tanımlı değil' ? '✅' : '❌';
        echo "<li>{$status} <strong>{$key}:</strong> {$value}</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 8. Final durum raporu
    echo "<h2>8. 🎯 Chat Email Bildirimleri Durum Raporu</h2>";
    
    $requirements = [
        'user_email_preferences tablosu' => in_array('user_email_preferences', $tables),
        'chat_message_notifications alanı' => $chatEnabled > 0,
        'email_templates tablosu' => in_array('email_templates', $tables),
        'Chat template\'leri' => $chatTemplatesCount >= 2,
        'file_chats tablosu' => in_array('file_chats', $tables),
        'SMTP ayarları' => getenv('SMTP_HOST') && getenv('SMTP_USERNAME')
    ];
    
    $readyCount = count(array_filter($requirements));
    $totalCount = count($requirements);
    
    echo "<div class='" . ($readyCount == $totalCount ? 'success' : 'warning') . "'>";
    echo "<h3>Sistem Hazırlık Durumu: {$readyCount}/{$totalCount}</h3>";
    echo "<ul>";
    foreach ($requirements as $req => $status) {
        $icon = $status ? '✅' : '❌';
        echo "<li>{$icon} {$req}</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    if ($readyCount == $totalCount) {
        echo "<div class='success'>";
        echo "<h3>🎉 Chat Email Bildirimleri Tamamen Hazır!</h3>";
        echo "<p>Hosting ortamında chat email bildirimleri çalışmaya hazır durumda.</p>";
        echo "<ul>";
        echo "<li>✅ Tüm gerekli tablolar mevcut</li>";
        echo "<li>✅ Email template'leri kurulu</li>";
        echo "<li>✅ {$chatEnabled} kullanıcı chat bildirimlerini etkinleştirmiş</li>";
        echo "<li>✅ Chat sistemi hazır</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>🔄 Test Adımları:</h3>";
        echo "<ol>";
        echo "<li>Kullanıcı hesabı ile giriş yapın</li>";
        echo "<li>Bir dosya detay sayfasına gidin</li>";
        echo "<li>Chat bölümünden admin'e mesaj gönderin</li>";
        echo "<li>Admin email adresini kontrol edin</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Eksik Gereksinimler</h3>";
        echo "<p>Chat email bildirimleri için bazı gereksinimler eksik.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Hosting Ortamı Hatası</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
    echo "</div>";
    error_log("Hosting chat email setup error: " . $e->getMessage());
}

echo "</body></html>";
?>
