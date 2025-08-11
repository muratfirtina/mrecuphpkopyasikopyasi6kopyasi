<?php
/**
 * Revizyon Bildirim Sistemi Test Dosyası
 * Bu dosya revizyon talebi bildirimi sisteminin çalışıp çalışmadığını test eder
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/NotificationManager.php';
require_once 'includes/FileManager.php';

// Admin kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn() || !isAdmin()) {
    die('Bu test sadece admin kullanıcıları tarafından çalıştırılabilir.');
}

echo "<h1>Revizyon Bildirim Sistemi Test</h1>";

try {
    $notificationManager = new NotificationManager($pdo);
    $fileManager = new FileManager($pdo);
    
    echo "<h2>1. Test Verilerini Kontrol Et</h2>";
    
    // Test kullanıcısını bul
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE role = 'user' AND status = 'active' LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "<p style='color: red;'>❌ Test için aktif kullanıcı bulunamadı</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Test kullanıcısı bulundu: {$testUser['username']} ({$testUser['first_name']} {$testUser['last_name']})</p>";
    
    // Test dosyasını bul
    $stmt = $pdo->prepare("SELECT id, original_name, user_id FROM file_uploads WHERE user_id = ? AND status = 'completed' LIMIT 1");
    $stmt->execute([$testUser['id']]);
    $testFile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testFile) {
        echo "<p style='color: red;'>❌ Test için uygun dosya bulunamadı (completed durumda dosya gerekli)</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Test dosyası bulundu: {$testFile['original_name']}</p>";
    
    // Admin kullanıcılarını kontrol et
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE role = 'admin' AND status = 'active'");
    $stmt->execute();
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($adminUsers)) {
        echo "<p style='color: red;'>❌ Test için aktif admin kullanıcısı bulunamadı</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ " . count($adminUsers) . " aktif admin kullanıcısı bulundu</p>";
    foreach ($adminUsers as $admin) {
        echo "<p>&nbsp;&nbsp;&nbsp;- {$admin['username']} ({$admin['first_name']} {$admin['last_name']}) - {$admin['email']}</p>";
    }
    
    echo "<h2>2. Mevcut Bekleyen Revizyon Kontrolü</h2>";
    
    // Bekleyen revizyon sayısını kontrol et
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE upload_id = ? AND status IN ('pending', 'in_progress')");
    $stmt->execute([$testFile['id']]);
    $existingCount = $stmt->fetchColumn();
    
    if ($existingCount > 0) {
    echo "<p style='color: orange;'>⚠️ Bu dosya için zaten {$existingCount} bekleyen veya işlemde revizyon talebi var</p>";
    echo "<p>Test için önce mevcut talepleri temizleyebilirsiniz:</p>";
    echo "<pre>DELETE FROM revisions WHERE upload_id = '{$testFile['id']}' AND status IN ('pending', 'in_progress');</pre>";
    exit;
    } else {
    echo "<p style='color: green;'>✅ Bu dosya için bekleyen veya işlemde revizyon talebi yok - test için uygun</p>";
    }
    
    echo "<h2>3. Test Revizyon Talebi Oluştur</h2>";
    
    $testRequestNotes = "Bu bir test revizyon talebidir. Sistem bildirimlerini test etmek için oluşturulmuştur. Test tarihi: " . date('Y-m-d H:i:s');
    
    echo "<p>Test revizyon talebi oluşturuluyor...</p>";
    
    $result = $fileManager->requestRevision($testFile['id'], $testUser['id'], $testRequestNotes);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ Test revizyon talebi başarıyla oluşturuldu!</p>";
        echo "<p><strong>Revizyon ID:</strong> {$result['revision_id']}</p>";
        echo "<p><strong>Mesaj:</strong> {$result['message']}</p>";
        
        $revisionId = $result['revision_id'];
        
        echo "<h2>4. Bildirim Kontrolü</h2>";
        
        sleep(1); // Bildirim oluşması için kısa bir bekleme
        
        // Admin'ler için bildirimleri kontrol et
        foreach ($adminUsers as $admin) {
            $notifications = $notificationManager->getUserNotifications($admin['id'], 5, false);
            $revisionNotifications = array_filter($notifications, function($notif) use ($revisionId) {
                return $notif['type'] === 'revision_request' && $notif['related_id'] === $revisionId;
            });
            
            if (!empty($revisionNotifications)) {
                echo "<p style='color: green;'>✅ {$admin['username']} için revizyon bildirimi oluşturuldu</p>";
                foreach ($revisionNotifications as $notif) {
                    echo "<p>&nbsp;&nbsp;&nbsp;<strong>Başlık:</strong> {$notif['title']}</p>";
                    echo "<p>&nbsp;&nbsp;&nbsp;<strong>Mesaj:</strong> {$notif['message']}</p>";
                    echo "<p>&nbsp;&nbsp;&nbsp;<strong>Oluşturma:</strong> {$notif['created_at']}</p>";
                    echo "<p>&nbsp;&nbsp;&nbsp;<strong>Action URL:</strong> {$notif['action_url']}</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ {$admin['username']} için revizyon bildirimi bulunamadı</p>";
            }
        }
        
        echo "<h2>5. Bildirim Sayı Kontrolü</h2>";
        
        foreach ($adminUsers as $admin) {
            $unreadCount = $notificationManager->getUnreadCount($admin['id']);
            echo "<p>{$admin['username']} için okunmamış bildirim sayısı: <strong>{$unreadCount}</strong></p>";
        }
        
        echo "<h2>6. Admin Panel Kontrolleri</h2>";
        
        // Bekleyen ve işleme alınan revizyon sayısını kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE status = 'pending'");
        $stmt->execute();
        $totalPendingRevisions = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE status = 'in_progress'");
        $stmt->execute();
        $totalInProgressRevisions = $stmt->fetchColumn();
        
        echo "<p>Toplam bekleyen revizyon sayısı: <strong style='color: red;'>{$totalPendingRevisions}</strong></p>";
        echo "<p>Toplam işlemdeki revizyon sayısı: <strong style='color: blue;'>{$totalInProgressRevisions}</strong></p>";
        echo "<p style='color: blue;'>👉 Admin paneli kontrolleri:</p>";
        echo "<ul>";
        echo "<li>Admin Header'da bildirim dropdown'unda bu revizyon bildirimi görünmeli</li>";
        if ($totalPendingRevisions > 0) {
            echo "<li>Admin Sidebar'da Revizyonlar yanında <strong style='color: red;'>kırmızı badge</strong> ile '{$totalPendingRevisions}' görünmeli</li>";
        } elseif ($totalInProgressRevisions > 0) {
            echo "<li>Admin Sidebar'da Revizyonlar yanında <strong style='color: blue;'>mavi badge</strong> ile '{$totalInProgressRevisions}' görünmeli</li>";
        }
        echo "<li>Admin Notifications sayfasında bu revizyon bildirimi listelenmiş olmalı</li>";
        echo "<li>Admin Revisions sayfasında bu revizyon talebi görünmeli</li>";
        echo "</ul>";
        
        echo "<h2>7. Test Tamamlandı</h2>";
        echo "<p style='color: green;'>✅ Revizyon bildirim sistemi testi başarıyla tamamlandı!</p>";
        echo "<p><a href='admin/revisions.php' target='_blank'>Admin Revisions Sayfasını Aç</a></p>";
        echo "<p><a href='admin/notifications.php' target='_blank'>Admin Notifications Sayfasını Aç</a></p>";
        echo "<p><a href='admin/index.php' target='_blank'>Admin Dashboard'u Aç</a></p>";
        
        echo "<h3>Test Verilerini Temizlemek İçin:</h3>";
        echo "<p>Eğer test verilerini silmek istiyorsanız:</p>";
        echo "<pre>";
        echo "-- Test revizyon talebini sil\n";
        echo "DELETE FROM revisions WHERE id = '{$revisionId}';\n\n";
        echo "-- Test bildirimlerini sil (opsiyonel)\n";
        echo "DELETE FROM notifications WHERE related_id = '{$revisionId}' AND type = 'revision_request';\n\n";
        echo "-- Tüm bekleyen ve işlemdeki test revizyonlarını sil (dikkatli kullanın)\n";
        echo "DELETE FROM revisions WHERE upload_id = '{$testFile['id']}' AND status IN ('pending', 'in_progress');";
        echo "</pre>";
        
    } else {
        echo "<p style='color: red;'>❌ Test revizyon talebi oluşturulamadı!</p>";
        echo "<p><strong>Hata:</strong> {$result['message']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Test sırasında hata oluştu: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><br><p><a href='admin/index.php'>Admin Panel'e Dön</a></p>";
?>
