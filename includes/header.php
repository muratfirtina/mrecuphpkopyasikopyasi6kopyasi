<?php
/**
 * Header ve Navigation - Tüm sayfalar için ortak header
 */

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

// Eğer sayfa başlığı tanımlanmamışsa varsayılan değer ata
if (!isset($pageTitle)) {
    $pageTitle = 'Ana Sayfa';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Profesyonel ECU hizmetleri - Güvenli, hızlı ve kaliteli ECU yazılım çözümleri'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'ECU, chip tuning, ECU yazılım, immobilizer, TCU'; ?>">
    
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo isset($cssPath) ? $cssPath : 'assets/css/style.css'; ?>" rel="stylesheet">
    
    <!-- Ek CSS dosyaları için -->
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">
                <i class="fas fa-microchip me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pageTitle == 'Ana Sayfa') ? 'active' : ''; ?>" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#services">
                            <i class="fas fa-cogs me-1"></i>Hizmetler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#about">
                            <i class="fas fa-info-circle me-1"></i>Hakkımızda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#contact">
                            <i class="fas fa-envelope me-1"></i>İletişim
                        </a>
                    </li>
                    
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <!-- Giriş yapan kullanıcılar için ek menü öğeleri -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/upload.php">
                                <i class="fas fa-upload me-1"></i>Dosya Yükle
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Kullanıcı Menüsü -->
                <ul class="navbar-nav">
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <!-- Notification icon -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="display: none;">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Bildirimler</h6></li>
                                <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>admin/notifications.php">Tüm Bildirimleri Gör</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i>
                                <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Kullanıcı'; ?>
                                <?php if (isset($_SESSION['credits'])): ?>
                                    <span class="badge bg-success ms-1">
                                        <?php echo $_SESSION['credits']; ?> Kredi
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>admin/">
                                            <i class="fas fa-cog me-2"></i>Admin Panel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>migration-dashboard.php">
                                            <i class="fas fa-exchange-alt me-2"></i>Migration Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/">
                                        <i class="fas fa-dashboard me-2"></i>Panel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/files.php">
                                        <i class="fas fa-file me-2"></i>Dosyalarım
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/profile.php">
                                        <i class="fas fa-user me-2"></i>Profil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Giriş
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>register.php">
                                <i class="fas fa-user-plus me-1"></i>Kayıt
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana içerik başlangıcı -->
    <main class="main-content">
