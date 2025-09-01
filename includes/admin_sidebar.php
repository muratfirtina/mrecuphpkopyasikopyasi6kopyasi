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
                            <i class="bi bi-tachometer-alt"></i>Genel Bakış
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Raporlar') ? 'active' : ''; ?>" href="reports.php">
                            <i class="bi bi-chart-bar"></i>Raporlar
                        </a>
                        
                        <!-- Dosya Yönetimi -->
                        <div class="nav-section">Dosya Yönetimi</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Dosya Yüklemeleri') ? 'active' : ''; ?>" href="uploads.php">
                            <i class="bi bi-upload"></i>Yüklemeler
                            <?php
                            // İşleme alınan dosya sayısını al
                            try {
                                $processingFilesStmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE status = 'processing'");
                                $processingFilesStmt->execute();
                                $processingFilesCount = $processingFilesStmt->fetchColumn();
                                
                                if ($processingFilesCount > 0) {
                                    echo '<span class="badge bg-info ms-2">' . $processingFilesCount . '</span>';
                                }
                            } catch(Exception $e) {
                                // Hata durumunda badge gösterme
                            }
                            ?>
                        </a>
                        
                        <!-- <a class="nav-link <?php echo ($pageTitle == 'Yanıt Dosyaları Yönetimi') ? 'active' : ''; ?>" href="responses.php">
                            <i class="bi bi-reply"></i>Yanıt Dosyaları
                            <?php
                            // Aktif yanıt dosya sayısını al
                            try {
                                $responseFilesStmt = $pdo->prepare("SELECT COUNT(*) FROM file_responses WHERE (is_cancelled = 0 OR is_cancelled IS NULL)");
                                $responseFilesStmt->execute();
                                $responseFilesCount = $responseFilesStmt->fetchColumn();
                                
                                if ($responseFilesCount > 0) {
                                    echo '<span class="badge bg-success ms-2">' . $responseFilesCount . '</span>';
                                }
                            } catch(Exception $e) {
                                // Hata durumunda badge gösterme
                            }
                            ?>
                        </a> -->
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Ek Dosyalar Yönetimi') ? 'active' : ''; ?>" href="additional-files.php">
                            <i class="bi bi-paperclip"></i>Ek Dosyalar
                            <?php
                            // Aktif ek dosya sayısını al
                            try {
                                $additionalFilesStmt = $pdo->prepare("SELECT COUNT(*) FROM additional_files WHERE (is_cancelled = 0 OR is_cancelled IS NULL)");
                                $additionalFilesStmt->execute();
                                $additionalFilesCount = $additionalFilesStmt->fetchColumn();
                                
                                /* if ($additionalFilesCount > 0) {
                                    echo '<span class="badge bg-info ms-2">' . $additionalFilesCount . '</span>';
                                } */
                            } catch(Exception $e) {
                                // Hata durumunda badge gösterme
                            }
                            ?>
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Revizyon İşlemleri') ? 'active' : ''; ?>" href="revisions.php">
                            <i class="bi bi-history"></i>Revizyonlar
                            <?php
                            // Bekleyen ve işleme alınan revizyon sayılarını al
                            try {
                                $pendingRevisionsStmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE status = 'pending'");
                                $pendingRevisionsStmt->execute();
                                $pendingRevisionsCount = $pendingRevisionsStmt->fetchColumn();
                                
                                $processingRevisionsStmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE status = 'in_progress'");
                                $processingRevisionsStmt->execute();
                                $processingRevisionsCount = $processingRevisionsStmt->fetchColumn();
                                
                                // Bekleyen revizyonlar için kırmızı badge (öncelikli)
                                if ($pendingRevisionsCount > 0) {
                                    echo '<span class="badge bg-danger ms-2">' . $pendingRevisionsCount . '</span>';
                                } elseif ($processingRevisionsCount > 0) {
                                    // Eğer bekleyen yoksa işlemdeki revizyonları mavi badge ile göster
                                    echo '<span class="badge bg-info ms-2">' . $processingRevisionsCount . '</span>';
                                }
                            } catch(Exception $e) {
                                // Hata durumunda badge gösterme
                            }
                            ?>
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Dosya İptal Talepleri') ? 'active' : ''; ?>" href="file-cancellations.php">
                            <i class="bi bi-times-circle"></i>İptal Talepleri
                            <?php
                            // Bekleyen iptal talep sayısını al
                            try {
                                $pendingCancellationsStmt = $pdo->prepare("SELECT COUNT(*) FROM file_cancellations WHERE status = 'pending'");
                                $pendingCancellationsStmt->execute();
                                $pendingCancellationsCount = $pendingCancellationsStmt->fetchColumn();
                                
                                if ($pendingCancellationsCount > 0) {
                                    echo '<span class="badge bg-warning ms-2">' . $pendingCancellationsCount . '</span>';
                                }
                            } catch(Exception $e) {
                                // Hata durumunda badge gösterme
                            }
                            ?>
                        </a>

                        <a class="nav-link <?php echo ($pageTitle == 'Bildirimler') ? 'active' : ''; ?>" href="notifications.php">
                            <i class="bi bi-bell"></i>Bildirimler
                            <?php
                            // Toplam bildirim sayısını hesapla (bekleyen dosyalar + chat bildirimleri)
                            try {
                                $totalNotificationCount = 0;
                                
                                // 1. Bekleyen dosyalar
                                $pendingProcessStmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE status = 'pending'");
                                $pendingProcessStmt->execute();
                                $pendingProcessCount = $pendingProcessStmt->fetchColumn();
                                $totalNotificationCount += $pendingProcessCount;
                                
                                // 2. Chat bildirimleri (okunmamış)
                                if (isset($_SESSION['user_id'])) {
                                    $chatNotificationStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'chat_message' AND is_read = FALSE");
                                    $chatNotificationStmt->execute([$_SESSION['user_id']]);
                                    $chatNotificationCount = $chatNotificationStmt->fetchColumn();
                                    $totalNotificationCount += $chatNotificationCount;
                                }
                                
                                // 3. Diğer okunmamış bildirimler
                                if (isset($_SESSION['user_id'])) {
                                    $otherNotificationStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type != 'chat_message' AND is_read = FALSE");
                                    $otherNotificationStmt->execute([$_SESSION['user_id']]);
                                    $otherNotificationCount = $otherNotificationStmt->fetchColumn();
                                    $totalNotificationCount += $otherNotificationCount;
                                }
                                
                                if ($totalNotificationCount > 0) {
                                    echo '<span class="badge bg-danger ms-2 sidebar-notification-badge">' . $totalNotificationCount . '</span>';
                                }
                            } catch(Exception $e) {
                                // Hata durumunda badge gösterme
                                error_log('Sidebar notification count error: ' . $e->getMessage());
                            }
                            ?>
                        </a>
                        
                        <!-- Kullanıcı Yönetimi -->
                        <div class="nav-section">Kullanıcı Yönetimi</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Kullanıcılar') ? 'active' : ''; ?>" href="users.php">
                            <i class="bi bi-users"></i>Kullanıcılar
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Kredi Yönetimi') ? 'active' : ''; ?>" href="credits.php">
                            <i class="bi bi-coins"></i>Kredi Yönetimi
                        </a>

                        <a class="nav-link <?php echo ($pageTitle == 'İletişim Mesajları') ? 'active' : ''; ?>" href="contact-messages.php">
                            <i class="bi bi-envelope"></i>İletişim Mesajları
                            <?php
                            // Yeni mesaj sayısını al
                            try {
                                $newContactMessagesStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
                                $newContactMessagesStmt->execute();
                                $newContactMessagesCount = $newContactMessagesStmt->fetchColumn();
                                
                                if ($newContactMessagesCount > 0) {
                                    echo '<span class="badge bg-danger ms-2">' . $newContactMessagesCount . '</span>';
                                }
                            } catch(Exception $e) {
                                // Hata durumunda badge gösterme
                            }
                            ?>
                        </a>
                        
                        
                        <!-- Ürün Yönetimi -->
                        <div class="nav-section">Ürün Yönetimi</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Kategoriler') ? 'active' : ''; ?>" href="categories.php">
                            <i class="bi bi-tags"></i>Kategoriler
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Ürün Markalar') ? 'active' : ''; ?>" href="product-brands.php">
                            <i class="bi bi-award"></i>Markalar
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Ürünler') ? 'active' : ''; ?>" href="products.php">
                            <i class="bi bi-box"></i>Ürünler
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Markalar') ? 'active' : ''; ?>" href="brands.php">
                            <i class="bi bi-certificate"></i>Araçlar
                        </a>
                        
                        <!-- Sistem Yönetimi -->
                        <div class="nav-section">Sistem</div>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'ECU Yönetimi') ? 'active' : ''; ?>" href="ecus.php">
                            <i class="bi bi-microchip"></i>ECU Yönetimi
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Device Yönetimi') ? 'active' : ''; ?>" href="devices.php">
                            <i class="bi bi-tools"></i>Device Yönetimi
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Sistem Ayarları') ? 'active' : ''; ?>" href="settings.php">
                            <i class="bi bi-cog"></i>Ayarlar
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Güvenlik Dashboard') ? 'active' : ''; ?>" href="security-dashboard.php">
                            <i class="bi bi-shield-alt"></i>Güvenlik
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Sistem Logları') ? 'active' : ''; ?>" href="logs.php">
                            <i class="bi bi-clipboard-list"></i>Loglar
                        </a>
                        
                        <a class="nav-link <?php echo ($pageTitle == 'Debug Tools') ? 'active' : ''; ?>" href="debug.php">
                            <i class="bi bi-bug"></i>Debug
                        </a>
                        
                        <!-- Diğer İşlemler -->
                        <div class="nav-section">Diğer</div>
                        
                        <a class="nav-link text-info" href="../design/contact.php">
                            <i class="bi bi-phone-alt"></i>İletişim Yönetimi
                        </a>
                        
                        <a class="nav-link text-warning" href="../user/">
                            <i class="bi bi-user"></i>Kullanıcı Paneli
                        </a>
                        
                        <a class="nav-link text-info" href="../index.php" target="_blank">
                            <i class="bi bi-external-link-alt"></i>Siteyi Görüntüle
                        </a>
                        
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="bi bi-sign-out-alt"></i>Çıkış Yap
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
                                            <i class="bi bi-tachometer-alt me-1"></i>Dashboard
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
                                        <i class="bi bi-info-circle me-3 fa-lg"></i>
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
