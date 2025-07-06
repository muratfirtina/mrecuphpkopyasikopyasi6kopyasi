<?php
/**
 * Veritabanı Tablo Kontrolü ve Test Verisi Oluşturma
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Kontrol</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Kontrol ve Test Verisi</h1>";

try {
    echo "<h2>1. Mevcut Tablolar</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div class='error'>❌ Hiç tablo bulunamadı!</div>";
    } else {
        echo "<div class='success'>✅ " . count($tables) . " tablo bulundu:</div>";
        echo "<ul>";
        foreach ($tables as $table) {
            // Her tablo için kayıt sayısını al
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "<li><strong>$table</strong> - $count kayıt</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>2. file_uploads Tablosu Kontrol</h2>";
    if (in_array('file_uploads', $tables)) {
        $uploads = $pdo->query("SELECT COUNT(*) FROM file_uploads")->fetchColumn();
        echo "<div class='info'>📁 file_uploads tablosu mevcut - $uploads kayıt</div>";
        
        if ($uploads == 0) {
            echo "<div class='info'>Test dosyası oluşturuluyor...</div>";
            
            // Test kullanıcısı var mı kontrol et
            $testUser = $pdo->query("SELECT id FROM users LIMIT 1")->fetch();
            if ($testUser) {
                $testUploadId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO file_uploads (id, user_id, original_name, filename, file_size, status, upload_date) 
                    VALUES (?, ?, 'test_file.bin', 'test_file.bin', 1024, 'completed', NOW())
                ");
                if ($stmt->execute([$testUploadId, $testUser['id']])) {
                    echo "<div class='success'>✅ Test dosyası oluşturuldu</div>";
                }
            }
        } else {
            echo "<div class='success'>✅ file_uploads tablosunda veri mevcut</div>";
            
            // Son 5 dosyayı göster
            $recentUploads = $pdo->query("
                SELECT fu.original_name, fu.status, fu.upload_date, u.username 
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id 
                ORDER BY fu.upload_date DESC 
                LIMIT 5
            ")->fetchAll();
            
            if (!empty($recentUploads)) {
                echo "<table>";
                echo "<tr><th>Dosya</th><th>Kullanıcı</th><th>Durum</th><th>Tarih</th></tr>";
                foreach ($recentUploads as $upload) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($upload['original_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($upload['username'] ?? 'Bilinmiyor') . "</td>";
                    echo "<td>" . htmlspecialchars($upload['status']) . "</td>";
                    echo "<td>" . date('d.m.Y H:i', strtotime($upload['upload_date'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    } else {
        echo "<div class='error'>❌ file_uploads tablosu bulunamadı!</div>";
    }
    
    echo "<h2>3. revisions Tablosu Kontrol</h2>";
    if (in_array('revisions', $tables)) {
        $revisions = $pdo->query("SELECT COUNT(*) FROM revisions")->fetchColumn();
        echo "<div class='info'>🔄 revisions tablosu mevcut - $revisions kayıt</div>";
        
        if ($revisions == 0) {
            echo "<div class='info'>Test revize talebi oluşturuluyor...</div>";
            
            // Test dosyası ve kullanıcısı var mı kontrol et
            $testUpload = $pdo->query("SELECT id, user_id FROM file_uploads WHERE status = 'completed' LIMIT 1")->fetch();
            if ($testUpload) {
                $testRevisionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO revisions (id, upload_id, user_id, request_notes, status, requested_at) 
                    VALUES (?, ?, ?, 'Test revize talebi - dosyada sorun var', 'pending', NOW())
                ");
                if ($stmt->execute([$testRevisionId, $testUpload['id'], $testUpload['user_id']])) {
                    echo "<div class='success'>✅ Test revize talebi oluşturuldu</div>";
                }
            } else {
                // Eğer hiç dosya yoksa, test dosyası ve revize oluştur
                $testUser = $pdo->query("SELECT id FROM users LIMIT 1")->fetch();
                if ($testUser) {
                    // Önce test dosyası oluştur
                    $testUploadId = generateUUID();
                    $stmt = $pdo->prepare("
                        INSERT INTO file_uploads (id, user_id, original_name, filename, file_size, status, upload_date) 
                        VALUES (?, ?, 'test_revision_file.bin', 'test_revision_file.bin', 2048, 'completed', NOW())
                    ");
                    if ($stmt->execute([$testUploadId, $testUser['id']])) {
                        // Sonra revize talebi oluştur
                        $testRevisionId = generateUUID();
                        $stmt = $pdo->prepare("
                            INSERT INTO revisions (id, upload_id, user_id, request_notes, status, requested_at) 
                            VALUES (?, ?, ?, 'Test revize talebi - dosyada düzenlemeler gerekli', 'pending', NOW())
                        ");
                        if ($stmt->execute([$testRevisionId, $testUploadId, $testUser['id']])) {
                            echo "<div class='success'>✅ Test dosyası ve revize talebi oluşturuldu</div>";
                        }
                    }
                }
            }
        } else {
            echo "<div class='success'>✅ revisions tablosunda veri mevcut</div>";
            
            // Son 5 revize talebini göster
            $recentRevisions = $pdo->query("
                SELECT r.request_notes, r.status, r.requested_at, u.username 
                FROM revisions r
                LEFT JOIN users u ON r.user_id = u.id 
                ORDER BY r.requested_at DESC 
                LIMIT 5
            ")->fetchAll();
            
            if (!empty($recentRevisions)) {
                echo "<table>";
                echo "<tr><th>Talep Notu</th><th>Kullanıcı</th><th>Durum</th><th>Tarih</th></tr>";
                foreach ($recentRevisions as $revision) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars(substr($revision['request_notes'], 0, 50)) . "...</td>";
                    echo "<td>" . htmlspecialchars($revision['username'] ?? 'Bilinmiyor') . "</td>";
                    echo "<td>" . htmlspecialchars($revision['status']) . "</td>";
                    echo "<td>" . date('d.m.Y H:i', strtotime($revision['requested_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    } else {
        echo "<div class='error'>❌ revisions tablosu bulunamadı!</div>";
    }
    
    echo "<h2>4. credit_transactions Tablosu Kontrol</h2>";
    if (in_array('credit_transactions', $tables)) {
        $transactions = $pdo->query("SELECT COUNT(*) FROM credit_transactions")->fetchColumn();
        echo "<div class='info'>💰 credit_transactions tablosu mevcut - $transactions kayıt</div>";
        
        if ($transactions == 0) {
            echo "<div class='info'>Test kredi işlemi oluşturuluyor...</div>";
            
            // Test kullanıcısı var mı kontrol et
            $testUser = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1")->fetch();
            if ($testUser) {
                $testTransactionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, type, amount, description, created_at) 
                    VALUES (?, ?, 'deposit', 100.00, 'Test kredi yüklemesi', NOW())
                ");
                if ($stmt->execute([$testTransactionId, $testUser['id']])) {
                    echo "<div class='success'>✅ Test kredi işlemi oluşturuldu</div>";
                }
            }
        } else {
            echo "<div class='success'>✅ credit_transactions tablosunda veri mevcut</div>";
        }
    } else {
        echo "<div class='error'>❌ credit_transactions tablosu bulunamadı!</div>";
    }
    
    echo "<h2>5. users Tablosu Kontrol</h2>";
    if (in_array('users', $tables)) {
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        
        echo "<div class='info'>👥 users tablosu mevcut - $userCount kullanıcı ($adminCount admin)</div>";
        
        if ($userCount == 0) {
            echo "<div class='error'>❌ Hiç kullanıcı yok! Admin kullanıcısı oluşturuluyor...</div>";
            
            $adminId = generateUUID();
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (id, username, email, password, first_name, last_name, role, status, created_at) 
                VALUES (?, 'admin', 'admin@mrecu.com', ?, 'Admin', 'User', 'admin', 'active', NOW())
            ");
            if ($stmt->execute([$adminId, $hashedPassword])) {
                echo "<div class='success'>✅ Admin kullanıcısı oluşturuldu (admin@mrecu.com / admin123)</div>";
            }
        }
        
        if ($adminCount == 0 && $userCount > 0) {
            echo "<div class='error'>❌ Admin kullanıcısı yok!</div>";
        }
    } else {
        echo "<div class='error'>❌ users tablosu bulunamadı!</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br><a href='admin/'>🏠 Admin paneline git</a> | <a href='index.php'>🌐 Ana sayfaya git</a>";
echo "</body></html>";
?>
