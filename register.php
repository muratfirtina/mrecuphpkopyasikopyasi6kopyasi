<?php
/**
 * Mr ECU - Kullanıcı Kayıt Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Zaten giriş yapmışsa yönlendir
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/' : 'user/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone'])
    ];
    
    // Validation
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
        empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Zorunlu alanları doldurun.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir email adresi girin.';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $user = new User($pdo);
        $result = $user->register($data);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Kayıt Ol';
$pageDescription = 'Mr ECU hesabı oluşturun ve profesyonel ECU hizmetlerimizden faydalanmaya başlayın.';
$pageKeywords = 'kayıt ol, hesap oluştur, üye ol, ECU hizmetleri';
$bodyClass = 'bg-light';

// Header include
include 'includes/header.php';
?>

    <!-- Register Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card shadow border-0">
                        <div class="card-header bg-success text-white text-center py-4">
                            <h3 class="mb-0">
                                <i class="fas fa-user-plus me-2"></i>
                                Hesap Oluştur
                            </h3>
                            <p class="mb-0 mt-2 opacity-75">Hemen ücretsiz hesabınızı oluşturun</p>
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
                                    <div class="mt-3">
                                        <a href="login.php" class="btn btn-success">
                                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$success): ?>
                                <!-- Kayıt Formu -->
                                <form method="POST" action="" id="registerForm" data-loading="true">
                                    <div class="row">
                                        <!-- Sol Kolon -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">
                                                    <i class="fas fa-user me-1"></i>
                                                    Ad <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="first_name" 
                                                       name="first_name" 
                                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                                       placeholder="Adınız"
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">
                                                    <i class="fas fa-user me-1"></i>
                                                    Soyad <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="last_name" 
                                                       name="last_name" 
                                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                                       placeholder="Soyadınız"
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="username" class="form-label">
                                                    <i class="fas fa-at me-1"></i>
                                                    Kullanıcı Adı <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="username" 
                                                       name="username" 
                                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                                       placeholder="kullanici_adi"
                                                       pattern="[a-zA-Z0-9_]+"
                                                       title="Sadece harf, rakam ve alt çizgi kullanabilirsiniz"
                                                       required>
                                                <div class="form-text">
                                                    Sadece harf, rakam ve alt çizgi (_) kullanabilirsiniz.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Sağ Kolon -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    E-posta Adresi <span class="text-danger">*</span>
                                                </label>
                                                <input type="email" 
                                                       class="form-control" 
                                                       id="email" 
                                                       name="email" 
                                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                                       placeholder="ornek@email.com"
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">
                                                    <i class="fas fa-phone me-1"></i>
                                                    Telefon
                                                </label>
                                                <input type="tel" 
                                                       class="form-control" 
                                                       id="phone" 
                                                       name="phone" 
                                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                                       placeholder="+90 (555) 123 45 67">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="password" class="form-label">
                                                    <i class="fas fa-lock me-1"></i>
                                                    Şifre <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="password" 
                                                           class="form-control" 
                                                           id="password" 
                                                           name="password" 
                                                           placeholder="••••••••"
                                                           minlength="6"
                                                           required>
                                                    <button type="button" 
                                                            class="btn btn-outline-secondary" 
                                                            onclick="togglePassword('password')"
                                                            id="togglePasswordBtn1">
                                                        <i class="fas fa-eye" id="togglePasswordIcon1"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">En az 6 karakter olmalıdır.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Şifre Tekrar -->
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-lock me-1"></i>
                                            Şifre Tekrar <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   placeholder="••••••••"
                                                   required>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    onclick="togglePassword('confirm_password')"
                                                    id="togglePasswordBtn2">
                                                <i class="fas fa-eye" id="togglePasswordIcon2"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Şartlar ve Koşullar -->
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="terms" 
                                                   name="terms"
                                                   required>
                                            <label class="form-check-label" for="terms">
                                                <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#termsModal">
                                                    Kullanım şartları
                                                </a> ve 
                                                <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#privacyModal">
                                                    gizlilik politikası
                                                </a>nı okudum ve kabul ediyorum. <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg" data-original-text="Hesap Oluştur">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Hesap Oluştur
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-light text-center py-3">
                            <p class="mb-0 text-muted">
                                Zaten hesabınız var mı? 
                                <a href="login.php" class="text-decoration-none fw-bold">
                                    Giriş yapın
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Avantajlar -->
                    <div class="row mt-5 text-center">
                        <div class="col-12 mb-4">
                            <h4>Ücretsiz Hesabınızla</h4>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <i class="fas fa-upload fa-3x text-primary mb-3"></i>
                                    <h6>Dosya Yükleme</h6>
                                    <p class="text-muted small mb-0">
                                        ECU dosyalarınızı güvenli şekilde yükleyin
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <i class="fas fa-cogs fa-3x text-success mb-3"></i>
                                    <h6>Profesyonel İşlem</h6>
                                    <p class="text-muted small mb-0">
                                        Uzman ekibimiz dosyalarınızı işler
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <i class="fas fa-download fa-3x text-info mb-3"></i>
                                    <h6>Hızlı İndirme</h6>
                                    <p class="text-muted small mb-0">
                                        İşlenmiş dosyalarınızı anında indirin
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanım Şartları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Genel Hükümler</h6>
                    <p>Bu kullanım şartları, <?php echo SITE_NAME; ?> platformunu kullanımınızı düzenler.</p>
                    
                    <h6>2. Hizmet Kapsamı</h6>
                    <p>Platform, ECU dosya işleme hizmetleri sunar. Kullanıcılar dosyalarını yükleyerek profesyonel işleme hizmeti alabilir.</p>
                    
                    <h6>3. Kullanıcı Sorumlulukları</h6>
                    <p>Kullanıcılar yükledikleri dosyaların yasal ve geçerli olmasından sorumludur.</p>
                    
                    <h6>4. Gizlilik</h6>
                    <p>Yüklenen tüm dosyalar gizli tutulur ve üçüncü taraflarla paylaşılmaz.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

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
                    
                    <h6>Toplanan Veriler</h6>
                    <ul>
                        <li>Ad, soyad bilgileri</li>
                        <li>E-posta adresi</li>
                        <li>Telefon numarası (isteğe bağlı)</li>
                        <li>Yüklenen dosyalar</li>
                    </ul>
                    
                    <h6>Verilerin Kullanımı</h6>
                    <p>Toplanan veriler sadece hizmet sunumu amacıyla kullanılır.</p>
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
    function togglePassword(fieldId) {
        const passwordInput = document.getElementById(fieldId);
        const iconId = fieldId === 'password' ? 'togglePasswordIcon1' : 'togglePasswordIcon2';
        const toggleIcon = document.getElementById(iconId);
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }
    
    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const terms = document.getElementById('terms').checked;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Şifreler eşleşmiyor!');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Şifre en az 6 karakter olmalıdır!');
            return false;
        }
        
        if (!terms) {
            e.preventDefault();
            alert('Kullanım şartlarını kabul etmelisiniz!');
            return false;
        }
    });
    
    // Real-time password match check
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            if (confirmPassword && password === confirmPassword) {
                this.classList.add('is-valid');
            }
        }
    });
    
    // Username validation
    document.getElementById('username').addEventListener('input', function() {
        const username = this.value;
        const regex = /^[a-zA-Z0-9_]+$/;
        
        if (username && !regex.test(username)) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            if (username.length >= 3) {
                this.classList.add('is-valid');
            }
        }
    });
";

// Footer include
include 'includes/footer.php';
?>
