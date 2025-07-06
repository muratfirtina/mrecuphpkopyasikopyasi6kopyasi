<?php
/**
 * Mr ECU - Ana Sayfa
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Ana Sayfa';
$pageDescription = 'Profesyonel ECU hizmetleri - Güvenli, hızlı ve kaliteli ECU yazılım çözümleri';
$pageKeywords = 'ECU, chip tuning, ECU yazılım, immobilizer, TCU, motor kontrol ünitesi';

// Header include
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Profesyonel ECU Hizmetleri</h1>
                    <p class="lead mb-4">
                        Araç ECU yazılımlarınızı güvenli şekilde yükleyin, işletin ve indirin. 
                        Uzman ekibimizle tüm marka ve modeller için hizmet veriyoruz.
                    </p>
                    <div class="d-flex gap-3">
                        <?php if (!isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-light btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Hemen Başla
                            </a>
                        <?php else: ?>
                            <a href="user/upload.php" class="btn btn-light btn-lg">
                                <i class="fas fa-upload me-2"></i>Dosya Yükle
                            </a>
                        <?php endif; ?>
                        <a href="#services" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Daha Fazla Bilgi
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-microchip" style="font-size: 15rem; opacity: 0.3;"></i>
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
                            <img src="https://via.placeholder.com/300x200/007bff/ffffff?text=ECU" class="img-fluid rounded shadow" alt="ECU">
                        </div>
                        <div class="col-6">
                            <img src="https://via.placeholder.com/300x200/28a745/ffffff?text=CHIP" class="img-fluid rounded shadow" alt="Chip">
                        </div>
                        <div class="col-6">
                            <img src="https://via.placeholder.com/300x200/dc3545/ffffff?text=TOOL" class="img-fluid rounded shadow" alt="Tool">
                        </div>
                        <div class="col-6">
                            <img src="https://via.placeholder.com/300x200/ffc107/ffffff?text=CAR" class="img-fluid rounded shadow" alt="Car">
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
