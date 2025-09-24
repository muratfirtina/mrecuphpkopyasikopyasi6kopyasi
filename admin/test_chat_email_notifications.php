<?php
/**
 * Mr ECU - Chat Email Bildirimlerini Test Script'i
 * Bu script, chat email bildirim sisteminin çalışıp çalışmadığını test eder
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/ChatManager.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Chat Email Bildirimlerini Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;} ";
echo ".success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>";
echo "</head><body>";

echo "<h1>🧪 Chat Email Bildirimleri Test</h1>";

try {
    // 1. ChatManager test
    echo "<h2>1. ChatManager Sınıfı Test</h2>";
    
    $chatManager = new ChatManager($pdo);
    echo "<p class='success'>✅ ChatManager başarıyla yüklendi.</p>";
    
    // 2. Email sistem kontrolü
    echo "<h2>2. Email Sistemi Kontrolü</h2>";
    
    // Email konfigürasyon kontrol
    $emailConfig = [
        'SMTP_HOST' => getenv('SMTP_HOST'),
        'SMTP_PORT' => getenv('SMTP_PORT'),
        'SMTP_USERNAME' => getenv('SMTP_USERNAME'),
        'FROM_EMAIL' => getenv('SMTP_FROM_EMAIL')
    ];
    
    foreach ($emailConfig as $key => $value) {
        if (!empty($value)) {
            echo "<p class='success'>✅ {$key}: {$value}</p>";
        } else {
            echo "<p class='error'>❌ {$key} tanımlı değil!</p>";
        }
    }
    
    // 3. Veritabanı kontrolleri
    echo "<h2>3. Veritabanı Kontrolü</h2>";
    
    // chat_message_notifications alanı kontrol
    $sql = "SHOW COLUMNS FROM user_email_preferences LIKE 'chat_message_notifications'";
    $stmt = $pdo->query($sql);
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ user_email_preferences.chat_message_notifications alanı mevcut.</p>";
    } else {
        echo "<p class='error'>❌ user_email_preferences.chat_message_notifications alanı eksik!</p>";
        echo "<p class='info'>💡 setup_chat_email_notifications.php dosyasını çalıştırın.</p>";
    }
    
    // Email template'leri kontrol
    $stmt = $pdo->query("SELECT name FROM email_templates WHERE name LIKE 'chat_message_%'");
    $templates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($templates) >= 2) {
        echo "<p class='success'>✅ Chat email template'leri mevcut: " . implode(', ', $templates) . "</p>";
    } else {
        echo "<p class='warning'>⚠️ Chat email template'leri eksik veya eksik!</p>";
        echo "<p class='info'>💡 setup_chat_email_notifications.php dosyasını çalıştırın.</p>";
    }
    
    // 4. Test kullanıcı ve dosya kontrolleri
    echo "<h2>4. Test Verileri</h2>";
    
    // Test kullanıcı var mı?
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email_verified = 1");
    $verifiedUserCount = $stmt->fetchColumn();
    echo "<p class='info'>ℹ️ {$verifiedUserCount} doğrulanmış email adresi olan kullanıcı.</p>";
    
    // Test dosya var mı?
    $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads");
    $fileCount = $stmt->fetchColumn();
    echo "<p class='info'>ℹ️ {$fileCount} test dosyası mevcut.</p>";
    
    // 5. Chat tablosu kontrol
    echo "<h2>5. Chat Sistemi Kontrolü</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM file_chats");
    $chatCount = $stmt->fetchColumn();
    echo "<p class='info'>ℹ️ {$chatCount} chat mesajı mevcut.</p>";
    
    // 6. Email tercihlerini kontrol et
    echo "<h2>6. Email Tercihleri Kontrol</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_email_preferences WHERE chat_message_notifications = 1");
    $enabledCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_email_preferences");
    $totalPrefs = $stmt->fetchColumn();
    
    echo "<p class='info'>ℹ️ {$enabledCount}/{$totalPrefs} kullanıcı chat email bildirimlerini aktif etmiş.</p>";
    
    // 7. Manuel test linkler
    echo "<h2>7. Manuel Test</h2>";
    
    echo "<div style='background:#f0f8ff;padding:15px;border-radius:8px;margin:15px 0;'>";
    echo "<h3>Test Adımları:</h3>";
    echo "<ol>";
    echo "<li><strong>Veritabanı Setup:</strong> ";
    echo "<a href='setup_chat_email_notifications.php' target='_blank'>Setup Script'i Çalıştır</a></li>";
    echo "<li><strong>Email Tercihleri:</strong> ";
    echo "Kullanıcı panelinden email tercihlerine git ve chat bildirimlerini kontrol et</li>";
    echo "<li><strong>Chat Test:</strong> ";
    echo "Bir dosya detay sayfasında chat mesajı gönder</li>";
    echo "<li><strong>Email Kontrol:</strong> ";
    echo "Alıcının email adresini kontrol et</li>";
    echo "</ol>";
    echo "</div>";
    
    // 8. Geliştirici bilgileri
    echo "<h2>8. Geliştirici Bilgileri</h2>";
    echo "<div style='background:#f9f9f9;padding:15px;border-radius:8px;'>";
    echo "<h4>Chat Email Bildirimi Akışı:</h4>";
    echo "<ul>";
    echo "<li>1️⃣ Kullanıcı/Admin chat mesajı gönderir</li>";
    echo "<li>2️⃣ <code>ChatManager::sendMessage()</code> çağrılır</li>";
    echo "<li>3️⃣ <code>ChatManager::sendChatNotification()</code> tetiklenir</li>";
    echo "<li>4️⃣ Sistem içi bildirim oluşturulur</li>";
    echo "<li>5️⃣ <code>ChatManager::sendChatEmailNotification()</code> çağrılır</li>";
    echo "<li>6️⃣ Alıcının email tercihleri kontrol edilir</li>";
    echo "<li>7️⃣ Email template hazırlanır ve gönderilir</li>";
    echo "</ul>";
    
    echo "<h4>Log Dosyaları:</h4>";
    echo "<ul>";
    echo "<li><code>../logs/email_test.log</code> - Email gönderim logları</li>";
    echo "<li>PHP error log - Genel hata logları</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin-top:30px;padding:15px;background:#d4edda;border-radius:8px;'>";
    echo "<h3 style='color:green;'>🎉 Sistem Hazır!</h3>";
    echo "<p>Chat email bildirimleri sistemi kuruldu ve test edilmeye hazır.</p>";
    echo "<p><strong>Not:</strong> Gerçek email gönderimi için SMTP ayarlarının doğru yapılandırıldığından emin olun.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;'>";
    echo "<h3>❌ Test Hatası</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
    echo "</div>";
}

echo "</body></html>";
?>
