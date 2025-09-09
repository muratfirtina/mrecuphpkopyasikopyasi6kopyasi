<?php
/**
 * Mr ECU - Şifre Sıfırlama İsteği (Kod Tabanlı Sistem)
 */

require_once 'config/config.php';
require_once 'config/database.php';

$error = '';
$success = '';
$step = 1; // 1: Email girişi, 2: Kod girişi, 3: Yeni şifre

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step'])) {
        $step = (int)$_POST['step'];
    }

    if ($step === 1) {
        // Email gönderme adımı
        $email = sanitize($_POST['email']);
        
        if (empty($email)) {
            $error = 'Email adresi zorunludur.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Geçerli bir email adresi girin.';
        } else {
            $user = new User($pdo);
            $result = $user->requestPasswordReset($email);
            
            if ($result['success']) {
                $success = $result['message'];
                $step = 2;
                $_SESSION['reset_email'] = $email; // Güvenlik için session'da saklayalım
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($step === 2) {
        // Kod doğrulama adımı
        $code = sanitize($_POST['code']);
        
        if (empty($code)) {
            $error = 'Doğrulama kodu zorunludur.';
        } elseif (!preg_match('/^\d{6}$/', $code)) {
            $error = 'Doğrulama kodu 6 haneli olmalıdır.';
        } else {
            $user = new User($pdo);
            $result = $user->verifyResetCode($code);
            
            if ($result['success']) {
                $_SESSION['reset_code'] = $code; // Güvenlik için
                $step = 3;
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($step === 3) {
        // Yeni şifre belirleme adımı
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $code = $_SESSION['reset_code'] ?? '';
        
        if (empty($password) || empty($confirmPassword)) {
            $error = 'Tüm alanları doldurun.';
        } elseif (strlen($password) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Şifreler eşleşmiyor.';
        } elseif (empty($code)) {
            $error = 'Oturum süresi doldu. Lütfen baştan başlayın.';
            $step = 1;
        } else {
            $user = new User($pdo);
            $result = $user->resetPasswordWithCode($code, $password);
            
            if ($result['success']) {
                // Session temizle
                unset($_SESSION['reset_email'], $_SESSION['reset_code']);
                $success = $result['message'];
                $step = 4; // Başarı adımı
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Session'dan email'i al (2. adım için)
$sessionEmail = $_SESSION['reset_email'] ?? '';

$pageTitle = 'Şifremi Unuttum';
$pageDescription = 'Şifrenizi sıfırlayın - Güvenli kod doğrulama sistemi ile yeni şifre belirleyin.';
$pageKeywords = 'şifre sıfırlama, şifremi unuttum, parola sıfırlama, doğrulama kodu';

// Header include
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="text-center mt-5 mb-4">
                <h1 class="h3 mb-3 fw-normal">
                    <i class="bi bi-key text-primary me-2"></i>
                    Şifre Sıfırlama
                </h1>
                
                <!-- Progress Steps -->
                <div class="d-flex justify-content-center mb-4">
                    <div class="d-flex align-items-center">
                        <div class="step-circle <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                        <div class="step-line <?php echo $step > 1 ? 'active' : ''; ?>"></div>
                        <div class="step-circle <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                        <div class="step-line <?php echo $step > 2 ? 'active' : ''; ?>"></div>
                        <div class="step-circle <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success && $step < 4): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($step === 1): ?>
                        <!-- Adım 1: Email Girişi -->
                        <div class="text-center mb-4">
                            <i class="bi bi-envelope text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Email Adresinizi Girin</h5>
                            <p class="text-muted">Size 6 haneli doğrulama kodu göndereceğiz.</p>
                        </div>
                        
                        <form method="POST" novalidate>
                            <input type="hidden" name="step" value="1">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           placeholder="email@example.com" required autofocus>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send me-2"></i>Doğrulama Kodu Gönder
                                </button>
                            </div>
                        </form>
                        
                    <?php elseif ($step === 2): ?>
                        <!-- Adım 2: Kod Girişi -->
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Doğrulama Kodunu Girin</h5>
                            <p class="text-muted">
                                <strong><?php echo substr($sessionEmail, 0, 3) . '***@' . explode('@', $sessionEmail)[1]; ?></strong> 
                                adresine 6 haneli kod gönderildi.
                            </p>
                        </div>
                        
                        <form method="POST" novalidate>
                            <input type="hidden" name="step" value="2">
                            
                            <div class="mb-3">
                                <label for="code" class="form-label">6 Haneli Doğrulama Kodu</label>
                                <input type="text" class="form-control text-center" id="code" name="code" 
                                       placeholder="123456" maxlength="6" pattern="\d{6}" 
                                       style="font-size: 24px; letter-spacing: 8px; font-family: monospace;" 
                                       required autofocus>
                                <div class="form-text">Kod 15 dakika geçerlidir.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Kodu Doğrula
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="step" value="1">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($sessionEmail); ?>">
                                <button type="submit" class="btn btn-link btn-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i>Yeni Kod Gönder
                                </button>
                            </form>
                        </div>
                        
                    <?php elseif ($step === 3): ?>
                        <!-- Adım 3: Yeni Şifre -->
                        <div class="text-center mb-4">
                            <i class="bi bi-lock text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Yeni Şifrenizi Belirleyin</h5>
                            <p class="text-muted">Güçlü bir şifre seçin.</p>
                        </div>
                        
                        <form method="POST" novalidate id="resetForm">
                            <input type="hidden" name="step" value="3">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Yeni Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required autofocus>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Şifre Tekrar</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <i class="bi bi-info-circle me-1"></i>
                                    Şifreniz en az 6 karakter olmalıdır.
                                </small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Şifremi Güncelle
                                </button>
                            </div>
                        </form>
                        
                    <?php else: ?>
                        <!-- Adım 4: Başarı -->
                        <div class="text-center">
                            <div class="text-success mb-4">
                                <i class="bi bi-check-circle" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-success">Şifreniz Güncellendi!</h4>
                            <p class="text-muted mb-4">Şifreniz başarıyla değiştirildi. Artık yeni şifrenizle giriş yapabilirsiniz.</p>
                            
                            <div class="d-grid">
                                <a href="login.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($step < 4): ?>
            <div class="text-center mt-4">
                <p class="text-muted">
                    Şifrenizi hatırladınız mı? 
                    <a href="login.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Giriş Sayfasına Dön
                    </a>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s;
}

.step-circle.active {
    background-color: #007bff;
    color: white;
}

.step-line {
    width: 60px;
    height: 2px;
    background-color: #e9ecef;
    transition: all 0.3s;
}

.step-line.active {
    background-color: #007bff;
}

/* Kod girişi için özel stil */
#code {
    background-color: #f8f9fa;
    border: 2px solid #dee2e6;
}

#code:focus {
    background-color: #fff;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>

<script>
// Şifre görünürlük toggle
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    
    // Şifre eşleşme kontrolü
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (confirm && password === confirm) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.remove('is-invalid', 'is-valid');
            }
        });
    }
    
    // Kod girişi için otomatik format
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            // Sadece rakam
            this.value = this.value.replace(/\D/g, '');
        });
        
        // Otomatik submit (6 hane dolunca)
        codeInput.addEventListener('input', function() {
            if (this.value.length === 6) {
                // Biraz bekle, sonra submit et
                setTimeout(() => {
                    this.form.submit();
                }, 500);
            }
        });
    }
});
</script>

<?php
// Footer include
include 'includes/footer.php';
?>
