<?php
/**
 * Mr ECU - Admin Kullanıcı Detay Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
$user = new User($pdo);
$fileManager = new FileManager($pdo);
$error = '';
$success = '';

// Kullanıcı ID'sini al
$userId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

// Pagination parametreleri
$uploadsPage = isset($_GET['uploads_page']) ? max(1, (int)$_GET['uploads_page']) : 1;
$creditsPage = isset($_GET['credits_page']) ? max(1, (int)$_GET['credits_page']) : 1;
$perPage = 10; // Sayfa başına kayıt sayısı

if (!$userId || !isValidUUID($userId)) {
    header('Location: users.php?error=invalid_user_id');
    exit;
}

// Kullanıcı bilgilerini getir
$userDetails = $user->getUserById($userId);

if (!$userDetails) {
    header('Location: users.php?error=user_not_found');
    exit;
}

// Kullanıcı dosyalarını getir - Pagination ile
$userUploads = $fileManager->getUserUploads($userId, $uploadsPage, $perPage);

// Toplam dosya sayısını getir (pagination için)
$totalUploads = $fileManager->getUserUploadCount($userId);
$totalUploadsPages = ceil($totalUploads / $perPage);

// Kullanıcı kredi işlemlerini getir - Pagination ile
$creditsOffset = ($creditsPage - 1) * $perPage;
try {
    $stmt = $pdo->prepare("
        SELECT * FROM credit_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_STR);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $creditsOffset, PDO::PARAM_INT);
    $stmt->execute();
    $creditTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $creditTransactions = [];
}

// Toplam kredi işlemi sayısını getir (pagination için)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalCredits = $stmt->fetchColumn();
    $totalCreditsPages = ceil($totalCredits / $perPage);
} catch(PDOException $e) {
    $totalCredits = 0;
    $totalCreditsPages = 0;
}

// Kullanıcı kredi istatistiklerini getir - Detaylı analiz
try {
    // Toplam kredi istatistikleri
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN transaction_type = 'add' AND type IN ('quota_add', 'quota_reset', 'quota_set') THEN amount ELSE 0 END) as total_quota_given,
            SUM(CASE WHEN transaction_type = 'withdraw' AND type = 'refund' THEN amount ELSE 0 END) as total_refunds,
            SUM(CASE WHEN transaction_type = 'withdraw' AND type IN ('manual', 'file_charge') THEN amount ELSE 0 END) as total_spent,
            SUM(CASE WHEN transaction_type = 'file_charge' THEN amount ELSE 0 END) as total_file_charges,
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN transaction_type = 'add' AND type IN ('quota_add', 'quota_reset', 'quota_set') THEN 1 END) as quota_transactions,
            COUNT(CASE WHEN transaction_type = 'withdraw' AND type = 'refund' THEN 1 END) as refund_transactions,
            MAX(CASE WHEN transaction_type = 'add' AND type IN ('quota_add', 'quota_reset', 'quota_set') THEN amount END) as highest_quota_transaction,
            MIN(created_at) as first_transaction_date,
            MAX(created_at) as last_transaction_date
        FROM credit_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $creditStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // İşlem türlerine göre dağılım
    $stmt = $pdo->prepare("
        SELECT 
            type,
            transaction_type,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount,
            MAX(amount) as max_amount,
            MIN(amount) as min_amount
        FROM credit_transactions 
        WHERE user_id = ?
        GROUP BY type, transaction_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$userId]);
    $creditBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Aylık kredi hareketleri (Son 12 ay)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month_year,
            SUM(CASE WHEN transaction_type = 'add' AND type IN ('quota_add', 'quota_reset', 'quota_set') THEN amount ELSE 0 END) as quota_added,
            SUM(CASE WHEN transaction_type = 'withdraw' THEN amount ELSE 0 END) as amount_spent,
            COUNT(*) as transaction_count
        FROM credit_transactions 
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month_year DESC
        LIMIT 12
    ");
    $stmt->execute([$userId]);
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $creditStats = [
        'total_quota_given' => 0,
        'total_refunds' => 0,
        'total_spent' => 0,
        'total_file_charges' => 0,
        'total_transactions' => 0,
        'quota_transactions' => 0,
        'refund_transactions' => 0,
        'highest_quota_transaction' => 0,
        'first_transaction_date' => null,
        'last_transaction_date' => null
    ];
    $creditBreakdown = [];
    $monthlyStats = [];
}

// Kullanıcı dosya istatistikleri
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_uploads,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_uploads,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_uploads,
            SUM(file_size) as total_file_size
        FROM file_uploads 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $userStats = [
        'total_uploads' => 0,
        'completed_uploads' => 0,
        'pending_uploads' => 0,
        'total_file_size' => 0
    ];
}

// Kredi ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credit'])) {
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description']);
    
    if ($amount <= 0) {
        $error = 'Kredi miktarı 0\'dan büyük olmalıdır.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Kullanıcının kredisini güncelle
            $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // İşlem kaydı oluştur
            $transactionId = generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (id, user_id, admin_id, type, amount, description, created_at) 
                VALUES (?, ?, ?, 'deposit', ?, ?, NOW())
            ");
            $stmt->execute([$transactionId, $userId, $_SESSION['user_id'], $amount, $description]);
            
            $pdo->commit();
            $success = number_format($amount, 2) . ' TL kredi başarıyla eklendi.';
            
            // Sayfayı yenile
            header("Location: user-details.php?id=$userId&success=" . urlencode($success));
            exit;
            
        } catch(PDOException $e) {
            $pdo->rollback();
            $error = 'Kredi ekleme sırasında hata oluştu.';
        }
    }
}

// Kullanıcı durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = sanitize($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $userId]);
        
        if ($result) {
            $success = 'Kullanıcı durumu güncellendi.';
            // Kullanıcı bilgilerini yenile
            $userDetails = $user->getUserById($userId);
        }
    } catch(PDOException $e) {
        $error = 'Durum güncelleme sırasında hata oluştu.';
    }
}

$pageTitle = 'Kullanıcı Detayları - ' . $userDetails['first_name'] . ' ' . $userDetails['last_name'];
$pageDescription = 'Kullanıcı detay bilgileri ve işlemleri';
$pageIcon = 'fas fa-user';

// Hızlı eylemler
$quickActions = [
    [
        'text' => 'Tüm Kullanıcılar',
        'url' => 'users.php',
        'icon' => 'fas fa-users',
        'class' => 'secondary'
    ],
    [
        'text' => 'Kredi Ekle',
        'url' => '#',
        'icon' => 'fas fa-plus',
        'class' => 'success',
        'onclick' => "document.getElementById('addCreditModal').style.display='block'"
    ]
];

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Başarı/Hata Mesajları -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($_GET['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Kullanıcı Bilgileri -->
<div class="row">
    <div class="col-lg-4">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>Kullanıcı Bilgileri
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-user fa-2x text-primary"></i>
                    </div>
                    <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($userDetails['first_name'] . ' ' . $userDetails['last_name']); ?></h4>
                    <span class="badge bg-<?php echo $userDetails['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo $userDetails['role'] === 'admin' ? 'Admin' : 'Kullanıcı'; ?>
                    </span>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Kullanıcı Adı:</strong></div>
                    <div class="col-sm-6"><?php echo htmlspecialchars($userDetails['username']); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>E-posta:</strong></div>
                    <div class="col-sm-6"><?php echo htmlspecialchars($userDetails['email']); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Telefon:</strong></div>
                    <div class="col-sm-6"><?php echo htmlspecialchars($userDetails['phone'] ?? 'Belirtilmemiş'); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Tanımlı Kota:</strong></div>
                    <div class="col-sm-6">
                        <span class="badge bg-primary">
                            <?php echo number_format($userDetails['credit_quota'] ?? 0, 2); ?> TL
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Mevcut Kullanım:</strong></div>
                    <div class="col-sm-6">
                        <span class="badge bg-warning">
                            <?php echo number_format($userDetails['credit_used'] ?? 0, 2); ?> TL
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Kalan Kullanım:</strong></div>
                    <div class="col-sm-6">
                        <span class="badge bg-success">
                            <?php 
                            $availableCredit = ($userDetails['credit_quota'] ?? 0) - ($userDetails['credit_used'] ?? 0);
                            echo number_format($availableCredit, 2); 
                            ?> TL
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Durum:</strong></div>
                    <div class="col-sm-6">
                        <span class="badge bg-<?php echo $userDetails['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo $userDetails['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Kayıt Tarihi:</strong></div>
                    <div class="col-sm-6"><?php echo date('d.m.Y H:i', strtotime($userDetails['created_at'])); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6"><strong>Son Giriş:</strong></div>
                    <div class="col-sm-6">
                        <?php echo $userDetails['last_login'] ? date('d.m.Y H:i', strtotime($userDetails['last_login'])) : 'Hiç giriş yapmamış'; ?>
                    </div>
                </div>
                
                <!-- Durum Güncelleme -->
                <hr>
                <h6><i class="fas fa-edit me-2"></i>Durum Güncelle</h6>
                <form method="POST" class="d-inline">
                    <div class="d-flex gap-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="active" <?php echo $userDetails['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo $userDetails['status'] === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                            <option value="banned" <?php echo $userDetails['status'] === 'banned' ? 'selected' : ''; ?>>Yasaklı</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                            <i class="fas fa-save"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- İstatistikler -->
        <div class="card admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>İstatistikler
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary"><?php echo $userStats['total_uploads']; ?></h4>
                        <small class="text-muted">Toplam Dosya</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success"><?php echo $userStats['completed_uploads']; ?></h4>
                        <small class="text-muted">Tamamlanan</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-warning"><?php echo $userStats['pending_uploads']; ?></h5>
                        <small class="text-muted">Bekleyen</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-info"><?php echo formatFileSize($userStats['total_file_size']); ?></h5>
                        <small class="text-muted">Toplam Boyut</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kredi İstatistikleri -->
        <div class="card admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Kredi İstatistikleri
                </h6>
            </div>
            <div class="card-body">
                <!-- Genel İstatistikler -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="text-center p-2 bg-success bg-opacity-10 rounded">
                            <h5 class="text-success mb-1"><?php echo number_format($creditStats['total_quota_given'] ?? 0, 2); ?> TL</h5>
                            <small class="text-muted">Toplam Verilen Kota</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 bg-danger bg-opacity-10 rounded">
                            <h5 class="text-danger mb-1"><?php echo number_format($creditStats['total_spent'] ?? 0, 2); ?> TL</h5>
                            <small class="text-muted">Toplam Harcama</small>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="text-center p-2 bg-info bg-opacity-10 rounded">
                            <h6 class="text-info mb-1"><?php echo number_format($creditStats['total_refunds'] ?? 0, 2); ?> TL</h6>
                            <small class="text-muted">Toplam İade</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 bg-warning bg-opacity-10 rounded">
                            <h6 class="text-warning mb-1"><?php echo $creditStats['quota_transactions'] ?? 0; ?></h6>
                            <small class="text-muted">Kota İşlemleri</small>
                        </div>
                    </div>
                </div>
                
                <!-- İleri İstatistikler -->
                <hr class="my-3">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <h6 class="text-primary"><?php echo number_format($creditStats['highest_quota_transaction'] ?? 0, 2); ?> TL</h6>
                        <small class="text-muted">En Yüksek Kota</small>
                    </div>
                    <div class="col-4">
                        <h6 class="text-secondary"><?php echo $creditStats['total_transactions'] ?? 0; ?></h6>
                        <small class="text-muted">Toplam İşlem</small>
                    </div>
                    <div class="col-4">
                        <h6 class="text-info"><?php echo $creditStats['refund_transactions'] ?? 0; ?></h6>
                        <small class="text-muted">İade İşlemi</small>
                    </div>
                </div>
                
                <!-- Tarih Bilgileri -->
                <?php if ($creditStats['first_transaction_date']): ?>
                <hr class="my-3">
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted">İlk İşlem:</small><br>
                        <small class="fw-bold"><?php echo date('d.m.Y', strtotime($creditStats['first_transaction_date'])); ?></small>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Son İşlem:</small><br>
                        <small class="fw-bold"><?php echo date('d.m.Y H:i', strtotime($creditStats['last_transaction_date'])); ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- İşlem Türleri Analizi -->
        <?php if (!empty($creditBreakdown)): ?>
        <div class="card admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-list me-2"></i>İşlem Türleri
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tür</th>
                                <th class="text-end">Adet</th>
                                <th class="text-end">Toplam</th>
                                <th class="text-end">Ortalama</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($creditBreakdown as $breakdown): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $breakdown['transaction_type'] === 'add' ? 'success' : 
                                            ($breakdown['type'] === 'refund' ? 'info' : 'warning'); 
                                    ?> badge-sm">
                                        <?php 
                                        $typeLabels = [
                                            'quota_add' => 'Kota Artırma',
                                            'quota_reset' => 'Kota Sıfırlama',
                                            'quota_set' => 'Kota Yenileme',
                                            'refund' => 'Kredi İadesi',
                                            'manual' => 'Manuel Kullanım',
                                            'file_charge' => 'Dosya Ücreti'
                                        ];
                                        echo $typeLabels[$breakdown['type']] ?? $breakdown['type'];
                                        ?>
                                    </span>
                                </td>
                                <td class="text-end"><?php echo $breakdown['count']; ?></td>
                                <td class="text-end"><?php echo number_format($breakdown['total_amount'], 2); ?> TL</td>
                                <td class="text-end"><?php echo number_format($breakdown['avg_amount'], 2); ?> TL</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Aylık Kredi Hareketleri -->
        <?php if (!empty($monthlyStats)): ?>
        <div class="card admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>Aylık Hareketler (Son 12 Ay)
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Ay</th>
                                <th class="text-end">Verilen Kota</th>
                                <th class="text-end">Harcama</th>
                                <th class="text-end">İşlem Sayısı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyStats as $monthly): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $monthNames = [
                                        '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                                        '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                                        '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
                                    ];
                                    $monthParts = explode('-', $monthly['month_year']);
                                    echo $monthNames[$monthParts[1]] . ' ' . $monthParts[0];
                                    ?>
                                </td>
                                <td class="text-end text-success">
                                    <?php echo $monthly['quota_added'] > 0 ? '+' . number_format($monthly['quota_added'], 2) . ' TL' : '-'; ?>
                                </td>
                                <td class="text-end text-danger">
                                    <?php echo $monthly['amount_spent'] > 0 ? '-' . number_format($monthly['amount_spent'], 2) . ' TL' : '-'; ?>
                                </td>
                                <td class="text-end"><?php echo $monthly['transaction_count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-8">
        <!-- Dosyalar -->
        <div class="card admin-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-folder me-2"></i>Son Yüklenen Dosyalar
                </h5>
                <a href="uploads.php?user_id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>Tümünü Gör
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($userUploads)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Henüz dosya yüklenmemiş</h6>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosya Adı</th>
                                    <th>Durum</th>
                                    <th>Boyut</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userUploads as $upload): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($upload['original_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $upload['status'] === 'completed' ? 'success' : 
                                                    ($upload['status'] === 'pending' ? 'warning' : 
                                                    ($upload['status'] === 'processing' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php 
                                                $statusText = [
                                                    'pending' => 'Bekliyor',
                                                    'processing' => 'İşleniyor', 
                                                    'completed' => 'Tamamlandı',
                                                    'rejected' => 'Reddedildi'
                                                ];
                                                echo $statusText[$upload['status']] ?? 'Bilinmiyor';
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatFileSize($upload['file_size']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($upload['upload_date'])); ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="uploads.php?view=<?php echo $upload['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Görüntüle
                                                </a>
                                                <?php if (!isset($upload['is_cancelled']) || !$upload['is_cancelled']): ?>
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="showCancelModal('<?php echo $upload['id']; ?>', 'upload', '<?php echo htmlspecialchars($upload['original_name'], ENT_QUOTES); ?>')" 
                                                            title="Dosyayı İptal Et">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="btn btn-sm btn-secondary disabled">
                                                        <i class="fas fa-ban"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalUploadsPages > 1): ?>
                        <div class="d-flex justify-content-center mt-3">
                            <nav>
                                <ul class="pagination pagination-sm">
                                    <!-- Önceki sayfa -->
                                    <?php if ($uploadsPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $userId; ?>&uploads_page=<?php echo $uploadsPage - 1; ?>&credits_page=<?php echo $creditsPage; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Sayfa numaraları -->
                                    <?php for ($i = 1; $i <= $totalUploadsPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $uploadsPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $userId; ?>&uploads_page=<?php echo $i; ?>&credits_page=<?php echo $creditsPage; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Sonraki sayfa -->
                                    <?php if ($uploadsPage < $totalUploadsPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $userId; ?>&uploads_page=<?php echo $uploadsPage + 1; ?>&credits_page=<?php echo $creditsPage; ?>">
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
        
        <!-- Kredi İşlemleri -->
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-coins me-2"></i>Son Kredi İşlemleri
                </h5>
                <button type="button" class="btn btn-sm btn-success" onclick="document.getElementById('addCreditModal').style.display='block'">
                    <i class="fas fa-plus me-1"></i>Kredi Ekle
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($creditTransactions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Henüz kredi işlemi yok</h6>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tip</th>
                                    <th>Miktar</th>
                                    <th>Açıklama</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($creditTransactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo in_array($transaction['type'], ['deposit', 'refund']) ? 'success' : 'warning'; 
                                            ?>">
                                                <?php 
                                                $typeText = [
                                                    'deposit' => 'Yükleme',
                                                    'withdraw' => 'Düşürme',
                                                    'file_charge' => 'Dosya Ücreti',
                                                    'refund' => 'İade'
                                                ];
                                                echo $typeText[$transaction['type']] ?? $transaction['type'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-<?php echo in_array($transaction['type'], ['deposit', 'refund']) ? 'success' : 'danger'; ?>">
                                                <?php echo in_array($transaction['type'], ['deposit', 'refund']) ? '+' : '-'; ?>
                                                <?php echo number_format($transaction['amount'], 2); ?> TL
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['description'] ?? 'Açıklama yok'); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalCreditsPages > 1): ?>
                        <div class="d-flex justify-content-center mt-3">
                            <nav>
                                <ul class="pagination pagination-sm">
                                    <!-- Önceki sayfa -->
                                    <?php if ($creditsPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $userId; ?>&uploads_page=<?php echo $uploadsPage; ?>&credits_page=<?php echo $creditsPage - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Sayfa numaraları -->
                                    <?php for ($i = 1; $i <= $totalCreditsPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $creditsPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $userId; ?>&uploads_page=<?php echo $uploadsPage; ?>&credits_page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Sonraki sayfa -->
                                    <?php if ($creditsPage < $totalCreditsPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $userId; ?>&uploads_page=<?php echo $uploadsPage; ?>&credits_page=<?php echo $creditsPage + 1; ?>">
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
    </div>
</div>

<!-- Kredi Ekleme Modal -->
<div id="addCreditModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border-radius: 10px; width: 400px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-plus me-2"></i>Kredi Ekle</h5>
            <button type="button" onclick="document.getElementById('addCreditModal').style.display='none'" class="btn-close"></button>
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label for="amount" class="form-label">Kredi Miktarı (TL)</label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Kredi ekleme sebebini yazın..."></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="add_credit" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>Kredi Ekle
                </button>
                <button type="button" onclick="document.getElementById('addCreditModal').style.display='none'" class="btn btn-secondary">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Footer include
include '../includes/admin_footer.php';
?>

<!-- Admin İptal Modal -->
<div class="modal fade" id="adminCancelModal" tabindex="-1" aria-labelledby="adminCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="adminCancelModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Dosya İptal Onayı
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form action="uploads.php" method="POST" id="adminCancelForm">
                <div class="modal-body py-4">
                    <input type="hidden" name="admin_cancel_file" value="1">
                    <input type="hidden" name="file_id" id="cancelFileId">
                    <input type="hidden" name="file_type" id="cancelFileType">
                    
                    <div class="mb-4">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: linear-gradient(135deg, #dc3545, #c82333); border-radius: 50%;">
                            <i class="fas fa-times text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-2 text-dark text-center">Bu dosyayı iptal etmek istediğinizden emin misiniz?</h6>
                        <p class="text-muted mb-3 text-center">
                            <strong>Dosya:</strong> <span id="cancelFileName"></span>
                        </p>
                        <div class="alert alert-warning d-flex align-items-center mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>Bu işlem dosyayı gizleyecek ve varsa ücret iadesi yapacaktır.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adminCancelNotes" class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>
                            İptal Sebebi (Opsiyonel)
                        </label>
                        <textarea class="form-control" id="adminCancelNotes" name="admin_notes" rows="3" 
                                  placeholder="İptal sebebinizi yazabilirsiniz..."></textarea>
                        <small class="text-muted">Bu not kullanıcıya gönderilecek bildirimde yer alacaktır.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="fas fa-check me-1"></i>Evet, İptal Et
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}
</style>

<script>
    // Admin iptal modal gösterme
    function showCancelModal(fileId, fileType, fileName) {
        document.getElementById('cancelFileId').value = fileId;
        document.getElementById('cancelFileType').value = fileType;
        document.getElementById('cancelFileName').textContent = fileName;
        document.getElementById('adminCancelNotes').value = '';
        
        var modal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
        modal.show();
    }
</script>
