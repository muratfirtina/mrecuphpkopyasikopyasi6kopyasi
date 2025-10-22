<?php
/**
 * Mr ECU - 500 Sunucu Hatası
 * NOT: Bu dosya config.php'yi REQUIRE ETMEMELIDIR (sonsuz döngü riski)
 */

// Eğer config zaten yüklenmişse sabitleri kullan, yoksa default değerler
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Mr.ECU';
$siteUrl = defined('SITE_URL') ? SITE_URL : '/';
$siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : 'mr.ecu@outlook.com';

// 500 status kodu gönder (eğer daha önce gönderilmemişse)
if (!headers_sent()) {
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Sunucu Hatası - <?php echo htmlspecialchars($siteName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-icon {
            font-size: 80px;
            color: #ffc107;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .lead {
            color: #666;
            margin-bottom: 30px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            border-radius: 50px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            padding: 15px 40px;
            font-size: 16px;
            border-radius: 50px;
            transition: all 0.3s;
        }
        
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .info-grid {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .info-item {
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
            transition: transform 0.3s;
        }
        
        .info-item:hover {
            transform: translateY(-5px);
        }
        
        .info-item i {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .alert-warning {
            border-left: 4px solid #ffc107;
            background: #fff3cd;
            border-radius: 10px;
        }
        
        .footer-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            
            <div class="error-code">500</div>
            
            <h2>Sunucu Hatası</h2>
            <p class="lead">
                Bir sunucu hatası oluştu. Teknik ekibimiz sorunu çözmek için çalışıyor. 
                Lütfen daha sonra tekrar deneyin.
            </p>
            
            <div class="d-grid gap-3 d-md-flex justify-content-center mb-4">
                <a href="<?php echo htmlspecialchars($siteUrl); ?>" class="btn btn-primary">
                    <i class="bi bi-house-door me-2"></i>Ana Sayfaya Dön
                </a>
                
                <button onclick="location.reload()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-2"></i>Tekrar Dene
                </button>
            </div>
            
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-tools me-2"></i>
                <div class="text-start">
                    <strong>Geçici Bakım:</strong> Sistem şu anda bakım altında olabilir. 
                    Lütfen birkaç dakika sonra tekrar deneyin.
                </div>
            </div>
            
            <div class="row info-grid g-3">
                <div class="col-md-4">
                    <div class="info-item">
                        <i class="bi bi-clock text-info"></i>
                        <h6 class="fw-bold">Kısa Sürede</h6>
                        <p class="small text-muted mb-0">Sorun en kısa sürede çözülecek</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="info-item">
                        <i class="bi bi-envelope text-primary"></i>
                        <h6 class="fw-bold">Destek</h6>
                        <p class="small text-muted mb-2">Acil durumlar için</p>
                        <a href="mailto:<?php echo htmlspecialchars($siteEmail); ?>" 
                           class="btn btn-sm btn-outline-primary">
                            Email Gönder
                        </a>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="info-item">
                        <i class="bi bi-bar-chart-line text-success"></i>
                        <h6 class="fw-bold">Durum</h6>
                        <p class="small text-muted mb-2">Sistem durumu</p>
                        <span class="badge bg-warning text-dark">Bakım</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-info">
                <p class="mb-1">
                    <strong>Hata Kodu:</strong> 500 | 
                    <strong>Tarih:</strong> <?php echo date('d.m.Y H:i'); ?> | 
                    <strong>IP:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor'); ?>
                </p>
                <p class="mb-0">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-reload after 30 seconds -->
    <script>
        // 30 saniye sonra otomatik yenileme
        let countdown = 30;
        const reloadBtn = document.querySelector('button[onclick="location.reload()"]');
        
        setTimeout(function() {
            if (reloadBtn) {
                const interval = setInterval(function() {
                    countdown--;
                    reloadBtn.innerHTML = `<i class="bi bi-arrow-clockwise fa-spin me-2"></i>Yenileniyor (${countdown})`;
                    reloadBtn.disabled = true;
                    
                    if (countdown <= 0) {
                        clearInterval(interval);
                        location.reload();
                    }
                }, 1000);
            }
        }, 30000);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC = Reload
            if (e.key === 'Escape') {
                location.reload();
            }
            // H = Home
            if (e.key === 'h' || e.key === 'H') {
                window.location.href = '<?php echo htmlspecialchars($siteUrl); ?>';
            }
        });
    </script>
</body>
</html>
