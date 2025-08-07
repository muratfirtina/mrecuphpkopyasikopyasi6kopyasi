<?php
/**
 * Notification System Debug
 * Bildirim sistemi debug ve test dosyası
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/NotificationManager.php';

echo "<h1>Mr ECU - Bildirim Sistemi Debug</h1>";

try {
    // 1. Database bağlantısını kontrol et
    echo "<h2>1. Database Bağlantı Kontrolü</h2>";
    if ($pdo) {
        echo "<p style='color: green;'>✓ Database bağlantısı başarılı</p>";
    } else {
        echo "<p style='color: red;'>✗ Database bağlantısı başarısız</p>";
        exit;
    }

    // 2. Notifications tablosunu kontrol et
    echo "<h2>2. Notifications Tablosu Kontrolü</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✓ Notifications tablosu mevcut</p>";
        echo "<details><summary>Tablo yapısı</summary><pre>";
        foreach ($columns as $column) {
            echo $column['Field'] . " - " . $column['Type'] . "\n";
        }
        echo "</pre></details>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Notifications tablosu bulunamadı: " . $e->getMessage() . "</p>";
        
        // Tabloyu oluştur
        echo "<h3>Notifications tablosunu oluşturuyor...</h3>";
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
        } catch (Exception $createError) {
            echo "<p style='color: red;'>✗ Notifications tablosu oluşturulamadı: " . $createError->getMessage() . "</p>";
        }
    }

    // 3. Admin kullanıcıları kontrol et
    echo "<h2>3. Admin Kullanıcıları Kontrolü</h2>";
    try {
        $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, role, status FROM users WHERE role = 'admin' AND status = 'active'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($admins)) {
            echo "<p style='color: orange;'>⚠ Aktif admin kullanıcısı bulunamadı</p>";
            
            // Tüm admin'leri kontrol et (status fark etmeksizin)
            $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, role, status FROM users WHERE role = 'admin'");
            $allAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($allAdmins)) {
                echo "<p style='color: red;'>✗ Hiç admin kullanıcısı yok</p>";
            } else {
                echo "<p style='color: orange;'>Admin kullanıcıları mevcut ama aktif değil:</p>";
                echo "<ul>";
                foreach ($allAdmins as $admin) {
                    echo "<li>{$admin['username']} - Status: {$admin['status']}</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: green;'>✓ " . count($admins) . " aktif admin kullanıcısı bulundu</p>";
            echo "<ul>";
            foreach ($admins as $admin) {
                echo "<li>{$admin['username']} ({$admin['first_name']} {$admin['last_name']}) - {$admin['email']}</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Admin kullanıcıları sorgulanırken hata: " . $e->getMessage() . "</p>";
    }

    // 4. NotificationManager sınıfını test et
    echo "<h2>4. NotificationManager Test</h2>";
    try {
        $notificationManager = new NotificationManager($pdo);
        echo "<p style='color: green;'>✓ NotificationManager sınıfı başlatıldı</p>";
        
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
        } else {
            echo "<p style='color: red;'>✗ Test bildirimi oluşturulamadı</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ NotificationManager testi başarısız: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }

    // 5. Revize talebi bildirimi test et (gerçek admin ile)
    echo "<h2>5. Revize Talebi Bildirimi Test</h2>";
    if (!empty($admins)) {
        try {
            $testRevisionId = 'test-revision-' . time();
            $testUserId = 'test-user-' . time();
            $testUploadId = 'test-upload-' . time();
            $testFileName = 'test-file.bin';
            
            $revisionNotifyResult = $notificationManager->notifyRevisionRequest(
                $testRevisionId,
                $testUserId,
                $testUploadId,
                $testFileName,
                'Bu bir test revize talebi notudur.'
            );
            
            if ($revisionNotifyResult) {
                echo "<p style='color: green;'>✓ Revize talebi bildirimi test başarılı</p>";
                
                // Bildirimleri kontrol et
                foreach ($admins as $admin) {
                    $notifications = $notificationManager->getUserNotifications($admin['id'], 5);
                    echo "<p>Admin {$admin['username']} için " . count($notifications) . " bildirim bulundu</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Revize talebi bildirimi test başarısız</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Revize talebi bildirimi test hatası: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Admin kullanıcısı olmadığı için revize bildirimi test edilemedi</p>";
    }

    // 6. Son bildirimleri listele
    echo "<h2>6. Son Bildirimler</h2>";
    try {
        $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
        $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recentNotifications)) {
            echo "<p>Henüz bildirim yok</p>";
        } else {
            echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Title</th><th>Created At</th><th>Read</th></tr>";
            foreach ($recentNotifications as $notification) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars(substr($notification['id'], 0, 8)) . "...</td>";
                echo "<td>" . htmlspecialchars(substr($notification['user_id'], 0, 8)) . "...</td>";
                echo "<td>" . htmlspecialchars($notification['type']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['created_at']) . "</td>";
                echo "<td>" . ($notification['is_read'] ? 'Okundu' : 'Okunmadı') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Bildirimler listelenirken hata: " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Genel hata: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.php'>Ana Sayfa</a></p>";
?>