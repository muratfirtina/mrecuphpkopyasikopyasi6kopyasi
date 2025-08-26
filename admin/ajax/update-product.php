<?php
/**
 * AJAX - Ürün Güncelleme
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
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit;
}

try {
    $productId = intval($_POST['product_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0) ?: null;
    $brandId = intval($_POST['brand_id'] ?? 0) ?: null;
    $shortDescription = sanitize($_POST['short_description'] ?? '');
    $description = $_POST['description'] ?? '';
    $sku = sanitize($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $salePrice = floatval($_POST['sale_price'] ?? 0) ?: null;
    $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0) ?: null;
    $dimensions = sanitize($_POST['dimensions'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $metaTitle = sanitize($_POST['meta_title'] ?? '');
    $metaDescription = sanitize($_POST['meta_description'] ?? '');
    
    // Validasyon
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Ürün adı gerekli']);
        exit;
    }
    
    if (empty($slug)) {
        $slug = createSlug($name);
    }
    
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir fiyat girin']);
        exit;
    }
    
    // Slug benzersizlik kontrolü (kendisi hariç)
    $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $productId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu slug zaten kullanımda']);
        exit;
    }
    
    // Ürünü güncelle
    $stmt = $pdo->prepare("
        UPDATE products SET 
        name = ?, slug = ?, category_id = ?, brand_id = ?, 
        short_description = ?, description = ?, sku = ?, 
        price = ?, sale_price = ?, stock_quantity = ?, 
        weight = ?, dimensions = ?, featured = ?, is_active = ?, 
        sort_order = ?, meta_title = ?, meta_description = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $name, $slug, $categoryId, $brandId, 
        $shortDescription, $description, $sku, 
        $price, $salePrice, $stockQuantity, 
        $weight, $dimensions, $featured, $isActive, 
        $sortOrder, $metaTitle, $metaDescription, $productId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Ürün başarıyla güncellendi']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
