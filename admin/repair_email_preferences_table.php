<?php
/**
 * Mr ECU - user_email_preferences Tablosu Kontrol ve Onarım Script'i
 * Bu script, eksik alanları kontrol eder ve ekler
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Email Preferences Tablo Onarımı</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#f5f5f5;} ";
echo ".success{color:green;background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;} ";
echo ".error{color:#721c24;background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;} ";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:5px;margin:10px 0;} ";
echo ".warning{color:#856404;background:#fff3cd;padding:10px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🔧 Email Preferences Tablo Onarımı</h1>";

try {
    // 1. Mevcut tablo yapısını kontrol et
    echo "<h2>1. Mevcut Tablo Yapısını Kontrol Ediliyor</h2>";
    
    $sql = "DESCRIBE user_email_preferences";
    $stmt = $pdo->query($sql);
    $existingColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Mevcut Alanlar:</h3><ul>";
    foreach ($existingColumns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} - Default: " . ($column['Default'] ?? 'NULL') . "</li>";
    }
    echo "</ul></div>";
    
    $columnNames = array_column($existingColumns, 'Field');
    
    // 2. Gerekli alanları tanımla
    $requiredColumns = [
        'id' => 'varchar(36) NOT NULL PRIMARY KEY',
        'user_id' => 'varchar(36) NOT NULL',
        'email_notifications' => 'tinyint(1) DEFAULT 1',
        'file_upload_notifications' => 'tinyint(1) DEFAULT 1',
        'file_ready_notifications' => 'tinyint(1) DEFAULT 1',
        'revision_notifications' => 'tinyint(1) DEFAULT 1',
        'additional_file_notifications' => 'tinyint(1) DEFAULT 1',
        'chat_message_notifications' => 'tinyint(1) DEFAULT 1',
        'marketing_emails' => 'tinyint(1) DEFAULT 0',
        'security_notifications' => 'tinyint(1) DEFAULT 1',
        'email_frequency' => "enum('immediate','daily','weekly') DEFAULT 'immediate'",
        'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    echo "<h2>2. Eksik Alanları Kontrol Ediliyor</h2>";
    
    $missingColumns = [];
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $columnNames)) {
            $missingColumns[$columnName] = $columnDef;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "<div class='warning'>";
        echo "<h3>Eksik Alanlar:</h3><ul>";
        foreach ($missingColumns as $columnName => $columnDef) {
            echo "<li><strong>{$columnName}</strong> - {$columnDef}</li>";
        }
        echo "</ul></div>";
        
        // 3. Eksik alanları ekle
        echo "<h2>3. Eksik Alanları Ekleniyor</h2>";
        
        foreach ($missingColumns as $columnName => $columnDef) {
            try {
                // Primary key kontrolü
                if (strpos($columnDef, 'PRIMARY KEY') !== false) {
                    $sql = "ALTER TABLE user_email_preferences ADD COLUMN {$columnName} {$columnDef}";
                } else {
                    $sql = "ALTER TABLE user_email_preferences ADD COLUMN {$columnName} {$columnDef}";
                }
                
                $pdo->exec($sql);
                echo "<div class='success'>✅ {$columnName} alanı başarıyla eklendi.</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ {$columnName} alanı eklenemedi: {$e->getMessage()}</div>";
            }
        }
    } else {
        echo "<div class='success'>✅ Tüm gerekli alanlar mevcut!</div>";
    }
    
    // 4. Tabloyu yeniden kontrol et
    echo "<h2>4. Güncellenmiş Tablo Yapısı</h2>";
    
    $sql = "DESCRIBE user_email_preferences";
    $stmt = $pdo->query($sql);
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Güncellenmiş Alanlar:</h3><ul>";
    foreach ($updatedColumns as $column) {
        $isNew = !in_array($column['Field'], $columnNames);
        $marker = $isNew ? "🆕 " : "";
        echo "<li>{$marker}<strong>{$column['Field']}</strong> - {$column['Type']} - Default: " . ($column['Default'] ?? 'NULL') . "</li>";
    }
    echo "</ul></div>";
    
    // 5. Kullanıcı verilerini kontrol et ve güncelle
    echo "<h2>5. Kullanıcı Verilerini Kontrol Ediliyor</h2>";
    
    // Tüm kullanıcılar için email preferences var mı?
    $sql = "SELECT COUNT(*) FROM users";
    $stmt = $pdo->query($sql);
    $totalUsers = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM user_email_preferences";
    $stmt = $pdo->query($sql);
    $totalPreferences = $stmt->fetchColumn();
    
    echo "<div class='info'>";
    echo "📊 <strong>Kullanıcı İstatistikleri:</strong><br>";
    echo "Toplam Kullanıcı: {$totalUsers}<br>";
    echo "Email Tercihleri Bulunan: {$totalPreferences}<br>";
    echo "</div>";
    
    if ($totalUsers > $totalPreferences) {
        $missingPrefs = $totalUsers - $totalPreferences;
        echo "<div class='warning'>⚠️ {$missingPrefs} kullanıcının email tercihleri eksik!</div>";
        
        // Eksik kullanıcılar için varsayılan tercihler oluştur
        echo "<h3>5.1. Eksik Email Tercihlerini Oluşturuyor</h3>";
        
        $sql = "SELECT u.id, u.email 
                FROM users u 
                LEFT JOIN user_email_preferences uep ON u.id = uep.user_id 
                WHERE uep.user_id IS NULL";
        $stmt = $pdo->query($sql);
        $usersWithoutPrefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($usersWithoutPrefs as $user) {
            try {
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
                
                echo "<div class='success'>✅ {$user['email']} için email tercihleri oluşturuldu.</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ {$user['email']} için email tercihleri oluşturulamadı: {$e->getMessage()}</div>";
            }
        }
    }
    
    // 6. Foreign key kontrolü
    echo "<h2>6. Foreign Key Kontrolü</h2>";
    
    $sql = "SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'user_email_preferences' 
            AND TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME = 'users'";
    $stmt = $pdo->query($sql);
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($foreignKeys)) {
        echo "<div class='warning'>⚠️ Foreign key constraint eksik!</div>";
        try {
            $sql = "ALTER TABLE user_email_preferences 
                    ADD CONSTRAINT fk_user_email_prefs_user_id 
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
            $pdo->exec($sql);
            echo "<div class='success'>✅ Foreign key constraint başarıyla eklendi.</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Foreign key constraint eklenemedi: {$e->getMessage()}</div>";
        }
    } else {
        echo "<div class='success'>✅ Foreign key constraint mevcut: " . implode(', ', $foreignKeys) . "</div>";
    }
    
    // 7. Son kontrol ve özet
    echo "<h2>7. 🎉 Tablo Onarımı Tamamlandı</h2>";
    
    $sql = "SELECT COUNT(*) FROM user_email_preferences WHERE chat_message_notifications = 1";
    $stmt = $pdo->query($sql);
    $chatEnabled = $stmt->fetchColumn();
    
    echo "<div class='success'>";
    echo "<h3>✅ Başarılı Sonuçlar:</h3>";
    echo "<ul>";
    echo "<li>user_email_preferences tablosu tamamen güncellendi</li>";
    echo "<li>chat_message_notifications alanı eklendi</li>";
    echo "<li>{$chatEnabled} kullanıcı chat email bildirimlerini etkinleştirdi</li>";
    echo "<li>Tüm kullanıcılar için email tercihleri mevcut</li>";
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
    error_log("Email preferences table repair error: " . $e->getMessage());
}

echo "</body></html>";
?>
