<!-- User Panel Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-dashboard"></i>
                    Panel
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'upload.php' ? 'active' : ''; ?>" href="upload.php">
                    <i class="fas fa-upload"></i>
                    Dosya Yükle
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'files.php' ? 'active' : ''; ?>" href="files.php">
                    <i class="fas fa-folder"></i>
                    Dosyalarım
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'revisions.php' ? 'active' : ''; ?>" href="revisions.php">
                    <i class="fas fa-edit"></i>
                    Revize Taleplerim
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE user_id = ? AND status = 'pending'");
                        $stmt->execute([$_SESSION['user_id']]);
                        $pendingUserRevisions = $stmt->fetchColumn();
                        if ($pendingUserRevisions > 0) {
                            echo '<span class="badge bg-warning ms-2">' . $pendingUserRevisions . '</span>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'credits.php' ? 'active' : ''; ?>" href="credits.php">
                    <i class="fas fa-coins"></i>
                    Kredi İşlemleri
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Hesap</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user"></i>
                    Profil Ayarları
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                    <i class="fas fa-history"></i>
                    İşlem Geçmişi
                </a>
            </li>
        </ul>

        <!-- Kredi Durumu Widget -->
        <div class="px-3 mt-4">
            <div class="card border-0 bg-light">
                <div class="card-body p-3">
                    <h6 class="card-title text-muted mb-2">
                        <i class="fas fa-coins me-1"></i>Kredi Bakiyesi
                    </h6>
                    <h4 class="mb-2 text-success"><?php echo number_format($_SESSION['credits'], 2); ?></h4>
                    <a href="credits.php" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-plus me-1"></i>Yükle
                    </a>
                </div>
            </div>
        </div>

        <!-- Destek Widget -->
        <div class="px-3 mt-3">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body p-3">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-headset me-1"></i>Yardıma mı ihtiyacınız var?
                    </h6>
                    <p class="card-text small mb-2">Teknik destek için bizimle iletişime geçin.</p>
                    <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-sm btn-light">
                        <i class="fas fa-envelope me-1"></i>İletişim
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
