<!-- Admin Panel Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
                <!-- Bekleyen Dosya Bildirimi -->
                <?php
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads WHERE status = 'pending'");
                    $pendingCount = $stmt->fetchColumn();
                    
                    // Bekleyen revize talepleri
                    $stmt = $pdo->query("SELECT COUNT(*) FROM revisions WHERE status = 'pending'");
                    $pendingRevisions = $stmt->fetchColumn();
                } catch(PDOException $e) {
                    $pendingCount = 0;
                    $pendingRevisions = 0;
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
