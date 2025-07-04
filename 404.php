<?php
/**
 * Mr ECU - 404 Sayfa Bulunamadı
 */

require_once 'config/config.php';

$pageTitle = '404 - Sayfa Bulunamadı';

// 404 status kodu gönder
http_response_code(404);
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
                        <h1 class="display-1 fw-bold text-primary">404</h1>
                    </div>
                    
                    <div class="mb-4">
                        <i class="fas fa-search text-muted" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h2 class="mb-3">Sayfa Bulunamadı</h2>
                    <p class="lead text-muted mb-4">
                        Aradığınız sayfa taşınmış, silinmiş veya hiç var olmamış olabilir.
                    </p>
                    
                    <div class="d-grid gap-2 d-md-block">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                        </a>
                        
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo isAdmin() ? 'admin/' : 'user/'; ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-dashboard me-2"></i>Panele Git
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <hr class="my-5">
                    
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-home text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Ana Sayfa</h6>
                            <p class="small text-muted">Sitemizin ana sayfasına dönün</p>
                            <a href="index.php" class="btn btn-sm btn-outline-primary">Ana Sayfa</a>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-upload text-success mb-2" style="font-size: 2rem;"></i>
                            <h6>Dosya Yükle</h6>
                            <p class="small text-muted">ECU dosyanızı yükleyin</p>
                            <?php if (isLoggedIn()): ?>
                                <a href="user/upload.php" class="btn btn-sm btn-outline-success">Dosya Yükle</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-sm btn-outline-success">Giriş Yapın</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-envelope text-info mb-2" style="font-size: 2rem;"></i>
                            <h6>İletişim</h6>
                            <p class="small text-muted">Bizimle iletişime geçin</p>
                            <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-sm btn-outline-info">Email Gönder</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Geri butonunu aktif hale getir
        if (window.history.length > 1) {
            const backButton = document.createElement('button');
            backButton.className = 'btn btn-outline-secondary btn-lg ms-2';
            backButton.innerHTML = '<i class="fas fa-arrow-left me-2"></i>Geri';
            backButton.onclick = () => window.history.back();
            
            const buttonContainer = document.querySelector('.d-grid');
            buttonContainer.appendChild(backButton);
        }
    </script>
</body>
</html>
