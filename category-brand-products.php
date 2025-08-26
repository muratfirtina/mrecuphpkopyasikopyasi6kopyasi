<?php
/**
 * Mr ECU - Kategori-Marka Ürünleri Sayfası
 * Belirli bir kategorideki belirli bir markaya ait ürünleri listeler
 */

require_once 'config/config.php';
require_once 'config/database.php';

// URL parametrelerini al
$categorySlug = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$brandSlug = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';

if (empty($categorySlug) || empty($brandSlug)) {
    redirect('/404.php');
}

// Kategori ve marka bilgilerini getir
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
    $stmt->execute([$categorySlug]);
    $category = $stmt->fetch();
    
    if (!$category) {
        redirect('/404.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM product_brands WHERE slug = ? AND is_active = 1");
    $stmt->execute([$brandSlug]);
    $brand = $stmt->fetch();
    
    if (!$brand) {
        redirect('/404.php');
    }
} catch(PDOException $e) {
    redirect('/404.php');
}

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Sıralama parametreleri
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'name';
$sortDir = isset($_GET['dir']) && $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';

$validSortOptions = [
    'name' => 'p.name',
    'price' => 'COALESCE(p.sale_price, p.price)',
    'date' => 'p.created_at',
    'featured' => 'p.featured'
];

$orderBy = $validSortOptions[$sortBy] ?? $validSortOptions['name'];

// Öne çıkan ürünler önce gelsin
if ($sortBy !== 'featured') {
    $orderBy = 'p.featured DESC, p.sort_order, ' . $orderBy;
}

// Toplam ürün sayısını hesapla
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM products p 
        WHERE p.category_id = ? AND p.brand_id = ? AND p.is_active = 1
    ");
    $stmt->execute([$category['id'], $brand['id']]);
    $totalProducts = $stmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $perPage);
} catch(PDOException $e) {
    $totalProducts = 0;
    $totalPages = 1;
}

// Ürünleri getir
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count
        FROM products p 
        WHERE p.category_id = ? AND p.brand_id = ? AND p.is_active = 1
        ORDER BY {$orderBy} {$sortDir}
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute([$category['id'], $brand['id']]);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $products = [];
}

// İlgili markalar (aynı kategorideki diğer markalar)
try {
    $stmt = $pdo->prepare("
        SELECT pb.*, COUNT(p.id) as product_count 
        FROM product_brands pb 
        INNER JOIN products p ON pb.id = p.brand_id 
        WHERE p.category_id = ? AND pb.id != ? AND p.is_active = 1 AND pb.is_active = 1
        GROUP BY pb.id 
        ORDER BY pb.sort_order, pb.name
        LIMIT 6
    ");
    $stmt->execute([$category['id'], $brand['id']]);
    $relatedBrands = $stmt->fetchAll();
} catch(PDOException $e) {
    $relatedBrands = [];
}

// Sayfa bilgilerini ayarla
$pageTitle = $brand['name'] . ' - ' . $category['name'] . ' - ' . SITE_NAME;
$pageDescription = $brand['meta_description'] ?: $category['name'] . ' kategorisindeki ' . $brand['name'] . ' ürünlerini keşfedin';

// Breadcrumb
$breadcrumb = [
    ['text' => 'Ana Sayfa', 'url' => '/mrecuphpkopyasikopyasi6kopyasi/'],
    ['text' => 'Ürünler', 'url' => '/mrecuphpkopyasikopyasi6kopyasi/urunler'],
    ['text' => $category['name'], 'url' => '/mrecuphpkopyasikopyasi6kopyasi/kategori/' . $category['slug']],
    ['text' => $brand['name'], 'url' => '', 'active' => true]
];

// Header include
include 'includes/header.php';
?>

<style>
/* Modern Hero with Background Image */
.brand-category-hero {
    position: relative;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: white;
    padding: 8rem 0 5rem 0;
    margin-bottom: 2rem;
    border-radius: 0 0 30px 30px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.7);
    height: 340px;
    display: flex;
    align-items: center;
}

.brand-category-hero .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(44, 62, 80, 0.5), rgba(3 9 191 / 0.5));
    border-radius: 0 0 30px 30px;
    z-index: 1;
}

.brand-category-hero .container {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 3.2rem;
    font-weight: 800;
    margin-bottom: 1rem;
    letter-spacing: -0.5px;
}

.hero-subtitle {
    font-size: 1.4rem;
    opacity: 0.95;
    margin-bottom: 2.5rem;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

/* Logo */
.brand-logo-large {
    width: 140px;
    height: 100px;
    object-fit: contain;
    background: white;
    border-radius: 16px;
    padding: 16px;
    margin: 0 auto 1.5rem auto;
    display: block;
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    transition: all 0.4s ease;
}

.brand-logo-large:hover {
    transform: scale(1.08);
}

/* Stats */
.hero-stats {
    display: flex;
    justify-content: center;
    gap: 3.5rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}

.hero-stat {
    text-align: center;
    background: rgba(255, 255, 255, 0.15);
    padding: 1.3rem 2.2rem;
    border-radius: 18px;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    min-width: 140px;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.hero-stat:hover {
    transform: translateY(-6px);
    background: rgba(255, 255, 255, 0.25);
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}

.hero-stat-number {
    font-size: 2.4rem;
    font-weight: 700;
    display: block;
}

.hero-stat-label {
    font-size: 0.95rem;
    opacity: 0.9;
    letter-spacing: 0.8px;
}

/* Breadcrumb */
.breadcrumb-item a {
    color: #007bff;
    transition: color 0.2s;
}
.breadcrumb-item a:hover {
    color: #0056b3;
    text-decoration: underline;
}
.breadcrumb-item.active {
    color: #495057;
    font-weight: 600;
}

/* Products Header */
.products-header {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2.5rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    border: 1px solid #e9ecef;
}

.products-header .form-select {
    border-radius: 12px;
    border: 1px solid #ced4da;
    padding: 0.6rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.products-header .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

/* Product Card */
.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 6px 25px rgba(0,0,0,0.1);
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    text-decoration: none;
    color: inherit;
    height: 100%;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;
    position: relative;
}

.product-card:hover {
    transform: translateY(-10px) scale(1.03);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    border-color: #007bff;
}

.product-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image {
    transform: scale(1.1);
}

/* Badges */
.product-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 2;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-featured {
    background: linear-gradient(135deg, #ff6b6b, #ffa500);
    color: white;
}

.badge-sale {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

/* Info */
.product-info {
    padding: 1.8rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.8rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-description {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 1.2rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}

/* Price */
.product-price {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.current-price {
    font-size: 1.4rem;
    font-weight: 700;
    color: #28a745;
}

.original-price {
    font-size: 1.1rem;
    color: #95a5a6;
    text-decoration: line-through;
}

.discount-percentage {
    background: #dc3545;
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

/* Button */
.btn-detail {
    flex: 1;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 0.8rem;
    border-radius: 10px;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.btn-detail:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4);
    color: white;
    text-decoration: none;
}

/* Related Brands */
.related-brands {
    margin: 4rem 0;
    background: #f9fafa;
    border-radius: 25px;
    padding: 3rem 2rem;
    border: 1px solid #e9ecef;
    box-shadow: 0 6px 20px rgba(0,0,0,0.05);
}

.related-brand-card {
    background: white;
    border-radius: 14px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.4s ease;
    text-decoration: none;
    color: inherit;
    height: 100%;
    display: block;
    border: 1px solid #f0f0f0;
}

.related-brand-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    border-color: #007bff;
}

.related-brand-logo {
    width: 70px;
    height: 50px;
    object-fit: contain;
    margin: 0 auto 1rem auto;
    display: block;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
    transition: all 0.3s ease;
}

.related-brand-card:hover .related-brand-logo {
    transform: scale(1.1);
}

.related-brand-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.4rem;
    font-size: 1rem;
}

.related-brand-count {
    color: #7f8c8d;
    font-size: 0.85rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 6rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    margin: 3rem 0;
}

.empty-state i {
    font-size: 5rem;
    color: #bdc3c7;
    margin-bottom: 1.5rem;
    opacity: 0.6;
}

.empty-state h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-size: 1.8rem;
}

.empty-state p {
    color: #7f8c8d;
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
    padding: 0.9rem 2rem;
    font-size: 1.1rem;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
}

/* Pagination */
.pagination-container {
    margin: 4rem 0 2rem 0;
    display: flex;
    justify-content: center;
}

.pagination .page-link {
    color: #007bff;
    border-radius: 12px !important;
    margin: 0 4px;
    min-width: 50px;
    text-align: center;
    transition: all 0.3s ease;
}

.pagination .page-link:hover {
    background: #007bff;
    color: white;
    transform: scale(1.05);
}

.pagination .page-item.active .page-link {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

/* Responsive */
@media (max-width: 768px) {
    .brand-category-hero {
        padding: 6rem 0 4rem 0;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .hero-stats {
        gap: 1.8rem;
    }
    
    .hero-stat {
        padding: 1rem 1.8rem;
        min-width: 120px;
    }
    
    .hero-stat-number {
        font-size: 2rem;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .products-header {
        padding: 1.5rem;
    }
    
    .related-brands {
        padding: 2rem 1rem;
    }
}
</style>

<main>
    <!-- Hero Section -->
<section class="brand-category-hero" style="background-image: url('/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($brand['logo'] ?: 'assets/images/default-brand-bg.jpg'); ?>');">
    <div class="overlay"></div> <!-- Karartma katmanı -->
    <div class="container">
        <div class="hero-content">
            <!-- <?php if ($brand['logo']): ?>
                <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($brand['logo']); ?>" 
                     alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                     class="brand-logo-large">
            <?php else: ?>
                <div class="brand-logo-large d-flex align-items-center justify-content-center bg-white">
                    <i class="fas fa-award text-primary fa-3x"></i>
                </div>
            <?php endif; ?> -->
            
            <h1 class="hero-title"><?php echo htmlspecialchars($brand['name']); ?></h1>
            <!-- <p class="hero-subtitle">
                <?php echo htmlspecialchars($category['name']); ?> kategorisindeki tüm ürünler
            </p> -->
            
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo $totalProducts; ?></span>
                    <span class="hero-stat-label">Ürün</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo htmlspecialchars($category['name']); ?></span>
                    <span class="hero-stat-label">Kategori</span>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Breadcrumb -->
    <div class="container">
        <nav aria-label="breadcrumb" class="mt-3">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumb as $crumb): ?>
                    <?php if (isset($crumb['active'])): ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo htmlspecialchars($crumb['text']); ?>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo $crumb['url']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($crumb['text']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>

    <div class="container">
        <!-- Products Header -->
        <?php if (!empty($products)): ?>
            <div class="products-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="h4 mb-2">
                            <?php echo htmlspecialchars($brand['name']); ?> - <?php echo htmlspecialchars($category['name']); ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <?php echo $totalProducts; ?> ürün listeleniyor
                        </p>
                    </div>
                    
                    <!-- Sort Controls -->
                    <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                        <label class="form-label mb-0 me-2">Sırala:</label>
                        <select class="form-select" id="sortProducts" style="min-width: 160px;">
                            <option value="name-asc" <?php echo ($sortBy === 'name' && $sortDir === 'ASC') ? 'selected' : ''; ?>>
                                İsim (A-Z)
                            </option>
                            <option value="name-desc" <?php echo ($sortBy === 'name' && $sortDir === 'DESC') ? 'selected' : ''; ?>>
                                İsim (Z-A)
                            </option>
                            <option value="price-asc" <?php echo ($sortBy === 'price' && $sortDir === 'ASC') ? 'selected' : ''; ?>>
                                Fiyat (Düşük-Yüksek)
                            </option>
                            <option value="price-desc" <?php echo ($sortBy === 'price' && $sortDir === 'DESC') ? 'selected' : ''; ?>>
                                Fiyat (Yüksek-Düşük)
                            </option>
                            <option value="date-desc" <?php echo ($sortBy === 'date' && $sortDir === 'DESC') ? 'selected' : ''; ?>>
                                En Yeni
                            </option>
                            <option value="featured-desc" <?php echo ($sortBy === 'featured' && $sortDir === 'DESC') ? 'selected' : ''; ?>>
                                Öne Çıkanlar
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <?php if ($product['primary_image']): ?>
                                <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($product['primary_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image text-muted fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Badges -->
                            <?php if ($product['featured']): ?>
                                <div class="product-badge badge-featured">
                                    <i class="fas fa-star me-1"></i>Öne Çıkan
                                </div>
                            <?php elseif ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <?php 
                                $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                                ?>
                                <div class="product-badge badge-sale">
                                    %<?php echo $discount; ?> İndirim
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-title">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                            
                            <?php if ($product['short_description']): ?>
                                <p class="product-description">
                                    <?php echo htmlspecialchars($product['short_description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="product-price">
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="current-price">
                                        <?php echo number_format($product['sale_price'], 2); ?> TL
                                    </span>
                                    <span class="original-price">
                                        <?php echo number_format($product['price'], 2); ?> TL
                                    </span>
                                    <?php 
                                    $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                                    ?>
                                    <span class="discount-percentage">-%<?php echo $discount; ?></span>
                                <?php else: ?>
                                    <span class="current-price">
                                        <?php echo number_format($product['price'], 2); ?> TL
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <a href="/mrecuphpkopyasikopyasi6kopyasi/urun/<?php echo $product['slug']; ?>" class="btn-detail">
                                    <i class="fas fa-eye"></i>
                                    Detayları Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Ürün sayfaları">
                        <ul class="pagination pagination-lg">
                            <!-- Previous Page -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sortBy; ?>&dir=<?php echo strtolower($sortDir); ?>" aria-label="Önceki">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo $sortBy; ?>&dir=<?php echo strtolower($sortDir); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Page -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sortBy; ?>&dir=<?php echo strtolower($sortDir); ?>" aria-label="Sonraki">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Bu kombinasyonda ürün bulunamadı</h3>
                <p><?php echo htmlspecialchars($brand['name']); ?> markasının <?php echo htmlspecialchars($category['name']); ?> kategorisinde henüz ürün bulunmuyor.</p>
                <a href="/mrecuphpkopyasikopyasi6kopyasi/kategori/<?php echo $category['slug']; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>
                    <?php echo htmlspecialchars($category['name']); ?> Kategorisine Dön
                </a>
            </div>
        <?php endif; ?>

        <!-- Related Brands -->
        <?php if (!empty($relatedBrands)): ?>
            <section class="related-brands">
                <div class="text-center mb-4">
                    <h2 class="h3 mb-3">
                        <i class="fas fa-award text-primary me-2"></i>
                        <?php echo htmlspecialchars($category['name']); ?> Kategorisindeki Diğer Markalar
                    </h2>
                    <p class="text-muted">
                        Aynı kategorideki diğer markaları da keşfedebilirsiniz
                    </p>
                </div>
                
                <div class="row">
                    <?php foreach ($relatedBrands as $relatedBrand): ?>
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                            <a href="/mrecuphpkopyasikopyasi6kopyasi/kategori/<?php echo $category['slug']; ?>/marka/<?php echo $relatedBrand['slug']; ?>" 
                               class="related-brand-card">
                                <?php if ($relatedBrand['logo']): ?>
                                    <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($relatedBrand['logo']); ?>" 
                                         alt="<?php echo htmlspecialchars($relatedBrand['name']); ?>" 
                                         class="related-brand-logo">
                                <?php else: ?>
                                    <div class="related-brand-logo d-flex align-items-center justify-content-center">
                                        <i class="fas fa-award text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h4 class="related-brand-name"><?php echo htmlspecialchars($relatedBrand['name']); ?></h4>
                                <p class="related-brand-count"><?php echo $relatedBrand['product_count']; ?> ürün</p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<script>
// Sort functionality
document.getElementById('sortProducts')?.addEventListener('change', function() {
    const value = this.value.split('-');
    const sort = value[0];
    const dir = value[1];
    
    const url = new URL(window.location);
    
    if (sort !== 'name') {
        url.searchParams.set('sort', sort);
    } else {
        url.searchParams.delete('sort');
    }
    
    if (dir !== 'asc') {
        url.searchParams.set('dir', dir);
    } else {
        url.searchParams.delete('dir');
    }
    
    // Reset to first page when sorting
    url.searchParams.delete('page');
    
    window.location.href = url.toString();
});

// Smooth animations on scroll
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    
    // Observe all product cards
    document.querySelectorAll('.product-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });
});
</script>

<?php
$jsonLD = [
    "@context" => "https://schema.org",
    "@type" => "CollectionPage",
    "name" => $brand['name'] . " - " . $category['name'],
    "description" => $pageDescription,
    "url" => BASE_URL . "/kategori/{$category['slug']}/marka/{$brand['slug']}",
    "breadcrumb" => [
        "@type" => "BreadcrumbList",
        "itemListElement" => []
    ],
    "mainEntity" => [
        "@type" => "Brand",
        "name" => $brand['name'],
        "image" => BASE_URL . '/mrecuphpkopyasikopyasi6kopyasi/' . ($brand['logo'] ?: 'assets/images/default-brand-logo.png'),
        "url" => BASE_URL . "/kategori/{$category['slug']}/marka/{$brand['slug']}"
    ]
];

foreach ($breadcrumb as $index => $crumb) {
    $jsonLD["breadcrumb"]["itemListElement"][] = [
        "@type" => "ListItem",
        "position" => $index + 1,
        "name" => $crumb['text'],
        "item" => isset($crumb['url']) && !empty($crumb['url']) ? BASE_URL . $crumb['url'] : null
    ];
}

echo '<script type="application/ld+json">' . json_encode($jsonLD, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<?php
// Footer include
include 'includes/footer.php';
?>
