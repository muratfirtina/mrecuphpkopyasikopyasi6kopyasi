<?php
/**
 * Mr ECU - Kullanıcı Kayıt Sayfası (Geliştirilmiş Tasarım)
 */
require_once 'config/config.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/' : 'user/');
}

$error = '';
$success = '';

$showEmailVerificationInfo = false;
$registeredEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone']),
        'terms_accepted' => isset($_POST['terms']) ? 1 : 0
    ];

    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
        empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Tüm zorunlu alanları doldurun.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Şifreler eşleşmiyor.';
    } elseif (!isset($_POST['terms'])) {
        $error = 'Kullanım şartları ve gizlilik politikasını kabul etmelisiniz.';
    } else {
        $user = new User($pdo);
        $result = $user->register($data);
        if ($result['success']) {
            $success = $result['message'];
            $showEmailVerificationInfo = true;
            $registeredEmail = $data['email'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Kayıt Ol';
$pageDescription = 'Mr ECU hesabı oluşturun ve profesyonel ECU hizmetlerimizden faydalanmaya başlayın.';
$pageKeywords = 'kayıt ol, hesap oluştur, üye ol, ECU hizmetleri';
$bodyClass = 'bg-dark';
include 'includes/header.php';
?>

<section class="py-5" style="min-height: 100vh; display: flex; align-items: center; background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center/cover; position: relative;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(44, 62, 80, 0.7); z-index: 1;"></div>
    <div class="container" style="position: relative; z-index: 2;">
        <div class="row justify-content-center">
            <!-- Register Form -->
            <div class="col-lg-5 col-md-7 mb-4">
                <div class="card border-0 shadow-lg" style="
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(12px);
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
                ">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                        <h3 class="mb-0"><i class="bi bi-person-plus me-2"></i> Hesap Oluştur</h3>
                        <p class="mb-0 mt-1" style="opacity: 0.85;">Ücretsiz üye olun</p>
                    </div>
                    <div class="card-body p-5">
                        <?php if ($error): ?>
                            <div class="alert alert-danger border-0 rounded-4 text-center">
                                <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success border-0 rounded-4 text-center">
                                <i class="bi bi-check-circle me-2"></i> <?php echo $success; ?>
                            </div>
                            
                            <?php if ($showEmailVerificationInfo): ?>
                            <div class="alert alert-info border-0 rounded-4 mt-3">
                                <div class="text-center">
                                    <i class="bi bi-envelope-check" style="font-size: 2rem; color: #0dcaf0;"></i>
                                    <h5 class="mt-2 mb-3">Email Doğrulama Gerekli</h5>
                                    <p class="mb-3">
                                        <strong><?php echo htmlspecialchars($registeredEmail); ?></strong><br>
                                        adresine doğrulama emaili gönderildi.
                                    </p>
                                    <p class="small text-muted">
                                        Email kutunuzu kontrol edin ve doğrulama bağlantısına tıklayın.
                                        Spam klasörünü de kontrol etmeyi unutmayın.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="verify.php" class="btn btn-info rounded-4 px-4 me-2">
                                    <i class="bi bi-envelope-check me-1"></i>Email Doğrula
                                </a>
                                <a href="login.php" class="btn btn-outline-success rounded-4 px-4">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Giriş Yap
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-success rounded-4 px-4">Giriş Yap</a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST" action="" id="registerForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ad *</label>
                                        <input type="text" name="first_name" class="form-control rounded-4" required
                                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Soyad *</label>
                                        <input type="text" name="last_name" class="form-control rounded-4" required
                                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Kullanıcı Adı *</label>
                                        <input type="text" name="username" class="form-control rounded-4" required
                                               pattern="[a-zA-Z0-9_]+"
                                               title="Sadece harf, rakam ve _ kullanabilirsiniz"
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">E-posta *</label>
                                        <input type="email" name="email" class="form-control rounded-4" required
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Telefon</label>
                                        <input type="tel" name="phone" class="form-control rounded-4"
                                               placeholder="+90 (555) 123 45 67">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Şifre *</label>
                                        <input type="password" name="password" id="password" class="form-control rounded-4" required minlength="6">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Şifre Tekrar *</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control rounded-4" required>
                                    </div>
                                </div>

                                <div class="form-check mt-4">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-none text-primary fw-bold">Kullanım Şartları</a> ve
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal" class="text-decoration-none text-primary fw-bold">Gizlilik Politikası</a>'nı okudum, anladım ve kabul ediyorum.
                                        <span class="text-danger">*</span>
                                    </label>
                                    <small class="form-text text-muted d-block mt-1">
                                        Bağlantılara tıklayarak ayrıntılı bilgileri okuyabilirsiniz.
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 mt-4 rounded-4 py-3 fw-bold"
                                        style="background: linear-gradient(135deg, #28a745, #20c997); border: none;">
                                    <i class="bi bi-person-plus me-2"></i> Hesap Oluştur
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <p class="text-muted small">Zaten hesabınız var mı?
                                <a href="login.php" class="text-success fw-bold text-decoration-none">Giriş yap</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-lg h-100" style="
                    background: rgba(255, 255, 255, 0.15);
                    backdrop-filter: blur(10px);
                    border-radius: 16px;
                    color: white;
                    overflow: hidden;
                ">
                    <div class="card-body p-4">
                        <h4 class="text-white mb-4">Neden <?php echo SITE_NAME; ?>?</h4>
                        <div class="d-flex align-items-start mb-4 text-white">
                            <i class="bi bi-shield-exclamation fa-2x me-3 opacity-75"></i>
                            <div>
                                <h6>Güvenli Platform</h6>
                                <small>SSL şifreleme ile korunan tüm verileriniz.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-4 text-white">
                            <i class="bi bi-rocket fa-2x me-3 opacity-75"></i>
                            <div>
                                <h6>Hızlı İşlem</h6>
                                <small>ECU dosyalarınız en kısa sürede işlenir.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-4 text-white">
                            <i class="bi bi-headset fa-2x me-3 opacity-75"></i>
                            <div>
                                <h6>Destek</h6>
                                <small>Mesai satleri içerisinde devamlı yanınızdayız.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms & Privacy Modals -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="margin-top: 120px;">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">
                    <i class="bi bi-file-text me-2"></i>Kullanım Şartları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="termsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yüklüyor...</span>
                        </div>
                        <p class="mt-2">Kullanım Şartları yüklüyor...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" onclick="acceptTerms()">Anlaşıldı ve Kabul Ediyorum</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content"style="margin-top: 120px;">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">
                    <i class="bi bi-shield-lock me-2"></i>Gizlilik Politikası
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="privacyContent">
                    <div class="text-center">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Yüklüyor...</span>
                        </div>
                        <p class="mt-2">Gizlilik politikası yüklüyor...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-success" onclick="acceptPrivacy()">Anlaşıldı ve Kabul Ediyorum</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirm_password').addEventListener('input', function () {
    const match = this.value === document.getElementById('password').value;
    this.classList.toggle('is-invalid', !match && this.value);
    this.classList.toggle('is-valid', match && this.value);
});

// Modal içerik yükleme
document.addEventListener('DOMContentLoaded', function() {
    // Terms modal açıldığında içeriği yükle
    document.getElementById('termsModal').addEventListener('shown.bs.modal', function () {
        loadTermsContent();
    });
    
    // Privacy modal açıldığında içeriği yükle
    document.getElementById('privacyModal').addEventListener('shown.bs.modal', function () {
        loadPrivacyContent();
    });
});

// Kullanım şartları içeriğini yükle
function loadTermsContent() {
    fetch('ajax/get_terms_content.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('termsContent').innerHTML = data.content;
                document.getElementById('termsModalLabel').textContent = data.title;
            } else {
                document.getElementById('termsContent').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Kullanım şartları şu anda yüklenemiyor. Lütfen daha sonra tekrar deneyin.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading terms:', error);
            document.getElementById('termsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle me-2"></i>
                    Kullanım şartları yüklenirken bir hata oluştu.
                </div>
            `;
        });
}

// Gizlilik politikası içeriğini yükle
function loadPrivacyContent() {
    fetch('ajax/get_privacy_content.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('privacyContent').innerHTML = data.content;
                document.getElementById('privacyModalLabel').textContent = data.title;
            } else {
                document.getElementById('privacyContent').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Gizlilik politikası şu anda yüklenemiyor. Lütfen daha sonra tekrar deneyin.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading privacy:', error);
            document.getElementById('privacyContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle me-2"></i>
                    Gizlilik politikası yüklenirken bir hata oluştu.
                </div>
            `;
        });
}

// Kullanım şartlarını kabul et
function acceptTerms() {
    document.getElementById('terms').checked = true;
    
    // Checkbox'u güncelle
    const termsCheckbox = document.getElementById('terms');
    termsCheckbox.classList.remove('is-invalid');
    termsCheckbox.classList.add('is-valid');
    
    // Modal'u kapat
    const termsModal = bootstrap.Modal.getInstance(document.getElementById('termsModal'));
    termsModal.hide();
    
    // Bildirim göster
    showToast('Kullanım şartları kabul edildi', 'success');
}

// Gizlilik politikasını kabul et
function acceptPrivacy() {
    document.getElementById('terms').checked = true;
    
    // Checkbox'u güncelle
    const termsCheckbox = document.getElementById('terms');
    termsCheckbox.classList.remove('is-invalid');
    termsCheckbox.classList.add('is-valid');
    
    // Modal'u kapat
    const privacyModal = bootstrap.Modal.getInstance(document.getElementById('privacyModal'));
    privacyModal.hide();
    
    // Bildirim göster
    showToast('Gizlilik politikası kabul edildi', 'success');
}

// Toast bildirim fonksiyonu
function showToast(message, type = 'success') {
    // Toast container oluştur (yoksa)
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Toast oluştur
    const toastId = 'toast_' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-check-circle text-${type} me-2"></i>
                <strong class="me-auto">Bildirim</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Toast'u göster
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    toast.show();
    
    // Toast kapandıktan sonra DOM'dan kaldır
    toastElement.addEventListener('hidden.bs.toast', function () {
        toastElement.remove();
    });
}
</script>

<style>
    @media (max-width: 768px) {
        .card-body { padding: 2rem 1.5rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>