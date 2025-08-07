<?php
/**
 * Revision Request Notification Test
 * Revizyon talebi bildirim sistemi tam testi
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/NotificationManager.php';
require_once 'includes/FileManager.php';

echo "<h1>Revizyon Talebi Bildirim Sistemi Test</h1>";

// Test verilerini hazırla
$testUserId = 'test-user-' . time();
$testUploadId = 'test-upload-' . time();
$testRevisionId = 'test-revision-' . time();

echo "<h2>Test Verileri</h2>";
echo "<p>Test User ID: $testUserId</p>";
echo "<p>Test Upload ID: $testUploadId</p>";
echo "<p>Test Revision ID: $testRevisionId</p>";

try {
    // 1. Test kullanıcısı oluştur
    echo "<h2>1. Test Kullanıcısı Oluşturma</h2>";
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO users (id, username, email, first_name, last_name, password, role, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'user', 'active', NOW())
    ");
    
    $userCreated = $stmt->execute([
        $testUserId,
        'testuser' . time(),
        'testuser' . time() . '@test.com',
        'Test',
        'User',
        password_hash('test123', PASSWORD_DEFAULT)
    ]);
    
    if ($userCreated) {
        echo "<p style='color: green;'>✓ Test kullanıcısı oluşturuldu</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Test kullanıcısı zaten mevcut</p>";
    }

    // 2. Test dosya upload kaydı oluştur
    echo "<h2>2. Test Dosya Upload Kaydı Oluşturma</h2>";
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO file_uploads (
            id, user_id, brand_id, model_id, series_id, engine_id, device_id, ecu_id,
            year, plate, kilometer, gearbox_type, fuel_type, hp_power, nm_torque,
            original_name, filename, file_size, status, upload_notes, upload_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW())
    ");
    
    // Dummy brand, model vs. ID'leri
    $dummyIds = ['dummy-brand', 'dummy-model', 'dummy-series', 'dummy-engine', 'dummy-device', 'dummy-ecu'];
    
    $uploadCreated = $stmt->execute([
        $testUploadId,
        $testUserId,
        ...$dummyIds,
        2023,
        '34TEST123',
        150000,
        'manual',
        'petrol',
        150,
        300,
        'test-original-file.bin',
        'test-stored-file.bin',
        1024000,
        'Test upload for revision'
    ]);
    
    if ($uploadCreated) {
        echo "<p style='color: green;'>✓ Test dosya upload kaydı oluşturuldu</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Test dosya upload kaydı zaten mevcut</p>";
    }

    // 3. Admin kullanıcıları kontrol et
    echo "<h2>3. Admin Kullanıcıları Kontrolü</h2>";
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name FROM users WHERE role = 'admin' AND status = 'active'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "<p style='color: red;'>✗ Aktif admin kullanıcısı bulunamadı!</p>";
        echo "<p><a href='debug_users.php'>Kullanıcı Debug Sayfasına Git</a></p>";
        exit;
    } else {
        echo "<p style='color: green;'>✓ " . count($admins) . " aktif admin kullanıcısı bulundu</p>";
        foreach ($admins as $admin) {
            echo "<p>- {$admin['username']} ({$admin['first_name']} {$admin['last_name']})</p>";
        }
    }

    // 4. NotificationManager başlat
    echo "<h2>4. NotificationManager Test</h2>";
    $notificationManager = new NotificationManager($pdo);
    echo "<p style='color: green;'>✓ NotificationManager başlatıldı</p>";

    // 5. Manuel revizyon talebi oluştur (FileManager kullanmadan)
    echo "<h2>5. Manuel Revizyon Talebi Oluşturma</h2>";
    $stmt = $pdo->prepare("
        INSERT INTO revisions (id, upload_id, user_id, request_notes, status, requested_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $revisionCreated = $stmt->execute([
        $testRevisionId,
        $testUploadId,
        $testUserId,
        'Bu bir test revizyon talebi notudur.'
    ]);
    
    if ($revisionCreated) {
        echo "<p style='color: green;'>✓ Test revizyon talebi oluşturuldu</p>";
    } else {
        echo "<p style='color: red;'>✗ Test revizyon talebi oluşturulamadı</p>";
        exit;
    }

    // 6. NotificationManager ile bildirim gönder
    echo "<h2>6. Bildirim Gönderme Test</h2>";
    $notifyResult = $notificationManager->notifyRevisionRequest(
        $testRevisionId,
        $testUserId,
        $testUploadId,
        'test-original-file.bin',
        'Bu bir test revizyon talebi notudur.'
    );
    
    if ($notifyResult) {
        echo "<p style='color: green;'>✓ Bildirim gönderme fonksiyonu başarılı</p>";
    } else {
        echo "<p style='color: red;'>✗ Bildirim gönderme fonksiyonu başarısız</p>";
    }

    // 7. Oluşturulan bildirimleri kontrol et
    echo "<h2>7. Oluşturulan Bildirimler</h2>";
    $stmt = $pdo->prepare("
        SELECT n.*, u.username as admin_username 
        FROM notifications n 
        LEFT JOIN users u ON n.user_id = u.id 
        WHERE n.related_id = ? AND n.type = 'revision_request'
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$testRevisionId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "<p style='color: red;'>✗ Bu revizyon talebi için bildirim bulunamadı!</p>";
        
        // Debug: Tüm son bildirimleri göster
        echo "<h3>Debug: Son 10 Bildirim</h3>";
        $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
        $allNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($allNotifications)) {
            echo "<p>Hiç bildirim bulunamadı</p>";
        } else {
            echo "<table border='1'>";
            echo "<tr><th>Type</th><th>Title</th><th>User ID</th><th>Related ID</th><th>Created At</th></tr>";
            foreach ($allNotifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . substr($notif['user_id'], 0, 8) . "...</td>";
                echo "<td>" . substr($notif['related_id'] ?? '', 0, 8) . "...</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: green;'>✓ " . count($notifications) . " bildirim bulundu</p>";
        
        echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
        echo "<tr><th>Admin</th><th>Title</th><th>Message</th><th>Created At</th><th>Action URL</th></tr>";
        foreach ($notifications as $notification) {
            echo "<tr>";
            echo "<td>{$notification['admin_username']}</td>";
            echo "<td>{$notification['title']}</td>";
            echo "<td>{$notification['message']}</td>";
            echo "<td>{$notification['created_at']}</td>";
            echo "<td>{$notification['action_url']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 8. FileManager ile gerçek test
    echo "<h2>8. FileManager ile Gerçek Test</h2>";
    $fileManager = new FileManager($pdo);
    
    // Yeni test verileri
    $testRevisionId2 = 'test-revision-fm-' . time();
    
    echo "<p>FileManager requestRevision fonksiyonunu test ediyoruz...</p>";
    $fmResult = $fileManager->requestRevision($testUploadId, $testUserId, 'FileManager ile test revizyon talebi');
    
    if ($fmResult['success']) {
        echo "<p style='color: green;'>✓ FileManager revizyon talebi başarılı: {$fmResult['message']}</p>";
        
        // Bu talep için bildirimleri kontrol et
        $stmt = $pdo->query("
            SELECT n.*, u.username as admin_username, r.id as revision_id
            FROM notifications n 
            LEFT JOIN users u ON n.user_id = u.id 
            LEFT JOIN revisions r ON n.related_id = r.id
            WHERE n.type = 'revision_request' AND r.upload_id = ?
            ORDER BY n.created_at DESC LIMIT 5
        ");
        $stmt->execute([$testUploadId]);
        $fmNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($fmNotifications)) {
            echo "<p style='color: red;'>✗ FileManager talebine ait bildirim bulunamadı</p>";
        } else {
            echo "<p style='color: green;'>✓ FileManager talebine ait " . count($fmNotifications) . " bildirim bulundu</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ FileManager revizyon talebi başarısız: {$fmResult['message']}</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Temizlik işlemi
echo "<hr>";
echo "<h2>Temizlik İşlemleri</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='action' value='cleanup'>";
echo "<input type='hidden' name='test_user_id' value='$testUserId'>";
echo "<input type='hidden' name='test_upload_id' value='$testUploadId'>";
echo "<button type='submit'>Test Verilerini Temizle</button>";
echo "</form>";

if ($_POST['action'] === 'cleanup') {
    try {
        $pdo->prepare("DELETE FROM notifications WHERE user_id LIKE 'test-%' OR related_id LIKE 'test-%'")->execute();
        $pdo->prepare("DELETE FROM revisions WHERE id LIKE 'test-%' OR user_id LIKE 'test-%'")->execute();
        $pdo->prepare("DELETE FROM file_uploads WHERE id LIKE 'test-%' OR user_id LIKE 'test-%'")->execute();
        $pdo->prepare("DELETE FROM users WHERE id LIKE 'test-%'")->execute();
        
        echo "<p style='color: green;'>✓ Test verileri temizlendi</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Temizlik hatası: " . $e->getMessage() . "</p>";
    }
}

echo "<p><a href='debug_notification_system.php'>Bildirim Sistemi Debug</a> | <a href='debug_users.php'>Kullanıcı Debug</a> | <a href='index.php'>Ana Sayfa</a></p>";
?>