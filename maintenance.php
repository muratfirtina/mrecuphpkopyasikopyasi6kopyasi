<?php
/**
 * Mr ECU - Maintenance Mode (Bakım Modu)
 * Sistem bakım altındayken gösterilecek sayfa
 */

// Bakım modu kontrolü - .env'den gelecek
$isMaintenanceMode = getenv('MAINTENANCE_MODE') === 'true' || 
                     (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true) ||
                     file_exists(__DIR__ . '/.maintenance');

// Admin IP'ler bakım modunda siteye erişebilir
$adminIPs = ['127.0.0.1', '::1']; // Gerekirse gerçek IP'leri ekleyin
$currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isAdminIP = in_array($currentIP, $adminIPs);

// Bakım modu aktif değilse veya admin IP'si ise ana sayfaya yönlendir
if (!$isMaintenanceMode || $isAdminIP) {
    if (file_exists(__DIR__ . '/index.php')) {
        include 'index.php';
        exit;
    }
}

// 503 status kodu gönder
http_response_code(503);

$maintenanceMessage = getenv('MAINTENANCE_MESSAGE') ?: 
    'Sistem şu anda bakım altındadır. Lütfen daha sonra tekrar ziyaret edin.';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakım Modu - Mr ECU</title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .maintenance-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .maintenance-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .maintenance-icon {
            font-size: 6rem;
            color: #ffd700;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .maintenance-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .maintenance-message {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .progress-container {
            margin: 2rem 0;
        }
        
        .progress {
            height: 10px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #ffd700, #ffed4e);
            animation: progress 3s ease-in-out infinite;
        }
        
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        .feature-list {
            text-align: left;
            max-width: 400px;
            margin: 2rem auto;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .feature-icon {
            color: #ffd700;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .countdown {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffd700;
            margin-top: 1rem;
        }
        
        .contact-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .social-links a {
            color: white;
            font-size: 2rem;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }
        
        .social-links a:hover {
            color: #ffd700;
        }
        
        @media (max-width: 768px) {
            .maintenance-card {
                padding: 2rem 1rem;
            }
            
            .maintenance-title {
                font-size: 2.5rem;
            }
            
            .maintenance-icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="maintenance-container">
            <div class="col-lg-8 mx-auto">
                <div class="maintenance-card">
                    <div class="maintenance-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    
                    <h1 class="maintenance-title">Bakım Modu</h1>
                    
                    <div class="maintenance-message">
                        <?php echo htmlspecialchars($maintenanceMessage); ?>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"></div>
                        </div>
                        <small class="text-light mt-2 d-block">Sistem güncellemeleri yapılıyor...</small>
                    </div>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <span>Güvenlik güncellemeleri</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-rocket feature-icon"></i>
                            <span>Performans iyileştirmeleri</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-cogs feature-icon"></i>
                            <span>Yeni özellikler</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-database feature-icon"></i>
                            <span>Veritabanı optimizasyonu</span>
                        </div>
                    </div>
                    
                    <div class="countdown" id="countdown">
                        Tahmini süre: Birkaç saat
                    </div>
                    
                    <div class="contact-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Bilgilendirme</h5>
                        <p class="mb-2">
                            <strong>Acil durumlar için:</strong><br>
                            <i class="fas fa-envelope me-2"></i>mr.ecu@outlook.com
                        </p>
                        
                        <div class="social-links mt-3">
                            <a href="#" aria-label="Facebook">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="#" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" aria-label="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button onclick="location.reload()" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sync-alt me-2"></i>Yenile
                        </button>
                    </div>
                    
                    <div class="mt-4">
                        <small class="text-light opacity-75">
                            © <?php echo date('Y'); ?> Mr ECU. Tüm hakları saklıdır. | 
                            Son güncelleme: <?php echo date('d.m.Y H:i'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Otomatik yenileme (5 dakika)
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Basit countdown timer (demo amaçlı)
        let minutes = 120; // 2 saat
        const countdownElement = document.getElementById('countdown');
        
        setInterval(function() {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            
            if (hours > 0) {
                countdownElement.textContent = `Tahmini süre: ${hours} saat ${mins} dakika`;
            } else {
                countdownElement.textContent = `Tahmini süre: ${mins} dakika`;
            }
            
            minutes--;
            
            if (minutes < 0) {
                countdownElement.textContent = 'Yakında tekrar açılıyor...';
                setTimeout(() => location.reload(), 30000);
            }
        }, 60000);
        
        // Progress bar animasyonu
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar');
            let width = 0;
            
            const animate = () => {
                width += Math.random() * 2;
                if (width > 90) width = 90;
                progressBar.style.width = width + '%';
                
                setTimeout(animate, 2000 + Math.random() * 3000);
            };
            
            animate();
        });
    </script>
</body>
</html>
