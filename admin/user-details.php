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

// Kullanıcı istatistikleri
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
                    <div class="col-sm-6"><strong>Kredi:</strong></div>
                    <div class="col-sm-6">
                        <span class="badge bg-success">
                            <?php echo number_format($userDetails['credits'], 2); ?> TL
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
                                            <a href="uploads.php?view=<?php echo $upload['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
