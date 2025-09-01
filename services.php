<?php

/**
 * Mr ECU - Hizmetler Sayfası (Yeni Tasarım)
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Hizmetlerimiz';
$pageDescription = 'Profesyonel ECU hizmetlerimizi keşfedin. ECU tuning, chip tuning, immobilizer ve daha fazlası.';
$pageKeywords = 'ECU tuning, chip tuning, immobilizer, TCU tuning, DPF off, EGR off, AdBlue off';

// Hizmetleri veritabanından getir
try {
    $stmt = $pdo->query("
        SELECT * FROM services 
        WHERE status = 'active' 
        ORDER BY sort_order ASC, name ASC
    ");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    $services = [];
    error_log('Services query error: ' . $e->getMessage());
}

// Header include
include 'includes/header.php';
?>

<style>
    /* === Page Header (Sadece height ve border-radius korundu) === */
    .page-header {
        border-radius: 0 0 30px 30px;
        height: 340px;
        position: relative;
        overflow: hidden;
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

    .page-header::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: rotate 25s linear infinite;
        z-index: 0;
    }

    .page-header .container {
        position: relative;
        z-index: 2;
    }

    /* === Hizmet Kartları === */
    .service-card {
        background: white;
        border-radius: 15px;
        padding: 0;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.4s ease;
        border: 1px solid rgba(0, 0, 0, 0.08);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .service-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        border-color: rgba(220, 53, 69, 0.2);
    }

    .service-card .card-header {
        /* background: linear-gradient(135deg, #dc3545, #c82333); */
        color: white;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        border: none;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        flex-shrink: 0;
    }

/* İçeriklerin okunabilirliği için koyu overlay */
.service-card .card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.5), rgba(200, 35, 51, 0.45));
    border-radius: 15px 15px 0 0;
    z-index: 1;
}

.service-card .card-header > * {
    position: relative;
    z-index: 2;
}

    .service-card .card-header i,
    .service-card .card-header img {
        margin-bottom: 1rem;
    }

    .service-card .card-header h3 {
        color: white;
        font-weight: 700;
        margin-bottom: 0;
    }

    /* === Card Body === */
    .service-card .card-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .service-description {
        font-size: 0.95rem;
        line-height: 1.5;
        min-height: 60px;
    }

    /* === Özellik Listesi === */
    .service-features {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem 0;
        flex-grow: 1;
    }

    .service-features li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .service-features li::before {
        content: '';
        width: 8px;
        height: 8px;
        background: linear-gradient(135deg, #dc3545, #c82333);
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* === Fiyat Bilgisi === */
    .pricing-info {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1rem;
        margin: 1.5rem 0;
        border: 1px solid #dee2e6;
        text-align: center;
    }

    .pricing-info .price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #dc3545;
        margin: 0.25rem 0;
    }

    /* === Butonlar === */
    .service-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin-top: auto;
        padding-top: 1rem;
    }

    .service-actions .btn {
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 25px;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
    }

    .btn-primary {
        background: #dc3545;
        color: white;
    }

    @media (min-width: 1200px) {

        .h3,
        h3 {
            font-size: 1.45rem !important;
        }
    }

    .btn-outline-primary {
        background: transparent;
        color: #dc3545;
        border: 2px solid #dc3545;
    }

    .btn-primary:hover,
    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
    }

    .btn-primary:hover {
        background: #c82333;
    }

    .btn-outline-primary:hover {
        background: #dc3545;
        color: white;
    }

    /* === Süreç Adımları === */
    .process-steps {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 5rem 0;
        position: relative;
        overflow: hidden;
    }

    .process-steps::before {
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

    .process-steps .container {
        position: relative;
        z-index: 2;
    }

    .process-step {
        text-align: center;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .process-step:hover {
        transform: translateY(-5px);
    }

    .process-step-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        background: linear-gradient(135deg, #dc3545, #c82333);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
    }

    .process-step .step-number {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #ffc107;
        color: #000;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .process-step h5 {
        font-weight: 600;
        margin-bottom: 0.75rem;
    }

    .process-step p {
        color: #6c757d;
        font-size: 0.95rem;
    }

    /* === CTA Section === */
    .cta-section {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 4rem 0;
        position: relative;
        overflow: hidden;
    }

    .cta-section::before {
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

    .cta-section .container {
        position: relative;
        z-index: 2;
    }

    .cta-section .btn {
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .cta-section .btn-light {
        background: white;
        color: #dc3545;
    }

    .cta-section .btn-outline-light {
        border: 2px solid white;
        color: white;
    }

    .cta-section .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.5);
    }

    /* === Animations === */
    @keyframes rotate {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* === Responsive === */
    @media (max-width: 768px) {
        .page-header {
            height: 340px;
        }

        .service-card .card-body {
            padding: 1.25rem;
        }

        .service-description {
            min-height: 50px;
        }

        .process-step-icon {
            width: 70px;
            height: 70px;
            font-size: 1.25rem;
        }

        .pricing-info .price {
            font-size: 1.3rem;
        }

        .service-actions {
            flex-direction: column;
            gap: 0.5rem;
        }

        .service-actions .btn {
            width: 100%;
            padding: 0.6rem 1rem;
        }
    }
</style>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Profesyonel ECU Hizmetlerimiz</h1>
                <p class="lead mb-4">
                    Araçlarınızın performansını maksimuma çıkarın. Uzman ekibimizle
                    tüm marka ve modeller için kaliteli hizmet veriyoruz.
                </p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-home me-1"></i>Ana Sayfa
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Hizmetler</li>
                    </ol>
                </nav>
            </div>
            <div class="col-lg-4 text-center">
                <i class="bi bi-gear-wide-connected" style="font-size: 8rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Ana Hizmetler -->
<section class="py-5">
    <div class="container">
        <?php if (!empty($services)): ?>
            <div class="row g-4">
                <?php foreach ($services as $service): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="service-card">
                            <div class="card-header" style="background-image: url('<?php echo !empty($service['image']) ? htmlspecialchars($service['image']) : 'assets/images/default-service-bg.jpg'; ?>');">
                                <?php if (!empty($service['icon_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($service['icon_picture']); ?>"
                                        alt="<?php echo htmlspecialchars($service['name']); ?>"
                                        style="width: 60px; height: 60px; object-fit: contain;">
                                <?php elseif (!empty($service['icon'])): ?>
                                    <i class="<?php echo htmlspecialchars($service['icon']); ?> fa-2x"></i>
                                <?php else: ?>
                                    <i class="bi bi-gear-wide-connected fa-2x"></i>
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            </div>

                            <div class="card-body">
                                <p class="text-muted mb-3 service-description">
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </p>

                                <!-- Özellikler -->
                                <?php if ($service['features'] && $service['features'] != 'null'): ?>
                                    <?php
                                    $features = json_decode($service['features'], true);
                                    if ($features && is_array($features)):
                                    ?>
                                        <ul class="service-features">
                                            <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                                                <li><?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Fiyat -->
                                <?php if ($service['price_from']): ?>
                                    <div class="pricing-info">
                                        <small>Hizmet Ücreti</small>
                                        <div class="price">
                                            <?php echo number_format($service['price_from'], 2); ?> TL
                                        </div>
                                        <small>Başlangıç Fiyatı</small>
                                    </div>
                                <?php endif; ?>

                                <!-- Butonlar -->
                                <div class="service-actions">
                                    <a href="hizmet/<?php echo urlencode($service['slug']); ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-info-circle me-1"></i>Detaylar
                                    </a>
                                    <a href="<?php echo isLoggedIn() ? 'user/upload.php' : 'register.php'; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-upload me-1"></i>Hizmet Al
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <h4>Hizmetler Yükleniyor...</h4>
                        <p>Şu anda hizmet bilgileri yüklenemiyor. Lütfen daha sonra tekrar deneyin.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Süreç Adımları -->
<section class="process-steps">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Nasıl Çalışır?</h2>
                <p class="lead text-muted">4 basit adımda profesyonel hizmet alın</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="position-relative">
                        <div class="process-step-icon">
                            <span class="step-number">1</span>
                            <i class="bi bi-upload"></i>
                        </div>
                    </div>
                    <h5>Dosya Yükle</h5>
                    <p class="text-muted">
                        ECU dosyanızı güvenli platformumuza yükleyin.
                        Tüm yaygın formatları destekliyoruz.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="position-relative">
                        <div class="process-step-icon">
                            <span class="step-number">2</span>
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                    </div>
                    <h5>Uzman İncelemesi</h5>
                    <p class="text-muted">
                        Deneyimli teknisyenlerimiz dosyanızı inceler
                        ve gerekli optimizasyonları yapar.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="position-relative">
                        <div class="process-step-icon">
                            <span class="step-number">3</span>
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <h5>Kalite Kontrolü</h5>
                    <p class="text-muted">
                        İşlenmiş dosya kapsamlı testlerden geçer
                        ve kalite kontrolünden onay alır.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="position-relative">
                        <div class="process-step-icon">
                            <span class="step-number">4</span>
                            <i class="bi bi-download"></i>
                        </div>
                    </div>
                    <h5>Dosya Teslimi</h5>
                    <p class="text-muted">
                        Optimize edilmiş dosyanız hazır!
                        İndirebilir ve aracınıza yükleyebilirsiniz.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="mb-3">Aracınızın Performansını Artırın!</h3>
                <p class="mb-0 lead">
                    Profesyonel ECU hizmetlerimizle aracınızın gizli gücünü ortaya çıkarın.
                    Hemen başlayın ve farkı yaşayın.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-column gap-2">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-light btn-lg">
                            <i class="bi bi-user-plus me-2"></i>Ücretsiz Kayıt Ol
                        </a>
                        <a href="login.php" class="btn btn-outline-light">
                            <i class="bi bi-sign-in-alt me-2"></i>Giriş Yap
                        </a>
                    <?php else: ?>
                        <a href="user/upload.php" class="btn btn-light btn-lg">
                            <i class="bi bi-upload me-2"></i>Dosya Yükle
                        </a>
                        <a href="user/" class="btn btn-outline-light">
                            <i class="bi bi-tachometer-alt me-2"></i>Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Footer include
include 'includes/footer.php';
?>