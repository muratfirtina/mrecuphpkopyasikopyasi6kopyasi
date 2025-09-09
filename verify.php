<?php
/**
 * Mr ECU - Email Doğrulama Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

$success = '';
$error = '';
$showResendForm = false;

// Token kontrolü
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    $user = new User($pdo);
    $result = $user->verifyEmail($token);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
        $showResendForm = true;
    }
} else {
    $error = 'Geçersiz doğrulama bağlantısı.';
    $showResendForm = true;
}

// Email yeniden gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_email'])) {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Email adresi zorunludur.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir email adresi girin.';
    } else {
        $user = new User($pdo);
        $result = $user->resendVerificationEmail($email);
        
        if ($result['success']) {
            $success = $result['message'];
            $showResendForm = false;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Email Doğrulama';
$pageDescription = 'Email adresinizi doğrulayın ve hesabınızı aktifleştirin.';
$pageKeywords = 'email doğrulama, hesap aktivasyonu, email verification';

// Header include
include 'includes/header.php';
?>

<section class="py-5" style="min-height: 100vh; display: flex; align-items: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg" style="
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(12px);
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
                ">
                    <div class="card-body p-5 text-center">
                        
                        <?php if ($success): ?>
                            <!-- Başarı durumu -->
                            <div class="text-success mb-4">
                                <i class="bi bi-check-circle" style="font-size: 5rem;"></i>
                            </div>
                            
                            <h2 class="text-success mb-3">Email Doğrulandı!</h2>
                            
                            <div class="alert alert-success border-0 rounded-4">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <a href="login.php" class="btn btn-success btn-lg rounded-4">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Giriş Yap
                                </a>
                                <a href="index.php" class="btn btn-outline-primary rounded-4">
                                    <i class="bi bi-house me-2"></i>
                                    Ana Sayfa
                                </a>
                            </div>
                            
                        <?php else: ?>
                            <!-- Hata durumu -->
                            <div class="text-warning mb-4">
                                <i class="bi bi-exclamation-triangle" style="font-size: 5rem;"></i>
                            </div>
                            
                            <h2 class="text-dark mb-3">Email Doğrulama</h2>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger border-0 rounded-4">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($showResendForm): ?>
                                <div class="mt-4">
                                    <h5 class="mb-3">Doğrulama Emaili Yeniden Gönder</h5>
                                    <p class="text-muted mb-4">
                                        Email adresinizi girin, size yeni bir doğrulama bağlantısı gönderelim.
                                    </p>
                                    
                                    <form method="POST" novalidate>
                                        <div class="mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-envelope text-muted"></i>
                                                </span>
                                                <input type="email" 
                                                       class="form-control rounded-end" 
                                                       name="email" 
                                                       placeholder="email@example.com" 
                                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                                       required>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" name="resend_email" class="btn btn-primary rounded-4">
                                                <i class="bi bi-send me-2"></i>
                                                Doğrulama Emaili Gönder
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4 pt-3 border-top">
                                <p class="text-muted small mb-2">
                                    Hesabınız zaten doğrulanmış mı?
                                </p>
                                <a href="login.php" class="btn btn-outline-success rounded-4">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Giriş Yap
                                </a>
                            </div>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
                
                <!-- Bilgi kartı -->
                <?php if (!$success): ?>
                <div class="card border-0 shadow-lg mt-4" style="
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(10px);
                    border-radius: 16px;
                    color: white;
                ">
                    <div class="card-body p-4">
                        <h6 class="text-white mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Email Doğrulama Hakkında
                        </h6>
                        
                        <div class="small text-white-50">
                            <div class="mb-2">
                                <i class="bi bi-shield-check me-2"></i>
                                Email doğrulama hesap güvenliğiniz için gereklidir
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-clock me-2"></i>
                                Doğrulama bağlantısı 24 saat geçerlidir
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-envelope me-2"></i>
                                Spam klasörünüzü de kontrol edin
                            </div>
                            <div>
                                <i class="bi bi-headset me-2"></i>
                                Sorun yaşıyorsanız destek ekibimize ulaşın
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="text-white-50 small">
                        <a href="index.php" class="text-white-50 text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Ana Sayfaya Dön
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Ek animasyonlar */
.card {
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

.alert {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .card-body {
        padding: 2rem 1.5rem;
    }
    
    .bi {
        font-size: 4rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Email input fokus
    const emailInput = document.querySelector('input[name="email"]');
    if (emailInput) {
        emailInput.focus();
    }
    
    // Form validasyonu
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const email = form.querySelector('input[name="email"]').value;
            
            if (!email || !email.includes('@')) {
                e.preventDefault();
                alert('Lütfen geçerli bir email adresi girin.');
                return false;
            }
        });
    }
    
    // Başarı durumunda otomatik yönlendirme (opsiyonel)
    <?php if ($success): ?>
    setTimeout(function() {
        // 5 saniye sonra login sayfasına yönlendir
        const redirectBtn = document.querySelector('a[href="login.php"]');
        if (redirectBtn) {
            redirectBtn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            redirectBtn.innerHTML = '<i class="bi bi-clock me-2"></i>Giriş Sayfasına Yönlendiriliyorsunuz...';
            
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        }
    }, 3000);
    <?php endif; ?>
});
</script>

<?php
// Footer include
include 'includes/footer.php';
?>
