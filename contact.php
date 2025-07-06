<?php
/**
 * Mr ECU - İletişim Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'İletişim';
$pageDescription = 'Mr ECU ile iletişime geçin. 7/24 destek, profesyonel hizmet ve hızlı çözümler için bizimle iletişime geçin.';
$pageKeywords = 'iletişim, destek, yardım, telefon, email, adres, 7/24 destek';

$error = '';
$success = '';

// İletişim formu gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Basit doğrulama
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Lütfen tüm zorunlu alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        // Gerçek uygulamada burada e-posta gönderimi yapılacak
        try {
            // Veritabanına mesajı kaydet (isteğe bağlı)
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            $success = 'Mesajınız başarıyla gönderildi. En kısa sürede size geri dönüş yapacağız.';
            
            // Form verilerini temizle
            unset($_POST);
        } catch(PDOException $e) {
            // E-posta gönderimi simülasyonu
            $success = 'Mesajınız alındı. 24 saat içinde size geri dönüş yapacağız.';
        }
    }
}

// Header include
include 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">İletişim</h1>
                    <p class="lead mb-4">
                        Sorularınız mı var? Yardıma mı ihtiyacınız var? 
                        7/24 uzman ekibimiz size yardımcı olmaya hazır.
                    </p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="index.php" class="text-white-50 text-decoration-none">
                                    <i class="fas fa-home me-1"></i>Ana Sayfa
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">İletişim</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-headset" style="font-size: 8rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- İletişim Bilgileri -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm contact-card">
                        <div class="card-body text-center p-4">
                            <div class="contact-icon mb-4">
                                <i class="fas fa-phone fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Telefon Desteği</h5>
                            <p class="card-text text-muted mb-3">
                                7/24 telefon desteği alın. Uzman ekibimiz her zaman yanınızda.
                            </p>
                            <div class="contact-details">
                                <h6 class="text-primary mb-2">+90 (555) 123 45 67</h6>
                                <small class="text-muted">Pazartesi - Pazar | 24 Saat</small>
                            </div>
                            <div class="mt-3">
                                <a href="tel:+905551234567" class="btn btn-outline-primary">
                                    <i class="fas fa-phone me-2"></i>Hemen Ara
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm contact-card">
                        <div class="card-body text-center p-4">
                            <div class="contact-icon mb-4">
                                <i class="fas fa-envelope fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">E-posta Desteği</h5>
                            <p class="card-text text-muted mb-3">
                                Detaylı sorularınız için e-posta gönderin. 2 saat içinde yanıt alın.
                            </p>
                            <div class="contact-details">
                                <h6 class="text-success mb-2"><?php echo SITE_EMAIL; ?></h6>
                                <small class="text-muted">Ortalama yanıt süresi: 2 saat</small>
                            </div>
                            <div class="mt-3">
                                <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-outline-success">
                                    <i class="fas fa-envelope me-2"></i>E-posta Gönder
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm contact-card">
                        <div class="card-body text-center p-4">
                            <div class="contact-icon mb-4">
                                <i class="fab fa-whatsapp fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">WhatsApp Desteği</h5>
                            <p class="card-text text-muted mb-3">
                                Anlık destek için WhatsApp'tan yazın. Hızlı ve pratik çözümler.
                            </p>
                            <div class="contact-details">
                                <h6 class="text-info mb-2">+90 (555) 123 45 67</h6>
                                <small class="text-muted">7/24 Aktif | Anlık Yanıt</small>
                            </div>
                            <div class="mt-3">
                                <a href="https://wa.me/905551234567?text=Merhaba,%20ECU%20hizmetleri%20hakkında%20bilgi%20almak%20istiyorum." 
                                   target="_blank" class="btn btn-outline-info">
                                    <i class="fab fa-whatsapp me-2"></i>WhatsApp'ta Yaz
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- İletişim Formu -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-primary text-white text-center py-4">
                            <h3 class="mb-0">
                                <i class="fas fa-paper-plane me-2"></i>
                                Bize Mesaj Gönderin
                            </h3>
                            <p class="mb-0 mt-2 opacity-75">Formunu doldurarak bizimle iletişime geçebilirsiniz</p>
                        </div>
                        
                        <div class="card-body p-5">
                            <!-- Hata/Başarı Mesajları -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo $success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$success): ?>
                                <form method="POST" id="contactForm" data-loading="true">
                                    <input type="hidden" name="send_message" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">
                                                    <i class="fas fa-user me-1"></i>
                                                    Ad Soyad <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                                       placeholder="Adınız ve soyadınız" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    E-posta Adresi <span class="text-danger">*</span>
                                                </label>
                                                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                                       placeholder="ornek@email.com" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">
                                                    <i class="fas fa-phone me-1"></i>
                                                    Telefon Numarası
                                                </label>
                                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone" 
                                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                                       placeholder="+90 (555) 123 45 67">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="subject" class="form-label">
                                                    <i class="fas fa-tag me-1"></i>
                                                    Konu <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select form-select-lg" id="subject" name="subject" required>
                                                    <option value="">Konu seçiniz...</option>
                                                    <option value="Genel Bilgi" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Genel Bilgi') ? 'selected' : ''; ?>>Genel Bilgi</option>
                                                    <option value="ECU Tuning" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'ECU Tuning') ? 'selected' : ''; ?>>ECU Tuning</option>
                                                    <option value="Chip Tuning" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Chip Tuning') ? 'selected' : ''; ?>>Chip Tuning</option>
                                                    <option value="İmmobilizer" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'İmmobilizer') ? 'selected' : ''; ?>>İmmobilizer</option>
                                                    <option value="DPF/EGR Off" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'DPF/EGR Off') ? 'selected' : ''; ?>>DPF/EGR Off</option>
                                                    <option value="Teknik Destek" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Teknik Destek') ? 'selected' : ''; ?>>Teknik Destek</option>
                                                    <option value="Faturalama" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Faturalama') ? 'selected' : ''; ?>>Faturalama</option>
                                                    <option value="Şikayet" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Şikayet') ? 'selected' : ''; ?>>Şikayet</option>
                                                    <option value="Öneri" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Öneri') ? 'selected' : ''; ?>>Öneri</option>
                                                    <option value="Diğer" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="message" class="form-label">
                                            <i class="fas fa-comment me-1"></i>
                                            Mesajınız <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="message" name="message" rows="6" 
                                                  placeholder="Lütfen mesajınızı detaylı olarak yazın..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                        <div class="form-text">Minimum 10 karakter gereklidir.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="privacy" name="privacy" required>
                                            <label class="form-check-label" for="privacy">
                                                <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#privacyModal">
                                                    Gizlilik politikası
                                                </a>nı okudum ve kabul ediyorum. <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg" data-original-text="Mesajı Gönder">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            Mesajı Gönder
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ofis Bilgileri -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-5 fw-bold mb-4">Ofisimizi Ziyaret Edin</h2>
                    <p class="lead mb-4">
                        İstanbul merkezindeki modern ofisimizde uzman ekibimizle 
                        yüz yüze görüşebilir, projelerinizi detaylı olarak konuşabilirsiniz.
                    </p>
                    
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-map-marker-alt text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Adres</h6>
                                    <p class="mb-0 text-muted">
                                        Örnek Mahallesi, Teknoloji Caddesi No: 123<br>
                                        Şişli / İstanbul
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-clock text-success fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Çalışma Saatleri</h6>
                                    <p class="mb-0 text-muted">
                                        Pazartesi - Cuma: 09:00 - 18:00<br>
                                        Cumartesi: 10:00 - 16:00
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-subway text-info fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Ulaşım</h6>
                                    <p class="mb-0 text-muted">
                                        Metro: Şişli-Mecidiyeköy<br>
                                        Otobüs: 54, 42A, 181
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="https://maps.google.com" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-map me-2"></i>Google Maps'te Gör
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3009.8383!2d28.9831!3d41.0477!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDHCsDAyJzUxLjciTiAyOMKwNTknMDMuMSJF!5e0!3m2!1str!2str!4v1234567890" 
                            width="100%" 
                            height="400" 
                            style="border:0; border-radius: 0.5rem;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gizlilik Politikası</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Kişisel Verilerin Korunması</h6>
                    <p><?php echo SITE_NAME; ?> olarak kişisel verilerinizin gizliliğini korumak önceliğimizdir.</p>
                    
                    <h6>İletişim Formunda Toplanan Veriler</h6>
                    <ul>
                        <li>Ad, soyad bilgileri</li>
                        <li>E-posta adresi</li>
                        <li>Telefon numarası (isteğe bağlı)</li>
                        <li>Mesaj içeriği</li>
                    </ul>
                    
                    <h6>Verilerin Kullanımı</h6>
                    <p>Bu veriler sadece size geri dönüş yapmak ve sorununuzu çözmek amacıyla kullanılır. 
                    Hiçbir şekilde üçüncü taraflarla paylaşılmaz.</p>
                    
                    <h6>Veri Saklama Süresi</h6>
                    <p>İletişim formundan gelen mesajlar, yanıtlandıktan sonra en fazla 1 yıl süreyle saklanır.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Contact card hover effects
document.querySelectorAll('.contact-card').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px)';
        this.style.transition = 'all 0.3s ease';
        this.style.boxShadow = '0 1rem 2rem rgba(0, 0, 0, 0.15)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '';
    });
});

// Form validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('message').value.trim();
    const privacy = document.getElementById('privacy').checked;
    
    if (!name || !email || !subject || !message) {
        e.preventDefault();
        showToast('Lütfen tüm zorunlu alanları doldurun!', 'error');
        return false;
    }
    
    if (message.length < 10) {
        e.preventDefault();
        showToast('Mesajınız en az 10 karakter olmalıdır!', 'error');
        return false;
    }
    
    if (!privacy) {
        e.preventDefault();
        showToast('Lütfen gizlilik politikasını kabul edin!', 'error');
        return false;
    }
    
    if (!isValidEmail(email)) {
        e.preventDefault();
        showToast('Lütfen geçerli bir e-posta adresi girin!', 'error');
        return false;
    }
});

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Phone number formatting
document.getElementById('phone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.startsWith('90')) {
        value = value.substring(2);
    }
    if (value.length > 0) {
        if (value.length <= 3) {
            this.value = '+90 (' + value;
        } else if (value.length <= 6) {
            this.value = '+90 (' + value.substring(0, 3) + ') ' + value.substring(3);
        } else {
            this.value = '+90 (' + value.substring(0, 3) + ') ' + value.substring(3, 6) + ' ' + value.substring(6, 8) + ' ' + value.substring(8, 10);
        }
    }
});

// Character counter for message
document.getElementById('message').addEventListener('input', function() {
    const maxLength = 1000;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;
    
    let counterElement = document.getElementById('messageCounter');
    if (!counterElement) {
        counterElement = document.createElement('div');
        counterElement.id = 'messageCounter';
        counterElement.className = 'form-text text-end';
        this.parentNode.appendChild(counterElement);
    }
    
    counterElement.textContent = currentLength + '/' + maxLength + ' karakter';
    
    if (remaining < 100) {
        counterElement.className = 'form-text text-end text-warning';
    } else if (remaining < 50) {
        counterElement.className = 'form-text text-end text-danger';
    } else {
        counterElement.className = 'form-text text-end text-muted';
    }
});

// Auto-resize textarea
document.getElementById('message').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Success message auto-scroll
if (document.querySelector('.alert-success')) {
    document.querySelector('.alert-success').scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });
}
";

// Footer include
include 'includes/footer.php';
?>
