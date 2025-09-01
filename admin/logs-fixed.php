<?php
/**
 * Mr ECU - Admin Sistem Logları (Düzeltilmiş)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$filter = sanitize($_GET['filter'] ?? 'all');

try {
    // Log kayıtlarını al - Basitleştirilmiş versiyon
    $where_clause = "";
    $params = [];
    
    // Sadece security_logs tablosundan veri al
    if ($filter !== 'all') {
        $where_clause = "WHERE event_type LIKE ?";
        $params[] = "%$filter%";
    }
    
    $offset = ($page - 1) * $limit;
    
    // Güvenlik logları
    $logs_query = "
        SELECT 
            'security' as source, 
            CASE 
                WHEN event_type IN ('sql_injection_attempt', 'xss_attempt', 'malicious_file_upload') THEN 'critical'
                WHEN event_type IN ('brute_force_detected', 'csrf_token_invalid', 'rate_limit_exceeded') THEN 'warning'
                ELSE 'info'
            END as level,
            created_at, 
            CONCAT('Security Event: ', event_type) as message,
            details,
            ip_address,
            user_agent
        FROM security_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        $where_clause
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $logs_stmt = $pdo->prepare($logs_query);
    $logs_stmt->execute($params);
    $logs = $logs_stmt->fetchAll();
    
    // Toplam kayıt sayısı
    $count_query = "SELECT COUNT(*) as total FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'] ?? 0;
    $total_pages = ceil($total_records / $limit);
    
} catch (Exception $e) {
    error_log('Logs page error: ' . $e->getMessage());
    $logs = [];
    $total_records = 0;
    $total_pages = 1;
}

$pageTitle = 'Sistem Logları';
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
    <style>
        .log-critical { background: #fff5f5; border-left: 4px solid #dc3545; }
        .log-warning { background: #fffbf0; border-left: 4px solid #ffc107; }
        .log-info { background: #f0f9ff; border-left: 4px solid #17a2b8; }
        .log-source { font-size: 0.8em; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include '../includes/user_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/user_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-history me-2"></i><?php echo $pageTitle; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="bi bi-sync-alt me-1"></i>Yenile
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtre -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" onchange="filterLogs(this.value)">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tüm Loglar</option>
                            <option value="critical" <?php echo $filter === 'critical' ? 'selected' : ''; ?>>Kritik Olaylar</option>
                            <option value="warning" <?php echo $filter === 'warning' ? 'selected' : ''; ?>>Uyarılar</option>
                            <option value="injection" <?php echo $filter === 'injection' ? 'selected' : ''; ?>>SQL Injection</option>
                            <option value="xss" <?php echo $filter === 'xss' ? 'selected' : ''; ?>>XSS Saldırıları</option>
                            <option value="brute_force" <?php echo $filter === 'brute_force' ? 'selected' : ''; ?>>Brute Force</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Son 30 günlük loglar gösteriliyor.</strong> 
                            Toplam <?php echo number_format($total_records); ?> kayıt bulundu.
                        </div>
                    </div>
                </div>

                <!-- Log Listesi -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-file-alt text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">Henüz log kaydı bulunmuyor.</p>
                                <p class="text-muted">Güvenlik olayları otomatik olarak burada görünecektir.</p>
                            </div>
                        <?php else: ?>
                            <div class="log-container">
                                <?php foreach ($logs as $log): ?>
                                    <div class="log-entry log-<?php echo $log['level']; ?> p-3 mb-2 rounded">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <span class="log-source badge bg-<?php 
                                                echo $log['source'] === 'security' ? 'danger' : 'primary'; 
                                                ?>">
                                                    <?php echo strtoupper($log['source']); ?>
                                                </span>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo formatDate($log['created_at']); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-1">
                                                <span class="badge bg-<?php 
                                                switch($log['level']) {
                                                    case 'critical': echo 'danger'; break;
                                                    case 'warning': echo 'warning'; break;
                                                    default: echo 'info'; break;
                                                }
                                                ?>">
                                                    <?php echo strtoupper($log['level']); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-5">
                                                <strong><?php echo htmlspecialchars($log['message']); ?></strong>
                                                <?php if (!empty($log['ip_address'])): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-globe me-1"></i>IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3">
                                                <?php if (!empty($log['details'])): ?>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $details = json_decode($log['details'], true);
                                                        if (is_array($details)) {
                                                            echo htmlspecialchars(substr(json_encode($details, JSON_UNESCAPED_UNICODE), 0, 100)) . '...';
                                                        } else {
                                                            echo htmlspecialchars(substr($log['details'], 0, 100)) . '...';
                                                        }
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-1 text-end">
                                                <?php if ($log['level'] === 'critical'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Log pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterLogs(level) {
            window.location.href = '?filter=' + encodeURIComponent(level);
        }

        // Auto refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
