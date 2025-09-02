<?php

/**
 * Mr ECU - Ürün Detay Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// URL'den ürün slug'ını al
$productSlug = '';
if (isset($_GET['slug'])) {
    $productSlug = sanitize($_GET['slug']);
} elseif (isset($_GET['id'])) {
    // ID ile erişim için slug'ı bul
    $productId = sanitize($_GET['id']);
    $stmt = $pdo->prepare("SELECT slug FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    if ($result) {
        $productSlug = $result['slug'];
        // SEO için doğru URL'e yönlendir
        redirect('/urun/' . $productSlug);
    }
}

if (empty($productSlug)) {
    redirect('/404.php');
}

// Ürün bilgilerini getir
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug,
               pb.name as brand_name, pb.slug as brand_slug, pb.logo as brand_logo
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.slug = ? AND p.is_active = 1
    ");
    $stmt->execute([$productSlug]);
    $product = $stmt->fetch();

    if (!$product) {
        redirect('/404.php');
    }
} catch (PDOException $e) {
    redirect('/404.php');
}

// Ürün resimlerini getir
try {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
    $stmt->execute([$product['id']]);
    $productImages = $stmt->fetchAll();
} catch (PDOException $e) {
    $productImages = [];
}

// İlgili ürünleri getir (aynı kategori veya marka)
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pb.name as brand_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.is_active = 1 AND p.id != ? 
        AND (p.category_id = ? OR p.brand_id = ?)
        ORDER BY p.featured DESC, RAND()
        LIMIT 6
    ");
    $stmt->execute([$product['id'], $product['category_id'], $product['brand_id']]);
    $relatedProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    $relatedProducts = [];
}

// Meta bilgileri ayarla
$pageTitle = $product['meta_title'] ?: $product['name'] . ' - ' . SITE_NAME;
$pageDescription = $product['meta_description'] ?: $product['short_description'] ?: mb_substr(strip_tags($product['description']), 0, 160);
$pageKeywords = $product['name'] . ', ' . $product['category_name'] . ', ' . $product['brand_name'];
$pageImage = !empty($productImages) ? BASE_URL . '/' . $productImages[0]['image_path'] : '';

// Breadcrumb
$breadcrumb = [
    ['text' => 'Ana Sayfa', 'url' => '/mrecuphpkopyasikopyasi6kopyasi/'],
    ['text' => 'Ürünler', 'url' => '/mrecuphpkopyasikopyasi6kopyasi/urunler'],
];

if ($product['category_name']) {
    $breadcrumb[] = ['text' => $product['category_name'], 'url' => BASE_URL . '/kategori/' . $product['category_slug']];
}

$breadcrumb[] = ['text' => $product['name'], 'url' => '', 'active' => true];

// Sayfa görüntülenmesini say
try {
    $stmt = $pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
    $stmt->execute([$product['id']]);
} catch (PDOException $e) {
    // Hata logla ama sayfayı durdurmayı
    error_log('Product view count update failed: ' . $e->getMessage());
}

// Contact cards bilgilerini getir
try {
    $stmt = $pdo->prepare("SELECT * FROM contact_cards WHERE id IN (1, 2, 3) ORDER BY id");
    $stmt->execute();
    $contactCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ID'lere göre indexle
    $contactCardsById = [];
    foreach ($contactCards as $card) {
        $contactCardsById[$card['id']] = $card;
    }
} catch (PDOException $e) {
    error_log('Contact cards fetch failed: ' . $e->getMessage());
    $contactCardsById = [];
}

// Header include
include 'includes/header.php';
?>

<!-- Google Fonts: Inter (UI) + Open Sans (Body) -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

<style>
    /* Reset & Base */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        line-height: 1.7;
        color: #333;
        background: #f9fafb;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        line-height: 1.3;
        color: #1f2937;
    }

    a {
        text-decoration: none;
        color: inherit;
        transition: color 0.2s ease;
    }

    a:hover {
        color: #007bff;
    }

    .container {
        max-width: 1280px;
        margin: 0 auto;
        /* padding: 0 1.5rem; */
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-primary {
        background: #007bff;
        color: white;
        border: 1px solid #007bff;
    }

    .btn-primary:hover {
        background: #0056b3;
        border-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
    }

    .btn-outline {
        background: transparent;
        color: #007bff;
        border: 1px solid #007bff;
    }

    .btn-outline:hover {
        background: #007bff;
        color: white;
    }
</style>

<style>
    /* Ürün Detay Ana Konteyner */
    .product-detail {
        padding: 4rem 4rem;
        background: white;
        border-radius: 24px;
        overflow: hidden;
        margin: 2rem auto;
        max-width: 1400px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }

    /* Grid Layout */
    .product-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
    }

    /* Resim Galerisi */
    .image-gallery {
        position: relative;
    }

    .main-image {
        width: 100%;
        height: 500px;
        object-fit: contain;
        border-radius: 16px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease;
    }

    .main-image:hover {
        transform: scale(1.02);
    }

    .thumbnail-container {
        display: flex;
        gap: 12px;
        margin-top: 1rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        scrollbar-width: none;
        padding: 1rem 1rem;
    }

    .thumbnail-container::-webkit-scrollbar {
        display: none;
    }

    .thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
        background: #f3f4f6;
    }

    .thumbnail:hover,
    .thumbnail.active {
        border-color: #007bff;
        transform: scale(1.05);
    }

    /* Ürün Bilgileri */
    .product-info {
        padding: 1rem 0;
    }

    .product-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }

    .product-brand {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 0.6rem 1.2rem;
        border-radius: 30px;
        text-decoration: none;
        color: #495057;
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1.2rem;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        gap: 0.6rem;
    }

    .product-brand:hover {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
    }

    .product-brand img {
        width: 36px;
        height: 24px;
        object-fit: contain;
    }

    /* Fiyat */
    .price-container {
        margin: 1.5rem 0;
    }

    .current-price {
        font-size: 2.2rem;
        font-weight: 700;
        color: #16a34a;
    }

    .original-price {
        font-size: 1.4rem;
        color: #9ca3af;
        text-decoration: line-through;
        margin-left: 1rem;
    }

    .discount-badge {
        background: #dc2626;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    /* Özellikler */
    .features {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 1.5rem;
        margin: 2rem 0;
    }

    .features h5 {
        font-size: 1.1rem;
        color: #374151;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .feature-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        list-style: none;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #4b5563;
        font-size: 0.95rem;
    }

    .feature-item::before {
        content: "✓";
        color: #16a34a;
        font-weight: bold;
        background: #d1fae5;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }

    /* İletişim Bölümü */
    .contact-section {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        border-radius: 16px;
        padding: 2.5rem;
        text-align: center;
        margin: 2.5rem 0;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.2);
    }

    .contact-section h4 {
        font-size: 1.6rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .contact-section p {
        opacity: 0.9;
        font-size: 1.1rem;
    }

    .contact-buttons {
        display: flex;
        gap: 1.2rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 1.8rem;
    }

    .contact-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.9rem 1.8rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        border: 2px solid;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .contact-btn-primary {
        background: white;
        color: #28a745;
        border-color: white;
    }

    .contact-btn-primary:hover {
        background: transparent;
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .contact-btn-secondary {
        background: transparent;
        color: white;
        border-color: rgba(255, 255, 255, 0.4);
    }

    .contact-btn-secondary:hover {
        background: white;
        color: #28a745;
        border-color: white;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    /* Ürün Açıklaması */
    .description-section {
        margin: 4rem 0;
    }

    .description-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .product-description {
        color: #4b5563;
        font-size: 1.05rem;
        line-height: 1.8;
    }

    .product-description p {
        margin-bottom: 1rem;
    }

    .product-description img {
        max-width: 100%;
        border-radius: 12px;
        margin: 1.5rem 0;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    /* Teknik Özellikler */
    .specs-card {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 1.8rem;
        margin-top: 2rem;
    }

    .specs-card h3 {
        font-size: 1.3rem;
        color: #111827;
        margin-bottom: 1.2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .spec-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    .spec-table td {
        padding: 0.75rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .spec-table td:first-child {
        font-weight: 600;
        color: #4b5563;
        width: 35%;
    }

    .spec-table tr:last-child td {
        border-bottom: none;
    }

    /* İlgili Ürünler */
    .related-products {
        margin: 4rem 0;
    }

    .related-product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
        border: 1px solid #e9ecef;
    }

    .related-product-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
        border-color: #007bff;
    }

    .related-product-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .related-product-card:hover .related-product-image {
        transform: scale(1.05);
    }

    .related-product-info {
        padding: 1.6rem;
    }

    .related-product-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .related-product-price {
        color: #28a745;
        font-weight: 700;
        font-size: 1.2rem;
    }

    /* Stok Durumu */
    .stock-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        margin-top: 0.5rem;
    }

    .stock-in {
        background: #dcfce7;
        color: #166534;
    }

    .stock-out {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .product-grid {
            grid-template-columns: 1fr;
        }

        .main-image {
            height: 400px;
        }
    }

    @media (max-width: 768px) {
        .product-detail {
            margin: 1rem;
            border-radius: 16px;
        }

        .contact-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            max-width: 300px;
        }
    }
</style>

<main>
    <!-- Breadcrumb -->
    <div class="container">
        <nav aria-label="breadcrumb" class="mt-4">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumb as $crumb): ?>
                    <?php if (isset($crumb['active'])): ?>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($crumb['text']); ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo $crumb['url']; ?>"><?php echo htmlspecialchars($crumb['text']); ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>

    <!-- Ürün Detay -->
    <div class="container">
        <div class="product-detail">
            <div class="product-grid">
                <!-- Resimler -->
                <div class="image-gallery">
                    <?php if (!empty($productImages)): ?>
                        <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($productImages[0]['image_path']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="main-image" id="mainImage">

                        <?php if (count($productImages) > 1): ?>
                            <div class="thumbnail-container">
                                <?php foreach ($productImages as $index => $image): ?>
                                    <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($image['image_path']); ?>"
                                        alt="Görsel <?php echo $index + 1; ?>"
                                        class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                        onclick="changeImage(this)">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="main-image d-flex align-items-center justify-content-center bg-light">
                            <i class="bi bi-image fa-2x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bilgiler -->
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <?php if ($product['brand_name']): ?>
                        <a href="<?php echo BASE_URL; ?>/marka/<?php echo $product['brand_slug']; ?>" class="product-brand">
                            <?php if ($product['brand_logo']): ?>
                                <img src="/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($product['brand_logo']); ?>"
                                    alt="<?php echo htmlspecialchars($product['brand_name']); ?>">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($product['brand_name']); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($product['short_description']): ?>
                        <p class="lead text-muted mb-4"><?php echo htmlspecialchars($product['short_description']); ?></p>
                    <?php endif; ?>

                    <div class="price-container">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="current-price"><?php echo number_format($product['sale_price'], 2); ?> TL</span>
                            <span class="original-price"><?php echo number_format($product['price'], 2); ?> TL</span>
                            <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                            <span class="discount-badge">-%<?php echo $discount; ?></span>
                        <?php else: ?>
                            <span class="current-price"><?php echo number_format($product['price'], 2); ?> TL</span>
                        <?php endif; ?>
                    </div>

                    <div class="features">
                        <h5><i class="bi bi-check-circle"></i> Ürün Özellikleri</h5>
                        <ul class="feature-list">
                            <li class="feature-item">Orijinal ve kaliteli üretim</li>
                            <li class="feature-item">2 yıl üretici garantisi</li>
                            <li class="feature-item">Hızlı kargo (1-2 iş günü)</li>
                            <li class="feature-item">Teknik destek hizmeti</li>
                        </ul>
                    </div>

                    <!-- İletişim Bölümü -->
                    <div class="contact-section">
                        <h4 class="mb-3">
                            <i class="bi bi-headset me-2"></i>Destek ve Satış
                        </h4>
                        <p class="mb-0">Ürün hakkında detaylı bilgi almak veya hemen satın almak için bizimle iletişime geçin.</p>

                        <div class="contact-buttons">
                            <!-- Telefon Button - ID: 1 -->
                            <?php if (isset($contactCardsById[1])): ?>
                                <a href="<?php echo $contactCardsById[1]['contact_link'] ?: 'tel:' . CONTACT_PHONE; ?>" class="contact-btn contact-btn-primary">
                                    <i class="<?php echo $contactCardsById[1]['icon'] ?: 'bi bi-telephone-fill'; ?>" style="color: <?php echo $contactCardsById[1]['icon_color'] ?: ''; ?>;"></i>
                                    <?php echo $contactCardsById[1]['button_text'] ?: 'Hemen Ara'; ?>
                                </a>
                            <?php else: ?>
                                <a href="tel:<?php echo CONTACT_PHONE; ?>" class="contact-btn contact-btn-primary">
                                    <i class="bi bi-telephone-fill"></i>
                                    Hemen Ara
                                </a>
                            <?php endif; ?>
                            
                            <!-- E-posta Button - ID: 2 -->
                            <?php if (isset($contactCardsById[2])): ?>
                                <a href="<?php echo $contactCardsById[2]['contact_link'] ?: 'mailto:' . SMTP_FROM_EMAIL . '?subject=' . urlencode($product['name'] . ' - Bilgi Talebi') . '&body=' . urlencode('Merhaba, ' . $product['name'] . ' ürünü hakkında bilgi almak istiyorum.'); ?>" 
                                   class="contact-btn contact-btn-secondary">
                                    <i class="<?php echo $contactCardsById[2]['icon'] ?: 'bi bi-envelope'; ?>" style="color: <?php echo $contactCardsById[2]['icon_color'] ?: ''; ?>;"></i>
                                    <?php echo $contactCardsById[2]['button_text'] ?: 'E-posta'; ?>
                                </a>
                            <?php else: ?>
                                <a href="mailto:<?php echo SMTP_FROM_EMAIL; ?>?subject=<?php echo urlencode($product['name'] . ' - Bilgi Talebi'); ?>&body=<?php echo urlencode('Merhaba, ' . $product['name'] . ' ürünü hakkında bilgi almak istiyorum.'); ?>"
                                   class="contact-btn contact-btn-secondary">
                                    <i class="bi bi-envelope"></i>
                                    E-posta
                                </a>
                            <?php endif; ?>
                            
                            <!-- WhatsApp Button - ID: 3 -->
                            <?php if (isset($contactCardsById[3])): ?>
                                <a href="<?php echo $contactCardsById[3]['contact_link'] ?: 'https://wa.me/' . preg_replace('/\D/', '', CONTACT_WHATSAPP) . '?text=' . urlencode("Merhaba, " . $product['name'] . " ürünü hakkında bilgi almak istiyorum."); ?>" 
                                   class="contact-btn contact-btn-secondary" target="_blank">
                                    <i class="<?php echo $contactCardsById[3]['icon'] ?: 'bi bi-whatsapp'; ?>" style="color: <?php echo $contactCardsById[3]['icon_color'] ?: ''; ?>;"></i>
                                    <?php echo $contactCardsById[3]['button_text'] ?: 'WhatsApp'; ?>
                                </a>
                            <?php else: ?>
                                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', CONTACT_WHATSAPP); ?>?text=<?php echo urlencode("Merhaba, " . $product['name'] . " ürünü hakkında bilgi almak istiyorum."); ?>"
                                   class="contact-btn contact-btn-secondary" target="_blank">
                                    <i class="bi bi-whatsapp"></i>
                                    WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Açıklama ve Özellikler -->
            <div class="description-section">
                <h2 class="description-title"><i class="bi bi-info-circle"></i> Ürün Açıklaması</h2>
                <div class="product-description">
                    <?php echo $product['description']; ?>
                </div>

                <div class="specs-card">
                    <h3><i class="bi bi-gear-wide-connected"></i> Teknik Özellikler</h3>
                    <table class="spec-table">
                        <?php if ($product['sku']): ?>
                            <tr>
                                <td>Ürün Kodu</td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($product['weight']): ?>
                            <tr>
                                <td>Ağırlık</td>
                                <td><?php echo $product['weight']; ?> kg</td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Marka</td>
                            <td><?php echo htmlspecialchars($product['brand_name'] ?: 'Belirtilmemiş'); ?></td>
                        </tr>
                        <tr>
                            <td>Kategori</td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?: 'Genel'); ?></td>
                        </tr>
                        <tr>
                            <td>Stok Durumu</td>
                            <td>
                                <span class="stock-badge stock-<?php echo $product['stock_quantity'] > 0 ? 'in' : 'out'; ?>">
                                    <?php echo $product['stock_quantity'] > 0 ? 'Stokta Var' : 'Tedarik Süreli'; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- İlgili Ürünler -->
            <?php if (!empty($relatedProducts)): ?>
                <div class="container related-products">
                    <h3 class="text-center mb-5">
                        <i class="bi bi-boxes me-2"></i>İlgili Ürünler
                    </h3>
                    <div class="row">
                        <?php foreach ($relatedProducts as $relatedProduct): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <a href="<?php echo BASE_URL; ?>/urun/<?php echo htmlspecialchars($relatedProduct['slug']); ?>" class="related-product-card">
                                    <?php if ($relatedProduct['primary_image']): ?>
                                        <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($relatedProduct['primary_image']); ?>"
                                            alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                            class="related-product-image">
                                    <?php else: ?>
                                        <div class="related-product-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="related-product-info">
                                        <h5 class="related-product-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></h5>
                                        <?php if ($relatedProduct['brand_name']): ?>
                                            <p class="text-muted mb-2">
                                                <small><i class="bi bi-award me-1"></i><?php echo htmlspecialchars($relatedProduct['brand_name']); ?></small>
                                            </p>
                                        <?php endif; ?>
                                        <div class="related-product-price">
                                            <?php if ($relatedProduct['sale_price'] && $relatedProduct['sale_price'] < $relatedProduct['price']): ?>
                                                <span class="text-muted text-decoration-line-through me-2">
                                                    <?php echo number_format($relatedProduct['price'], 2); ?> TL
                                                </span>
                                            <?php endif; ?>
                                            <?php echo number_format($relatedProduct['sale_price'] ?: $relatedProduct['price'], 2); ?> TL
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function changeImage(thumb) {
        const main = document.getElementById('mainImage');
        main.src = thumb.src;
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
    }
</script>

<script>
    function changeMainImage(imageSrc, thumbnail) {
        document.getElementById('mainProductImage').src = imageSrc;

        // Thumbnail aktifliğini değiştir
        document.querySelectorAll('.product-thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }

    // Resim zoom özelliği
    document.getElementById('mainProductImage').addEventListener('click', function() {
        // Modal veya lightbox açabilirsiniz
        const modal = document.createElement('div');
        modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.9); z-index: 9999; display: flex; 
        align-items: center; justify-content: center; cursor: zoom-out;
    `;

        const img = document.createElement('img');
        img.src = this.src;
        img.style.cssText = 'max-width: 90%; max-height: 90%; object-fit: contain;';

        modal.appendChild(img);
        document.body.appendChild(modal);

        modal.addEventListener('click', function() {
            document.body.removeChild(modal);
        });
    });

    // Smooth scroll for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>

<?php
// JSON-LD structured data for SEO
$jsonLD = [
    "@context" => "https://schema.org",
    "@type" => "Product",
    "name" => $product['name'],
    "description" => $product['short_description'] ?: strip_tags($product['description']),
    "sku" => $product['sku'],
    "mpn" => $product['sku'], // Manufacturer Part Number
    "brand" => [
        "@type" => "Brand",
        "name" => $product['brand_name'] ?: SITE_NAME
    ],
    "offers" => [
        "@type" => "Offer",
        "price" => (float)($product['sale_price'] ?: $product['price']),
        "priceCurrency" => "TRY",
        "availability" => $product['stock_quantity'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "priceValidUntil" => date('Y-m-d', strtotime('+1 year')),
        "seller" => [
            "@type" => "Organization",
            "name" => COMPANY_NAME,
            "url" => BASE_URL
        ],
        "url" => BASE_URL . '/urun/' . $product['slug']
    ],
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => "4.8",
        "reviewCount" => "47"
    ],
    "image" => []
];

if (!empty($productImages)) {
    foreach ($productImages as $image) {
        $jsonLD["image"][] = BASE_URL . '/' . $image['image_path'];
    }
}

echo '<script type="application/ld+json">' . json_encode($jsonLD, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

// Footer include
include 'includes/footer.php';
?>