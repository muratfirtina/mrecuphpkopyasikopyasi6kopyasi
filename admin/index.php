<?php
/**
 * Mr ECU - Admin Panel Ana Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Dashboard istatistikleri
$userCount = $user->getUserCount();
$fileStats = $fileManager->getFileStats();

// Son yüklenen dosyalar
$recentUploads = $fileManager->getAllUploads(1, 10);

// Toplam kredi
try {
    $stmt = $pdo->query("SELECT SUM(credits) as total_credits FROM users WHERE role = 'user'");
    $totalCredits = $stmt->fetch()['total_credits'] ?? 0;
} catch(PDOException $e) {
    $totalCredits = 0;
}

// Kategori ve ürün istatistikleri
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM categories");
    $totalCategories = $stmt->fetch()['total_categories'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as active_categories FROM categories WHERE is_active = 1");
    $activeCategories = $stmt->fetch()['active_categories'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $totalProducts = $stmt->fetch()['total_products'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as active_products FROM products WHERE is_active = 1");
    $activeProducts = $stmt->fetch()['active_products'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as featured_products FROM products WHERE featured = 1 AND is_active = 1");
    $featuredProducts = $stmt->fetch()['featured_products'] ?? 0;
} catch(PDOException $e) {
    $totalCategories = 0;
    $activeCategories = 0;
    $totalProducts = 0;
    $activeProducts = 0;
    $featuredProducts = 0;
}

$pageTitle = 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>Yenile
                            </button>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card primary">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format((int)$userCount); ?></h4>
                                    <p class="text-muted mb-0">Toplam Kullanıcı</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="users.php" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format((int)($fileStats['total_files'] ?? 0)); ?></h4>
                                    <p class="text-muted mb-0">Toplam Dosya</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="uploads.php" class="btn btn-sm btn-outline-success">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format((int)($fileStats['pending_files'] ?? 0)); ?></h4>
                                    <p class="text-muted mb-0">Bekleyen Dosya</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="uploads.php?status=pending" class="btn btn-sm btn-outline-warning">İşle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card danger">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format((float)$totalCredits, 2); ?></h4>
                                    <p class="text-muted mb-0">Toplam Kredi</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-coins text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="credits.php" class="btn btn-sm btn-outline-warning">Yönet</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Katalog Yönetimi Kartları -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card info">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format((int)$totalCategories); ?></h4>
                                    <p class="text-muted mb-0">Toplam Kategori</p>
                                    <small class="text-success"><?php echo $activeCategories; ?> aktif</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-tags text-info" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="categories.php" class="btn btn-sm btn-outline-info">Yönet</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card secondary">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format((int)$totalProducts); ?></h4>
                                    <p class="text-muted mb-0">Toplam Ürün</p>
                                    <small class="text-success"><?php echo $activeProducts; ?> aktif</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-box text-secondary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="products.php" class="btn btn-sm btn-outline-secondary">Yönet</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format((int)$featuredProducts); ?></h4>
                                    <p class="text-muted mb-0">Öne Çıkan Ürün</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-star text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="products.php?featured=1" class="btn btn-sm btn-outline-warning">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1">%<?php echo $totalProducts > 0 ? number_format(($activeProducts / $totalProducts) * 100, 1) : 0; ?></h4>
                                    <p class="text-muted mb-0">Aktif Ürün Oranı</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-pie text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="products.php?status=active" class="btn btn-sm btn-outline-success">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dosya Durum Grafiği -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Dosya Durumları
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-warning"><?php echo (int)($fileStats['pending_files'] ?? 0); ?></h3>
                                            <p class="text-muted mb-0">Bekleyen</p>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-info"><?php echo (int)($fileStats['processing_files'] ?? 0); ?></h3>
                                            <p class="text-muted mb-0">İşleniyor</p>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-success"><?php echo (int)($fileStats['completed_files'] ?? 0); ?></h3>
                                            <p class="text-muted mb-0">Tamamlanan</p>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-danger"><?php echo (int)($fileStats['rejected_files'] ?? 0); ?></h3>
                                            <p class="text-muted mb-0">Reddedilen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs me-2"></i>Hızlı İşlemler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="uploads.php?status=pending" class="btn btn-warning">
                                        <i class="fas fa-clock me-2"></i>Bekleyen Dosyalar
                                    </a>
                                    <a href="users.php" class="btn btn-outline-primary">
                                        <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
                                    </a>
                                    <a href="categories.php" class="btn btn-outline-info">
                                        <i class="fas fa-tags me-2"></i>Kategori Yönetimi
                                    </a>
                                    <a href="products.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-box me-2"></i>Ürün Yönetimi
                                    </a>
                                    <a href="brands.php" class="btn btn-outline-success">
                                        <i class="fas fa-car me-2"></i>Marka/Model Yönetimi
                                    </a>
                                    <a href="settings.php" class="btn btn-outline-dark">
                                        <i class="fas fa-cog me-2"></i>Sistem Ayarları
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Yüklenen Dosyalar -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Son Yüklenen Dosyalar
                        </h5>
                        <a href="uploads.php" class="btn btn-sm btn-outline-primary">Tümünü Görüntüle</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentUploads)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">Henüz dosya yüklenmemiş.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Dosya</th>
                                            <th>Araç</th>
                                            <th>Durum</th>
                                            <th>Tarih</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUploads as $upload): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($upload['username']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($upload['email']); ?></small>
                                                </td>
                                                <td>
                                                    <i class="fas fa-file me-2"></i>
                                                    <?php echo htmlspecialchars($upload['original_name']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo number_format((float)($upload['file_size'] ?? 0) / 1024, 2); ?> KB</small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($upload['brand_name'] . ' ' . $upload['model_name']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo $upload['year']; ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = 'secondary';
                                                    $statusText = $upload['status'];
                                                    
                                                    switch ($upload['status']) {
                                                        case 'pending':
                                                            $statusClass = 'warning';
                                                            $statusText = 'Bekliyor';
                                                            break;
                                                        case 'processing':
                                                            $statusClass = 'info';
                                                            $statusText = 'İşleniyor';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'success';
                                                            $statusText = 'Tamamlandı';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'danger';
                                                            $statusText = 'Reddedildi';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                                <td><?php echo formatDate($upload['upload_date']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="uploads.php?id=<?php echo $upload['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($upload['status'] === 'pending'): ?>
                                                            <a href="process.php?id=<?php echo $upload['id']; ?>" class="btn btn-outline-success btn-sm">
                                                                <i class="fas fa-cogs"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto refresh dashboard every 2 minutes
        setTimeout(function() {
            location.reload();
        }, 120000);

        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('tr-TR');
            document.title = timeString + ' - <?php echo $pageTitle . ' - ' . SITE_NAME; ?>';
        }
        
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
