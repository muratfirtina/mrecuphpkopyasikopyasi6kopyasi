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
$processingUploads = count(array_filter($userUploads, function($upload) { return $upload['status'] === 'processing'; }));

$pageTitle = 'Dashboard';
$pageDescription = 'Hoşgeldiniz! Hesabınızın genel durumunu buradan takip edebilirsiniz.';

// Header include
include '../includes/user_header.php';
include '../includes/user_sidebar.php';
?>

                    <!-- Welcome Card -->
                    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        <div class="card-body text-white p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="mb-2">Hoşgeldiniz, <?php echo $_SESSION['username']; ?>!</h4>
                                    <p class="mb-0 opacity-75">
                                        Hesabınızda <strong><?php echo $totalUploads; ?> dosya</strong> bulunuyor. 
                                        Mevcut krediniz: <strong><?php echo number_format($userCredits, 2); ?> TL</strong>
                                    </p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <i class="fas fa-user-circle" style="font-size: 4rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İstatistik Kartları -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number"><?php echo $totalUploads; ?></div>
                                        <div class="stat-label">Toplam Dosya</div>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-file text-primary fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number text-warning"><?php echo $pendingUploads; ?></div>
                                        <div class="stat-label">Bekleyen</div>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-clock text-warning fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number text-info"><?php echo $processingUploads; ?></div>
                                        <div class="stat-label">İşleniyor</div>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-cogs text-info fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number text-success"><?php echo $completedUploads; ?></div>
                                        <div class="stat-label">Tamamlanan</div>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-check-circle text-success fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Son Dosyalar -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Son Yüklenen Dosyalar</h5>
                                    <a href="files.php" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>Tümünü Gör
                                    </a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($userUploads)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">Henüz dosya yüklenmemiş</h6>
                                            <p class="text-muted mb-3">İlk dosyanızı yüklemek için butona tıklayın</p>
                                            <a href="upload.php" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i>Dosya Yükle
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Dosya Adı</th>
                                                        <th>Durum</th>
                                                        <th>Yüklenme Tarihi</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_slice($userUploads, 0, 5) as $upload): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="fas fa-file-alt text-muted me-2"></i>
                                                                    <span class="fw-medium"><?php echo htmlspecialchars($upload['original_filename']); ?></span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $statusClass = [
                                                                    'pending' => 'warning',
                                                                    'processing' => 'info',
                                                                    'completed' => 'success',
                                                                    'rejected' => 'danger'
                                                                ];
                                                                $statusText = [
                                                                    'pending' => 'Bekliyor',
                                                                    'processing' => 'İşleniyor',
                                                                    'completed' => 'Tamamlandı',
                                                                    'rejected' => 'Reddedildi'
                                                                ];
                                                                ?>
                                                                <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?>">
                                                                    <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('d.m.Y H:i', strtotime($upload['upload_date'])); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <?php if ($upload['status'] === 'completed' && !empty($upload['processed_file_path'])): ?>
                                                                        <a href="download.php?id=<?php echo $upload['id']; ?>" 
                                                                           class="btn btn-success btn-sm" title="İndir">
                                                                            <i class="fas fa-download"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <a href="files.php?view=<?php echo $upload['id']; ?>" 
                                                                       class="btn btn-outline-primary btn-sm" title="Detay">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
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
                        </div>

                        <!-- Hızlı İşlemler -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0">
                                    <h5 class="mb-0">Hızlı İşlemler</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-3">
                                        <a href="upload.php" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Yeni Dosya Yükle
                                        </a>
                                        
                                        <a href="files.php" class="btn btn-outline-primary">
                                            <i class="fas fa-folder me-2"></i>Dosyalarımı Görüntüle
                                        </a>
                                        
                                        <a href="credits.php" class="btn btn-outline-success">
                                            <i class="fas fa-credit-card me-2"></i>Kredi Yükle
                                        </a>
                                        
                                        <a href="profile.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-user-cog me-2"></i>Profil Ayarları
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Kredi Durumu -->
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-header bg-white border-0">
                                    <h6 class="mb-0">Hesap Durumu</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 border-end">
                                            <div class="p-2">
                                                <h4 class="text-success mb-1"><?php echo number_format($userCredits, 2); ?></h4>
                                                <small class="text-muted">Mevcut Kredi</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-2">
                                                <h4 class="text-primary mb-1"><?php echo $totalUploads; ?></h4>
                                                <small class="text-muted">Toplam İşlem</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($userCredits < 10): ?>
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <small>
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Krediniz azalıyor. <a href="credits.php" class="alert-link">Kredi yükleyin</a>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Duyurular -->
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-header bg-white border-0">
                                    <h6 class="mb-0">Duyurular</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="bg-info bg-opacity-10 p-2 rounded me-3">
                                            <i class="fas fa-info-circle text-info"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Yeni Özellik!</h6>
                                            <small class="text-muted">
                                                Artık dosyalarınızı daha hızlı işliyoruz.
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-start">
                                        <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                                            <i class="fas fa-check-circle text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Sistem Güncellemesi</h6>
                                            <small class="text-muted">
                                                Platform güvenlik güncellemesi tamamlandı.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

<?php
// Footer include
include '../includes/user_footer.php';
?>
