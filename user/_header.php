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
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                <!-- Kredi Durumu -->
                <li class="nav-item me-3 d-flex align-items-center">
                    <div class="credit-display bg-white bg-opacity-15 px-3 py-1 rounded-pill">
                        <i class="fas fa-coins text-warning me-1"></i>
                        <span class="fw-bold text-white"><?php echo number_format($_SESSION['credits'], 2); ?></span>
                        <small class="text-white-75">Kredi</small>
                    </div>
                </li>

                <!-- Bildirimler -->
                <?php
                try {
                    // Bekleyen revize talepleri
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE user_id = ? AND status = 'pending'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $pendingUserRevisions = $stmt->fetchColumn();

                    // Tamamlanan dosyalar
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND status = 'completed' AND notified = 0");
                    $stmt->execute([$_SESSION['user_id']]);
                    $completedFiles = $stmt->fetchColumn();
                } catch(PDOException $e) {
                    $pendingUserRevisions = 0;
                    $completedFiles = 0;
                }
                
                $totalNotifications = $pendingUserRevisions + $completedFiles;
                ?>
                
                <?php if ($totalNotifications > 0): ?>
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative p-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fa-lg text-white"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $totalNotifications; ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 300px;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Bildirimler</span>
                            <span class="badge bg-primary"><?php echo $totalNotifications; ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
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
                        
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center small text-muted" href="files.php">
                                Tüm dosyalarımı gör
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Kullanıcı Menüsü -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user-circle fa-lg"></i>
                        </div>
                        <div class="user-info">
                            <span class="fw-semibold"><?php echo $_SESSION['username']; ?></span>
                            <small class="d-block text-white-75">Kullanıcı</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 250px;">
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
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
    transition: all 0.2s ease;
}

.dropdown-header {
    font-weight: 600;
}

.navbar-toggler:focus {
    box-shadow: none;
}
</style>