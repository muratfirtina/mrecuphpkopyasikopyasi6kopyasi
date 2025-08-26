<?php
/**
 * AJAX - Ürün Resmi Ekle
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

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün ID']);
    exit;
}

if (!isset($_FILES['images']) || empty($_FILES['images']['tmp_name'][0])) {
    echo json_encode(['success' => false, 'message' => 'Resim seçilmedi']);
    exit;
}

// Resim yükleme fonksiyonu
function uploadProductImages($files, $productId) {
    $uploadedImages = [];
    
    if (!isset($files['tmp_name']) || !is_array($files['tmp_name'])) {
        return $uploadedImages;
    }
    
    $uploadDir = '../../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    foreach ($files['tmp_name'] as $index => $tmpName) {
        if (empty($tmpName)) continue;
        
        $fileType = $files['type'][$index];
        $fileSize = $files['size'][$index];
        $fileName = $files['name'][$index];
        
        // Dosya türü kontrolü
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Sadece JPG, PNG, GIF ve WEBP formatları desteklenir: ' . $fileName);
        }
        
        // Dosya boyutu kontrolü
        if ($fileSize > $maxSize) {
            throw new Exception('Dosya boyutu 10MB\'dan büyük olamaz: ' . $fileName);
        }
        
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'product_' . $productId . '_' . uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($tmpName, $filePath)) {
            $uploadedImages[] = [
                'path' => 'uploads/products/' . $newFileName,
                'alt' => pathinfo($fileName, PATHINFO_FILENAME),
                'original_name' => $fileName
            ];
        }
    }
    
    return $uploadedImages;
}

try {
    $pdo->beginTransaction();
    
    // Mevcut resim sayısını kontrol et
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(sort_order) as max_sort FROM product_images WHERE product_id = ?");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    $currentCount = $result['count'];
    $nextSortOrder = ($result['max_sort'] ?? -1) + 1;
    
    // Resimleri yükle
    $uploadedImages = uploadProductImages($_FILES['images'], $productId);
    
    $insertedImages = [];
    
    foreach ($uploadedImages as $sortOrder => $image) {
        // Eğer hiç resim yoksa, ilk resmi ana resim yap
        $isPrimary = ($currentCount == 0 && $sortOrder == 0) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$productId, $image['path'], $image['alt'], $nextSortOrder + $sortOrder, $isPrimary]);
        
        $insertedImages[] = [
            'id' => $pdo->lastInsertId(),
            'product_id' => $productId,
            'image_path' => $image['path'],
            'alt_text' => $image['alt'],
            'sort_order' => $nextSortOrder + $sortOrder,
            'is_primary' => $isPrimary,
            'original_name' => $image['original_name']
        ];
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => count($uploadedImages) . ' resim başarıyla eklendi',
        'images' => $insertedImages
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
