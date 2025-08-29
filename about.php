<?php
/**
 * Mr ECU - Hakkımızda Sayfası (Database Entegreli)
 * Referans: https://www.mrecufile.com.tr/hakkimizda
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Hakkımızda';
$pageDescription = 'Mr ECU Teknoloji ve Otomotiv Çözümleri - Online Chiptuning dosya hizmeti sunan deneyimli ekibimizi tanıyın. 10+ yıllık sektör deneyimi ile global ölçekte hizmet.';
$pageKeywords = 'hakkımızda, ECU uzmanları, chiptuning, online dosya hizmeti, otomotiv teknoloji, 10 yıl deneyim, profesyonel ekip';

// Database'den about içeriklerini çek
try {
    // Ana about content
    $about_query = "SELECT * FROM about_content WHERE is_active = 1 LIMIT 1";
    $about_stmt = $pdo->prepare($about_query);
    $about_stmt->execute();
    $about_content = $about_stmt->fetch(PDO::FETCH_ASSOC);

    // Core Values
    $values_query = "SELECT * FROM about_core_values WHERE is_active = 1 ORDER BY order_no ASC";
    $values_stmt = $pdo->prepare($values_query);
    $values_stmt->execute();
    $core_values = $values_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Service Features
    $features_query = "SELECT * FROM about_service_features WHERE is_active = 1 ORDER BY order_no ASC";
    $features_stmt = $pdo->prepare($features_query);
    $features_stmt->execute();
    $service_features = $features_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vision
    $vision_query = "SELECT * FROM about_vision WHERE is_active = 1 LIMIT 1";
    $vision_stmt = $pdo->prepare($vision_query);
    $vision_stmt->execute();
    $vision_content = $vision_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Hata durumunda default değerler
    $about_content = [];
    $core_values = [];
    $service_features = [];
    $vision_content = [];
    error_log("About page database error: " . $e->getMessage());
}

// Header include
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3" style="color: #fff! important;">Hakkımızda</h1>
                <p class="lead mb-4" style="color: #fff! important;">Mr. ECU Teknoloji ve Otomotiv Çözümleri</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php" class="text-white-50 text-decoration-none">
                                <i class="fas fa-home me-1"></i>Ana Sayfa
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Hakkımızda</li>
                    </ol>
                </nav>
            </div>
            <div class="col-lg-4 text-center">
                <img src="/mrecuphpkopyasikopyasi6kopyasi/assets/images/mreculogomini.png" alt="MR ECU Logo" style="width: 250px;">
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="about-content">
                    <h2 class="display-5 fw-bold mb-4 text-primary" style="color: #071e3d !important;">
                        <?php echo !empty($about_content['title']) ? htmlspecialchars($about_content['title']) : 'Neden Biz?'; ?>
                    </h2>
                    
                    <?php if (!empty($about_content['subtitle'])): ?>
                        <p class="lead mb-4">
                            <strong><?php echo htmlspecialchars($about_content['subtitle']); ?></strong>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($about_content['description'])): ?>
                        <p class="lead mb-4">
                            <?php echo nl2br(htmlspecialchars($about_content['description'])); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($about_content['main_content'])): ?>
                        <div class="mb-4">
                            <?php echo nl2br(htmlspecialchars($about_content['main_content'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Key Features -->
                    <?php if (!empty($about_content['features'])): ?>
                        <?php 
                        $features = json_decode($about_content['features'], true);
                        if (is_array($features) && !empty($features)): 
                        ?>
                            <div class="row g-3 mt-4">
                                <?php foreach ($features as $feature): ?>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="<?php echo htmlspecialchars($feature['icon'] ?? 'fas fa-check-circle text-success'); ?> me-2 fs-5"></i>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($feature['title'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="about-image position-relative">
                    <img src="<?php echo !empty($about_content['image_url']) ? htmlspecialchars($about_content['image_url']) : 'https://storage.acerapps.io/app-1580/images/about-img.webp'; ?>" 
                         class="img-fluid rounded-3" 
                         alt="<?php echo htmlspecialchars($about_content['title'] ?? 'Mr ECU - Otomotiv Teknoloji Çözümleri'); ?>">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Values Section -->
<?php if (!empty($core_values)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Temel Değerlerimiz</h2>
                <p class="lead text-muted">Çalışma prensiplerimiz ve değerlerimiz</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($core_values as $value): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon me-3 <?php echo htmlspecialchars($value['background_color']); ?> p-3 rounded-3">
                                    <i class="<?php echo htmlspecialchars($value['icon']); ?> <?php echo htmlspecialchars($value['icon_color']); ?>" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($value['title']); ?></h5>
                                    <p class="card-text">
                                        <?php echo nl2br(htmlspecialchars($value['description'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-primary text-white" style="background: linear-gradient(135deg, #007bff 0%, #071e3d  100%);">
                    <div class="card-body p-4 text-center">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h5 class="mb-2" style="color: #fff !important;">Bizim Hedefimiz</h5>
                                <p class="mb-0">
                                    Hızla değişen otomotiv dünyasına <strong>bilgi birikimimiz</strong> ve 
                                    <strong>yüksek standartlarımız</strong> ile yön veriyoruz. Hedefimiz, iş ortaklarımızın 
                                    her zaman <strong>bir adım önde</strong> olmasını sağlayarak, onlara 
                                    <strong>güçlü bir çözüm ortağı</strong> olmaktır.
                                </p>
                            </div>
                            <div class="col-lg-4 text-center">
                                <i class="fas fa-bullseye" style="font-size: 4rem; opacity: 0.7;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Services Features -->
<?php if (!empty($service_features)): ?>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Hizmet Özelliklerimiz</h2>
                <p class="lead text-muted">Neden bizi tercih etmelisiniz?</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php 
            $col_class = count($service_features) <= 3 ? 'col-lg-4' : 'col-lg-3';
            foreach ($service_features as $feature): 
            ?>
                <div class="<?php echo $col_class; ?> col-md-6">
                    <div class="text-center service-feature h-100 p-4">
                        <div class="service-icon mb-4">
                            <?php if (!empty($feature['icon_url'])): ?>
                                <img src="<?php echo htmlspecialchars($feature['icon_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($feature['title']); ?>" 
                                     class="img-fluid" style="height: 60px;">
                            <?php else: ?>
                                <i class="<?php echo htmlspecialchars($feature['icon'] ?? 'fas fa-cog'); ?>" style="font-size: 3rem; color: #007bff;"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($feature['title']); ?></h5>
                        <p class="text-muted">
                            <?php echo nl2br(htmlspecialchars($feature['description'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Vision Section -->
<?php if (!empty($vision_content)): ?>
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="<?php echo !empty($vision_content['image_url']) ? htmlspecialchars($vision_content['image_url']) : 'https://storage.acerapps.io/app-1580/images/ch3.webp'; ?>" 
                     class="img-fluid rounded-3 shadow-lg" 
                     alt="<?php echo htmlspecialchars($vision_content['title'] ?? 'Mr ECU Teknoloji'); ?>">
            </div>
            <div class="col-lg-6">
                <div class="vision-content ps-lg-4">
                    <h2 class="display-5 fw-bold mb-4 text-primary"><?php echo htmlspecialchars($vision_content['title']); ?></h2>
                    
                    <?php if (!empty($vision_content['subtitle'])): ?>
                        <p class="lead mb-4">
                            <?php echo nl2br(htmlspecialchars($vision_content['subtitle'])); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($vision_content['description'])): ?>
                        <p class="mb-4">
                            <?php echo nl2br(htmlspecialchars($vision_content['description'])); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($vision_content['main_content'])): ?>
                        <p class="mb-4">
                            <?php echo nl2br(htmlspecialchars($vision_content['main_content'])); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($vision_content['features'])): ?>
                        <?php 
                        $vision_features = json_decode($vision_content['features'], true);
                        if (is_array($vision_features) && !empty($vision_features)): 
                        ?>
                            <div class="row g-3 mt-4">
                                <?php foreach ($vision_features as $vf): ?>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                            <i class="<?php echo htmlspecialchars($vf['icon'] ?? 'fas fa-rocket text-primary'); ?> me-3 fs-4"></i>
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($vf['title'] ?? ''); ?></h6>
                                                <?php if (!empty($vf['description'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($vf['description']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<div class="container">
    <div class="contact-cta">
        <h2 class="mb-4" style="color: #fff !important;">Aracınızın Performansını Artırmaya Hazır mısınız?</h2>
        <p class="lead mb-4" style="color: #fff !important;">
            Profesyonel ekibimiz ile iletişime geçin ve aracınız için en uygun chip tuning çözümünü keşfedin.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="tel:+905551234567" class="btn btn-light btn-lg">
                <i class="fas fa-phone me-2"></i>Hemen Ara
            </a>
            <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-outline-light btn-lg">
                <i class="fas fa-envelope me-2"></i>E-posta Gönder
            </a>
            <a href="register.php" class="btn btn-warning btn-lg">
                <i class="fas fa-upload me-2"></i>Dosya Yükle
            </a>
        </div>
        
        <div class="row mt-5 text-center">
            <div class="col-md-4">
                <i class="fas fa-shield-alt fa-2x mb-3"></i>
                <h5 style="color: #fff !important;">Güvenli İşlem</h5>
                <p style="color: #fff !important;">Aracınızın garantisi bozulmaz</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-clock fa-2x mb-3"></i>
                <h5 style="color: #fff !important;">Hızlı Teslimat</h5>
                <p style="color: #fff !important;">24 saat içinde dosyanız hazır</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-undo fa-2x mb-3"></i>
                <h5 style="color: #fff !important;">Geri Dönüş Garantisi</h5>
                <p style="color: #fff !important;">İstediğiniz zaman eski haline döndürülebilir</p>
            </div>
        </div>
    </div>
</div>

<style>
/* About Page Styles */
.hover-card {
    transition: all 0.3s ease;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
}

.service-feature {
    transition: all 0.3s ease;
    border-radius: 1rem;
    background: white;
}

.service-feature:hover {
    transform: translateY(-10px);
    background: rgba(0, 123, 255, 0.05);
}

.service-icon {
    transition: all 0.3s ease;
}

.service-feature:hover .service-icon {
    transform: scale(1.1);
}

.stat-item {
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: scale(1.05);
}

.vision-content p {
    font-size: 1.1rem;
    line-height: 1.8;
}

/* Text visibility fixes */
body {
    color: #333 !important;
}

.text-dark {
    color: #333 !important;
}

.text-muted {
    color: #6c757d !important;
}

h1, h2, h3, h4, h5, h6 {
    color: #333 !important;
}

.display-5.text-primary {
    color: #007bff !important;
}

.card-text {
    color: #333 !important;
}

.lead {
    color: #333 !important;
}
.contact-cta {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 3rem 0;
    border-radius: 15px;
    text-align: center;
    margin: 3rem 0;
}
.page-header {
    border-radius: 0 0 30px 30px;
    height: 340px;
    position: relative;
    overflow: hidden;
}
.page-header .container {
    position: relative;
    z-index: 2;
}
.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #071e3d 0%, #1a3a6e 100%);
    z-index: 1;
}

/* Responsive fixes */
@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
    }
    
    .service-feature {
        margin-bottom: 2rem;
    }
}
</style>

<script>
// Initialize AOS when page loads
document.addEventListener('DOMContentLoaded', function() {
    // AOS initialize - only if AOS is available
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    }
    
    // Additional smooth animations for stats
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.stat-item, .service-feature, .hover-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});

// Counter animation for statistics
function animateCounters() {
    const counters = document.querySelectorAll('.stat-item h3');
    const speed = 200;
    
    counters.forEach(counter => {
        const updateCount = () => {
            const target = parseInt(counter.textContent.replace(/\D/g, ''));
            const count = +counter.getAttribute('data-count') || 0;
            const increment = target / speed;
            
            if (count < target) {
                counter.setAttribute('data-count', Math.ceil(count + increment));
                counter.textContent = Math.ceil(count + increment) + counter.textContent.replace(/\d+/g, '').replace(/^\d+/, '');
                setTimeout(updateCount, 1);
            } else {
                counter.textContent = counter.textContent.replace(/\d+/, target);
            }
        };
        updateCount();
    });
}

// Trigger counter animation when stats section is visible
const statsSection = document.querySelector('.py-5.bg-light');
if (statsSection) {
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                setTimeout(animateCounters, 500);
                statsObserver.unobserve(entry.target);
            }
        });
    });
    
    statsObserver.observe(statsSection);
}
</script>

<?php
// Footer include
include 'includes/footer.php';
?>