<?php
/**
 * KVKK Aydınlatma Metni Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Sayfa bilgileri
$pageTitle = 'KVKK Aydınlatma Metni';
$pageDescription = 'Kişisel Verilerin Korunması Kanunu çerçevesinde aydınlatma metni';
$pageKeywords = 'KVKK, kişisel veri, aydınlatma metni, veri koruma, Mr ECU';

// Header include
include 'includes/header.php';
?>

<!-- KVKK İçeriği -->
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
                        KVKK Aydınlatma Metni
                    </li>
                </ol>
            </nav>

            <!-- Ana İçerik -->
            <div class="legal-content bg-white p-5 rounded-4 shadow-sm">
                <!-- Başlık -->
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-info mb-3">
                        <i class="bi bi-info-circle me-3"></i>
                        KVKK Aydınlatma Metni
                    </h1>
                    <p class="lead text-muted">
                        6698 Sayılı Kişisel Verilerin Korunması Kanunu
                    </p>
                    <p class="text-muted">
                        Son güncelleme: <?php echo date('d.m.Y'); ?>
                    </p>
                </div>

                <!-- İçerik -->
                <div class="kvkk-content">
                    <div class="content-text lh-lg">
                        <div class="alert alert-info border-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Bu aydınlatma metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu uyarınca hazırlanmıştır.
                        </div>

                        <h3>1. Veri Sorumlusu</h3>
                        <p><strong>Mr ECU</strong> olarak, kişisel verilerinizin korunmasına önem vermekteyiz.</p>

                        <h3>2. Kişisel Verilerin Hangi Amaçla İşlendiği</h3>
                        <p>Kişisel verileriniz aşağıdaki amaçlarla işlenmektedir:</p>
                        <ul>
                            <li>Hizmet sunumu ve müşteri ilişkileri yönetimi</li>
                            <li>İletişim faaliyetlerinin yürütülmesi</li>
                            <li>Müşteri memnuniyetinin ölçülmesi</li>
                            <li>Yasal yükümlülüklerin yerine getirilmesi</li>
                            <li>İş süreçlerinin yürütülmesi</li>
                        </ul>

                        <h3>3. Toplanan Kişisel Veri Türleri</h3>
                        <p>Aşağıdaki kişisel veri kategorileri işlenmektedir:</p>
                        <ul>
                            <li><strong>Kimlik Bilgileri:</strong> Ad, soyad, T.C. kimlik numarası</li>
                            <li><strong>İletişim Bilgileri:</strong> Telefon, e-posta, adres</li>
                            <li><strong>Müşteri İşlem Bilgileri:</strong> Hizmet geçmişi, tercihler</li>
                            <li><strong>Finansal Bilgiler:</strong> Ödeme bilgileri, fatura bilgileri</li>
                        </ul>

                        <h3>4. Kişisel Verilerin Toplanma Yöntemi</h3>
                        <p>Kişisel verileriniz aşağıdaki yöntemlerle toplanmaktadır:</p>
                        <ul>
                            <li>Web sitesi üzerinden</li>
                            <li>Telefon görüşmeleri sırasında</li>
                            <li>E-posta yazışmaları ile</li>
                            <li>Fiziksel ortamda yapılan başvurular ile</li>
                        </ul>

                        <h3>5. İşlenen Kişisel Verilerin Kimlere ve Hangi Amaçla Aktarılabileceği</h3>
                        <p>Kişisel verileriniz aşağıdaki durumlarda üçüncü kişilerle paylaşılabilir:</p>
                        <ul>
                            <li>Yasal yükümlülük gereği kamu kurumları ile</li>
                            <li>Hizmet sağlayıcıları ile (teknik destek, kargo vb.)</li>
                            <li>İş ortakları ile (sözleşme gereği)</li>
                        </ul>

                        <h3>6. Kişisel Veri Toplamanın Yöntemi ve Hukuki Sebebi</h3>
                        <p>Kişisel verileriniz, 6698 sayılı KVKK'nın 5. maddesinde belirtilen:</p>
                        <ul>
                            <li>Açık rızanız ile</li>
                            <li>Sözleşmenin kurulması ve ifası için</li>
                            <li>Yasal yükümlülük gereği</li>
                            <li>Meşru menfaat gereği</li>
                        </ul>
                        <p>hukuki sebepler dahilinde işlenmektedir.</p>

                        <h3>7. Kişisel Veri Sahibinin Hakları</h3>
                        <p>6698 sayılı KVKK'nın 11. maddesi uyarınca haklarınız:</p>
                        <ul>
                            <li>Kişisel veri işlenip işlenmediğini öğrenme</li>
                            <li>Kişisel verileri işlenmişse buna ilişkin bilgi talep etme</li>
                            <li>Kişisel verilerin işlenme amacını ve bunların amacına uygun kullanılıp kullanılmadığını öğrenme</li>
                            <li>Yurt içinde veya yurt dışında kişisel verilerin aktarıldığı üçüncü kişileri bilme</li>
                            <li>Kişisel verilerin eksik veya yanlış işlenmiş olması hâlinde bunların düzeltilmesini isteme</li>
                            <li>Kişisel verilerin silinmesini veya yok edilmesini isteme</li>
                            <li>Düzeltme, silme ve yok etme işlemlerinin üçüncü kişilere bildirilmesini isteme</li>
                            <li>İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle kişinin aleyhine bir sonucun ortaya çıkmasına itiraz etme</li>
                            <li>Kişisel verilerin kanuna aykırı olarak işlenmesi sebebiyle zarara uğraması hâlinde zararın giderilmesini talep etme</li>
                        </ul>

                        <h3>8. Veri Güvenliği</h3>
                        <p>Kişisel verilerinizin güvenliği için:</p>
                        <ul>
                            <li>Teknik güvenlik önlemleri alınmaktadır</li>
                            <li>İdari güvenlik önlemleri uygulanmaktadır</li>
                            <li>Fiziksel güvenlik önlemleri mevcuttur</li>
                            <li>Düzenli güvenlik denetimleri yapılmaktadır</li>
                        </ul>

                        <h3>9. Veri Saklama Süresi</h3>
                        <p>Kişisel verileriniz:</p>
                        <ul>
                            <li>İşleme amacının gerektirdiği süre boyunca</li>
                            <li>Yasal saklama yükümlülüğü süresince</li>
                            <li>Zamanaşımı süreleri boyunca</li>
                        </ul>
                        <p>saklanacak olup, süre sonunda güvenli şekilde silinecektir.</p>

                        <h3>10. İletişim</h3>
                        <p>KVKK kapsamındaki tüm başvurularınızı aşağıdaki kanallardan yapabilirsiniz:</p>
                        <ul>
                            <li><strong>E-posta:</strong> <a href="mailto:info@mrecutuning.com">info@mrecutuning.com</a></li>
                            <li><strong>Posta:</strong> İstanbul, Türkiye</li>
                            <li><strong>İletişim Formu:</strong> <a href="contact.php">Web sitesi iletişim formu</a></li>
                        </ul>

                        <div class="alert alert-warning border-0 mt-4">
                            <h6 class="alert-heading">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Önemli Not
                            </h6>
                            <p class="mb-0">
                                Bu aydınlatma metni, yasal değişiklikler veya iş süreçlerindeki değişiklikler nedeniyle güncellenebilir. 
                                Güncellemeler web sitemizde yayınlanacaktır.
                            </p>
                        </div>
                    </div>
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
                            <a href="contact.php" class="btn btn-outline-info">
                                <i class="bi bi-envelope me-2"></i>
                                KVKK Başvurusu Yap
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
                            <i class="bi bi-shield-lock text-success mb-3" style="font-size: 2rem;"></i>
                            <h6 class="card-title">Gizlilik Politikası</h6>
                            <p class="card-text text-muted">Kişisel verilerinizin korunması hakkında</p>
                            <a href="privacy.php" class="btn btn-success btn-sm">
                                <i class="bi bi-arrow-right me-1"></i>Gizlilik Politikası
                            </a>
                        </div>
                    </div>
                </div>
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
        border-bottom: 3px solid #17a2b8;
        padding-bottom: 0.5rem;
    }
    
    .content-text h2 {
        font-size: 1.5rem;
        border-bottom: 2px solid #007bff;
        padding-bottom: 0.3rem;
    }
    
    .content-text h3 {
        font-size: 1.3rem;
        color: #17a2b8;
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
        color: #17a2b8;
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
