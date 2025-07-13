<?php
/**
 * Revision Detail - Hata Debug Versiyonu
 */

// Error reporting'i açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Revision Detail Debug</h1>";

try {
    echo "<p>1. ✅ PHP çalışıyor</p>";
    
    require_once '../config/config.php';
    echo "<p>2. ✅ Config yüklendi</p>";
    
    require_once '../config/database.php';
    echo "<p>3. ✅ Database config yüklendi</p>";
    
    // UUID validation function
    if (!function_exists('isValidUUID')) {
        function isValidUUID($uuid) {
            if (!is_string($uuid) || (strlen($uuid) !== 36)) {
                return false;
            }
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
        }
    }
    echo "<p>4. ✅ UUID fonksiyonu tanımlandı</p>";
    
    // Giriş kontrolü
    if (!isLoggedIn()) {
        echo "<p>❌ Giriş yapmamışsınız!</p>";
        echo "<a href='../login.php'>Giriş Yap</a>";
        exit;
    }
    echo "<p>5. ✅ Giriş kontrolü başarılı</p>";
    
    // Revize ID kontrolü
    if (!isset($_GET['id'])) {
        echo "<p>❌ Revize ID belirtilmedi!</p>";
        echo "<p>URL: " . $_SERVER['REQUEST_URI'] . "</p>";
        echo "<a href='revisions.php'>Revize Listesine Dön</a>";
        exit;
    }
    echo "<p>6. ✅ Revize ID parametresi mevcut: " . htmlspecialchars($_GET['id']) . "</p>";
    
    $revisionId = sanitize($_GET['id']);
    $userId = $_SESSION['user_id'];
    echo "<p>7. ✅ ID sanitize edildi: " . htmlspecialchars($revisionId) . "</p>";
    echo "<p>8. ✅ User ID: " . htmlspecialchars($userId) . "</p>";
    
    if (!isValidUUID($revisionId)) {
        echo "<p>❌ Geçersiz revize ID formatı: " . htmlspecialchars($revisionId) . "</p>";
        echo "<a href='revisions.php'>Revize Listesine Dön</a>";
        exit;
    }
    echo "<p>9. ✅ UUID formatı geçerli</p>";
    
    $user = new User($pdo);
    echo "<p>10. ✅ User sınıfı oluşturuldu</p>";
    
    $fileManager = new FileManager($pdo);
    echo "<p>11. ✅ FileManager sınıfı oluşturuldu</p>";
    
    // Session'daki kredi bilgisini güncelle
    $_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
    echo "<p>12. ✅ Krediler güncellendi</p>";
    
    // Revize detaylarını getir
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
    
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$revision) {
        echo "<p>❌ Revize bulunamadı!</p>";
        echo "<p>Aranan ID: " . htmlspecialchars($revisionId) . "</p>";
        echo "<p>User ID: " . htmlspecialchars($userId) . "</p>";
        echo "<a href='revisions.php'>Revize Listesine Dön</a>";
        exit;
    }
    
    echo "<p>15. ✅ Revize verisi bulundu!</p>";
    echo "<h2>Revize Bilgileri:</h2>";
    echo "<pre>";
    print_r($revision);
    echo "</pre>";
    
    echo "<h2>✅ Tüm kontroller başarılı!</h2>";
    echo "<p><a href='revisions.php'>← Revize Listesine Dön</a></p>";
    echo "<p><a href='revision-detail.php?id=" . urlencode($revisionId) . "'>🔄 Normal Detay Sayfasını Dene</a></p>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ HATA: " . $e->getMessage() . "</p>";
    echo "<p>Dosya: " . $e->getFile() . "</p>";
    echo "<p>Satır: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>
