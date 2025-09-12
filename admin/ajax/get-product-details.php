<?php
/**
 * AJAX - Ürün Detayları Getir
 */

// Hata raporlamayı aç ve hataları yakala
error_reporting(E_ALL);
ini_set('display_errors', 0); // AJAX'ta görüntülemeyi kapat ama loglama devam etsin
ini_set('log_errors', 1);

// Output buffer başlat
ob_start();

try {
    header('Content-Type: application/json');
    
    require_once '../../config/config.php';
    require_once '../../config/database.php';
    require_once '../../includes/functions.php';
    
    // Buffer'daki istenmeyen çıktıları temizle
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log('AJAX Warning: Unexpected output detected: ' . $output);
    }
    
} catch (Exception $e) {
    // Buffer temizle
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Config loading error: ' . $e->getMessage()]);
    exit;
}

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün ID']);
    exit;
}

try {
    // Ürün bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.name as category_name,
               pb.name as brand_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        exit;
    }
    
    // Ürün resimlerini getir
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll();
    
    // Kategorileri getir
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Markaları getir
    $stmt = $pdo->query("SELECT id, name FROM product_brands WHERE is_active = 1 ORDER BY name");
    $brands = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'images' => $images,
        'categories' => $categories,
        'brands' => $brands
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
