<?php
// Ters kredi sistemi için kullanıcı kredi bilgilerini al
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT credit_quota, credit_used FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userCreditInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $creditQuota = $userCreditInfo['credit_quota'] ?? 0;
        $creditUsed = $userCreditInfo['credit_used'] ?? 0;
        $availableCredits = $creditQuota - $creditUsed;
        $usagePercentage = $creditQuota > 0 ? ($creditUsed / $creditQuota) * 100 : 0;
        
        // Session'a kullanılabilir kredi bilgisini kaydet (eski sistemle uyumluluk için)
        $_SESSION['credits'] = $availableCredits;
    } catch(PDOException $e) {
        $creditQuota = 0;
        $creditUsed = 0;
        $availableCredits = 0;
        $usagePercentage = 0;
        $_SESSION['credits'] = 0;
    }
} else {
    $creditQuota = 0;
    $creditUsed = 0;
    $availableCredits = 0;
    $usagePercentage = 0;
}
?>

<!-- User Panel Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <div class="navbar-brand-icon me-2">
                <i class="fas fa-microchip"></i>
            </div>
            <div>
                <span class="fw-bold"><?php echo SITE_NAME; ?></span>
                <small class="d-block text-white-50" style="font-size: 0.7rem;">Kullanıcı Paneli</small>
            </div>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link text-white-75 hover-bright" href="../index.php">
                        <i class="fas fa-home me-1"></i>Ana Sayfa
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <!-- Kredi Durumu - Ters Kredi Sistemi -->
                <li class="nav-item me-3">
                    <div class="credit-display-new bg-white bg-opacity-15 px-3 py-2 rounded-3" 
                         data-bs-toggle="tooltip" 
                         data-bs-placement="bottom" 
                         title="Kota: <?php echo number_format($creditQuota, 0); ?> TL | Kullanılan: <?php echo number_format($creditUsed, 2); ?> TL">
                        
                        <!-- Kredi Başlığı -->
                        <div class="credit-header d-flex align-items-center justify-content-between mb-1">
                            <div class="credit-icon-label d-flex align-items-center">
                                <i class="fas fa-wallet text-warning me-1"></i>
                                <span class="credit-label">Kullanılabilir</span>
                            </div>
                            <div class="credit-status">
                                <?php if ($availableCredits > 0): ?>
                                    <span class="status-dot bg-success"></span>
                                <?php else: ?>
                                    <span class="status-dot bg-danger"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Ana Kredi Tutaran -->
                        <div class="credit-amount-display d-flex align-items-baseline mb-2">
                            <span class="amount-value"><?php echo number_format($creditUsed, 0); ?></span>
                            <span class="amount-currency">TL</span>
                            <span class="quota-info">/ <?php echo number_format($creditQuota, 0); ?>TL</span>
                        </div>
                        
                        <!-- Mini Progress Bar -->
                        <div class="credit-progress-mini">
                            <div class="progress-track">
                                <div class="progress-used" style="width: <?php echo $usagePercentage; ?>%;"></div>
                                <div class="progress-remaining" style="width: <?php echo 100 - $usagePercentage; ?>%;"></div>
                            </div>
                            <div class="progress-labels d-flex justify-content-between">
                                <small class="usage-label">
                                    <i class="fas fa-minus-circle text-danger me-1"></i>
                                    <?php echo number_format($creditUsed, 0); ?> TL
                                </small>
                                <small class="remaining-label">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    <?php echo number_format($availableCredits, 0); ?> TL
                                </small>
                            </div>
                        </div>
                        
                        <!-- Klik Yönlendirme -->
                        <a href="credits.php" class="credit-link-overlay"></a>
                    </div>
                </li>

                <!-- Gelişmiş Bildirim Sistemi -->
                <?php
                try {
                    // NotificationManager'ı dahil et
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/../includes/NotificationManager.php';
                    }
                    
                    // Kullanıcının bekleyen revize talepleri
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE user_id = ? AND status = 'pending'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $pendingUserRevisions = $stmt->fetchColumn();

                    // Tamamlanan dosyalar (henüz bildirilmemiş)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND status = 'completed' AND notified = 0");
                    $stmt->execute([$_SESSION['user_id']]);
                    $completedFiles = $stmt->fetchColumn();
                    
                    // Kullanıcı bildirimlerini al
                    $userNotifications = [];
                    $unreadNotificationCount = 0;
                    
                    if (isset($_SESSION['user_id'])) {
                        $notificationManager = new NotificationManager($pdo);
                        $userNotifications = $notificationManager->getUserNotifications($_SESSION['user_id'], 5, false);
                        $unreadNotificationCount = $notificationManager->getUnreadCount($_SESSION['user_id']);
                    }
                } catch(Exception $e) {
                    error_log('User notification error: ' . $e->getMessage());
                    $pendingUserRevisions = 0;
                    $completedFiles = 0;
                    $userNotifications = [];
                    $unreadNotificationCount = 0;
                }
                
                $totalNotifications = $pendingUserRevisions + $completedFiles + count($userNotifications);
                ?>
                
                <?php if ($totalNotifications > 0): ?>
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative p-2" href="#" id="userNotificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-lg text-white"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $totalNotifications; ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userNotificationDropdown">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Bildirimler</span>
                            <span class="badge bg-primary"><?php echo $totalNotifications; ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- Sistem Bildirimleri -->
                        <?php foreach ($userNotifications as $notification): ?>
                        <li>
                            <a class="dropdown-item py-3 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                               href="<?php echo $notification['action_url'] ?: '#'; ?>" 
                               onclick="markNotificationRead('<?php echo htmlspecialchars($notification['id']); ?>')">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <div class="<?php 
                                            switch($notification['type']) {
                                                case 'file_status_update':
                                                    echo 'bg-success bg-opacity-10 p-2 rounded-circle';
                                                    break;
                                                case 'revision_response':
                                                    echo 'bg-info bg-opacity-10 p-2 rounded-circle';
                                                    break;
                                                default:
                                                    echo 'bg-primary bg-opacity-10 p-2 rounded-circle';
                                            }
                                        ?>">
                                            <i class="<?php 
                                                switch($notification['type']) {
                                                    case 'file_status_update':
                                                        echo 'fas fa-check-circle text-success';
                                                        break;
                                                    case 'revision_response':
                                                        echo 'fas fa-reply text-info';
                                                        break;
                                                    default:
                                                        echo 'fas fa-info-circle text-primary';
                                                }
                                            ?>"></i>
                                        </div>
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
                        
                        <!-- Tamamlanan Dosyalar -->
                        <?php if ($completedFiles > 0): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="files.php?status=completed">
                                <div class="me-3">
                                    <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo $completedFiles; ?> dosya tamamlandı</div>
                                    <small class="text-muted">İndirebilirsiniz</small>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Bekleyen Revize Talepleri -->
                        <?php if ($pendingUserRevisions > 0): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="revisions.php">
                                <div class="me-3">
                                    <div class="bg-warning bg-opacity-10 p-2 rounded-circle">
                                        <i class="fas fa-edit text-warning"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo $pendingUserRevisions; ?> revize talebi</div>
                                    <small class="text-muted">İncelemeyi bekliyor</small>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (empty($userNotifications) && $completedFiles == 0 && $pendingUserRevisions == 0): ?>
                        <li class="dropdown-item text-center text-muted py-3">
                            <i class="fas fa-bell-slash fa-2x mb-2"></i><br>
                            Henüz bildirim yok
                        </li>
                        <?php endif; ?>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <div class="d-flex justify-content-between px-3 py-2">
                                <a href="#" class="btn btn-sm btn-outline-secondary" onclick="markAllNotificationsRead()">Tümünü Okundu İşaretle</a>
                                <a href="files.php" class="small text-muted">Tüm dosyalarımı gör</a>
                            </div>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Kullanıcı Menüsü -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user-circle fa-lg"></i>
                        </div>
                        <div class="user-info">
                            <span class="fw-semibold"><?php echo $_SESSION['username']; ?></span>
                            <small class="d-block text-white-75">Kullanıcı</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarDropdown">
                        <li class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle fa-2x text-muted me-2"></i>
                                <div>
                                    <div class="fw-semibold"><?php echo $_SESSION['username']; ?></div>
                                    <small class="text-muted"><?php echo $_SESSION['email']; ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <li class="dropdown-header small text-uppercase text-muted">Hesap İşlemleri</li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="profile.php">
                                <i class="fas fa-user me-3 text-primary"></i>Profil Ayarları
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="credits.php">
                                <i class="fas fa-coins me-3 text-warning"></i>Kredi Yükle
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="transactions.php">
                                <i class="fas fa-history me-3 text-info"></i>İşlem Geçmişi
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header small text-uppercase text-muted">Dosya İşlemleri</li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="upload.php">
                                <i class="fas fa-upload me-3 text-success"></i>Dosya Yükle
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="files.php">
                                <i class="fas fa-folder me-3 text-secondary"></i>Dosyalarım
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="revisions.php">
                                <i class="fas fa-edit me-3 text-warning"></i>
                                Revize Taleplerim
                                <?php if ($pendingUserRevisions > 0): ?>
                                    <span class="badge bg-warning ms-auto"><?php echo $pendingUserRevisions; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        
                        <?php if (isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-header small text-uppercase text-muted">Yönetim</li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="../admin/">
                                    <i class="fas fa-cog me-3 text-danger"></i>Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                        
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

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%) !important;
}

.navbar-brand-icon {
    width: 35px;
    height: 35px;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.text-white-75 {
    color: rgba(255,255,255,0.75) !important;
}

.text-white-50 {
    color: rgba(255,255,255,0.5) !important;
}

.hover-bright:hover {
    color: rgba(255,255,255,1) !important;
}

.credit-display {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Yeni Ters Kredi Sistemi Gösterimi */
.credit-display-new {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    min-width: 200px;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.25) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.credit-display-new:hover {
    background: rgba(255,255,255,0.25) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.credit-header {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.9);
}

.credit-label {
    font-weight: 500;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.8);
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.credit-amount-display {
    color: white;
}

.amount-value {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}

.amount-currency {
    font-size: 0.9rem;
    font-weight: 500;
    margin-left: 0.25rem;
    color: rgba(255,255,255,0.8);
}

.quota-info {
    font-size: 0.8rem;
    font-weight: 400;
    margin-left: 0.25rem;
    color: rgba(255,255,255,0.6);
}

/* Mini Progress Bar */
.credit-progress-mini {
    margin-top: 0.5rem;
}

.progress-track {
    display: flex;
    height: 4px;
    border-radius: 2px;
    overflow: hidden;
    background: rgba(255,255,255,0.2);
    margin-bottom: 0.5rem;
}

.progress-used {
    background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
    transition: width 1s ease-out;
    border-radius: 2px 0 0 2px;
}

.progress-remaining {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    transition: width 1s ease-out;
    border-radius: 0 2px 2px 0;
}

.progress-labels {
    font-size: 0.65rem;
    color: rgba(255,255,255,0.8);
    line-height: 1;
}

.usage-label, .remaining-label {
    font-weight: 500;
}

.usage-label i {
    font-size: 0.6rem;
}

.remaining-label i {
    font-size: 0.6rem;
}

/* Klik Overlay */
.credit-link-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10;
    text-decoration: none;
}

.user-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dropdown-menu {
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    z-index: 1050;
    border: 1px solid rgba(0,0,0,0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    display: block !important; /* Bootstrap varsayılan display:none'ı ezeriz */
}

.dropdown-menu.show {
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) !important;
}

.dropdown-toggle::after {
    transition: transform 0.3s ease;
}

.dropdown-toggle[aria-expanded="true"]::after {
    transform: rotate(180deg);
}

/* Dropdown fade-in animasyonu */
@keyframes dropdownFadeIn {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu.show {
    animation: dropdownFadeIn 0.3s ease forwards;
}

/* Bootstrap dropdown default behavior override */
.dropdown-menu[data-bs-popper] {
    left: auto !important;
    right: 0 !important;
}

/* Notification dropdown özel stilleri */
#userNotificationDropdown + .dropdown-menu {
    min-width: 350px;
    max-height: 400px;
    overflow-y: auto;
}

/* User menu dropdown özel stilleri */
#navbarDropdown + .dropdown-menu {
    min-width: 250px;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
    transition: all 0.2s ease;
}

.dropdown-item:active {
    background-color: #e9ecef;
}

.dropdown-item.bg-light {
    background-color: #f8f9fa !important;
}

.dropdown-header {
    font-weight: 600;
}

.navbar-toggler:focus {
    box-shadow: none;
}

/* Dropdown responsive düzenlemeler */
@media (max-width: 991.98px) {
    .dropdown-menu {
        position: static !important;
        transform: none !important;
        box-shadow: none;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-top: 0.5rem;
    }
    
    .dropdown-menu.show {
        display: block !important;
    }
    
    #userNotificationDropdown + .dropdown-menu,
    #navbarDropdown + .dropdown-menu {
        min-width: 100%;
    }
}

/* Responsive İyileştirmeler */
@media (max-width: 991.98px) {
    .credit-display-new {
        min-width: 180px;
        padding: 0.75rem 1rem !important;
        margin-bottom: 1rem;
    }
    
    .amount-value {
        font-size: 1.2rem;
    }
    
    .progress-labels {
        font-size: 0.6rem;
    }
    
    .progress-labels .fas {
        display: none;
    }
}

@media (max-width: 575.98px) {
    .credit-display-new {
        min-width: 160px;
        padding: 0.5rem 0.75rem !important;
    }
    
    .amount-value {
        font-size: 1.1rem;
    }
    
    .credit-label {
        font-size: 0.7rem;
    }
    
    .quota-info {
        font-size: 0.7rem;
    }
    
    .progress-labels {
        font-size: 0.55rem;
    }
    
    .progress-track {
        height: 3px;
    }
}
</style>

<!-- User Bildirim JavaScript -->
<script src="../assets/js/notifications.js"></script>

<!-- Kredi Gösterim Tooltip & Animasyon JavaScript -->
<script>
// Bootstrap JavaScript yüklenene kadar bekle
function initializeHeaderComponents() {
    console.log('Header components initializing...');
    
    // Tooltip'leri aktifleştir
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        console.log('Bootstrap tooltips initializing...');
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Progress bar animasyonu
    const progressBars = document.querySelectorAll('.progress-used, .progress-remaining');
    progressBars.forEach((bar, index) => {
        const finalWidth = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.transition = 'width 1s ease-out';
            bar.style.width = finalWidth;
        }, 300 + (index * 150));
    });
    
    // Kredi kartına hover efekti
    const creditDisplay = document.querySelector('.credit-display-new');
    if (creditDisplay) {
        creditDisplay.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        creditDisplay.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    }
    
    // Bootstrap dropdown'larını initialize et
    if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        console.log('Bootstrap dropdowns initializing...');
        const dropdownToggleList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
        
        dropdownToggleList.forEach(function(dropdownToggleEl) {
            // Bootstrap dropdown instance oluştur
            new bootstrap.Dropdown(dropdownToggleEl, {
                autoClose: 'outside'
            });
        });
        
        console.log(`Initialized ${dropdownToggleList.length} dropdowns`);
    } else {
        console.warn('Bootstrap JavaScript henüz yüklenmedi, fallback kullanılıyor...');
        
        // Fallback için manuel dropdown işlevi - HTML yapısına uygun
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(dropdownToggle) {
            dropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Fallback dropdown click:', this.id);
                
                // Dropdown menüyü bul - aria-labelledby ile ilişkilendirilmiş
                const dropdownId = this.getAttribute('id');
                const dropdownMenu = document.querySelector(`[aria-labelledby="${dropdownId}"]`);
                
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    // Diğer dropdown'ları kapat
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        if (menu !== dropdownMenu) {
                            menu.classList.remove('show');
                        }
                    });
                    
                    // Bu dropdown'ı aç/kapat
                    dropdownMenu.classList.toggle('show');
                    this.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
                    
                    console.log('Dropdown toggled:', dropdownMenu.classList.contains('show'));
                } else {
                    console.error('Dropdown menu bulunamadı:', dropdownId);
                }
            });
        });
    }
    
    // Dropdown dışında tıklandığında kapat
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                menu.classList.remove('show');
                const dropdownId = menu.getAttribute('aria-labelledby');
                const toggle = document.getElementById(dropdownId);
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
}

// Birden fazla kere çalıştırmamak için kontrol
let headerInitialized = false;

// DOMContentLoaded event'inde initialize et
document.addEventListener('DOMContentLoaded', function() {
    if (!headerInitialized) {
        // Bootstrap yüklenmesini bekle (maksimum 3 saniye)
        let attempts = 0;
        const maxAttempts = 30;
        
        function waitForBootstrap() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                console.log('Bootstrap loaded, initializing components...');
                initializeHeaderComponents();
                headerInitialized = true;
            } else if (attempts < maxAttempts) {
                attempts++;
                setTimeout(waitForBootstrap, 100);
            } else {
                console.warn('Bootstrap timeout, using fallback...');
                initializeHeaderComponents();
                headerInitialized = true;
            }
        }
        
        waitForBootstrap();
    }
});

// Sayfa tamamen yüklendiğinde de kontrol et
window.addEventListener('load', function() {
    if (!headerInitialized) {
        console.log('Page loaded, initializing components as fallback...');
        initializeHeaderComponents();
        headerInitialized = true;
    }
});
</script>
