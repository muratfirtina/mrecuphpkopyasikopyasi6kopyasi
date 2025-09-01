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

    <!-- Hero Section Slider -->
    <section class="hero-slider">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
            <!-- Slide Indicators -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="4"></button>
            </div>

            <!-- Carousel Slides -->
            <div class="carousel-inner">
                <!-- Slide 1: ECU Programlama -->
                <div class="carousel-item active">
                    <div class="hero-slide" style="background: linear-gradient(rgba(44, 62, 80, 0.8), rgba(142, 68, 173, 0.8)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=1080&fit=crop') center/cover; height: 100vh; position: relative;">
                        <div class="container py-5 h-100">
                            <div class="row align-items-center text-white h-100">
                                <div class="col-lg-8">
                                    <h1 class="display-3 fw-bold mb-3 slide-title">Profesyonel ECU Programlama</h1>
                                    <h2 class="display-5 fw-bold mb-4" style="background: linear-gradient(45deg, #e91c1cff, #fd6060ff, #ff5261ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                        <span id="typewriter-text">Optimize Edin</span><span class="typewriter-cursor">|</span>
                                    </h2>
                                    <p class="lead mb-4">
                                        Magic Motorsport FLEX, Alientech KESS3, AutoTuner ve Launch anza 
                                        tespit cihazları. Kaliteli yazılım tecrübemiz ve dosya sistemimizle işinizi 
                                        büyütün.
                                    </p>
                                    <div class="d-flex gap-3 mb-5">
                                        <a href="#devices" class="btn btn-danger btn-lg px-4">
                                            <i class="bi bi-search me-2"></i>Cihazları İncele
                                        </a>
                                        <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                            <a href="user/upload.php" class="btn btn-outline-light btn-lg px-4">
                                                <i class="bi bi-upload me-2"></i>Dosya Yükle
                                            </a>
                                        <?php else: ?>
                                            <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                                                <i class="bi bi-upload me-2"></i>Dosya Yükle
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-center">
                                        <i class="bi bi-microchip" style="font-size: 10rem; opacity: 0.2;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Chip Tuning -->
                <div class="carousel-item">
                    <div class="hero-slide" style="background: linear-gradient(rgba(231, 76, 60, 0.8), rgba(192, 57, 43, 0.8)), url('https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=1920&h=1080&fit=crop') center/cover; height: 100vh; position: relative;">
                        <div class="container py-5 h-100">
                            <div class="row align-items-center text-white h-100">
                                <div class="col-lg-8">
                                    <h1 class="display-3 fw-bold mb-3 slide-title">Yüksek Performans</h1>
                                    <h2 class="display-5 fw-bold mb-4" style="background: linear-gradient(45deg, #ff6b35, #f7931e, #ff4757); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                        Chip Tuning
                                    </h2>
                                    <p class="lead mb-4">
                                        Aracınızın motor performansını maksimuma çıkarın. Güvenli ve profesyonel 
                                        chip tuning hizmetimizle güç ve tork artışı sağlayın.
                                    </p>
                                    <div class="d-flex gap-3 mb-5">
                                        <a href="#services" class="btn btn-warning btn-lg px-4">
                                            <i class="bi bi-tachometer-alt me-2"></i>Performans Artışı
                                        </a>
                                        <a href="contact.php" class="btn btn-outline-light btn-lg px-4">
                                            <i class="bi bi-phone me-2"></i>Bilgi Alın
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-center">
                                        <i class="bi bi-tachometer-alt" style="font-size: 10rem; opacity: 0.2;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 3: Immobilizer -->
                <div class="carousel-item">
                    <div class="hero-slide" style="background: linear-gradient(rgba(39, 174, 96, 0.8), rgba(22, 160, 133, 0.8)), url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=1920&h=1080&fit=crop') center/cover; height: 100vh; position: relative;">
                        <div class="container py-5 h-100">
                            <div class="row align-items-center text-white h-100">
                                <div class="col-lg-8">
                                    <h1 class="display-3 fw-bold mb-3 slide-title">Güvenlik Sistemleri</h1>
                                    <h2 class="display-5 fw-bold mb-4" style="background: linear-gradient(45deg, #2ecc71, #27ae60, #16a085); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                        Immobilizer Çözümleri
                                    </h2>
                                    <p class="lead mb-4">
                                        Anahtar programlama, immobilizer bypass ve güvenlik sistemi 
                                        çözümleri. Uzman ekibimizle tüm marka ve modeller desteklenir.
                                    </p>
                                    <div class="d-flex gap-3 mb-5">
                                        <a href="#security" class="btn btn-success btn-lg px-4">
                                            <i class="bi bi-key me-2"></i>Güvenlik Çözümleri
                                        </a>
                                        <a href="about.php" class="btn btn-outline-light btn-lg px-4">
                                            <i class="bi bi-info-circle me-2"></i>Detaylar
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-center">
                                        <i class="bi bi-key" style="font-size: 10rem; opacity: 0.2;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 4: TCU Yazılımları -->
                <div class="carousel-item">
                    <div class="hero-slide" style="background: linear-gradient(rgba(155, 89, 182, 0.8), rgba(142, 68, 173, 0.8)), url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1920&h=1080&fit=crop') center/cover; height: 100vh; position: relative;">
                        <div class="container py-5 h-100">
                            <div class="row align-items-center text-white h-100">
                                <div class="col-lg-8">
                                    <h1 class="display-3 fw-bold mb-3 slide-title">Şanzıman Kontrolü</h1>
                                    <h2 class="display-5 fw-bold mb-4" style="background: linear-gradient(45deg, #9b59b6, #8e44ad, #663399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                        TCU Yazılımları
                                    </h2>
                                    <p class="lead mb-4">
                                        Şanzıman kontrol ünitesi yazılımları ile vites geçiş performansını 
                                        optimize edin. Daha yumuşak ve hızlı vites değişimleri.
                                    </p>
                                    <div class="d-flex gap-3 mb-5">
                                        <a href="#transmission" class="btn btn-info btn-lg px-4">
                                            <i class="bi bi-gear-wide-connected me-2"></i>TCU Hizmetleri
                                        </a>
                                        <a href="services.php" class="btn btn-outline-light btn-lg px-4">
                                            <i class="bi bi-list me-2"></i>Tüm Hizmetler
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-center">
                                        <i class="bi bi-gear-wide-connected" style="font-size: 10rem; opacity: 0.2;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 5: 7/24 Destek -->
                <div class="carousel-item">
                    <div class="hero-slide" style="background: linear-gradient(rgba(52, 73, 94, 0.8), rgba(44, 62, 80, 0.8)), url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?w=1920&h=1080&fit=crop') center/cover; height: 100vh; position: relative;">
                        <div class="container py-5 h-100">
                            <div class="row align-items-center text-white h-100">
                                <div class="col-lg-8">
                                    <h1 class="display-3 fw-bold mb-3 slide-title">Kesintisiz Hizmet</h1>
                                    <h2 class="display-5 fw-bold mb-4" style="background: linear-gradient(45deg, #3498db, #2980b9, #1f4e79); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                        7/24 Teknik Destek
                                    </h2>
                                    <p class="lead mb-4">
                                        Uzman ekibimiz 7 gün 24 saat hizmetinizde. Acil durumlarınızda 
                                        anında çözüm üretiyoruz. Güvenilir ve hızlı destek garantisi.
                                    </p>
                                    <div class="d-flex gap-3 mb-5">
                                        <a href="contact.php" class="btn btn-primary btn-lg px-4">
                                            <i class="bi bi-headset me-2"></i>Hemen İletişim
                                        </a>
                                        <a href="#contact" class="btn btn-outline-light btn-lg px-4">
                                            <i class="bi bi-clock me-2"></i>7/24 Destek
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-center">
                                        <i class="bi bi-headset" style="font-size: 10rem; opacity: 0.2;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                                <i class="bi bi-shield-alt text-primary" style="font-size: 3rem;"></i>
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
                                <i class="bi bi-clock text-primary" style="font-size: 3rem;"></i>
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
                                <i class="bi bi-users text-primary" style="font-size: 3rem;"></i>
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
                        <i class="bi bi-microchip text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>ECU Yazılımları</h5>
                        <p class="text-muted">Motor kontrol ünitesi yazılım düzenlemeleri</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="bi bi-gear-wide-connected text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>TCU Yazılımları</h5>
                        <p class="text-muted">Şanzıman kontrol ünitesi düzenlemeleri</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="bi bi-key text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Immobilizer</h5>
                        <p class="text-muted">İmmobilizer ve anahtar programlama</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="bi bi-tachometer-alt text-primary mb-3" style="font-size: 2.5rem;"></i>
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
                        <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>10+ yıl deneyim</li>
                        <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Profesyonel ekip</li>
                        <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>7/24 teknik destek</li>
                        <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Güncel teknoloji</li>
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
                        <i class="bi bi-envelope text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Email</h5>
                        <p class="text-muted"><?php echo SITE_EMAIL; ?></p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-info text-center p-4">
                        <i class="bi bi-phone text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>Telefon</h5>
                        <p class="text-muted">+90 (555) 123 45 67</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-info text-center p-4">
                        <i class="bi bi-map-marker-alt text-primary mb-3" style="font-size: 2.5rem;"></i>
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

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}

#typewriter-text {
    display: inline-block;
    min-width: 250px;
    text-align: left;
}

/* Hero Slider Styles */
.hero-slider {
    position: relative;
    overflow: hidden;
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

.device-cards-overlay {
    max-width: 800px;
}

.device-card {
    transition: all 0.3s ease;
    border-radius: 15px;
    backdrop-filter: blur(20px);
}

.device-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
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

.gradient-text {
    background: linear-gradient(45deg, #e91c1cff, #fd6060ff, #ff5261ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hero-slide {
        height: 80vh !important;
    }
    
    .display-3 {
        font-size: 2.5rem;
    }
    
    .display-5 {
        font-size: 2rem;
    }
    
    .device-cards-overlay {
        position: relative !important;
        transform: none !important;
        margin-top: 2rem;
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
</style>

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
    const words = ['Optimize Edin', 'Güçlendirin', 'Geliştirin'];
    
    if (typeWriterElement) {
        new TypeWriter(typeWriterElement, words, 2000);
    }
    
    // Initialize carousel with custom settings
    const heroCarousel = document.querySelector('#heroCarousel');
    if (heroCarousel) {
        // Show typewriter only on first slide
        heroCarousel.addEventListener('slid.bs.carousel', function (event) {
            const typewriterEl = document.querySelector('#typewriter-text');
            const cursorEl = document.querySelector('.typewriter-cursor');
            
            if (event.to === 0) {
                // First slide - show typewriter
                if (typewriterEl) typewriterEl.style.display = 'inline-block';
                if (cursorEl) cursorEl.style.display = 'inline-block';
            } else {
                // Other slides - hide typewriter
                if (typewriterEl) typewriterEl.style.display = 'none';
                if (cursorEl) cursorEl.style.display = 'none';
            }
        });
    }
});
</script>
