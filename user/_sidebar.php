<!-- User Panel Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse modern-sidebar">
    <div class="position-sticky pt-3">
        <!-- Ana Menü -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <div class="nav-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- Dosya İşlemleri -->
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Dosya İşlemleri</span>
            <i class="fas fa-folder-open"></i>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'upload.php' ? 'active' : ''; ?>" href="upload.php">
                    <div class="nav-icon bg-success">
                        <i class="fas fa-upload"></i>
                    </div>
                    <span>Dosya Yükle</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'files.php' ? 'active' : ''; ?>" href="files.php">
                    <div class="nav-icon bg-primary">
                        <i class="fas fa-folder"></i>
                    </div>
                    <span>Dosyalarım</span>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND status = 'completed' AND notified = 0");
                        $stmt->execute([$_SESSION['user_id']]);
                        $newFiles = $stmt->fetchColumn();
                        if ($newFiles > 0) {
                            echo '<span class="badge bg-success ms-auto">' . $newFiles . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="files.php?status=pending">
                    <div class="nav-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span>Bekleyen Dosyalar</span>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND status = 'pending'");
                        $stmt->execute([$_SESSION['user_id']]);
                        $pendingFiles = $stmt->fetchColumn();
                        if ($pendingFiles > 0) {
                            echo '<span class="badge bg-warning ms-auto">' . $pendingFiles . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="files.php?status=processing">
                    <div class="nav-icon bg-info">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <span>İşlenen Dosyalar</span>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND status = 'processing'");
                        $stmt->execute([$_SESSION['user_id']]);
                        $processingFiles = $stmt->fetchColumn();
                        if ($processingFiles > 0) {
                            echo '<span class="badge bg-info ms-auto">' . $processingFiles . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'revisions.php' ? 'active' : ''; ?>" href="revisions.php">
                    <div class="nav-icon bg-orange">
                        <i class="fas fa-edit"></i>
                    </div>
                    <span>Revize Taleplerim</span>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE user_id = ? AND status = 'pending'");
                        $stmt->execute([$_SESSION['user_id']]);
                        $pendingUserRevisions = $stmt->fetchColumn();
                        if ($pendingUserRevisions > 0) {
                            echo '<span class="badge bg-warning ms-auto">' . $pendingUserRevisions . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
        </ul>

        <!-- Hesap Yönetimi -->
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Hesap Yönetimi</span>
            <i class="fas fa-user-cog"></i>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <div class="nav-icon bg-secondary">
                        <i class="fas fa-user"></i>
                    </div>
                    <span>Profil Ayarları</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'credits.php' ? 'active' : ''; ?>" href="credits.php">
                    <div class="nav-icon bg-warning">
                        <i class="fas fa-coins"></i>
                    </div>
                    <span>Kredi İşlemleri</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                    <div class="nav-icon bg-info">
                        <i class="fas fa-history"></i>
                    </div>
                    <span>İşlem Geçmişi</span>
                </a>
            </li>
        </ul>

        <!-- Kredi Durumu Widget -->
        <div class="px-3 mt-4">
            <div class="sidebar-widget gradient-card">
                <div class="widget-content">
                    <div class="widget-header">
                        <i class="fas fa-coins widget-icon"></i>
                        <h6 class="widget-title">Kredi Bakiyesi</h6>
                    </div>
                    <div class="widget-value">
                        <?php echo number_format($_SESSION['credits'], 2); ?> <span class="currency">TL</span>
                    </div>
                    <div class="widget-actions">
                        <a href="credits.php" class="btn btn-sm btn-light-custom">
                            <i class="fas fa-plus me-1"></i>Yükle
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- İstatistik Widget -->
        <!-- <div class="px-3 mt-3">
            <div class="sidebar-widget stats-card">
                <div class="widget-content">
                    <div class="widget-header">
                        <i class="fas fa-chart-bar widget-icon"></i>
                        <h6 class="widget-title">Bu Ay</h6>
                    </div>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND MONTH(upload_date) = MONTH(CURRENT_DATE()) AND YEAR(upload_date) = YEAR(CURRENT_DATE())");
                        $stmt->execute([$_SESSION['user_id']]);
                        $monthlyUploads = $stmt->fetchColumn();
                        
                        $stmt = $pdo->prepare("SELECT SUM(amount) FROM credit_transactions WHERE user_id = ? AND transaction_type = 'deduct' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
                        $stmt->execute([$_SESSION['user_id']]);
                        $monthlySpent = $stmt->fetchColumn() ?: 0;
                    } catch(PDOException $e) {
                        $monthlyUploads = 0;
                        $monthlySpent = 0;
                    }
                    ?>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $monthlyUploads; ?></div>
                            <div class="stat-label">Dosya</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($monthlySpent, 0); ?></div>
                            <div class="stat-label">Harcama</div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Destek Widget -->
        <div class="px-3 mt-3">
            <div class="sidebar-widget support-card">
                <div class="widget-content">
                    <div class="widget-header">
                        <i class="fas fa-headset widget-icon"></i>
                        <h6 class="widget-title">Yardım & Destek</h6>
                    </div>
                    <p class="widget-text">Herhangi bir sorunla karşılaştığınızda bizimle iletişime geçebilirsiniz.</p>
                    <div class="widget-actions">
                        <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-sm btn-light-custom">
                            <i class="fas fa-envelope me-1"></i>İletişim
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.modern-sidebar {
    background: #f8f9fa;
    border-right: 1px solid #e9ecef;
    min-height: calc(100vh - 56px);
}

.sidebar-heading {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #6c757d;
    border-radius: 8px;
    margin: 0 0.5rem 0.25rem 0.5rem;
    transition: all 0.2s ease;
    text-decoration: none;
    position: relative;
}

.nav-link:hover {
    color: #495057;
    background-color: #e9ecef;
    transform: translateX(2px);
}

.nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.nav-link.active .nav-icon {
    background-color: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

.nav-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 0.875rem;
    color: #fff;
    background-color: #6c757d;
}

.nav-icon.bg-success { background-color: #28a745 !important; }
.nav-icon.bg-primary { background-color: #007bff !important; }
.nav-icon.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
.nav-icon.bg-info { background-color: #17a2b8 !important; }
.nav-icon.bg-orange { background-color: #fd7e14 !important; }
.nav-icon.bg-secondary { background-color: #6c757d !important; }

/* Widget Stilleri */
.sidebar-widget {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 1rem;
}

.gradient-card {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    color: white;
}

.stats-card {
    
    color: green;
}

.support-card {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    color: white;
}

.widget-content {
    padding: 1rem;
}

.widget-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.widget-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.5rem;
    font-size: 0.875rem;
}

.widget-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
}

.widget-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.currency {
    font-size: 0.875rem;
    opacity: 0.8;
}

.widget-text {
    font-size: 0.8rem;
    margin-bottom: 0.75rem;
    opacity: 0.9;
    line-height: 1.4;
}

.widget-actions {
    margin-top: auto;
}

.btn-light-custom {
    background-color: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    font-size: 0.8rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.btn-light-custom:hover {
    background-color: rgba(255,255,255,0.3);
    color: white;
    transform: translateY(-1px);
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 0.25rem;
}

.badge {
    font-size: 0.7rem;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 767.98px) {
    .modern-sidebar {
        border-right: none;
        border-bottom: 1px solid #e9ecef;
    }
    
    .sidebar-widget {
        margin-bottom: 0.75rem;
    }
    
    .widget-content {
        padding: 0.75rem;
    }
}
</style>