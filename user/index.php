<?php
/**
 * Mr ECU - Kullanıcı Panel Ana Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);

// Kullanıcı istatistikleri
$userId = $_SESSION['user_id'];
$userCredits = $user->getUserCredits($userId);
$userUploads = $fileManager->getUserAllFiles($userId, 1, 10);

// İstatistikler - FileManager kullanarak
try {
    // FileManager ile istatistikleri al
    $stats = $fileManager->getUserFileStats($userId);
    $totalUploads = $stats['total'];
    $pendingUploads = $stats['pending'];
    $processingUploads = $stats['processing'];
    $completedUploads = $stats['completed'];
    $rejectedUploads = $stats['rejected'];

    // Bu ayki istatistikler
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND MONTH(upload_date) = MONTH(CURRENT_DATE()) AND YEAR(upload_date) = YEAR(CURRENT_DATE())");
    $stmt->execute([$userId]);
    $monthlyUploads = $stmt->fetchColumn();

    // Bu ayki harcama - sadece credit_transactions tablosu varsa
    $monthlySpent = 0;
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM credit_transactions WHERE user_id = ? AND type IN ('withdraw', 'file_charge') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stmt->execute([$userId]);
        $monthlySpent = $stmt->fetchColumn() ?: 0;
    } catch(PDOException $e) {
        // credit_transactions tablosu yoksa 0 olarak bırak
        $monthlySpent = 0;
    }

    // Son işlemler - sadece credit_transactions tablosu varsa
    $recentTransactions = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM credit_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId]);
        $recentTransactions = $stmt->fetchAll();
    } catch(PDOException $e) {
        // credit_transactions tablosu yoksa boş array
        $recentTransactions = [];
    }

} catch(PDOException $e) {
    $totalUploads = 0;
    $pendingUploads = 0;
    $processingUploads = 0;
    $completedUploads = 0;
    $rejectedUploads = 0;
    $monthlyUploads = 0;
    $monthlySpent = 0;
    $recentTransactions = [];
}

$pageTitle = 'Dashboard';
$pageDescription = 'Hoşgeldiniz! Hesabınızın genel durumunu buradan takip edebilirsiniz.';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">Dashboard</h1>
                    <p class="text-muted mb-0">Hoşgeldiniz, <?php echo htmlspecialchars($_SESSION['username']); ?>! Hesabınızın genel durumunu buradan takip edebilirsiniz.</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="upload.php" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i>Dosya Yükle
                        </a>
                    </div>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="welcome-banner mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="welcome-content">
                            <h3 class="mb-2">Hoşgeldiniz, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                            <p class="mb-3">
                                Hesabınızda <strong><?php echo $totalUploads; ?> dosya</strong> bulunuyor. 
                                Bu ay <strong><?php echo $monthlyUploads; ?> dosya</strong> yüklediniz ve 
                                <strong><?php echo number_format($monthlySpent, 2); ?> TL</strong> harcadınız.
                            </p>
                            <div class="welcome-stats">
                                <div class="stat">
                                    <i class="fas fa-coins text-warning"></i>
                                    <span>Mevcut Kredi: <strong><?php echo number_format($userCredits, 2); ?> TL</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="welcome-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İstatistik Kartları -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number"><?php echo $totalUploads; ?></div>
                                    <div class="stat-label">Toplam Dosya</div>
                                    <div class="stat-change">
                                        <i class="fas fa-arrow-up text-success"></i>
                                        <span class="text-success">Bu ay +<?php echo $monthlyUploads; ?></span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-file"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-warning"><?php echo $pendingUploads; ?></div>
                                    <div class="stat-label">Bekleyen</div>
                                    <?php if ($pendingUploads > 0): ?>
                                        <div class="stat-change">
                                            <i class="fas fa-clock text-warning"></i>
                                            <span class="text-warning">İnceleme aşamasında</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="stat-change">
                                            <i class="fas fa-check text-success"></i>
                                            <span class="text-success">Bekleyen dosya yok</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-info"><?php echo $processingUploads; ?></div>
                                    <div class="stat-label">İşleniyor</div>
                                    <?php if ($processingUploads > 0): ?>
                                        <div class="stat-change">
                                            <i class="fas fa-cogs text-info"></i>
                                            <span class="text-info">Aktif işlem</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="stat-change">
                                            <i class="fas fa-check text-success"></i>
                                            <span class="text-success">İşlenen dosya yok</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-cogs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-success"><?php echo $completedUploads; ?></div>
                                    <div class="stat-label">Tamamlanan</div>
                                    <div class="stat-change">
                                        <i class="fas fa-download text-success"></i>
                                        <span class="text-success">İndirilmeye hazır</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Son Dosyalar -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0">
                                <i class="fas fa-folder me-2 text-primary"></i>Son Yüklenen Dosyalar
                            </h5>
                            <a href="files.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Tümünü Gör
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($userUploads)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <h6>Henüz dosya yüklenmemiş</h6>
                                    <p class="text-muted mb-3">İlk dosyanızı yüklemek için butona tıklayın</p>
                                    <a href="upload.php" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Dosya Yükle
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0">Dosya Adı</th>
                                                <th class="border-0">Durum</th>
                                                <th class="border-0">Yüklenme Tarihi</th>
                                                <th class="border-0 text-center">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($userUploads, 0, 5) as $upload): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="file-icon me-3">
                                                                <i class="fas fa-file-alt"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-medium"><?php echo htmlspecialchars($upload['original_name'] ?? 'Bilinmeyen dosya'); ?></div>
                                                                <small class="text-muted">
                                                                    <?php 
                                                                    $fileSize = isset($upload['file_size']) ? formatFileSize($upload['file_size']) : 'N/A';
                                                                    echo $fileSize;
                                                                    ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusConfig = [
                                                            'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock'],
                                                            'processing' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cogs'],
                                                            'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle'],
                                                            'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle']
                                                        ];
                                                        $config = $statusConfig[$upload['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                                                        ?>
                                                        <span class="badge bg-<?php echo $config['class']; ?> badge-modern">
                                                            <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                                                            <?php echo $config['text']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="text-muted">
                                                            <?php echo date('d.m.Y', strtotime($upload['upload_date'])); ?>
                                                            <small class="d-block">
                                                                <?php echo date('H:i', strtotime($upload['upload_date'])); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($upload['status'] === 'completed'): ?>
                                                                <a href="download.php?id=<?php echo $upload['id']; ?>" 
                                                                   class="btn btn-success btn-sm" title="İndir">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="file-detail.php?id=<?php echo $upload['id']; ?>" 
                                                               class="btn btn-outline-primary btn-sm" title="Detay">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
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

                <!-- Yan Panel -->
                <div class="col-lg-4">
                    <!-- Hızlı İşlemler -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2 text-warning"></i>Hızlı İşlemler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="upload.php" class="btn btn-primary btn-modern">
                                    <i class="fas fa-upload me-2"></i>Yeni Dosya Yükle
                                </a>
                                
                                <a href="files.php" class="btn btn-outline-primary btn-modern">
                                    <i class="fas fa-folder me-2"></i>Dosyalarımı Görüntüle
                                </a>
                                
                                <a href="credits.php" class="btn btn-outline-success btn-modern">
                                    <i class="fas fa-credit-card me-2"></i>Kredi Yükle
                                </a>
                                
                                <a href="profile.php" class="btn btn-outline-secondary btn-modern">
                                    <i class="fas fa-user-cog me-2"></i>Profil Ayarları
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Hesap Durumu -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-pie me-2 text-info"></i>Hesap Durumu
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="account-stats">
                                <div class="stat-row">
                                    <div class="stat-info">
                                        <i class="fas fa-coins text-warning"></i>
                                        <span>Mevcut Kredi</span>
                                    </div>
                                    <div class="stat-value text-warning fw-bold">
                                        <?php echo number_format($userCredits, 2); ?> TL
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-info">
                                        <i class="fas fa-file text-primary"></i>
                                        <span>Toplam Dosya</span>
                                    </div>
                                    <div class="stat-value text-primary fw-bold">
                                        <?php echo $totalUploads; ?>
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-info">
                                        <i class="fas fa-calendar text-info"></i>
                                        <span>Bu Ay</span>
                                    </div>
                                    <div class="stat-value text-info fw-bold">
                                        <?php echo $monthlyUploads; ?> dosya
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($userCredits < 10): ?>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <small>
                                        Krediniz azalıyor. <a href="credits.php" class="alert-link fw-semibold">Kredi yükleyin</a>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Son İşlemler -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0">
                                <i class="fas fa-history me-2 text-secondary"></i>Son İşlemler
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentTransactions)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Henüz işlem yapılmamış</p>
                                </div>
                            <?php else: ?>
                                <div class="transaction-list">
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                        <div class="transaction-item">
                                            <div class="transaction-icon">
                                                <?php if (($transaction['transaction_type'] ?? '') === 'add'): ?>
                                                    <i class="fas fa-plus-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-minus-circle text-danger"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="transaction-details">
                                                <div class="transaction-desc">
                                                    <?php echo htmlspecialchars($transaction['description'] ?? 'Açıklama yok'); ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="transaction-amount">
                                                <?php if (($transaction['transaction_type'] ?? '') === 'add'): ?>
                                                    <span class="text-success">+<?php echo number_format($transaction['amount'], 2); ?> TL</span>
                                                <?php else: ?>
                                                    <span class="text-danger">-<?php echo number_format($transaction['amount'], 2); ?> TL</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="transactions.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-list me-1"></i>Tüm İşlemler
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
/* Modern Dashboard Stilleri */
.welcome-banner {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    border-radius: 16px;
    padding: 2rem;
    color: white;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.welcome-content h3 {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.welcome-content p {
    opacity: 0.9;
    line-height: 1.6;
}

.welcome-stats .stat {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
}

.welcome-stats .stat i {
    margin-right: 0.5rem;
    font-size: 1.1rem;
}

.welcome-icon {
    font-size: 5rem;
    opacity: 0.2;
}

.stat-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-card-body {
    padding: 1.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.stat-change {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
}

.stat-change i {
    margin-right: 0.25rem;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.file-icon {
    width: 36px;
    height: 36px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.badge-modern {
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
}

.btn-modern {
    border-radius: 8px;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-modern:hover {
    transform: translateY(-1px);
}

.account-stats {
    space-y: 1rem;
}

.stat-row {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-info {
    display: flex;
    align-items: center;
    flex: 1;
}

.stat-info i {
    margin-right: 0.75rem;
    width: 16px;
}

.stat-value {
    font-size: 0.95rem;
}

.transaction-list {
    max-height: 300px;
    overflow-y: auto;
}

.transaction-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-icon {
    margin-right: 0.75rem;
    font-size: 1.1rem;
}

.transaction-details {
    flex: 1;
}

.transaction-desc {
    font-size: 0.9rem;
    font-weight: 500;
    line-height: 1.3;
}

.transaction-amount {
    font-weight: 600;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 767.98px) {
    .welcome-banner {
        padding: 1.5rem;
        text-align: center;
    }
    
    .welcome-icon {
        font-size: 3rem;
        margin-top: 1rem;
    }
    
    .stat-card-body {
        padding: 1.25rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}
</style>

<?php
// Header include  
include '../includes/user_footer.php';
?>