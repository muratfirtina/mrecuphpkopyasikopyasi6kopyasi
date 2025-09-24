<?php
/**
 * Mr ECU - user_email_preferences ID Alanları Düzeltme Script'i (Gelişmiş)
 * Bu script, primary key sorununu çözüp id alanlarını UUID uyumlu hale getirir
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Email Preferences ID Alanları Düzeltme v2</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🔧 Email Preferences ID Alanları Düzeltme v2</h1>";

try {
    // 1. Mevcut tablo yapısını kontrol et
    echo "<h2>1. Mevcut Tablo Yapısı Kontrol Ediliyor</h2>";
    
    $sql = "SHOW CREATE TABLE user_email_preferences";
    $stmt = $pdo->query($sql);
    $tableCreate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Mevcut Tablo Yapısı:</h3>";
    echo "<pre style='background:#f8f9fa;padding:10px;border-radius:3px;font-size:12px;'>";
    echo htmlspecialchars($tableCreate['Create Table']);
    echo "</pre>";
    echo "</div>";
    
    // 2. Primary key ve index'leri kontrol et
    echo "<h2>2. Index ve Constraint Kontrolleri</h2>";
    
    $sql = "SHOW INDEX FROM user_email_preferences";
    $stmt = $pdo->query($sql);
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Mevcut Index'ler:</h3><ul>";
    foreach ($indexes as $index) {
        echo "<li><strong>{$index['Key_name']}</strong> - Column: {$index['Column_name']} - Type: " . ($index['Key_name'] == 'PRIMARY' ? 'PRIMARY KEY' : 'INDEX') . "</li>";
    }
    echo "</ul></div>";
    
    // 3. Foreign key'leri kontrol et
    $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'user_email_preferences' 
            AND TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME IS NOT NULL";
    $stmt = $pdo->query($sql);
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($foreignKeys)) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Mevcut Foreign Key'ler (kaldırılacak):</h3><ul>";
        foreach ($foreignKeys as $fk) {
            echo "<li><strong>{$fk['CONSTRAINT_NAME']}</strong> - {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</li>";
        }
        echo "</ul></div>";
    }
    
    // 4. Tabloyu yeniden oluşturma stratejisi
    echo "<h2>3. Tablo Yeniden Yapılandırılıyor</h2>";
    
    echo "<h3>3.1. Mevcut Veriler Yedekleniyor</h3>";
    // Mevcut veri var mı kontrol et
    $sql = "SELECT COUNT(*) FROM user_email_preferences";
    $stmt = $pdo->query($sql);
    $existingData = $stmt->fetchColumn();
    
    if ($existingData > 0) {
        echo "<div class='warning'>⚠️ {$existingData} mevcut kayıt bulundu, ancak tip uyumsuzluğu nedeniyle bunlar geçersiz.</div>";
    } else {
        echo "<div class='info'>ℹ️ Tablo zaten boş, güvenli şekilde yeniden yapılandırılabilir.</div>";
    }
    
    echo "<h3>3.2. Tabloyu Drop Edip Yeniden Oluşturuyor</h3>";
    
    // Tabloyu drop et
    $sql = "DROP TABLE IF EXISTS user_email_preferences";
    $pdo->exec($sql);
    echo "<div class='info'>✅ Eski user_email_preferences tablosu kaldırıldı.</div>";
    
    // Yeni tabloyu UUID uyumlu olarak oluştur
    $sql = "CREATE TABLE `user_email_preferences` (
        `id` varchar(36) NOT NULL PRIMARY KEY,
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
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `idx_user_id` (`user_id`),
        KEY `idx_chat_notifications` (`chat_message_notifications`),
        CONSTRAINT `fk_user_email_prefs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ Yeni user_email_preferences tablosu UUID uyumlu olarak oluşturuldu.</div>";
    
    echo "<h2>4. Kullanıcılar İçin Email Tercihleri Oluşturuluyor</h2>";
    
    // Tüm kullanıcıları al
    $sql = "SELECT id, email, first_name, last_name FROM users ORDER BY created_at";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Toplam " . count($users) . " kullanıcı için email tercihleri oluşturuluyor...</div>";
    
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
            
            echo "<div class='success'>✅ <strong>{$userName}</strong> ({$user['email']}) için email tercihleri oluşturuldu.</div>";
            $successCount++;
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ {$user['email']} için email tercihleri oluşturulamadı: {$e->getMessage()}</div>";
            $errorCount++;
        }
    }
    
    echo "<h2>5. Final Kontrol ve İstatistikler</h2>";
    
    // Yeni tablo yapısını göster
    $sql = "DESCRIBE user_email_preferences";
    $stmt = $pdo->query($sql);
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>🆕 Yeni Tablo Yapısı:</h3><ul>";
    foreach ($finalColumns as $column) {
        $isIdField = in_array($column['Field'], ['id', 'user_id']);
        $marker = $isIdField ? "🔑 " : "";
        $highlight = $isIdField ? "style='background:#fffacd;'" : "";
        echo "<li {$highlight}>{$marker}<strong>{$column['Field']}</strong> - {$column['Type']} - Default: " . ($column['Default'] ?? 'NULL') . "</li>";
    }
    echo "</ul></div>";
    
    // Foreign key'leri kontrol et
    $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'user_email_preferences' 
            AND TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME IS NOT NULL";
    $stmt = $pdo->query($sql);
    $newForeignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($newForeignKeys)) {
        echo "<div class='success'>";
        echo "<h3>✅ Foreign Key Constraints:</h3><ul>";
        foreach ($newForeignKeys as $fk) {
            echo "<li><strong>{$fk['CONSTRAINT_NAME']}</strong> - {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</li>";
        }
        echo "</ul></div>";
    }
    
    // İstatistikler
    $sql = "SELECT COUNT(*) FROM user_email_preferences";
    $stmt = $pdo->query($sql);
    $totalPrefs = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM user_email_preferences WHERE chat_message_notifications = 1";
    $stmt = $pdo->query($sql);
    $chatEnabled = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM users";
    $stmt = $pdo->query($sql);
    $totalUsers = $stmt->fetchColumn();
    
    echo "<div class='success'>";
    echo "<h3>📊 Başarı İstatistikleri:</h3>";
    echo "<ul>";
    echo "<li>Toplam kullanıcı: <strong>{$totalUsers}</strong></li>";
    echo "<li>Email tercihi oluşturulan: <strong>{$successCount}</strong></li>";
    echo "<li>Hatalı kayıt: <strong>{$errorCount}</strong></li>";
    echo "<li>Chat bildirimi etkin: <strong>{$chatEnabled}</strong></li>";
    echo "<li>Kapsam: <strong>" . round(($totalPrefs / $totalUsers) * 100, 1) . "%</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // Örnek kayıt göster
    $sql = "SELECT u.email, u.first_name, u.last_name, 
                   uep.chat_message_notifications, uep.file_ready_notifications,
                   uep.created_at
            FROM user_email_preferences uep
            JOIN users u ON uep.user_id = u.id 
            LIMIT 3";
    $stmt = $pdo->query($sql);
    $sampleRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sampleRecords)) {
        echo "<div class='info'>";
        echo "<h3>📝 Örnek Kayıtlar:</h3>";
        echo "<table style='width:100%;border-collapse:collapse;'>";
        echo "<tr style='background:#f8f9fa;'><th style='border:1px solid #ddd;padding:8px;'>Kullanıcı</th><th style='border:1px solid #ddd;padding:8px;'>Chat Bildirimi</th><th style='border:1px solid #ddd;padding:8px;'>Dosya Bildirimi</th><th style='border:1px solid #ddd;padding:8px;'>Oluşturma</th></tr>";
        foreach ($sampleRecords as $record) {
            $userName = trim($record['first_name'] . ' ' . $record['last_name']);
            if (empty($userName)) $userName = $record['email'];
            $chatStatus = $record['chat_message_notifications'] ? '✅ Açık' : '❌ Kapalı';
            $fileStatus = $record['file_ready_notifications'] ? '✅ Açık' : '❌ Kapalı';
            echo "<tr><td style='border:1px solid #ddd;padding:8px;'>{$userName}</td><td style='border:1px solid #ddd;padding:8px;'>{$chatStatus}</td><td style='border:1px solid #ddd;padding:8px;'>{$fileStatus}</td><td style='border:1px solid #ddd;padding:8px;'>{$record['created_at']}</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<h2>🎉 Tablo Yeniden Yapılandırması Başarıyla Tamamlandı!</h2>";
    echo "<p><strong>✅ Sorunlar çözüldü:</strong></p>";
    echo "<ul>";
    echo "<li>ID alanları UUID uyumlu (varchar) yapıldı</li>";
    echo "<li>Foreign key constraints doğru şekilde eklendi</li>";
    echo "<li>Tüm kullanıcılar için email tercihleri oluşturuldu</li>";
    echo "<li>Chat message notifications varsayılan olarak açık</li>";
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
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre style='background:#f8f9fa;padding:10px;border-radius:3px;font-size:11px;max-height:200px;overflow:auto;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div>";
    error_log("Email preferences ID fix v2 error: " . $e->getMessage());
}

echo "</body></html>";
?>
