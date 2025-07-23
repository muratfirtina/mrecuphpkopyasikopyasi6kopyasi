<?php
/**
 * Mr ECU - Modern Kullanıcı İşlem Geçmişi Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/transactions.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
$userId = $_SESSION['user_id'];

// Filtreleme parametreleri
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;

// Kredi işlemlerini getir
try {
    $offset = ($page - 1) * $limit;
    
    // LIMIT ve OFFSET değerlerini integer'a çevir
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    $whereClause = 'WHERE ct.user_id = ?';
    $params = [$userId];
    
    if ($type) {
        $whereClause .= ' AND COALESCE(ct.transaction_type, ct.type) = ?';
        $params[] = $type;
    }
    
    if ($dateFrom) {
        $whereClause .= ' AND DATE(ct.created_at) >= ?';
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereClause .= ' AND DATE(ct.created_at) <= ?';
        $params[] = $dateTo;
    }
    
    $stmt = $pdo->prepare("
        SELECT ct.*, u.username as admin_username,
               COALESCE(ct.transaction_type, ct.type) as effective_type
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        {$whereClause}
        ORDER BY ct.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    
    $stmt->execute($params); // Sadece WHERE clause parametreleri
    $creditTransactions = $stmt->fetchAll();
    
    // Toplam sayı
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM credit_transactions ct
        {$whereClause}
    ");
    $countStmt->execute($params); // Sadece WHERE clause parametreleri
    $totalTransactions = $countStmt->fetchColumn();
    $totalPages = ceil($totalTransactions / $limit);
    
} catch(PDOException $e) {
    $creditTransactions = [];
    $totalTransactions = 0;
    $totalPages = 0;
}

// Son dosya işlemleri
try {
    $stmt = $pdo->prepare("
        SELECT fu.id, fu.user_id, 
               COALESCE(fu.original_name, fu.filename, fu.name) as file_name,
               fu.file_size, fu.status, fu.upload_date,
               b.name as brand_name, 
               m.name as model_name
        FROM file_uploads fu
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.user_id = ?
        ORDER BY fu.upload_date DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentFiles = $stmt->fetchAll();
} catch(PDOException $e) {
    $recentFiles = [];
}

// İstatistikler
try {
    // Toplam kredi işlemleri
    $stmt = $pdo->prepare("SELECT COALESCE(transaction_type, type) as effective_type, COUNT(*) as count, SUM(amount) as total FROM credit_transactions WHERE user_id = ? GROUP BY COALESCE(transaction_type, type)");
    $stmt->execute([$userId]);
    $transactionStats = [];
    while ($row = $stmt->fetch()) {
        $transactionStats[$row['effective_type']] = $row;
    }
    
    // Bu ay ki işlemler  
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute([$userId]);
    $monthlyTransactions = $stmt->fetchColumn();
    
    // Toplam harcama
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM credit_transactions WHERE user_id = ? AND COALESCE(transaction_type, type) IN ('deduct', 'purchase', 'withdraw', 'file_charge')");
    $stmt->execute([$userId]);
    $totalSpent = $stmt->fetchColumn() ?: 0;
    
    // Toplam yükleme
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM credit_transactions WHERE user_id = ? AND COALESCE(transaction_type, type) IN ('add', 'deposit')");
    $stmt->execute([$userId]);
    $totalLoaded = $stmt->fetchColumn() ?: 0;
    
} catch(PDOException $e) {
    $transactionStats = [];
    $monthlyTransactions = 0;
    $totalSpent = 0;
    $totalLoaded = 0;
}

$pageTitle = 'İşlem Geçmişi';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-history me-2 text-info"></i>İşlem Geçmişi
                    </h1>
                    <p class="text-muted mb-0">Kredi işlemlerinizi ve hesap aktivitelerinizi takip edin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" onclick="exportTransactions()">
                            <i class="fas fa-download me-1"></i>Dışa Aktar
                        </button>
                    </div>
                </div>
            </div>

            <!-- İstatistik Kartları -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card transactions">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-success"><?php echo number_format($totalLoaded, 2); ?></div>
                                    <div class="stat-label">Toplam Yüklenen</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-arrow-up text-success"></i>
                                        <span class="text-success">Kredi TL</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card transactions">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-danger"><?php echo number_format($totalSpent, 2); ?></div>
                                    <div class="stat-label">Toplam Harcanan</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-arrow-down text-danger"></i>
                                        <span class="text-danger">Kredi TL</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-danger">
                                    <i class="fas fa-minus-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card transactions">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-primary"><?php echo $totalTransactions; ?></div>
                                    <div class="stat-label">Toplam İşlem</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-chart-line text-primary"></i>
                                        <span class="text-primary">Tüm zamanlar</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card transactions">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-warning"><?php echo $monthlyTransactions; ?></div>
                                    <div class="stat-label">Bu Ay İşlem</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-calendar text-warning"></i>
                                        <span class="text-warning">Aylık aktivite</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtre ve Arama -->
            <div class="filter-card mb-4">
                <div class="filter-header">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filtrele ve Ara
                    </h6>
                </div>
                <div class="filter-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="type" class="form-label">
                                <i class="fas fa-tag me-1"></i>İşlem Tipi
                            </label>
                            <select class="form-select form-control-modern" id="type" name="type">
                                <option value="">Tüm İşlemler</option>
                                <option value="add" <?php echo $type === 'add' ? 'selected' : ''; ?>>Kredi Yükleme (Add)</option>
                                <option value="deduct" <?php echo $type === 'deduct' ? 'selected' : ''; ?>>Kredi Kullanımı (Deduct)</option>
                                <option value="withdraw" <?php echo $type === 'withdraw' ? 'selected' : ''; ?>>Kredi Kullanımı (Withdraw)</option>
                                <option value="file_charge" <?php echo $type === 'file_charge' ? 'selected' : ''; ?>>Dosya Ücreti</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Başlangıç Tarihi
                            </label>
                            <input type="date" class="form-control form-control-modern" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">
                                <i class="fas fa-calendar-check me-1"></i>Bitiş Tarihi
                            </label>
                            <input type="date" class="form-control form-control-modern" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="fas fa-search me-1"></i>Filtrele
                                </button>
                                <a href="transactions.php" class="btn btn-outline-secondary btn-modern">
                                    <i class="fas fa-undo me-1"></i>Temizle
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-4">
                <!-- İşlem Listesi -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2 text-primary"></i>Kredi İşlemleri
                                <?php if ($totalTransactions > 0): ?>
                                    <span class="badge bg-primary ms-2"><?php echo $totalTransactions; ?></span>
                                <?php endif; ?>
                            </h5>
                            <div class="transaction-tabs">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?type=" class="btn <?php echo empty($type) ? 'btn-primary' : 'btn-outline-primary'; ?>">Tümü</a>
                                    <a href="?type=add" class="btn <?php echo $type === 'add' ? 'btn-success' : 'btn-outline-success'; ?>">Yükleme</a>
                                    <a href="?type=deduct" class="btn <?php echo $type === 'deduct' ? 'btn-danger' : 'btn-outline-danger'; ?>">Kullanım</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <?php if (empty($creditTransactions)): ?>
                                <div class="empty-state-transactions">
                                    <div class="empty-content">
                                        <div class="empty-icon">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <h6>
                                            <?php if ($type || $dateFrom || $dateTo): ?>
                                                Filtreye uygun işlem bulunamadı
                                            <?php else: ?>
                                                Henüz işlem yapılmamış
                                            <?php endif; ?>
                                        </h6>
                                        <p class="text-muted mb-3">
                                            <?php if ($type || $dateFrom || $dateTo): ?>
                                                Farklı filtre kriterleri deneyebilirsiniz.
                                            <?php else: ?>
                                                İlk kredi yüklemenizi yapmak için butona tıklayın.
                                            <?php endif; ?>
                                        </p>
                                        <div class="empty-actions">
                                            <?php if ($type || $dateFrom || $dateTo): ?>
                                                <a href="transactions.php" class="btn btn-outline-primary">
                                                    <i class="fas fa-list me-1"></i>Tüm İşlemler
                                                </a>
                                            <?php endif; ?>
                                            <a href="credits.php" class="btn btn-primary">
                                                <i class="fas fa-credit-card me-1"></i>Kredi Yükle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- İşlem Timeline -->
                                <div class="transaction-timeline">
                                    <?php foreach ($creditTransactions as $transaction): ?>
                                        <div class="transaction-item">
                                            <div class="transaction-icon">
                                                <?php
                                                $iconClass = 'fas fa-circle';
                                                $iconColor = 'secondary';
                                                $amountClass = 'secondary';
                                                $amountPrefix = '';
                                                
                                                $effectiveType = $transaction['effective_type'] ?? $transaction['transaction_type'] ?? $transaction['type'] ?? 'unknown';
                                                switch ($effectiveType) {
                                                    case 'add':
                                                    case 'deposit':
                                                        $iconClass = 'fas fa-plus-circle';
                                                        $iconColor = 'success';
                                                        $amountClass = 'success';
                                                        $amountPrefix = '+';
                                                        break;
                                                    case 'deduct':
                                                    case 'withdraw':
                                                    case 'file_charge':
                                                    case 'purchase':
                                                        $iconClass = 'fas fa-minus-circle';
                                                        $iconColor = 'danger';
                                                        $amountClass = 'danger';
                                                        $amountPrefix = '-';
                                                        break;
                                                    default:
                                                        $iconClass = 'fas fa-circle';
                                                        $iconColor = 'secondary';
                                                        $amountClass = 'secondary';
                                                        $amountPrefix = '';
                                                        break;
                                                }
                                                ?>
                                                <i class="<?php echo $iconClass; ?> text-<?php echo $iconColor; ?>"></i>
                                            </div>
                                            
                                            <div class="transaction-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="transaction-details">
                                                        <h6 class="transaction-title">
                                                            <?php
                                                            $title = 'Bilinmeyen İşlem';
                                                            $effectiveType = $transaction['effective_type'] ?? $transaction['transaction_type'] ?? $transaction['type'] ?? 'unknown';
                                                            switch ($effectiveType) {
                                                                case 'add':
                                                                case 'deposit':
                                                                    $title = 'Kredi Yükleme';
                                                                    break;
                                                                case 'deduct':
                                                                case 'withdraw':
                                                                case 'file_charge':
                                                                case 'purchase':
                                                                    $title = 'Kredi Kullanımı';
                                                                    break;
                                                            }
                                                            echo $title;
                                                            ?>
                                                        </h6>
                                                        
                                                        <?php if ($transaction['description']): ?>
                                                            <p class="transaction-description">
                                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        
                                                        <div class="transaction-meta">
                                                            <span class="meta-item">
                                                                <i class="fas fa-calendar-alt me-1"></i>
                                                                <?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?>
                                                            </span>
                                                            
                                                            <?php if ($transaction['admin_username']): ?>
                                                                <span class="meta-item">
                                                                    <i class="fas fa-user-cog me-1"></i>
                                                                    <?php echo htmlspecialchars($transaction['admin_username']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="transaction-amount">
                                                        <span class="amount text-<?php echo $amountClass; ?>">
                                                            <?php echo $amountPrefix; ?><?php echo number_format($transaction['amount'], 2); ?> TL
                                                        </span>
                                                        <span class="badge bg-<?php echo $iconColor; ?> badge-sm">
                                                            <?php 
                                                            $effectiveType = $transaction['effective_type'] ?? $transaction['transaction_type'] ?? $transaction['type'] ?? 'unknown';
                                                            echo in_array($effectiveType, ['add', 'deposit']) ? 'Yüklendi' : 'Kullanıldı'; 
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="card-footer bg-white border-0">
                                        <nav aria-label="İşlem sayfalama">
                                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                                <!-- Önceki sayfa -->
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <!-- Sayfa numaraları -->
                                                <?php 
                                                $start = max(1, $page - 2);
                                                $end = min($totalPages, $page + 2);
                                                ?>
                                                
                                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <!-- Sonraki sayfa -->
                                                <?php if ($page < $totalPages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                        
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> 
                                                (Toplam <?php echo $totalTransactions; ?> işlem)
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Yan Panel -->
                <div class="col-lg-4">
                    <!-- Hızlı İşlemler -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0">
                                <i class="fas fa-bolt me-2 text-warning"></i>Hızlı İşlemler
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="credits.php" class="btn btn-primary btn-modern">
                                    <i class="fas fa-credit-card me-2"></i>Kredi Yükle
                                </a>
                                
                                <a href="files.php" class="btn btn-outline-primary btn-modern">
                                    <i class="fas fa-folder me-2"></i>Dosyalarım
                                </a>
                                
                                <button type="button" class="btn btn-outline-secondary btn-modern" onclick="exportTransactions()">
                                    <i class="fas fa-download me-2"></i>Excel İndir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Son Dosya İşlemleri -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0">
                                <i class="fas fa-file me-2 text-secondary"></i>Son Dosya İşlemleri
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentFiles)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Henüz dosya işlemi yok</p>
                                </div>
                            <?php else: ?>
                                <div class="recent-files-list">
                                    <?php foreach (array_slice($recentFiles, 0, 5) as $file): ?>
                                        <div class="recent-file-item">
                                            <div class="file-icon">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <div class="file-details">
                                                <div class="file-name" title="<?php echo htmlspecialchars($file['file_name'] ?? 'Bilinmeyen dosya'); ?>">
                                                    <?php 
                                                    $fileName = $file['file_name'] ?? 'Bilinmeyen dosya';
                                                    echo htmlspecialchars(strlen($fileName) > 20 ? substr($fileName, 0, 20) . '...' : $fileName); 
                                                    ?>
                                                </div>
                                                <div class="file-meta">
                                                    <span><?php echo htmlspecialchars($file['brand_name'] ?? 'Bilinmeyen marka'); ?></span>
                                                    <span><?php echo isset($file['upload_date']) ? date('d.m.Y', strtotime($file['upload_date'])) : 'Tarih yok'; ?></span>
                                                </div>
                                            </div>
                                            <div class="file-status">
                                                <?php
                                                $statusConfig = [
                                                    'pending' => ['class' => 'warning', 'icon' => 'clock'],
                                                    'processing' => ['class' => 'info', 'icon' => 'cogs'],
                                                    'completed' => ['class' => 'success', 'icon' => 'check'],
                                                    'rejected' => ['class' => 'danger', 'icon' => 'times']
                                                ];
                                                $config = $statusConfig[$file['status'] ?? 'unknown'] ?? ['class' => 'secondary', 'icon' => 'question'];
                                                ?>
                                                <i class="fas fa-<?php echo $config['icon']; ?> text-<?php echo $config['class']; ?>"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="files.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-list me-1"></i>Tüm Dosyalar
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Modern Transactions Page Styles */
.stat-card.transactions {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.stat-card.transactions:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

/* Empty State */
.empty-state-transactions {
    padding: 3rem 2rem;
    text-align: center;
}

.empty-state-transactions .empty-icon {
    font-size: 4rem;
    color: #e9ecef;
    margin-bottom: 1.5rem;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Transaction Timeline */
.transaction-timeline {
    padding: 1.5rem;
}

.transaction-item {
    display: flex;
    margin-bottom: 1.5rem;
    position: relative;
}

.transaction-item:last-child {
    margin-bottom: 0;
}

.transaction-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 12px;
    top: 40px;
    bottom: -24px;
    width: 2px;
    background: #e9ecef;
}

.transaction-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    margin-top: 0.25rem;
    position: relative;
    z-index: 1;
    background: white;
}

.transaction-icon i {
    font-size: 1.25rem;
}

.transaction-content {
    flex: 1;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid #e9ecef;
}

.transaction-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.transaction-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.transaction-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.meta-item {
    color: #9ca3af;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}

.meta-item i {
    color: #9ca3af;
}

.transaction-amount {
    text-align: right;
}

.amount {
    font-size: 1.25rem;
    font-weight: 700;
    display: block;
    margin-bottom: 0.25rem;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Transaction Tabs */
.transaction-tabs .btn-group .btn {
    border-radius: 6px;
    margin: 0 2px;
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
}

/* Recent Files */
.recent-files-list {
    max-height: 300px;
    overflow-y: auto;
}

.recent-file-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.recent-file-item:last-child {
    border-bottom: none;
}

.recent-file-item .file-icon {
    width: 32px;
    height: 32px;
    background: #e9ecef;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    color: #6c757d;
}

.file-details {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-meta {
    display: flex;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #9ca3af;
}

.file-status {
    margin-left: 0.5rem;
}

/* Responsive */
@media (max-width: 767.98px) {
    .transaction-timeline {
        padding: 1rem;
    }
    
    .transaction-item {
        margin-bottom: 1rem;
    }
    
    .transaction-content {
        padding: 1rem;
    }
    
    .transaction-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .transaction-amount {
        text-align: left;
        margin-top: 0.5rem;
    }
    
    .amount {
        font-size: 1.1rem;
    }
    
    .empty-state-transactions {
        padding: 2rem 1rem;
    }
    
    .empty-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
// Export Transactions to Excel
function exportTransactions() {
    const transactions = <?php echo json_encode($creditTransactions); ?>;
    
    if (transactions.length === 0) {
        alert('Dışa aktarılacak işlem bulunamadı.');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Tarih,İşlem Tipi,Miktar,Açıklama,Admin\n";

    transactions.forEach(function(transaction) {
        const transactionType = transaction.effective_type || transaction.transaction_type || transaction.type || 'unknown';
        const row = [
            transaction.created_at,
            (transactionType === 'add' || transactionType === 'deposit') ? 'Kredi Yükleme' : 'Kredi Kullanımı',
            transaction.amount,
            transaction.description || '',
            transaction.admin_username || ''
        ].map(field => `"${field.toString().replace(/"/g, '""')}"`).join(",");
        csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "islem_gecmisi_" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Smooth scrolling for anchors
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>