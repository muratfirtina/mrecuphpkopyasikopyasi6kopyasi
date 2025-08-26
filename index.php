<?php
/**
 * Mr ECU - Ana Sayfa (Database Temelli)
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Ana Sayfa';
$pageDescription = 'Profesyonel ECU hizmetleri - Güvenli, hızlı ve kaliteli ECU yazılım çözümleri';
$pageKeywords = 'ECU, chip tuning, ECU yazılım, immobilizer, TCU, motor kontrol ünitesi';

// Design ayarlarını al
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM design_settings WHERE is_active = 1");
    $designSettings = [];
    while ($row = $stmt->fetch()) {
        $designSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $designSettings = [];
}

// Slider verilerini al
try {
    $stmt = $pdo->query("
        SELECT * FROM design_sliders 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    $sliders = $stmt->fetchAll();
    
    // Debug: Slider sayısını logla
    error_log('Index.php - Active sliders found: ' . count($sliders));
    foreach($sliders as $s) {
        error_log('Slider: ' . $s['title'] . ' - Image: ' . $s['background_image']);
    }
} catch (Exception $e) {
    $sliders = [];
    error_log('Index.php - Slider query error: ' . $e->getMessage());
}

// Öne çıkan ürünleri al
try {
    $stmt = $pdo->query("
        SELECT p.*, 
               c.name as category_name, c.slug as category_slug,
               pb.name as brand_name, pb.slug as brand_slug, pb.logo as brand_logo,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.is_active = 1 AND p.featured = 1
        ORDER BY p.sort_order, p.name
        LIMIT " . FEATURED_PRODUCTS_COUNT . "
    ");
    $featuredProducts = $stmt->fetchAll();
} catch (Exception $e) {
    $featuredProducts = [];
    error_log('Index.php - Featured products query error: ' . $e->getMessage());
}

// En yeni ürünleri al
try {
    $stmt = $pdo->query("
        SELECT p.*, 
               c.name as category_name, c.slug as category_slug,
               pb.name as brand_name, pb.slug as brand_slug, pb.logo as brand_logo,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $latestProducts = $stmt->fetchAll();
} catch (Exception $e) {
    $latestProducts = [];
    error_log('Index.php - Latest products query error: ' . $e->getMessage());
}

// Typewriter ayarları
$typewriterEnabled = isset($designSettings['hero_typewriter_enable']) ? (bool)$designSettings['hero_typewriter_enable'] : true;
$typewriterWords = isset($designSettings['hero_typewriter_words']) ? 
    explode(',', $designSettings['hero_typewriter_words']) : 
    ['Optimize Edin', 'Güçlendirin', 'Geliştirin'];
$animationSpeed = isset($designSettings['hero_animation_speed']) ? (int)$designSettings['hero_animation_speed'] : 3000;

// Calculator Typewriter kelimeleri
$calculatorTypewriterWords = ['Optimize Edin', 'Güçlendirin', 'Geliştirin'];

// Header include
include 'includes/header.php';
?>

    <!-- Hero Section Slider -->
    <style>
    /* Carousel Debug CSS */
    .carousel-item {
        display: none !important;
    }
    .carousel-item.active {
        display: block !important;
    }
    .carousel-inner {
        position: relative;
        width: 100%;
        overflow: hidden;
    }
    .hero-slide {
        width: 100% !important;
        height: 750px !important;
    }
    
    /* Hero slider genişletme - yukarıya doğru büyütme */
    .hero-slider {
        margin-top: -150px !important;
        padding-top: 150px !important;
    }
    </style>
    <section class="hero-slider" style="position: relative; min-height: 750px; z-index: 1040;">
        <?php if (!empty($sliders)): ?>
        <!-- DEBUG: Slider verilerini kontrol et -->
        <?php 
        echo "<!-- DEBUG Slider Data: ";
        foreach($sliders as $s) {
            echo "ID: {$s['id']}, Background: {$s['background_image']}, Active: {$s['is_active']} | ";
        }
        echo " -->";
        ?>
        
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="<?php echo $animationSpeed; ?>" style="height: 750px;">
            <!-- Slide Indicators -->
            <!-- <div class="carousel-indicators">
                <?php foreach ($sliders as $index => $slider): ?>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                            class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                <?php endforeach; ?>
            </div> -->

            <!-- Carousel Slides -->
            <div class="carousel-inner" style="height: 730px;">
                <?php foreach ($sliders as $index => $slider): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" style="height: 750px; display: block;">
                    <!-- DEBUG: Resim yolu kontrolü -->
                    <?php 
                    $fullImagePath = __DIR__ . '/' . $slider['background_image'];
                    $imageExists = file_exists($fullImagePath);
                    echo "<!-- DEBUG Slide {$index}: Image={$slider['background_image']}, Exists={$imageExists}, FullPath={$fullImagePath} -->";
                    ?>
                    
                    <div class="hero-slide" style="
                        background: linear-gradient(rgba(44, 62, 80, 0.3), rgba(3, 9, 191, 0.4)), url('/mrecuphpkopyasikopyasi6kopyasi/<?php echo htmlspecialchars($slider['background_image']); ?>') center/cover no-repeat;
                        background-size: cover;
                        background-position: center;
                        height: 750px;
                        min-height: 750px;
                        position: relative;
                        display: flex;
                        align-items: center;
                        width: 100%;
                    ">
                        <!-- Üstten aşağıya gradient karartma overlay -->
                        <div style="
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 30%, rgba(0,0,0,0.1) 60%, rgba(0,0,0,0) 100%);
                            z-index: 1;
                        "></div>
                        <div class="container py-5 h-100" style="position: relative; z-index: 2;">
                            <div class="row align-items-center text-white h-100">
                                <?php if ($index === 0): ?>
                                    <!-- İlk Slider: Standart İçerik -->
                                    <div class="col-lg-8">
                                        <h1 class="display-3 fw-bold mb-3 slide-title"><?php echo htmlspecialchars($slider['title']); ?></h1>
                                        
                                        <?php if ($typewriterEnabled): ?>
                                            <h2 class="display-5 fw-bold mb-4" style="background: linear-gradient(45deg, #e91c1cff, #fd6060ff, #ff5261ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                                <span id="typewriter-text"><?php echo htmlspecialchars($typewriterWords[0]); ?></span><span class="typewriter-cursor">|</span>
                                            </h2>
                                        <?php else: ?>
                                            <h2 class="display-5 fw-bold mb-4" style="color: <?php echo htmlspecialchars($slider['text_color']); ?>;">
                                                <?php echo htmlspecialchars($slider['subtitle']); ?>
                                            </h2>
                                        <?php endif; ?>
                                        
                                        <p class="lead mb-4">
                                            <?php echo htmlspecialchars($slider['description']); ?>
                                        </p>
                                        
                                        <div class="d-flex gap-3 mb-5">
                                            <a href="<?php echo htmlspecialchars($slider['button_link']); ?>" class="btn btn-danger btn-lg px-4">
                                                <i class="fas fa-search me-2"></i><?php echo htmlspecialchars($slider['button_text']); ?>
                                            </a>
                                            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                                <a href="user/upload.php" class="btn btn-outline-light btn-lg px-4">
                                                    <i class="fas fa-upload me-2"></i>Dosya Yükle
                                                </a>
                                            <?php else: ?>
                                                <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                                                    <i class="fas fa-upload me-2"></i>Dosya Yükle
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-center">
                                            <i class="fas fa-microchip" style="font-size: 10rem; opacity: 0.2;"></i>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Diğer Slider'lar: Orijinal İçerik -->
                                    <div class="col-lg-8">
                                        <h1 class="display-3 fw-bold mb-3 slide-title"><?php echo htmlspecialchars($slider['title']); ?></h1>
                                        
                                        <?php if ($index === 0 && $typewriterEnabled): ?>
                                            <h2 class="display-5 fw-bold mb-4" style="background: linear-gradient(45deg, #e91c1cff, #fd6060ff, #ff5261ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                                <span id="typewriter-text"><?php echo htmlspecialchars($typewriterWords[0]); ?></span><span class="typewriter-cursor">|</span>
                                            </h2>
                                        <?php else: ?>
                                            <h2 class="display-5 fw-bold mb-4" style="color: <?php echo htmlspecialchars($slider['text_color']); ?>;">
                                                <?php echo htmlspecialchars($slider['subtitle']); ?>
                                            </h2>
                                        <?php endif; ?>
                                        
                                        <p class="lead mb-4">
                                            <?php echo htmlspecialchars($slider['description']); ?>
                                        </p>
                                        
                                        <div class="d-flex gap-3 mb-5">
                                            <a href="<?php echo htmlspecialchars($slider['button_link']); ?>" class="btn btn-danger btn-lg px-4">
                                                <i class="fas fa-search me-2"></i><?php echo htmlspecialchars($slider['button_text']); ?>
                                            </a>
                                            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                                <a href="user/upload.php" class="btn btn-outline-light btn-lg px-4">
                                                    <i class="fas fa-upload me-2"></i>Dosya Yükle
                                                </a>
                                            <?php else: ?>
                                                <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                                                    <i class="fas fa-upload me-2"></i>Dosya Yükle
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-center">
                                            <?php
                                            // Slider'a göre icon belirleme
                                            $icon = 'fas fa-microchip';
                                            if (strpos(strtolower($slider['title']), 'performans') !== false) {
                                                $icon = 'fas fa-tachometer-alt';
                                            } elseif (strpos(strtolower($slider['title']), 'güvenlik') !== false) {
                                                $icon = 'fas fa-key';
                                            } elseif (strpos(strtolower($slider['title']), 'şanzıman') !== false) {
                                                $icon = 'fas fa-cogs';
                                            } elseif (strpos(strtolower($slider['title']), 'destek') !== false) {
                                                $icon = 'fas fa-headset';
                                            }
                                            ?>
                                            <i class="<?php echo $icon; ?>" style="font-size: 10rem; opacity: 0.2;"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Carousel Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        <?php else: ?>
        <!-- Fallback eğer slider yoksa -->
        <div class="hero-slide" style="background: linear-gradient(rgba(44, 62, 80, 0.8), rgb(3 9 191 / 0.5)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=1080&fit=crop') center/cover; height: 750px; position: relative;">
            <!-- Üstten aşağıya gradient karartma overlay -->
            <div style="
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 30%, rgba(0,0,0,0.1) 60%, rgba(0,0,0,0) 100%);
                z-index: 1;
            "></div>
            <div class="container py-5 h-100" style="position: relative; z-index: 2;">
                <div class="row align-items-center text-white h-100">
                    <div class="col-lg-8">
                        <h1 class="display-3 fw-bold mb-3">Profesyonel ECU Hizmetleri</h1>
                        <h2 class="display-5 fw-bold mb-4">Mr ECU</h2>
                        <p class="lead mb-4">Güvenli, hızlı ve kaliteli ECU yazılım çözümleri</p>
                        <a href="#services" class="btn btn-danger btn-lg px-4">Hizmetlerimizi İnceleyin</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Animated Category Boxes Section -->
    <section class="jet-animated-boxes py-5 bg-gradient-primary">
        <div class="container">
            <?php
            // Tüm aktif kategorileri getir
            try {
                $stmt = $pdo->query("
                    SELECT c.*, COUNT(p.id) as product_count 
                    FROM categories c 
                    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                    WHERE c.is_active = 1 
                    GROUP BY c.id 
                    HAVING product_count > 0
                    ORDER BY c.sort_order, c.name
                ");
                $categories = $stmt->fetchAll();
            } catch (Exception $e) {
                $categories = [];
                error_log('Categories query error: ' . $e->getMessage());
            }
            ?>
            
            <div class="row g-4 justify-content-center" style="margin: 0px 30px;">
                <?php 
                $delay = 0.1;
                foreach ($categories as $category): 
                    // Kategori ikonunu belirleme
                    $icon = 'fas fa-cogs'; // varsayılan ikon
                    $categoryName = strtolower($category['name']);
                    
                    if (strpos($categoryName, 'ecu') !== false || strpos($categoryName, 'motor') !== false) {
                        $icon = 'fas fa-microchip';
                    } elseif (strpos($categoryName, 'immobilizer') !== false || strpos($categoryName, 'anahtar') !== false) {
                        $icon = 'fas fa-key';
                    } elseif (strpos($categoryName, 'tcu') !== false || strpos($categoryName, 'şanzıman') !== false) {
                        $icon = 'fas fa-cogs';
                    } elseif (strpos($categoryName, 'dpf') !== false || strpos($categoryName, 'egr') !== false) {
                        $icon = 'fas fa-filter';
                    } elseif (strpos($categoryName, 'adblue') !== false) {
                        $icon = 'fas fa-tint';
                    } elseif (strpos($categoryName, 'chip') !== false || strpos($categoryName, 'tuning') !== false) {
                        $icon = 'fas fa-tachometer-alt';
                    }
                ?>
                
                <div class="col-lg-3 col-md-6">
                    <div class="jet-box" data-animation="fadeInUp" data-delay="<?php echo $delay; ?>">
                        <div class="flip-card">
                            <div class="flip-card-inner">
                                <!-- Front Side (Kırmızı) -->
                                <div class="flip-card-front">
                                    <div class="jet-box-icon">
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <h4 class="jet-box-title"><?php echo strtoupper(htmlspecialchars($category['name'])); ?></h4>
                                    <h5 class="jet-box-subtitle"><?php echo $category['product_count']; ?> ÜRÜN</h5>
                                </div>
                                <!-- Back Side (Siyah) -->
                                <div class="flip-card-back">
                                    <h4 class="flip-back-title"><?php echo strtoupper(htmlspecialchars($category['name'])); ?></h4>
                                    <h5 class="flip-back-subtitle"><?php echo $category['product_count']; ?> ÜRÜN MEVCUT</h5>
                                    <p class="flip-back-description">
                                        <?php echo !empty($category['description']) 
                                            ? htmlspecialchars(substr($category['description'], 0, 100)) . '...' 
                                            : 'Bu kategorideki tüm ürünleri keşfedin ve ihtiyacınıza uygun çözümü bulun.'; ?>
                                    </p>
                                    <a href="kategori/<?php echo urlencode($category['slug']); ?>" class="flip-back-link">
                                        İnceleyin <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php 
                $delay += 0.1;
                endforeach; 
                ?>

                <!-- Upload Card (4. kart) -->
                <div class="col-lg-3 col-md-6">
                    <div class="jet-box" data-animation="fadeInUp" data-delay="0.4">
                        <div class="flip-card">
                            <div class="flip-card-inner">
                                <!-- Front Side (Kırmızı) -->
                                <div class="flip-card-front">
                                    <div class="jet-box-icon">
                                        <i class="fas fa-upload"></i>
                                    </div>
                                    <h4 class="jet-box-title">DOSYA</h4>
                                    <h5 class="jet-box-subtitle">YÜKLEME</h5>
                                </div>
                                <!-- Back Side (Siyah) -->
                                <div class="flip-card-back">
                                    <h4 class="flip-back-title">DOSYA</h4>
                                    <h5 class="flip-back-subtitle">YÜKLEME SİSTEMİ</h5>
                                    <p class="flip-back-description">
                                        ECU dosyanızı güvenli şekilde yükleyin ve profesyonel işleme için gönderiniz.
                                    </p>
                                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                        <a href="user/upload.php" class="flip-back-link">
                                            Yükleyin <i class="fas fa-cloud-upload-alt"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="register.php" class="flip-back-link">
                                            REGISTER & UPLOAD <i class="fas fa-user-plus"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Neden Bizi Seçmelisiniz?</h2>
                    <p class="lead text-muted">Profesyonel ECU hizmetleri için güvenilir çözümler</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Güvenlik</h5>
                            <p class="card-text">
                                Dosyalarınız SSL şifreleme ile korunur. Sadece sizin erişebileceğiniz 
                                güvenli bir platform.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-clock text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Hızlı İşlem</h5>
                            <p class="card-text">
                                Dosyalarınız uzman ekibimiz tarafından en kısa sürede işlenir ve 
                                hazır hale getirilir.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-users text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Uzman Ekip</h5>
                            <p class="card-text">
                                Alanında uzman teknisyenlerimiz tüm marka ve modeller için 
                                profesyonel hizmet sunar.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Chip Tuning Calculator Form -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Chip Tuning Hesaplayıcı</h2>
                    <p class="lead text-muted">1120+ Marka ve Model ile Performansınızı Hesaplayın</p>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Chip Tuning Calculator Form -->
                    <div class="chip-tuning-calculator bg-primary bg-opacity-10 rounded">
                        <div class="calculator-header text-center mb-4">
                            <h3 class="mb-2 text-primary">1120+ Marka ve Model Hesaplayın</h3>
                            <h2 class="mb-4">Chip Tuning ile <span class="text-danger" id="calculator-typewriter">Optimize Edin</span><span class="typewriter-cursor-calc">|</span></h2>
                        </div>
                        
                        <form id="chipTuningForm" class="tuning-form">
                            <div class="row g-3 justify-content-center">
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <select class="form-select tuning-select" id="brand_select" name="brand_id" required>
                                        <option value="">Marka Seçiniz</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <select class="form-select tuning-select" id="model_select" name="model_id" disabled required>
                                        <option value="">Model Seçiniz</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <select class="form-select tuning-select" id="series_select" name="series_id" disabled required>
                                        <option value="">Seri Seçiniz</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <select class="form-select tuning-select" id="engine_select" name="engine_id" disabled required>
                                        <option value="">Motor Seçiniz</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <button type="submit" class="btn btn-danger btn-lg w-100 tuning-calculate-btn" disabled>
                                        <i class="fas fa-calculator me-2"></i>Hesapla
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Loading Indicator -->
                        <div id="calculatorLoading" class="text-center mt-3" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                            <p class="mt-2 text-primary">Hesaplanıyor...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Hizmetlerimiz</h2>
                    <p class="lead text-muted">Sunduğumuz ECU hizmetleri</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-microchip text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>ECU Yazılımları</h5>
                        <p class="text-muted">Motor kontrol ünitesi yazılım düzenlemeleri</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-cogs text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>TCU Yazılımları</h5>
                        <p class="text-muted">Şanzıman kontrol ünitesi düzenlemeleri</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-key text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Immobilizer</h5>
                        <p class="text-muted">İmmobilizer ve anahtar programlama</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-tachometer-alt text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Chip Tuning</h5>
                        <p class="text-muted">Performans artırma ve optimizasyon</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-primary">1000+</h3>
                        <p class="text-muted">İşlenmiş Dosya</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-primary">500+</h3>
                        <p class="text-muted">Mutlu Müşteri</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-primary">50+</h3>
                        <p class="text-muted">Desteklenen Marka</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-primary">24/7</h3>
                        <p class="text-muted">Teknik Destek</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-5 fw-bold mb-4">Hakkımızda</h2>
                    <p class="lead mb-4">
                        <?php echo SITE_NAME; ?> olarak, otomotiv ECU alanında uzun yıllardır hizmet veren 
                        deneyimli bir ekibiz. Müşterilerimize en kaliteli ve güvenilir hizmeti sunmak 
                        için sürekli kendimizi geliştiriyor ve teknolojik yenilikleri takip ediyoruz.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>10+ yıl deneyim</li>
                        <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>Profesyonel ekip</li>
                        <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>7/24 teknik destek</li>
                        <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>Güncel teknoloji</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="feature-box text-center p-4 bg-primary bg-opacity-10 rounded shadow">
                                <i class="fas fa-microchip text-primary" style="font-size: 4rem;"></i>
                                <h6 class="mt-2 mb-0">ECU</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box text-center p-4 bg-success bg-opacity-10 rounded shadow">
                                <i class="fas fa-memory text-success" style="font-size: 4rem;"></i>
                                <h6 class="mt-2 mb-0">CHIP</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box text-center p-4 bg-danger bg-opacity-10 rounded shadow">
                                <i class="fas fa-tools text-danger" style="font-size: 4rem;"></i>
                                <h6 class="mt-2 mb-0">TOOL</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box text-center p-4 bg-warning bg-opacity-10 rounded shadow">
                                <i class="fas fa-car text-warning" style="font-size: 4rem;"></i>
                                <h6 class="mt-2 mb-0">CAR</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">İletişim</h2>
                    <p class="lead text-muted">Bizimle iletişime geçin</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="contact-info text-center p-4">
                        <i class="fas fa-envelope text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Email</h5>
                        <p class="text-muted"><?php echo SITE_EMAIL; ?></p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-info text-center p-4">
                        <i class="fas fa-phone text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Telefon</h5>
                        <p class="text-muted">+90 (555) 123 45 67</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-info text-center p-4">
                        <i class="fas fa-map-marker-alt text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Adres</h5>
                        <p class="text-muted">İstanbul, Türkiye</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
// Footer include
include 'includes/footer.php';
?>

<style>
.typewriter-cursor {
    color: #ff6b35;
    animation: blink 1s infinite;
    font-weight: normal;
}

.typewriter-cursor-calc {
    color: #dc3545;
    animation: blink 1s infinite;
    font-weight: normal;
    margin-left: 5px;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}

#typewriter-text {
    display: inline-block;
    min-width: 250px;
    text-align: left;
}

#calculator-typewriter {
    display: inline-block;
    min-width: 200px;
    text-align: center;
}

/* Hero Slider Styles */
.hero-slider {
position: relative;
overflow: hidden;
}
    
    /* Section spacing after hero - Removed because body has padding-top for fixed navbar */
    #services {
        /* padding-top removed - handled by body padding-top */
    }

.hero-slide {
    position: relative;
    overflow: hidden;
}

.carousel-item {
    transition: transform 0.8s ease-in-out;
}

.carousel-indicators {
    bottom: 20px;
    z-index: 15;
}

.carousel-indicators button {
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.5);
    background-color: transparent;
    opacity: 0.7;
    transition: all 0.3s ease;
}

.carousel-indicators button.active,
.carousel-indicators button:hover {
    background-color: #fff;
    opacity: 1;
    transform: scale(1.2);
}

.carousel-control-prev,
.carousel-control-next {
    z-index: 15;
    width: 5%;
    transition: opacity 0.3s ease;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 0.8;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
    width: 3rem;
    height: 3rem;
    background-size: 100%;
    border-radius: 50%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.carousel-control-prev-icon:hover,
.carousel-control-next-icon:hover {
    background-color: rgba(0,0,0,0.7);
    transform: scale(1.1);
}

.slide-title {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    animation: slideInLeft 1s ease-out;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hero-slide {
        height: 650px !important;
    }
    
    .hero-slider {
        margin-top: -120px !important;
        padding-top: 120px !important;
    }
    
    .display-3 {
        font-size: 2.5rem;
    }
    
    .display-5 {
        font-size: 2rem;
    }
    
    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        width: 2rem;
        height: 2rem;
    }
}

/* Additional slide animations */
.carousel-item.active .slide-title {
    animation: slideInLeft 1s ease-out;
}

.carousel-item.active .lead {
    animation: slideInRight 1s ease-out 0.3s both;
}

.carousel-item.active .btn {
    animation: slideInUp 1s ease-out 0.6s both;
}

/* Feature boxes for about section */
.feature-box {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
}

.feature-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}

.service-card {
    background: white;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

/* Chip Tuning Calculator Styles */
.chip-tuning-calculator {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 3rem 2rem;
    border: 1px solid rgba(0, 123, 255, 0.2);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.calculator-header h3 {
    color: #0d6efd;
    font-weight: 300;
    font-size: 1.2rem;
}

.calculator-header h2 {
    color: #212529;
    font-weight: 700;
    font-size: 2.5rem;
}

.tuning-select {
    background: white;
    border: 2px solid rgba(0, 123, 255, 0.3);
    border-radius: 12px;
    padding: 1rem;
    font-weight: 500;
    font-size: 1rem;
    color: #2c3e50;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.tuning-select:focus {
    background: white;
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    outline: none;
}

.tuning-select:disabled {
    background: rgba(248, 249, 250, 1);
    color: rgba(108, 117, 125, 1);
    cursor: not-allowed;
    border-color: rgba(206, 212, 218, 1);
}

.tuning-calculate-btn {
    background: linear-gradient(135deg, #dc3545, #c82333);
    border: none;
    border-radius: 12px;
    padding: 1rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
}

.tuning-calculate-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #c82333, #a71e2a);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
}

.tuning-calculate-btn:disabled {
    background: rgba(108, 117, 125, 0.6);
    cursor: not-allowed;
    box-shadow: none;
}

/* Responsive için calculator */
@media (max-width: 768px) {
    .chip-tuning-calculator {
        padding: 2rem 1rem;
        margin: 0 1rem;
    }
    
    .calculator-header h2 {
        font-size: 2rem;
    }
    
    .tuning-select,
    .tuning-calculate-btn {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .calculator-header h2 {
        font-size: 1.8rem;
    }
    
    .calculator-header h3 {
        font-size: 1rem;
    }
    
    .tuning-select,
    .tuning-calculate-btn {
        padding: 0.7rem;
        font-size: 0.85rem;
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* MyTuning Files Style Flip Cards */
.jet-animated-boxes {
    background: transparent;
    padding: 0;
    position: relative;
    z-index: 1050;
    margin-top: -200px;
    padding-bottom: 80px;
}

.jet-box {
    opacity: 0;
    transform: translateY(50px);
    transition: all 0.6s ease;
    height: 100%;
    margin-bottom: 20px;
}

.jet-box.animate {
    opacity: 1;
    transform: translateY(0);
}

.flip-card {
    background-color: transparent;
    width: 100%;
    height: 300px;
    perspective: 1000px;
    cursor: pointer;
}

.flip-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.8s;
    transform-style: preserve-3d;
}

.flip-card:hover .flip-card-inner {
    transform: rotateX(180deg);
}

.flip-card-front, .flip-card-back {
    position: absolute;
    width: 100%;
    height: 346px;
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    /* border-radius: 8px; */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 30px 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.flip-card-front {
    background: linear-gradient(135deg, #d32734 0%, #c82333 100%);
    color: white;
}

.flip-card-back {
    background: linear-gradient(135deg, #002d5b 0%, #003469 100%);
    color: white;
    transform: rotateX(180deg);
    justify-content: flex-start;
    text-align: left;
    padding: 40px 30px;
}

.jet-box-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.jet-box-icon i {
    font-size: 2.5rem;
    color: white;
}

.jet-box-title {
    font-size: 1.2rem;
    font-weight: 800;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: white;
}

.jet-box-subtitle {
    font-size: 0.7rem;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
    color: white;
}

.flip-back-title {
    font-size: 1.2rem;
    font-weight: 800;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: white;
    align-self: flex-start;
}

.flip-back-subtitle {
    font-size: 0.7rem;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 25px;
    opacity: 0.8;
    color: white;
    align-self: flex-start;
}

.flip-back-description {
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 30px;
    opacity: 0.9;
    color: rgba(255, 255, 255, 0.9);
    text-align: left;
}

.flip-back-link {
    color: #dc3545;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border-bottom: 2px solid transparent;
    padding-bottom: 3px;
}

.flip-back-link:hover {
    color: #fff;
    border-bottom-color: #dc3545;
}

.flip-back-link i {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.flip-back-link:hover i {
    transform: translateX(3px);
}

/* Responsive Styles */
@media (max-width: 992px) {
    .jet-animated-boxes {
        margin-top: -150px;
    }
    
    .flip-card {
        height: 280px;
    }
    
    .jet-box-icon {
        width: 70px;
        height: 70px;
    }
    
    .jet-box-icon i {
        font-size: 2.2rem;
    }
    
    .jet-box-title {
        font-size: 1.8rem;
    }
    
    .flip-back-title {
        font-size: 1.6rem;
    }
}

@media (max-width: 768px) {
    .jet-animated-boxes {
        margin-top: -120px;
        padding-bottom: 60px;
    }
    
    .flip-card {
        height: 260px;
        margin-bottom: 20px;
    }
    
    .jet-box-icon {
        width: 60px;
        height: 60px;
        margin-bottom: 15px;
    }
    
    .jet-box-icon i {
        font-size: 2rem;
    }
    
    .jet-box-title {
        font-size: 1.5rem;
    }
    
    .jet-box-subtitle {
        font-size: 0.9rem;
    }
    
    .flip-back-title {
        font-size: 1.4rem;
    }
    
    .flip-back-subtitle {
        font-size: 0.8rem;
    }
    
    .flip-back-description {
        font-size: 0.85rem;
        margin-bottom: 20px;
    }
    
    .flip-card-back {
        padding: 30px 20px;
    }
}

/* Animation Classes */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease forwards;
}

/* Mobile Touch Support for Flip Cards */
@media (hover: none) and (pointer: coarse) {
    .flip-card:active .flip-card-inner {
        transform: rotateY(180deg);
    }
    
    .flip-card-inner {
        transition: transform 0.6s ease;
    }
}
</style>

<?php if ($typewriterEnabled && !empty($typewriterWords)): ?>
<script>
class TypeWriter {
    constructor(element, words, wait = 3000) {
        this.element = element;
        this.words = words;
        this.wait = parseInt(wait, 10);
        this.txt = '';
        this.wordIndex = 0;
        this.isDeleting = false;
        this.type();
    }

    type() {
        const current = this.wordIndex % this.words.length;
        const fullTxt = this.words[current];

        if (this.isDeleting) {
            this.txt = fullTxt.substring(0, this.txt.length - 1);
        } else {
            this.txt = fullTxt.substring(0, this.txt.length + 1);
        }

        this.element.innerHTML = this.txt;

        let typeSpeed = 150;

        if (this.isDeleting) {
            typeSpeed /= 2;
        }

        if (!this.isDeleting && this.txt === fullTxt) {
            typeSpeed = this.wait;
            this.isDeleting = true;
        } else if (this.isDeleting && this.txt === '') {
            this.isDeleting = false;
            this.wordIndex++;
            typeSpeed = 500;
        }

        setTimeout(() => this.type(), typeSpeed);
    }
}

// Initialize typewriter when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const typeWriterElement = document.querySelector('#typewriter-text');
    const words = <?php echo json_encode($typewriterWords); ?>;
    
    if (typeWriterElement) {
        new TypeWriter(typeWriterElement, words, 500);
    }
    
    // Initialize carousel with custom settings
    const heroCarousel = document.querySelector('#heroCarousel');
    if (heroCarousel) {
        // Hero carousel is ready - no special typewriter handling needed since calculator is now separate
        console.log('Hero carousel initialized');
    }
    
    // Initialize Calculator Typewriter
    const calculatorTypewriterElement = document.querySelector('#calculator-typewriter');
    const calculatorWords = <?php echo json_encode($calculatorTypewriterWords); ?>;
    
    if (calculatorTypewriterElement) {
        new TypeWriter(calculatorTypewriterElement, calculatorWords, 2000);
    }
    
    // Initialize Chip Tuning Calculator
    initializeChipTuningCalculator();
    
    // Initialize Jet Animated Boxes
    initializeJetBoxes();
});

// Chip Tuning Calculator JavaScript
function initializeChipTuningCalculator() {
    const brandSelect = document.getElementById('brand_select');
    const modelSelect = document.getElementById('model_select');
    const seriesSelect = document.getElementById('series_select');
    const engineSelect = document.getElementById('engine_select');
    const calculateBtn = document.querySelector('.tuning-calculate-btn');
    const form = document.getElementById('chipTuningForm');
    const loading = document.getElementById('calculatorLoading');
    
    if (!brandSelect || !form) {
        return; // Calculator not on this page
    }
    
    // Load brands on page load
    loadBrands();
    
    // Event listeners
    brandSelect.addEventListener('change', function() {
        const brandId = this.value;
        resetSelects(['model', 'series', 'engine']);
        
        if (brandId) {
            loadModels(brandId);
        }
    });
    
    modelSelect.addEventListener('change', function() {
        const modelId = this.value;
        resetSelects(['series', 'engine']);
        
        if (modelId) {
            loadSeries(modelId);
        }
    });
    
    seriesSelect.addEventListener('change', function() {
        const seriesId = this.value;
        resetSelects(['engine']);
        
        if (seriesId) {
            loadEngines(seriesId);
        }
    });
    
    engineSelect.addEventListener('change', function() {
        calculateBtn.disabled = !this.value;
    });
    
    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const engineId = engineSelect.value;
        if (!engineId) {
            alert('Lütfen tüm alanları doldurun!');
            return;
        }
        
        // Show loading
        loading.style.display = 'block';
        calculateBtn.disabled = true;
        
        // Redirect to results page
        window.location.href = `tuning-results.php?engine_id=${engineId}`;
    });
    
    // Helper functions
    function loadBrands() {
        fetch('ajax/chip_tuning_calculator.php?action=get_brands')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateSelect(brandSelect, data.data, 'id', 'name');
                } else {
                    console.error('Brands load error:', data.message);
                }
            })
            .catch(error => {
                console.error('Brands fetch error:', error);
            });
    }
    
    function loadModels(brandId) {
        showSelectLoading(modelSelect);
        
        fetch(`ajax/chip_tuning_calculator.php?action=get_models&brand_id=${brandId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateSelect(modelSelect, data.data, 'id', 'name');
                    modelSelect.disabled = false;
                } else {
                    console.error('Models load error:', data.message);
                }
            })
            .catch(error => {
                console.error('Models fetch error:', error);
            });
    }
    
    function loadSeries(modelId) {
        showSelectLoading(seriesSelect);
        
        fetch(`ajax/chip_tuning_calculator.php?action=get_series&model_id=${modelId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateSelect(seriesSelect, data.data, 'id', 'name');
                    seriesSelect.disabled = false;
                } else {
                    console.error('Series load error:', data.message);
                }
            })
            .catch(error => {
                console.error('Series fetch error:', error);
            });
    }
    
    function loadEngines(seriesId) {
        showSelectLoading(engineSelect);
        
        fetch(`ajax/chip_tuning_calculator.php?action=get_engines&series_id=${seriesId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateSelect(engineSelect, data.data, 'id', 'name');
                    engineSelect.disabled = false;
                } else {
                    console.error('Engines load error:', data.message);
                }
            })
            .catch(error => {
                console.error('Engines fetch error:', error);
            });
    }
    
    function populateSelect(selectElement, data, valueField, textField) {
        // Clear existing options except first
        selectElement.innerHTML = selectElement.options[0].outerHTML;
        
        // Add new options
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            selectElement.appendChild(option);
        });
    }
    
    function showSelectLoading(selectElement) {
        selectElement.innerHTML = '<option value="">Yükleniyor...</option>';
        selectElement.disabled = true;
    }
    
    function resetSelects(selects) {
        selects.forEach(selectType => {
            let selectElement;
            let defaultText;
            
            switch(selectType) {
                case 'model':
                    selectElement = modelSelect;
                    defaultText = 'Model Seçiniz';
                    break;
                case 'series':
                    selectElement = seriesSelect;
                    defaultText = 'Seri Seçiniz';
                    break;
                case 'engine':
                    selectElement = engineSelect;
                    defaultText = 'Motor Seçiniz';
                    break;
            }
            
            if (selectElement) {
                selectElement.innerHTML = `<option value="">${defaultText}</option>`;
                selectElement.disabled = true;
            }
        });
        
        calculateBtn.disabled = true;
    }
}

// Flip Card Animation System
function initializeJetBoxes() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const jetBox = entry.target;
                const delay = parseFloat(jetBox.dataset.delay) * 1000 || 0;
                
                setTimeout(() => {
                    jetBox.classList.add('animate');
                }, delay);
                
                observer.unobserve(jetBox);
            }
        });
    }, observerOptions);
    
    // Observe all flip card boxes
    const jetBoxes = document.querySelectorAll('.jet-box');
    jetBoxes.forEach((box) => {
        observer.observe(box);
    });
    
    // Add mobile touch support for flip cards
    const flipCards = document.querySelectorAll('.flip-card');
    flipCards.forEach(card => {
        let isFlipped = false;
        
        // Handle click/tap events
        card.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                
                if (!isFlipped) {
                    this.querySelector('.flip-card-inner').style.transform = 'rotateY(180deg)';
                    isFlipped = true;
                } else {
                    this.querySelector('.flip-card-inner').style.transform = 'rotateY(0deg)';
                    isFlipped = false;
                }
            }
        });
        
        // Handle touch events for better mobile experience
        card.addEventListener('touchstart', function(e) {
            if (window.innerWidth <= 768) {
                this.style.transform = 'scale(0.98)';
            }
        });
        
        card.addEventListener('touchend', function(e) {
            if (window.innerWidth <= 768) {
                this.style.transform = 'scale(1)';
            }
        });
    });
    
    // Reset flip states on resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            flipCards.forEach(card => {
                card.querySelector('.flip-card-inner').style.transform = 'rotateY(0deg)';
            });
        }
    });
}
</script>
<?php endif; ?>
