<?php
/**
 * Admin Panel Header - Admin paneli için özel header
 */

// Ana dizinden config dosyalarını include et
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php?error=access_denied');
    exit;
}

// Eğer sayfa başlığı tanımlanmamışsa varsayılan değer ata
if (!isset($pageTitle)) {
    $pageTitle = 'Admin Panel';
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
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Mr ECU Admin Paneli - Sistem yönetimi ve kontrolü'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'admin panel, yönetim, sistem kontrolü'; ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?php echo $pageTitle . ' - Admin Panel - ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js for admin charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="<?php echo $cssPath; ?>" rel="stylesheet">
    
    <!-- Admin Panel Specific CSS -->
    <style>
        .admin-panel-wrapper {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .admin-sidebar {
            background: #2c3e50;
            min-height: calc(100vh - 56px);
            border-right: 1px solid #34495e;
        }
        
        .admin-content {
            background: white;
            min-height: calc(100vh - 56px);
        }
        
        .admin-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-sidebar .nav-link {
            color: #ecf0f1;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
            transition: all 0.15s ease-in-out;
            padding: 0.75rem 1rem;
        }
        
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background-color: #3498db;
            color: white;
        }
        
        .admin-sidebar .nav-link i {
            width: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .admin-sidebar .nav-section {
            color: #bdc3c7;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 1rem 0.5rem;
            margin-top: 1rem;
            border-top: 1px solid #34495e;
        }
        
        .admin-sidebar .nav-section:first-child {
            border-top: none;
            margin-top: 0;
        }
        
        .stat-widget {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1.5rem;
            transition: all 0.15s ease-in-out;
        }
        
        .stat-widget:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }
        
        .admin-navbar {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        }
        
        .admin-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
        }
        
        .admin-navbar .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .admin-navbar .nav-link:hover {
            color: white !important;
        }
        
        .admin-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }
        
        .admin-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        
        .quick-stats {
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }
        
        .table-admin {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .table-admin thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        
        .alert-admin {
            border: none;
            border-left: 4px solid;
        }
        
        .alert-admin.alert-info {
            border-left-color: #17a2b8;
            background-color: rgba(23, 162, 184, 0.1);
        }
        
        .alert-admin.alert-warning {
            border-left-color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .alert-admin.alert-danger {
            border-left-color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .alert-admin.alert-success {
            border-left-color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
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
<body class="admin-panel-wrapper">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-microchip me-2"></i>
                <?php echo SITE_NAME; ?> - Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-globe me-1"></i>Siteyi Görüntüle
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../user/">
                            <i class="fas fa-user me-1"></i>Kullanıcı Paneli
                        </a>
                    </li>
                </ul>
                
                <!-- Kullanıcı Menüsü -->
                <ul class="navbar-nav">
                    <!-- Bildirimler -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                3
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Bildirimler</h6></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-info-circle me-2 text-info"></i>Yeni kullanıcı kaydı</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-upload me-2 text-warning"></i>Bekleyen dosya var</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Sistem uyarısı</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">Tümünü gör</a></li>
                        </ul>
                    </li>
                    
                    <!-- Admin Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?>
                            <span class="badge bg-warning text-dark ms-1">Admin</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Admin İşlemleri</h6></li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog me-2"></i>Sistem Ayarları
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="security-dashboard.php">
                                    <i class="fas fa-shield-alt me-2"></i>Güvenlik
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="logs.php">
                                    <i class="fas fa-clipboard-list me-2"></i>Sistem Logları
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="../user/profile.php">
                                    <i class="fas fa-user me-2"></i>Profil Ayarları
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../logout.php">
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
