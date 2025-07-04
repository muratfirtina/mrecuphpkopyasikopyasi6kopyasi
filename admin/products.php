<?php
/**
 * Mr ECU - Ürün Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// İşlem kontrolü
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

// Upload klasörünü oluştur
$uploadDir = '../uploads/products/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Ürün ekleme
if ($_POST && $action === 'add') {
    try {
        $name = trim($_POST['name']);
        $shortDescription = trim($_POST['short_description']);
        $description = trim($_POST['description']);
        $sku = trim($_POST['sku']);
        $price = (float)$_POST['price'];
        $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $stockQuantity = (int)$_POST['stock_quantity'];
        $manageStock = isset($_POST['manage_stock']) ? 1 : 0;
        $stockStatus = $_POST['stock_status'];
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
        $dimensions = trim($_POST['dimensions']);
        $categoryId = (int)$_POST['category_id'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)$_POST['sort_order'];
        $metaTitle = trim($_POST['meta_title']);
        $metaDescription = trim($_POST['meta_description']);
        
        // Slug oluştur
        $slug = createSlug($name);
        
        // Ürünü ekle
        $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, short_description, sku, price, sale_price, stock_quantity, manage_stock, stock_status, weight, dimensions, category_id, featured, is_active, sort_order, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $shortDescription, $sku, $price, $salePrice, $stockQuantity, $manageStock, $stockStatus, $weight, $dimensions, $categoryId, $featured, $isActive, $sortOrder, $metaTitle, $metaDescription]);
        
        $productId = $pdo->lastInsertId();
        
        // Fotoğraf yükleme (5 adet)
        for ($i = 1; $i <= 5; $i++) {
            if (isset($_FILES["image_$i"]) && $_FILES["image_$i"]['error'] === 0) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (in_array($_FILES["image_$i"]['type'], $allowedTypes) && $_FILES["image_$i"]['size'] <= $maxSize) {
                    $extension = pathinfo($_FILES["image_$i"]['name'], PATHINFO_EXTENSION);
                    $filename = 'product-' . $productId . '-' . $i . '-' . uniqid() . '.' . $extension;
                    $fullPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES["image_$i"]['tmp_name'], $fullPath)) {
                        $imagePath = 'uploads/products/' . $filename;
                        $altText = $_POST["alt_text_$i"] ?? '';
                        $isPrimary = ($i === 1) ? 1 : 0; // İlk fotoğraf ana fotoğraf
                        
                        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$productId, $imagePath, $altText, $i, $isPrimary]);
                    }
                }
            }
        }
        
        // Ürün özelliklerini ekle
        if (!empty($_POST['attributes'])) {
            foreach ($_POST['attributes'] as $index => $attribute) {
                if (!empty($attribute['name']) && !empty($attribute['value'])) {
                    $stmt = $pdo->prepare("INSERT INTO product_attributes (product_id, attribute_name, attribute_value, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$productId, $attribute['name'], $attribute['value'], $index]);
                }
            }
        }
        
        $message = 'Ürün başarıyla eklendi.';
        $messageType = 'success';
        $action = 'list';
        
    } catch(PDOException $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Ürün güncelleme
if ($_POST && $action === 'edit' && $id) {
    try {
        $name = trim($_POST['name']);
        $shortDescription = trim($_POST['short_description']);
        $description = trim($_POST['description']);
        $sku = trim($_POST['sku']);
        $price = (float)$_POST['price'];
        $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $stockQuantity = (int)$_POST['stock_quantity'];
        $manageStock = isset($_POST['manage_stock']) ? 1 : 0;
        $stockStatus = $_POST['stock_status'];
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
        $dimensions = trim($_POST['dimensions']);
        $categoryId = (int)$_POST['category_id'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)$_POST['sort_order'];
        $metaTitle = trim($_POST['meta_title']);
        $metaDescription = trim($_POST['meta_description']);
        
        // Slug oluştur
        $slug = createSlug($name);
        
        // Ürünü güncelle
        $stmt = $pdo->prepare("UPDATE products SET name = ?, slug = ?, description = ?, short_description = ?, sku = ?, price = ?, sale_price = ?, stock_quantity = ?, manage_stock = ?, stock_status = ?, weight = ?, dimensions = ?, category_id = ?, featured = ?, is_active = ?, sort_order = ?, meta_title = ?, meta_description = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $shortDescription, $sku, $price, $salePrice, $stockQuantity, $manageStock, $stockStatus, $weight, $dimensions, $categoryId, $featured, $isActive, $sortOrder, $metaTitle, $metaDescription, $id]);
        
        // Fotoğraf yükleme (5 adet)
        for ($i = 1; $i <= 5; $i++) {
            if (isset($_FILES["image_$i"]) && $_FILES["image_$i"]['error'] === 0) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (in_array($_FILES["image_$i"]['type'], $allowedTypes) && $_FILES["image_$i"]['size'] <= $maxSize) {
                    $extension = pathinfo($_FILES["image_$i"]['name'], PATHINFO_EXTENSION);
                    $filename = 'product-' . $id . '-' . $i . '-' . uniqid() . '.' . $extension;
                    $fullPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES["image_$i"]['tmp_name'], $fullPath)) {
                        $imagePath = 'uploads/products/' . $filename;
                        $altText = $_POST["alt_text_$i"] ?? '';
                        $isPrimary = ($i === 1) ? 1 : 0;
                        
                        // Eski fotoğrafı sil
                        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND sort_order = ?");
                        $stmt->execute([$id, $i]);
                        $oldImage = $stmt->fetch();
                        if ($oldImage && file_exists('../' . $oldImage['image_path'])) {
                            unlink('../' . $oldImage['image_path']);
                        }
                        
                        // Mevcut fotoğrafı güncelle veya yeni ekle
                        $stmt = $pdo->prepare("REPLACE INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$id, $imagePath, $altText, $i, $isPrimary]);
                    }
                }
            }
        }
        
        // Ürün özelliklerini güncelle
        $pdo->prepare("DELETE FROM product_attributes WHERE product_id = ?")->execute([$id]);
        if (!empty($_POST['attributes'])) {
            foreach ($_POST['attributes'] as $index => $attribute) {
                if (!empty($attribute['name']) && !empty($attribute['value'])) {
                    $stmt = $pdo->prepare("INSERT INTO product_attributes (product_id, attribute_name, attribute_value, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $attribute['name'], $attribute['value'], $index]);
                }
            }
        }
        
        $message = 'Ürün başarıyla güncellendi.';
        $messageType = 'success';
        $action = 'list';
        
    } catch(PDOException $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Ürün silme
if ($action === 'delete' && $id) {
    try {
        // Ürün fotoğraflarını sil
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        
        foreach ($images as $image) {
            if (file_exists('../' . $image['image_path'])) {
                unlink('../' . $image['image_path']);
            }
        }
        
        // Ürünü sil (CASCADE ile ilişkili tablolar da silinir)
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $message = 'Ürün başarıyla silindi.';
        $messageType = 'success';
        $action = 'list';
        
    } catch(PDOException $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Ürünleri getir
$page = $_GET['page'] ?? 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$whereClause = '';
$params = [];

// Filtreleme
if (!empty($_GET['category'])) {
    $whereClause .= " AND p.category_id = ?";
    $params[] = $_GET['category'];
}

if (!empty($_GET['search'])) {
    $whereClause .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}

if (!empty($_GET['featured'])) {
    $whereClause .= " AND p.featured = 1";
}

if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'active') {
        $whereClause .= " AND p.is_active = 1";
    } elseif ($_GET['status'] === 'inactive') {
        $whereClause .= " AND p.is_active = 0";
    }
}

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE 1=1 $whereClause
    ORDER BY p.sort_order, p.name
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Toplam ürün sayısı
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE 1=1 $whereClause");
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Kategorileri getir
$stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
$categories = $stmt->fetchAll();

// Düzenleme için ürün bilgilerini getir
$editProduct = null;
$productImages = [];
$productAttributes = [];
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch();
    
    // Ürün fotoğrafları
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
    $stmt->execute([$id]);
    $productImages = $stmt->fetchAll();
    
    // Ürün özellikleri
    $stmt = $pdo->prepare("SELECT * FROM product_attributes WHERE product_id = ? ORDER BY sort_order");
    $stmt->execute([$id]);
    $productAttributes = $stmt->fetchAll();
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

$pageTitle = 'Ürün Yönetimi';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-box me-2"></i>Ürün Yönetimi
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php if ($action === 'list'): ?>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Yeni Ürün
                                </a>
                            <?php else: ?>
                                <a href="products.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <!-- Filtreleme -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="get" class="row g-3">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="search" placeholder="Ürün adı veya SKU ara..." 
                                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="category">
                                        <option value="">Tüm Kategoriler</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo ($_GET['category'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="status">
                                        <option value="">Tüm Durumlar</option>
                                        <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="featured">
                                        <option value="">Öne Çıkan Durum</option>
                                        <option value="1" <?php echo ($_GET['featured'] ?? '') === '1' ? 'selected' : ''; ?>>Öne Çıkan</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search me-1"></i>Filtrele
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="products.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Temizle
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Ürün Listesi -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Ürünler (<?php echo $totalProducts; ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($products)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-box text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3">Henüz ürün eklenmemiş.</p>
                                    <a href="?action=add" class="btn btn-primary">İlk Ürünü Ekle</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fotoğraf</th>
                                                <th>Ürün</th>
                                                <th>Kategori</th>
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
                                                        <?php if ($product['primary_image']): ?>
                                                            <img src="../<?php echo $product['primary_image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <small class="d-block text-center text-muted"><?php echo $product['image_count']; ?> foto</small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                                        <?php if ($product['short_description']): ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo mb_substr(htmlspecialchars($product['short_description']), 0, 50); ?>...</small>
                                                        <?php endif; ?>
                                                        <?php if ($product['featured']): ?>
                                                            <br>
                                                            <span class="badge bg-warning">Öne Çıkan</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($product['sale_price']): ?>
                                                            <span class="text-decoration-line-through text-muted"><?php echo number_format($product['price'], 2); ?> ₺</span>
                                                            <br>
                                                            <strong class="text-danger"><?php echo number_format($product['sale_price'], 2); ?> ₺</strong>
                                                        <?php else: ?>
                                                            <strong><?php echo number_format($product['price'], 2); ?> ₺</strong>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($product['manage_stock']): ?>
                                                            <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                                <?php echo $product['stock_quantity']; ?> adet
                                                            </span>
                                                            <br>
                                                            <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $product['stock_status'])); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Takip edilmiyor</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($product['is_active']): ?>
                                                            <span class="badge bg-success">Aktif</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Pasif</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="?action=delete&id=<?php echo $product['id']; ?>" 
                                                               class="btn btn-outline-danger"
                                                               onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Sayfalama -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Ürün sayfalama">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <!-- Ürün Ekleme/Düzenleme Formu -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo $action === 'add' ? 'Yeni Ürün Ekle' : 'Ürün Düzenle'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <!-- Sol Kolon - Temel Bilgiler -->
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Ürün Adı *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="short_description" class="form-label">Kısa Açıklama</label>
                                            <textarea class="form-control" id="short_description" name="short_description" rows="2"><?php echo htmlspecialchars($editProduct['short_description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Detaylı Açıklama</label>
                                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="sku" class="form-label">SKU (Ürün Kodu) *</label>
                                                    <input type="text" class="form-control" id="sku" name="sku" 
                                                           value="<?php echo htmlspecialchars($editProduct['sku'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="category_id" class="form-label">Kategori *</label>
                                                    <select class="form-select" id="category_id" name="category_id" required>
                                                        <option value="">Kategori Seçin</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>" 
                                                                    <?php echo ($editProduct['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="price" class="form-label">Fiyat (₺) *</label>
                                                    <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                                           value="<?php echo $editProduct['price'] ?? 0; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="sale_price" class="form-label">İndirimli Fiyat (₺)</label>
                                                    <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" 
                                                           value="<?php echo $editProduct['sale_price'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Stok Yönetimi -->
                                        <div class="border rounded p-3 mb-3">
                                            <h6>Stok Yönetimi</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="manage_stock" name="manage_stock" 
                                                                   <?php echo ($editProduct['manage_stock'] ?? 1) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="manage_stock">
                                                                Stok takibi yap
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="stock_quantity" class="form-label">Stok Miktarı</label>
                                                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                                               value="<?php echo $editProduct['stock_quantity'] ?? 0; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="stock_status" class="form-label">Stok Durumu</label>
                                                        <select class="form-select" id="stock_status" name="stock_status">
                                                            <option value="in_stock" <?php echo ($editProduct['stock_status'] ?? 'in_stock') === 'in_stock' ? 'selected' : ''; ?>>Stokta</option>
                                                            <option value="out_of_stock" <?php echo ($editProduct['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>Stokta Yok</option>
                                                            <option value="on_backorder" <?php echo ($editProduct['stock_status'] ?? '') === 'on_backorder' ? 'selected' : ''; ?>>Sipariş Alınıyor</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fiziksel Özellikler -->
                                        <div class="border rounded p-3 mb-3">
                                            <h6>Fiziksel Özellikler</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="weight" class="form-label">Ağırlık (kg)</label>
                                                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                                               value="<?php echo $editProduct['weight'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="dimensions" class="form-label">Boyutlar (cm)</label>
                                                        <input type="text" class="form-control" id="dimensions" name="dimensions" 
                                                               placeholder="En x Boy x Yükseklik"
                                                               value="<?php echo htmlspecialchars($editProduct['dimensions'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ürün Özellikleri -->
                                        <div class="border rounded p-3 mb-3">
                                            <h6>Ürün Özellikleri</h6>
                                            <div id="attributes-container">
                                                <?php if (!empty($productAttributes)): ?>
                                                    <?php foreach ($productAttributes as $index => $attr): ?>
                                                        <div class="row mb-2 attribute-row">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="attributes[<?php echo $index; ?>][name]" 
                                                                       placeholder="Özellik adı" value="<?php echo htmlspecialchars($attr['attribute_name']); ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <input type="text" class="form-control" name="attributes[<?php echo $index; ?>][value]" 
                                                                       placeholder="Özellik değeri" value="<?php echo htmlspecialchars($attr['attribute_value']); ?>">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" class="btn btn-outline-danger btn-sm remove-attribute">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="row mb-2 attribute-row">
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="attributes[0][name]" placeholder="Özellik adı">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control" name="attributes[0][value]" placeholder="Özellik değeri">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button type="button" class="btn btn-outline-danger btn-sm remove-attribute">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-attribute">
                                                <i class="fas fa-plus me-1"></i>Özellik Ekle
                                            </button>
                                        </div>

                                        <!-- Diğer Ayarlar -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="sort_order" class="form-label">Sıra Numarası</label>
                                                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                                           value="<?php echo $editProduct['sort_order'] ?? 0; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="featured" name="featured" 
                                                               <?php echo ($editProduct['featured'] ?? 0) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="featured">
                                                            Öne çıkan ürün
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                               <?php echo ($editProduct['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="is_active">
                                                            Aktif
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sağ Kolon - Fotoğraflar -->
                                    <div class="col-md-4">
                                        <h6>Ürün Fotoğrafları (Maksimum 5 adet)</h6>
                                        <small class="text-muted d-block mb-3">Maksimum 5MB, JPG/PNG/GIF/WEBP formatları desteklenir.</small>
                                        
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="mb-3">
                                                <label for="image_<?php echo $i; ?>" class="form-label">
                                                    Fotoğraf <?php echo $i; ?>
                                                    <?php if ($i === 1): ?>
                                                        <span class="badge bg-primary">Ana Fotoğraf</span>
                                                    <?php endif; ?>
                                                </label>
                                                <input type="file" class="form-control mb-2" id="image_<?php echo $i; ?>" 
                                                       name="image_<?php echo $i; ?>" accept="image/*">
                                                <input type="text" class="form-control mb-2" name="alt_text_<?php echo $i; ?>" 
                                                       placeholder="Alt metin" 
                                                       value="<?php echo htmlspecialchars($productImages[$i-1]['alt_text'] ?? ''); ?>">
                                                
                                                <?php if (isset($productImages[$i-1]) && $productImages[$i-1]['image_path']): ?>
                                                    <div class="mt-2">
                                                        <img src="../<?php echo $productImages[$i-1]['image_path']; ?>" 
                                                             alt="Fotoğraf <?php echo $i; ?>" class="img-thumbnail" style="max-width: 150px;">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <!-- SEO Bilgileri -->
                                <hr>
                                <h6>SEO Bilgileri</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="meta_title" class="form-label">Meta Başlık</label>
                                            <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                                   value="<?php echo htmlspecialchars($editProduct['meta_title'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="meta_description" class="form-label">Meta Açıklama</label>
                                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($editProduct['meta_description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="products.php" class="btn btn-secondary">İptal</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        <?php echo $action === 'add' ? 'Ürün Ekle' : 'Güncelle'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Özellik ekleme/çıkarma
        let attributeIndex = <?php echo count($productAttributes); ?>;
        
        document.getElementById('add-attribute').addEventListener('click', function() {
            const container = document.getElementById('attributes-container');
            const div = document.createElement('div');
            div.className = 'row mb-2 attribute-row';
            div.innerHTML = `
                <div class="col-md-4">
                    <input type="text" class="form-control" name="attributes[${attributeIndex}][name]" placeholder="Özellik adı">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="attributes[${attributeIndex}][value]" placeholder="Özellik değeri">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-attribute">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
            attributeIndex++;
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-attribute')) {
                e.target.closest('.attribute-row').remove();
            }
        });
    </script>
</body>
</html>
