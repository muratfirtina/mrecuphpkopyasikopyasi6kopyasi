<?php
/**
 * AJAX - Ürün Güncelleme (FULL VERSION)
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// JSON header
header('Content-Type: application/json');

try {
    require_once '../../config/config.php';
    require_once '../../config/database.php';
    require_once '../../includes/functions.php';
    
    // Admin kontrolü
    if (!isLoggedIn() || !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
        exit;
    }

    // POST verilerini al
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
    $currency = sanitize($_POST['currency'] ?? 'TL');
    $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0) ?: null;
    $dimensions = sanitize($_POST['dimensions'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $metaTitle = sanitize($_POST['meta_title'] ?? '');
    $metaDescription = sanitize($_POST['meta_description'] ?? '');
    $eticaretUrl = !empty($_POST['eticareturl']) ? sanitize($_POST['eticareturl']) : null;
    
    // Validasyon
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Ürün adı gerekli']);
        exit;
    }
    
    if ($price < 0) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir fiyat girin']);
        exit;
    }
    
    // Slug oluştur/kontrol et
    if (empty($slug)) {
        // createSlug() fonksiyonunu kullan (Türkçe karakter desteği ile)
        $slug = createSlug($name);
    }
    
    // SKU kontrolü
    if (empty($sku)) {
        // Basit SKU oluşturma
        $sku = 'PRD-' . str_pad($productId, 5, '0', STR_PAD_LEFT);
    }
    
    // Ürünü güncelle
    $stmt = $pdo->prepare("
        UPDATE products SET 
        name = ?, slug = ?, category_id = ?, brand_id = ?, 
        short_description = ?, description = ?, sku = ?, 
        price = ?, sale_price = ?, currency = ?, stock_quantity = ?, 
        weight = ?, dimensions = ?, featured = ?, is_active = ?, 
        sort_order = ?, meta_title = ?, meta_description = ?, eticareturl = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $name, $slug, $categoryId, $brandId, 
        $shortDescription, $description, $sku, 
        $price, $salePrice, $currency, $stockQuantity, 
        $weight, $dimensions, $featured, $isActive, 
        $sortOrder, $metaTitle, $metaDescription, $eticaretUrl, $productId
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Ürün başarıyla güncellendi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Güncelleme başarısız']);
    }
    
} catch (PDOException $e) {
    error_log('AJAX PDO Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('AJAX General Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
