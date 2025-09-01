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

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $shortDescription = sanitize($_POST['short_description']);
    $description = $_POST['description']; // HTML içerik olabilir
    $sku = sanitize($_POST['sku']);
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
    } else {
        try {
            $pdo->beginTransaction();
            
            // Slug oluştur
            $slug = createSlug($name);
            
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
        
        $slug = createSlug($name);
        
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
                    <i class="bi bi-edit me-2"></i>Ürün Düzenle
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
                                            <i class="bi bi-edit"></i>
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
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="mb-3">Ürün Bilgileri</h6>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="short_description" class="form-label">Kısa Açıklama</label>
                                <textarea class="form-control" name="short_description" rows="2" 
                                          placeholder="Ürün listelerinde gösterilecek kısa açıklama"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Detaylı Açıklama</label>
                                <textarea class="form-control" name="description" id="add_description" rows="8"
                                          placeholder="Ürün sayfasında gösterilecek detaylı açıklama. HTML kullanabilirsiniz."></textarea>
                                <div class="form-text">HTML etiketleri kullanabilirsiniz. Resimleri drag & drop ile ekleyebilirsiniz.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product_images" class="form-label">Ürün Resimleri</label>
                                <input type="file" class="form-control" name="product_images[]" 
                                       multiple accept="image/*">
                                <div class="form-text">Birden fazla resim seçebilirsiniz. İlk resim ana resim olacaktır. Maksimum 10MB per dosya.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h6 class="mb-3">Ürün Özellikleri</h6>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU</label>
                                        <input type="text" class="form-control" name="sku" 
                                               placeholder="Ürün kodu">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Fiyat (TL) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sale_price" class="form-label">İndirimli Fiyat</label>
                                        <input type="number" class="form-control" name="sale_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Stok Miktarı</label>
                                <input type="number" class="form-control" name="stock_quantity" value="0" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Kategori Seçin</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marka</label>
                                <select class="form-select" name="brand_id">
                                    <option value="">Marka Seçin</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>">
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">Ağırlık (kg)</label>
                                        <input type="number" class="form-control" name="weight" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label">Sıralama</label>
                                        <input type="number" class="form-control" name="sort_order" value="0" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dimensions" class="form-label">Boyutlar</label>
                                <input type="text" class="form-control" name="dimensions" 
                                       placeholder="Örn: 10x20x30 cm">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durum Ayarları</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" checked>
                                    <label class="form-check-label">Aktif</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured">
                                    <label class="form-check-label">Öne Çıkan</label>
                                </div>
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
                                        <label for="meta_title" class="form-label">Meta Başlık</label>
                                        <input type="text" class="form-control" name="meta_title" maxlength="255">
                                        <div class="form-text">Arama motorları için sayfa başlığı</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="meta_description" class="form-label">Meta Açıklama</label>
                                        <textarea class="form-control" name="meta_description" rows="3" maxlength="160"></textarea>
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

<?php
$pageJS = "
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

function viewProduct(productId) {
    // Ürün detay sayfasına yönlendir veya modal aç
    window.open('product-detail.php?id=' + productId, '_blank');
}

function editProduct(productId) {
    // Loading göster
    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    
    // AJAX ile ürün bilgilerini getir
    fetch('ajax/get-product-details.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                
                // Form alanlarını doldur
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
                
                // Checkbox'lar
                document.getElementById('edit_is_active').checked = product.is_active == 1;
                document.getElementById('edit_featured').checked = product.featured == 1;
                
                // Kategori seçeneklerini doldur
                const categorySelect = document.getElementById('edit_category_id');
                categorySelect.innerHTML = '<option value="">Kategori Seçin</option>';
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    option.selected = category.id == product.category_id;
                    categorySelect.appendChild(option);
                });
                
                // Marka seçeneklerini doldur
                const brandSelect = document.getElementById('edit_brand_id');
                brandSelect.innerHTML = '<option value="">Marka Seçin</option>';
                data.brands.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.id;
                    option.textContent = brand.name;
                    option.selected = brand.id == product.brand_id;
                    brandSelect.appendChild(option);
                });
                
                // Modal'ı aç
                modal.show();
            } else {
                alert('Ürün bilgileri alınamadı: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ürün bilgileri alınamadı:', error);
            alert('Bir hata oluştu.');
        });
}

// Edit form submit
document.addEventListener('DOMContentLoaded', function() {
    // URL'de edit parametresi var mı kontrol et
    const urlParams = new URLSearchParams(window.location.search);
    const editProductId = urlParams.get('edit');
    
    if (editProductId) {
        // Edit modal'ını aç
        setTimeout(() => {
            editProduct(editProductId);
        }, 500);
        
        // URL'den edit parametresini temizle
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    const editForm = document.getElementById('editProductForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(editForm);
            const submitBtn = editForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Loading göster
            submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin me-1"></i>Güncelleniyor...';
            submitBtn.disabled = true;
            
            fetch('ajax/update-product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success message
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success alert-dismissible fade show';
                    successAlert.innerHTML = `
                        <i class="bi bi-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Insert after page title
                    const pageTitle = document.querySelector('.card-header');
                    pageTitle.parentNode.insertBefore(successAlert, pageTitle.nextSibling);
                    
                    // Modal'ı kapat
                    bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                    
                    // Sayfayı yenile (ürün listesini güncellemek için)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Güncelleme hatası:', error);
                alert('Bir hata oluştu.');
            })
            .finally(() => {
                // Loading'i kaldır
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});

// Modal temizleme
document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
    // CKEditor içeriğini temizle
    if (window.addDescriptionEditor) {
        window.addDescriptionEditor.setData('');
    }
});

// Dosya önizleme
function previewImages(input) {
    const previewContainer = document.getElementById('image_preview');
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-image';
                img.style.cssText = 'max-width: 100px; max-height: 100px; object-fit: cover; margin: 5px; border-radius: 4px;';
                
                const wrapper = document.createElement('div');
                wrapper.className = 'd-inline-block position-relative';
                wrapper.appendChild(img);
                
                if (index === 0) {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary position-absolute top-0 start-0';
                    badge.textContent = 'Ana';
                    badge.style.fontSize = '10px';
                    wrapper.appendChild(badge);
                }
                
                previewContainer.appendChild(wrapper);
            }
            reader.readAsDataURL(file);
        });
    }
}
";

$pageExtraHead = "
<script src='https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js'></script>
<script src='js/products.js'></script>
<style>
.ck-editor__editable_inline {
    min-height: 200px;
}
.preview-image {
    border: 2px solid #ddd;
}
</style>
";

include '../includes/admin_footer.php';
?>
