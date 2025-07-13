<?php
/**
 * Revision Detail - Adım Adım Debug
 */

// Error reporting'i açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Revision Detail Step-by-Step Debug</h1>";

try {
    echo "<p>1. ✅ Başlatıldı</p>";
    
    require_once '../config/config.php';
    require_once '../config/database.php';
    echo "<p>2. ✅ Config dosyaları yüklendi</p>";
    
    // UUID validation function - daha basit
    if (!function_exists('isValidUUID')) {
        function isValidUUID($uuid) {
            echo "<div style='background: #ffffcc; padding: 10px; margin: 5px;'>UUID Kontrolü: '" . htmlspecialchars($uuid) . "' (uzunluk: " . strlen($uuid) . ")</div>";
            $result = !empty($uuid) && is_string($uuid) && strlen($uuid) >= 30;
            echo "<div style='background: " . ($result ? '#ccffcc' : '#ffcccc') . "; padding: 10px; margin: 5px;'>UUID Geçerli: " . ($result ? 'EVET' : 'HAYIR') . "</div>";
            return $result;
        }
    }
    echo "<p>3. ✅ UUID fonksiyonu tanımlandı</p>";
    
    // Helper function for file size formatting
    if (!function_exists('formatFileSize')) {
        function formatFileSize($bytes) {
            if ($bytes == 0) return '0 B';
            $k = 1024;
            $sizes = array('B', 'KB', 'MB', 'GB', 'TB');
            $i = floor(log($bytes) / log($k));
            return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
        }
    }
    echo "<p>4. ✅ Helper fonksiyonlar tanımlandı</p>";
    
    // Giriş kontrolü
    if (!isLoggedIn()) {
        echo "<p>❌ Giriş yapmamışsınız! REDIRECT EDILECEK</p>";
        echo "<p>Bu yüzden redirect oluyor!</p>";
        exit;
    }
    echo "<p>5. ✅ Giriş kontrolü başarılı</p>";
    
    // Revize ID kontrolü
    if (!isset($_GET['id'])) {
        echo "<p>❌ Revize ID belirtilmedi! REDIRECT EDILECEK</p>";
        echo "<p>URL: " . $_SERVER['REQUEST_URI'] . "</p>";
        exit;
    }
    echo "<p>6. ✅ GET['id'] mevcut: '" . htmlspecialchars($_GET['id']) . "'</p>";
    
    $originalId = $_GET['id'];
    echo "<p>7. Original ID: '" . htmlspecialchars($originalId) . "'</p>";
    
    $revisionId = sanitize($_GET['id']);
    echo "<p>8. Sanitized ID: '" . htmlspecialchars($revisionId) . "'</p>";
    
    if ($originalId !== $revisionId) {
        echo "<p style='color: orange;'>⚠️ UYARI: sanitize() fonksiyonu ID'yi değiştirdi!</p>";
    }
    
    $userId = $_SESSION['user_id'];
    echo "<p>9. ✅ User ID: " . htmlspecialchars($userId) . "</p>";
    
    // UUID kontrolü
    echo "<h3>UUID Validation Test:</h3>";
    $isValid = isValidUUID($revisionId);
    
    if (!$isValid) {
        echo "<p>❌ UUID GEÇERSİZ! REDIRECT EDILECEK</p>";
        echo "<p>Bu yüzden redirect oluyor!</p>";
        exit;
    }
    echo "<p>10. ✅ UUID geçerli</p>";
    
    $user = new User($pdo);
    $fileManager = new FileManager($pdo);
    echo "<p>11. ✅ Sınıflar oluşturuldu</p>";
    
    // Session'daki kredi bilgisini güncelle
    $_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
    echo "<p>12. ✅ Krediler güncellendi</p>";
    
    // Revize detaylarını getir
    echo "<h3>Database Query Test:</h3>";
    $stmt = $pdo->prepare("
        SELECT r.*, 
               fu.original_name, fu.filename, fu.file_size, fu.status as file_status, fu.created_at as file_uploaded_at,
               fu.file_path, fu.file_type, fu.estimated_completion_time,
               u.username as admin_username, u.first_name as admin_first_name, u.last_name as admin_last_name,
               br.name as brand_name, br.logo as brand_logo,
               cat.name as category_name,
               rev_files.original_name as revision_filename, rev_files.file_path as revision_file_path,
               rev_files.file_size as revision_file_size, rev_files.created_at as revision_uploaded_at
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN users u ON r.admin_id = u.id
        LEFT JOIN brands br ON fu.brand_id = br.id
        LEFT JOIN categories cat ON fu.category_id = cat.id
        LEFT JOIN file_uploads rev_files ON r.revision_file_id = rev_files.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    echo "<p>13. ✅ SQL sorgusu hazırlandı</p>";
    
    $stmt->execute([$revisionId, $userId]);
    echo "<p>14. ✅ SQL sorgusu çalıştırıldı</p>";
    echo "<p>Parameters: ['" . htmlspecialchars($revisionId) . "', '" . htmlspecialchars($userId) . "']</p>";
    
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$revision) {
        echo "<p>❌ Revize bulunamadı! REDIRECT EDILECEK</p>";
        echo "<p>Bu yüzden redirect oluyor!</p>";
        
        // Basit sorgu ile kontrol edelim
        $checkStmt = $pdo->prepare("SELECT id, user_id FROM revisions WHERE id = ?");
        $checkStmt->execute([$revisionId]);
        $check = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check) {
            echo "<p>Revize ID bulundu ama user_id eşleşmiyor:</p>";
            echo "<p>Veritabanındaki user_id: " . htmlspecialchars($check['user_id']) . "</p>";
            echo "<p>Session user_id: " . htmlspecialchars($userId) . "</p>";
        } else {
            echo "<p>Revize ID hiç bulunamadı!</p>";
        }
        exit;
    }
    
    echo "<p>15. ✅ Revize verisi bulundu!</p>";
    echo "<h2>🎉 TÜM KONTROLLER BAŞARILI!</h2>";
    echo "<p>Revision detail sayfası normal olarak açılmalı.</p>";
    
    echo "<h3>Bulunan Revize:</h3>";
    echo "<pre>";
    print_r($revision);
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ DATABASE HATASI: " . $e->getMessage() . "</p>";
    echo "<p>Bu yüzden redirect oluyor!</p>";
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ GENEL HATA: " . $e->getMessage() . "</p>";
    echo "<p>Dosya: " . $e->getFile() . " (Satır: " . $e->getLine() . ")</p>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h3 { color: #333; border-bottom: 1px solid #ccc; }
</style>
