<?php
/**
 * Mr ECU - Admin Raporlar (Düzeltilmiş Versiyon)
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
    // Kullanıcı istatistikleri (last_login problemi düzeltildi)
    $user_stats_query = "
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as monthly_new_users,
            SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_active_users,
            SUM(CASE WHEN role = 'user' THEN credits ELSE 0 END) as total_credits
        FROM users
    ";
    
    // executeSecureQuery yerine normal PDO kullan
    $user_stats_stmt = $pdo->query($user_stats_query);
    $user_stats = $user_stats_stmt->fetch();
    
    // Dosya istatistikleri
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
    
    // Kredi istatistikleri (user_credits tablosundan)
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
    
    // Günlük aktivite (son 30 gün)
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
    
    // Popüler markalar
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
    
    // Güvenlik olayları (eğer güvenlik sistemi aktifse)
    $security_stats = [];
    if (SECURITY_ENABLED) {
        $security_check = $pdo->query("SHOW TABLES LIKE 'security_logs'");
        if ($security_check->rowCount() > 0) {
            $security_stats_query = "
                SELECT 
                    event_type,
                    COUNT(*) as event_count,
                    MAX(created_at) as last_event
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY event_type
                ORDER BY event_count DESC
                LIMIT 5
            ";
            $security_stats_stmt = $pdo->query($security_stats_query);
            $security_stats = $security_stats_stmt->fetchAll();
        }
    }
    
} catch (Exception $e) {
    error_log('Reports page error: ' . $e->getMessage());
    // Varsayılan değerler
    $user_stats = ['total_users' => 0, 'monthly_new_users' => 0, 'weekly_active_users' => 0, 'total_credits' => 0];
    $file_stats = ['total_files' => 0, 'monthly_uploads' => 0, 'completed_files' => 0, 'pending_files' => 0, 'avg_file_size' => 0];
    $credit_stats = ['total_credits_purchased' => 0, 'total_credits_spent' => 0, 'monthly_transactions' => 0];
    $daily_activity = [];
    $popular_brands = [];
    $security_stats = [];
}

$pageTitle = 'Raporlar';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-card { transition: transform 0.2s; }
        .report-card:hover { transform: translateY(-5px); }
        .chart-container { position: relative; height: 300px; }
        .metric-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; }
        .table-stats th { background: #f8f9fa; }
        .debug-info { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-bar me-2"></i><?php echo $pageTitle; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>Yenile
                            </button>
                            <a href="reports-debug.php" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-bug me-1"></i>Debug
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Debug Bilgisi -->
                <div class="debug-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Debug:</strong> 
                    user_credits tablosu: <?php 
                    $table_check = $pdo->query("SHOW TABLES LIKE 'user_credits'");
                    echo $table_check->rowCount() > 0 ? '✅ Mevcut' : '❌ Yok'; 
                    ?> | 
                    Toplam dosya: <?php echo $file_stats['total_files']; ?> | 
                    Aktif kullanıcı: <?php echo $user_stats['total_users']; ?>
                </div>

                <!-- Genel Metrikler -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card metric-card report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <h3><?php echo number_format((int)$user_stats['total_users']); ?></h3>
                                <p class="mb-0">Toplam Kullanıcı</p>
                                <small class="opacity-75">+<?php echo (int)$user_stats['monthly_new_users']; ?> bu ay</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card bg-success text-white report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-file fa-3x mb-3"></i>
                                <h3><?php echo number_format((int)$file_stats['total_files']); ?></h3>
                                <p class="mb-0">Toplam Dosya</p>
                                <small class="opacity-75">+<?php echo (int)$file_stats['monthly_uploads']; ?> bu ay</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-coins fa-3x mb-3"></i>
                                <h3><?php echo number_format((float)$credit_stats['total_credits_purchased'], 0); ?></h3>
                                <p class="mb-0">Toplam Kredi Satışı</p>
                                <small class="opacity-75"><?php echo (int)$credit_stats['monthly_transactions']; ?> işlem/ay</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card bg-info text-white report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-percentage fa-3x mb-3"></i>
                                <h3><?php 
                                echo $file_stats['total_files'] > 0 ? 
                                     round(((int)$file_stats['completed_files'] / (int)$file_stats['total_files']) * 100, 1) : 0; 
                                ?>%</h3>
                                <p class="mb-0">Tamamlanma Oranı</p>
                                <small class="opacity-75"><?php echo (int)$file_stats['pending_files']; ?> dosya bekliyor</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafikler ve Tablolar -->
                <div class="row">
                    <!-- Günlük Aktivite Grafiği -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Günlük Dosya Yükleme Aktivitesi (Son 30 Gün)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($daily_activity)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-chart-line text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Son 30 günde aktivite yok</p>
                                    </div>
                                <?php else: ?>
                                    <div class="chart-container">
                                        <canvas id="activityChart"></canvas>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Dosya Durum Dağılımı -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Dosya Durumları
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ((int)$file_stats['total_files'] == 0): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-file text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Henüz dosya yok</p>
                                    </div>
                                <?php else: ?>
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Popüler Markalar -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-car me-2"></i>Popüler Araç Markaları
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($popular_brands)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-chart-bar text-muted fa-3x"></i>
                                        <p class="text-muted mt-3">Henüz marka verisi yok</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-stats">
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

                    <!-- Güvenlik Olayları veya Sistem Bilgileri -->
                    <div class="col-md-6 mb-4">
                        <?php if (SECURITY_ENABLED && !empty($security_stats)): ?>
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>Güvenlik Olayları (Son 30 Gün)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Olay Türü</th>
                                                <th>Sayı</th>
                                                <th>Son Olay</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($security_stats as $security): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                        echo in_array($security['event_type'], ['sql_injection_attempt', 'xss_attempt']) ? 'danger' : 'warning'; 
                                                        ?>">
                                                            <?php echo htmlspecialchars($security['event_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><strong><?php echo number_format($security['event_count']); ?></strong></td>
                                                    <td><small><?php echo formatDate($security['last_event']); ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <a href="security-dashboard.php" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-eye me-1"></i>Detaylı Görüntüle
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Sistem Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li><strong>PHP Sürümü:</strong> <?php echo phpversion(); ?></li>
                                    <li><strong>Sunucu:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor'; ?></li>
                                    <li><strong>Ortalama Dosya Boyutu:</strong> <?php echo number_format((float)$file_stats['avg_file_size'] / 1024, 2); ?> KB</li>
                                    <li><strong>Aktif Kullanıcı (7 gün):</strong> <?php echo (int)$user_stats['weekly_active_users']; ?></li>
                                    <li><strong>Güvenlik Sistemi:</strong> 
                                        <span class="badge bg-<?php echo SECURITY_ENABLED ? 'success' : 'warning'; ?>">
                                            <?php echo SECURITY_ENABLED ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </li>
                                    <li><strong>Toplam Kredi:</strong> <?php echo number_format((float)$user_stats['total_credits'], 2); ?> ₺</li>
                                </ul>
                                
                                <!-- Hızlı İşlemler -->
                                <div class="mt-3">
                                    <h6>Hızlı İşlemler:</h6>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="fix-users-table.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-wrench me-1"></i>Users Tablosu
                                        </a>
                                        <a href="create-user-credits.php" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-coins me-1"></i>Kredi Tablosu
                                        </a>
                                        <a href="debug-database.php" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-database me-1"></i>DB Debug
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (!empty($daily_activity)): ?>
        // Günlük aktivite grafiği
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    echo implode(',', array_map(function($item) {
                        return "'" . date('d.m', strtotime($item['activity_date'])) . "'";
                    }, $daily_activity));
                ?>],
                datasets: [{
                    label: 'Dosya Yüklemeleri',
                    data: [<?php 
                        echo implode(',', array_map(function($item) {
                            return $item['uploads_count'];
                        }, $daily_activity));
                    ?>],
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
        <?php endif; ?>

        <?php if ((int)$file_stats['total_files'] > 0): ?>
        // Dosya durum dağılımı grafiği
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Tamamlanan', 'Bekleyen', 'Diğer'],
                datasets: [{
                    data: [
                        <?php echo (int)$file_stats['completed_files']; ?>,
                        <?php echo (int)$file_stats['pending_files']; ?>,
                        <?php echo (int)$file_stats['total_files'] - (int)$file_stats['completed_files'] - (int)$file_stats['pending_files']; ?>
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
        <?php endif; ?>

        // Auto refresh every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
