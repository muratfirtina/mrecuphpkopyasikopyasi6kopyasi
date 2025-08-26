<?php
/**
 * Mr ECU - Admin Ürün Detay
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$product = null;
$productImages = [];

// Ürün ID'sini al
$productId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($productId)) {
    redirect('products.php');
}

// Ürün bilgilerini getir
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.name as category_name, c.slug as category_slug,
               pb.name as brand_name, pb.slug as brand_slug, pb.logo as brand_logo
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        redirect('products.php');
    }
} catch(PDOException $e) {
    $error = 'Ürün bilgileri alınamadı.';
}

// Ürün resimlerini getir
if ($product) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
        $stmt->execute([$productId]);
        $productImages = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Images yok, boş bırak
    }
}

$pageTitle = $product ? $product['name'] . ' - Ürün Detay' : 'Ürün Detay';
$pageDescription = 'Ürün detay bilgileri';
$pageIcon = 'fas fa-eye';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<style>
.product-detail-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.product-main-image {
    width: 100%;
    max-width: 400px;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

.product-thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.product-thumbnail:hover,
.product-thumbnail.active {
    border-color: #007bff;
}

.product-info-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.product-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.badge-featured { background: #ffc107; color: #000; }
.badge-active { background: #28a745; color: white; }
.badge-inactive { background: #dc3545; color: white; }
.badge-sale { background: #17a2b8; color: white; }
</style>

<?php if ($error): ?>
    <div class="alert alert-admin alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($product): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">
                <i class="fas fa-eye me-2 text-primary"></i>
                Ürün Detay
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Ürünler</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Geri Dön
            </a>
            <button type="button" class="btn btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)">
                <i class="fas fa-edit me-1"></i>Düzenle
            </button>
        </div>
    </div>

    <div class="product-detail-container">
        <div class="row">
            <!-- Ürün Resimleri -->
            <div class="col-lg-5 p-4">
                <?php if (!empty($productImages)): ?>
                    <div class="mb-3">
                        <img src="../<?php echo htmlspecialchars($productImages[0]['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-main-image" id="mainProductImage">
                    </div>
                    
                    <?php if (count($productImages) > 1): ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($productImages as $index => $image): ?>
                                <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?> - <?php echo $index + 1; ?>"
                                     class="product-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('../<?php echo htmlspecialchars($image['image_path']); ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="product-main-image bg-light d-flex align-items-center justify-content-center">
                        <div class="text-center text-muted">
                            <i class="fas fa-image fa-3x mb-3"></i>
                            <p>Ürün görseli bulunmuyor</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ürün Bilgileri -->
            <div class="col-lg-7 p-4">
                <div class="product-info-card">
                    <h2 class="h4 mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>
                    
                    <!-- Durum Badge'leri -->
                    <div class="mb-3">
                        <span class="product-badge badge-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $product['is_active'] ? 'Aktif' : 'Pasif'; ?>
                        </span>
                        <?php if ($product['featured']): ?>
                            <span class="product-badge badge-featured">Öne Çıkan</span>
                        <?php endif; ?>
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="product-badge badge-sale">İndirimli</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Fiyat Bilgisi -->
                    <div class="mb-3">
                        <h5 class="text-success mb-1">
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <del class="text-muted me-2"><?php echo number_format($product['price'], 2); ?> TL</del>
                                <?php echo number_format($product['sale_price'], 2); ?> TL
                                <?php 
                                $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                                echo "<small class='text-danger'>({$discount}% indirim)</small>";
                                ?>
                            <?php else: ?>
                                <?php echo number_format($product['price'], 2); ?> TL
                            <?php endif; ?>
                        </h5>
                    </div>
                    
                    <!-- Marka ve Kategori -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Marka:</strong>
                            <?php if ($product['brand_name']): ?>
                                <span class="badge bg-warning"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Belirlenmemiş</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Kategori:</strong>
                            <?php if ($product['category_name']): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Belirlenmemiş</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Teknik Bilgiler -->
                    <div class="row mb-3">
                        <?php if ($product['sku']): ?>
                            <div class="col-md-6">
                                <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <strong>Stok:</strong> 
                            <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $product['stock_quantity']; ?> adet
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($product['weight'] || $product['dimensions']): ?>
                        <div class="row mb-3">
                            <?php if ($product['weight']): ?>
                                <div class="col-md-6">
                                    <strong>Ağırlık:</strong> <?php echo $product['weight']; ?> kg
                                </div>
                            <?php endif; ?>
                            <?php if ($product['dimensions']): ?>
                                <div class="col-md-6">
                                    <strong>Boyutlar:</strong> <?php echo htmlspecialchars($product['dimensions']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Kısa Açıklama -->
                    <?php if ($product['short_description']): ?>
                        <div class="mb-3">
                            <strong>Kısa Açıklama:</strong>
                            <p class="text-muted"><?php echo htmlspecialchars($product['short_description']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- SEO Bilgileri -->
                <?php if ($product['meta_title'] || $product['meta_description']): ?>
                    <div class="product-info-card">
                        <h5 class="mb-3"><i class="fas fa-search me-2"></i>SEO Bilgileri</h5>
                        
                        <?php if ($product['meta_title']): ?>
                            <div class="mb-2">
                                <strong>Meta Başlık:</strong>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($product['meta_title']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['meta_description']): ?>
                            <div class="mb-2">
                                <strong>Meta Açıklama:</strong>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($product['meta_description']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <strong>SEO URL:</strong>
                            <code>/urun/<?php echo htmlspecialchars($product['slug']); ?></code>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Detaylı Açıklama -->
        <?php if ($product['description']): ?>
            <div class="row">
                <div class="col-12">
                    <div class="product-info-card">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Detaylı Açıklama</h5>
                        <div class="product-description">
                            <?php echo $product['description']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alert alert-admin alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Ürün bulunamadı.
    </div>
<?php endif; ?>

<?php
$pageJS = "
function changeMainImage(imageSrc, thumbnail) {
    document.getElementById('mainProductImage').src = imageSrc;
    
    // Thumbnail aktifliğini değiştir
    document.querySelectorAll('.product-thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    thumbnail.classList.add('active');
}

function editProduct(productId) {
    // Ürün düzenleme sayfasına yönlendir
    window.location.href = 'products.php?edit=' + productId;
}
";

// Footer include
include '../includes/admin_footer.php';
?>
