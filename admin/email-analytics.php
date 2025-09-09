<?php
/**
 * Mr ECU - Email Analytics ve Monitoring
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Email analytics verilerini getir
function getEmailAnalytics($pdo, $days = 30) {
    try {
        $analytics = [];
        
        // Günlük email istatistikleri
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM email_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$days]);
        $analytics['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Saatlik dağılım (son 24 saat)
        $stmt = $pdo->query("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
            FROM email_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ");
        $analytics['hourly'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Email tipleri dağılımı (subject'e göre)
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN subject LIKE '%Doğrulama%' THEN 'Email Doğrulama'
                    WHEN subject LIKE '%Şifre%' THEN 'Şifre Sıfırlama'
                    WHEN subject LIKE '%Yeni Dosya%' THEN 'Dosya Bildirimleri'
                    WHEN subject LIKE '%Hazır%' THEN 'Dosya Hazır'
                    WHEN subject LIKE '%Revizyon%' THEN 'Revizyon Talepleri'
                    WHEN subject LIKE '%Test%' THEN 'Test Emailler'
                    ELSE 'Diğer'
                END as email_type,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                ROUND(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as success_rate
            FROM email_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY email_type
            ORDER BY count DESC
        ");
        $analytics['types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // En çok email alan domainler
        $stmt = $pdo->query("
            SELECT 
                SUBSTRING_INDEX(to_email, '@', -1) as domain,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                ROUND(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as success_rate
            FROM email_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY domain
            HAVING count >= 5
            ORDER BY count DESC
            LIMIT 10
        ");
        $analytics['domains'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hata analizi
        $stmt = $pdo->query("
            SELECT 
                error_message,
                COUNT(*) as count,
                DATE(MAX(created_at)) as last_occurrence
            FROM email_queue 
            WHERE status = 'failed' 
            AND error_message IS NOT NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY error_message
            ORDER BY count DESC
            LIMIT 10
        ");
        $analytics['errors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Genel özet
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_emails,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                ROUND(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as overall_success_rate,
                COUNT(DISTINCT to_email) as unique_recipients,
                COUNT(DISTINCT DATE(created_at)) as active_days
            FROM email_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $analytics['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $analytics;
    } catch (PDOException $e) {
        error_log('getEmailAnalytics error: ' . $e->getMessage());
        return null;
    }
}

// Sistem sağlığını kontrol et
function checkEmailSystemHealth($pdo) {
    $health = [];
    
    try {
        // SMTP bağlantı testi
        $health['smtp'] = [
            'status' => 'unknown',
            'message' => 'Test edilecek'
        ];
        
        // Veritabanı tabloları kontrolü
        $requiredTables = ['email_queue', 'users', 'email_logs'];
        $health['database'] = ['status' => 'ok', 'missing_tables' => []];
        
        foreach ($requiredTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                $health['database']['status'] = 'error';
                $health['database']['missing_tables'][] = $table;
            }
        }
        
        // Email queue durumu
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as pending_count,
                MAX(created_at) as newest_pending
            FROM email_queue 
            WHERE status = 'pending'
        ");
        $queueStatus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($queueStatus['pending_count'] > 100) {
            $health['queue'] = [
                'status' => 'warning',
                'message' => 'Çok sayıda bekleyen email var: ' . $queueStatus['pending_count']
            ];
        } elseif ($queueStatus['pending_count'] > 0) {
            $health['queue'] = [
                'status' => 'info',
                'message' => $queueStatus['pending_count'] . ' bekleyen email'
            ];
        } else {
            $health['queue'] = [
                'status' => 'ok',
                'message' => 'Email kuyruğu temiz'
            ];
        }
        
        // Son 24 saatte hata oranı
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM email_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $errorRate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($errorRate['total'] > 0) {
            $failureRate = ($errorRate['failed'] / $errorRate['total']) * 100;
            if ($failureRate > 20) {
                $health['error_rate'] = [
                    'status' => 'error',
                    'message' => 'Yüksek hata oranı: %' . round($failureRate, 1)
                ];
            } elseif ($failureRate > 10) {
                $health['error_rate'] = [
                    'status' => 'warning',
                    'message' => 'Orta hata oranı: %' . round($failureRate, 1)
                ];
            } else {
                $health['error_rate'] = [
                    'status' => 'ok',
                    'message' => 'Düşük hata oranı: %' . round($failureRate, 1)
                ];
            }
        } else {
            $health['error_rate'] = [
                'status' => 'ok',
                'message' => 'Son 24 saatte email gönderilmedi'
            ];
        }
        
        // Log dosyası boyutu kontrolü
        $logFile = __DIR__ . '/../logs/email_test.log';
        if (file_exists($logFile)) {
            $logSize = filesize($logFile);
            if ($logSize > 10 * 1024 * 1024) { // 10MB
                $health['logs'] = [
                    'status' => 'warning',
                    'message' => 'Log dosyası çok büyük: ' . round($logSize / 1024 / 1024, 1) . ' MB'
                ];
            } else {
                $health['logs'] = [
                    'status' => 'ok',
                    'message' => 'Log dosyası boyutu normal: ' . round($logSize / 1024, 1) . ' KB'
                ];
            }
        } else {
            $health['logs'] = [
                'status' => 'info',
                'message' => 'Log dosyası bulunamadı'
            ];
        }
        
    } catch (Exception $e) {
        error_log('checkEmailSystemHealth error: ' . $e->getMessage());
        $health['general'] = [
            'status' => 'error',
            'message' => 'Sistem sağlığı kontrol edilemedi'
        ];
    }
    
    return $health;
}

$analytics = getEmailAnalytics($pdo, 30);
$health = checkEmailSystemHealth($pdo);

$pageTitle = 'Email Analytics';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-graph-up me-2"></i>
                    Email Analytics ve Monitoring
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Yenile
                    </button>
                </div>
            </div>
            
            <!-- Sistem Sağlığı -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-heart-pulse me-2"></i>
                                Sistem Sağlığı
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($health as $component => $status): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $iconClass = 'bi-question-circle text-secondary';
                                        $badgeClass = 'bg-secondary';
                                        
                                        switch ($status['status']) {
                                            case 'ok':
                                                $iconClass = 'bi-check-circle text-success';
                                                $badgeClass = 'bg-success';
                                                break;
                                            case 'warning':
                                                $iconClass = 'bi-exclamation-triangle text-warning';
                                                $badgeClass = 'bg-warning';
                                                break;
                                            case 'error':
                                                $iconClass = 'bi-x-circle text-danger';
                                                $badgeClass = 'bg-danger';
                                                break;
                                            case 'info':
                                                $iconClass = 'bi-info-circle text-info';
                                                $badgeClass = 'bg-info';
                                                break;
                                        }
                                        ?>
                                        <i class="bi <?php echo $iconClass; ?> me-2"></i>
                                        <div>
                                            <div class="fw-bold"><?php echo ucfirst($component); ?></div>
                                            <small class="text-muted"><?php echo $status['message']; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Özet İstatistikler -->
            <?php if ($analytics && $analytics['summary']): ?>
            <div class="row mb-4">
                <div class="col-md-2 col-sm-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1"><?php echo number_format($analytics['summary']['total_emails']); ?></h3>
                            <small>Toplam Email</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1"><?php echo number_format($analytics['summary']['total_sent']); ?></h3>
                            <small>Gönderilen</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1"><?php echo number_format($analytics['summary']['total_failed']); ?></h3>
                            <small>Başarısız</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1"><?php echo number_format($analytics['summary']['total_pending']); ?></h3>
                            <small>Bekleyen</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1"><?php echo $analytics['summary']['overall_success_rate']; ?>%</h3>
                            <small>Başarı Oranı</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1"><?php echo number_format($analytics['summary']['unique_recipients']); ?></h3>
                            <small>Benzersiz Alıcı</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Günlük Email Grafiği -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Son 30 Günün Email Aktivitesi</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyEmailChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Email Tipları -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Email Tipları</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="emailTypesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Domain İstatistikleri -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Email Domainleri</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($analytics['domains'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Domain</th>
                                            <th>Toplam</th>
                                            <th>Başarı Oranı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($analytics['domains'] as $domain): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($domain['domain']); ?></td>
                                            <td><?php echo $domain['count']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $domain['success_rate'] >= 90 ? 'bg-success' : ($domain['success_rate'] >= 70 ? 'bg-warning' : 'bg-danger'); ?>">
                                                    <?php echo $domain['success_rate']; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">Yeterli veri yok.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Hata Analizi -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">En Sık Karşılaşılan Hatalar</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($analytics['errors'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Hata Mesajı</th>
                                            <th>Sayı</th>
                                            <th>Son Görülme</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($analytics['errors'] as $error): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo htmlspecialchars(substr($error['error_message'], 0, 50)) . (strlen($error['error_message']) > 50 ? '...' : ''); ?></small>
                                            </td>
                                            <td><?php echo $error['count']; ?></td>
                                            <td><small><?php echo date('d.m.Y', strtotime($error['last_occurrence'])); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted text-center">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Son 7 günde hata kaydı yok!
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Günlük email grafiği
<?php if (!empty($analytics['daily'])): ?>
const dailyData = <?php echo json_encode(array_reverse($analytics['daily'])); ?>;
const dailyCtx = document.getElementById('dailyEmailChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'Gönderilen',
            data: dailyData.map(d => d.sent),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }, {
            label: 'Başarısız',
            data: dailyData.map(d => d.failed),
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1
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
<?php endif; ?>

// Email tipları grafiği
<?php if (!empty($analytics['types'])): ?>
const typesData = <?php echo json_encode($analytics['types']); ?>;
const typesCtx = document.getElementById('emailTypesChart').getContext('2d');
new Chart(typesCtx, {
    type: 'doughnut',
    data: {
        labels: typesData.map(t => t.email_type),
        datasets: [{
            data: typesData.map(t => t.count),
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40',
                '#FF6384'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
<?php endif; ?>

// Otomatik yenileme (5 dakikada bir)
setInterval(function() {
    location.reload();
}, 5 * 60 * 1000);
</script>

<?php include '../includes/admin_footer.php'; ?>
