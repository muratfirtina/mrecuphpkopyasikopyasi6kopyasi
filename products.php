<?php
/**
 * Mr ECU - Ürün Listeleme Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// URL parametrelerini al
$categorySlug = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$brandSlug = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'name';
$sortDir = isset($_GET['dir']) && $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Sayfa bilgilerini başlat
$pageTitle = 'Ürünler';
$pageDescription = 'ECU programlama cihazları, chip tuning araçları ve otomotiv yazılım ürünleri';
$selectedCategory = null;
$selectedBrand = null;

// SQL query hazırlığı
$whereConditions = ['p.is_active = 1'];
$queryParams = [];

// Kategori filtresi
if (!empty($categorySlug)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
        $stmt->execute([$categorySlug]);
        $selectedCategory = $stmt->fetch();
        
        if ($selectedCategory) {
            $whereConditions[] = 'p.category_id = ?';
            $queryParams[] = $selectedCategory['id'];
            $pageTitle = $selectedCategory['name'] . ' - ' . SITE_NAME;
            $pageDescription = $selectedCategory['meta_description'] ?: $selectedCategory['description'] ?: $pageDescription;
        } else {
            // Geçersiz kategori slug'ı
            redirect('/404.php');
        }
    } catch(PDOException $e) {
        redirect('/404.php');
    }
}

// Marka filtresi
if (!empty($brandSlug)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_brands WHERE slug = ? AND is_active = 1");
        $stmt->execute([$brandSlug]);
        $selectedBrand = $stmt->fetch();
        
        if ($selectedBrand) {
            $whereConditions[] = 'p.brand_id = ?';
            $queryParams[] = $selectedBrand['id'];
            $pageTitle = $selectedBrand['name'] . ' Ürünleri - ' . SITE_NAME;
            $pageDescription = $selectedBrand['meta_description'] ?: $selectedBrand['description'] ?: $pageDescription;
        } else {
            // Geçersiz marka slug'ı
            redirect('/404.php');
        }
    } catch(PDOException $e) {
        redirect('/404.php');
    }
}

// Arama filtresi
if (!empty($searchQuery)) {
    $whereConditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ? OR pb.name LIKE ?)';
    $searchTerm = '%' . $searchQuery . '%';
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $pageTitle = '"' . $searchQuery . '" Arama Sonuçları - ' . SITE_NAME;
    $pageDescription = $searchQuery . ' ile ilgili ürünleri keşfedin';
}

// Sıralama seçenekleri
$validSortOptions = [
    'name' => 'p.name',
    'price' => 'COALESCE(p.sale_price, p.price)',
    'date' => 'p.created_at',
    'featured' => 'p.featured'
];

$orderBy = $validSortOptions[$sortBy] ?? $validSortOptions['name'];

// Öne çıkan ürünler önce gelsin (featured products first)
if ($sortBy !== 'featured') {
    $orderBy = 'p.featured DESC, p.sort_order, ' . $orderBy;
}

// Toplam ürün sayısını hesapla (sayfalama için)
try {
    $countSQL = "
        SELECT COUNT(*) as total
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE " . implode(' AND ', $whereConditions);
    
    $stmt = $pdo->prepare($countSQL);
    $stmt->execute($queryParams);
    $totalProducts = $stmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $perPage);
} catch(PDOException $e) {
    $totalProducts = 0;
    $totalPages = 1;
}

// Ürünleri getir
try {
    $productsSQL = "
        SELECT p.*, 
               c.name as category_name, c.slug as category_slug,
               pb.name as brand_name, pb.slug as brand_slug, pb.logo as brand_logo,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE " . implode(' AND ', $whereConditions) . "
        ORDER BY {$orderBy} {$sortDir}
        LIMIT {$perPage} OFFSET {$offset}";
    
    $stmt = $pdo->prepare($productsSQL);
    $stmt->execute($queryParams);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $products = [];
}

// Kategorileri getir (yan menü için)
try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        WHERE c.is_active = 1 
        GROUP BY c.id 
        HAVING product_count > 0 OR c.id = " . ($selectedCategory['id'] ?? 0) . "
        ORDER BY c.sort_order, c.name
    ");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Markaları getir (yan menü için)
try {
    $stmt = $pdo->query("
        SELECT pb.*, COUNT(p.id) as product_count 
        FROM product_brands pb 
        LEFT JOIN products p ON pb.id = p.brand_id AND p.is_active = 1
        WHERE pb.is_active = 1 
        GROUP BY pb.id 
        HAVING product_count > 0 OR pb.id = " . ($selectedBrand['id'] ?? 0) . "
        ORDER BY pb.sort_order, pb.name
    ");
    $brands = $stmt->fetchAll();
} catch(PDOException $e) {
    $brands = [];
}

// Breadcrumb oluştur
$breadcrumb = [
    ['text' => 'Ana Sayfa', 'url' => BASE_URL . '/']
];

if ($selectedCategory) {
$breadcrumb[] = ['text' => 'Ürünler', 'url' => BASE_URL . '/urunler'];
$breadcrumb[] = ['text' => $selectedCategory['name'], 'url' => '', 'active' => true];
} elseif ($selectedBrand) {
$breadcrumb[] = ['text' => 'Ürünler', 'url' => BASE_URL . '/urunler'];
$breadcrumb[] = ['text' => $selectedBrand['name'], 'url' => '', 'active' => true];
} elseif (!empty($searchQuery)) {
$breadcrumb[] = ['text' => 'Arama: ' . $searchQuery, 'url' => '', 'active' => true];
} else {
$breadcrumb[] = ['text' => 'Ürünler', 'url' => '', 'active' => true];
}

// Header include
include 'includes/header.php';
?>

<style>
.products-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem 0;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image-container {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 600;
}

.badge-featured {
    background: linear-gradient(135deg, #ff6b6b, #ffa500);
    color: white;
}

.badge-sale {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.product-info {
    padding: 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-brand {
    display: inline-flex;
    align-items: center;
    font-size: 0.875rem;
    color: #6c757d;
    text-decoration: none;
    margin-bottom: 0.5rem;
    transition: color 0.3s ease;
}

.product-brand:hover {
    color: #007bff;
    text-decoration: none;
}

.product-brand-logo {
    width: 20px;
    height: 15px;
    object-fit: contain;
    margin-right: 0.5rem;
}

.product-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.75rem;
    line-height: 1.4;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-title:hover {
    color: #007bff;
    text-decoration: none;
}

.product-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex-grow: 1;
}

.product-price-container {
    margin-top: auto;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.current-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #28a745;
}

.original-price {
    font-size: 1rem;
    color: #6c757d;
    text-decoration: line-through;
}

.discount-percentage {
    background: #dc3545;
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.contact-button {
    display: block;
    width: 100%;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    text-align: center;
    padding: 0.75rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.contact-button:hover {
    background: linear-gradient(135deg, #0056b3, #004494);
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
}

.filters-sidebar {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    position: relative;
    display: none; /* Başlangıçta kapalı */
    transition: all 0.3s ease;
}

.filters-sidebar.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filters-toggle {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,123,255,0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.filters-toggle::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.filters-toggle:hover::before {
    left: 100%;
}

.toggle-icon {
    transition: transform 0.3s ease;
}

.filters-toggle.active .toggle-icon {
    transform: rotate(180deg);
}

.filters-toggle:hover {
    background: linear-gradient(135deg, #0056b3, #004494);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,123,255,0.3);
}

.filters-toggle.active {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.filters-toggle.active:hover {
    background: linear-gradient(135deg, #c82333, #a71e2a);
}

/* Mobil için responsive ayarlar */
@media (max-width: 991.98px) {
    .filters-sidebar {
        position: fixed;
        top: 120px; /* Header yüksekliği kadar aşağıdan başlat */
        left: 0;
        width: 100%;
        height: calc(100vh - 120px); /* Header yüksekliğini çıkar */
        z-index: 10500; /* Header'dan daha yüksek z-index */
        overflow-y: auto;
        border-radius: 0;
        margin-bottom: 0;
        padding: 2rem 1rem 1rem 1rem;
    }
    
    .filters-sidebar.show {
        animation: slideInLeft 0.3s ease;
    }
    
    @keyframes slideInLeft {
        from {
            transform: translateX(-100%);
        }
        to {
            transform: translateX(0);
        }
    }
    
    .sidebar-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        z-index: 10600; /* En üstte olsun */
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        transition: all 0.3s ease;
    }
    
    .sidebar-close:hover {
        background: #c82333;
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }
    
    .filters-overlay {
        position: fixed;
        top: 120px; /* Header yüksekliği kadar aşağıdan başlat */
        left: 0;
        width: 100%;
        height: calc(100vh - 120px); /* Header yüksekliğini çıkar */
        background: rgba(0,0,0,0.7);
        z-index: 10400; /* Sidebar'dan düşük ama header'dan yüksek */
        display: none;
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }
    
    .filters-overlay.show {
        display: block;
    }
}

.filter-section {
    margin-bottom: 2rem;
}

.filter-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 0.5rem;
}

.filter-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.filter-item {
    margin-bottom: 0.5rem;
}

.filter-link {
    display: flex;
    justify-content: between;
    align-items: center;
    color: #6c757d;
    text-decoration: none;
    padding: 0.5rem 0;
    transition: color 0.3s ease;
}

.filter-link:hover,
.filter-link.active {
    color: #007bff;
    text-decoration: none;
}

.filter-count {
    background: #f8f9fa;
    color: #6c757d;
    padding: 0.125rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    margin-left: auto;
}

.products-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.sort-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.sort-select {
    min-width: 150px;
}

.no-products {
    background: white;
    border-radius: 12px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.pagination-container {
    margin-top: 3rem;
    display: flex;
    justify-content: center;
}

@media (max-width: 768px) {
    .products-container {
        padding: 1rem 0;
    }
    
    .sort-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sort-select {
        width: 100%;
    }
    
    /* Küçük mobil ekranlar için daha düşük header yüksekliği */
    .filters-sidebar {
        top: 100px !important;
        height: calc(100vh - 100px) !important;
    }
    
    .filters-overlay {
        top: 100px !important;
        height: calc(100vh - 100px) !important;
    }
}

/* Tablet boyutları için özel ayarlar */
@media (min-width: 768px) and (max-width: 991.98px) {
    .filters-sidebar {
        top: 140px !important;
        height: calc(100vh - 140px) !important;
    }
    
    .filters-overlay {
        top: 140px !important;
        height: calc(100vh - 140px) !important;
    }
}

/* Büyük mobil ekranlar için */
@media (min-width: 576px) and (max-width: 767.98px) {
    .filters-sidebar {
        top: 110px !important;
        height: calc(100vh - 110px) !important;
    }
    
    .filters-overlay {
        top: 110px !important;
        height: calc(100vh - 110px) !important;
    }
}
</style>

<main class="products-container">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
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

        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <!-- Filters Toggle Button -->
                <button class="btn filters-toggle w-100" id="filtersToggle" type="button">
                    <i class="bi bi-funnel me-2"></i>
                    <span class="toggle-text">Filtreleri Göster</span>
                    <i class="bi bi-chevron-down ms-auto toggle-icon"></i>
                </button>
                
                <!-- Mobile Overlay -->
                <div class="filters-overlay" id="filtersOverlay"></div>
                
                <div class="filters-sidebar" id="filtersSidebar">
                    <!-- Mobile Close Button -->
                    <button class="sidebar-close d-lg-none" id="sidebarClose" type="button">
                        <i class="bi bi-x"></i>
                    </button>
                    <!-- Search -->
                    <div class="filter-section">
                        <h5 class="filter-title">
                            <i class="bi bi-search me-2"></i>Arama
                        </h5>
                        <form action="<?php echo BASE_URL; ?>/urunler" method="get" class="mb-0">
                            <?php if ($categorySlug): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($categorySlug); ?>">
                            <?php endif; ?>
                            <?php if ($brandSlug): ?>
                                <input type="hidden" name="brand" value="<?php echo htmlspecialchars($brandSlug); ?>">
                            <?php endif; ?>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Ürün ara..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Categories -->
                    <?php if (!empty($categories)): ?>
                        <div class="filter-section">
                            <h5 class="filter-title">
                                <i class="bi bi-tags me-2"></i>Kategoriler
                            </h5>
                            <ul class="filter-list">
                                <li class="filter-item">
                                    <a href="<?php echo BASE_URL; ?>/urunler" 
                                       class="filter-link <?php echo empty($categorySlug) ? 'active' : ''; ?>">
                                        Tüm Kategoriler
                                    </a>
                                </li>
                                <?php foreach ($categories as $category): ?>
                                    <li class="filter-item">
                                        <a href="<?php echo BASE_URL; ?>/kategori/<?php echo htmlspecialchars($category['slug']); ?>"
                                           class="filter-link <?php echo $categorySlug === $category['slug'] ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                            <span class="filter-count"><?php echo $category['product_count']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Brands -->
                    <?php if (!empty($brands)): ?>
                        <div class="filter-section">
                            <h5 class="filter-title">
                                <i class="bi bi-award me-2"></i>Markalar
                            </h5>
                            <ul class="filter-list">
                                <li class="filter-item">
                                    <a href="<?php echo BASE_URL; ?>/urunler" 
                                       class="filter-link <?php echo empty($brandSlug) ? 'active' : ''; ?>">
                                        Tüm Markalar
                                    </a>
                                </li>
                                <?php foreach ($brands as $brand): ?>
                                    <li class="filter-item">
                                        <a href="<?php echo BASE_URL; ?>/marka/<?php echo htmlspecialchars($brand['slug']); ?>"
                                           class="filter-link <?php echo $brandSlug === $brand['slug'] ? 'active' : ''; ?>">
                                            <?php if ($brand['logo']): ?>
                                                <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($brand['logo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($brand['name']); ?>"
                                                     class="product-brand-logo">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                            <span class="filter-count"><?php echo $brand['product_count']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Content -->
            <div class="col-lg-9">
                <!-- Products Header -->
                <div class="products-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h1 class="h3 mb-2">
                                <?php echo htmlspecialchars($pageTitle); ?>
                            </h1>
                            <p class="text-muted mb-0">
                                <?php echo $totalProducts; ?> ürün bulundu
                                <?php if (!empty($searchQuery)): ?>
                                    "<?php echo htmlspecialchars($searchQuery); ?>" için
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- Sort Controls -->
                        <div class="sort-controls mt-3 mt-lg-0">
                            <label class="form-label mb-0 me-2">Sırala:</label>
                            <select class="form-select sort-select" id="sortProducts">
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
                <?php if (!empty($products)): ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="product-card">
                                    <div class="product-image-container">
                                        <a href="<?php echo BASE_URL; ?>/urun/<?php echo $product['slug']; ?>">
                                            <?php if ($product['primary_image']): ?>
                                                <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($product['primary_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="product-image">
                                            <?php else: ?>
                                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-image text-muted fa-3x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                        
                                        <!-- Badges -->
                                        <?php if ($product['featured']): ?>
                                            <div class="product-badge badge-featured">
                                                <i class="bi bi-star me-1"></i>Öne Çıkan
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
                                        <?php if ($product['brand_name']): ?>
                                            <a href="<?php echo BASE_URL; ?>/marka/<?php echo $product['brand_slug']; ?>" 
                                               class="product-brand">
                                                <?php if ($product['brand_logo']): ?>
                                                    <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($product['brand_logo']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['brand_name']); ?>"
                                                         class="product-brand-logo">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($product['brand_name']); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo BASE_URL; ?>/urun/<?php echo $product['slug']; ?>" class="product-title">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                        
                                        <?php if ($product['short_description']): ?>
                                            <p class="product-description">
                                                <?php echo htmlspecialchars($product['short_description']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="product-price-container">
                                            <div class="product-price">
                                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                                    <span class="current-price">
                                                        <?php echo number_format($product['sale_price'], 2); ?> TL
                                                    </span>
                                                    <span class="original-price">
                                                        <?php echo number_format($product['price'], 2); ?> TL
                                                    </span>
                                                <?php else: ?>
                                                    <span class="current-price">
                                                        <?php echo number_format($product['price'], 2); ?> TL
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <a href="<?php echo BASE_URL; ?>/urun/<?php echo $product['slug']; ?>" class="contact-button">
                                                <i class="bi bi-info-circle me-2"></i>Detayları Görüntüle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-container">
                            <nav aria-label="Ürün sayfaları">
                                <ul class="pagination">
                                    <!-- Previous Page -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildProductURL(['page' => $page - 1]); ?>" aria-label="Önceki">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Page Numbers -->
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    ?>
                                    
                                    <?php if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildProductURL(['page' => 1]); ?>">1</a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildProductURL(['page' => $i]); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildProductURL(['page' => $totalPages]); ?>">
                                                <?php echo $totalPages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Next Page -->
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildProductURL(['page' => $page + 1]); ?>" aria-label="Sonraki">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Products Found -->
                    <div class="no-products">
                        <i class="bi bi-search fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted mb-3">Ürün bulunamadı</h4>
                        <?php if (!empty($searchQuery)): ?>
                            <p class="text-muted mb-4">
                                "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>" için arama sonucu bulunamadı.
                            </p>
                            <a href="<?php echo BASE_URL; ?>/urunler" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Tüm Ürünleri Görüntüle
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-4">Seçilen kriterlerde ürün bulunmamaktadır.</p>
                            <a href="<?php echo BASE_URL; ?>/urunler" class="btn btn-primary">
                                <i class="bi bi-refresh me-2"></i>Filtreleri Temizle
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
// URL builder function
function buildProductURL($params = []) {
    global $categorySlug, $brandSlug, $searchQuery, $sortBy, $sortDir;
    
    $currentParams = [];
    
    if ($categorySlug) $currentParams['category'] = $categorySlug;
    if ($brandSlug) $currentParams['brand'] = $brandSlug;
    if ($searchQuery) $currentParams['search'] = $searchQuery;
    if ($sortBy !== 'name') $currentParams['sort'] = $sortBy;
    if ($sortDir !== 'ASC') $currentParams['dir'] = strtolower($sortDir);
    
    $currentParams = array_merge($currentParams, $params);
    
    // SEO Dostu URL'ler
    if (isset($currentParams['category'])) {
        $slug = $currentParams['category'];
        unset($currentParams['category']);
        
        $url = BASE_URL . '/kategori/' . $slug;
        if (!empty($currentParams)) {
            $url .= '?' . http_build_query($currentParams);
        }
        return $url;
    }
    
    if (isset($currentParams['brand'])) {
        $slug = $currentParams['brand'];
        unset($currentParams['brand']);
        
        $url = BASE_URL . '/marka/' . $slug;
        if (!empty($currentParams)) {
            $url .= '?' . http_build_query($currentParams);
        }
        return $url;
    }
    
    // Ana ürün listesi
    $url = BASE_URL . '/urunler';
    if (!empty($currentParams)) {
        $url .= '?' . http_build_query($currentParams);
    }
    return $url;
}
?>

<script>
// Filters Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const filtersToggle = document.getElementById('filtersToggle');
    const filtersSidebar = document.getElementById('filtersSidebar');
    const filtersOverlay = document.getElementById('filtersOverlay');
    const sidebarClose = document.getElementById('sidebarClose');
    const toggleText = filtersToggle.querySelector('.toggle-text');
    const toggleIcon = filtersToggle.querySelector('.toggle-icon');
    
    let isFiltersOpen = false;
    
    // Toggle filters sidebar
    function toggleFilters() {
        isFiltersOpen = !isFiltersOpen;
        
        if (isFiltersOpen) {
            // Önce overlay'i göster (mobilde)
            if (window.innerWidth <= 991) {
                filtersOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            
            // Sidebar'i göster
            setTimeout(() => {
                filtersSidebar.classList.add('show');
            }, 50);
            
            filtersToggle.classList.add('active');
            toggleText.textContent = 'Filtreleri Gizle';
            
        } else {
            // Sidebar'i gizle
            filtersSidebar.classList.remove('show');
            filtersToggle.classList.remove('active');
            toggleText.textContent = 'Filtreleri Göster';
            
            // Animasyon bittikten sonra overlay'i gizle
            setTimeout(() => {
                filtersOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }, 300);
        }
    }
    
    // Close filters
    function closeFilters() {
        if (isFiltersOpen) {
            toggleFilters();
        }
    }
    
    // Event listeners
    filtersToggle.addEventListener('click', toggleFilters);
    sidebarClose?.addEventListener('click', closeFilters);
    filtersOverlay?.addEventListener('click', closeFilters);
    
    // ESC tuşu ile kapatma
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isFiltersOpen) {
            closeFilters();
        }
    });
    
    // Ekran boyutu değiştiğinde overlay'i temizle
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            filtersOverlay?.classList.remove('show');
            document.body.style.overflow = '';
            // Desktop'ta filtreleri açık bırak
            if (isFiltersOpen) {
                filtersSidebar.classList.add('show');
            }
        }
    });
    
    // Sayfa yüklenirken mobil kontrolü
    function handleInitialLoad() {
        if (window.innerWidth <= 991) {
            // Mobilde filtreleri kapat
            filtersSidebar.classList.remove('show');
            filtersOverlay?.classList.remove('show');
            isFiltersOpen = false;
            filtersToggle.classList.remove('active');
            toggleText.textContent = 'Filtreleri Göster';
        }
    }
    
    handleInitialLoad();
});

// Sort functionality
document.getElementById('sortProducts').addEventListener('change', function() {
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

// Lazy loading for images
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.product-image');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
});
</script>

<!-- Products JavaScript -->
<script src="admin/js/products.js"></script>

<?php
// Footer include
include 'includes/footer.php';
?>
