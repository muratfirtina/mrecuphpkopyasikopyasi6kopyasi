<?php
/**
 * Mr ECU - 500 Sunucu Hatası
 */

require_once 'config/config.php';

$pageTitle = '500 - Sunucu Hatası';

// 500 status kodu gönder
http_response_code(500);
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
                        <h1 class="display-1 fw-bold text-warning">500</h1>
                    </div>
                    
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h2 class="mb-3">Sunucu Hatası</h2>
                    <p class="lead text-muted mb-4">
                        Bir sunucu hatası oluştu. Teknik ekibimiz sorunu çözmek için çalışıyor. 
                        Lütfen daha sonra tekrar deneyin.
                    </p>
                    
                    <div class="d-grid gap-2 d-md-block">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-home me-2"></i>Ana Sayfaya Dön
                        </a>
                        
                        <button onclick="location.reload()" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-sync-alt me-2"></i>Tekrar Dene
                        </button>
                    </div>
                    
                    <hr class="my-5">
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-tools me-2"></i>
                        <strong>Geçici Bakım:</strong> Sistem şu anda bakım altında olabilir. 
                        Lütfen birkaç dakika sonra tekrar deneyin.
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <i class="bi bi-clock text-info mb-2" style="font-size: 2rem;"></i>
                            <h6>Kısa Sürede</h6>
                            <p class="small text-muted">Sorun en kısa sürede çözülecek</p>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <i class="bi bi-envelope text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Destek</h6>
                            <p class="small text-muted">Acil durumlar için iletişim</p>
                            <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-sm btn-outline-primary">Email Gönder</a>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <i class="bi bi-chart-line text-success mb-2" style="font-size: 2rem;"></i>
                            <h6>Durum</h6>
                            <p class="small text-muted">Sistem durumu takibi</p>
                            <span class="badge bg-warning">Bakım</span>
                        </div>
                    </div>
                    
                    <div class="mt-5">
                        <small class="text-muted">
                            Hata Kodu: 500 | Tarih: <?php echo date('d.m.Y H:i'); ?> | 
                            IP: <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // 30 saniye sonra otomatik yenileme
        setTimeout(function() {
            const reloadBtn = document.querySelector('button[onclick="location.reload()"]');
            if (reloadBtn) {
                reloadBtn.innerHTML = '<i class="bi bi-sync-alt fa-spin me-2"></i>Otomatik Yenileniyor...';
                reloadBtn.disabled = true;
                
                setTimeout(function() {
                    location.reload();
                }, 3000);
            }
        }, 30000);
    </script>
</body>
</html>
