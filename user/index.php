<?php
/**
 * Mr ECU - Kullanıcı Panel Ana Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);

// Kullanıcı istatistikleri
$userId = $_SESSION['user_id'];
$userCredits = $user->getUserCredits($userId);
$userUploads = $fileManager->getUserUploads($userId, 1, 10);

// İstatistikler
$totalUploads = count($fileManager->getUserUploads($userId, 1, 1000));
$pendingUploads = count(array_filter($userUploads, function($upload) { return $upload['status'] === 'pending'; }));
$completedUploads = count(array_filter($userUploads, function($upload) { return $upload['status'] === 'completed'; }));

$pageTitle = 'Panel';
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
                        <i class="fas fa-dashboard me-2"></i>Panel
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="upload.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-upload me-1"></i>Dosya Yükle
                            </a>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card primary">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format($userCredits, 2); ?></h4>
                                    <p class="text-muted mb-0">Kredi Bakiyesi</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-coins text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo $totalUploads; ?></h4>
                                    <p class="text-muted mb-0">Toplam Dosya</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo $pendingUploads; ?></h4>
                                    <p class="text-muted mb-0">Bekleyen Dosya</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card danger">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo $completedUploads; ?></h4>
                                    <p class="text-muted mb-0">Tamamlanan</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kredi Durumu -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-coins me-2"></i>Kredi Durumu
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="credit-display">
                                    <div class="credit-amount"><?php echo number_format($userCredits, 2); ?></div>
                                    <div>Mevcut Kredi</div>
                                </div>
                                <div class="mt-3">
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Dosya indirme maliyeti: <?php echo FILE_DOWNLOAD_COST; ?> kredi
                                    </p>
                                    <a href="credits.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i>Kredi Yükle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Hızlı İşlemler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="upload.php" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Yeni Dosya Yükle
                                    </a>
                                    <a href="files.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-folder me-2"></i>Dosyalarımı Görüntüle
                                    </a>
                                    <a href="profile.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-user me-2"></i>Profil Ayarları
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
                        <a href="files.php" class="btn btn-sm btn-outline-primary">Tümünü Görüntüle</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userUploads)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">Henüz dosya yüklenmemiş.</p>
                                <a href="upload.php" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i>İlk Dosyanı Yükle
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Dosya</th>
                                            <th>Araç</th>
                                            <th>Durum</th>
                                            <th>Tarih</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($userUploads as $upload): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-file me-2"></i>
                                                    <?php echo htmlspecialchars($upload['original_name']); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($upload['brand_name'] . ' ' . $upload['model_name'] . ' (' . $upload['year'] . ')'); ?>
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
                                                        <a href="files.php?id=<?php echo $upload['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($upload['has_response'] > 0): ?>
                                                            <a href="download.php?id=<?php echo $upload['response_id']; ?>" class="btn btn-outline-success btn-sm">
                                                                <i class="fas fa-download"></i>
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
        // Sayfa yüklendiğinde istatistikleri güncelle
        document.addEventListener('DOMContentLoaded', function() {
            // Auto refresh için interval ayarla (opsiyonel)
            // setInterval(function() {
            //     location.reload();
            // }, 60000); // 60 saniye
        });
    </script>
</body>
</html>
