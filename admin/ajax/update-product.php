<?php
/**
 * AJAX - Ürün Güncelleme
 */

header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/database.php';

// Slug oluşturma fonksiyonu
function createSlug($text) {
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    
    // Türkçe karakterleri değiştir
    $tr = array('ş','Ş','ı','I','İ','ğ','Ğ','ü','Ü','ö','Ö','Ç','ç');
    $en = array('s','s','i','i','i','g','g','u','u','o','o','c','c');
    $text = str_replace($tr, $en, $text);
    
    // Sadece harf, rakam ve tire bırak
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

// Benzersiz slug oluşturma fonksiyonu
function createUniqueSlug($pdo, $text, $excludeId = null) {
    $baseSlug = createSlug($text);
    $slug = $baseSlug;
    $counter = 1;
    
    // Benzersiz slug bulana kadar dene
    while (!isSlugUnique($pdo, $slug, $excludeId)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

// Slug benzersizlik kontrolü
function isSlugUnique($pdo, $slug, $excludeId = null) {
    $sql = "SELECT id FROM products WHERE slug = ?";
    $params = [$slug];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch() === false;
}

// Otomatik SKU üretme fonksiyonu
function generateUniqueSKU($pdo, $productName = '', $excludeId = null) {
    // Ürün adından basit bir prefix oluştur
    $prefix = '';
    if (!empty($productName)) {
        $words = explode(' ', $productName);
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $prefix .= strtoupper(substr($word, 0, 1));
            }
            if (strlen($prefix) >= 3) break;
        }
    }
    
    if (empty($prefix)) {
        $prefix = 'PRD';
    }
    
    // Benzersiz SKU bulana kadar dene
    $attempts = 0;
    do {
        $randomNumber = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $sku = $prefix . '-' . $randomNumber;
        
        // Bu SKU kullanılıyor mu kontrol et
        $sql = "SELECT COUNT(*) FROM products WHERE sku = ?";
        $params = [$sku];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = $stmt->fetchColumn() > 0;
        
        $attempts++;
        if ($attempts > 100) {
            // Çok fazla deneme yapıldı, timestamp ekle
            $sku = $prefix . '-' . time() . '-' . mt_rand(100, 999);
            break;
        }
    } while ($exists);
    
    return $sku;
}

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
    
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir fiyat girin']);
        exit;
    }
    
    // SKU boşsa otomatik üret
    if (empty($sku)) {
        $sku = generateUniqueSKU($pdo, $name, $productId);
    } else {
        // Kullanıcı SKU girdi, duplicate kontrolu yap (mevcut ürün hariç)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ? AND id != ?");
        $stmt->execute([$sku, $productId]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Bu SKU zaten başka bir ürün tarafından kullanılmakta.']);
            exit;
        }
    }
    
    // Benzersiz slug oluştur (mevcut ürün ID'si hariç)
    if (empty($slug)) {
        $slug = createUniqueSlug($pdo, $name, $productId);
    } else {
        // Manuel slug girildi, benzersizlik kontrolü yap
        if (!isSlugUnique($pdo, $slug, $productId)) {
            echo json_encode(['success' => false, 'message' => 'Bu slug zaten kullanımda']);
            exit;
        }
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
