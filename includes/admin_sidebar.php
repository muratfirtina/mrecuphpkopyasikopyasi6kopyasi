            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar">
                <div class="d-flex flex-column p-3">
                    
                    <!-- Hızlı İstatistikler -->
                    <div class="quick-stats mb-4">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end border-light border-opacity-25">
                                    <h5 class="mb-1"><?php 
                                        // Sidebar için kullanıcı sayısını al
                                        try {
                                            $sidebarUserStmt = $pdo->query("SELECT COUNT(*) FROM users");
                                            echo $sidebarUserStmt->fetchColumn();
                                        } catch(Exception $e) {
                                            echo '0';
                                        }
                                    ?></h5>
                                    <small class="opacity-75">Kullanıcı</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="mb-1"><?php 
                                    // Sidebar için dosya sayısını al
                                    try {
                                        $sidebarFileStmt = $pdo->query("SELECT COUNT(*) FROM file_uploads");
                                        echo $sidebarFileStmt->fetchColumn();
                                    } catch(Exception $e) {
                                        echo '0';
                                    }
                                ?></h5>
                                <small class="opacity-75">Dosya</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <nav class="nav nav-pills flex-column">
                        <!-- Dashboard Section -->
                        <div class="nav-section">Dashboard</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Admin Panel' || $pageTitle == 'Dashboard') ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-tachometer-alt"></i>Genel Bakış
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Raporlar') ? 'active' : ''; ?>" href="reports.php">
                            <i class="fas fa-chart-bar"></i>Raporlar
                        </a>
                        
                        <!-- Dosya Yönetimi -->
                        <div class="nav-section">Dosya Yönetimi</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Dosya Yüklemeleri') ? 'active' : ''; ?>" href="uploads.php">
                            <i class="fas fa-upload"></i>Yüklemeler
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Revizyon İşlemleri') ? 'active' : ''; ?>" href="revisions.php">
                            <i class="fas fa-history"></i>Revizyonlar
                        </a>

                        <a class="nav-link <?php echo ($pageTitle == 'Bildirimler') ? 'active' : ''; ?>" href="notifications.php">
                            <i class="fas fa-bell fa-lg text-white"></i>Bildirimler
                        </a>
                        
                        <!-- Kullanıcı Yönetimi -->
                        <div class="nav-section">Kullanıcı Yönetimi</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Kullanıcılar') ? 'active' : ''; ?>" href="users.php">
                            <i class="fas fa-users"></i>Kullanıcılar
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Kredi Yönetimi') ? 'active' : ''; ?>" href="credits.php">
                            <i class="fas fa-coins"></i>Kredi Yönetimi
                        </a>
                        
                        
                        <!-- Ürün Yönetimi -->
                        <div class="nav-section">Ürün Yönetimi</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Kategoriler') ? 'active' : ''; ?>" href="categories.php">
                            <i class="fas fa-tags"></i>Kategoriler
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Ürünler') ? 'active' : ''; ?>" href="products.php">
                            <i class="fas fa-box"></i>Ürünler
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Markalar') ? 'active' : ''; ?>" href="brands.php">
                            <i class="fas fa-certificate"></i>Araçlar
                        </a>
                        
                        <!-- Sistem Yönetimi -->
                        <div class="nav-section">Sistem</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'ECU Yönetimi') ? 'active' : ''; ?>" href="ecus.php">
                            <i class="fas fa-microchip"></i>ECU Yönetimi
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Device Yönetimi') ? 'active' : ''; ?>" href="devices.php">
                            <i class="fas fa-tools"></i>Device Yönetimi
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Sistem Ayarları') ? 'active' : ''; ?>" href="settings.php">
                            <i class="fas fa-cog"></i>Ayarlar
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Güvenlik Dashboard') ? 'active' : ''; ?>" href="security-dashboard.php">
                            <i class="fas fa-shield-alt"></i>Güvenlik
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Sistem Logları') ? 'active' : ''; ?>" href="logs.php">
                            <i class="fas fa-clipboard-list"></i>Loglar
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Debug Tools') ? 'active' : ''; ?>" href="debug.php">
                            <i class="fas fa-bug"></i>Debug
                        </a>
                        
                        <!-- Diğer İşlemler -->
                        <div class="nav-section">Diğer</div>
                        
                        <a class="nav-link text-warning" href="../user/">
                            <i class="fas fa-user"></i>Kullanıcı Paneli
                        </a>
                        
                        <a class="nav-link text-info" href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt"></i>Siteyi Görüntüle
                        </a>
                        
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>Çıkış Yap
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col-md-9 col-lg-10 admin-content">
                <div class="p-4">
                    
                    <!-- Sayfa Başlığı -->
                    <div class="admin-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">
                                    <?php if (isset($pageIcon)): ?>
                                        <i class="<?php echo $pageIcon; ?> me-2 text-primary"></i>
                                    <?php endif; ?>
                                    <?php echo $pageTitle; ?>
                                </h1>
                                <?php if (isset($pageDescription)): ?>
                                    <p class="text-muted mb-0"><?php echo $pageDescription; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Breadcrumb -->
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="index.php" class="text-decoration-none">
                                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                        </a>
                                    </li>
                                    <?php if ($pageTitle != 'Admin Panel' && $pageTitle != 'Dashboard'): ?>
                                        <li class="breadcrumb-item active" aria-current="page"><?php echo $pageTitle; ?></li>
                                    <?php endif; ?>
                                </ol>
                            </nav>
                        </div>
                        
                        <!-- Hızlı Eylemler (varsa) -->
                        <?php if (isset($quickActions) && is_array($quickActions)): ?>
                            <div class="mt-3">
                                <div class="btn-group" role="group">
                                    <?php foreach ($quickActions as $action): ?>
                                        <a href="<?php echo $action['url']; ?>" 
                                           class="btn btn-<?php echo $action['class'] ?? 'primary'; ?> btn-sm">
                                            <?php if (isset($action['icon'])): ?>
                                                <i class="<?php echo $action['icon']; ?> me-1"></i>
                                            <?php endif; ?>
                                            <?php echo $action['text']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sistem Durumu (Ana sayfada göster) -->
                    <?php if ($pageTitle == 'Admin Panel' || $pageTitle == 'Dashboard'): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-admin alert-info">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle me-3 fa-lg"></i>
                                        <div class="flex-grow-1">
                                            <strong>Sistem Durumu:</strong> Tüm sistemler normal çalışıyor.
                                            <br><small class="text-muted">Son güncelleme: <?php echo date('d.m.Y H:i'); ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-success">Aktif</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Sayfa İçeriği Başlangıcı -->
