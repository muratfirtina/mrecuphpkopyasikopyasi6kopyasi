<?php
/**
 * Notification System Fix and Setup
 * Bildirim sistemi düzeltme ve kurulum dosyası
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/NotificationManager.php';

echo "<h1>Bildirim Sistemi Düzeltme ve Kurulum</h1>";

$fixes = [];
$errors = [];

try {
    // 1. Notifications tablosunu kontrol et ve oluştur
    echo "<h2>1. Notifications Tablosu Kontrolü</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
        echo "<p style='color: green;'>✓ Notifications tablosu mevcut</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Notifications tablosu bulunamadı, oluşturuluyor...</p>";
        
        $createTableSQL = "
        CREATE TABLE `notifications` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `user_id` VARCHAR(36) NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `related_id` VARCHAR(36) NULL,
            `related_type` VARCHAR(50) NULL,
            `action_url` VARCHAR(500) NULL,
            `is_read` BOOLEAN DEFAULT FALSE,
            `read_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_user_id` (`user_id`),
            KEY `idx_is_read` (`is_read`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $pdo->exec($createTableSQL);
            echo "<p style='color: green;'>✓ Notifications tablosu başarıyla oluşturuldu</p>";
            $fixes[] = "Notifications tablosu oluşturuldu";
        } catch (Exception $createError) {
            echo "<p style='color: red;'>✗ Notifications tablosu oluşturulamadı: " . $createError->getMessage() . "</p>";
            $errors[] = "Notifications tablosu oluşturulamadı";
        }
    }

    // 2. Admin kullanıcıları kontrol et
    echo "<h2>2. Admin Kullanıcıları Kontrolü</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as active_admins FROM users WHERE role = 'admin' AND status = 'active'");
    $activeAdmins = $stmt->fetchColumn();
    
    if ($activeAdmins == 0) {
        echo "<p style='color: orange;'>⚠ Aktif admin kullanıcısı bulunamadı</p>";
        
        // Mevcut admin'leri kontrol et
        $stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
        $totalAdmins = $stmt->fetchColumn();
        
        if ($totalAdmins > 0) {
            // Mevcut admin'leri aktif hale getir
            echo "<p>Mevcut admin kullanıcıları aktif hale getiriliyor...</p>";
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE role = 'admin'");
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Mevcut admin kullanıcıları aktif hale getirildi</p>";
                $fixes[] = "Admin kullanıcıları aktif hale getirildi";
            }
        } else {
            // Yeni admin kullanıcısı oluştur
            echo "<p>Yeni admin kullanıcısı oluşturuluyor...</p>";
            $adminId = generateUUID();
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (id, username, email, first_name, last_name, password, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active', NOW())
            ");
            
            if ($stmt->execute([$adminId, 'admin', 'admin@mrecu.com', 'Admin', 'User', $hashedPassword])) {
                echo "<p style='color: green;'>✓ Yeni admin kullanıcısı oluşturuldu</p>";
                echo "<p><strong>Admin Giriş Bilgileri:</strong><br>Username: admin<br>Password: admin123</p>";
                $fixes[] = "Yeni admin kullanıcısı oluşturuldu";
            } else {
                echo "<p style='color: red;'>✗ Admin kullanıcısı oluşturulamadı</p>";
                $errors[] = "Admin kullanıcısı oluşturulamadı";
            }
        }
    } else {
        echo "<p style='color: green;'>✓ $activeAdmins aktif admin kullanıcısı mevcut</p>";
    }

    // 3. NotificationManager test et
    echo "<h2>3. NotificationManager Test</h2>";
    try {
        $notificationManager = new NotificationManager($pdo);
        echo "<p style='color: green;'>✓ NotificationManager başarıyla yüklendi</p>";
        
        // Test bildirimi oluştur
        $testUserId = 'test-user-' . time();
        $testResult = $notificationManager->createNotification(
            $testUserId,
            'test',
            'Test Bildirimi',
            'Bu bir test bildirimidir.',
            'test-id',
            'test',
            'test.php'
        );
        
        if ($testResult) {
            echo "<p style='color: green;'>✓ Test bildirimi başarıyla oluşturuldu</p>";
            $fixes[] = "NotificationManager test başarılı";
        } else {
            echo "<p style='color: red;'>✗ Test bildirimi oluşturulamadı</p>";
            $errors[] = "NotificationManager test başarısız";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ NotificationManager test hatası: " . $e->getMessage() . "</p>";
        $errors[] = "NotificationManager hatası: " . $e->getMessage();
    }

    // 4. Revizyon tablosu kontrolü
    echo "<h2>4. Revizyon Tablosu Kontrolü</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM revisions");
        echo "<p style='color: green;'>✓ Revisions tablosu mevcut</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Revisions tablosu bulunamadı, oluşturuluyor...</p>";
        
        $createRevisionsSQL = "
        CREATE TABLE `revisions` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `upload_id` VARCHAR(36) NOT NULL,
            `response_id` VARCHAR(36) NULL,
            `revision_file_id` VARCHAR(36) NULL,
            `parent_revision_id` VARCHAR(36) NULL,
            `user_id` VARCHAR(36) NOT NULL,
            `admin_id` VARCHAR(36) NULL,
            `request_notes` TEXT NOT NULL,
            `admin_notes` TEXT NULL,
            `admin_response` TEXT NULL,
            `status` ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
            `credits_charged` DECIMAL(10,2) DEFAULT 0.00,
            `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `completed_at` TIMESTAMP NULL,
            KEY `idx_upload_id` (`upload_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_admin_id` (`admin_id`),
            KEY `idx_status` (`status`),
            KEY `idx_requested_at` (`requested_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $pdo->exec($createRevisionsSQL);
            echo "<p style='color: green;'>✓ Revisions tablosu başarıyla oluşturuldu</p>";
            $fixes[] = "Revisions tablosu oluşturuldu";
        } catch (Exception $createError) {
            echo "<p style='color: red;'>✗ Revisions tablosu oluşturulamadı: " . $createError->getMessage() . "</p>";
            $errors[] = "Revisions tablosu oluşturulamadı";
        }
    }

    // 5. Güncel admin listesi
    echo "<h2>5. Aktif Admin Kullanıcıları</h2>";
    $stmt = $pdo->query("SELECT username, email, first_name, last_name FROM users WHERE role = 'admin' AND status = 'active'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "<p style='color: red;'>✗ Aktif admin kullanıcısı bulunamadı!</p>";
    } else {
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>{$admin['username']} - {$admin['first_name']} {$admin['last_name']} ({$admin['email']})</li>";
        }
        echo "</ul>";
    }

    // 6. Test temizliği
    echo "<h2>6. Test Verilerini Temizleme</h2>";
    try {
        $pdo->prepare("DELETE FROM notifications WHERE user_id LIKE 'test-%'")->execute();
        echo "<p style='color: green;'>✓ Test bildirimleri temizlendi</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Test temizlik hatası: " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Genel hata: " . $e->getMessage() . "</p>";
    $errors[] = "Genel hata: " . $e->getMessage();
}

// Özet
echo "<hr>";
echo "<h2>Düzeltme Özeti</h2>";

if (!empty($fixes)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3 style='color: #155724;'>Başarıyla Düzeltilen Sorunlar:</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>✓ $fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>Düzeltilemeyen Sorunlar:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>✗ $error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (empty($errors)) {
    echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3 style='color: #0c5460;'>🎉 Bildirim Sistemi Hazır!</h3>";
    echo "<p>Artık kullanıcılar revizyon talebi oluşturduğunda adminlere bildirim gidecektir.</p>";
    echo "<p><strong>Test etmek için:</strong></p>";
    echo "<ol>";
    echo "<li>Kullanıcı hesabı ile giriş yapın</li>";
    echo "<li>Bir dosya yükleyin ve tamamlanmasını bekleyin</li>";
    echo "<li>Dosya için revizyon talebi oluşturun</li>";
    echo "<li>Admin hesabı ile giriş yapın ve bildirimleri kontrol edin</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<p><a href='debug_notification_system.php'>Bildirim Debug</a> | <a href='debug_revision_test.php'>Revizyon Test</a> | <a href='admin/notifications.php'>Admin Bildirimleri</a> | <a href='index.php'>Ana Sayfa</a></p>";
?>