<?php
/**
 * Mr ECU - user_email_preferences ID Alanları Düzeltme Script'i
 * Bu script, id ve user_id alanlarını UUID uyumlu hale getirir
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Email Preferences ID Alanları Düzeltme</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🔧 Email Preferences ID Alanları Düzeltme</h1>";

try {
    // 1. users tablosunun id formatını kontrol et
    echo "<h2>1. Users Tablosu Kontrol Ediliyor</h2>";
    
    $sql = "DESCRIBE users";
    $stmt = $pdo->query($sql);
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $userIdType = null;
    foreach ($userColumns as $column) {
        if ($column['Field'] === 'id') {
            $userIdType = $column['Type'];
            break;
        }
    }
    
    echo "<div class='info'>";
    echo "<strong>users.id</strong> alanının tipi: <code>{$userIdType}</code>";
    echo "</div>";
    
    // 2. user_email_preferences tablosunun yapısını kontrol et
    echo "<h2>2. user_email_preferences Tablo Yapısı</h2>";
    
    $sql = "DESCRIBE user_email_preferences";
    $stmt = $pdo->query($sql);
    $prefColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $prefIdType = null;
    $prefUserIdType = null;
    
    foreach ($prefColumns as $column) {
        if ($column['Field'] === 'id') {
            $prefIdType = $column['Type'];
        } elseif ($column['Field'] === 'user_id') {
            $prefUserIdType = $column['Type'];
        }
    }
    
    echo "<div class='info'>";
    echo "<strong>user_email_preferences.id</strong> tipi: <code>{$prefIdType}</code><br>";
    echo "<strong>user_email_preferences.user_id</strong> tipi: <code>{$prefUserIdType}</code>";
    echo "</div>";
    
    // 3. Tip uyumsuzluğu kontrolü
    $needsFixing = false;
    
    if (strpos($userIdType, 'varchar') !== false && strpos($prefUserIdType, 'int') !== false) {
        echo "<div class='warning'>";
        echo "⚠️ Tip uyumsuzluğu tespit edildi!<br>";
        echo "users.id = varchar (UUID), user_email_preferences.user_id = int";
        echo "</div>";
        $needsFixing = true;
    }
    
    if (strpos($prefIdType, 'int') !== false) {
        echo "<div class='warning'>";
        echo "⚠️ user_email_preferences.id alanı int formatında, UUID için varchar olmalı!";
        echo "</div>";
        $needsFixing = true;
    }
    
    if ($needsFixing) {
        echo "<h2>3. ID Alanları Düzeltiliyor</h2>";
        
        // Önce mevcut veriler varsa temizle
        echo "<h3>3.1. Mevcut Veriler Temizleniyor</h3>";
        $sql = "TRUNCATE TABLE user_email_preferences";
        $pdo->exec($sql);
        echo "<div class='info'>✅ user_email_preferences tablosu temizlendi.</div>";
        
        // id alanını varchar yap
        echo "<h3>3.2. id Alanı UUID Uyumlu Hale Getiriliyor</h3>";
        $sql = "ALTER TABLE user_email_preferences MODIFY COLUMN id varchar(36) NOT NULL PRIMARY KEY";
        $pdo->exec($sql);
        echo "<div class='success'>✅ id alanı varchar(36) yapıldı.</div>";
        
        // user_id alanını varchar yap
        echo "<h3>3.3. user_id Alanı UUID Uyumlu Hale Getiriliyor</h3>";
        $sql = "ALTER TABLE user_email_preferences MODIFY COLUMN user_id varchar(36) NOT NULL";
        $pdo->exec($sql);
        echo "<div class='success'>✅ user_id alanı varchar(36) yapıldı.</div>";
        
        echo "<h2>4. Foreign Key Constraint Ekleniyor</h2>";
        try {
            $sql = "ALTER TABLE user_email_preferences 
                    ADD CONSTRAINT fk_user_email_prefs_user_id 
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
            $pdo->exec($sql);
            echo "<div class='success'>✅ Foreign key constraint başarıyla eklendi.</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "<div class='warning'>⚠️ Foreign key constraint eklenemedi: {$e->getMessage()}</div>";
            } else {
                echo "<div class='info'>ℹ️ Foreign key constraint zaten mevcut.</div>";
            }
        }
        
        echo "<h2>5. Kullanıcılar İçin Email Tercihleri Oluşturuluyor</h2>";
        
        // Tüm kullanıcıları al
        $sql = "SELECT id, email FROM users ORDER BY created_at";
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>Toplam {count($users)} kullanıcı için email tercihleri oluşturuluyor...</div>";
        
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
                         marketing_emails, security_notifications) 
                        VALUES (?, ?, 1, 1, 1, 1, 1, 1, 0, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$prefId, $user['id']]);
                
                echo "<div class='success'>✅ {$user['email']} için email tercihleri oluşturuldu (ID: {$prefId}).</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ {$user['email']} için email tercihleri oluşturulamadı: {$e->getMessage()}</div>";
            }
        }
        
    } else {
        echo "<div class='success'>";
        echo "<h2>✅ Tablo Yapısı Doğru!</h2>";
        echo "<p>ID alanları zaten UUID uyumlu.</p>";
        echo "</div>";
    }
    
    // 6. Son kontrol
    echo "<h2>6. Final Kontrol</h2>";
    
    // Güncellenmiş yapıyı göster
    $sql = "DESCRIBE user_email_preferences";
    $stmt = $pdo->query($sql);
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Güncellenmiş Tablo Yapısı:</h3><ul>";
    foreach ($finalColumns as $column) {
        $isIdField = in_array($column['Field'], ['id', 'user_id']);
        $marker = $isIdField ? "🔑 " : "";
        echo "<li>{$marker}<strong>{$column['Field']}</strong> - {$column['Type']} - Default: " . ($column['Default'] ?? 'NULL') . "</li>";
    }
    echo "</ul></div>";
    
    // İstatistikler
    $sql = "SELECT COUNT(*) FROM user_email_preferences";
    $stmt = $pdo->query($sql);
    $totalPrefs = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM user_email_preferences WHERE chat_message_notifications = 1";
    $stmt = $pdo->query($sql);
    $chatEnabled = $stmt->fetchColumn();
    
    echo "<div class='success'>";
    echo "<h3>📊 İstatistikler:</h3>";
    echo "<ul>";
    echo "<li>Toplam email tercihi kaydı: <strong>{$totalPrefs}</strong></li>";
    echo "<li>Chat bildirimi etkin: <strong>{$chatEnabled}</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔄 Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li><a href='setup_chat_email_notifications.php'>Chat Email Bildirimleri Setup</a> script'ini çalıştırın</li>";
    echo "<li><a href='test_chat_email_notifications.php'>Test Script</a>'ini çalıştırın</li>";
    echo "<li>Kullanıcı panelinden email tercihlerini kontrol edin</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Kritik Hata</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
    echo "</div>";
    error_log("Email preferences ID fix error: " . $e->getMessage());
}

echo "</body></html>";
?>
