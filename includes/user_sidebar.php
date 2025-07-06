            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 user-sidebar">
                <div class="d-flex flex-column p-3">
                    <!-- Kredi Kartı -->
                    <div class="credit-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1">Mevcut Krediniz</h6>
                                <h4 class="mb-0 fw-bold">
                                    <?php echo isset($_SESSION['credits']) ? number_format($_SESSION['credits'], 2) : '0.00'; ?> TL
                                </h4>
                            </div>
                            <i class="fas fa-coins fa-2x opacity-75"></i>
                        </div>
                        <div class="mt-2">
                            <a href="credits.php" class="btn btn-light btn-sm">
                                <i class="fas fa-plus me-1"></i>Kredi Yükle
                            </a>
                        </div>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <nav class="nav nav-pills flex-column sidebar-nav">
                        <a class="nav-link <?php echo ($pageTitle == 'Kullanıcı Paneli' || $pageTitle == 'Dashboard') ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Dosya Yükle') ? 'active' : ''; ?>" href="upload.php">
                            <i class="fas fa-upload me-2"></i>Dosya Yükle
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Dosyalarım') ? 'active' : ''; ?>" href="files.php">
                            <i class="fas fa-file me-2"></i>Dosyalarım
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Revizyon İşlemleri') ? 'active' : ''; ?>" href="revisions.php">
                            <i class="fas fa-history me-2"></i>Revizyonlar
                        </a>
                        
                        <hr class="my-3">
                        
                        <a class="nav-link <?php echo ($pageTitle == 'İşlemlerim') ? 'active' : ''; ?>" href="transactions.php">
                            <i class="fas fa-receipt me-2"></i>İşlemlerim
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Kredi Yükle') ? 'active' : ''; ?>" href="credits.php">
                            <i class="fas fa-credit-card me-2"></i>Kredi Yükle
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Profil Ayarları') ? 'active' : ''; ?>" href="profile.php">
                            <i class="fas fa-user-cog me-2"></i>Profil
                        </a>
                        
                        <hr class="my-3">
                        
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col-md-9 col-lg-10 user-content">
                <div class="p-4">
                    
                    <!-- Sayfa Başlığı -->
                    <div class="user-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2"><?php echo $pageTitle; ?></h1>
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
                                    <?php if ($pageTitle != 'Kullanıcı Paneli' && $pageTitle != 'Dashboard'): ?>
                                        <li class="breadcrumb-item active" aria-current="page"><?php echo $pageTitle; ?></li>
                                    <?php endif; ?>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    
                    <!-- Sayfa İçeriği Başlangıcı -->
