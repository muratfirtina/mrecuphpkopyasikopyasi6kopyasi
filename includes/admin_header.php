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
        
        /* Admin Dropdown Güzelleştirmeleri - User Header'dan Uyarlanmış */
        .admin-avatar {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-info {
            text-align: left;
        }
        
        .text-white-75 {
            color: rgba(255,255,255,0.75) !important;
        }
        
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border: none;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);
            transition: all 0.2s ease;
        }
        
        .dropdown-item {
            padding: 0.7rem 1rem;
            border-radius: 6px;
            margin: 0 0.5rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-header {
            font-weight: 600;
            padding: 1rem 1rem 0.5rem;
            color: #495057;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e9ecef;
        }
        
        /* Bildirim Dropdown Özel Stilleri */
        .dropdown-item.py-3 {
            padding: 1rem !important;
            margin: 0;
            border-radius: 0;
        }
        
        .dropdown-item.py-3:hover {
            background-color: #f8f9fa;
            transform: none;
        }
        
        .dropdown-item.bg-light {
            background-color: #f8f9fa !important;
            border-left: 3px solid #007bff;
        }
        
        /* Icon Stilleri */
        .dropdown-item i {
            width: 1.2rem;
            text-align: center;
        }
        
        /* Badge Stilleri */
        .badge {
            font-size: 0.7rem;
        }
        
        /* Notification Icon Containers */
        .bg-opacity-10 {
            background-color: rgba(var(--bs-success-rgb), 0.1) !important;
        }
        
        .bg-success.bg-opacity-10 {
            background-color: rgba(25, 135, 84, 0.1) !important;
        }
        
        .bg-warning.bg-opacity-10 {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        .bg-danger.bg-opacity-10 {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        
        .bg-info.bg-opacity-10 {
            background-color: rgba(13, 202, 240, 0.1) !important;
        }
        
        /* Responsive İyileştirmeler */
        @media (max-width: 991.98px) {
            .dropdown-menu {
                min-width: 250px !important;
                max-width: 90vw;
            }
            
            .admin-info {
                display: none;
            }
            
            .admin-avatar {
                margin-right: 0 !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .dropdown-menu {
                min-width: 220px !important;
            }
            
            .dropdown-item {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .dropdown-header {
                padding: 0.75rem 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
        }
        
        /* Animasyon İyileştirmeleri */
        .dropdown-toggle::after {
            transition: transform 0.2s ease;
        }
        
        .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }
        
        /* Navbar Toggler Focus Düzeltmesi */
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        /* Bildirim Animasyonları */
        .dropdown-item.py-3 {
            transition: all 0.2s ease;
        }
        
        .dropdown-item.py-3:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Okundu işaretleme animasyonu */
        .notification-read {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .notification-unread {
            opacity: 1;
            font-weight: 500;
        }
        
        /* Badge pulse animasyonu */
        .badge-notification {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
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
                <ul class="navbar-nav" style="align-items: center;">
                    <!-- Gelişmiş Bildirim Sistemi -->
                    <?php
                    try {
                        // NotificationManager'ı dahil et
                        if (!class_exists('NotificationManager')) {
                            require_once __DIR__ . '/../includes/NotificationManager.php';
                        }
                        
                        // Admin bildirimleri için gerçek veriler
                        $adminNotifications = [];
                        $unreadCount = 0;
                        
                        if (isset($_SESSION['user_id']) && isAdmin()) {
                            $notificationManager = new NotificationManager($pdo);
                            
                            // Tüm admin kullanıcılarını al
                            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
                            $stmt->execute();
                            $adminUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            // Mevcut admin için bildirimleri al
                            if (in_array($_SESSION['user_id'], $adminUsers)) {
                                // Header için son 10 bildirim al (daha fazla göstermek için)
                                $adminNotifications = $notificationManager->getUserNotifications($_SESSION['user_id'], 10, false);
                                $unreadCount = $notificationManager->getUnreadCount($_SESSION['user_id']);
                            }
                            
                            // Ek admin-specific bildirimler
                            // Bekleyen dosyalar
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE status = 'pending'");
                            $stmt->execute();
                            $pendingFiles = $stmt->fetchColumn();
                            
                            // Bekleyen revize talepleri
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE status = 'pending'");
                            $stmt->execute();
                            $pendingRevisions = $stmt->fetchColumn();
                            
                            // Eğer statik bildirimler de eklemek istiyorsak
                            if (empty($adminNotifications)) {
                                $adminNotifications = [];
                                if ($pendingFiles > 0) {
                                    $adminNotifications[] = [
                                        'id' => 'pending_files',
                                        'type' => 'file_upload',
                                        'title' => 'Bekleyen Dosyalar',
                                        'message' => $pendingFiles . ' dosya onay bekliyor',
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'is_read' => false,
                                        'action_url' => 'uploads.php?status=pending'
                                    ];
                                }
                                if ($pendingRevisions > 0) {
                                    $adminNotifications[] = [
                                        'id' => 'pending_revisions',
                                        'type' => 'revision_request',
                                        'title' => 'Bekleyen Revize Talepleri',
                                        'message' => $pendingRevisions . ' revize talebi bekliyor',
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'is_read' => false,
                                        'action_url' => 'revisions.php?status=pending'
                                    ];
                                }
                                $unreadCount = $pendingFiles + $pendingRevisions;
                            }
                        }
                    } catch(Exception $e) {
                        error_log('Admin notification error: ' . $e->getMessage());
                        $adminNotifications = [];
                        $unreadCount = 0;
                    }
                    ?>
                    
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link position-relative p-2" href="#" id="adminNotificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg text-white"></i>
                            <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger badge-notification">
                                <?php echo $unreadCount; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 380px; max-height: 500px; overflow-y: auto;">
                            <li class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Admin Bildirimleri</span>
                                <span class="badge bg-primary"><?php echo count($adminNotifications); ?></span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <?php foreach ($adminNotifications as $notification): ?>
                            <li>
                                <a class="dropdown-item py-3 <?php echo ($notification['is_read'] ?? false) ? '' : 'bg-light'; ?>" 
                                   href="<?php echo htmlspecialchars($notification['action_url'] ?? '#'); ?>" 
                                   onclick="markNotificationRead('<?php echo htmlspecialchars($notification['id']); ?>'); return true;" 
                                   data-notification-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <div class="<?php 
                                                switch($notification['type']) {
                                                    case 'user_registration':
                                                        echo 'bg-success bg-opacity-10 p-2 rounded-circle';
                                                        break;
                                                    case 'file_upload':
                                                        echo 'bg-warning bg-opacity-10 p-2 rounded-circle';
                                                        break;
                                                    case 'revision_request':
                                                        echo 'bg-info bg-opacity-10 p-2 rounded-circle';
                                                        break;
                                                    case 'system_warning':
                                                        echo 'bg-danger bg-opacity-10 p-2 rounded-circle';
                                                        break;
                                                    default:
                                                        echo 'bg-info bg-opacity-10 p-2 rounded-circle';
                                                }
                                            ?>">
                                                <i class="<?php 
                                                    switch($notification['type']) {
                                                        case 'user_registration':
                                                            echo 'fas fa-user-plus text-success';
                                                            break;
                                                        case 'file_upload':
                                                            echo 'fas fa-upload text-warning';
                                                            break;
                                                        case 'revision_request':
                                                            echo 'fas fa-edit text-info';
                                                            break;
                                                        case 'system_warning':
                                                            echo 'fas fa-exclamation-triangle text-danger';
                                                            break;
                                                        default:
                                                            echo 'fas fa-info-circle text-info';
                                                    }
                                                ?>"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($notification['title']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars(substr($notification['message'], 0, 100)); ?><?php echo strlen($notification['message']) > 100 ? '...' : ''; ?></div>
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo isset($notification['created_at']) ? date('d.m.Y H:i', strtotime($notification['created_at'])) : 'Yeni'; ?>
                                            </div>
                                        </div>
                                        <?php if (!($notification['is_read'] ?? false)): ?>
                                        <div class="ms-2">
                                            <span class="bg-primary rounded-circle" style="width: 8px; height: 8px; display: inline-block;"></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <div class="d-flex justify-content-between px-3 py-2">
                                    <a href="#" class="btn btn-sm btn-outline-secondary" onclick="markAllNotificationsRead(); return false;">Tümünü Okundu İşaretle</a>
                                    <a href="notifications.php" class="small text-muted">Tüm bildirimleri gör</a>
                                </div>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Admin Kullanıcı Menüsü -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="admin-avatar me-2">
                                <i class="fas fa-user-shield fa-lg"></i>
                            </div>
                            <div class="admin-info">
                                <span class="fw-semibold"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?></span>
                                <small class="d-block text-white-75">Administrator</small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 280px;">
                            <li class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-shield fa-2x text-muted me-2"></i>
                                    <div>
                                        <div class="fw-semibold"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?></div>
                                        <small class="text-muted"><?php echo isset($_SESSION['email']) ? $_SESSION['email'] : 'admin@system.com'; ?></small>
                                        <div><span class="badge bg-warning text-dark small">Administrator</span></div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <li class="dropdown-header small text-uppercase text-muted">Sistem Yönetimi</li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="index.php">
                                    <i class="fas fa-tachometer-alt me-3 text-primary"></i>Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="users.php">
                                    <i class="fas fa-users me-3 text-info"></i>Kullanıcı Yönetimi
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="uploads.php">
                                    <i class="fas fa-folder me-3 text-warning"></i>Dosya Yönetimi
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="settings.php">
                                    <i class="fas fa-cog me-3 text-secondary"></i>Sistem Ayarları
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-header small text-uppercase text-muted">Güvenlik & Raporlar</li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="security-dashboard.php">
                                    <i class="fas fa-shield-alt me-3 text-success"></i>Güvenlik Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="logs.php">
                                    <i class="fas fa-clipboard-list me-3 text-dark"></i>Sistem Logları
                                </a>
                            </li>
                            <!-- <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="analytics.php">
                                    <i class="fas fa-chart-bar me-3 text-info"></i>Analitik Raporlar
                                </a>
                            </li> -->
                            
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-header small text-uppercase text-muted">Hesap İşlemleri</li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="../user/profile.php">
                                    <i class="fas fa-user me-3 text-primary"></i>Profil Ayarları
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="../user/">
                                    <i class="fas fa-user-circle me-3 text-secondary"></i>Kullanıcı Paneli
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-3"></i>Güvenli Çıkış
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

<!-- Admin Dropdown JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tooltip'leri aktifleştir (eğer varsa)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined') {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Dropdown animasyonları
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('show.bs.dropdown', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        toggle.addEventListener('hide.bs.dropdown', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Bildirim işaretleme fonksiyonları
    window.markNotificationRead = function(notificationId) {
        // Bildirimi görsel olarak okundu olarak işaretle
        const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (notificationElement) {
            notificationElement.classList.remove('bg-light');
            // Okunmamış işaret noktasını kaldır
            const unreadDot = notificationElement.querySelector('.bg-primary.rounded-circle');
            if (unreadDot) {
                unreadDot.style.display = 'none';
            }
        }
        
        // AJAX ile bildirimi orundu olarak işaretle
        fetch('ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Bildirim sayısını güncelle
                updateNotificationBadge();
                
                // Eğer hiç okunmamış bildirim kalmadıysa badge'i kaldır
                setTimeout(() => {
                    const remainingUnread = document.querySelectorAll('.dropdown-item.bg-light');
                    if (remainingUnread.length === 0) {
                        const badges = document.querySelectorAll('#adminNotificationDropdown .badge');
                        badges.forEach(badge => badge.remove());
                    }
                }, 100);
            } else {
                console.error('Bildirim okundu olarak işaretlenemedi:', data.message);
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    };
    
    window.markAllNotificationsRead = function() {
        // Tüm bildirimleri görsel olarak okundu olarak işaretle
        const unreadNotifications = document.querySelectorAll('.dropdown-item.bg-light');
        unreadNotifications.forEach(notification => {
            notification.classList.remove('bg-light');
            // Okunmamış işaret noktalarını kaldır
            const unreadDot = notification.querySelector('.bg-primary.rounded-circle');
            if (unreadDot) {
                unreadDot.style.display = 'none';
            }
        });
        
        // Badge'i KESIN olarak sıfırla - Tüm olası selektorları kullan
        const badges = document.querySelectorAll('#adminNotificationDropdown .badge, .badge-notification, .position-absolute.badge');
        badges.forEach(badge => {
            if (badge) {
                badge.remove(); // Elementi tamamen kaldır
            }
        });
        
        // Ek güvenlik için parent element üzerinden de kontrol et
        const notificationLink = document.querySelector('#adminNotificationDropdown');
        if (notificationLink) {
            const allBadges = notificationLink.querySelectorAll('.badge');
            allBadges.forEach(badge => badge.remove());
        }
        
        // AJAX ile tüm bildirimleri okundu olarak işaretle
        fetch('ajax/mark_all_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Tüm bildirimler okundu olarak işaretlendi');
                
                // Bildirim sayısını güncelle
                updateNotificationBadge();
                
                // Ek güvenlik: 500ms sonra tekrar kontrol et ve badge varsa kaldır
                setTimeout(() => {
                    const remainingBadges = document.querySelectorAll('#adminNotificationDropdown .badge');
                    remainingBadges.forEach(badge => {
                        console.log('Kalıcı badge kaldırılıyor:', badge);
                        badge.remove();
                    });
                    
                    // Dropdown header badge'ini de sıfırla
                    const headerBadge = document.querySelector('.dropdown-header .badge');
                    if (headerBadge) {
                        headerBadge.textContent = '0';
                    }
                }, 500);
                
            } else {
                console.error('Error marking all notifications as read:', data.message);
                // Hata durumunda sayfayı yenile
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
            // Hata durumunda sayfayı yenile
            location.reload();
        });
        return false;
    };
    
    // Notification Badge güncelleme fonksiyonunu global scope'a ata
    window.updateNotificationBadge = function() {
        // Okunmamış bildirim sayısını güncelle
        fetch('ajax/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Önce mevcut badge'leri tamamen kaldır
                const existingBadges = document.querySelectorAll('#adminNotificationDropdown .badge');
                existingBadges.forEach(badge => badge.remove());
                
                // Eğer bildirim varsa yeni badge oluştur
                if (data.count > 0) {
                    const notificationLink = document.querySelector('#adminNotificationDropdown');
                    if (notificationLink) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger badge-notification';
                        newBadge.textContent = data.count;
                        notificationLink.appendChild(newBadge);
                    }
                }
                
                // Dropdown header'daki sayıyı da güncelle
                const headerBadge = document.querySelector('.dropdown-header .badge');
                if (headerBadge) {
                    headerBadge.textContent = data.count;
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
        });
    };
    
    // Dropdown hover efektleri
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        if (!item.classList.contains('py-3')) {
            item.addEventListener('mouseenter', function() {
                this.style.paddingLeft = '1.5rem';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.paddingLeft = '1rem';
            });
        }
    });
    
    // Navbar responsive özellikler
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            this.classList.toggle('active');
        });
    }
    
    // Admin Dashboard fonksiyonlarını global scope'a ata
    window.refreshNotifications = function() {
        // Gerçek sistemde AJAX ile sunucudan yeni bildirimler çekilecek
        console.log('Bildirimler yenileniyor...');
        
        // Simulated notification refresh
        setTimeout(() => {
            console.log('Bildirimler yenilendi');
        }, 1000);
    };
    
    // Auto-refresh notifications every 30 seconds
    setInterval(window.refreshNotifications, 30000);
    
    // Auto-refresh admin notifications
    setInterval(window.updateNotificationBadge, 30000);
    
    // Sayfa yüklenirken badge durumunu kontrol et
    window.updateNotificationBadge();
    
    // Badge temizleme güvenlik fonksiyonu
    window.ensureBadgeCleared = function() {
        const badges = document.querySelectorAll('#adminNotificationDropdown .badge');
        badges.forEach(badge => {
            badge.remove();
        });
        console.log('Badge temizleme güvenlik fonksiyonu çalıştı');
    };
});
</script>
