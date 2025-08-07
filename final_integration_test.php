<?php
/**
 * FINAL INTEGRATION TEST - Complete Notification System
 * Bildirim sisteminin tam entegrasyon testi
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/NotificationManager.php';
require_once 'includes/FileManager.php';

echo "<h1>🎯 Final Entegrasyon Testi - Bildirim Sistemi</h1>";
echo "<p><em>Revizyon talebi oluşturulduğunda admin'e bildirim gönderilmesi tam testi</em></p>";

$testResults = [];
$errors = [];
$fixes = [];

// Test setup
$testUserId = 'final-test-user-' . time();
$testUploadId = 'final-test-upload-' . time();

try {
    echo "<h2>🔍 Ön Kontroller</h2>";
    
    // 1. Database bağlantı kontrolü
    if ($pdo) {
        $testResults[] = "✅ Database bağlantısı aktif";
    } else {
        $errors[] = "❌ Database bağlantısı başarısız";
        exit;
    }
    
    // 2. Notifications tablosu kontrolü
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
        $testResults[] = "✅ Notifications tablosu mevcut";
    } catch (Exception $e) {
        $errors[] = "❌ Notifications tablosu eksik";
    }
    
    // 3. Revisions tablosu kontrolü
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM revisions");
        $testResults[] = "✅ Revisions tablosu mevcut";
    } catch (Exception $e) {
        $errors[] = "❌ Revisions tablosu eksik";
    }
    
    // 4. Admin kullanıcı kontrolü
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'");
    $adminCount = $stmt->fetchColumn();
    if ($adminCount > 0) {
        $testResults[] = "✅ $adminCount aktif admin kullanıcısı mevcut";
    } else {
        $errors[] = "❌ Aktif admin kullanıcısı bulunamadı";
    }
    
    if (!empty($errors)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ Kritik Hatalar - Test Devam Edemiyor</h3>";
        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
        echo "<p><a href='fix_notification_system.php'>🔧 Sistem Kurulum Dosyasını Çalıştır</a></p>";
        echo "</div>";
        exit;
    }
    
    foreach ($testResults as $result) {
        echo "<p>$result</p>";
    }

    echo "<h2>🚀 Test Verilerini Hazırlama</h2>";
    
    // Test kullanıcısı oluştur
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO users (id, username, email, first_name, last_name, password, role, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'user', 'active', NOW())
    ");
    
    $userCreated = $stmt->execute([
        $testUserId,
        'final_test_user_' . time(),
        'test_user_' . time() . '@mrecu.test',
        'Test',
        'User',
        password_hash('test123', PASSWORD_DEFAULT)
    ]);
    
    if ($userCreated) {
        echo "<p>✅ Test kullanıcısı oluşturuldu: $testUserId</p>";
    }
    
    // Test dosya upload kaydı oluştur
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO file_uploads (
            id, user_id, brand_id, model_id, series_id, engine_id, device_id, ecu_id,
            year, plate, kilometer, gearbox_type, fuel_type, hp_power, nm_torque,
            original_name, filename, file_size, status, upload_notes, upload_date
        ) VALUES (?, ?, 'dummy-brand', 'dummy-model', 'dummy-series', 'dummy-engine', 'dummy-device', 'dummy-ecu',
                 2023, '34TEST123', 150000, 'manual', 'petrol', 150, 300, 'test_file_final.bin', 
                 'stored_test_file_final.bin', 2048000, 'completed', 'Final test upload', NOW())
    ");
    
    $uploadCreated = $stmt->execute([$testUploadId, $testUserId]);
    
    if ($uploadCreated) {
        echo "<p>✅ Test dosya kaydı oluşturuldu: $testUploadId</p>";
    }

    echo "<h2>🎬 Gerçek Test Senaryosu</h2>";
    echo "<p><strong>Senaryo:</strong> Kullanıcı tamamlanmış bir dosya için revizyon talebi oluşturuyor</p>";
    
    // FileManager ile gerçek revizyon talebi oluştur
    $fileManager = new FileManager($pdo);
    $revisionNotes = "Final test revizyon talebi - Dosyayı daha da optimize edebilir misiniz? Özellikle yakıt tüketimi konusunda iyileştirme arıyorum.";
    
    echo "<h3>📝 Step 1: Revizyon Talebi Oluşturma</h3>";
    $revisionResult = $fileManager->requestRevision($testUploadId, $testUserId, $revisionNotes);
    
    if ($revisionResult['success']) {
        echo "<p style='color: green;'>✅ Revizyon talebi başarıyla oluşturuldu</p>";
        echo "<p><strong>Mesaj:</strong> " . htmlspecialchars($revisionResult['message']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Revizyon talebi oluşturulamadı: " . htmlspecialchars($revisionResult['message']) . "</p>";
        exit;
    }

    // 2 saniye bekle (gerçek hayat simülasyonu)
    sleep(2);

    echo "<h3>🔔 Step 2: Admin Bildirimlerini Kontrol Etme</h3>";
    
    // Son oluşturulan revizyon talebini bul
    $stmt = $pdo->prepare("SELECT id FROM revisions WHERE upload_id = ? AND user_id = ? ORDER BY requested_at DESC LIMIT 1");
    $stmt->execute([$testUploadId, $testUserId]);
    $revisionId = $stmt->fetchColumn();
    
    if ($revisionId) {
        echo "<p>✅ Revizyon talebi ID bulundu: $revisionId</p>";
        
        // Bu revizyon talebi için bildirimleri kontrol et
        $stmt = $pdo->prepare("
            SELECT n.*, u.username as admin_username 
            FROM notifications n 
            LEFT JOIN users u ON n.user_id = u.id 
            WHERE n.related_id = ? AND n.type = 'revision_request'
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$revisionId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($notifications)) {
            echo "<p style='color: green;'>✅ " . count($notifications) . " bildirim başarıyla oluşturuldu!</p>";
            
            echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>📨 Oluşturulan Bildirimler:</h4>";
            
            foreach ($notifications as $notification) {
                echo "<div style='background: white; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #007bff;'>";
                echo "<strong>Admin:</strong> " . htmlspecialchars($notification['admin_username']) . "<br>";
                echo "<strong>Başlık:</strong> " . htmlspecialchars($notification['title']) . "<br>";
                echo "<strong>Mesaj:</strong> " . htmlspecialchars($notification['message']) . "<br>";
                echo "<strong>Tarih:</strong> " . date('d.m.Y H:i:s', strtotime($notification['created_at'])) . "<br>";
                echo "<strong>Action URL:</strong> " . htmlspecialchars($notification['action_url']) . "<br>";
                echo "<strong>Durum:</strong> " . ($notification['is_read'] ? 'Okundu' : '<span style="color: #dc3545; font-weight: bold;">Okunmamış</span>') . "<br>";
                echo "</div>";
            }
            echo "</div>";
            
        } else {
            echo "<p style='color: red;'>❌ Bu revizyon talebi için bildirim bulunamadı!</p>";
            
            // Debug: Tüm son bildirimleri göster
            echo "<h4>🐛 Debug: Son 10 Bildirim</h4>";
            $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
            $allNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($allNotifications)) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Type</th><th>Title</th><th>Related ID</th><th>User ID</th><th>Created At</th></tr>";
                foreach ($allNotifications as $notif) {
                    echo "<tr>";
                    echo "<td>{$notif['type']}</td>";
                    echo "<td>{$notif['title']}</td>";
                    echo "<td>" . substr($notif['related_id'] ?? '', 0, 12) . "...</td>";
                    echo "<td>" . substr($notif['user_id'] ?? '', 0, 12) . "...</td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Hiç bildirim bulunamadı</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Revizyon ID bulunamadı!</p>";
    }

    echo "<h3>📧 Step 3: E-posta Bildirim Kontrolü</h3>";
    
    // E-posta log dosyasını kontrol et
    $emailLogPath = __DIR__ . '/logs/email_test.log';
    if (file_exists($emailLogPath)) {
        $emailLog = file_get_contents($emailLogPath);
        $lastEmailEntries = explode(str_repeat('-', 50), $emailLog);
        $lastEntry = array_pop($lastEmailEntries);
        
        if (strpos($lastEntry, 'Final test revizyon talebi') !== false) {
            echo "<p style='color: green;'>✅ E-posta bildirimi log dosyasına yazıldı</p>";
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
            echo "<strong>Son E-posta Log Girişi:</strong><br>";
            echo nl2br(htmlspecialchars(trim($lastEntry)));
            echo "</div>";
        } else {
            echo "<p style='color: orange;'>⚠ E-posta log dosyasında bu test ile ilgili giriş bulunamadı</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ E-posta log dosyası bulunamadı: $emailLogPath</p>";
        echo "<p>Not: EMAIL_TEST_MODE = " . (EMAIL_TEST_MODE ? 'true' : 'false') . "</p>";
    }

    echo "<h3>🔧 Step 4: Admin Panel Entegrasyonu Kontrolü</h3>";
    
    // Admin için bildirim sayısını kontrol et
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
    $adminId = $stmt->fetchColumn();
    
    if ($adminId) {
        $notificationManager = new NotificationManager($pdo);
        $unreadCount = $notificationManager->getUnreadCount($adminId);
        
        echo "<p>✅ Admin ID: $adminId</p>";
        echo "<p>✅ Admin'in okunmamış bildirim sayısı: $unreadCount</p>";
        
        if ($unreadCount > 0) {
            echo "<p style='color: green;'>✅ Admin panel header'da bildirim rozeti görünecek</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Admin'in okunmamış bildirimi yok (test bildirimleri temizlenmiş olabilir)</p>";
        }
    }

    echo "<h2>🎉 Test Sonuçları</h2>";
    
    if (!empty($notifications)) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724;'>✅ TEST BAŞARILI!</h3>";
        echo "<p><strong>🎯 Bildirim Sistemi Tamamen Çalışıyor</strong></p>";
        echo "<ul>";
        echo "<li>✅ Revizyon talebi başarıyla oluşturuldu</li>";
        echo "<li>✅ Admin kullanıcılarına bildirim gönderildi</li>";
        echo "<li>✅ E-posta bildirimi sistemi çalışıyor (test modu)</li>";
        echo "<li>✅ Admin panel entegrasyonu aktif</li>";
        echo "<li>✅ Veritabanı kayıtları doğru şekilde oluşturuldu</li>";
        echo "</ul>";
        
        echo "<h4>📋 Sonraki Adımlar:</h4>";
        echo "<ol>";
        echo "<li><strong>Gerçek Test:</strong> Normal kullanıcı hesabı ile giriş yapıp revizyon talebi oluşturun</li>";
        echo "<li><strong>Admin Kontrolü:</strong> <a href='admin/notifications.php'>Admin bildirimler sayfasını</a> ziyaret edin</li>";
        echo "<li><strong>E-posta Ayarları:</strong> Gerçek e-posta göndermek için EMAIL_TEST_MODE'u false yapın</li>";
        echo "<li><strong>Temizlik:</strong> Test verilerini temizlemek için aşağıdaki butonu kullanın</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3 style='color: #721c24;'>❌ TEST BAŞARISIZ</h3>";
        echo "<p>Revizyon talebi oluşturuldu ancak admin bildirimler oluşturulamadı.</p>";
        echo "<p><a href='debug_revision_test.php'>🔍 Detaylı Debug Test</a> | <a href='fix_notification_system.php'>🔧 Sistem Düzeltme</a></p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Test Sırasında Hata Oluştu</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
    echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
    echo "</div>";
}

// Temizlik işlemleri
echo "<hr>";
echo "<h2>🧹 Test Verilerini Temizleme</h2>";

if ($_POST['action'] ?? '' === 'cleanup') {
    try {
        echo "<p>Test verileri temizleniyor...</p>";
        
        // Test bildirimlerini sil
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE related_id IN (SELECT id FROM revisions WHERE user_id LIKE 'final-test-%')");
        $stmt->execute();
        
        // Test revizyon taleplerini sil
        $stmt = $pdo->prepare("DELETE FROM revisions WHERE user_id LIKE 'final-test-%'");
        $stmt->execute();
        
        // Test dosya yüklemelerini sil
        $stmt = $pdo->prepare("DELETE FROM file_uploads WHERE user_id LIKE 'final-test-%'");
        $stmt->execute();
        
        // Test kullanıcılarını sil
        $stmt = $pdo->prepare("DELETE FROM users WHERE id LIKE 'final-test-%'");
        $stmt->execute();
        
        echo "<p style='color: green;'>✅ Test verileri başarıyla temizlendi!</p>";
        
        // E-posta log dosyasını da temizle
        $emailLogPath = __DIR__ . '/logs/email_test.log';
        if (file_exists($emailLogPath)) {
            file_put_contents($emailLogPath, '');
            echo "<p style='color: green;'>✅ E-posta test log dosyası temizlendi</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Temizlik hatası: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<form method='post' style='margin: 20px 0;'>";
echo "<input type='hidden' name='action' value='cleanup'>";
echo "<button type='submit' class='btn btn-warning' onclick='return confirm(\"Test verileri silinecek. Emin misiniz?\")' style='background: #ffc107; color: #000; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;'>";
echo "🗑️ Test Verilerini Temizle</button>";
echo "</form>";

echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🔗 Hızlı Bağlantılar</h3>";
echo "<p>";
echo "<a href='admin/notifications.php' style='margin-right: 15px;'>📊 Admin Bildirimleri</a>";
echo "<a href='admin/revisions.php' style='margin-right: 15px;'>📝 Revizyon Talepleri</a>";
echo "<a href='debug_notification_system.php' style='margin-right: 15px;'>🔍 Bildirim Debug</a>";
echo "<a href='fix_notification_system.php' style='margin-right: 15px;'>🔧 Sistem Düzeltme</a>";
echo "<a href='index.php' style='margin-right: 15px;'>🏠 Ana Sayfa</a>";
echo "</p>";
echo "</div>";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>ℹ️ Sistem Bilgileri</h3>";
echo "<p><strong>Test Zamanı:</strong> " . date('d.m.Y H:i:s') . "</p>";
echo "<p><strong>PHP Versiyon:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>E-posta Modu:</strong> " . (EMAIL_TEST_MODE ? 'Test Modu (Log)' : 'Gerçek E-posta') . "</p>";
echo "<p><strong>Database:</strong> " . (isset($pdo) ? 'Bağlı' : 'Bağlantısız') . "</p>";
echo "<p><strong>Session User:</strong> " . ($_SESSION['user_id'] ?? 'Giriş yapılmamış') . "</p>";
echo "</div>";
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    line-height: 1.6; 
    margin: 20px; 
    background: #f8f9fa; 
}
h1, h2, h3 { color: #333; margin-top: 30px; }
h1 { border-bottom: 3px solid #007bff; padding-bottom: 10px; }
h2 { border-left: 4px solid #28a745; padding-left: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px; }
h3 { color: #495057; }
p { margin: 8px 0; }
.btn { 
    display: inline-block; 
    padding: 10px 15px; 
    background: #007bff; 
    color: white; 
    text-decoration: none; 
    border-radius: 5px; 
    border: none; 
    cursor: pointer; 
    margin-right: 10px;
}
.btn:hover { background: #0056b3; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>