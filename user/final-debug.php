<?php
/**
 * Son Debug - Revision Detail Tamamen Test
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Final Revision Detail Debug</h1>";

try {
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    // UUID validation function
    if (!function_exists('isValidUUID')) {
        function isValidUUID($uuid) {
            return !empty($uuid) && is_string($uuid) && strlen($uuid) >= 32;
        }
    }
    
    // Helper function
    if (!function_exists('formatFileSize')) {
        function formatFileSize($bytes) {
            if ($bytes == 0) return '0 B';
            $k = 1024;
            $sizes = array('B', 'KB', 'MB', 'GB', 'TB');
            $i = floor(log($bytes) / log($k));
            return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
        }
    }
    
    echo "<p>1. ✅ Config ve fonksiyonlar yüklendi</p>";
    
    // Session check
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        echo "<p>❌ Giriş yapmamışsınız!</p>";
        exit;
    }
    echo "<p>2. ✅ Giriş kontrolü başarılı</p>";
    
    // ID Check
    if (!isset($_GET['id'])) {
        echo "<p>❌ ID parametresi yok!</p>";
        exit;
    }
    
    $revisionId = sanitize($_GET['id']);
    $userId = $_SESSION['user_id'];
    
    echo "<p>3. ✅ Parameters: ID='" . htmlspecialchars($revisionId) . "', UserID='" . htmlspecialchars($userId) . "'</p>";
    
    if (!isValidUUID($revisionId)) {
        echo "<p>❌ UUID geçersiz!</p>";
        exit;
    }
    echo "<p>4. ✅ UUID geçerli</p>";
    
    // Class check
    $user = new User($pdo);
    $fileManager = new FileManager($pdo);
    $_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
    echo "<p>5. ✅ Sınıflar oluşturuldu ve krediler güncellendi</p>";
    
    // Database query - en basit hali
    echo "<h3>Database Query Test</h3>";
    $stmt = $pdo->prepare("
        SELECT r.*, 
               fu.original_name, fu.filename, fu.file_size, fu.status as file_status, fu.upload_date as file_uploaded_at,
               fu.file_type, fu.hp_power, fu.nm_torque, fu.plate,
               u.username as admin_username, u.first_name as admin_first_name, u.last_name as admin_last_name,
               br.name as brand_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN users u ON r.admin_id = u.id
        LEFT JOIN brands br ON fu.brand_id = br.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    
    echo "<p>6. ✅ SQL hazırlandı</p>";
    
    $result = $stmt->execute([$revisionId, $userId]);
    echo "<p>7. ✅ SQL çalıştırıldı - Sonuç: " . ($result ? 'Başarılı' : 'Başarısız') . "</p>";
    
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$revision) {
        echo "<p>❌ Revize bulunamadı!</p>";
        
        // Debug için basit sorgu yapalım
        $debugStmt = $pdo->prepare("SELECT id, user_id, status FROM revisions WHERE id = ?");
        $debugStmt->execute([$revisionId]);
        $debugRevision = $debugStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debugRevision) {
            echo "<p>Revize mevcut ama user_id eşleşmiyor:</p>";
            echo "<p>DB User ID: " . htmlspecialchars($debugRevision['user_id']) . "</p>";
            echo "<p>Session User ID: " . htmlspecialchars($userId) . "</p>";
            echo "<p>Status: " . htmlspecialchars($debugRevision['status']) . "</p>";
        } else {
            echo "<p>Revize ID hiç bulunamadı!</p>";
            
            // Tüm revizeler
            $allStmt = $pdo->prepare("SELECT id, user_id FROM revisions LIMIT 5");
            $allStmt->execute();
            $allRevisions = $allStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Mevcut Revizeler:</h4>";
            foreach ($allRevisions as $rev) {
                echo "<p>ID: " . htmlspecialchars($rev['id']) . " | User: " . htmlspecialchars($rev['user_id']) . "</p>";
            }
        }
        exit;
    }
    
    echo "<p>8. ✅ Revize bulundu!</p>";
    
    // Status config
    $statusConfig = [
        'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock', 'bg' => 'warning'],
        'in_progress' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cog', 'bg' => 'info'],
        'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle', 'bg' => 'success'],
        'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle', 'bg' => 'danger'],
        'cancelled' => ['class' => 'secondary', 'text' => 'İptal Edildi', 'icon' => 'ban', 'bg' => 'secondary']
    ];
    $currentStatus = $statusConfig[$revision['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question', 'bg' => 'secondary'];
    
    echo "<p>9. ✅ Status config tamamlandı</p>";
    
    echo "<h2>🎉 TÜM KONTROLLER BAŞARILI!</h2>";
    echo "<p>Revision detail sayfası çalışmalı. Şimdi include dosyalarını test edelim:</p>";
    
    // Include test
    if (file_exists('../includes/user_header.php')) {
        echo "<p>✅ user_header.php mevcut</p>";
    } else {
        echo "<p>❌ user_header.php bulunamadı!</p>";
    }
    
    if (file_exists('_sidebar.php')) {
        echo "<p>✅ _sidebar.php mevcut</p>";
    } else {
        echo "<p>❌ _sidebar.php bulunamadı!</p>";
    }
    
    echo "<h3>Revize Verisi:</h3>";
    echo "<table border='1' cellpadding='5'>";
    foreach ($revision as $key => $value) {
        echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>Test Linkleri:</h2>";
    echo "<p><a href='revision-detail.php?id=" . urlencode($revisionId) . "' style='background: green; color: white; padding: 10px; text-decoration: none;'>🔗 Normal Revision Detail</a></p>";
    echo "<p><a href='revisions.php' style='background: blue; color: white; padding: 10px; text-decoration: none;'>🔗 Revisions Listesi</a></p>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ HATA: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Dosya: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Satır: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
