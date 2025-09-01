<?php
/**
 * Mr ECU - 403 Erişim Engellendi
 */

require_once 'config/config.php';

$pageTitle = '403 - Erişim Engellendi';

// 403 status kodu gönder
http_response_code(403);
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
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="text-center">
                    <div class="error-code mb-4">
                        <h1 class="display-1 fw-bold text-danger">403</h1>
                    </div>
                    
                    <div class="mb-4">
                        <i class="bi bi-ban text-danger" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h2 class="mb-3">Erişim Engellendi</h2>
                    <p class="lead text-muted mb-4">
                        Bu sayfaya erişim yetkiniz bulunmuyor. Lütfen giriş yapın veya yetkiniz olup olmadığını kontrol edin.
                    </p>
                    
                    <div class="d-grid gap-2 d-md-block">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-home me-2"></i>Ana Sayfaya Dön
                        </a>
                        
                        <?php if (!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-success btn-lg">
                                <i class="bi bi-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                        <?php else: ?>
                            <a href="<?php echo isAdmin() ? 'admin/' : 'user/'; ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-dashboard me-2"></i>Panele Git
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <hr class="my-5">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Bilgi:</strong> Eğer bu sayfaya erişmeniz gerektiğini düşünüyorsanız, 
                        lütfen sistem yöneticisi ile iletişime geçin.
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <i class="bi bi-envelope text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Destek</h6>
                            <p class="small text-muted">Teknik destek için email gönderin</p>
                            <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-sm btn-outline-primary">Email Gönder</a>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <i class="bi bi-question-circle text-info mb-2" style="font-size: 2rem;"></i>
                            <h6>Yardım</h6>
                            <p class="small text-muted">SSS ve yardım sayfaları</p>
                            <a href="index.php#contact" class="btn btn-sm btn-outline-info">Yardım Al</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
