<?php
/**
 * Mr ECU - Admin İşlem Geçmişi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$filter = sanitize($_GET['filter'] ?? 'all');
$date_filter = sanitize($_GET['date'] ?? '');

try {
    // İşlem geçmişini al
    $where_conditions = [];
    $params = [];
    
    // Kredi işlemleri ve dosya işlemlerini birleştir
    $base_query = "
        SELECT 
            'credit' as type,
            id,
            user_id,
            amount as transaction_amount,
            transaction_type as action_type,
            description,
            reference_id,
            reference_type,
            created_at,
            NULL as admin_id
        FROM user_credits 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        
        UNION ALL
        
        SELECT 
            'file' as type,
            id,
            user_id,
            0 as transaction_amount,
            status as action_type,
            CONCAT('Dosya: ', original_name) as description,
            id as reference_id,
            'file_upload' as reference_type,
            upload_date as created_at,
            NULL as admin_id
        FROM file_uploads 
        WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    ";
    
    // Filtre uygula
    if ($filter !== 'all') {
        $base_query = "
            SELECT * FROM (
                $base_query
            ) as combined_transactions
            WHERE action_type = ?
        ";
        $params[] = $filter;
    } else {
        $base_query = "
            SELECT * FROM (
                $base_query
            ) as combined_transactions
        ";
    }
    
    // Tarih filtresi
    if (!empty($date_filter)) {
        $base_query .= $filter !== 'all' ? ' AND ' : ' WHERE ';
        $base_query .= 'DATE(created_at) = ?';
        $params[] = $date_filter;
    }
    
    $transactions_query = $base_query . "
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    if (!empty($params)) {
        $transactions_stmt = $pdo->prepare($transactions_query);
        $transactions_stmt->execute($params);
    } else {
        $transactions_stmt = $pdo->query($transactions_query);
    }
    $transactions = $transactions_stmt->fetchAll();
    
    // Toplam kayıt sayısı
    try {
        $count_base_query = "
            SELECT created_at, 'credit' as type, transaction_type as action_type
            FROM user_credits WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            UNION ALL
            SELECT upload_date as created_at, 'file' as type, status as action_type
            FROM file_uploads WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        ";
        
        if ($filter !== 'all' || !empty($date_filter)) {
            $count_query = "SELECT COUNT(*) as total FROM ($count_base_query) as combined_transactions";
            $count_params = [];
            
            $count_conditions = [];
            if ($filter !== 'all') {
                $count_conditions[] = 'action_type = ?';
                $count_params[] = $filter;
            }
            if (!empty($date_filter)) {
                $count_conditions[] = 'DATE(created_at) = ?';
                $count_params[] = $date_filter;
            }
            
            if (!empty($count_conditions)) {
                $count_query .= ' WHERE ' . implode(' AND ', $count_conditions);
            }
            
            $count_stmt = $pdo->prepare($count_query);
            $count_stmt->execute($count_params);
        } else {
            $count_query = "SELECT COUNT(*) as total FROM ($count_base_query) as combined_transactions";
            $count_stmt = $pdo->query($count_query);
        }
        
        $count_result = $count_stmt->fetch();
        $total_records = $count_result ? (int)$count_result['total'] : 0;
        $total_pages = ceil($total_records / $limit);
        
    } catch (Exception $e) {
        error_log('Count query error: ' . $e->getMessage());
        $total_records = 0;
        $total_pages = 1;
    }
    
    // İstatistikler
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM user_credits WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as daily_credits,
            (SELECT COUNT(*) FROM file_uploads WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as daily_uploads,
            (SELECT SUM(ABS(amount)) FROM user_credits WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as monthly_credit_volume,
            (SELECT COUNT(*) FROM file_uploads WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as monthly_uploads
    ";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log('Transactions page error: ' . $e->getMessage());
    $transactions = [];
    $total_records = 0;
    $total_pages = 1;
    $stats = ['daily_credits' => 0, 'daily_uploads' => 0, 'monthly_credit_volume' => 0, 'monthly_uploads' => 0];
}

$pageTitle = 'İşlem Geçmişi';
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
        .transaction-credit { border-left: 4px solid #28a745; }
        .transaction-file { border-left: 4px solid #17a2b8; }
        .amount-positive { color: #28a745; font-weight: bold; }
        .amount-negative { color: #dc3545; font-weight: bold; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
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
                        <i class="fas fa-exchange-alt me-2"></i><?php echo $pageTitle; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>Yenile
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportTransactions()">
                                <i class="fas fa-download me-1"></i>Excel İndir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-coins fa-2x mb-2"></i>
                                <h4><?php echo number_format($stats['daily_credits']); ?></h4>
                                <p class="mb-0">Günlük Kredi İşlemleri</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-upload fa-2x mb-2"></i>
                                <h4><?php echo number_format($stats['daily_uploads']); ?></h4>
                                <p class="mb-0">Günlük Dosya Yüklemeleri</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <h4><?php echo number_format($stats['monthly_credit_volume']); ?></h4>
                                <p class="mb-0">Aylık Kredi Hacmi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-file-upload fa-2x mb-2"></i>
                                <h4><?php echo number_format($stats['monthly_uploads']); ?></h4>
                                <p class="mb-0">Aylık Dosya Sayısı</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtreler -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-select" onchange="filterTransactions(this.value, '<?php echo $date_filter; ?>')">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tüm İşlemler</option>
                            <option value="credit_purchase" <?php echo $filter === 'credit_purchase' ? 'selected' : ''; ?>>Kredi Alımı</option>
                            <option value="file_charge" <?php echo $filter === 'file_charge' ? 'selected' : ''; ?>>Dosya Ücreti</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Bekleyen Dosyalar</option>
                            <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Tamamlanan Dosyalar</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" value="<?php echo $date_filter; ?>" 
                               onchange="filterTransactions('<?php echo $filter; ?>', this.value)">
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Son 90 günlük işlemler gösteriliyor.</strong> 
                            Toplam <?php echo number_format((int)$total_records); ?> kayıt bulundu.
                        </div>
                    </div>
                </div>

                <!-- İşlem Listesi -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-exchange-alt text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">Henüz işlem kaydı bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Tür</th>
                                            <th>Kullanıcı</th>
                                            <th>İşlem</th>
                                            <th>Açıklama</th>
                                            <th>Miktar</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr class="transaction-<?php echo $transaction['type']; ?>">
                                                <td>
                                                    <small><?php echo formatDate($transaction['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $transaction['type'] === 'credit' ? 'success' : 'info'; ?>">
                                                        <?php echo $transaction['type'] === 'credit' ? 'Kredi' : 'Dosya'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Kullanıcı bilgisini al
                                                    try {
                                                        $user_stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
                                                        $user_stmt->execute([$transaction['user_id']]);
                                                        $user_info = $user_stmt->fetch();
                                                        if ($user_info) {
                                                            echo '<strong>' . htmlspecialchars($user_info['username']) . '</strong><br>';
                                                            echo '<small class="text-muted">' . htmlspecialchars($user_info['email']) . '</small>';
                                                        } else {
                                                            echo '<span class="text-muted">Kullanıcı bulunamadı</span>';
                                                        }
                                                    } catch (Exception $e) {
                                                        echo '<span class="text-muted">ID: ' . $transaction['user_id'] . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($transaction['action_type']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($transaction['reference_type']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($transaction['description']); ?>
                                                </td>
                                                <td>
                                                    <?php if ($transaction['type'] === 'credit'): ?>
                                                        <span class="<?php echo $transaction['transaction_amount'] > 0 ? 'amount-positive' : 'amount-negative'; ?>">
                                                            <?php echo $transaction['transaction_amount'] > 0 ? '+' : ''; ?>
                                                            <?php echo number_format($transaction['transaction_amount'], 2); ?> ₺
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = 'secondary';
                                                    $statusText = $transaction['action_type'];
                                                    
                                                    switch ($transaction['action_type']) {
                                                        case 'credit_purchase':
                                                            $statusClass = 'success';
                                                            $statusText = 'Kredi Alındı';
                                                            break;
                                                        case 'file_charge':
                                                            $statusClass = 'warning';
                                                            $statusText = 'Ücret Kesildi';
                                                            break;
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
                                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Transaction pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php 
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($date_filter); ?>">İlk</a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($date_filter); ?>">Önceki</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo (int)$i; ?>&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($date_filter); ?>">
                                                    <?php echo (int)$i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($date_filter); ?>">Sonraki</a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($date_filter); ?>">Son</a>
                                            </li>
                                        <?php endif; ?>
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
        function filterTransactions(filter, date) {
            let url = '?filter=' + encodeURIComponent(filter);
            if (date) {
                url += '&date=' + encodeURIComponent(date);
            }
            window.location.href = url;
        }

        function exportTransactions() {
            // Excel export işlemi
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.set('export', 'excel');
            
            const exportUrl = 'export-transactions.php?' + currentParams.toString();
            window.open(exportUrl, '_blank');
        }

        // Auto refresh every 2 minutes
        setInterval(function() {
            location.reload();
        }, 120000);
    </script>
</body>
</html>
