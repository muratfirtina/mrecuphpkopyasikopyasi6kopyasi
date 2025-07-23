<?php
// Ters kredi sistemi için kullanıcı kredi bilgilerini al
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT credit_quota, credit_used FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $sidebarCreditInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sidebarCreditQuota = $sidebarCreditInfo['credit_quota'] ?? 0;
        $sidebarCreditUsed = $sidebarCreditInfo['credit_used'] ?? 0;
        $sidebarAvailableCredits = $sidebarCreditQuota - $sidebarCreditUsed;
        $sidebarUsagePercentage = $sidebarCreditQuota > 0 ? ($sidebarCreditUsed / $sidebarCreditQuota) * 100 : 0;
        
        // Session'a kullanılabilir kredi bilgisini kaydet (eski sistemle uyumluluk için)
        $_SESSION['credits'] = $sidebarAvailableCredits;
    } catch(PDOException $e) {
        $sidebarCreditQuota = 0;
        $sidebarCreditUsed = 0;
        $sidebarAvailableCredits = 0;
        $sidebarUsagePercentage = 0;
        $_SESSION['credits'] = 0;
    }
} else {
    $sidebarCreditQuota = 0;
    $sidebarCreditUsed = 0;
    $sidebarAvailableCredits = 0;
    $sidebarUsagePercentage = 0;
}
?>

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

        <!-- Kredi Durumu Widget - Ters Kredi Sistemi -->
        <div class="px-3 mt-4">
            <div class="sidebar-widget gradient-card credit-widget-enhanced">
                <div class="widget-content">
                    <!-- Widget Başlığı -->
                    <div class="widget-header">
                        <i class="fas fa-wallet widget-icon"></i>
                        <div class="header-info">
                            <h6 class="widget-title">Kredi Durumu</h6>
                            <small class="widget-subtitle">Kullanılabilir Bakiye</small>
                        </div>
                        <div class="credit-status-indicator">
                            <?php if ($sidebarAvailableCredits > 0): ?>
                                <span class="status-dot active" title="Aktif"></span>
                            <?php else: ?>
                                <span class="status-dot depleted" title="Tükendi"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Ana Kredi Gösterimi -->
                    <div class="credit-main-display">
                        <span class="amount-value"><?php echo number_format($sidebarCreditUsed, 0); ?></span>
                        <span class="amount-currency">TL</span>
                        <span class="quota-info">/ <?php echo number_format($sidebarCreditQuota, 0); ?> TL</span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="credit-progress-sidebar">
                        <div class="progress-container">
                            <div class="progress-bar-sidebar">
                                <div class="progress-used-sidebar" 
                                     style="width: <?php echo $sidebarUsagePercentage; ?>%;"
                                     data-bs-toggle="tooltip" 
                                     title="Kullanılan: <?php echo number_format($sidebarCreditUsed, 2); ?> TL">
                                </div>
                                <div class="progress-remaining-sidebar" 
                                     style="width: <?php echo 100 - $sidebarUsagePercentage; ?>%;"
                                     data-bs-toggle="tooltip" 
                                     title="Kalan: <?php echo number_format($sidebarAvailableCredits, 2); ?> TL">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progress İstatistikleri -->
                        <div class="progress-stats">
                            <div class="stat-used">
                                <i class="fas fa-minus-circle"></i>
                                <span><?php echo number_format($sidebarCreditUsed, 0); ?> TL</span>
                            </div>
                            <div class="stat-remaining">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo number_format($sidebarAvailableCredits, 0); ?> TL</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Yüzde Gösterimi -->
                    <div class="usage-percentage-display">
                        <div class="percentage-circle">
                            <div class="percentage-text">
                                <?php echo number_format(100 - $sidebarUsagePercentage, 0); ?>%
                            </div>
                            <div class="percentage-label">Kalan</div>
                        </div>
                    </div>
                    
                    <!-- Widget Aksiyonları -->
                    <div class="widget-actions">
                        <div class="action-buttons">
                            <a href="credits.php" class="btn btn-sm btn-light-custom btn-secondary-action">
                                <i class="fas fa-history me-1"></i>Geçmiş
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

/* Ters Kredi Sistemi Widget Geliştirmeleri */
.credit-widget-enhanced {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    color: white;
    position: relative;
    overflow: visible;
}

.credit-widget-enhanced::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
    border-radius: 14px;
    z-index: -1;
}

.widget-content {
    padding: 1.25rem;
    position: relative;
    z-index: 1;
}

.widget-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.header-info {
    flex: 1;
}

.widget-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1rem;
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    backdrop-filter: blur(10px);
}

.widget-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.2;
}

.widget-subtitle {
    font-size: 0.75rem;
    opacity: 0.8;
    font-weight: 400;
}

/* Status Indicator */
.credit-status-indicator {
    margin-left: 0.5rem;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s infinite;
}

.status-dot.active {
    background: #28a745;
    box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
}

.status-dot.depleted {
    background: #dc3545;
    box-shadow: 0 0 8px rgba(220, 53, 69, 0.6);
}

@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
    100% { opacity: 1; transform: scale(1); }
}

/* Ana Kredi Gösterimi */
.credit-main-display {
    text-align: center;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.amount-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}

.amount-currency {
    font-size: 1rem;
    font-weight: 500;
    margin-left: 0.25rem;
    opacity: 0.8;
}

.quota-info {
    font-size: 0.8rem;
    opacity: 0.7;
    font-weight: 400;
    margin-left: 0.25rem;
}

/* Progress Bar */
.credit-progress-sidebar {
    margin: 1rem 0;
}

.progress-container {
    margin-bottom: 0.75rem;
}

.progress-bar-sidebar {
    display: flex;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    background: rgba(255,255,255,0.2);
    margin-bottom: 0.5rem;
}

.progress-used-sidebar {
    background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
    transition: width 1.2s ease-out;
    border-radius: 3px 0 0 3px;
}

.progress-remaining-sidebar {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    transition: width 1.2s ease-out;
    border-radius: 0 3px 3px 0;
}

/* Progress İstatistikleri */
.progress-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    opacity: 0.9;
}

.stat-used, .stat-remaining {
    display: flex;
    align-items: center;
    font-weight: 500;
}

.stat-used i {
    color: #dc3545;
    margin-right: 0.25rem;
    font-size: 0.65rem;
}

.stat-remaining i {
    color: #28a745;
    margin-right: 0.25rem;
    font-size: 0.65rem;
}

/* Yüzde Gösterimi */
.usage-percentage-display {
    display: flex;
    justify-content: center;
    margin: 1rem 0;
}

.percentage-circle {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.2);
}

.percentage-text {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}

.percentage-label {
    font-size: 0.6rem;
    opacity: 0.8;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.1rem;
}

/* Widget Aksiyonları */
.widget-actions {
    margin-top: 1rem;
}

.action-buttons {
    display: grid;
    gap: 0.5rem;
}

.btn-primary-action {
    background-color: rgba(255,255,255,0.25) !important;
    border: 1px solid rgba(255,255,255,0.4) !important;
    color: white !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    padding: 0.5rem 0.75rem !important;
}

.btn-secondary-action {
    background-color: rgba(255,255,255,0.1) !important;
    border: 1px solid rgba(255,255,255,0.2) !important;
    color: rgba(255,255,255,0.9) !important;
    font-size: 0.75rem !important;
    font-weight: 500 !important;
    padding: 0.5rem 0.75rem !important;
}

.btn-primary-action:hover {
    background-color: rgba(255,255,255,0.35) !important;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-secondary-action:hover {
    background-color: rgba(255,255,255,0.2) !important;
    color: white !important;
    transform: translateY(-1px);
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
        padding: 1rem;
    }
    
    /* Kredi Widget Responsive */
    .amount-value {
        font-size: 1.5rem;
    }
    
    .percentage-circle {
        width: 50px;
        height: 50px;
    }
    
    .percentage-text {
        font-size: 0.9rem;
    }
    
    .percentage-label {
        font-size: 0.55rem;
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .btn-primary-action, .btn-secondary-action {
        padding: 0.6rem 1rem !important;
        font-size: 0.8rem !important;
    }
}

@media (max-width: 575.98px) {
    .credit-widget-enhanced {
        margin: 0.5rem;
    }
    
    .amount-value {
        font-size: 1.4rem;
    }
    
    .quota-info {
        font-size: 0.75rem;
    }
    
    .progress-stats {
        font-size: 0.65rem;
    }
    
    .stat-used i, .stat-remaining i {
        font-size: 0.6rem;
    }
}
</style>

<!-- Sidebar Kredi Widget JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar kredi widget animasyonları
    initSidebarCreditAnimations();
    
    function initSidebarCreditAnimations() {
        // Progress bar animasyonu
        const progressBars = document.querySelectorAll('.progress-used-sidebar, .progress-remaining-sidebar');
        progressBars.forEach((bar, index) => {
            const finalWidth = bar.style.width;
            bar.style.width = '0%';
            
            setTimeout(() => {
                bar.style.transition = 'width 1.5s ease-out';
                bar.style.width = finalWidth;
            }, 400 + (index * 200));
        });
        
        // Percentage circle animasyonu
        const percentageCircle = document.querySelector('.percentage-circle');
        if (percentageCircle) {
            percentageCircle.style.opacity = '0';
            percentageCircle.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                percentageCircle.style.transition = 'all 0.8s ease-out';
                percentageCircle.style.opacity = '1';
                percentageCircle.style.transform = 'scale(1)';
            }, 800);
        }
        
        // Tooltip'leri aktifleştir
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('.sidebar-widget [data-bs-toggle="tooltip"]'));
        if (typeof bootstrap !== 'undefined') {
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    placement: 'top'
                });
            });
        }
        
        // Widget hover efektleri
        const creditWidget = document.querySelector('.credit-widget-enhanced');
        if (creditWidget) {
            creditWidget.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            creditWidget.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        }
        
        // Button hover animasyonları
        const actionButtons = document.querySelectorAll('.btn-primary-action, .btn-secondary-action');
        actionButtons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-1px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    }
    
    // Kredi değişikliklerini dinle (AJAX güncellemeleri için)
    window.updateSidebarCredit = function(availableCredits, creditQuota, creditUsed) {
        const amountElement = document.querySelector('.amount-value');
        const quotaElement = document.querySelector('.quota-info');
        const percentageElement = document.querySelector('.percentage-text');
        const progressUsed = document.querySelector('.progress-used-sidebar');
        const progressRemaining = document.querySelector('.progress-remaining-sidebar');
        const statUsed = document.querySelector('.stat-used span');
        const statRemaining = document.querySelector('.stat-remaining span');
        const statusDot = document.querySelector('.status-dot');
        
        if (amountElement) {
            // Animasyonlu sayı güncellemesi
            animateNumber(amountElement, parseInt(amountElement.textContent.replace(/[^0-9]/g, '')), creditUsed, 1000);
        }
        
        if (quotaElement) {
            quotaElement.textContent = `/ ${creditQuota.toLocaleString()} TL`;
        }
        
        const usagePercentage = creditQuota > 0 ? (creditUsed / creditQuota) * 100 : 0;
        const remainingPercentage = 100 - usagePercentage;
        
        if (percentageElement) {
            animateNumber(percentageElement, parseInt(percentageElement.textContent), Math.round(remainingPercentage), 800);
        }
        
        if (progressUsed) {
            progressUsed.style.width = usagePercentage + '%';
        }
        
        if (progressRemaining) {
            progressRemaining.style.width = remainingPercentage + '%';
        }
        
        if (statUsed) {
            statUsed.textContent = creditUsed.toLocaleString() + ' TL';
        }
        
        if (statRemaining) {
            statRemaining.textContent = availableCredits.toLocaleString() + ' TL';
        }
        
        // Status dot güncelle
        if (statusDot) {
            statusDot.className = 'status-dot ' + (availableCredits > 0 ? 'active' : 'depleted');
            statusDot.title = availableCredits > 0 ? 'Aktif' : 'Tükendi';
        }
    };
    
    // Sayı animasyonu fonksiyonu
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const difference = end - start;
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.round(start + (difference * easeOutQuart(progress)));
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        }
        
        requestAnimationFrame(updateNumber);
    }
    
    // Easing fonksiyonu
    function easeOutQuart(t) {
        return 1 - (--t) * t * t * t;
    }
});
</script>