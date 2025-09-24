<?php
/**
 * Mr ECU - Email Templates Mevcut Yapıya Uygun Setup
 * Bu script, mevcut email_templates tablosuna chat template'lerini ekler
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Chat Email Templates Kurulum (Mevcut Yapı)</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>💬 Chat Email Templates Kurulum</h1>";

try {
    // 1. Mevcut tablo yapısını onaylayarak başla
    echo "<h2>1. Mevcut Email Templates Yapısı Onaylanıyor</h2>";
    
    $sql = "DESCRIBE email_templates";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    echo "<div class='info'>";
    echo "<h3>Tespit Edilen Alanlar:</h3><ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']}</li>";
    }
    echo "</ul></div>";
    
    // Alan adlarını kontrol et
    $hasTemplateKey = in_array('template_key', $columnNames);
    $hasName = in_array('name', $columnNames);
    $hasBody = in_array('body', $columnNames);
    $hasContent = in_array('content', $columnNames);
    
    echo "<div class='info'>";
    echo "📋 <strong>Alan Kontrolü:</strong><br>";
    echo "• Template adı alanı: " . ($hasTemplateKey ? "✅ template_key" : ($hasName ? "✅ name" : "❌ Yok")) . "<br>";
    echo "• İçerik alanı: " . ($hasBody ? "✅ body" : ($hasContent ? "✅ content" : "❌ Yok")) . "<br>";
    echo "</div>";
    
    if (!$hasTemplateKey && !$hasName) {
        throw new Exception("Template adı alanı bulunamadı!");
    }
    if (!$hasBody && !$hasContent) {
        throw new Exception("İçerik alanı bulunamadı!");
    }
    
    // Mevcut şablonları listele
    echo "<h2>2. Mevcut Template'ler</h2>";
    
    $nameField = $hasTemplateKey ? 'template_key' : 'name';
    $contentField = $hasBody ? 'body' : 'content';
    
    $sql = "SELECT {$nameField}, subject, is_active FROM email_templates";
    $stmt = $pdo->query($sql);
    $existingTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Mevcut Template'ler (" . count($existingTemplates) . "):</h3>";
    if (!empty($existingTemplates)) {
        echo "<ul>";
        foreach ($existingTemplates as $template) {
            $status = $template['is_active'] ? "🟢 Aktif" : "🔴 Pasif";
            echo "<li><strong>{$template[$nameField]}</strong> - {$template['subject']} ({$status})</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // 3. Chat template'leri kontrol et
    echo "<h2>3. Chat Template'leri Kontrol Ediliyor</h2>";
    
    $chatTemplates = [
        'chat_message_admin' => [
            'subject' => 'Yeni Kullanıcı Mesajı - {{file_name}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
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
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
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
    
    foreach ($chatTemplates as $templateKey => $templateData) {
        // Template'in var olup olmadığını kontrol et
        $sql = "SELECT COUNT(*) FROM email_templates WHERE {$nameField} = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$templateKey]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo "<div class='success'>✅ {$templateKey} template'i zaten mevcut.</div>";
        } else {
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
            $stmt->execute([
                $templateId,
                $templateKey,
                $templateData['subject'],
                $templateData['body'],
                $templateData['variables']
            ]);
            
            echo "<div class='success'>✅ {$templateKey} template'i başarıyla eklendi (ID: {$templateId}).</div>";
        }
    }
    
    // 4. Final kontrol
    echo "<h2>4. 📊 Template Sistemi Final Kontrol</h2>";
    
    $sql = "SELECT COUNT(*) FROM email_templates";
    $stmt = $pdo->query($sql);
    $totalTemplates = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM email_templates WHERE {$nameField} LIKE 'chat_message_%'";
    $stmt = $pdo->query($sql);
    $chatTemplatesCount = $stmt->fetchColumn();
    
    $sql = "SELECT {$nameField}, subject, is_active FROM email_templates ORDER BY {$nameField}";
    $stmt = $pdo->query($sql);
    $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>";
    echo "<h3>🎉 Email Template Sistemi Hazır!</h3>";
    echo "<ul>";
    echo "<li>Toplam template: <strong>{$totalTemplates}</strong></li>";
    echo "<li>Chat template'leri: <strong>{$chatTemplatesCount}/2</strong></li>";
    echo "<li>Aktif template'ler: <strong>" . count(array_filter($allTemplates, function($t) { return $t['is_active']; })) . "</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📋 Tüm Template'ler:</h3>";
    echo "<table style='width:100%;border-collapse:collapse;'>";
    echo "<tr style='background:#f8f9fa;'><th style='border:1px solid #ddd;padding:8px;'>Template Key</th><th style='border:1px solid #ddd;padding:8px;'>Subject</th><th style='border:1px solid #ddd;padding:8px;'>Durum</th></tr>";
    foreach ($allTemplates as $template) {
        $status = $template['is_active'] ? '<span style="color:green;">✅ Aktif</span>' : '<span style="color:red;">❌ Pasif</span>';
        $isChat = strpos($template[$nameField], 'chat_message_') === 0;
        $rowStyle = $isChat ? "background:#e8f5e8;" : "";
        echo "<tr style='{$rowStyle}'><td style='border:1px solid #ddd;padding:8px;'><strong>{$template[$nameField]}</strong></td><td style='border:1px solid #ddd;padding:8px;'>{$template['subject']}</td><td style='border:1px solid #ddd;padding:8px;'>{$status}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 5. ChatManager uyumluluk bilgisi
    echo "<h2>5. 🔧 ChatManager Uyumluluk Bilgisi</h2>";
    
    echo "<div class='warning'>";
    echo "<h3>⚠️ Önemli: ChatManager.php Güncellemesi Gerekli</h3>";
    echo "<p>Email template'leri mevcut yapıya göre eklendi, ancak <strong>ChatManager.php</strong> dosyasında aşağıdaki değişiklikleri yapmanız gerekiyor:</p>";
    echo "<ol>";
    echo "<li>Template sorgularında <code>name</code> yerine <code>template_key</code> kullanın</li>";
    echo "<li>Template içeriğinde <code>content</code> yerine <code>body</code> kullanın</li>";
    echo "<li>Variables alanı <code>JSON</code> değil <code>TEXT</code> formatında</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔄 Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li><strong>ChatManager.php'yi güncelleyin</strong> (alan adları için)</li>";
    echo "<li><a href='setup_chat_email_notifications.php'>Chat Email Bildirimleri Setup</a> script'ini çalıştırın</li>";
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
    error_log("Chat email templates setup error: " . $e->getMessage());
}

echo "</body></html>";
?>
