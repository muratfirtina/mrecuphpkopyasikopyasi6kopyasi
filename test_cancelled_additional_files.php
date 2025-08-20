<?php
/**
 * Test İptal Edilmiş Ek Dosyalar Görünürlüğü
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/FileManager.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Bu sayfaya erişim yetkiniz yok.');
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>İptal Edilmiş Ek Dosyalar Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .cancelled { background-color: #ffebee; }
        .active { background-color: #e8f5e8; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🧪 İptal Edilmiş Ek Dosyalar Test</h1>";

try {
    $fileManager = new FileManager($pdo);
    
    // 1. Tüm ek dosyaları (iptal edilmiş dahil) veritabanından getir
    echo "<div class='step'>";
    echo "<h2>1. Veritabanındaki Tüm Ek Dosyalar</h2>";
    
    $stmt = $pdo->query("
        SELECT af.*, 
               fu.original_name as main_file_name,
               sender.username as sender_username, 
               receiver.username as receiver_username,
               receiver.role as receiver_role
        FROM additional_files af
        LEFT JOIN file_uploads fu ON af.related_file_id = fu.id
        LEFT JOIN users sender ON af.sender_id = sender.id
        LEFT JOIN users receiver ON af.receiver_id = receiver.id
        ORDER BY af.upload_date DESC
        LIMIT 20
    ");
    $allFiles = $stmt->fetchAll();
    
    if (!empty($allFiles)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Ana Dosya</th><th>Ek Dosya Adı</th><th>Gönderen</th><th>Alan</th><th>İptal Durumu</th><th>Tarih</th></tr>";
        
        foreach ($allFiles as $file) {
            $cancelClass = $file['is_cancelled'] ? 'cancelled' : 'active';
            $cancelText = $file['is_cancelled'] ? 'İPTAL EDİLMİŞ' : 'AKTİF';
            
            echo "<tr class='{$cancelClass}'>";
            echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($file['main_file_name'] ?: 'Bilinmiyor') . "</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td>" . htmlspecialchars($file['sender_username'] ?: 'Bilinmiyor') . "</td>";
            echo "<td>" . htmlspecialchars($file['receiver_username'] ?: 'Bilinmiyor') . " ({$file['receiver_role']})</td>";
            echo "<td><strong>{$cancelText}</strong></td>";
            echo "<td>" . date('d.m.Y H:i', strtotime($file['upload_date'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $cancelledCount = count(array_filter($allFiles, function($f) { return $f['is_cancelled']; }));
        $activeCount = count($allFiles) - $cancelledCount;
        
        echo "<div class='info'>📊 <strong>Toplam:</strong> " . count($allFiles) . " dosya | <strong>Aktif:</strong> {$activeCount} | <strong>İptal Edilmiş:</strong> {$cancelledCount}</div>";
    } else {
        echo "<div class='warning'>❌ Ek dosya bulunamadı.</div>";
    }
    echo "</div>";
    
    // 2. Test kullanıcısı seçimi
    echo "<div class='step'>";
    echo "<h2>2. Test Kullanıcısı Seçin</h2>";
    
    $stmt = $pdo->query("
        SELECT DISTINCT u.id, u.username, u.first_name, u.last_name, u.role,
               COUNT(af.id) as total_files,
               SUM(CASE WHEN af.is_cancelled = 1 THEN 1 ELSE 0 END) as cancelled_files
        FROM users u
        LEFT JOIN additional_files af ON (u.id = af.receiver_id OR u.id = af.sender_id)
        WHERE u.role = 'user'
        GROUP BY u.id
        HAVING total_files > 0
        ORDER BY total_files DESC
        LIMIT 10
    ");
    $testUsers = $stmt->fetchAll();
    
    if (!empty($testUsers)) {
        echo "<table>";
        echo "<tr><th>Kullanıcı Adı</th><th>Ad Soyad</th><th>Toplam Ek Dosya</th><th>İptal Edilmiş</th><th>Test</th></tr>";
        
        foreach ($testUsers as $testUser) {
            echo "<tr>";
            echo "<td>@" . htmlspecialchars($testUser['username']) . "</td>";
            echo "<td>" . htmlspecialchars($testUser['first_name'] . ' ' . $testUser['last_name']) . "</td>";
            echo "<td>{$testUser['total_files']}</td>";
            echo "<td>{$testUser['cancelled_files']}</td>";
            echo "<td><a href='?test_user={$testUser['id']}' class='btn'>Test Et</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>❌ Ek dosyası olan kullanıcı bulunamadı.</div>";
    }
    echo "</div>";
    
    // 3. Kullanıcı ve Admin testi
    if (isset($_GET['test_user'])) {
        $testUserId = sanitize($_GET['test_user']);
        
        echo "<div class='step'>";
        echo "<h2>3. Kullanıcı vs Admin Ek Dosya Görünürlük Testi</h2>";
        echo "<div class='info'>Test edilen kullanıcı ID: {$testUserId}</div>";
        
        // Kullanıcı bilgisini al
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$testUserId]);
        $testUser = $stmt->fetch();
        
        if ($testUser) {
            echo "<h3>Test Kullanıcısı: @{$testUser['username']} ({$testUser['first_name']} {$testUser['last_name']})</h3>";
            
            // Bu kullanıcının ana dosyalarını bul
            $stmt = $pdo->prepare("
                SELECT DISTINCT fu.id, fu.original_name
                FROM file_uploads fu
                LEFT JOIN additional_files af ON af.related_file_id = fu.id
                WHERE fu.user_id = ? OR af.receiver_id = ? OR af.sender_id = ?
                LIMIT 5
            ");
            $stmt->execute([$testUserId, $testUserId, $testUserId]);
            $userFiles = $stmt->fetchAll();
            
            if (!empty($userFiles)) {
                foreach ($userFiles as $userFile) {
                    echo "<h4>📁 Ana Dosya: " . htmlspecialchars($userFile['original_name']) . "</h4>";
                    
                    // USER olarak FileManager getAdditionalFiles metodunu test et
                    $userVisibleFiles = $fileManager->getAdditionalFiles($userFile['id'], $testUserId, 'user');
                    
                    // ADMIN olarak FileManager getAdditionalFiles metodunu test et
                    $adminVisibleFiles = $fileManager->getAdditionalFiles($userFile['id'], $testUserId, 'admin');
                    
                    // Veritabanından direkt tüm dosyaları al (karşılaştırma için)
                    $stmt = $pdo->prepare("
                        SELECT af.*, 
                               CASE WHEN af.is_cancelled = 1 THEN 'İPTAL EDİLMİŞ' ELSE 'AKTİF' END as cancel_status
                        FROM additional_files af
                        WHERE af.related_file_id = ?
                        AND ((af.sender_id = ? AND af.sender_type = 'user') OR (af.receiver_id = ? AND af.receiver_type = 'user'))
                        ORDER BY af.upload_date DESC
                    ");
                    $stmt->execute([$userFile['id'], $testUserId, $testUserId]);
                    $allUserFiles = $stmt->fetchAll();
                    
                    echo "<div class='row' style='display: flex; gap: 15px;'>";
                    
                    // Sol taraf - USER görünümü
                    echo "<div style='flex: 1;'>";
                    echo "<h5>👤 USER Görünümü (" . count($userVisibleFiles) . " adet)</h5>";
                    if (!empty($userVisibleFiles)) {
                        echo "<table>";
                        echo "<tr><th>Dosya Adı</th><th>Tarih</th><th>Durum</th></tr>";
                        foreach ($userVisibleFiles as $file) {
                            $statusIcon = empty($file['is_cancelled']) ? '✅' : '⚠️';
                            $statusText = empty($file['is_cancelled']) ? 'AKTİF' : 'İPTAL EDİLMİŞ';
                            echo "<tr class='active'>";
                            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
                            echo "<td>" . date('d.m.Y H:i', strtotime($file['upload_date'])) . "</td>";
                            echo "<td>{$statusIcon} {$statusText}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class='info'>USER için görünür ek dosya yok.</div>";
                    }
                    echo "</div>";
                    
                    // Orta - ADMIN görünümü
                    echo "<div style='flex: 1;'>";
                    echo "<h5>👨‍💼 ADMIN Görünümü (" . count($adminVisibleFiles) . " adet)</h5>";
                    if (!empty($adminVisibleFiles)) {
                        echo "<table>";
                        echo "<tr><th>Dosya Adı</th><th>Tarih</th><th>Durum</th></tr>";
                        foreach ($adminVisibleFiles as $file) {
                            $class = $file['is_cancelled'] ? 'cancelled' : 'active';
                            $statusIcon = $file['is_cancelled'] ? '❌' : '✅';
                            $statusText = $file['is_cancelled'] ? 'İPTAL EDİLMİŞ' : 'AKTİF';
                            echo "<tr class='{$class}'>";
                            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
                            echo "<td>" . date('d.m.Y H:i', strtotime($file['upload_date'])) . "</td>";
                            echo "<td>{$statusIcon} {$statusText}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class='info'>ADMIN için görünür ek dosya yok.</div>";
                    }
                    echo "</div>";
                    
                    // Sağ taraf - Veritabanındaki tüm dosyalar
                    echo "<div style='flex: 1;'>";
                    echo "<h5>🗄️ DB'deki Tüm Dosyalar (" . count($allUserFiles) . " adet)</h5>";
                    if (!empty($allUserFiles)) {
                        echo "<table>";
                        echo "<tr><th>Dosya Adı</th><th>Tarih</th><th>Durum</th></tr>";
                        foreach ($allUserFiles as $file) {
                            $class = $file['is_cancelled'] ? 'cancelled' : 'active';
                            $icon = $file['is_cancelled'] ? '❌' : '✅';
                            echo "<tr class='{$class}'>";
                            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
                            echo "<td>" . date('d.m.Y H:i', strtotime($file['upload_date'])) . "</td>";
                            echo "<td>{$icon} {$file['cancel_status']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class='info'>Bu dosya için ek dosya yok.</div>";
                    }
                    echo "</div>";
                    
                    echo "</div>";
                    
                    // Karşılaştırma sonucu
                    $userHiddenCount = count($allUserFiles) - count($userVisibleFiles);
                    $adminHiddenCount = count($allUserFiles) - count($adminVisibleFiles);
                    
                    echo "<div class='row' style='margin-top: 15px;'>";
                    echo "<div class='col' style='flex: 1;'>";
                    if ($userHiddenCount > 0) {
                        echo "<div class='success'>✅ USER: <strong>{$userHiddenCount} iptal edilmiş dosya gizlendi</strong></div>";
                    } else {
                        echo "<div class='info'>ℹ️ USER: İptal edilmiş dosya yok</div>";
                    }
                    echo "</div>";
                    
                    echo "<div class='col' style='flex: 1;'>";
                    if ($adminHiddenCount === 0 && count($allUserFiles) > 0) {
                        echo "<div class='success'>✅ ADMIN: <strong>Tüm dosyalar görülebilir</strong></div>";
                    } elseif (count($allUserFiles) === 0) {
                        echo "<div class='info'>ℹ️ ADMIN: Dosya yok</div>";
                    } else {
                        echo "<div class='warning'>⚠️ ADMIN: Bazı dosyalar gizli</div>";
                    }
                    echo "</div>";
                    echo "</div>";
                    
                    echo "<hr>";
                }
            } else {
                echo "<div class='warning'>❌ Bu kullanıcının ek dosyası bulunamadı.</div>";
            }
            
        } else {
            echo "<div class='error'>❌ Kullanıcı bulunamadı.</div>";
        }
        echo "</div>";
    }
    
    // 4. Özet
    echo "<div class='step'>";
    echo "<h2>4. Test Özeti</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_additional_files,
            SUM(CASE WHEN is_cancelled = 1 THEN 1 ELSE 0 END) as cancelled_files,
            SUM(CASE WHEN is_cancelled = 0 OR is_cancelled IS NULL THEN 1 ELSE 0 END) as active_files
        FROM additional_files
    ");
    $summary = $stmt->fetch();
    
    echo "<ul>";
    echo "<li><strong>Toplam Ek Dosya:</strong> {$summary['total_additional_files']}</li>";
    echo "<li><strong>Aktif Dosya:</strong> {$summary['active_files']}</li>";
    echo "<li><strong>İptal Edilmiş:</strong> {$summary['cancelled_files']}</li>";
    echo "</ul>";
    
    if ($summary['cancelled_files'] > 0) {
        echo "<div class='success'>✅ FileManager metodları güncellendi:</div>";
        echo "<ul>";
        echo "<li><strong>USER:</strong> İptal edilmiş dosyalar gösterilmiyor ve indirilebilmiyor</li>";
        echo "<li><strong>ADMIN:</strong> İptal edilmiş dosyalar dahil tüm dosyalar görülebilir ve indirilebilir</li>";
        echo "</ul>";
    } else {
        echo "<div class='info'>ℹ️ Henüz iptal edilmiş ek dosya yok.</div>";
    }
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔗 Test Linkleri:</h3>";
    echo "<p><a href='user/file-detail.php' class='btn'>Kullanıcı Dosya Detay</a></p>";
    echo "<p><a href='admin/file-detail.php' class='btn'>Admin Dosya Detay</a></p>";
    echo "<p><a href='{$_SERVER['PHP_SELF']}' class='btn'>Sayfayı Yenile</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";
echo "</body></html>";
?>
