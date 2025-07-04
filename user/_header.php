<!-- User Panel Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-microchip me-2"></i>
            <?php echo SITE_NAME; ?>
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
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo $_SESSION['username']; ?>
                        <span class="badge bg-success ms-1"><?php echo number_format($_SESSION['credits'], 2); ?> Kredi</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Hesap</h6></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="credits.php"><i class="fas fa-coins me-2"></i>Kredi Yükle</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">İşlemler</h6></li>
                        <li><a class="dropdown-item" href="upload.php"><i class="fas fa-upload me-2"></i>Dosya Yükle</a></li>
                        <li><a class="dropdown-item" href="files.php"><i class="fas fa-folder me-2"></i>Dosyalarım</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="../admin/"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
