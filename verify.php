<?php
/**
 * Mr ECU - Email Doğrulama
 */

require_once 'config/config.php';
require_once 'config/database.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    $error = 'Geçersiz doğrulama linki.';
} else {
    $user = new User($pdo);
    
    if ($user->verifyEmail($token)) {
        $success = 'Email adresiniz başarıyla doğrulandı. Artık giriş yapabilirsiniz.';
    } else {
        $error = 'Doğrulama linki geçersiz veya süresi dolmuş.';
    }
}

$pageTitle = 'Email Doğrulama';
$pageDescription = 'Email doğrulama sayfası - Hesabınızı aktifleştirin ve sisteme giriş yapmaya başlayın.';
$pageKeywords = 'email doğrulama, hesap aktivasyonu, email aktivasyonu';

// Header include
include 'includes/header.php';
?>

    <!-- Page Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mt-5 mb-4">
                    <h1 class="h3 mb-3 fw-normal">
                        <i class="bi bi-envelope-check text-primary me-2"></i>
                        Email Doğrulama
                    </h1>
                    <p class="text-muted">Hesabınızı aktifleştirin</p>
                </div>
                
                <div class="card shadow">
                    <div class="card-body p-4 text-center">
                        <?php if ($error): ?>
                            <div class="mb-4">
                                <i class="bi bi-times-circle text-danger" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-danger">Doğrulama Başarısız</h4>
                                <p class="text-muted"><?php echo $error; ?></p>
                                
                                <div class="alert alert-info mt-4" role="alert">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Yardım:</strong><br>
                                    • Doğrulama linkinin süresi dolmuş olabilir<br>
                                    • Link yanlış veya eksik kopyalanmış olabilir<br>
                                    • Tekrar kayıt olup yeni doğrulama linki alabilirsiniz
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="bi bi-sign-in-alt me-1"></i>Giriş Yap
                                    </a>
                                    <a href="register.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-user-plus me-1"></i>Yeni Hesap Oluştur
                                    </a>
                                    <a href="forgot-password.php" class="btn btn-outline-info">
                                        <i class="bi bi-key me-1"></i>Şifremi Unuttum
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="mb-4">
                                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-success">Doğrulama Başarılı! 🎉</h4>
                                <p class="text-muted"><?php echo $success; ?></p>
                                
                                <div class="alert alert-success mt-4" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Tebrikler!</strong><br>
                                    Hesabınız aktifleştirildi. Artık tüm özelliklerimizden yararlanabilirsiniz.
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <a href="login.php" class="btn btn-success btn-lg">
                                        <i class="bi bi-sign-in-alt me-1"></i>Giriş Yap
                                    </a>
                                    <a href="index.php#services" class="btn btn-outline-primary">
                                        <i class="bi bi-gear-wide-connected me-1"></i>Hizmetlerimizi İncele
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <a href="index.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Ana Sayfaya Dön
                        </a>
                    </p>
                    
                    <p class="text-muted">
                        Sorun mu yaşıyorsunuz? 
                        <a href="contact.php" class="text-decoration-none">
                            <i class="bi bi-life-ring me-1"></i>Destek Alın
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php
$pageJS = "
    // Auto-redirect after successful verification
    " . ($success ? "
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 10000); // 10 saniye sonra otomatik yönlendirme
    " : "") . "
    
    // Copy email verification help
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('[data-copy]');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const text = this.getAttribute('data-copy');
                navigator.clipboard.writeText(text).then(function() {
                    // Feedback göster
                });
            });
        });
    });
";

// Footer include
include 'includes/footer.php';
?>
