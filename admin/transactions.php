<?php
/**
 * Mr ECU - Admin İşlem Geçmişi (Düzeltilmiş Versiyon)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
$user = new User($pdo);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$filter = sanitize($_GET['filter'] ?? 'all');
$dateFilter = sanitize($_GET['date'] ?? '');
$search = sanitize($_GET['search'] ?? '');

try {
    // Temel sorgu
    $whereClause = "WHERE ct.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
    $params = [];
    
    if ($filter !== 'all') {
        $whereClause .= " AND ct.type = ?";
        $params[] = $filter;
    }
    
    if ($dateFilter) {
        switch ($dateFilter) {
            case 'today':
                $whereClause .= " AND DATE(ct.created_at) = CURDATE()";
                break;
            case 'week':
                $whereClause .= " AND ct.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $whereClause .= " AND ct.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }
    
    if ($search) {
        $whereClause .= " AND (u.username LIKE ? OR u.email LIKE ? OR ct.description LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }
    
    // credit_transactions tablosu var mı kontrol et
    $table_check = $pdo->query("SHOW TABLES LIKE 'credit_transactions'");
    $transactions = [];
    $totalTransactions = 0;
    
    if ($table_check->fetch()) {
        // Toplam transaction sayısı
        $countQuery = "
            SELECT COUNT(*) 
            FROM credit_transactions ct
            LEFT JOIN users u ON ct.user_id = u.id
            $whereClause
        ";
        $stmt = $pdo->prepare($countQuery);
        $stmt->execute($params);
        $totalTransactions = $stmt->fetchColumn();
        
        // Transactions getir
        $query = "
            SELECT ct.*, u.username, u.email, u.first_name, u.last_name,
                   admin.username as admin_username
            FROM credit_transactions ct
            LEFT JOIN users u ON ct.user_id = u.id
            LEFT JOIN users admin ON ct.admin_id = admin.id
            $whereClause 
            ORDER BY ct.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $totalPages = ceil($totalTransactions / $limit);
    
    // İstatistikler
    $stats = ['total_credits' => 0, 'total_debits' => 0, 'today_transactions' => 0];
    
    if ($table_check->rowCount() > 0) {
        $stmt = $pdo->query("
            SELECT 
                SUM(CASE WHEN type IN ('deposit', 'refund') THEN amount ELSE 0 END) as total_credits,
                SUM(CASE WHEN type IN ('withdraw', 'file_charge') THEN amount ELSE 0 END) as total_debits,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_transactions
            FROM credit_transactions
        ");
        $stats = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    $transactions = [];
    $totalTransactions = 0;
    $totalPages = 0;
    $stats = ['total_credits' => 0, 'total_debits' => 0, 'today_transactions' => 0];
    error_log('Transactions error: ' . $e->getMessage());
}

$pageTitle = 'İşlem Geçmişi';
$pageDescription = 'Kredi işlemlerini görüntüleyin ve yönetin';
$pageIcon = 'fas fa-history';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- İstatistik Kartları -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format($stats['total_credits'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">Toplam Kredi</div>
                    <small class="text-muted">Yüklenen toplam kredi</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="fas fa-plus text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-danger"><?php echo number_format($stats['total_debits'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">Toplam Düşürülen</div>
                    <small class="text-muted">Kullanılan/düşürülen kredi</small>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded">
                    <i class="fas fa-minus text-danger fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format($totalTransactions); ?></div>
                    <div class="stat-label">Toplam İşlem</div>
                    <small class="text-muted">Son 90 gün</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-exchange-alt text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo number_format($stats['today_transactions'] ?? 0); ?></div>
                    <div class="stat-label">Bugünkü İşlemler</div>
                    <small class="text-muted">Günlük aktivite</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="fas fa-clock text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtre Kartı -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="filter" class="form-label">İşlem Tipi</label>
                <select class="form-select" id="filter" name="filter">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tümü</option>
                    <option value="deposit" <?php echo $filter === 'deposit' ? 'selected' : ''; ?>>Kredi Yükleme</option>
                    <option value="withdraw" <?php echo $filter === 'withdraw' ? 'selected' : ''; ?>>Kredi Düşme</option>
                    <option value="file_charge" <?php echo $filter === 'file_charge' ? 'selected' : ''; ?>>Dosya Ücreti</option>
                    <option value="refund" <?php echo $filter === 'refund' ? 'selected' : ''; ?>>İade</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date" class="form-label">Tarih</label>
                <select class="form-select" id="date" name="date">
                    <option value="" <?php echo $dateFilter === '' ? 'selected' : ''; ?>>Son 90 Gün</option>
                    <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Bugün</option>
                    <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>Son 7 Gün</option>
                    <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>Son 30 Gün</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Arama</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Kullanıcı adı, e-posta veya açıklama...">
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Filtrele
                </button>
            </div>
            
            <div class="col-md-2">
                <a href="transactions.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-undo me-1"></i>Temizle
                </a>
            </div>
            
            <div class="col-md-1">
                <button type="button" class="btn btn-success w-100" onclick="exportTransactions()">
                    <i class="fas fa-download"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- İşlem Listesi -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Kredi İşlemleri (<?php echo number_format($totalTransactions); ?> adet)
        </h5>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($search || $filter !== 'all' || $dateFilter): ?>
                        Filtreye uygun işlem bulunamadı
                    <?php else: ?>
                        Henüz kredi işlemi yok
                    <?php endif; ?>
                </h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kullanıcı</th>
                            <th>İşlem Tipi</th>
                            <th>Miktar</th>
                            <th>Açıklama</th>
                            <th>Admin</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong>
                                            <a href="user-details.php?id=<?php echo $transaction['user_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                            </a>
                                        </strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($transaction['username']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $typeClass = [
                                        'deposit' => 'success',
                                        'refund' => 'info',
                                        'withdraw' => 'warning',
                                        'file_charge' => 'danger'
                                    ];
                                    $typeText = [
                                        'deposit' => 'Kredi Yükleme',
                                        'refund' => 'İade',
                                        'withdraw' => 'Kredi Düşme',
                                        'file_charge' => 'Dosya Ücreti'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $typeClass[$transaction['type']] ?? 'secondary'; ?>">
                                        <?php echo $typeText[$transaction['type']] ?? $transaction['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-<?php echo in_array($transaction['type'], ['deposit', 'refund']) ? 'success' : 'danger'; ?>">
                                        <?php echo in_array($transaction['type'], ['deposit', 'refund']) ? '+' : '-'; ?>
                                        <?php echo number_format($transaction['amount'], 2); ?> TL
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width: 250px;">
                                        <?php echo htmlspecialchars($transaction['description'] ?? 'Açıklama yok'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($transaction['admin_username']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($transaction['admin_username']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Sistem</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo date('d.m.Y', strtotime($transaction['created_at'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Sayfalama -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Sayfalama">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Sayfa özel JavaScript
$pageJS = "
function exportTransactions() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('?' + params.toString(), '_blank');
}
";

// Footer include
include '../includes/admin_footer.php';
?>
