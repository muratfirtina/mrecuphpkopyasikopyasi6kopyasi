<?php
/**
 * Bildirim URL'lerini Düzelt
 * Veritabanındaki eski bildirim URL formatlarını yeni formata çevirir
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Bildirim URL'lerini Düzelt</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .fixed { background: #d4edda; }
    </style>
</head>
<body>";

echo "<h1>🔧 Bildirim URL'lerini Düzelt</h1>";

try {
    // 1. Mevcut sorunlu URL'leri tespit et
    echo "<h2>📋 Mevcut Bildirimler Analizi</h2>";
    
    $stmt = $pdo->query("
        SELECT id, user_id, type, title, action_url, created_at 
        FROM notifications 
        WHERE action_url IS NOT NULL 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Son 20 bildirim:</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Action URL</th><th>Tarih</th><th>Durum</th></tr>";
    
    $problemCount = 0;
    foreach ($notifications as $notification) {
        $isProblematic = (
            strpos($notification['action_url'], '../user/') !== false ||
            strpos($notification['action_url'], 'user/user/') !== false ||
            strpos($notification['action_url'], '/user/user/') !== false ||
            strpos($notification['action_url'], 'uploads.php') !== false ||
            // user/ ile başlayan URL'ler de sorunlu (user/file-detail.php gibi)
            (strpos($notification['action_url'], 'user/') === 0 && $notification['action_url'] !== 'user/')
        );
        
        if ($isProblematic) {
            $problemCount++;
        }
        
        $rowClass = $isProblematic ? 'style="background: #ffebee;"' : '';
        $status = $isProblematic ? '❌ Sorunlu' : '✅ OK';
        
        echo "<tr $rowClass>";
        echo "<td>" . substr($notification['id'], 0, 8) . "...</td>";
        echo "<td>{$notification['type']}</td>";
        echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
        echo "<td>" . htmlspecialchars($notification['action_url']) . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($notification['created_at'])) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($problemCount > 0) {
        echo "<div class='warning'>⚠️ $problemCount adet sorunlu URL tespit edildi!</div>";
    } else {
        echo "<div class='success'>✅ Tüm URL'ler temiz görünüyor!</div>";
    }
    
    // 2. Sorunlu URL'leri düzelt
    if ($problemCount > 0) {
        echo "<h2>🔧 URL Düzeltme İşlemi</h2>";
        
        $fixedCount = 0;
        
        // ../user/ prefiksi olanları düzelt
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET action_url = REPLACE(action_url, '../user/', '') 
            WHERE action_url LIKE '%../user/%'
        ");
        if ($stmt->execute()) {
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "<div class='info'>📝 $count bildirimde '../user/' prefiksi kaldırıldı</div>";
                $fixedCount += $count;
            }
        }
        
        // /user/user/ çift tekrarını düzelt
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET action_url = REPLACE(action_url, '/user/user/', '/user/') 
            WHERE action_url LIKE '%/user/user/%'
        ");
        if ($stmt->execute()) {
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "<div class='info'>📝 $count bildirimde '/user/user/' çift tekrarı düzeltildi</div>";
                $fixedCount += $count;
            }
        }
        
        // user/ prefiksi olanları da düzelt (başında slash olmayan)
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET action_url = REPLACE(action_url, 'user/', '') 
            WHERE action_url LIKE 'user/%'
        ");
        if ($stmt->execute()) {
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "<div class='info'>📝 $count bildirimde 'user/' prefiksi kaldırıldı</div>";
                $fixedCount += $count;
            }
        }
        
        // Admin için uploads.php -> file-detail.php değiştir
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET action_url = REPLACE(action_url, 'uploads.php', 'file-detail.php') 
            WHERE action_url LIKE '%uploads.php%'
        ");
        if ($stmt->execute()) {
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "<div class='info'>📝 $count bildirimde 'uploads.php' -> 'file-detail.php' değiştirildi</div>";
                $fixedCount += $count;
            }
        }
        
        // Admin için revisions.php -> revision-detail.php değiştir (sadece admin bildirimleri için)
        $stmt = $pdo->prepare("
            UPDATE notifications n
            INNER JOIN users u ON n.user_id = u.id
            SET n.action_url = REPLACE(n.action_url, 'revisions.php', 'revision-detail.php') 
            WHERE n.action_url LIKE '%revisions.php%'
            AND u.role = 'admin'
        ");
        if ($stmt->execute()) {
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "<div class='info'>📝 $count admin bildiriminde 'revisions.php' -> 'revision-detail.php' değiştirildi</div>";
                $fixedCount += $count;
            }
        }
        
        echo "<div class='success'>✅ Toplam $fixedCount bildirim düzeltildi!</div>";
    }
    
    // 3. Düzeltme sonrası kontrol
    echo "<h2>📋 Düzeltme Sonrası Durum</h2>";
    
    $stmt = $pdo->query("
        SELECT id, user_id, type, title, action_url, created_at 
        FROM notifications 
        WHERE action_url IS NOT NULL 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Action URL</th><th>Tarih</th><th>Durum</th></tr>";
    
    $remainingProblems = 0;
    foreach ($notifications as $notification) {
        $isProblematic = (
            strpos($notification['action_url'], '../user/') !== false ||
            strpos($notification['action_url'], 'user/user/') !== false ||
            strpos($notification['action_url'], '/user/user/') !== false ||
            // user/ ile başlayan URL'ler de sorunlu (user/file-detail.php gibi)
            (strpos($notification['action_url'], 'user/') === 0 && $notification['action_url'] !== 'user/')
        );
        
        if ($isProblematic) {
            $remainingProblems++;
        }
        
        $rowClass = $isProblematic ? 'style="background: #ffebee;"' : 'class="fixed"';
        $status = $isProblematic ? '❌ Hala Sorunlu' : '✅ Düzeltildi';
        
        echo "<tr $rowClass>";
        echo "<td>" . substr($notification['id'], 0, 8) . "...</td>";
        echo "<td>{$notification['type']}</td>";
        echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
        echo "<td>" . htmlspecialchars($notification['action_url']) . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($notification['created_at'])) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($remainingProblems == 0) {
        echo "<div class='success'>🎉 Tüm URL'ler başarıyla düzeltildi!</div>";
        echo "<div class='info'>💡 Artık bildirimler doğru sayfaya yönlendirecek</div>";
    } else {
        echo "<div class='warning'>⚠️ $remainingProblems adet bildirimde hala sorun var. Elle kontrol edilmesi gerekebilir.</div>";
    }
    
    // 4. Özet istatistikler
    echo "<h2>📊 Genel İstatistikler</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
    $totalNotifications = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as withUrl FROM notifications WHERE action_url IS NOT NULL");
    $notificationsWithUrl = $stmt->fetchColumn();
    
    echo "<ul>";
    echo "<li>Toplam bildirim sayısı: <strong>$totalNotifications</strong></li>";
    echo "<li>URL'li bildirim sayısı: <strong>$notificationsWithUrl</strong></li>";
    echo "<li>Düzeltilen bildirim sayısı: <strong>" . ($fixedCount ?? 0) . "</strong></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata oluştu: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='error'>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

echo "<br><br>";
echo "<p><strong>Test Etme:</strong></p>";
echo "<ul>";
echo "<li><a href='user/files.php' target='_blank'>👤 User Files sayfasını test et</a></li>";
echo "<li><a href='admin/file-detail.php' target='_blank'>🔧 Admin File Detail sayfasını test et</a></li>";
echo "<li><a href='user/revisions.php' target='_blank'>📝 User Revisions sayfasını test et</a></li>";
echo "</ul>";
echo "<br>";
echo "<p><a href='index.php'>🏠 Ana sayfaya dön</a></p>";
echo "</body></html>";
?>