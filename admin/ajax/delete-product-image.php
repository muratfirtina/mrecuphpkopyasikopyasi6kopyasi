<?php
/**
 * AJAX - Ürün Resmi Sil
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
    $stmt = $pdo->prepare("SELECT id, product_id, image_path, is_primary FROM product_images WHERE id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Resim bulunamadı']);
        exit;
    }
    
    // Fiziksel dosyayı sil
    $fullPath = '../../' . $image['image_path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
    
    // Veritabanından sil
    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
    $stmt->execute([$imageId]);
    
    // Eğer silinene ana resim ise, bir sonraki resmi ana resim yap
    if ($image['is_primary'] == 1) {
        $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE product_id = ? ORDER BY sort_order LIMIT 1");
        $stmt->execute([$image['product_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Resim başarıyla silindi'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
