<!-- Admin Panel Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Dosya Yönetimi</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'uploads.php' ? 'active' : ''; ?>" href="uploads.php">
                    <i class="fas fa-folder"></i>
                    Tüm Dosyalar
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads WHERE status = 'pending'");
                        $pendingCount = $stmt->fetchColumn();
                        if ($pendingCount > 0) {
                            echo '<span class="badge bg-warning ms-2">' . $pendingCount . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="uploads.php?status=pending">
                    <i class="fas fa-clock"></i>
                    Bekleyen Dosyalar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="uploads.php?status=processing">
                    <i class="fas fa-spinner"></i>
                    İşleniyor
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="uploads.php?status=completed">
                    <i class="fas fa-check-circle"></i>
                    Tamamlanan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'revisions.php' ? 'active' : ''; ?>" href="revisions.php">
                    <i class="fas fa-edit"></i>
                    Revize Talepleri
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM revisions WHERE status = 'pending'");
                        $pendingRevisions = $stmt->fetchColumn();
                        if ($pendingRevisions > 0) {
                            echo '<span class="badge bg-warning ms-2">' . $pendingRevisions . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Kullanıcı Yönetimi</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    Kullanıcılar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'credits.php' ? 'active' : ''; ?>" href="credits.php">
                    <i class="fas fa-coins"></i>
                    Kredi Yönetimi
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Güvenlik</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'security-dashboard.php' ? 'active' : ''; ?>" href="security-dashboard.php">
                    <i class="fas fa-shield-alt"></i>
                    Güvenlik Dashboard
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) AND event_type IN ('sql_injection_attempt', 'xss_attempt', 'brute_force_detected')");
                        $threatCount = $stmt->fetchColumn();
                        if ($threatCount > 0) {
                            echo '<span class="badge bg-danger ms-2">' . $threatCount . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Sistem Yönetimi</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'brands.php' ? 'active' : ''; ?>" href="brands.php">
                    <i class="fas fa-car"></i>
                    Marka/Model
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                    <i class="fas fa-tags"></i>
                    Kategoriler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>" href="products.php">
                    <i class="fas fa-box"></i>
                    Ürünler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Ayarlar
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Raporlar</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : ''; ?>" href="logs.php">
                    <i class="fas fa-history"></i>
                    Sistem Logları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                    <i class="fas fa-exchange-alt"></i>
                    İşlemler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    Raporlar
                </a>
            </li>
        </ul>

        <!-- Sistem Durumu Widget -->
        <div class="px-3 mt-4">
            <div class="card border-0 bg-light">
                <div class="card-body p-3">
                    <h6 class="card-title text-muted mb-2">
                        <i class="fas fa-server me-1"></i>Sistem Durumu
                    </h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted">CPU</small>
                            <div class="text-success"><strong>Normal</strong></div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Disk</small>
                            <div class="text-success"><strong>OK</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hızlı İstatistikler -->
        <div class="px-3 mt-3">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body p-3">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-chart-line me-1"></i>Bugün
                    </h6>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads WHERE DATE(upload_date) = CURDATE()");
                        $todayUploads = $stmt->fetchColumn();
                        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
                        $todayUsers = $stmt->fetchColumn();
                    } catch(PDOException $e) {
                        $todayUploads = 0;
                        $todayUsers = 0;
                    }
                    ?>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h5 class="mb-0"><?php echo $todayUploads; ?></h5>
                                <small>Dosya</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h5 class="mb-0"><?php echo $todayUsers; ?></h5>
                                <small>Kullanıcı</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
