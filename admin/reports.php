<?php
/**
 * Mr ECU - Admin Raporlar
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$report_type = sanitize($_GET['type'] ?? 'overview');
$date_range = sanitize($_GET['range'] ?? '30');

try {
    // Kullanıcı istatistikleri (last_login sütunu kontrolü ile)
    $user_stats_query = "
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as monthly_new_users,
            0 as weekly_active_users,
            SUM(CASE WHEN role = 'user' AND credits IS NOT NULL THEN credits ELSE 0 END) as total_credits
        FROM users
    ";
    
    $user_stats_stmt = $pdo->query($user_stats_query);
    $user_stats = $user_stats_stmt->fetch();
    
    // Dosya istatistikleri (doğru tablo adı ile)
    $file_stats_query = "
        SELECT 
            COUNT(*) as total_files,
            SUM(CASE WHEN upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as monthly_uploads,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_files,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_files,
            AVG(file_size) as avg_file_size
        FROM file_uploads
    ";
    $file_stats_stmt = $pdo->query($file_stats_query);
    $file_stats = $file_stats_stmt->fetch();
    
    // Kredi istatistikleri
    $credit_stats = ['total_credits_purchased' => 0, 'total_credits_spent' => 0, 'monthly_transactions' => 0];
    
    // user_credits tablosu var mı kontrol et
    $table_check = $pdo->query("SHOW TABLES LIKE 'user_credits'");
    if ($table_check->rowCount() > 0) {
        $credit_stats_query = "
            SELECT 
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_credits_purchased,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_credits_spent,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_transactions
            FROM user_credits
        ";
        $credit_stats_stmt = $pdo->query($credit_stats_query);
        $credit_stats = $credit_stats_stmt->fetch();
    }
    
    // Günlük aktivite (son 30 gün) - doğru tablo adı
    $daily_activity_query = "
        SELECT 
            DATE(upload_date) as activity_date,
            COUNT(*) as uploads_count
        FROM file_uploads 
        WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(upload_date)
        ORDER BY activity_date ASC
    ";
    $daily_activity_stmt = $pdo->query($daily_activity_query);
    $daily_activity = $daily_activity_stmt->fetchAll();
    
    // Popüler markalar - doğru tablo adı ve sütun adları
    $popular_brands_query = "
        SELECT 
            b.name as brand_name,
            COUNT(fu.id) as upload_count,
            ROUND((COUNT(fu.id) * 100.0) / NULLIF((SELECT COUNT(*) FROM file_uploads), 0), 2) as percentage
        FROM brands b
        LEFT JOIN file_uploads fu ON b.id = fu.brand_id
        WHERE b.status = 'active'
        GROUP BY b.id, b.name
        HAVING upload_count > 0
        ORDER BY upload_count DESC
        LIMIT 10
    ";
    $popular_brands_stmt = $pdo->query($popular_brands_query);
    $popular_brands = $popular_brands_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Reports page error: ' . $e->getMessage());
    // Varsayılan değerler
    $user_stats = ['total_users' => 0, 'monthly_new_users' => 0, 'weekly_active_users' => 0, 'total_credits' => 0];
    $file_stats = ['total_files' => 0, 'monthly_uploads' => 0, 'completed_files' => 0, 'pending_files' => 0, 'avg_file_size' => 0];
    $credit_stats = ['total_credits_purchased' => 0, 'total_credits_spent' => 0, 'monthly_transactions' => 0];
    $daily_activity = [];
    $popular_brands = [];
}

$pageTitle = 'Raporlar';
$pageDescription = 'Sistem performansı ve kullanım istatistikleri';
$pageIcon = 'bi bi-chart-bar';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Genel Metrikler -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format((int)$user_stats['total_users']); ?></div>
                    <div class="stat-label">Toplam Kullanıcı</div>
                    <small class="text-success">+<?php echo (int)$user_stats['monthly_new_users']; ?> bu ay</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="bi bi-users text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format((int)$file_stats['total_files']); ?></div>
                    <div class="stat-label">Toplam Dosya</div>
                    <small class="text-success">+<?php echo (int)$file_stats['monthly_uploads']; ?> bu ay</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="bi bi-file text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo number_format((float)$credit_stats['total_credits_purchased'], 0); ?></div>
                    <div class="stat-label">Toplam Kredi Satışı</div>
                    <small class="text-muted"><?php echo (int)$credit_stats['monthly_transactions']; ?> işlem/ay</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="bi bi-coins text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php 
                    echo $file_stats['total_files'] > 0 ? 
                         round(((int)$file_stats['completed_files'] / (int)$file_stats['total_files']) * 100, 1) : 0; 
                    ?>%</div>
                    <div class="stat-label">Tamamlanma Oranı</div>
                    <small class="text-muted"><?php echo (int)$file_stats['pending_files']; ?> dosya bekliyor</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="bi bi-percentage text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafikler ve Tablolar -->
<div class="row">
    <!-- Günlük Aktivite Grafiği -->
    <div class="col-md-8 mb-4">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-chart-line me-2"></i>Günlük Dosya Yükleme Aktivitesi (Son 30 Gün)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($daily_activity)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chart-line text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Son 30 günde aktivite yok</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container">
                        <canvas id="activityChart" style="height: 300px;"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dosya Durum Dağılımı -->
    <div class="col-md-4 mb-4">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-chart-pie me-2"></i>Dosya Durumları
                </h5>
            </div>
            <div class="card-body">
                <?php if ((int)$file_stats['total_files'] == 0): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-file text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Henüz dosya yok</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container">
                        <canvas id="statusChart" style="height: 300px;"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Popüler Markalar -->
    <div class="col-md-6 mb-4">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-car me-2"></i>Popüler Araç Markaları
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($popular_brands)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-chart-bar text-muted fa-3x"></i>
                        <p class="text-muted mt-3">Henüz marka verisi yok</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-admin table-sm">
                            <thead>
                                <tr>
                                    <th>Marka</th>
                                    <th>Dosya Sayısı</th>
                                    <th>Oran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_brands as $brand): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($brand['brand_name']); ?></strong></td>
                                        <td><?php echo number_format($brand['upload_count']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                    <div class="progress-bar bg-primary" style="width: <?php echo $brand['percentage']; ?>%"></div>
                                                </div>
                                                <small><?php echo $brand['percentage']; ?>%</small>
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

    <!-- Sistem Bilgileri -->
    <div class="col-md-6 mb-4">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>Sistem Bilgileri
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><strong>PHP Sürümü:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>Sunucu:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor'; ?></li>
                    <li><strong>Ortalama Dosya Boyutu:</strong> <?php echo number_format((float)$file_stats['avg_file_size'] / 1024, 2); ?> KB</li>
                    <li><strong>Aktif Kullanıcı (7 gün):</strong> <?php echo (int)$user_stats['weekly_active_users']; ?></li>
                    <li><strong>Toplam Kredi:</strong> <?php echo number_format((float)$user_stats['total_credits'], 2); ?> ₺</li>
                </ul>
                
                <!-- Hızlı İşlemler -->
                <div class="mt-3">
                    <h6>Hızlı İşlemler:</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="settings.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-cog me-1"></i>Ayarlar
                        </a>
                        <a href="logs.php" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-list me-1"></i>Loglar
                        </a>
                        <a href="debug-database.php" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-database me-1"></i>DB Debug
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Chart.js ekleme
$additionalJS = ['https://cdn.jsdelivr.net/npm/chart.js'];

$pageJS = "
    " . (!empty($daily_activity) ? "
    // Günlük aktivite grafiği
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityChart = new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: [" . implode(',', array_map(function($item) {
                return "'" . date('d.m', strtotime($item['activity_date'])) . "'";
            }, $daily_activity)) . "],
            datasets: [{
                label: 'Dosya Yüklemeleri',
                data: [" . implode(',', array_map(function($item) {
                    return $item['uploads_count'];
                }, $daily_activity)) . "],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    " : "") . "

    " . ((int)$file_stats['total_files'] > 0 ? "
    // Dosya durum dağılımı grafiği
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Tamamlanan', 'Bekleyen', 'Diğer'],
            datasets: [{
                data: [
                    " . (int)$file_stats['completed_files'] . ",
                    " . (int)$file_stats['pending_files'] . ",
                    " . ((int)$file_stats['total_files'] - (int)$file_stats['completed_files'] - (int)$file_stats['pending_files']) . "
                ],
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#17a2b8'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    " : "") . "

    // Auto refresh every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
";

// Footer include
include '../includes/admin_footer.php';
?>
