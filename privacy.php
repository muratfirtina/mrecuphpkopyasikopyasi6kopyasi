<?php
/**
 * Gizlilik Politikası Sayfası
 * Design settings'dan dinamik içerik çeker
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Design ayarlarını al
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM design_settings WHERE setting_key IN ('privacy_policy_title', 'privacy_policy_content')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [
        'privacy_policy_title' => 'Gizlilik Politikası',
        'privacy_policy_content' => '<p>Gizlilik politikası henüz eklenmemiş.</p>'
    ];
}

// Sayfa bilgileri
$pageTitle = $settings['privacy_policy_title'] ?? 'Gizlilik Politikası';
$pageDescription = 'Mr ECU gizlilik politikası ve kişisel veri koruma uygulamaları';
$pageKeywords = 'gizlilik politikası, privacy policy, kişisel veri, Mr ECU, KVKK';

// Header include
include 'includes/header.php';
?>

<!-- Gizlilik Politikası İçeriği -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">
                            <i class="bi bi-house me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($pageTitle); ?>
                    </li>
                </ol>
            </nav>

            <!-- Ana İçerik -->
            <div class="legal-content bg-white p-5 rounded-4 shadow-sm">
                <!-- Başlık -->
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-success mb-3">
                        <i class="bi bi-shield-lock me-3"></i>
                        <?php echo htmlspecialchars($pageTitle); ?>
                    </h1>
                    <p class="lead text-muted">
                        Son güncelleme: <?php echo date('d.m.Y'); ?>
                    </p>
                </div>

                <!-- İçerik -->
                <div class="privacy-content">
                    <?php if (!empty($settings['privacy_policy_content'])): ?>
                        <div class="content-text lh-lg">
                            <?php echo $settings['privacy_policy_content']; ?>
                        </div>
                    <?php else: ?>
                        <!-- Varsayılan İçerik -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Bu sayfa henüz hazırlanmamış.</strong>
                            Gizlilik politikası içeriği yakında eklenecektir.
                        </div>
                        
                        <div class="content-text">
                            <h3>Kişisel Verilerin Toplanması</h3>
                            <p>Mr ECU olarak, size daha iyi hizmet verebilmek için bazı kişisel verilerinizi topluyoruz.</p>
                            
                            <h3>Veri Güvenliği</h3>
                            <p>Kişisel verileriniz en yüksek güvenlik önlemleri ile korunmaktadır.</p>
                            
                            <h3>Çerezler (Cookies)</h3>
                            <ul>
                                <li>Web sitemizde çerezler kullanılmaktadır</li>
                                <li>Çerezler, site deneyiminizi geliştirmek için kullanılır</li>
                                <li>Çerez ayarlarınızı tarayıcınızdan değiştirebilirsiniz</li>
                            </ul>
                            
                            <h3>Üçüncü Taraf Paylaşımı</h3>
                            <p>Kişisel verileriniz üçüncü taraflarla paylaşılmaz.</p>
                            
                            <h3>Veri Saklama Süresi</h3>
                            <p>Verileriniz yasal zorunluluklar çerçevesinde saklanır.</p>
                            
                            <h3>Haklarınız</h3>
                            <ul>
                                <li>Verilerinize erişim hakkı</li>
                                <li>Düzeltme hakkı</li>
                                <li>Silme hakkı</li>
                                <li>İtiraz etme hakkı</li>
                            </ul>
                            
                            <h3>İletişim</h3>
                            <p>Gizlilik ile ilgili sorularınız için <a href="contact.php">iletişim sayfamızı</a> kullanabilirsiniz.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- KVKK Uyarısı -->
                <div class="alert alert-warning border-0 mt-5">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        KVKK Uyarısı
                    </h6>
                    <p class="mb-0">
                        6698 sayılı Kişisel Verilerin Korunması Kanunu uyarınca, 
                        kişisel verilerinizin işlenmesi hakkında detaylı bilgi için 
                        <a href="kvkk.php" class="alert-link">KVKK Aydınlatma Metni</a>ni inceleyebilirsiniz.
                    </p>
                </div>

                <!-- Alt Bilgi -->
                <div class="border-top pt-4 mt-5">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="text-muted mb-0">
                                <i class="bi bi-calendar-check me-2"></i>
                                Yürürlük tarihi: <?php echo date('d.m.Y'); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="contact.php" class="btn btn-outline-success">
                                <i class="bi bi-envelope me-2"></i>
                                Gizlilik Konusunda İletişime Geçin
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İlgili Linkler -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="bi bi-shield-check text-primary mb-3" style="font-size: 2rem;"></i>
                            <h6 class="card-title">Kullanım Şartları</h6>
                            <p class="card-text text-muted">Hizmet kullanım koşulları hakkında</p>
                            <a href="terms.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-arrow-right me-1"></i>Kullanım Şartları
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="bi bi-info-circle text-info mb-3" style="font-size: 2rem;"></i>
                            <h6 class="card-title">KVKK Aydınlatma</h6>
                            <p class="card-text text-muted">Kişisel Verilerin Korunması Kanunu</p>
                            <a href="kvkk.php" class="btn btn-info btn-sm">
                                <i class="bi bi-arrow-right me-1"></i>KVKK Metni
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Özel Stil -->
<style>
    .legal-content {
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .content-text {
        font-size: 1.1rem;
        line-height: 1.8;
    }
    
    .content-text h1, .content-text h2, .content-text h3 {
        color: #071e3d;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    
    .content-text h1 {
        font-size: 2rem;
        border-bottom: 3px solid #28a745;
        padding-bottom: 0.5rem;
    }
    
    .content-text h2 {
        font-size: 1.5rem;
        border-bottom: 2px solid #007bff;
        padding-bottom: 0.3rem;
    }
    
    .content-text h3 {
        font-size: 1.3rem;
        color: #28a745;
    }
    
    .content-text ul, .content-text ol {
        margin: 1rem 0;
        padding-left: 2rem;
    }
    
    .content-text li {
        margin-bottom: 0.5rem;
    }
    
    .content-text p {
        margin-bottom: 1rem;
        text-align: justify;
    }
    
    .content-text a {
        color: #28a745;
        text-decoration: none;
    }
    
    .content-text a:hover {
        text-decoration: underline;
    }
    
    .breadcrumb {
        background: transparent;
        padding: 0;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .legal-content {
            padding: 2rem !important;
        }
        
        .content-text {
            font-size: 1rem;
        }
    }
</style>

<?php
// Footer include
include 'includes/footer.php';
?>
