<?php
/**
 * User Panel Header - Kullanıcı paneli için özel header
 */

// Ana dizinden config dosyalarını include et
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Kullanıcı girişi kontrolü
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Eğer sayfa başlığı tanımlanmamışsa varsayılan değer ata
if (!isset($pageTitle)) {
    $pageTitle = 'Kullanıcı Paneli';
}

// CSS ve JS dosyaları için temel yol
$basePath = '../';
$cssPath = '../assets/css/style.css';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Mr ECU Kullanıcı Paneli - Dosyalarınızı yönetin ve işlemlerinizi takip edin'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'kullanıcı paneli, dosya yönetimi, ECU işlemleri'; ?>">
    
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo $cssPath; ?>" rel="stylesheet">
    
    <!-- User Panel Specific CSS -->
    <style>
        .user-panel-wrapper {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .user-sidebar {
            background: white;
            border-right: 1px solid #e9ecef;
            min-height: calc(100vh - 56px);
        }
        
        .user-content {
            background: white;
            min-height: calc(100vh - 56px);
        }
        
        .user-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav .nav-link {
            color: #495057;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
            transition: all 0.15s ease-in-out;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        
        .sidebar-nav .nav-link i {
            width: 1.5rem;
        }
        
        .credit-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.15s ease-in-out;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #007bff;
        }
    </style>
    
    <!-- Ek CSS dosyaları için -->
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body class="user-panel-wrapper">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-microchip me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../about.php">
                            <i class="fas fa-info-circle me-1"></i>Hakkımızda
                        </a>
                    </li>
                </ul>
                
                <!-- Kullanıcı Menüsü -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i>
                            <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Kullanıcı'; ?>
                            <?php if (isset($_SESSION['credits'])): ?>
                                <span class="badge bg-success ms-1">
                                    <?php echo number_format($_SESSION['credits'], 2); ?> Kredi
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Hesap Bilgileri</h6></li>
                            <?php if (function_exists('isAdmin') && isAdmin()): ?>
                                <li>
                                    <a class="dropdown-item" href="../admin/">
                                        <i class="fas fa-cog me-2"></i>Admin Panel
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i>Profil Ayarları
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="transactions.php">
                                    <i class="fas fa-credit-card me-2"></i>İşlemler
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="credits.php">
                                    <i class="fas fa-coins me-2"></i>Kredi Yükle
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana içerik başlangıcı -->
    <div class="container-fluid">
        <div class="row">
