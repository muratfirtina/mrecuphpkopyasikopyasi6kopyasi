<?php
/**
 * Mr ECU - Kullanıcı Giriş Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Zaten giriş yapmışsa yönlendir
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/');
    } else {
        redirect('user/');
    }
}

$error = '';
$success = '';

// URL parametrelerinden hata mesajlarını al
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_invalid':
            $error = 'Oturumunuz geçersiz hale gelmiş. Lütfen tekrar giriş yapın.';
            break;
        case 'access_denied':
            $error = 'Bu sayfaya erişim yetkiniz yok.';
            break;
        case 'user_not_found':
            $error = 'Kullanıcı hesabı bulunamadı veya silinmiş.';
            break;
        default:
            $error = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Email ve şifre alanları zorunludur.';
    } else {
        $user = new User($pdo);
        
        if ($user->login($email, $password)) {
            // Beni hatırla seçeneği
            if ($remember) {
                $rememberToken = generateRandomString(32);
                $user->setRememberToken($_SESSION['user_id'], $rememberToken);
                setcookie('remember_token', $rememberToken, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }
            
            // Yönlendirme kontrolü
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : (isAdmin() ? 'admin/' : 'user/');
            redirect($redirect);
        } else {
            $error = 'Email veya şifre hatalı.';
        }
    }
}

$pageTitle = 'Giriş Yap';
$pageDescription = 'Mr ECU hesabınızla giriş yapın ve profesyonel ECU hizmetlerimizden faydalanın.';
$pageKeywords = 'giriş, login, kullanıcı girişi, ECU hizmetleri';
$bodyClass = 'bg-light';

// Header include
include 'includes/header.php';
?>

    <!-- Login Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card shadow border-0">
                        <div class="card-header bg-primary text-white text-center py-4">
                            <h3 class="mb-0">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Giriş Yap
                            </h3>
                            <p class="mb-0 mt-2 opacity-75">Hesabınıza giriş yapın</p>
                        </div>
                        
                        <div class="card-body p-4">
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
                            
                            <!-- Giriş Formu -->
                            <form method="POST" action="" id="loginForm" data-loading="true">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        E-posta Adresi
                                    </label>
                                    <input type="email" 
                                           class="form-control form-control-lg" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                           placeholder="ornek@email.com"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>
                                        Şifre
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control form-control-lg" 
                                               id="password" 
                                               name="password" 
                                               placeholder="••••••••"
                                               required>
                                        <button type="button" 
                                                class="btn btn-outline-secondary" 
                                                onclick="togglePassword()"
                                                id="togglePasswordBtn">
                                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="remember" 
                                               name="remember"
                                               <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="remember">
                                            Beni hatırla
                                        </label>
                                    </div>
                                    
                                    <a href="forgot-password.php" class="text-decoration-none">
                                        Şifremi unuttum
                                    </a>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" data-original-text="Giriş Yap">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Giriş Yap
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card-footer bg-light text-center py-3">
                            <p class="mb-0 text-muted">
                                Hesabınız yok mu? 
                                <a href="register.php" class="text-decoration-none fw-bold">
                                    Hemen kayıt olun
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Güvenlik Bilgileri -->
                    <div class="text-center mt-4">
                        <div class="row text-muted">
                            <div class="col-4">
                                <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                <br>
                                <small>Güvenli Giriş</small>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-lock fa-2x text-primary mb-2"></i>
                                <br>
                                <small>SSL Şifreleme</small>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-user-shield fa-2x text-info mb-2"></i>
                                <br>
                                <small>Gizlilik Koruması</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bilgi Paneli -->
                <div class="col-lg-4 col-md-5 mt-4 mt-md-0">
                    <div class="ms-lg-4">
                        <h4 class="mb-3">Neden <?php echo SITE_NAME; ?>?</h4>
                        
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                        <i class="fas fa-rocket text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Hızlı İşlem</h6>
                                        <small class="text-muted">
                                            Dosyalarınız profesyonel ekibimiz tarafından 
                                            hızla işlenir.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                        <i class="fas fa-shield-alt text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Güvenli Platform</h6>
                                        <small class="text-muted">
                                            Tüm dosyalarınız SSL şifreleme ile 
                                            korunur.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                                        <i class="fas fa-headset text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">7/24 Destek</h6>
                                        <small class="text-muted">
                                            Uzman ekibimiz size her zaman 
                                            yardımcı olmaya hazır.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>İlk kez mi kullanıyorsunuz?</strong><br>
                            <small>
                                Hesap oluşturmak için 
                                <a href="register.php" class="alert-link">kayıt ol</a> 
                                sayfasını ziyaret edin.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
// Sayfa özel JavaScript
$pageJS = "
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }
    
    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        if (!email || !password) {
            e.preventDefault();
            alert('Lütfen tüm alanları doldurun.');
            return false;
        }
        
        if (!isValidEmail(email)) {
            e.preventDefault();
            alert('Lütfen geçerli bir e-posta adresi girin.');
            return false;
        }
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Auto-focus on email field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
    });
";

// Footer include
include 'includes/footer.php';
?>
