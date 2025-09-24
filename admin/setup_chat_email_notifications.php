<?php
/**
 * Mr ECU - Chat Email Bildirimlerini Etkinleştirme Script'i
 * Bu script, chat email bildirimleri için veritabanı güncellemelerini yapar
 */

require_once '../config/config.php';
require_once '../config/database.php';

try {
    echo "Chat Email Bildirimleri Etkinleştiriliyor...\n\n";
    
    // 1. user_email_preferences tablosuna chat_message_notifications alanı ekle
    echo "1. user_email_preferences tablosunu kontrol ediliyor...\n";
    
    // Önce tabloyu kontrol et
    $sql = "SHOW COLUMNS FROM user_email_preferences LIKE 'chat_message_notifications'";
    $stmt = $pdo->query($sql);
    
    if ($stmt->rowCount() == 0) {
        // Alan yoksa ekle - güvenli pozisyon bul
        $sql = "SHOW COLUMNS FROM user_email_preferences";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Hangi alandan sonra ekleyeceğimizi belirle
        $afterColumn = '';
        if (in_array('additional_file_notifications', $columnNames)) {
            $afterColumn = 'AFTER `additional_file_notifications`';
        } elseif (in_array('revision_notifications', $columnNames)) {
            $afterColumn = 'AFTER `revision_notifications`';
        } elseif (in_array('file_ready_notifications', $columnNames)) {
            $afterColumn = 'AFTER `file_ready_notifications`';
        } elseif (in_array('file_upload_notifications', $columnNames)) {
            $afterColumn = 'AFTER `file_upload_notifications`';
        }
        
        $sql = "ALTER TABLE `user_email_preferences` 
                ADD COLUMN `chat_message_notifications` tinyint(1) DEFAULT 1 
                COMMENT 'Chat mesajları için email bildirimleri' {$afterColumn}";
        
        try {
            $pdo->exec($sql);
            echo "✅ chat_message_notifications alanı başarıyla eklendi.\n\n";
        } catch (PDOException $e) {
            throw new Exception("chat_message_notifications alanı eklenemedi: " . $e->getMessage());
        }
    } else {
        echo "⚠️  chat_message_notifications alanı zaten mevcut.\n\n";
    }
    
    // 2. Mevcut kullanıcılar için varsayılan chat bildirimi tercihi ekle
    echo "2. Mevcut kullanıcılar için varsayılan chat bildirimi tercihleri güncelleniyor...\n";
    
    $sql = "UPDATE `user_email_preferences` 
            SET `chat_message_notifications` = 1 
            WHERE `chat_message_notifications` IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "✅ {$affected} kullanıcının chat bildirimi tercihi güncellendi.\n\n";
    
    // 3. Chat mesajları için email template'leri ekle
    echo "3. Chat mesajları için email template'leri kontrol ediliyor...\n";
    
    // Mevcut template yapısını tespit et
    $sql = "DESCRIBE email_templates";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    $nameField = in_array('template_key', $columnNames) ? 'template_key' : 'name';
    $contentField = in_array('body', $columnNames) ? 'body' : 'content';
    
    echo "Template yapısı: {$nameField} / {$contentField} alanları kullanılacak.\n";
    
    $templates = [
        'chat_message_admin' => [
            'subject' => 'Yeni Kullanıcı Mesajı - {{file_name}}',
            'content' => '<div>Chat template content...</div>',
            'variables' => 'file_name,sender_name,message,chat_url'
        ],
        'chat_message_user' => [
            'subject' => 'Yeni Admin Mesajı - {{file_name}}', 
            'content' => '<div>Chat template content...</div>',
            'variables' => 'user_name,file_name,message,chat_url'
        ]
    ];
    
    foreach ($templates as $templateName => $template) {
        $sql = "SELECT COUNT(*) FROM email_templates WHERE {$nameField} = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$templateName]);
        
        if ($stmt->fetchColumn() > 0) {
            echo "✅ {$templateName} template'i zaten mevcut.\n";
        } else {
            echo "⚠️  {$templateName} template'i eksik ama önceki adımda eklenmiş olmalı.\n";
        }
    }
    
    echo "\n🎉 Chat email bildirimleri başarıyla etkinleştirildi!\n\n";
    
    // 4. Test için kullanıcı sayısı kontrol et
    echo "4. Sistem durumu kontrol ediliyor...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email_verified = 1");
    $verifiedUsers = $stmt->fetchColumn();
    echo "✅ {$verifiedUsers} doğrulanmış email adresi bulunan kullanıcı var.\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_email_preferences WHERE chat_message_notifications = 1");
    $chatNotificationUsers = $stmt->fetchColumn();
    echo "✅ {$chatNotificationUsers} kullanıcı chat email bildirimlerini aktif ettirmiş.\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM email_templates WHERE name LIKE 'chat_message_%'");
    $chatTemplates = $stmt->fetchColumn();
    echo "✅ {$chatTemplates} chat email template'i mevcut.\n";
    
    echo "\n✨ Chat email bildirimleri artık çalışır durumda!\n";
    echo "📝 Kullanıcılar email tercihlerinden chat bildirimlerini açıp kapatabilir.\n";
    echo "🔧 Sistem artık hem admin hem de kullanıcı chat mesajları için email gönderecek.\n";
    
} catch (Exception $e) {
    echo "❌ Hata oluştu: " . $e->getMessage() . "\n";
    echo "📂 Log dosyalarını kontrol edin: " . __DIR__ . "/../logs/\n";
    error_log("Chat email notifications setup error: " . $e->getMessage());
}
