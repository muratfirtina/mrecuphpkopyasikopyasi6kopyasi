<?php
/**
 * Mr ECU - Kategori Sayfası
 * Belirli bir kategorideki markaları listeler
 */

require_once 'config/config.php';
require_once 'config/database.php';

// URL'den kategori slug'ını al
$categorySlug = '';
if (isset($_GET['slug'])) {
    $categorySlug = sanitize($_GET['slug']);
} else {
    // Fallback: URL path'den slug çıkar (URL rewrite çalışmıyorsa)
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/kategori\/([a-zA-Z0-9-]+)/', $uri, $matches)) {
        $categorySlug = $matches[1];
    } else {
        redirect('/404.php');
    }
}

// Kategori bilgilerini getir
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
    $stmt->execute([$categorySlug]);
    $category = $stmt->fetch();
    
    if (!$category) {
        redirect('/404.php');
    }
} catch(PDOException $e) {
    redirect('/404.php');
}

// Bu kategorideki markaları getir (sadece ürünü olan markalar)
try {
    $stmt = $pdo->prepare("
        SELECT pb.*, COUNT(p.id) as product_count 
        FROM product_brands pb 
        INNER JOIN products p ON pb.id = p.brand_id 
        WHERE p.category_id = ? AND p.is_active = 1 AND pb.is_active = 1
        GROUP BY pb.id 
        ORDER BY pb.sort_order, pb.name
    ");
    $stmt->execute([$category['id']]);
    $brands = $stmt->fetchAll();
} catch(PDOException $e) {
    $brands = [];
}

// Bu kategorideki toplam ürün sayısını getir
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND is_active = 1");
    $stmt->execute([$category['id']]);
    $totalProducts = $stmt->fetch()['total'];
} catch(PDOException $e) {
    $totalProducts = 0;
}

// Bu kategorideki öne çıkan ürünleri getir
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pb.name as brand_name, pb.slug as brand_slug,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.category_id = ? AND p.is_active = 1 AND p.featured = 1
        ORDER BY p.sort_order, p.name
        LIMIT 6
    ");
    $stmt->execute([$category['id']]);
    $featuredProducts = $stmt->fetchAll();
} catch(PDOException $e) {
    $featuredProducts = [];
}

// Sayfa bilgilerini ayarla
$pageTitle = $category['name'] . ' - ' . SITE_NAME;
$pageDescription = $category['meta_description'] ?: $category['description'] ?: $category['name'] . ' kategorisindeki ürünleri keşfedin';
$pageKeywords = $category['name'] . ', ' . implode(', ', array_column($brands, 'name'));

// Breadcrumb
$breadcrumb = [
    ['text' => 'Ana Sayfa', 'url' => '/mrecuphpkopyasikopyasi6kopyasi/'],
    ['text' => 'Ürünler', 'url' => '/mrecuphpkopyasikopyasi6kopyasi/urunler'],
    ['text' => $category['name'], 'url' => '', 'active' => true]
];

// Header include
include 'includes/header.php';
?>

<style>
/* Modern Hero Section with Background Image */
.category-hero {
    height: 340px;
    position: relative;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: white;
    padding: 6rem 0 3rem 0;
    margin-bottom: 2.5rem;
    border-radius: 0 0 30px 30px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.5);
}

.category-hero .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(44, 62, 80, 0.5), rgba(3 9 191 / 0.5));
    border-radius: 0 0 30px 30px;
    z-index: 1;
}

.category-hero .container {
    position: relative;
    z-index: 2;
}

.category-title {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
    text-shadow: 0 2px 6px rgba(0,0,0,0.6);
}

.category-description {
    font-size: 1.3rem;
    max-width: 700px;
    margin: 0 auto 2rem auto;
    opacity: 0.95;
}

.category-stats {
    display: flex;
    justify-content: center;
    gap: 2.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.15);
    padding: 1.2rem 2rem;
    border-radius: 16px;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    min-width: 130px;
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.stat-number {
    font-size: 2.6rem;
    font-weight: 700;
    display: block;
    margin-bottom: 0.3rem;
}

.stat-label {
    font-size: 0.95rem;
    opacity: 0.9;
    letter-spacing: 0.5px;
}

/* Breadcrumb modernization */
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

/* Brand Cards - Modern Flat Design */
.brands-section {
    margin: 2rem 0 4rem 0;
}

.brand-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    text-decoration: none;
    color: inherit;
    height: 100%;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
}

.brand-card:hover {
    transform: translateY(-10px) scale(1.03);
    box-shadow: 0 15px 35px rgba(0,0,0,0.18);
    border-color: #007bff;
}

.brand-logo {
    width: 280px;
    object-fit: contain;
    margin: 0 auto 1.2rem auto;
    display: block;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 12px;
    transition: all 0.3s ease;
}

.brand-card:hover .brand-logo {
    transform: scale(1.05);
}

.brand-name {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.brand-product-count {
    color: #7f8c8d;
    font-size: 0.95rem;
    margin-bottom: 1rem;
}

.brand-view-products {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 0.8rem 1.6rem;
    border-radius: 30px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.brand-card:hover .brand-view-products {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: scale(1.08);
}

/* Featured Products - Modern Grid */
.featured-products {
    background: #f9fafa;
    padding: 4rem 0;
    margin: 3rem 0;
    border-radius: 25px;
    border: 1px solid #e9ecef;
}

.product-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.09);
    transition: all 0.4s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    border: 1px solid #f0f0f0;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    border-color: #007bff;
}

.product-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: all 0.4s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-info {
    padding: 1.6rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-brand {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.product-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.8rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 1.1rem;
}

.product-price {
    margin-top: auto;
    font-size: 1.2rem;
    font-weight: 700;
    color: #28a745;
}

/* CTA Section - Refreshed */
.all-products-cta {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    padding: 4rem 0;
    margin: 3rem 0;
    border-radius: 25px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.2);
}

.cta-title {
    font-size: 2.4rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.cta-description {
    font-size: 1.2rem;
    opacity: 0.95;
    margin-bottom: 2rem;
    max-width: 650px;
    margin-left: auto;
    margin-right: auto;
}

.cta-button {
    background: white;
    color: #28a745;
    padding: 1.1rem 2.2rem;
    border-radius: 60px;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    border: 2px solid transparent;
}

.cta-button:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    color: #1e7e34;
    border-color: #1e7e34;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 6rem 0;
    color: #95a5a6;
}

.empty-state i {
    font-size: 5rem;
    margin-bottom: 1.5rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .category-title {
        font-size: 2.5rem;
    }
    
    .category-description {
        font-size: 1.1rem;
    }
    
    .category-stats {
        gap: 1.2rem;
    }
    
    .stat-item {
        padding: 1rem 1.5rem;
        min-width: 100px;
    }
    
    .stat-number {
        font-size: 2.2rem;
    }
    
    .brand-logo {
        width: 240px;
        height: 100px;
    }
    
    .cta-title {
        font-size: 1.8rem;
    }
    
    .cta-description {
        font-size: 1rem;
    }
}
</style>

<main>
    
    <!-- Category Hero -->
    <section class="category-hero" style="background-image: url('/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($category['image'] ?: 'assets/images/default-category-bg.jpg'); ?>');">    
        <div class="overlay"></div>
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <h1 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h1>
                    <?php if ($category['description']): ?>
                        <p class="category-description">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="category-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($brands); ?></span>
                            <span class="stat-label">Marka</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $totalProducts; ?></span>
                            <span class="stat-label">Ürün</span>
                        </div>
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

    <!-- Brands Section -->
    <?php if (!empty($brands)): ?>
        <section class="brands-section">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="h1 mb-3">
                        <i class="text-primary me-3"></i>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h2>
                    <p class="lead text-muted">
                        İhtiyacınıza uygun markayı seçerek ürünleri keşfedebilirsiniz
                    </p>
                </div>
                
                <div class="row">
                    <?php foreach ($brands as $brand): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <a href="/mrecuphpkopyasikopyasi6kopyasi/kategori/<?php echo $category['slug']; ?>/marka/<?php echo $brand['slug']; ?>" class="brand-card">
                                <?php if ($brand['logo']): ?>
                                    <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($brand['logo']); ?>" 
                                         alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                                         class="brand-logo">
                                <?php else: ?>
                                    <div class="brand-logo d-flex align-items-center justify-content-center">
                                        <i class="bi bi-award text-muted fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- <h3 class="brand-name"><?php echo htmlspecialchars($brand['name']); ?></h3> -->
                                <p class="brand-product-count">
                                    <i class="bi bi-box me-1"></i>
                                    <?php echo $brand['product_count']; ?> ürün
                                </p>
                                
                                <span class="brand-view-products">
                                    Ürünleri Görüntüle
                                    <i class="bi bi-arrow-right"></i>
                                </span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="empty-state">
            <div class="container">
                <i class="bi bi-box-open"></i>
                <h3>Bu kategoride henüz marka bulunmuyor</h3>
                <p>Yakında yeni markalar eklenecektir.</p>
            </div>
        </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <?php if (!empty($featuredProducts)): ?>
        <section class="featured-products">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="h1 mb-3">
                        <i class="bi bi-star text-warning me-3"></i>
                        Öne Çıkan Ürünler
                    </h2>
                    <p class="lead text-muted">
                        <?php echo htmlspecialchars($category['name']); ?> kategorisindeki popüler ürünler
                    </p>
                </div>
                
                <div class="row">
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <a href="/mrecuphpkopyasikopyasi6kopyasi/urun/<?php echo $product['slug']; ?>" class="product-card">
                                <?php if ($product['primary_image']): ?>
                                    <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($product['primary_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-muted fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-info">
                                    <?php if ($product['brand_name']): ?>
                                        <p class="product-brand">
                                            <i class="bi bi-award me-1"></i>
                                            <?php echo htmlspecialchars($product['brand_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    
                                    <div class="product-price">
                                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                            <span class="text-muted text-decoration-line-through me-2">
                                                <?php echo number_format($product['price'], 2); ?> TL
                                            </span>
                                        <?php endif; ?>
                                        <?php echo number_format($product['sale_price'] ?: $product['price'], 2); ?> TL
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- All Products CTA -->
    <section class="all-products-cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Tüm Ürünleri Keşfedin</h2>
                <p class="cta-description">
                    <?php echo htmlspecialchars($category['name']); ?> kategorisindeki tüm ürünleri görüntülemek için tıklayın
                </p>
                <a href="/mrecuphpkopyasikopyasi6kopyasi/urunler?category=<?php echo $category['slug']; ?>" class="cta-button">
                    <i class="bi bi-th-large"></i>
                    Tüm Ürünleri Görüntüle
                </a>
            </div>
        </div>
    </section>
</main>

<script>
// Smooth animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate statistics
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
        const targetValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = Math.ceil(targetValue / 50);
        
        const updateCounter = () => {
            currentValue += increment;
            if (currentValue >= targetValue) {
                stat.textContent = targetValue;
            } else {
                stat.textContent = currentValue;
                setTimeout(updateCounter, 20);
            }
        };
        
        // Start animation when element is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(stat.parentElement);
    });
    
    // Add hover effects
    const brandCards = document.querySelectorAll('.brand-card');
    brandCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>

<?php
// JSON-LD structured data for SEO
$jsonLD = [
    "@context" => "https://schema.org/",
    "@type" => "CollectionPage",
    "name" => $category['name'],
    "description" => $category['description'],
    "url" => BASE_URL . '/kategori/' . $category['slug'],
    "breadcrumb" => [
        "@type" => "BreadcrumbList",
        "itemListElement" => []
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

// Footer include
include 'includes/footer.php';
?>
