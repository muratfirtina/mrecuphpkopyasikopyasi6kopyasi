<?php
/**
 * Mr ECU - Admin Panel Ana Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Dashboard istatistikleri
$userCount = $user->getUserCount();
$fileStats = $fileManager->getFileStats();

// Son yüklenen dosyalar
$recentUploads = $fileManager->getAllUploads(1, 10);

// Toplam kredi
try {
    $stmt = $pdo->query("SELECT SUM(credit_quota) as total_credits FROM users WHERE role = 'user'");
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

// Günlük istatistikler
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_uploads FROM uploads WHERE DATE(upload_date) = ?");
    $stmt->execute([$today]);
    $todayUploads = $stmt->fetch()['today_uploads'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_users FROM users WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $todayUsers = $stmt->fetch()['today_users'] ?? 0;
} catch(PDOException $e) {
    $todayUploads = 0;
    $todayUsers = 0;
}

// Sistem durumu
$systemStatus = [
    'database' => true,
    'uploads_dir' => is_writable('../uploads/'),
    'logs_dir' => is_writable('../logs/'),
    'php_version' => version_compare(PHP_VERSION, '7.4.0', '>=')
];

$pageTitle = 'Dashboard';
$pageDescription = 'Sistem genel durumu ve istatistikler';
$pageIcon = 'bi bi-speedometer';

// Sidebar için istatistikler
$totalUsers = $userCount;
$totalUploads = $fileStats['total'] ?? 0;

// Hızlı eylemler
$quickActions = [
    [
        'text' => 'Yeni Kullanıcı',
        'url' => 'users.php?action=create',
        'icon' => 'bi bi-person-plus',
        'class' => 'success'
    ],
    [
        'text' => 'Dosyaları Görüntüle',
        'url' => 'uploads.php',
        'icon' => 'bi bi-folder-open',
        'class' => 'primary'
    ],
    [
        'text' => 'Sistem Ayarları',
        'url' => 'settings.php',
        'icon' => 'bi bi-gear',
        'class' => 'secondary'
    ]
];

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Ana İstatistikler -->
<!-- <div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format($userCount); ?></div>
                    <div class="stat-label">Toplam Kullanıcı</div>
                    <?php if ($todayUsers > 0): ?>
                        <small class="text-success">
                            <i class="bi bi-arrow-up me-1"></i>+<?php echo $todayUsers; ?> bugün
                        </small>
                    <?php endif; ?>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="bi bi-person text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo number_format($totalUploads); ?></div>
                    <div class="stat-label">Toplam Dosya</div>
                    <?php if ($todayUploads > 0): ?>
                        <small class="text-success">
                            <i class="bi bi-arrow-up me-1"></i>+<?php echo $todayUploads; ?> bugün
                        </small>
                    <?php endif; ?>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="bi bi-folder2-open text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format($totalCredits, 2); ?> TL</div>
                    <div class="stat-label">Toplam Kredi</div>
                    <small class="text-muted">Kullanıcı bakiyeleri</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="bi bi-coin text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo number_format($totalProducts); ?></div>
                    <div class="stat-label">Toplam Ürün</div>
                    <small class="text-muted"><?php echo $activeProducts; ?> aktif</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="bi bi-box text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div> -->

<!-- Dosya İstatistikleri -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card admin-card border-left-warning">
            <div class="card-body text-center">
                <h4 class="text-warning mb-1"><?php echo $fileStats['pending'] ?? 0; ?></h4>
                <small class="text-muted">Bekleyen Dosya</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card admin-card border-left-info">
            <div class="card-body text-center">
                <h4 class="text-info mb-1"><?php echo $fileStats['processing'] ?? 0; ?></h4>
                <small class="text-muted">İşlenen Dosya</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card admin-card border-left-success">
            <div class="card-body text-center">
                <h4 class="text-success mb-1"><?php echo $fileStats['completed'] ?? 0; ?></h4>
                <small class="text-muted">Tamamlanan</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card admin-card border-left-danger">
            <div class="card-body text-center">
                <h4 class="text-danger mb-1"><?php echo $fileStats['rejected'] ?? 0; ?></h4>
                <small class="text-muted">Reddedilen</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Son Yüklenen Dosyalar -->
    <div class="col-lg-12">
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock me-2"></i>Son Yüklenen Dosyalar
                </h5>
                <a href="uploads.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>Tümünü Gör
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentUploads)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder-open fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Henüz dosya yüklenmemiş</h6>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>Dosya Adı</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentUploads, 0, 8) as $upload): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                                    <i class="bi bi-person text-primary"></i>
                                                </div>
                                                <span class="fw-medium"><?php echo htmlspecialchars($upload['username'] ?? 'Bilinmiyor'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-block" style="max-width: 200px;" title="<?php echo htmlspecialchars($upload['original_name'] ?? $upload['filename'] ?? 'Bilinmiyor'); ?>">
                                                <?php echo htmlspecialchars($upload['original_name'] ?? $upload['filename'] ?? 'Bilinmiyor'); ?>
                                            </span>
                                            <small class="text-muted"><?php echo formatFileSize($upload['file_size'] ?? 0); ?></small>
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
                                                <a href="file-detail.php?id=<?php echo $upload['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm" title="Detay">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($upload['status'] === 'pending'): ?>
                                                    <a href="uploads.php?action=process&id=<?php echo $upload['id']; ?>" 
                                                       class="btn btn-outline-success btn-sm" title="İşle">
                                                        <i class="bi bi-play"></i>
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
    </div>

</div>

<!-- Email Sistemi Widget -->
<!-- <div class="row mt-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-envelope-gear me-2"></i>Email Sistemi
                </h5>
                <div>
                    <a href="email-dashboard-fixed.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-speedometer2 me-1"></i>Email Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Email sistem durumu kontrolü
                $emailStatus = [
                    'email_manager' => file_exists('../includes/EmailManager.php'),
                    'email_tables' => false,
                    'templates' => file_exists('../email_templates/verification.html'),
                    'smtp_config' => !empty(getenv('SMTP_HOST'))
                ];
                
                // Email tabloları kontrolü
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'email_queue'");
                    $emailStatus['email_tables'] = $stmt->rowCount() > 0;
                } catch (Exception $e) {
                    $emailStatus['email_tables'] = false;
                }
                
                $emailOverallStatus = array_sum($emailStatus) / count($emailStatus) * 100;
                ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>📊 Sistem Durumu</h6>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar <?php echo $emailOverallStatus >= 75 ? 'bg-success' : ($emailOverallStatus >= 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                 role="progressbar" style="width: <?php echo $emailOverallStatus; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo round($emailOverallStatus); ?>% Tamamlandı</small>
                        
                        <div class="mt-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge <?php echo $emailStatus['email_manager'] ? 'bg-success' : 'bg-danger'; ?> me-2">●</span>
                                <small>Email Manager</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge <?php echo $emailStatus['email_tables'] ? 'bg-success' : 'bg-warning'; ?> me-2">●</span>
                                <small>Email Tabloları</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge <?php echo $emailStatus['templates'] ? 'bg-success' : 'bg-warning'; ?> me-2">●</span>
                                <small>Email Templates</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge <?php echo $emailStatus['smtp_config'] ? 'bg-success' : 'bg-warning'; ?> me-2">●</span>
                                <small>SMTP Ayarları</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>🚀 Hızlı Eylemler</h6>
                        <div class="d-grid gap-2">
                            <?php if ($emailOverallStatus < 100): ?>
                                <a href="../email-setup.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-tools me-1"></i>Email Sistemi Kurulumu
                                </a>
                                
                                <a href="../create-email-tables-simple.php" class="btn btn-warning btn-sm">
                                    <i class="bi bi-database-add me-1"></i>Eksik Tabloları Oluştur
                                </a>
                            <?php endif; ?>
                            
                            <a href="../email-system-test.php" class="btn btn-success btn-sm">
                                <i class="bi bi-bug me-1"></i>Sistem Testleri
                            </a>
                            
                            <a href="../smtp-test.php" class="btn btn-info btn-sm">
                                <i class="bi bi-gear me-1"></i>SMTP Test & Ayarlar
                            </a>
                            
                            <a href="../email-test-simple.php" class="btn btn-warning btn-sm">
                                <i class="bi bi-envelope me-1"></i>Email Test Gönder
                            </a>
                            
                            <div class="btn-group" role="group">
                                <a href="email-settings.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-gear me-1"></i>Ayarlar
                                </a>
                                <a href="email-analytics.php" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-graph-up me-1"></i>Analytics
                                </a>
                            </div>
                        </div>
                        
                        <?php if ($emailOverallStatus >= 75): ?>
                            <div class="alert alert-success mt-3 mb-0">
                                <small><i class="bi bi-check-circle me-1"></i><strong>Email sistemi hazır!</strong> Tüm özellikler kullanılabilir.</small>
                            </div>
                        <?php elseif ($emailOverallStatus >= 50): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <small><i class="bi bi-exclamation-triangle me-1"></i><strong>Email sistemi kısmen hazır.</strong> Kurulumu tamamlayın.</small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mt-3 mb-0">
                                <small><i class="bi bi-x-circle me-1"></i><strong>Email sistemi kurulmamış.</strong> Kurulumu başlatın.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->

<!-- Grafik Alanı -->
<!-- <div class="row mt-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi bar-chart-line me-2"></i>Son 7 Günlük Aktivite
                </h5>
            </div>
            <div class="card-body">
                <canvas id="activityChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div> -->

<?php
// Sayfa özel JavaScript
$pageJS = "
// File size formatter function
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Activity Chart
const ctx = document.getElementById('activityChart').getContext('2d');
const activityChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['6 gün önce', '5 gün önce', '4 gün önce', '3 gün önce', '2 gün önce', 'Dün', 'Bugün'],
        datasets: [{
            label: 'Dosya Yüklemeleri',
            data: [12, 19, 8, 15, 25, 18, " . $todayUploads . "],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }, {
            label: 'Yeni Kullanıcılar',
            data: [2, 5, 3, 8, 4, 6, " . $todayUsers . "],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 300000);
";

// Footer include
include '../includes/admin_footer.php';
?>
