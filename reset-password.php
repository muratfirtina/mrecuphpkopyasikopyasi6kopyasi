<?php
/**
 * Mr ECU - Şifre Sıfırlama
 */

require_once 'config/config.php';
require_once 'config/database.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    $error = 'Geçersiz sıfırlama linki.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Şifre alanları zorunludur.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $user = new User($pdo);
        
        if ($user->resetPassword($token, $password)) {
            $success = 'Şifreniz başarıyla güncellendi. Artık yeni şifrenizle giriş yapabilirsiniz.';
        } else {
            $error = 'Sıfırlama linki geçersiz veya süresi dolmuş.';
        }
    }
}

$pageTitle = 'Şifre Sıfırla';
$pageDescription = 'Yeni şifre belirleyin - Güvenli bir şifre seçin ve hesabınıza tekrar erişim sağlayın.';
$pageKeywords = 'şifre sıfırlama, yeni şifre, parola güncelleme';

// Header include
include 'includes/header.php';
?>

    <!-- Page Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mt-5 mb-4">
                    <h1 class="h3 mb-3 fw-normal">
                        <i class="fas fa-lock text-primary me-2"></i>
                        Şifre Sıfırlama
                    </h1>
                    <p class="text-muted">Yeni şifre belirleyin</p>
                </div>
                
                <div class="card shadow">
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                            
                            <?php if (strpos($error, 'Geçersiz') !== false): ?>
                                <div class="text-center">
                                    <a href="forgot-password.php" class="btn btn-primary">
                                        <i class="fas fa-key me-1"></i>Yeni Sıfırlama Bağlantısı İste
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-success">
                                        <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                                    </a>
                                </div>
                            </div>
                        <?php elseif (!empty($token) && empty($error)): ?>
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-key text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Yeni Şifre Belirleyin</h5>
                            <p class="text-muted">Güvenli bir şifre seçin ve tekrar girin.</p>
                        </div>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="password" class="form-label">Yeni Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required autofocus>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">En az 6 karakter olmalıdır.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Şifre Tekrar</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="6" required>
                                </div>
                                <div class="invalid-feedback">
                                    Şifreler eşleşmiyor.
                                </div>
                            </div>
                            
                            <!-- Şifre Güçlülük Göstergesi -->
                            <div class="mb-3">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="passwordHelp" class="form-text text-muted">Şifre gücü: Zayıf</small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Şifreyi Güncelle
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-sign-in-alt me-1"></i>Giriş Sayfasına Dön
                        </a>
                    </p>
                    
                    <p class="text-muted">
                        Hesabınız yok mu? 
                        <a href="register.php" class="text-decoration-none">
                            <i class="fas fa-user-plus me-1"></i>Kayıt Ol
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php
$pageJS = "
    // Şifre görünürlük toggle
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            confirmPassword.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            confirmPassword.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Şifre güçlülük kontrolü
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('passwordHelp');
        
        let strength = 0;
        let text = 'Çok Zayıf';
        let color = 'bg-danger';
        
        if (password.length >= 6) strength += 1;
        if (password.match(/[a-z]+/)) strength += 1;
        if (password.match(/[A-Z]+/)) strength += 1;
        if (password.match(/[0-9]+/)) strength += 1;
        if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;
        
        switch (strength) {
            case 0:
            case 1:
                text = 'Çok Zayıf';
                color = 'bg-danger';
                break;
            case 2:
                text = 'Zayıf';
                color = 'bg-warning';
                break;
            case 3:
                text = 'Orta';
                color = 'bg-info';
                break;
            case 4:
                text = 'Güçlü';
                color = 'bg-success';
                break;
            case 5:
                text = 'Çok Güçlü';
                color = 'bg-success';
                break;
        }
        
        strengthBar.className = 'progress-bar ' + color;
        strengthBar.style.width = (strength / 5) * 100 + '%';
        strengthText.textContent = 'Şifre gücü: ' + text;
    });
    
    // Şifre eşleştirme kontrolü
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (password !== confirmPassword) {
            this.setCustomValidity('Şifreler eşleşmiyor');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            const forms = document.getElementsByClassName('needs-validation');
            Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
";

// Footer include
include 'includes/footer.php';
?>
