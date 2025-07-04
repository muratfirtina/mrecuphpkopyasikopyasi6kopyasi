<?php
/**
 * Mr ECU - Kullanıcı İşlem Geçmişi
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

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';

// Kredi işlemlerini getir
try {
    $offset = ($page - 1) * $limit;
    
    $whereClause = 'WHERE ct.user_id = ?';
    $params = [$userId];
    
    if ($type) {
        $whereClause .= ' AND ct.type = ?';
        $params[] = $type;
    }
    
    $stmt = $pdo->prepare("
        SELECT ct.*, u.username as admin_username 
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        {$whereClause}
        ORDER BY ct.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt->execute($params);
    $creditTransactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $creditTransactions = [];
}

// Sistem loglarını getir
try {
    $stmt = $pdo->prepare("
        SELECT * FROM system_logs 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    $systemLogs = $stmt->fetchAll();
} catch(PDOException $e) {
    $systemLogs = [];
}

// Dosya işlemlerini getir
$userFiles = $fileManager->getUserUploads($userId, 1, 20);

// İstatistikler
$totalTransactions = count($creditTransactions);
$totalLogs = count($systemLogs);
$totalFiles = count($userFiles);

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
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-history me-2"></i>İşlem Geçmişi
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTransactions()">
                                <i class="fas fa-download me-1"></i>Dışa Aktar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="dashboard-card primary">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo $totalTransactions; ?></h4>
                                    <p class="text-muted mb-0">Kredi İşlemi</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-coins text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="dashboard-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo $totalFiles; ?></h4>
                                    <p class="text-muted mb-0">Dosya İşlemi</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="dashboard-card warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo $totalLogs; ?></h4>
                                    <p class="text-muted mb-0">Sistem Aktivitesi</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="credits-tab" data-bs-toggle="tab" data-bs-target="#credits" type="button" role="tab">
                            <i class="fas fa-coins me-2"></i>Kredi İşlemleri
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">
                            <i class="fas fa-file me-2"></i>Dosya İşlemleri
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" type="button" role="tab">
                            <i class="fas fa-activity me-2"></i>Sistem Aktiviteleri
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="transactionTabsContent">
                    <!-- Kredi İşlemleri Tab -->
                    <div class="tab-pane fade show active" id="credits" role="tabpanel">
                        <div class="card border-0">
                            <div class="card-header bg-transparent">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-0">Kredi İşlemleri</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="btn-group btn-group-sm float-end">
                                            <a href="?type=" class="btn btn-outline-secondary <?php echo empty($type) ? 'active' : ''; ?>">Tümü</a>
                                            <a href="?type=deposit" class="btn btn-outline-success <?php echo $type === 'deposit' ? 'active' : ''; ?>">Yükleme</a>
                                            <a href="?type=withdraw" class="btn btn-outline-danger <?php echo $type === 'withdraw' ? 'active' : ''; ?>">Çekme</a>
                                            <a href="?type=file_charge" class="btn btn-outline-warning <?php echo $type === 'file_charge' ? 'active' : ''; ?>">Dosya</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($creditTransactions)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-receipt text-muted" style="font-size: 4rem;"></i>
                                        <h4 class="mt-3 text-muted">İşlem bulunamadı</h4>
                                        <p class="text-muted">
                                            <?php if ($type): ?>
                                                Bu kategoride işlem bulunmuyor.
                                            <?php else: ?>
                                                Henüz kredi işlemi yapmadınız.
                                            <?php endif; ?>
                                        </p>
                                        <a href="credits.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Kredi Satın Al
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>İşlem Tipi</th>
                                                    <th>Miktar</th>
                                                    <th>Açıklama</th>
                                                    <th>Durum</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($creditTransactions as $transaction): ?>
                                                    <tr>
                                                        <td>
                                                            <div><?php echo formatDate($transaction['created_at']); ?></div>
                                                            <small class="text-muted"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $iconClass = '';
                                                            $badgeClass = '';
                                                            $typeText = '';
                                                            
                                                            switch ($transaction['type']) {
                                                                case 'deposit':
                                                                    $iconClass = 'fas fa-plus-circle';
                                                                    $badgeClass = 'bg-success';
                                                                    $typeText = 'Kredi Yükleme';
                                                                    break;
                                                                case 'withdraw':
                                                                    $iconClass = 'fas fa-minus-circle';
                                                                    $badgeClass = 'bg-danger';
                                                                    $typeText = 'Kredi Çekme';
                                                                    break;
                                                                case 'file_charge':
                                                                    $iconClass = 'fas fa-download';
                                                                    $badgeClass = 'bg-warning';
                                                                    $typeText = 'Dosya İndirme';
                                                                    break;
                                                                case 'refund':
                                                                    $iconClass = 'fas fa-undo';
                                                                    $badgeClass = 'bg-info';
                                                                    $typeText = 'İade';
                                                                    break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $badgeClass; ?>">
                                                                <i class="<?php echo $iconClass; ?> me-1"></i><?php echo $typeText; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <strong class="<?php echo ($transaction['type'] === 'deposit' || $transaction['type'] === 'refund') ? 'text-success' : 'text-danger'; ?>">
                                                                <?php if ($transaction['type'] === 'deposit' || $transaction['type'] === 'refund'): ?>
                                                                    +<?php echo number_format($transaction['amount'], 2); ?>
                                                                <?php else: ?>
                                                                    -<?php echo number_format($transaction['amount'], 2); ?>
                                                                <?php endif; ?>
                                                            </strong>
                                                        </td>
                                                        <td>
                                                            <?php if ($transaction['description']): ?>
                                                                <span title="<?php echo htmlspecialchars($transaction['description']); ?>">
                                                                    <?php echo htmlspecialchars(substr($transaction['description'], 0, 50)); ?>
                                                                    <?php if (strlen($transaction['description']) > 50): ?>...<?php endif; ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">Tamamlandı</span>
                                                            <?php if ($transaction['admin_username']): ?>
                                                                <br><small class="text-muted">Admin: <?php echo htmlspecialchars($transaction['admin_username']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Sayfalama -->
                                    <?php if (count($creditTransactions) >= $limit): ?>
                                        <nav aria-label="Sayfa navigasyonu">
                                            <ul class="pagination justify-content-center">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $type ? '&type=' . $type : ''; ?>">Önceki</a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <li class="page-item active">
                                                    <span class="page-link"><?php echo $page; ?></span>
                                                </li>
                                                
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $type ? '&type=' . $type : ''; ?>">Sonraki</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Dosya İşlemleri Tab -->
                    <div class="tab-pane fade" id="files" role="tabpanel">
                        <div class="card border-0">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0">Dosya İşlemleri</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($userFiles)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-folder-open text-muted" style="font-size: 4rem;"></i>
                                        <h4 class="mt-3 text-muted">Dosya işlemi yok</h4>
                                        <p class="text-muted">Henüz dosya yüklemesi yapmadınız.</p>
                                        <a href="upload.php" class="btn btn-primary">
                                            <i class="fas fa-upload me-1"></i>Dosya Yükle
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline">
                                        <?php foreach ($userFiles as $file): ?>
                                            <div class="timeline-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="fas fa-file me-2"></i>
                                                            <?php echo htmlspecialchars($file['original_name']); ?>
                                                        </h6>
                                                        <p class="text-muted mb-1">
                                                            <strong><?php echo htmlspecialchars($file['brand_name'] . ' ' . $file['model_name']); ?></strong>
                                                            (<?php echo $file['year']; ?>) - <?php echo $file['fuel_type']; ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <?php echo formatDate($file['upload_date']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        $statusText = $file['status'];
                                                        
                                                        switch ($file['status']) {
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
                                                        <br>
                                                        <a href="files.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-primary mt-1">
                                                            <i class="fas fa-eye"></i> Detay
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sistem Aktiviteleri Tab -->
                    <div class="tab-pane fade" id="activities" role="tabpanel">
                        <div class="card border-0">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0">Sistem Aktiviteleri</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($systemLogs)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-chart-line text-muted" style="font-size: 4rem;"></i>
                                        <h4 class="mt-3 text-muted">Aktivite kaydı yok</h4>
                                        <p class="text-muted">Henüz sistem aktivitesi bulunmuyor.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Aktivite</th>
                                                    <th>Açıklama</th>
                                                    <th>IP Adresi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($systemLogs as $log): ?>
                                                    <tr>
                                                        <td>
                                                            <small><?php echo formatDate($log['created_at']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $iconClass = 'fas fa-circle';
                                                            $textClass = 'text-secondary';
                                                            
                                                            switch ($log['action']) {
                                                                case 'login':
                                                                    $iconClass = 'fas fa-sign-in-alt';
                                                                    $textClass = 'text-success';
                                                                    break;
                                                                case 'logout':
                                                                    $iconClass = 'fas fa-sign-out-alt';
                                                                    $textClass = 'text-warning';
                                                                    break;
                                                                case 'file_upload':
                                                                    $iconClass = 'fas fa-upload';
                                                                    $textClass = 'text-primary';
                                                                    break;
                                                                case 'file_download':
                                                                    $iconClass = 'fas fa-download';
                                                                    $textClass = 'text-info';
                                                                    break;
                                                                case 'profile_update':
                                                                    $iconClass = 'fas fa-user-edit';
                                                                    $textClass = 'text-secondary';
                                                                    break;
                                                                case 'password_change':
                                                                    $iconClass = 'fas fa-key';
                                                                    $textClass = 'text-warning';
                                                                    break;
                                                            }
                                                            ?>
                                                            <i class="<?php echo $iconClass; ?> <?php echo $textClass; ?> me-2"></i>
                                                            <span class="<?php echo $textClass; ?>"><?php echo ucfirst($log['action']); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($log['description']): ?>
                                                                <small><?php echo htmlspecialchars($log['description']); ?></small>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted"><?php echo $log['ip_address']; ?></small>
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
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        function exportTransactions() {
            // Export işlemi - CSV formatında
            const transactions = <?php echo json_encode($creditTransactions); ?>;
            
            if (transactions.length === 0) {
                alert('Dışa aktarılacak işlem bulunamadı.');
                return;
            }

            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Tarih,İşlem Tipi,Miktar,Açıklama,Admin\n";

            transactions.forEach(function(transaction) {
                const row = [
                    transaction.created_at,
                    transaction.type,
                    transaction.amount,
                    transaction.description || '',
                    transaction.admin_username || ''
                ].map(field => `"${field}"`).join(",");
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

        // URL'deki tab parametresine göre aktif tab'ı ayarla
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        
        if (activeTab) {
            const tabButton = document.getElementById(activeTab + '-tab');
            if (tabButton) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }
    </script>
</body>
</html>
