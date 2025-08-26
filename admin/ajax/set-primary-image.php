<?php
/**
 * AJAX - Ana Resim Belirle
 */

header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$imageId = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

if ($imageId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz resim ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Resim bilgilerini getir
    $stmt = $pdo->prepare("SELECT product_id FROM product_images WHERE id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Resim bulunamadı']);
        exit;
    }
    
    // Önce tüm resimleri ana olmaktan çıkar
    $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
    $stmt->execute([$image['product_id']]);
    
    // Seçilen resmi ana resim yap
    $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?");
    $stmt->execute([$imageId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Ana resim güncellendi'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
