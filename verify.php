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
                    <p class="text-muted">Email Doğrulama</p>
                </div>
                
                <div class="card shadow">
                    <div class="card-body p-4 text-center">
                        <?php if ($error): ?>
                            <div class="mb-4">
                                <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-danger">Doğrulama Başarısız</h4>
                                <p class="text-muted"><?php echo $error; ?></p>
                                <div class="d-grid gap-2 mt-4">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                                    </a>
                                    <a href="register.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-user-plus me-1"></i>Yeni Hesap Oluştur
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-success">Doğrulama Başarılı</h4>
                                <p class="text-muted"><?php echo $success; ?></p>
                                <div class="d-grid gap-2 mt-4">
                                    <a href="login.php" class="btn btn-success btn-lg">
                                        <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
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
</body>
</html>
