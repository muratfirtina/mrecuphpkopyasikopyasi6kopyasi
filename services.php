<?php
/**
 * Mr ECU - Hizmetler Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Hizmetlerimiz';
$pageDescription = 'Profesyonel ECU hizmetlerimizi keşfedin. ECU tuning, chip tuning, immobilizer ve daha fazlası.';
$pageKeywords = 'ECU tuning, chip tuning, immobilizer, TCU tuning, DPF off, EGR off, AdBlue off';

// Header include
include 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="bg-gradient-primary text-white py-5">
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
                                    <i class="fas fa-home me-1"></i>Ana Sayfa
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Hizmetler</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-cogs" style="font-size: 8rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Ana Hizmetler -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- ECU Tuning -->
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-lg service-detail-card">
                        <div class="card-header bg-primary text-white text-center py-4">
                            <i class="fas fa-microchip fa-3x mb-3"></i>
                            <h3 class="mb-0">ECU Tuning / Chip Tuning</h3>
                        </div>
                        <div class="card-body p-4">
                            <p class="lead mb-4">
                                Motor kontrol ünitesi (ECU) yazılımınızı optimize ederek 
                                aracınızın gücünü ve performansını artırın.
                            </p>
                            
                            <h5 class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Sağladığı Faydalar:
                            </h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">
                                    <i class="fas fa-arrow-up text-success me-2"></i>
                                    %15-25 güç artışı
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-arrow-up text-success me-2"></i>
                                    %20-30 tork artışı
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-gas-pump text-info me-2"></i>
                                    %5-15 yakıt tasarrufu
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-tachometer-alt text-warning me-2"></i>
                                    Daha iyi gaz tepkisi
                                </li>
                            </ul>
                            
                            <div class="pricing-info bg-light p-3 rounded mb-3">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="mb-1">Hizmet Ücreti</h6>
                                        <small class="text-muted">Profesyonel işlem garantili</small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <h4 class="text-primary mb-0">5 TL</h4>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Not:</strong> Tüm marka ve modeller için uygulanabilir.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TCU Tuning -->
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-lg service-detail-card">
                        <div class="card-header bg-success text-white text-center py-4">
                            <i class="fas fa-cogs fa-3x mb-3"></i>
                            <h3 class="mb-0">TCU Tuning</h3>
                        </div>
                        <div class="card-body p-4">
                            <p class="lead mb-4">
                                Şanzıman kontrol ünitesi (TCU) optimizasyonu ile 
                                vites geçiş performansını geliştirin.
                            </p>
                            
                            <h5 class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Sağladığı Faydalar:
                            </h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">
                                    <i class="fas fa-stopwatch text-primary me-2"></i>
                                    Daha hızlı vites geçişi
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-cog text-info me-2"></i>
                                    Optimize edilmiş şanzıman davranışı
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-chart-line text-success me-2"></i>
                                    Gelişmiş performans
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-wrench text-warning me-2"></i>
                                    Manual mod iyileştirmeleri
                                </li>
                            </ul>
                            
                            <div class="pricing-info bg-light p-3 rounded mb-3">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="mb-1">Hizmet Ücreti</h6>
                                        <small class="text-muted">Uzman TCU optimizasyonu</small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <h4 class="text-success mb-0">7 TL</h4>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <small>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Uyarı:</strong> Sadece otomatik şanzımanlı araçlar için geçerlidir.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İmmobilizer -->
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-lg service-detail-card">
                        <div class="card-header bg-warning text-dark text-center py-4">
                            <i class="fas fa-key fa-3x mb-3"></i>
                            <h3 class="mb-0">İmmobilizer Kaldırma</h3>
                        </div>
                        <div class="card-body p-4">
                            <p class="lead mb-4">
                                Anahtar kaybı veya immobilizer arızası durumunda 
                                sistemi güvenli şekilde devre dışı bırakın.
                            </p>
                            
                            <h5 class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Ne Zaman Gerekir:
                            </h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">
                                    <i class="fas fa-key text-danger me-2"></i>
                                    Anahtar kaybı durumunda
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    İmmobilizer arızası
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-tools text-info me-2"></i>
                                    ECU değişimi sonrası
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-car text-primary me-2"></i>
                                    İkinci el ECU takılması
                                </li>
                            </ul>
                            
                            <div class="pricing-info bg-light p-3 rounded mb-3">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="mb-1">Hizmet Ücreti</h6>
                                        <small class="text-muted">Hızlı ve güvenli çözüm</small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <h4 class="text-warning mb-0">3 TL</h4>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-danger">
                                <small>
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <strong>Güvenlik:</strong> Sadece yasal durumlar için uygulanır.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DPF/EGR/AdBlue Off -->
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-lg service-detail-card">
                        <div class="card-header bg-info text-white text-center py-4">
                            <i class="fas fa-wrench fa-3x mb-3"></i>
                            <h3 class="mb-0">DPF / EGR / AdBlue Off</h3>
                        </div>
                        <div class="card-body p-4">
                            <p class="lead mb-4">
                                Emisyon sistemi problemleri için profesyonel 
                                yazılım çözümleri sunuyoruz.
                            </p>
                            
                            <h5 class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Hangi Sistemler:
                            </h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">
                                    <i class="fas fa-filter text-primary me-2"></i>
                                    DPF (Diesel Partikül Filtresi)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-recycle text-success me-2"></i>
                                    EGR (Egzoz Gazı Geri Devir)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-tint text-info me-2"></i>
                                    AdBlue / SCR Sistemi
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                    Lambda Sensör Devre Dışı
                                </li>
                            </ul>
                            
                            <div class="pricing-info bg-light p-3 rounded mb-3">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="mb-1">Hizmet Ücreti</h6>
                                        <small class="text-muted">Sistem başına fiyatlandırma</small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <h4 class="text-info mb-0">4 TL</h4>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <small>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Önemli:</strong> Çevre mevzuatlarına uygun kullanım sorumluluğu size aittir.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Süreç Adımları -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Nasıl Çalışır?</h2>
                    <p class="lead text-muted">4 basit adımda profesyonel hizmet alın</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="process-step-icon mx-auto mb-3">
                            <span class="step-number">1</span>
                            <i class="fas fa-upload"></i>
                        </div>
                        <h5>Dosya Yükle</h5>
                        <p class="text-muted">
                            ECU dosyanızı güvenli platformumuza yükleyin. 
                            Tüm yaygın formatları destekliyoruz.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="process-step-icon mx-auto mb-3">
                            <span class="step-number">2</span>
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h5>Uzman İncelemesi</h5>
                        <p class="text-muted">
                            Deneyimli teknisyenlerimiz dosyanızı inceler 
                            ve gerekli optimizasyonları yapar.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="process-step-icon mx-auto mb-3">
                            <span class="step-number">3</span>
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5>Kalite Kontrolü</h5>
                        <p class="text-muted">
                            İşlenmiş dosya kapsamlı testlerden geçer 
                            ve kalite kontrolünden onay alır.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="process-step-icon mx-auto mb-3">
                            <span class="step-number">4</span>
                            <i class="fas fa-download"></i>
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

    <!-- Desteklenen Markalar -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Desteklenen Markalar</h2>
                    <p class="lead text-muted">50+ marka ve 1000+ model için hizmet veriyoruz</p>
                </div>
            </div>
            
            <div class="row g-4 text-center">
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">BMW</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Mercedes</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Audi</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Volkswagen</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Ford</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Opel</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Renault</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Peugeot</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Fiat</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Toyota</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">Honda</h5>
                        <small class="text-muted">Tüm Modeller</small>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="brand-item p-3">
                        <h5 class="text-primary">+40 Daha</h5>
                        <small class="text-muted">Tüm Markalar</small>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <div class="alert alert-info d-inline-block">
                    <i class="fas fa-info-circle me-2"></i>
                    Markanız listede yok mu? <strong>Sorun değil!</strong> 
                    Dosyanızı yükleyin, uyumluluğunu kontrol edelim.
                </div>
            </div>
        </div>
    </section>

    <!-- SSS -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Sıkça Sorulan Sorular</h2>
                    <p class="lead text-muted">Merak ettiğiniz konularda yanıtlar</p>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1">
                                    ECU tuning güvenli midir?
                                </button>
                            </h2>
                            <div id="faqCollapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Evet, profesyonel olarak yapıldığında ECU tuning tamamen güvenlidir. 
                                    Uzman ekibimiz her dosyayı dikkatli şekilde inceler ve motor parametrelerini 
                                    güvenli sınırlar içinde optimize eder. Ayrıca orijinal dosyanızın yedeğini 
                                    her zaman saklarız.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2">
                                    İşlem süresi ne kadar?
                                </button>
                            </h2>
                            <div id="faqCollapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Normal şartlarda dosyalarınız 2-24 saat içinde işlenir. Acil durumlar için 
                                    öncelikli işlem hizmeti de sunuyoruz. İşlem tamamlandığında size e-posta 
                                    bildirimi gönderilir ve dosyanızı indirebilirsiniz.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3">
                                    Hangi dosya formatlarını destekliyorsunuz?
                                </button>
                            </h2>
                            <div id="faqCollapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    .bin, .hex, .a2l, .kp, .ori, .mod, .ecu, .tun formatlarını destekliyoruz. 
                                    Bu formatlar dışında bir dosyanız varsa, lütfen destek ekibimizle iletişime 
                                    geçin. Çoğu durumda özel formatlar için de çözüm bulabiliriz.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4">
                                    Garanti veriyor musunuz?
                                </button>
                            </h2>
                            <div id="faqCollapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Evet! Tüm hizmetlerimiz için %100 memnuniyet garantisi veriyoruz. 
                                    Eğer sonuçtan memnun kalmazsanız, ücretsiz revizyon yapıyoruz. 
                                    Ayrıca her dosya için orijinal yedek tutarak geri dönüş imkanı sağlıyoruz.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5">
                                    Teknik destek alabilir miyim?
                                </button>
                            </h2>
                            <div id="faqCollapse5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Tabii ki! 7/24 teknik destek ekibimiz her zaman yanınızda. 
                                    E-posta, telefon ve WhatsApp üzerinden bizimle iletişime geçebilirsiniz. 
                                    Deneyimli teknisyenlerimiz tüm sorularınızı yanıtlamaya hazır.
                                </div>
                            </div>
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
                                <i class="fas fa-user-plus me-2"></i>Ücretsiz Kayıt Ol
                            </a>
                            <a href="login.php" class="btn btn-outline-light">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                        <?php else: ?>
                            <a href="user/upload.php" class="btn btn-light btn-lg">
                                <i class="fas fa-upload me-2"></i>Dosya Yükle
                            </a>
                            <a href="user/" class="btn btn-outline-light">
                                <i class="fas fa-tachometer-alt me-2"></i>Panel
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
// Sayfa özel CSS
$additionalCSS = [];

// Sayfa özel JavaScript
$pageJS = "
// Service detail card hover effects
document.querySelectorAll('.service-detail-card').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px)';
        this.style.transition = 'all 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Process step animation
const observeElements = document.querySelectorAll('.process-step-icon');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeInUp 0.6s ease';
        }
    });
});

observeElements.forEach(el => observer.observe(el));

// Brand items hover effect
document.querySelectorAll('.brand-item').forEach(function(item) {
    item.addEventListener('mouseenter', function() {
        this.style.backgroundColor = '#f8f9fa';
        this.style.borderRadius = '0.5rem';
        this.style.transform = 'scale(1.05)';
        this.style.transition = 'all 0.3s ease';
    });
    
    item.addEventListener('mouseleave', function() {
        this.style.backgroundColor = 'transparent';
        this.style.transform = 'scale(1)';
    });
});

// Smooth scroll for internal links
document.querySelectorAll('a[href^=\"#\"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
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
";

// CSS ekleme
echo '<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.service-detail-card {
    transition: all 0.3s ease;
}

.service-detail-card:hover {
    transform: translateY(-10px);
}

.process-step-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    color: white;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.process-step-icon .step-number {
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
    font-size: 1rem;
}

.brand-item {
    transition: all 0.3s ease;
    cursor: pointer;
}

.pricing-info {
    border-left: 4px solid #007bff;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.accordion-button:not(.collapsed) {
    background-color: #007bff;
    color: white;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
}

@media (max-width: 768px) {
    .process-step-icon {
        width: 80px;
        height: 80px;
        font-size: 1.5rem;
    }
    
    .display-4 {
        font-size: 2rem;
    }
    
    .service-detail-card {
        margin-bottom: 2rem;
    }
}
</style>';

// Footer include
include 'includes/footer.php';
?>
