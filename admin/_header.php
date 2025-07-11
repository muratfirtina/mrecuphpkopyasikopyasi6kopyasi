<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?> Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <style>
        body {
            padding-top: 56px; /* Fixed navbar için */
            background-color: #f8f9fa;
        }
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 20px 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #f8f9fa;
            width: 240px;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        main {
            margin-left: 240px;
            padding: 20px;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                top: 56px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            main {
                margin-left: 0;
            }
        }
        .nav-link.active {
            background-color: rgba(0, 123, 255, 0.1);
            border-radius: 0.375rem;
            color: #0d6efd !important;
        }
        .dropdown-menu {
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
    </style>
</head>
<body>
    <!-- Admin Panel Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-microchip me-2"></i>
            <?php echo SITE_NAME; ?> <span class="badge bg-danger">Admin</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                    <a class="nav-link" href="../user/">
                        <i class="fas fa-user me-1"></i>Kullanıcı Paneli
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <!-- Gelişmiş Bildirim Sistemi -->
                <?php
                try {
                    // Bekleyen dosyalar
                    $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads WHERE status = 'pending'");
                    $pendingCount = $stmt->fetchColumn();
                    
                    // Bekleyen revize talepleri
                    $stmt = $pdo->query("SELECT COUNT(*) FROM revisions WHERE status = 'pending'");
                    $pendingRevisions = $stmt->fetchColumn();
                    
                    // Admin bildirimlerini al (sadece admin kullanıcıları için)
                    $adminNotifications = [];
                    $unreadNotificationCount = 0;
                    
                    if (isset($_SESSION['user_id'])) {
                        $notificationManager = new NotificationManager($pdo);
                        $adminNotifications = $notificationManager->getUserNotifications($_SESSION['user_id'], 5, true);
                        $unreadNotificationCount = $notificationManager->getUnreadCount($_SESSION['user_id']);
                    }
                    
                } catch(PDOException $e) {
                    $pendingCount = 0;
                    $pendingRevisions = 0;
                    $adminNotifications = [];
                    $unreadNotificationCount = 0;
                }
                ?>
                <?php if ($pendingCount > 0): ?>
                <li class="nav-item">
                    <a class="nav-link" href="uploads.php?status=pending">
                        <i class="fas fa-bell text-warning me-1"></i>
                        <span class="badge bg-warning text-dark"><?php echo $pendingCount; ?></span>
                        Bekleyen Dosya
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($pendingRevisions > 0): ?>
                <li class="nav-item">
                    <a class="nav-link" href="revisions.php?status=pending">
                        <i class="fas fa-edit text-info me-1"></i>
                        <span class="badge bg-info text-dark"><?php echo $pendingRevisions; ?></span>
                        Revize Talebi
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Gelişmiş Bildirim Dropdown -->
                <?php if ($unreadNotificationCount > 0): ?>
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative" href="#" id="adminNotificationDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fa-lg text-white"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $unreadNotificationCount; ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Bildirimler</span>
                            <span class="badge bg-primary"><?php echo $unreadNotificationCount; ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <?php foreach ($adminNotifications as $notification): ?>
                        <li>
                            <a class="dropdown-item py-3 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                               href="<?php echo $notification['action_url'] ?: '#'; ?>" 
                               onclick="markNotificationRead('<?php echo htmlspecialchars($notification['id']); ?>')">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <?php 
                                        $iconClass = 'fas fa-info-circle text-info';
                                        switch($notification['type']) {
                                            case 'file_upload':
                                                $iconClass = 'fas fa-upload text-success';
                                                break;
                                            case 'revision_request':
                                                $iconClass = 'fas fa-edit text-warning';
                                                break;
                                            case 'file_status_update':
                                                $iconClass = 'fas fa-check-circle text-primary';
                                                break;
                                        }
                                        ?>
                                        <i class="<?php echo $iconClass; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars(substr($notification['message'], 0, 100)); ?><?php echo strlen($notification['message']) > 100 ? '...' : ''; ?></div>
                                        <div class="text-muted small mt-1">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        
                        <?php if (empty($adminNotifications)): ?>
                        <li class="dropdown-item text-center text-muted py-3">
                            <i class="fas fa-bell-slash fa-2x mb-2"></i><br>
                            Henüz bildirim yok
                        </li>
                        <?php endif; ?>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <div class="d-flex justify-content-between px-3 py-2">
                                <a href="#" class="btn btn-sm btn-outline-secondary" onclick="markAllNotificationsRead()">Tümünü Okundu İşaretle</a>
                                <a href="notifications.php" class="btn btn-sm btn-primary">Tüm Bildirimler</a>
                            </div>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-1"></i>
                        <?php echo $_SESSION['username']; ?>
                        <span class="badge bg-success ms-1"><?php echo number_format($_SESSION['credits'], 2); ?> Kredi</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Admin İşlemleri</h6></li>
                        <li><a class="dropdown-item" href="index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="uploads.php"><i class="fas fa-folder me-2"></i>Dosyalar</a></li>
                        <li><a class="dropdown-item" href="users.php"><i class="fas fa-users me-2"></i>Kullanıcılar</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Katalog Yönetimi</h6></li>
                        <li><a class="dropdown-item" href="categories.php"><i class="fas fa-tags me-2"></i>Kategoriler</a></li>
                        <li><a class="dropdown-item" href="products.php"><i class="fas fa-box me-2"></i>Ürünler</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Sistem</h6></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Ayarlar</a></li>
                        <li><a class="dropdown-item" href="logs.php"><i class="fas fa-history me-2"></i>Loglar</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Hesap</h6></li>
                        <li><a class="dropdown-item" href="../user/profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Admin Bildirim JavaScript -->
    <script src="../assets/js/notifications.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
