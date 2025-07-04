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
                setcookie('remember_token', generateToken(), time() + (30 * 24 * 60 * 60), '/'); // 30 gün
            }
            
            // Admin kontrolünü tekrar yap (session güncellendikten sonra)
            $isUserAdmin = isAdmin();
            
            // Yönlendirme
            if (isset($_GET['redirect'])) {
                $redirectUrl = $_GET['redirect'];
                // URL temizle (hata parametrelerini kaldır)
                $cleanUrl = strtok($redirectUrl, '?');
                redirect($cleanUrl);
            } else {
                // Varsayılan yönlendirme
                if ($isUserAdmin) {
                    redirect('admin/');
                } else {
                    redirect('user/');
                }
            }
        } else {
            $error = 'Email veya şifre hatalı.';
        }
    }
}

$pageTitle = 'Giriş Yap';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mt-5 mb-4">
                    <a href="index.php" class="text-decoration-none">
                        <h1 class="h3 mb-3 fw-normal">
                            <i class="fas fa-microchip text-primary"></i>
                            <?php echo SITE_NAME; ?>
                        </h1>
                    </a>
                    <p class="text-muted">Hesabınıza giriş yapın</p>
                </div>
                
                <div class="card shadow">
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Beni hatırla
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="forgot-password.php" class="text-decoration-none">
                                <i class="fas fa-key me-1"></i>Şifremi Unuttum
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        Hesabınız yok mu? 
                        <a href="register.php" class="text-decoration-none">
                            <i class="fas fa-user-plus me-1"></i>Kayıt Ol
                        </a>
                    </p>
                    
                    <p class="text-muted">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Ana Sayfaya Dön
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Şifre görünürlük toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
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
    </script>
</body>
</html>
