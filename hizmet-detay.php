<?php
/**
 * Mr ECU - Hizmet Detay Sayfası (Yeni Tasarım)
 */

require_once 'config/config.php';
require_once 'config/database.php';

// URL'den slug'ı al
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect('services.php');
}

// Hizmet bilgilerini getir
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE slug = ? AND status = 'active'");
    $stmt->execute([$slug]);
    $service = $stmt->fetch();
    
    if (!$service) {
        redirect('services.php');
    }
} catch (Exception $e) {
    error_log('Service detail query error: ' . $e->getMessage());
    redirect('services.php');
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

// Meta bilgileri
$pageTitle = $service['name'] . ' - Hizmetlerimiz';
$pageDescription = substr($service['description'], 0, 160);
$pageKeywords = $service['name'] . ', ECU, chip tuning, ' . str_replace(' ', ', ', strtolower($service['name']));

// Features'ları decode et
$features = [];
if ($service['features']) {
    $features = json_decode($service['features'], true) ?? [];
}

// Header include
include 'includes/header.php';
?>

<style>
/* === Service Hero Section === */
.service-hero {
    background: linear-gradient(135deg, rgba(7, 30, 61, 0.8) 0%, rgba(26, 58, 110, 0.8) 100%);
    color: white;
    padding: 4rem 0;
    border-radius: 0 0 30px 30px;
    height: 340px;
    position: relative;
    overflow: hidden;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}

.service-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: rotate 25s linear infinite;
    z-index: 1;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.service-hero .container {
    position: relative;
    z-index: 2;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 1rem;
}

.breadcrumb-item a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb-item a:hover {
    color: #fff;
}

.breadcrumb-item.active {
    color: #fff;
    font-weight: 600;
}

.service-hero h1 {
    font-size: 2.8rem;
    font-weight: 700;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
    animation: slideInLeft 1s ease-out;
}

.service-hero .lead {
    font-size: 1.2rem;
    opacity: 0.9;
    animation: slideInRight 1s ease-out 0.3s both;
}

/* === Main Content Styles === */
.service-detail-container {
    padding: 3rem 0;
}

.service-main-content {
    background: white;
    border-radius: 15px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    transition: transform 0.4s ease, box-shadow 0.4s ease;
}

.service-main-content:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

/* === Features Grid === */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.feature-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.feature-item:hover {
    background: #e9f7ff;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.1);
    border-color: rgba(0, 123, 255, 0.2);
}

.feature-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
}

/* === Process Steps === */
.process-steps {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 2rem;
    margin: 2rem 0;
    border: 1px solid #dee2e6;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.step-item:hover {
    background: #fff;
    border-radius: 8px;
    padding: 1rem;
}

.step-item:last-child {
    border-bottom: none;
}

.step-number {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
}

/* === Sidebar === */
.service-sidebar {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 2rem;
    transition: all 0.4s ease;
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.service-sidebar:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
}

/* === Price Card === */
.price-card {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
}

.price-amount-large {
    font-size: 3rem;
    font-weight: 700;
    margin: 1rem 0;
    background: linear-gradient(45deg, #fff, #f8f9fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* === CTA Buttons === */
.cta-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: center;
}

.cta-buttons .btn {
    padding: 1rem 2rem;
    font-weight: 600;
    border-radius: 25px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
}

.cta-buttons .btn-primary {
    background: #dc3545;
    color: white;
}

.cta-buttons .btn-primary:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
}

/* === Alert Box === */
.alert-info {
    background: #e7f3ff;
    border-left: 5px solid #007bff;
    border-radius: 12px;
}

.alert-info .alert-heading {
    color: #0056b3;
}

/* === Quick Links & Contact === */
.contact-info i {
    color: #dc3545;
}

.quick-links .btn {
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.quick-links .btn:hover {
    background: #071e3d !important;
    color: white !important;
    transform: translateY(-2px);
}

/* === Related Services === */
.related-services-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 5rem 0;
    position: relative;
    overflow: hidden;
}

.related-services-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(220, 53, 69, 0.05) 0%, transparent 50%);
    animation: rotate 20s linear infinite;
    z-index: 1;
}

.related-services-section .container {
    position: relative;
    z-index: 2;
}

/* === Responsive Adjustments === */
@media (max-width: 768px) {
    .service-hero {
        height: 340px;
        padding: 2.5rem 0;
    }

    .service-hero h1 {
        font-size: 2.2rem;
    }

    .service-main-content,
    .service-sidebar {
        padding: 1.5rem;
    }

    .step-item {
        gap: 0.8rem;
    }

    .step-number {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }

    .price-amount-large {
        font-size: 2.5rem;
    }

    .cta-buttons {
        flex-direction: column;
    }

    .breadcrumb {
        font-size: 0.9rem;
    }
}
.contact-cta {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 3rem 0;
    border-radius: 15px;
    text-align: center;
    margin: 3rem 0;
}

/* === Animations === */
@keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-50px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(50px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>

<!-- Service Hero Section -->
<section class="service-hero" <?php if (!empty($service['image'])): ?>style="background-image: url('../<?php echo htmlspecialchars($service['image']); ?>')"<?php endif; ?>>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php"><i class="bi bi-home me-1"></i>Ana Sayfa</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="services.php">Hizmetlerimiz</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </li>
                    </ol>
                </nav>

                <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($service['name']); ?></h1>
                <p class="lead mb-4"><?php echo htmlspecialchars($service['description']); ?></p>

                <?php if ($service['price_from']): ?>
                    <div class="d-flex align-items-center mb-4">
                        <span class="me-3">Başlangıç Fiyatı:</span>
                        <span class="h4 mb-0 text-warning fw-bold">
                            <?php echo number_format($service['price_from'], 2); ?> TL
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4 text-center">
                <div class="service-icon-large" style="
                    width: 120px;
                    height: 120px;
                    background: linear-gradient(135deg, #dc3545, #c82333);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto;
                    font-size: 3rem;
                    color: white;
                    box-shadow: 0 10px 30px rgba(220, 53, 69, 0.4);
                ">
                    <?php if (!empty($service['icon_picture'])): ?>
                        <img src="../<?php echo htmlspecialchars($service['icon_picture']); ?>" 
                             alt="<?php echo htmlspecialchars($service['name']); ?>" 
                             style="width: 80px; height: 80px; object-fit: contain;">
                    <?php else: ?>
                        <i class="<?php echo htmlspecialchars($service['icon']); ?>"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Service Detail Content -->
<section class="service-detail-container">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="service-main-content">
                    <h2 class="mb-4">Hizmet Detayları</h2>
                    <p class="lead"><?php echo htmlspecialchars($service['description']); ?></p>
                    
                    <?php if (!empty($service['detailed_content'])): ?>
                        <div class="detailed-content-section mt-4">
                            <h3 class="mb-3">Detaylı Bilgiler</h3>
                            <div class="content-wrapper" style="
                                background: #f8f9fa;
                                border-radius: 12px;
                                padding: 2rem;
                                border: 1px solid #e9ecef;
                                line-height: 1.8;
                            ">
                                <?php echo $service['detailed_content']; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($features)): ?>
                        <h3 class="mt-5 mb-4">Özellikler ve Faydalar</h3>
                        <div class="features-grid">
                            <?php foreach ($features as $feature): ?>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="bi bi-check"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($feature); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Process Steps -->
                    <div class="process-steps">
                        <h3 class="mb-4">Hizmet Süreci</h3>
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div>
                                <h5 class="mb-1">Dosya Yükleme</h5>
                                <p class="mb-0 text-muted">ECU dosyanızı güvenli platformumuza yükleyin</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div>
                                <h5 class="mb-1">Analiz ve İşlem</h5>
                                <p class="mb-0 text-muted">Uzman ekibimiz dosyanızı analiz eder ve gerekli işlemleri yapar</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div>
                                <h5 class="mb-1">Kalite Kontrolü</h5>
                                <p class="mb-0 text-muted">İşlenmiş dosya kalite kontrolünden geçer</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">4</div>
                            <div>
                                <h5 class="mb-1">Teslimat</h5>
                                <p class="mb-0 text-muted">Optimize edilmiş dosyanızı indirip aracınıza yükleyebilirsiniz</p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="alert alert-info">
                        <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Önemli Notlar</h5>
                        <ul class="mb-0">
                            <li>Tüm işlemlerimiz için %100 memnuniyet garantisi veriyoruz</li>
                            <li>Orijinal dosyanızın yedeğini saklarız</li>
                            <li>7/24 teknik destek hizmetimiz mevcuttur</li>
                            <li>İşlem süresi genellikle 2-24 saat arasındadır</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="service-sidebar">
                    <?php if ($service['price_from']): ?>
                        <div class="price-card">
                            <h4 class="mb-2">Hizmet Ücreti</h4>
                            <div class="price-amount-large">
                                <?php echo number_format($service['price_from'], 2); ?> TL
                            </div>
                            <p class="mb-0">Başlangıç fiyatıdır</p>
                        </div>
                    <?php endif; ?>

                    <div class="cta-buttons">
                        <?php if (isLoggedIn()): ?>
                            <a href="user/upload.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-upload me-2"></i>Hizmet Al
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus me-2"></i>Kayıt Ol & Hizmet Al
                            </a>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4">

                    <!-- Contact Info -->
                    <div class="contact-info">
                        <h5 class="mb-3"><i class="bi bi-headset me-2 text-primary"></i>İletişim</h5>
                        
                        <!-- E-posta - ID: 2 -->
                        <?php if (isset($contactCardsById[2])): ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="<?php echo $contactCardsById[2]['icon'] ?: 'bi bi-envelope'; ?> text-primary me-2" style="color: <?php echo $contactCardsById[2]['icon_color'] ?: ''; ?> !important;"></i>
                                <span><?php echo $contactCardsById[2]['contact_info'] ?: SITE_EMAIL; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-envelope text-primary me-2"></i>
                                <span><?php echo SITE_EMAIL; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Telefon - ID: 1 -->
                        <?php if (isset($contactCardsById[1])): ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="<?php echo $contactCardsById[1]['icon'] ?: 'bi bi-telephone-fill'; ?> text-primary me-2" style="color: <?php echo $contactCardsById[1]['icon_color'] ?: ''; ?> !important;"></i>
                                <span><?php echo $contactCardsById[1]['contact_info'] ?: '+90 (555) 123 45 67'; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-telephone-fill text-primary me-2"></i>
                                <span>+90 (555) 123 45 67</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- WhatsApp - ID: 3 -->
                        <?php if (isset($contactCardsById[3])): ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="<?php echo $contactCardsById[3]['icon'] ?: 'bi bi-whatsapp'; ?> text-primary me-2" style="color: <?php echo $contactCardsById[3]['icon_color'] ?: ''; ?> !important;"></i>
                                <span><?php echo $contactCardsById[3]['contact_info'] ?: '+90 (555) 123 45 67'; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-whatsapp text-primary me-2"></i>
                                <span>+90 (555) 123 45 67</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <hr class="my-4">

                    <!-- Quick Links -->
                    <div class="quick-links">
                        <h5 class="mb-3"><i class="bi bi-external-link-alt me-2 text-primary"></i>Hızlı Erişim</h5>
                        <div class="d-grid gap-2">
                            <a href="services.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Tüm Hizmetler
                            </a>
                            <?php if (isLoggedIn()): ?>
                                <a href="user/" class="btn btn-outline-secondary">
                                    <i class="bi bi-speedometer me-2"></i>Panelim
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-sign-in-alt me-2"></i>Giriş Yap
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Services -->
<?php
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE status = 'active' AND id != ? ORDER BY RAND() LIMIT 3");
    $stmt->execute([$service['id']]);
    $relatedServices = $stmt->fetchAll();
} catch (Exception $e) {
    $relatedServices = [];
}
?>

<?php if (!empty($relatedServices)): ?>
<section class="related-services-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Diğer Hizmetlerimiz</h2>
                <p class="lead text-muted">İlginizi çekebilecek profesyonel çözümler</p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($relatedServices as $relatedService): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm bg-white rounded transition-all" style="border-radius: 15px;">
                    <div class="card-body text-center p-4">
                        <div class="service-icon-wrapper mb-3" style="
                            width: 60px;
                            height: 60px;
                            margin: 0 auto 1rem;
                            background: linear-gradient(135deg, #dc3545, #c82333);
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <?php if (!empty($relatedService['icon_picture'])): ?>
                                <img src="../<?php echo htmlspecialchars($relatedService['icon_picture']); ?>" 
                                     alt="<?php echo htmlspecialchars($relatedService['name']); ?>" 
                                     style="width: 40px; height: 40px; object-fit: contain;">
                            <?php else: ?>
                                <i class="<?php echo htmlspecialchars($relatedService['icon']); ?>" style="color: white; font-size: 1.5rem;"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($relatedService['name']); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($relatedService['description'], 0, 100)) . '...'; ?>
                        </p>
                        <?php if ($relatedService['price_from']): ?>
                            <div class="mb-3">
                                <span class="h6 text-danger fw-bold"><?php echo number_format($relatedService['price_from'], 2); ?> TL</span>
                            </div>
                        <?php endif; ?>
                        <a href="hizmet/<?php echo urlencode($relatedService['slug']); ?>" class="btn btn-outline-danger btn-sm px-4">
                            <i class="bi bi-eye me-1"></i>İncele
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<div class="container">
    <div class="contact-cta">
        <h2 class="mb-4">Aracınızın Performansını Artırmaya Hazır mısınız?</h2>
        <p class="lead mb-4">
            Profesyonel ekibimiz ile iletişime geçin ve aracınız için en uygun chip tuning çözümünü keşfedin.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <!-- Telefon Button - ID: 1 -->
            <?php if (isset($contactCardsById[1])): ?>
                <a href="<?php echo $contactCardsById[1]['contact_link'] ?: 'tel:+905551234567'; ?>" class="btn btn-light btn-lg">
                    <i class="<?php echo $contactCardsById[1]['icon'] ?: 'bi bi-telephone-fill'; ?> me-2" style="color: <?php echo $contactCardsById[1]['icon_color'] ?: ''; ?>;"></i><?php echo $contactCardsById[1]['button_text'] ?: 'Hemen Ara'; ?>
                </a>
            <?php else: ?>
                <a href="tel:+905551234567" class="btn btn-light btn-lg">
                    <i class="bi bi-telephone-fill me-2"></i>Hemen Ara
                </a>
            <?php endif; ?>
            
            <!-- E-posta Button - ID: 2 -->
            <?php if (isset($contactCardsById[2])): ?>
                <a href="<?php echo $contactCardsById[2]['contact_link'] ?: 'mailto:' . SITE_EMAIL; ?>" class="btn btn-outline-light btn-lg">
                    <i class="<?php echo $contactCardsById[2]['icon'] ?: 'bi bi-envelope'; ?> me-2" style="color: <?php echo $contactCardsById[2]['icon_color'] ?: ''; ?>;"></i><?php echo $contactCardsById[2]['button_text'] ?: 'E-posta Gönder'; ?>
                </a>
            <?php else: ?>
                <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-envelope me-2"></i>E-posta Gönder
                </a>
            <?php endif; ?>
            
            <a href="../register.php" class="btn btn-warning btn-lg">
                <i class="bi bi-upload me-2"></i>Dosya Yükle
            </a>
        </div>
        
        <div class="row mt-5 text-center">
            <div class="col-md-4">
                <i class="bi bi-shield-exclamation fa-2x mb-3"></i>
                <h5>Güvenli İşlem</h5>
                <p>Aracınızın garantisi bozulmaz</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-clock fa-2x mb-3"></i>
                <h5>Hızlı Teslimat</h5>
                <p>24 saat içinde dosyanız hazır</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-undo fa-2x mb-3"></i>
                <h5>Geri Dönüş Garantisi</h5>
                <p>İstediğiniz zaman eski haline döndürülebilir</p>
            </div>
        </div>
    </div>
</div>

<?php
// Footer include
include 'includes/footer.php';
?>