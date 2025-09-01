<?php
/**
 * Mr ECU - Ürün Yönetimi (Gelişmiş)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Resim yükleme fonksiyonu
function uploadProductImages($files, $productId) {
    $uploadedImages = [];
    
    if (!isset($files['tmp_name']) || !is_array($files['tmp_name'])) {
        return $uploadedImages;
    }
    
    $uploadDir = '../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
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
                'is_primary' => $index === 0 ? 1 : 0
            ];
        }
    }
    
    return $uploadedImages;
}

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
function generateUniqueSKU($pdo, $productName = '') {
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
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
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

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $shortDescription = sanitize($_POST['short_description']);
    $description = $_POST['description']; // HTML içerik olabilir
    $sku = sanitize($_POST['sku']);
    
    // SKU boşsa otomatik üret
    if (empty($sku)) {
        $sku = generateUniqueSKU($pdo, $name);
    } else {
        // Kullanıcı SKU girdi, duplicate kontrolu yap
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Bu SKU zaten kullanılmakta. Lütfen farklı bir SKU girin veya boş bırakın (otomatik üretilir).';
        }
    }
    
    $price = floatval($_POST['price']);
    $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $stockQuantity = intval($_POST['stock_quantity']);
    $categoryId = !empty($_POST['category_id']) ? sanitize($_POST['category_id']) : null;
    $brandId = !empty($_POST['brand_id']) ? sanitize($_POST['brand_id']) : null;
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $dimensions = sanitize($_POST['dimensions']);
    $metaTitle = sanitize($_POST['meta_title']);
    $metaDescription = sanitize($_POST['meta_description']);
    $isFeatured = isset($_POST['featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = intval($_POST['sort_order']);
    
    if (empty($name)) {
        $error = 'Ürün adı zorunludur.';
    } elseif (empty($error)) {
        try {
            $pdo->beginTransaction();
            
            // Benzersiz slug oluştur
            $slug = createUniqueSlug($pdo, $name);
            
            // Ürün ekle
            $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, short_description, sku, price, sale_price, stock_quantity, category_id, brand_id, weight, dimensions, featured, is_active, sort_order, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $slug, $description, $shortDescription, $sku, $price, $salePrice, $stockQuantity, $categoryId, $brandId, $weight, $dimensions, $isFeatured, $isActive, $sortOrder, $metaTitle, $metaDescription]);
            
            if ($result) {
                $productId = $pdo->lastInsertId();
                
                // Resimleri yükle
                if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['tmp_name'][0])) {
                    $uploadedImages = uploadProductImages($_FILES['product_images'], $productId);
                    
                    foreach ($uploadedImages as $sortOrder => $image) {
                        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$productId, $image['path'], $image['alt'], $sortOrder, $image['is_primary']]);
                    }
                }
                
                $pdo->commit();
                $success = 'Ürün başarıyla eklendi.';
            }
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = 'Ürün eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Ürün güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $productId = sanitize($_POST['product_id']);
    $name = sanitize($_POST['name']);
    $shortDescription = sanitize($_POST['short_description']);
    $description = $_POST['description']; // HTML içerik olabilir
    $sku = sanitize($_POST['sku']);
    
    // SKU boşsa otomatik üret
    if (empty($sku)) {
        $sku = generateUniqueSKU($pdo, $name);
    } else {
        // Kullanıcı SKU girdi, duplicate kontrolu yap (mevcut ürün hariç)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ? AND id != ?");
        $stmt->execute([$sku, $productId]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Bu SKU zaten başka bir ürün tarafından kullanılmakta. Lütfen farklı bir SKU girin veya boş bırakın (otomatik üretilir).';
        }
    }
    
    if (empty($error)) {
        $price = floatval($_POST['price']);
        $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
        $stockQuantity = intval($_POST['stock_quantity']);
        $categoryId = !empty($_POST['category_id']) ? sanitize($_POST['category_id']) : null;
        $brandId = !empty($_POST['brand_id']) ? sanitize($_POST['brand_id']) : null;
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $dimensions = sanitize($_POST['dimensions']);
        $metaTitle = sanitize($_POST['meta_title']);
        $metaDescription = sanitize($_POST['meta_description']);
        $isFeatured = isset($_POST['featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = intval($_POST['sort_order']);
        
        try {
        $pdo->beginTransaction();
        
        // Benzersiz slug oluştur (mevcut ürün ID'si hariç)
        $slug = createUniqueSlug($pdo, $name, $productId);
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, slug = ?, description = ?, short_description = ?, sku = ?, price = ?, sale_price = ?, stock_quantity = ?, category_id = ?, brand_id = ?, weight = ?, dimensions = ?, featured = ?, is_active = ?, sort_order = ?, meta_title = ?, meta_description = ? WHERE id = ?");
        $result = $stmt->execute([$name, $slug, $description, $shortDescription, $sku, $price, $salePrice, $stockQuantity, $categoryId, $brandId, $weight, $dimensions, $isFeatured, $isActive, $sortOrder, $metaTitle, $metaDescription, $productId]);
        
        // Yeni resimler eklendi mi kontrol et
        if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['tmp_name'][0])) {
            $uploadedImages = uploadProductImages($_FILES['product_images'], $productId);
            
            foreach ($uploadedImages as $sortOrder => $image) {
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$productId, $image['path'], $image['alt'], $sortOrder, $image['is_primary']]);
            }
        }
        
        $pdo->commit();
        
        if ($result) {
            $success = 'Ürün güncellendi.';
        }
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = 'Ürün güncellenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Ürün silme
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $productId = sanitize($_GET['delete']);
    
    try {
        $pdo->beginTransaction();
        
        // Ürün resimlerini sil
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        $images = $stmt->fetchAll();
        
        foreach ($images as $image) {
            if (file_exists('../' . $image['image_path'])) {
                unlink('../' . $image['image_path']);
            }
        }
        
        // Veritabanından sil
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $result = $stmt->execute([$productId]);
        
        $pdo->commit();
        
        if ($result) {
            $success = 'Ürün başarıyla silindi.';
        }
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = 'Ürün silinirken hata oluştu.';
    }
}

// Ürün resmini silme
if (isset($_GET['delete_image']) && !empty($_GET['delete_image'])) {
    $imageId = sanitize($_GET['delete_image']);
    
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch();
        
        if ($image && file_exists('../' . $image['image_path'])) {
            unlink('../' . $image['image_path']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $result = $stmt->execute([$imageId]);
        
        if ($result) {
            $success = 'Resim başarıyla silindi.';
        }
    } catch(PDOException $e) {
        $error = 'Resim silinirken hata oluştu.';
    }
}

// Ürünleri getir
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name, pb.name as brand_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        ORDER BY p.sort_order, p.name
    ");
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $products = [];
}

// Kategorileri getir
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Markaları getir
try {
    $stmt = $pdo->query("SELECT * FROM product_brands WHERE is_active = 1 ORDER BY name");
    $brands = $stmt->fetchAll();
} catch(PDOException $e) {
    $brands = [];
}

$pageTitle = 'Ürün Yönetimi';
$pageDescription = 'Ürünleri yönetin';
$pageIcon = 'bi bi-box';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<style>
.product-image {
    max-width: 60px;
    max-height: 60px;
    object-fit: cover;
    border-radius: 4px;
}
.product-card {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.product-card:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-color: #5a5c69;
}
.product-description {
    max-height: 100px;
    overflow-y: auto;
}

/* Fotoğraf yönetimi stilleri */
#current-product-images .card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}

#current-product-images .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

#current-product-images .card img {
    border-radius: 0.375rem 0.375rem 0 0;
}

#current-product-images .btn-group-sm .btn {
    padding: 0.25rem 0.375rem;
    font-size: 0.75rem;
}

.image-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    transition: border-color 0.15s ease-in-out;
}

.image-upload-area:hover {
    border-color: #007bff;
    background-color: #f8f9ff;
}

.image-upload-area.dragover {
    border-color: #007bff;
    background-color: #e7f3ff;
}

#current-image-count {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Ana resim badge'i */
.badge.bg-primary {
    background-color: #007bff !important;
}

/* Loading durumu */
.btn.loading {
    position: relative;
    color: transparent !important;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Ürün İstatistikleri -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-box fa-2x text-primary mb-2"></i>
                <h4><?php echo count($products); ?></h4>
                <small class="text-muted">Toplam Ürün</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-star fa-2x text-warning mb-2"></i>
                <h4><?php echo count(array_filter($products, function($p) { return $p['featured']; })); ?></h4>
                <small class="text-muted">Öne Çıkan</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-check-circle fa-2x text-success mb-2"></i>
                <h4><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h4>
                <small class="text-muted">Aktif Ürün</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-image fa-2x text-info mb-2"></i>
                <h4><?php echo array_sum(array_column($products, 'image_count')); ?></h4>
                <small class="text-muted">Toplam Resim</small>
            </div>
        </div>
    </div>
</div>

<!-- Düzenleme Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>Ürün Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    
                    <!-- Temel Bilgiler -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Ürün Adı *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_slug" class="form-label">URL Slug</label>
                                <input type="text" class="form-control" id="edit_slug" name="slug">
                                <div class="form-text">URL'de görünecek format (otomatik oluşturulur)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_sku" class="form-label">SKU (Stok Kodu)</label>
                                <input type="text" class="form-control" id="edit_sku" name="sku">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_short_description" class="form-label">Kısa Açıklama</label>
                                <textarea class="form-control" id="edit_short_description" name="short_description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_price" class="form-label">Fiyat (TL) *</label>
                                        <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_sale_price" class="form-label">İndirimli Fiyat (TL)</label>
                                        <input type="number" class="form-control" id="edit_sale_price" name="sale_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_stock_quantity" class="form-label">Stok Miktarı</label>
                                <input type="number" class="form-control" id="edit_stock_quantity" name="stock_quantity" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Kategori Seçin</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_brand_id" class="form-label">Marka</label>
                                <select class="form-select" id="edit_brand_id" name="brand_id">
                                    <option value="">Marka Seçin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Açıklama -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Detaylı Açıklama</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="8"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ek Bilgiler -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_weight" class="form-label">Ağırlık (kg)</label>
                                <input type="number" class="form-control" id="edit_weight" name="weight" step="0.01" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_dimensions" class="form-label">Boyutlar</label>
                                <input type="text" class="form-control" id="edit_dimensions" name="dimensions" placeholder="Örn: 10x20x30 cm">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_sort_order" class="form-label">Sıralama</label>
                                <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durum Ayarları</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">Aktif</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_featured" name="featured">
                                    <label class="form-check-label" for="edit_featured">Öne Çıkan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mevcut Ürün Resimleri -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">
                                <i class="bi bi-images me-2"></i>Mevcut Resimler
                                <span class="badge bg-info ms-2" id="current-image-count">0</span>
                            </h6>
                            <div id="current-product-images" class="row g-2 mb-3"></div>
                            
                            <!-- Yeni Resim Ekleme -->
                            <div class="mt-3">
                                <label class="form-label">Yeni Resim Ekle</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="edit_new_images" 
                                           accept="image/*" multiple>
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="addNewProductImages()">
                                        <i class="bi bi-upload me-1"></i>Yükle
                                    </button>
                                </div>
                                <div class="form-text">Birden fazla resim seçebilirsiniz. Maksimum 10MB per dosya.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Bölümü -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">SEO Bilgileri</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_meta_title" class="form-label">Meta Başlık</label>
                                        <input type="text" class="form-control" id="edit_meta_title" name="meta_title" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_meta_description" class="form-label">Meta Açıklama</label>
                                        <textarea class="form-control" id="edit_meta_description" name="meta_description" rows="3" maxlength="160"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ürün Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-box me-2"></i>Ürünler (<?php echo count($products); ?>)
        </h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus me-1"></i>Yeni Ürün
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="text-center py-4">
                <i class="bi bi-box fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Ürün bulunamadı</h6>
                <p class="text-muted">Henüz ürün eklenmemiş.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus me-1"></i>İlk Ürünü Ekle
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Kategori & Marka</th>
                            <th>Fiyat</th>
                            <th>Stok</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($product['primary_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($product['primary_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image me-3">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 60px; height: 60px; font-size: 10px; color: white;">
                                                Resim Yok
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <?php if ($product['featured']): ?>
                                                <i class="bi bi-star text-warning ms-1" title="Öne Çıkan"></i>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($product['slug']); ?></small>
                                            <?php if ($product['sku']): ?>
                                                <br>
                                                <small class="badge bg-light text-dark">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($product['image_count'] > 0): ?>
                                                <br>
                                                <small class="text-info"><?php echo $product['image_count']; ?> resim</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($product['category_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($product['brand_name']): ?>
                                        <br><span class="badge bg-warning"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!$product['category_name'] && !$product['brand_name']): ?>
                                        <span class="text-muted">Kategorisiz</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                            <del class="text-muted"><?php echo number_format($product['price'], 2); ?> TL</del><br>
                                            <strong class="text-danger"><?php echo number_format($product['sale_price'], 2); ?> TL</strong>
                                        <?php else: ?>
                                            <strong><?php echo number_format($product['price'], 2); ?> TL</strong>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $product['stock_quantity']; ?> adet
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $product['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                        <?php if ($product['featured']): ?>
                                            <span class="badge bg-warning">Öne Çıkan</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="viewProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Bu ürünü ve tüm resimlerini silmek istediğinizden emin misiniz?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ürün Ekleme Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus me-2"></i>Yeni Ürün Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Temel Bilgiler -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_name" class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="add_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="add_sku" class="form-label">SKU (Stok Kodu)</label>
                                <input type="text" class="form-control" name="sku" id="add_sku" placeholder="Boş bırakırsanız otomatik üretilir">
                                <div class="form-text">Boş bırakırsanız ürün adına göre otomatik SKU üretilir (Örn: PRD-12345)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="add_short_description" class="form-label">Kısa Açıklama</label>
                                <textarea class="form-control" name="short_description" id="add_short_description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="add_price" class="form-label">Fiyat (TL) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="price" id="add_price" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="add_sale_price" class="form-label">İndirimli Fiyat (TL)</label>
                                        <input type="number" class="form-control" name="sale_price" id="add_sale_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="add_stock_quantity" class="form-label">Stok Miktarı</label>
                                <input type="number" class="form-control" name="stock_quantity" id="add_stock_quantity" value="0" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="add_category_id" class="form-label">Kategori</label>
                                <select class="form-select" name="category_id" id="add_category_id">
                                    <option value="">Kategori Seçin</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="add_brand_id" class="form-label">Marka</label>
                                <select class="form-select" name="brand_id" id="add_brand_id">
                                    <option value="">Marka Seçin</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>">
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Açıklama -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="add_description" class="form-label">Detaylı Açıklama</label>
                                <textarea class="form-control" name="description" id="add_description" rows="8"></textarea>
                                <div class="form-text">HTML etiketleri kullanabilirsiniz. Resimleri drag & drop ile ekleyebilirsiniz.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ek Bilgiler -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_weight" class="form-label">Ağırlık (kg)</label>
                                <input type="number" class="form-control" name="weight" id="add_weight" step="0.01" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="add_dimensions" class="form-label">Boyutlar</label>
                                <input type="text" class="form-control" name="dimensions" id="add_dimensions" placeholder="Örn: 10x20x30 cm">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_sort_order" class="form-label">Sıralama</label>
                                <input type="number" class="form-control" name="sort_order" id="add_sort_order" value="0" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durum Ayarları</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" checked>
                                    <label class="form-check-label" for="add_is_active">Aktif</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured" id="add_featured">
                                    <label class="form-check-label" for="add_featured">Öne Çıkan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ürün Resimleri -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">
                                <i class="bi bi-images me-2"></i>Ürün Resimleri
                            </h6>
                            <div class="mb-3">
                                <label for="add_product_images" class="form-label">Resim Dosyaları</label>
                                <input type="file" class="form-control" name="product_images[]" id="add_product_images"
                                       multiple accept="image/*">
                                <div class="form-text">Birden fazla resim seçebilirsiniz. İlk resim ana resim olacaktır. Maksimum 10MB per dosya.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Bölümü -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">SEO Bilgileri</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="add_meta_title" class="form-label">Meta Başlık</label>
                                        <input type="text" class="form-control" name="meta_title" id="add_meta_title" maxlength="255">
                                        <div class="form-text">Arama motorları için sayfa başlığı</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="add_meta_description" class="form-label">Meta Açıklama</label>
                                        <textarea class="form-control" name="meta_description" id="add_meta_description" rows="3" maxlength="160"></textarea>
                                        <div class="form-text">Arama motorları için sayfa açıklaması (160 karakter)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Ürün Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CKEditor Scripts -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<!-- JavaScript Kodları -->
<script>
// Mr ECU Admin - Products Page JavaScript
// Ürün düzenleme formunu AJAX ile gönder
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Sayfanın yeniden yüklenmesini engelle

    const formData = new FormData(this);

    // update_product parametresini ekle (sunucu tarafı kontrolü için)
    formData.append('update_product', '1');

    fetch('ajax/update-product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ağ hatası: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message || 'Ürün başarıyla güncellendi.');
            // Modalı kapat
            bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
            // Sayfayı yenile
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('AJAX hatası:', error);
        alert('Bir hata oluştu: ' + error.message);
    });
});
// Global değişkenler
let currentProductId = null;

// Global fonksiyonları window objesine ata
window.viewProduct = function(productId) {
    // Detay sayfasına git (aynı sekmede)
    window.location.href = 'product-detail.php?id=' + productId;
};

// Alternatif olarak yeni sekmede açmak isterseniz:
window.viewProductNewTab = function(productId) {
    window.open('product-detail.php?id=' + productId, '_blank');
};

window.editProduct = function(productId) {
    currentProductId = productId;
    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    
    fetch('ajax/get-product-details.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                
                document.getElementById('edit_product_id').value = product.id;
                document.getElementById('edit_name').value = product.name || '';
                document.getElementById('edit_slug').value = product.slug || '';
                document.getElementById('edit_sku').value = product.sku || '';
                document.getElementById('edit_short_description').value = product.short_description || '';
                document.getElementById('edit_description').value = product.description || '';
                document.getElementById('edit_price').value = product.price || '';
                document.getElementById('edit_sale_price').value = product.sale_price || '';
                document.getElementById('edit_stock_quantity').value = product.stock_quantity || 0;
                document.getElementById('edit_weight').value = product.weight || '';
                document.getElementById('edit_dimensions').value = product.dimensions || '';
                document.getElementById('edit_sort_order').value = product.sort_order || 0;
                document.getElementById('edit_meta_title').value = product.meta_title || '';
                document.getElementById('edit_meta_description').value = product.meta_description || '';
                
                document.getElementById('edit_is_active').checked = product.is_active == 1;
                document.getElementById('edit_featured').checked = product.featured == 1;
                
                const categorySelect = document.getElementById('edit_category_id');
                categorySelect.innerHTML = '<option value="">Kategori Seçin</option>';
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    option.selected = category.id == product.category_id;
                    categorySelect.appendChild(option);
                });
                
                const brandSelect = document.getElementById('edit_brand_id');
                brandSelect.innerHTML = '<option value="">Marka Seçin</option>';
                data.brands.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.id;
                    option.textContent = brand.name;
                    option.selected = brand.id == product.brand_id;
                    brandSelect.appendChild(option);
                });
                
                // Fotoğrafları yükle
                loadProductImages(data.images);
                
                modal.show();
            } else {
                alert('Ürün bilgileri alınamadı: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ürün bilgileri alınamadı:', error);
            alert('Bir hata oluştu.');
        });
};

// Fotoğrafları yükle ve göster
function loadProductImages(images) {
    const container = document.getElementById('current-product-images');
    const countBadge = document.getElementById('current-image-count');
    
    if (!container) return;
    
    container.innerHTML = '';
    countBadge.textContent = images.length;
    
    if (images.length === 0) {
        container.innerHTML = '<div class="col-12 text-center py-4"><p class="text-muted mb-0"><i class="bi bi-image fa-2x mb-2"></i><br>Henüz resim eklenmemiş</p></div>';
        return;
    }
    
    images.forEach(image => {
        const imageCol = document.createElement('div');
        imageCol.classList.add('col-md-3', 'col-sm-4', 'col-6');
        
        const primaryBadge = image.is_primary == 1 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1"><i class="bi bi-star"></i> Ana</span>' : '';
        const primaryButton = image.is_primary != 1 ? 
            '<button type="button" class="btn btn-outline-warning" onclick="setPrimaryImage(' + image.id + ')" title="Ana Resim Yap"><i class="bi bi-star"></i></button>' : 
            '<button type="button" class="btn btn-warning" disabled><i class="bi bi-star"></i></button>';
        
        imageCol.innerHTML = 
            '<div class="card position-relative">' +
                '<img src="../' + image.image_path + '" class="card-img-top" style="height: 120px; object-fit: cover;">' +
                primaryBadge +
                '<div class="card-body p-2">' +
                    '<div class="btn-group btn-group-sm w-100">' +
                        primaryButton +
                        '<button type="button" class="btn btn-outline-danger" onclick="deleteProductImage(' + image.id + ')" title="Sil">' +
                            '<i class="bi bi-trash"></i>' +
                        '</button>' +
                    '</div>' +
                    '<small class="text-muted d-block mt-1">' + (image.alt_text || 'Resim') + '</small>' +
                '</div>' +
            '</div>';
        
        container.appendChild(imageCol);
    });
}

// Yeni fotoğraf ekleme
window.addNewProductImages = function() {
    if (!currentProductId) {
        alert('Geçersiz ürün ID');
        return;
    }
    
    const fileInput = document.getElementById('edit_new_images');
    const files = fileInput.files;
    
    if (files.length === 0) {
        alert('Lütfen en az bir resim seçin');
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', currentProductId);
    
    for (let i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
    }
    
    fetch('ajax/add-product-images.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            fileInput.value = '';
            refreshProductImages();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Resim yükleme hatası:', error);
        alert('Bir hata oluştu.');
    });
};

// Fotoğrafı sil
window.deleteProductImage = function(imageId) {
    if (!confirm('Bu resmi silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('ajax/delete-product-image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'image_id=' + imageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshProductImages();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Resim silme hatası:', error);
        alert('Bir hata oluştu.');
    });
};

// Ana resim belirle
window.setPrimaryImage = function(imageId) {
    fetch('ajax/set-primary-image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'image_id=' + imageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshProductImages();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Ana resim belirleme hatası:', error);
        alert('Bir hata oluştu.');
    });
};

// Fotoğrafları yeniden yükle
function refreshProductImages() {
    if (!currentProductId) return;
    
    fetch('ajax/get-product-details.php?id=' + currentProductId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadProductImages(data.images);
            }
        })
        .catch(error => {
            console.error('Fotoğraflar yeniden yüklenemedi:', error);
        });
}

console.log('Products.js yüklendi - tüm fonksiyonlar hazır!');

// CKEditor için basit konfigürasyon
if (typeof ClassicEditor !== 'undefined') {
    ClassicEditor
        .create(document.querySelector('#add_description'), {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed', '|', 'undo', 'redo'],
            image: {
                toolbar: ['imageTextAlternative', 'imageStyle:full', 'imageStyle:side']
            }
        })
        .catch(error => {
            console.error('CKEditor yüklenemedi:', error);
        });
}
</script>

<?php include '../includes/admin_footer.php'; ?>
