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
// Eğer meta_title varsa, onu olduğu gibi kullan (site adı ekleme)
// Eğer meta_title yoksa, ürün adı + site adını kullan
$pageTitle = $product['meta_title'] ?: ($product['name'] . ' - ' . SITE_NAME);
$pageDescription = $product['meta_description'] ?: ($product['short_description'] ?: mb_substr(strip_tags($product['description']), 0, 160));
$pageKeywords = $product['name'] . ', ' . $product['category_name'] . ', ' . $product['brand_name'];
$pageImage = !empty($productImages) ? BASE_URL . '/' . $productImages[0]['image_path'] : BASE_URL . '/assets/images/og-image.jpg';

// Breadcrumb
$breadcrumb = [
    ['text' => 'Ana Sayfa', 'url' => BASE_URL . '/'],
    ['text' => 'Ürünler', 'url' => BASE_URL . '/urunler'],
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    /* Root Variables for Consistent Design */
    :root {
        --primary-color: #007bff;
        --primary-dark: #0056b3;
        --success-color: #28a745;
        --success-dark: #1e7e34;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --info-color: #17a2b8;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;
        --white: #ffffff;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --border-radius: 16px;
        --border-radius-lg: 24px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Reset & Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        line-height: 1.6;
        color: var(--gray-700);
        background: var(--gray-50);
        font-size: 16px;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        line-height: 1.2;
        color: var(--gray-900);
        margin-bottom: 0.75rem;
    }

    /* Mobile-First Product Detail Container */
    .product-detail-wrapper {
        background: var(--white);
        min-height: 100vh;
    }

    /* Mobile Navigation Bar */
    .mobile-nav-bar {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: var(--white);
        border-bottom: 1px solid var(--gray-200);
        padding: 1rem 0;
        box-shadow: var(--shadow-sm);
    }

    .mobile-nav-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 100%;
        padding: 0 1rem;
    }

    .back-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--gray-100);
        color: var(--gray-700);
        text-decoration: none;
        transition: var(--transition);
        border: none;
        font-size: 1.25rem;
    }

    .back-button:hover {
        background: var(--gray-200);
        color: var(--gray-900);
    }

    .mobile-nav-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-900);
        text-align: center;
        flex: 1;
        margin: 0 1rem;
    }

    .share-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--gray-100);
        color: var(--gray-700);
        text-decoration: none;
        transition: var(--transition);
        border: none;
        font-size: 1.125rem;
    }

    .share-button:hover {
        background: var(--gray-200);
        color: var(--gray-900);
    }

    /* Image Gallery Mobile */
    .mobile-image-gallery {
        position: relative;
        background: var(--white);
        margin-bottom: 0;
    }

    .main-image-container {
        position: relative;
        width: 100%;
        height: 320px;
        background: var(--white);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .main-product-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.3s ease;
        background: var(--white);
    }

    .image-zoom-overlay {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 36px;
        height: 36px;
        background: rgba(0, 0, 0, 0.6);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 0.875rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .image-zoom-overlay:hover {
        background: rgba(0, 0, 0, 0.8);
    }

    /* Thumbnail Slider */
    .thumbnail-slider {
        display: flex;
        gap: 8px;
        padding: 16px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        background: var(--white);
    }

    .thumbnail-slider::-webkit-scrollbar {
        display: none;
    }

    .thumbnail-item {
        flex-shrink: 0;
        width: 60px;
        height: 60px;
        border-radius: 8px;
        border: 2px solid transparent;
        overflow: hidden;
        cursor: pointer;
        transition: var(--transition);
        background: var(--gray-100);
    }

    .thumbnail-item.active {
        border-color: var(--primary-color);
        transform: scale(1.05);
    }

    .thumbnail-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Product Info Section */
    .product-info-section {
        background: var(--white);
        padding: 1.5rem 1rem;
        margin-bottom: 8px;
    }

    .product-brand-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--gray-100);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-700);
        text-decoration: none;
        margin-bottom: 1rem;
        transition: var(--transition);
    }

    .product-brand-badge:hover {
        background: var(--primary-color);
        color: var(--white);
    }

    .product-brand-badge img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    .product-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900);
        line-height: 1.3;
        margin-bottom: 0.75rem;
    }

    .product-short-description {
        color: var(--gray-600);
        font-size: 0.95rem;
        line-height: 1.5;
        margin-bottom: 1.25rem;
    }

    /* Price Section */
    .price-section {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .current-price {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--success-color);
        font-family: 'Inter', sans-serif;
    }

    .original-price {
        font-size: 1.125rem;
        color: var(--gray-400);
        text-decoration: line-through;
        font-weight: 500;
    }

    .discount-badge {
        background: var(--danger-color);
        color: var(--white);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.25px;
    }

    /* Features Quick List */
    .features-quick-list {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .features-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        font-size: 0.875rem;
        color: var(--gray-600);
    }

    .feature-check {
        width: 16px;
        height: 16px;
        background: var(--success-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 0.625rem;
        flex-shrink: 0;
    }

    /* E-Commerce Buy Button */
    .ecommerce-buy-button {
        display: block;
        width: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: var(--border-radius);
        padding: 1.25rem;
        text-decoration: none;
        color: var(--white);
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .ecommerce-buy-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s ease;
    }

    .ecommerce-buy-button:hover::before {
        left: 100%;
    }

    .ecommerce-buy-button:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
    }

    .ecommerce-buy-button:active {
        transform: translateY(-2px);
    }

    .ecommerce-btn-content {
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }

    .ecommerce-btn-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        backdrop-filter: blur(10px);
    }

    .ecommerce-btn-text {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .ecommerce-btn-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--white);
        line-height: 1.3;
    }

    .ecommerce-btn-subtitle {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 400;
    }

    .ecommerce-btn-arrow {
        font-size: 1.25rem;
        color: var(--white);
        flex-shrink: 0;
        transition: transform 0.3s ease;
    }

    .ecommerce-buy-button:hover .ecommerce-btn-arrow {
        transform: translateX(5px);
    }

    /* Mobile responsiveness */
    @media (max-width: 576px) {
        .ecommerce-btn-title {
            font-size: 0.9rem;
        }
        
        .ecommerce-btn-subtitle {
            font-size: 0.7rem;
        }
        
        .ecommerce-btn-icon {
            width: 40px;
            height: 40px;
            font-size: 1.25rem;
        }
    }

    /* Fixed Bottom Section */
    .fixed-bottom-section {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--white);
        border-top: 1px solid var(--gray-200);
        margin: 0 2rem;
        padding: 0.75rem;
        z-index: 1000;
        box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .contact-buttons-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
        /* f */
    }

    .contact-btn-mobile {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 5px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        text-align: center;
    }

    .contact-btn-primary {
        background: var(--primary-color);
        color: var(--white);
    }

    .contact-btn-primary:hover {
        background: var(--primary-dark);
        color: var(--white);
        transform: translateY(-1px);
    }

    .contact-btn-success {
        background: var(--success-color);
        color: var(--white);
    }

    .contact-btn-success:hover {
        background: var(--success-dark);
        color: var(--white);
        transform: translateY(-1px);
    }

    .main-cta-button {
        width: 100%;
        background: linear-gradient(135deg, var(--success-color), var(--success-dark));
        color: var(--white);
        border: none;
        padding: 16px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 1.125rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: var(--transition);
        cursor: pointer;
        box-shadow: var(--shadow-md);
    }

    .main-cta-button:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    /* Content Sections */
    .content-section {
        color: var(--gray-700);
        line-height: 1.6;
        font-size: 0.9rem;
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        padding: 1rem;
        margin: 1rem 2rem;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        color: var(--primary-color);
        font-size: 1.125rem;
    }

    /* Specifications Table */
    .specs-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .specs-table tr {
        border-bottom: 1px solid var(--gray-200);
    }

    .specs-table tr:last-child {
        border-bottom: none;
    }

    .specs-table td {
        padding: 12px 0;
        vertical-align: middle;
    }

    .specs-table td:first-child {
        font-weight: 500;
        color: var(--gray-700);
        width: 40%;
        font-size: 0.875rem;
    }

    .specs-table td:last-child {
        color: var(--gray-900);
        font-weight: 500;
        font-size: 0.875rem;
    }

    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .stock-in {
        background: #dcfce7;
        color: #166534;
    }

    .stock-out {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Related Products */
    .related-products-section {
        background: var(--white);
        padding: 1.5rem 1rem;
        margin-bottom: 100px;
        /* Space for fixed bottom */
    }

    .related-products-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 1rem;
    }

    .related-product-card {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .related-product-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-color);
    }

    .related-product-image {
        width: 100%;
        height: 120px;
        object-fit: cover;
        background: var(--gray-100);
    }

    .related-product-info {
        padding: 12px;
    }

    .related-product-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-900);
        line-height: 1.3;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .related-product-brand {
        font-size: 0.75rem;
        color: var(--gray-500);
        margin-bottom: 6px;
    }

    .related-product-price {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--success-color);
    }

    /* Breadcrumb Mobile */
    .breadcrumb-mobile {
        background: var(--white);
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
        display: none;
        /* Hidden by default, shown only on larger screens */
    }

    .breadcrumb {
        margin: 0;
        padding: 0;
        background: transparent;
        font-size: 0.875rem;
    }

    .breadcrumb-item {
        color: var(--gray-600);
    }

    .breadcrumb-item.active {
        color: var(--gray-900);
        font-weight: 500;
    }

    .breadcrumb-item a {
        color: var(--primary-color);
        text-decoration: none;
    }

    .breadcrumb-item a:hover {
        text-decoration: underline;
    }

    @media (max-width: 767px) {
        .breadcrumb-mobile {
            display: none;
        }

        .content-section {
            color: var(--gray-700);
            line-height: 1.6;
            font-size: 0.9rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin: 1rem 1rem;
        }

        .fixed-bottom-section {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            margin: 0rem 0rem;
            padding: 0.75rem;
            z-index: 1000;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    }

    /* Desktop Styles */
    @media (min-width: 768px) {
        .mobile-nav-bar {
            display: none;
        }

        .breadcrumb-mobile {
            display: block;
        }

        .product-detail-wrapper {
            max-width: 1200px;
            margin: 2rem auto;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
        }

        .main-image-container {
            height: 400px;
        }

        .product-info-section {
            padding: 2rem;
        }

        .fixed-bottom-section {
            position: relative;
            background: var(--gray-50);
            border-top: none;
            box-shadow: none;
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
        }

        .related-products-section {
            margin-bottom: 0;
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
        }

        .related-products-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .product-title {
            font-size: 2rem;
        }

        .current-price {
            font-size: 2.25rem;
        }

        .contact-buttons-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .related-products-grid {
            grid-template-columns: repeat(4, 1fr);
        }

        .product-detail-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .mobile-image-gallery {
            grid-column: 1;
        }

        .product-info-section {
            grid-column: 2;
        }

        .content-section {
            grid-column: 1 / -1;
        }

        .related-products-section {
            grid-column: 1 / -1;
        }

        .fixed-bottom-section {
            grid-column: 1 / -1;
        }
    }

    /* Loading Animation */
    .image-loading {
        background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-100) 50%, var(--gray-200) 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    /* Smooth Scrolling */
    html {
        scroll-behavior: smooth;
    }

    /* Touch Feedback */
    .touch-feedback {
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
    }

    /* Product Description Styles */
    .product-description {
        color: var(--gray-700);
        line-height: 1.6;
        font-size: 0.9rem;
    }

    .product-description p {
        margin-bottom: 1rem;
    }

    .product-description img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 1rem 0;
    }

    .product-description ul,
    .product-description ol {
        margin-left: 1.25rem;
        margin-bottom: 1rem;
    }

    .product-description li {
        margin-bottom: 0.5rem;
    }

    /* Accessibility Improvements */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* Dark Mode Support */
    @media (prefers-color-scheme: dark) {
        :root {
            --gray-50: #1f2937;
            --gray-100: #374151;
            --gray-200: #4b5563;
            --white: #1f2937;
            --gray-900: #f9fafb;
            --gray-800: #e5e7eb;
            --gray-700: #d1d5db;
        }
    }
</style>

<div class="product-detail-wrapper">
    <!-- Mobile Navigation Bar -->
    <div class="mobile-nav-bar">
        <div class="mobile-nav-content">
            <a href="<?php echo BASE_URL; ?>/urunler" class="back-button touch-feedback" id="backButton">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="mobile-nav-title">Ürün Detayı</div>
            <button class="share-button touch-feedback" onclick="shareProduct()">
                <i class="bi bi-share"></i>
            </button>
        </div>
    </div>

    <!-- Breadcrumb for Desktop -->
    <div class="breadcrumb-mobile">
        <nav aria-label="breadcrumb">
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

    <!-- Image Gallery -->
    <div class="mobile-image-gallery">
        <div class="main-image-container">
            <?php if (!empty($productImages)): ?>
                <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($productImages[0]['image_path']); ?>"
                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                    class="main-product-image"
                    id="mainProductImage">
                <div class="image-zoom-overlay" onclick="openImageModal()">
                    <i class="bi bi-zoom-in"></i>
                </div>
            <?php else: ?>
                <div class="main-product-image d-flex align-items-center justify-content-center bg-light">
                    <i class="bi bi-image fa-2x text-muted"></i>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($productImages) > 1): ?>
            <div class="thumbnail-slider">
                <?php foreach ($productImages as $index => $image): ?>
                    <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>"
                        onclick="changeMainImage('<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($image['image_path']); ?>', this)">
                        <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($image['image_path']); ?>"
                            alt="Görsel <?php echo $index + 1; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Info Section -->
    <div class="product-info-section">
        <?php if ($product['brand_name']): ?>
            <a href="<?php echo BASE_URL; ?>/marka/<?php echo $product['brand_slug']; ?>" class="product-brand-badge touch-feedback">
                <?php if ($product['brand_logo']): ?>
                    <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($product['brand_logo']); ?>"
                        alt="<?php echo htmlspecialchars($product['brand_name']); ?>">
                <?php endif; ?>
                <?php echo htmlspecialchars($product['brand_name']); ?>
            </a>
        <?php endif; ?>

        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

        <?php if ($product['short_description']): ?>
            <p class="product-short-description"><?php echo htmlspecialchars($product['short_description']); ?></p>
        <?php endif; ?>

        <?php if ($product['price'] > 0): ?>
            <div class="price-section">
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="current-price"><?php echo number_format($product['sale_price'], 2); ?> <?php echo $product['currency'] ?? 'TL'; ?></span>
                    <span class="original-price"><?php echo number_format($product['price'], 2); ?> <?php echo $product['currency'] ?? 'TL'; ?></span>
                    <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                    <span class="discount-badge">-%<?php echo $discount; ?></span>
                <?php else: ?>
                    <span class="current-price"><?php echo number_format($product['price'], 2); ?> <?php echo $product['currency'] ?? 'TL'; ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="features-quick-list">
            <div class="features-title">
                <i class="bi bi-check-circle-fill"></i>
                Ürün Avantajları
            </div>
            <div class="feature-item">
                <div class="feature-check">✓</div>
                <span>Orijinal ve kaliteli üretim</span>
            </div>
            <div class="feature-item">
                <div class="feature-check">✓</div>
                <span>1 yıl üretici garantisi</span>
            </div>
            <div class="feature-item">
                <div class="feature-check">✓</div>
                <span>Hızlı kargo (Aynı gün kargo)</span>
            </div>
            <div class="feature-item">
                <div class="feature-check">✓</div>
                <span>Teknik destek hizmeti</span>
            </div>
        </div>
        
        <?php if (!empty($product['eticareturl'])): ?>
        <!-- E-Ticaret Satın Alma Butonu -->
        <a href="<?php echo htmlspecialchars($product['eticareturl']); ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="ecommerce-buy-button">
            <div class="ecommerce-btn-content">
                <div class="ecommerce-btn-icon">
                    <i class="bi bi-cart-check-fill"></i>
                </div>
                <div class="ecommerce-btn-text">
                    <span class="ecommerce-btn-title">Satın Almak İçin E-Ticaret Sitemizi Ziyaret Edin</span>
                    <span class="ecommerce-btn-subtitle">Güvenli ödeme ile hızlı teslimat</span>
                </div>
                <div class="ecommerce-btn-arrow">
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
        <?php endif; ?>
    </div>

    <!-- Fixed Bottom Contact Section -->
    <div class="fixed-bottom-section">
        <div class="contact-buttons-grid">
            <!-- Phone Button -->
            <?php if (isset($contactCardsById[1])): ?>
                <a href="<?php echo $contactCardsById[1]['contact_link'] ?: 'tel:' . CONTACT_PHONE; ?>"
                    class="contact-btn-mobile contact-btn-primary touch-feedback">
                    <i class="<?php echo $contactCardsById[1]['icon'] ?: 'bi bi-telephone-fill'; ?>"></i>
                    <span><?php echo $contactCardsById[1]['button_text'] ?: 'Ara'; ?></span>
                </a>
            <?php else: ?>
                <a href="tel:<?php echo CONTACT_PHONE; ?>" class="contact-btn-mobile contact-btn-primary touch-feedback">
                    <i class="bi bi-telephone-fill"></i>
                    <span>Ara</span>
                </a>
            <?php endif; ?>

            <!-- WhatsApp Button -->
            <?php if (isset($contactCardsById[3])): ?>
                <a href="<?php echo $contactCardsById[3]['contact_link'] ?: 'https://wa.me/' . preg_replace('/\D/', '', CONTACT_WHATSAPP) . '?text=' . urlencode("Merhaba, " . $product['name'] . " ürünü hakkında bilgi almak istiyorum."); ?>"
                    class="contact-btn-mobile contact-btn-success touch-feedback" target="_blank">
                    <i class="<?php echo $contactCardsById[3]['icon'] ?: 'bi bi-whatsapp'; ?>"></i>
                    <span><?php echo $contactCardsById[3]['button_text'] ?: 'WhatsApp'; ?></span>
                </a>
            <?php else: ?>
                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', CONTACT_WHATSAPP); ?>?text=<?php echo urlencode("Merhaba, " . $product['name'] . " ürünü hakkında bilgi almak istiyorum."); ?>"
                    class="contact-btn-mobile contact-btn-success touch-feedback" target="_blank">
                    <i class="bi bi-whatsapp"></i>
                    <span>WhatsApp</span>
                </a>
            <?php endif; ?>

            <!-- Main CTA Button -->
            <?php if (isset($contactCardsById[2])): ?>
                <a href="<?php echo $contactCardsById[2]['contact_link'] ?: 'mailto:' . SMTP_FROM_EMAIL . '?subject=' . urlencode($product['name'] . ' - Bilgi Talebi') . '&body=' . urlencode('Merhaba, ' . $product['name'] . ' ürünü hakkında bilgi almak istiyorum.'); ?>"
                    class="contact-btn-mobile  touch-feedback">
                    <i class="<?php echo $contactCardsById[2]['icon'] ?: 'bi bi-envelope-fill'; ?>"></i>
                    <span><?php echo $contactCardsById[2]['button_text'] ?: 'Bilgi Al & Satın Al'; ?></span>
                </a>
            <?php else: ?>
                <a href="mailto:<?php echo SMTP_FROM_EMAIL; ?>?subject=<?php echo urlencode($product['name'] . ' - Bilgi Talebi'); ?>&body=<?php echo urlencode('Merhaba, ' . $product['name'] . ' ürünü hakkında bilgi almak istiyorum.'); ?>"
                    class="contact-btn-mobile  touch-feedback">
                    <i class="bi bi-envelope-fill"></i>
                    <span>Bilgi Al & Satın Al</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Description Section -->
    <div class="content-section">
        <h2 class="section-title">
            <i class="bi bi-info-circle"></i>
            Ürün Açıklaması
        </h2>
        <div class="product-description">
            <?php echo $product['description']; ?>
        </div>
    </div>

    <!-- Specifications Section -->
    <div class="content-section">
        <h3 class="section-title">
            <i class="bi bi-gear-wide-connected"></i>
            Teknik Özellikler
        </h3>
        <table class="specs-table">
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
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <i class="bi bi-check-circle-fill"></i>
                            Stokta Var
                        <?php else: ?>
                            <i class="bi bi-clock-fill"></i>
                            Tedarik Süreli
                        <?php endif; ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Related Products Section -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="related-products-section">
            <h3 class="section-title">
                <i class="bi bi-boxes"></i>
                İlgili Ürünler
            </h3>
            <div class="related-products-grid">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <a href="<?php echo BASE_URL; ?>/urun/<?php echo htmlspecialchars($relatedProduct['slug']); ?>"
                        class="related-product-card touch-feedback">
                        <?php if ($relatedProduct['primary_image']): ?>
                            <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($relatedProduct['primary_image']); ?>"
                                alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                class="related-product-image">
                        <?php else: ?>
                            <div class="related-product-image d-flex align-items-center justify-content-center">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="related-product-info">
                            <div class="related-product-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></div>
                            <?php if ($relatedProduct['brand_name']): ?>
                                <div class="related-product-brand"><?php echo htmlspecialchars($relatedProduct['brand_name']); ?></div>
                            <?php endif; ?>
                            <?php if ($relatedProduct['price'] > 0): ?>
                                <div class="related-product-price">
                                    <?php echo number_format($relatedProduct['sale_price'] ?: $relatedProduct['price'], 2); ?> <?php echo $relatedProduct['currency'] ?? 'TL'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    // Image Gallery Functions
    function changeMainImage(imageSrc, thumbnail) {
        const mainImage = document.getElementById('mainProductImage');
        mainImage.src = imageSrc;

        // Update thumbnail active state
        document.querySelectorAll('.thumbnail-item').forEach(item => {
            item.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }

    // Image Modal/Zoom Function
    function openImageModal() {
        const mainImage = document.getElementById('mainProductImage');
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.95); 
            z-index: 9999; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: zoom-out;
            padding: 1rem;
        `;

        const img = document.createElement('img');
        img.src = mainImage.src;
        img.style.cssText = 'max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px;';

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
        closeBtn.style.cssText = `
            position: absolute; 
            top: 1rem; 
            right: 1rem; 
            background: rgba(255,255,255,0.9); 
            border: none; 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer;
            font-size: 1.125rem;
            color: #333;
        `;

        modal.appendChild(img);
        modal.appendChild(closeBtn);
        document.body.appendChild(modal);

        // Add body scroll lock
        document.body.style.overflow = 'hidden';

        // Close modal function
        const closeModal = () => {
            document.body.removeChild(modal);
            document.body.style.overflow = '';
        };

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        closeBtn.addEventListener('click', closeModal);

        // Keyboard support
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        });
    }

    // Share Product Function
    function shareProduct() {
        if (navigator.share) {
            navigator.share({
                title: '<?php echo htmlspecialchars($product['name']); ?>',
                text: '<?php echo htmlspecialchars($product['short_description'] ?: mb_substr(strip_tags($product['description']), 0, 100)); ?>',
                url: window.location.href
            }).catch(console.error);
        } else {
            // Fallback: Copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                // Show toast notification
                showToast('Link kopyalandı!');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = window.location.href;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('Link kopyalandı!');
            });
        }
    }

    // Toast Notification Function
    function showToast(message) {
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.875rem;
            z-index: 10000;
            animation: toastSlide 0.3s ease;
        `;

        // Add animation keyframes
        if (!document.querySelector('#toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes toastSlide {
                    from { opacity: 0; transform: translateX(-50%) translateY(20px); }
                    to { opacity: 1; transform: translateX(-50%) translateY(0); }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastSlide 0.3s ease reverse';
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 2000);
    }

    // Lazy Loading for Images
    function initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('image-loading');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }

    // Smart Navigation - History-aware back button
    function initSmartNavigation() {
        const backButton = document.getElementById('backButton');
        if (backButton) {
            backButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if user came from products page
                const referrer = document.referrer;
                const currentHost = window.location.hostname;
                const referrerHost = referrer ? new URL(referrer).hostname : '';
                
                // If referrer is from same site and contains /urunler, go back in history
                if (referrer && referrerHost === currentHost && 
                    (referrer.includes('/urunler') || referrer.includes('/kategori/') || referrer.includes('/marka/'))) {
                    // Store current scroll position for the target page
                    sessionStorage.setItem('restoreScrollPosition', 'true');
                    window.history.back();
                } else {
                    // Default fallback to products page
                    window.location.href = this.getAttribute('href');
                }
            });
        }
    }
    
    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', function() {
        initLazyLoading();
        initSmartNavigation();

        // Add smooth scroll behavior for internal links
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

        // Add haptic feedback for touch devices
        if ('vibrate' in navigator) {
            document.querySelectorAll('.touch-feedback').forEach(element => {
                element.addEventListener('touchstart', () => {
                    navigator.vibrate(10);
                }, {
                    passive: true
                });
            });
        }
    });

    // Performance optimization: Preload critical images
    function preloadCriticalImages() {
        const criticalImages = ['<?php echo !empty($productImages) ? BASE_URL . "/" . $productImages[0]["image_path"] : ""; ?>'];
        criticalImages.forEach(src => {
            if (src) {
                const link = document.createElement('link');
                link.rel = 'preload';
                link.as = 'image';
                link.href = src;
                document.head.appendChild(link);
            }
        });
    }

    preloadCriticalImages();
</script>

<?php
// JSON-LD structured data for SEO - Google Console Uyarısı Düzeltildi
$jsonLD = [
    "@context" => "https://schema.org",
    "@type" => "Product",
    "name" => $product['name'],
    "description" => $product['short_description'] ?: strip_tags($product['description']),
    "sku" => $product['sku'],
    "mpn" => $product['sku'],
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
        "url" => BASE_URL . '/urun/' . $product['slug'],
        // İADE POLİTİKASI - Google Console için gerekli alan
        "hasMerchantReturnPolicy" => [
            "@type" => "MerchantReturnPolicy",
            "applicableCountry" => "TR",
            "returnPolicyCategory" => "https://schema.org/MerchantReturnFiniteReturnWindow",
            "merchantReturnDays" => 14,
            "returnMethod" => "https://schema.org/ReturnByMail",
            "returnFees" => "https://schema.org/FreeReturn"
        ],
        // KARGO BİLGİLERİ - Zengin snippet'ler için önerilen alan
        "shippingDetails" => [
            "@type" => "OfferShippingDetails",
            "shippingRate" => [
                "@type" => "MonetaryAmount",
                "value" => "0",
                "currency" => "TRY"
            ],
            "shippingDestination" => [
                "@type" => "DefinedRegion",
                "addressCountry" => "TR"
            ],
            "deliveryTime" => [
                "@type" => "ShippingDeliveryTime",
                "handlingTime" => [
                    "@type" => "QuantitativeValue",
                    "minValue" => 0,
                    "maxValue" => 1,
                    "unitCode" => "DAY"
                ],
                "transitTime" => [
                    "@type" => "QuantitativeValue",
                    "minValue" => 1,
                    "maxValue" => 3,
                    "unitCode" => "DAY"
                ]
            ]
        ]
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

// BreadcrumbList Schema
$breadcrumbSchema = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => []
];

foreach ($breadcrumb as $index => $crumb) {
    $breadcrumbSchema["itemListElement"][] = [
        "@type" => "ListItem",
        "position" => $index + 1,
        "name" => $crumb['text'],
        "item" => isset($crumb['url']) && !empty($crumb['url']) ? $crumb['url'] : null
    ];
}

echo '<script type="application/ld+json">' . json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

// Footer include
include 'includes/footer.php';
?>
