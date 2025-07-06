<?php
/**
 * Mr ECU - Hakkımızda Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Hakkımızda';
$pageDescription = 'Mr ECU hakkında bilgi edinin. Profesyonel ECU hizmetleri sunan deneyimli ekibimizi tanıyın.';
$pageKeywords = 'hakkımızda, ECU uzmanları, deneyim, profesyonel ekip, otomotiv';

// Header include
include 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold mb-3">Hakkımızda</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item">
                                <a href="index.php" class="text-white-50 text-decoration-none">
                                    <i class="fas fa-home me-1"></i>Ana Sayfa
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Hakkımızda</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- About Content -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-5 fw-bold mb-4">Kimiz?</h2>
                    <p class="lead mb-4">
                        <?php echo SITE_NAME; ?> olarak, otomotiv ECU alanında 10+ yıllık deneyimi olan 
                        uzman bir ekibiz. Türkiye'nin önde gelen ECU hizmet sağlayıcılarından biri olarak, 
                        müşterilerimize en kaliteli ve güvenilir çözümleri sunuyoruz.
                    </p>
                    <p class="mb-4">
                        Modern teknolojilerle donatılmış tesisimizde, tüm marka ve modeller için ECU, TCU, 
                        immobilizer ve chip tuning hizmetleri veriyoruz. Güvenlik ve kalite bizim önceliğimizdir.
                    </p>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Profesyonel Ekip</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Güvenli Platform</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Hızlı Hizmet</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>7/24 Destek</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://via.placeholder.com/600x400/007bff/ffffff?text=MR+ECU+TEAM" 
                         class="img-fluid rounded shadow" alt="Mr ECU Ekibi">
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Değerlerimiz</h2>
                    <p class="lead text-muted">Çalışma prensiplerimiz ve değerlerimiz</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Güvenlik</h5>
                            <p class="card-text">
                                Müşteri bilgileri ve dosyalarının güvenliği bizim en önemli önceliğimizdir. 
                                Tüm veriler şifrelenmiş ortamda saklanır.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-star text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Kalite</h5>
                            <p class="card-text">
                                En yüksek kalite standartlarında hizmet vermek için sürekli kendimizi 
                                geliştiriyor ve yenilikleri takip ediyoruz.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-handshake text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Güven</h5>
                            <p class="card-text">
                                Müşterilerimizle uzun vadeli güven ilişkileri kurmayı hedefliyoruz. 
                                Şeffaf ve dürüst iletişim önceliğimizdir.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-clock text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Hız</h5>
                            <p class="card-text">
                                Zamanınızın değerli olduğunu biliyoruz. Bu nedenle hizmetlerimizi 
                                mümkün olan en kısa sürede teslim ediyoruz.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-cogs text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">İnovasyon</h5>
                            <p class="card-text">
                                Teknolojik gelişmeleri yakından takip ediyor ve en güncel 
                                yöntemlerle hizmet sunuyoruz.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-users text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Müşteri Odaklılık</h5>
                            <p class="card-text">
                                Müşteri memnuniyeti bizim başarı kriterimizdir. Her proje için 
                                özel çözümler geliştiriyoruz.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Ekibimiz</h2>
                    <p class="lead text-muted">Deneyimli ve uzman kadromuz</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm">
                        <img src="https://via.placeholder.com/300x300/6c757d/ffffff?text=TEAM+1" 
                             class="card-img-top" alt="Ekip Üyesi 1">
                        <div class="card-body text-center">
                            <h5 class="card-title">Ahmet Yılmaz</h5>
                            <p class="text-muted">Kurucu & ECU Uzmanı</p>
                            <p class="card-text">15+ yıl ECU deneyimi ile takımımızın lideri</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm">
                        <img src="https://via.placeholder.com/300x300/6c757d/ffffff?text=TEAM+2" 
                             class="card-img-top" alt="Ekip Üyesi 2">
                        <div class="card-body text-center">
                            <h5 class="card-title">Mehmet Kaya</h5>
                            <p class="text-muted">Chip Tuning Uzmanı</p>
                            <p class="card-text">Performans optimizasyonu konusunda uzman</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm">
                        <img src="https://via.placeholder.com/300x300/6c757d/ffffff?text=TEAM+3" 
                             class="card-img-top" alt="Ekip Üyesi 3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Fatma Demir</h5>
                            <p class="text-muted">Müşteri Hizmetleri</p>
                            <p class="card-text">7/24 müşteri desteği ve iletişim sorumlusu</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm">
                        <img src="https://via.placeholder.com/300x300/6c757d/ffffff?text=TEAM+4" 
                             class="card-img-top" alt="Ekip Üyesi 4">
                        <div class="card-body text-center">
                            <h5 class="card-title">Can Özkan</h5>
                            <p class="text-muted">Yazılım Geliştirici</p>
                            <p class="card-text">Platform geliştirme ve teknik altyapı uzmanı</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-bullseye text-primary me-3" style="font-size: 2rem;"></i>
                                <h3 class="card-title mb-0">Misyonumuz</h3>
                            </div>
                            <p class="card-text">
                                Otomotiv sektöründe ECU hizmetleri alanında en güvenilir ve kaliteli 
                                çözümleri sunarak, müşterilerimizin araçlarının performansını maksimuma 
                                çıkarmak ve teknolojik gelişmelerin öncüsü olmaktır.
                            </p>
                            <p class="card-text">
                                Güvenlik, kalite ve müşteri memnuniyetini ön planda tutarak, 
                                sektörde standartları belirleyen bir marka olmayı hedefliyoruz.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-eye text-primary me-3" style="font-size: 2rem;"></i>
                                <h3 class="card-title mb-0">Vizyonumuz</h3>
                            </div>
                            <p class="card-text">
                                Türkiye'nin ECU hizmetleri alanında lider markası olmak ve uluslararası 
                                pazarda da tanınan bir platform haline gelmektir. Teknolojik yenilikleri 
                                takip ederek, sürekli gelişen bir hizmet anlayışı benimser.
                            </p>
                            <p class="card-text">
                                Gelecekte elektrikli araçlar ve yeni nesil otonom sistemler için de 
                                çözümler geliştirerek, otomotiv teknolojilerinin her alanında hizmet 
                                vermeyi planlıyoruz.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="mb-3">Profesyonel ECU Hizmetlerimizden Yararlanın</h3>
                    <p class="mb-0">
                        Deneyimli ekibimiz ve güvenilir platformumuzla tanışın. 
                        Size en uygun çözümü bulalım.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Hemen Başlayın
                        </a>
                    <?php else: ?>
                        <a href="user/upload.php" class="btn btn-light btn-lg">
                            <i class="fas fa-upload me-2"></i>Dosya Yükleyin
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

<?php
// Footer include
include 'includes/footer.php';
?>
