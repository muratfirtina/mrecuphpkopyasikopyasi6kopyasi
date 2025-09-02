<?php
/**
 * Mr ECU - Şifre Sıfırlama İsteği
 */

require_once 'config/config.php';
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Email adresi zorunludur.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir email adresi girin.';
    } else {
        $user = new User($pdo);
        
        if ($user->requestPasswordReset($email)) {
            $success = 'Şifre sıfırlama bağlantısı email adresinize gönderildi.';
        } else {
            $error = 'Bu email adresiyle kayıtlı kullanıcı bulunamadı.';
        }
    }
}

$pageTitle = 'Şifremi Unuttum';
$pageDescription = 'Şifrenizi sıfırlayın - Email adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.';
$pageKeywords = 'şifre sıfırlama, şifremi unuttum, parola sıfırlama';

// Header include
include 'includes/header.php';
?>

    <!-- Page Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mt-5 mb-4">
                    <h1 class="h3 mb-3 fw-normal">
                        <i class="bi bi-key text-primary me-2"></i>
                        Şifre Sıfırlama
                    </h1>
                    <p class="text-muted">Şifrenizi sıfırlamak için email adresinizi girin</p>
                </div>
                
                <div class="card shadow">
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-success">
                                        <i class="bi bi-sign-in-alt me-1"></i>Giriş Sayfasına Dön
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                        
                        <div class="text-center mb-4">
                            <i class="bi bi-envelope text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Email Adresinizi Girin</h5>
                            <p class="text-muted">Size şifre sıfırlama bağlantısı göndereceğiz.</p>
                        </div>
                        
                        <form method="POST" novalidate>
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
                                    <i class="bi bi-send me-2"></i>Sıfırlama Bağlantısı Gönder
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        Şifrenizi hatırladınız mı? 
                        <a href="login.php" class="text-decoration-none">
                            <i class="bi bi-sign-in-alt me-1"></i>Giriş Yap
                        </a>
                    </p>
                    
                    <p class="text-muted">
                        Hesabınız yok mu? 
                        <a href="register.php" class="text-decoration-none">
                            <i class="bi bi-person-plus me-1"></i>Kayıt Ol
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php
$pageJS = "
    // Auto-focus on email field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
    });
";

// Footer include
include 'includes/footer.php';
?>
