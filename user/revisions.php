<?php
/**
 * Mr ECU - Modern Kullanıcı Revize Talepleri Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/revisions.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
$userId = $_SESSION['user_id'];



// Filtreleme ve arama parametreleri
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;

// Kullanıcının revize taleplerini getir
$revisions = $fileManager->getUserRevisions($userId, $page, $limit, $dateFrom, $dateTo, $status, $search);
$totalRevisions = $fileManager->getUserRevisionCount($userId, $dateFrom, $dateTo, $status, $search);
$totalPages = ceil($totalRevisions / $limit);

// Her revizyon için dosyalarını al
foreach ($revisions as &$revision) {
    if ($revision['status'] === 'completed') {
        $revision['revision_files'] = $fileManager->getRevisionFiles($revision['id'], $userId);
    } else {
        $revision['revision_files'] = [];
    }
}

// İstatistikler
try {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM revisions WHERE user_id = ? GROUP BY status");
    $stmt->execute([$userId]);
    $revisionStats = [];
    while ($row = $stmt->fetch()) {
        $revisionStats[$row['status']] = $row['count'];
    }
    
    $totalRevisionCount = array_sum($revisionStats);
    $pendingCount = $revisionStats['pending'] ?? 0;
    $completedCount = $revisionStats['completed'] ?? 0;
    $rejectedCount = $revisionStats['rejected'] ?? 0;
} catch(PDOException $e) {
    $revisionStats = [];
    $totalRevisionCount = 0;
    $pendingCount = 0;
    $completedCount = 0;
    $rejectedCount = 0;
}

$pageTitle = 'Revize Taleplerim';

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
                        <i class="fas fa-edit me-2 text-warning"></i>Revize Taleplerim
                    </h1>
                    <p class="text-muted mb-0">Dosyalarınız için gönderdiğiniz revize taleplerini takip edin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="files.php?status=completed" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Yeni Revize Talebi
                        </a>
                        <a href="files.php" class="btn btn-outline-secondary">
                            <i class="fas fa-folder me-1"></i>Dosyalarım
                        </a>
                    </div>
                </div>
            </div>

            <!-- İstatistik Kartları -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card revision">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-primary"><?php echo $totalRevisionCount; ?></div>
                                    <div class="stat-label">Toplam Talep</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-chart-line text-success"></i>
                                        <span class="text-success">Tüm talepleriniz</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-edit"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card revision">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-warning"><?php echo $pendingCount; ?></div>
                                    <div class="stat-label">Bekleyen</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-clock text-warning"></i>
                                        <span class="text-warning">İnceleniyor</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card revision">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-success"><?php echo $completedCount; ?></div>
                                    <div class="stat-label">Tamamlanan</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <span class="text-success">Başarılı</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card revision">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-danger"><?php echo $rejectedCount; ?></div>
                                    <div class="stat-label">Reddedilen</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-times-circle text-danger"></i>
                                        <span class="text-danger">İptal edildi</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revize Sistemi Bilgilendirme -->
            <div class="info-banner mb-4">
                <div class="info-content">
                    <div class="info-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="info-text">
                        <h6 class="mb-1">Revize Sistemi Nasıl Çalışır?</h6>
                        <p class="mb-0">
                            Tamamlanmış dosyalarınızda değişiklik isteyebilirsiniz. Talep gönderdiğinizde admin ekibimiz 
                            talebinizi inceler ve uygun revizeyi gerçekleştirir. 
                            <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#infoModal">Detaylı bilgi</a>
                        </p>
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
                            <label for="search" class="form-label">
                                <i class="fas fa-search me-1"></i>Arama
                            </label>
                            <input type="text" class="form-control form-control-modern" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Dosya adı, notlar...">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">
                                <i class="fas fa-tag me-1"></i>Durum
                            </label>
                            <select class="form-select form-control-modern" id="status" name="status">
                                <option value="">Tüm Durumlar</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Tamamlanan</option>
                                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Başlangıç Tarihi
                            </label>
                            <input type="date" class="form-control form-control-modern" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        
                        <div class="col-md-2">
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
                                <a href="revisions.php" class="btn btn-outline-secondary btn-modern">
                                    <i class="fas fa-undo me-1"></i>Temizle
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Revize Talepleri Listesi -->
            <?php if (empty($revisions)): ?>
                <div class="empty-state-card">
                    <div class="empty-content">
                        <div class="empty-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h4>
                            <?php if ($search || $status || $dateFrom || $dateTo): ?>
                                Arama veya filtreye uygun revize talebi bulunamadı
                            <?php else: ?>
                                Henüz revize talebi göndermemişsiniz
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-4">
                            <?php if ($search || $status || $dateFrom || $dateTo): ?>
                                Farklı arama terimi veya filtre kriterleri deneyebilir veya tüm revize taleplerinizi görüntüleyebilirsiniz.
                            <?php else: ?>
                                Tamamlanmış dosyalarınız için revize talep edebilir ve değişiklik isteyebilirsiniz.
                            <?php endif; ?>
                        </p>
                        <div class="empty-actions">
                            <?php if ($search || $status || $dateFrom || $dateTo): ?>
                                <a href="revisions.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-list me-2"></i>Tüm Talepler
                                </a>
                            <?php endif; ?>
                            <a href="files.php?status=completed" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Revize Talebi Gönder
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Revize Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Revize Talepleriniz
                            <span class="badge bg-primary ms-2"><?php echo count($revisions); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="100">ID</th>
                                        <th>Dosya Adı</th>
                                        <th width="120">Durum</th>
                                        <th width="150">Talep Tarihi</th>
                                        <th width="150">Tamamlanma</th>
                                        <th width="100">Kredi</th>
                                        <th width="120">Admin</th>
                                        <th width="200">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($revisions as $revision): ?>
                                        <?php
                                        $statusConfig = [
                                            'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock'],
                                            'in_progress' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cog'],
                                            'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle'],
                                            'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle'],
                                            'cancelled' => ['class' => 'secondary', 'text' => 'İptal', 'icon' => 'ban']
                                        ];
                                        $config = $statusConfig[$revision['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                                        ?>
                                        <tr class="revision-row" data-revision-id="<?php echo htmlspecialchars($revision['id']); ?>">
                                            <td>
                                                <span class="font-monospace text-muted">
                                                    #<?php echo substr($revision['id'], 0, 8); ?>
                                                </span>
                                                <!-- Debug: Tam ID göster -->
                                                <small class="d-block text-muted" style="font-size: 10px;">Debug: <?php echo htmlspecialchars($revision['id']); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-code text-primary me-2"></i>
                                                    <div>
                                                        <div class="fw-semibold text-truncate" style="max-width: 250px;" 
                                                             title="<?php echo htmlspecialchars($revision['original_name'] ?? 'Bilinmeyen dosya'); ?>">
                                                            <?php echo htmlspecialchars($revision['original_name'] ?? 'Bilinmeyen dosya'); ?>
                                                        </div>
                                                        <?php if ($revision['request_notes']): ?>
                                                            <small class="text-muted text-truncate d-block" style="max-width: 250px;" 
                                                                   title="<?php echo htmlspecialchars($revision['request_notes']); ?>">
                                                                <i class="fas fa-comment me-1"></i>
                                                                <?php echo htmlspecialchars(substr($revision['request_notes'], 0, 50)); ?>
                                                                <?php if (strlen($revision['request_notes']) > 50): ?>..<?php endif; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $config['class']; ?> badge-status">
                                                    <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                                                    <?php echo $config['text']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-nowrap">
                                                    <div><?php echo date('d.m.Y', strtotime($revision['requested_at'])); ?></div>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($revision['requested_at'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($revision['completed_at']): ?>
                                                    <div class="text-nowrap">
                                                        <div><?php echo date('d.m.Y', strtotime($revision['completed_at'])); ?></div>
                                                        <small class="text-muted"><?php echo date('H:i', strtotime($revision['completed_at'])); ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($revision['credits_charged'] > 0): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-coins me-1"></i>
                                                        <?php echo $revision['credits_charged']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-gift me-1"></i>
                                                        Ücretsiz
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($revision['admin_username']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user-cog text-secondary me-1"></i>
                                                        <span class="text-truncate" style="max-width: 80px;" 
                                                              title="<?php echo htmlspecialchars($revision['admin_username']); ?>">
                                                            <?php echo htmlspecialchars($revision['admin_username']); ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="revision-detail.php?id=<?php echo htmlspecialchars($revision['id']); ?>" 
                                                       class="btn btn-outline-primary" title="Detay">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <a href="files.php?view=<?php echo $revision['upload_id']; ?>" 
                                                       class="btn btn-outline-secondary" title="Orijinal Dosya">
                                                        <i class="fas fa-file"></i>
                                                    </a>
                                                    
                                                    <?php if ($revision['status'] === 'completed' && !empty($revision['revision_files'])): ?>
                                                        <a href="download-revision.php?id=<?php echo $revision['revision_files'][0]['id']; ?>" 
                                                           class="btn btn-success" title="Revize Dosyasını İndir">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <nav aria-label="Revize sayfalama">
                            <ul class="pagination justify-content-center">
                                <!-- Önceki sayfa -->
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
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
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Sonraki sayfa -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        
                        <div class="pagination-info">
                            <small class="text-muted">
                                Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> 
                                (Toplam <?php echo $totalRevisions; ?> revize talebi)
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>



<!-- Bilgilendirme Modal -->
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2 text-primary"></i>Revize Sistemi Hakkında
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="info-section">
                    <h6><i class="fas fa-question-circle me-2"></i>Revize Sistemi Nedir?</h6>
                    <p>Revize sistemi, tamamlanmış ECU dosyalarınızda değişiklik talep etmenizi sağlayan özelliğimizdir. Bu sayede dosyanızın teknik parametrelerini ayarlayabilirsiniz.</p>
                </div>
                
                <div class="info-section">
                    <h6><i class="fas fa-cog me-2"></i>Nasıl Çalışır?</h6>
                    <ol class="ps-3">
                        <li>Tamamlanmış dosyalarınızdan birini seçin</li>
                        <li>Revize talep et butonuna tıklayın</li>
                        <li>Hangi değişiklikleri istediğinizi detaylı açıklayın</li>
                        <li>Admin ekibimiz talebinizi inceler</li>
                        <li>Revize tamamlandığında bilgilendirilirsiniz</li>
                    </ol>
                </div>
                
                <div class="info-section">
                    <h6><i class="fas fa-coins me-2"></i>Ücretlendirme</h6>
                    <p>Revize talepleri için değişikliğin karmaşıklığına göre ek ücret alınabilir. Ücret bilgisi talep onaylanmadan önce size bildirilir.</p>
                </div>
                
                <div class="info-section">
                    <h6><i class="fas fa-clock me-2"></i>İşlem Süresi</h6>
                    <p>Revize talepleri genellikle 24-72 saat içinde tamamlanır. Karmaşık değişiklikler daha uzun sürebilir.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Anladım</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Revisions Page Styles */
.stat-card.revision {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.stat-card.revision:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

/* Info Banner */
.info-banner {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #90caf9;
}

.info-content {
    display: flex;
    align-items: flex-start;
}

.info-icon {
    font-size: 1.5rem;
    color: #1976d2;
    margin-right: 1rem;
    margin-top: 0.125rem;
}

.info-text h6 {
    color: #1565c0;
    font-weight: 600;
}

.info-text p {
    color: #0d47a1;
    margin: 0;
}

/* Revisions Table Styles */
.revision-row {
    transition: all 0.2s ease;
}

.revision-row:hover {
    background-color: #f8f9fa;
}

.revision-row td:not(:last-child) {
    cursor: pointer;
}

.badge-status {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 0.4rem 0.6rem;
    border-radius: 6px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
    font-size: 0.875rem;
    padding: 0.75rem;
}

.table td {
    vertical-align: middle;
    padding: 0.75rem;
    border-top: 1px solid #f0f0f0;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.btn-group-sm .btn {
    padding: 0.375rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 4px;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Info Modal */
.info-section {
    margin-bottom: 1.5rem;
}

.info-section:last-child {
    margin-bottom: 0;
}

.info-section h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.info-section p, .info-section ol {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 767.98px) {
    .revisions-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .revision-card-header {
        padding: 1.25rem 1.25rem 0.75rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .revision-card-body {
        padding: 0 1.25rem 0.75rem;
    }
    
    .revision-card-footer {
        padding: 0.75rem 1.25rem 1.25rem;
    }
    
    .revision-actions {
        flex-direction: column;
    }
    
    .action-btn {
        flex: none;
    }
    
    .info-banner {
        padding: 1rem;
    }
    
    .info-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .info-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}
</style>

<script>
// Table row click handler
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, setting up click handlers...');
    
    // Add click handler for table rows (but not for buttons)
    const tableRows = document.querySelectorAll('.revision-row');
    console.log('Found table rows:', tableRows.length);
    
    tableRows.forEach((row, index) => {
        const revisionId = row.getAttribute('data-revision-id');
        console.log(`Row ${index}: revision ID = ${revisionId}`);
        
        row.addEventListener('click', function(e) {
            console.log('Row clicked:', e.target);
            
            // Don't trigger if clicking on buttons, links, or their children
            if (e.target.closest('.btn') || e.target.closest('a') || e.target.closest('.btn-group')) {
                console.log('Click ignored - button/link clicked');
                return;
            }
            
            const revisionId = this.getAttribute('data-revision-id');
            console.log('Navigating to revision:', revisionId);
            
            if (revisionId) {
                const url = `revision-detail.php?id=${revisionId}`;
                console.log('URL:', url);
                window.location.href = url;
            } else {
                console.error('No revision ID found');
            }
        });
        
        // Add hover cursor only to non-button areas
        row.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
        });
        
        // Reset cursor when hovering over buttons
        const buttons = row.querySelectorAll('.btn, a');
        buttons.forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                row.style.cursor = 'default';
            });
            btn.addEventListener('mouseleave', function() {
                row.style.cursor = 'pointer';
            });
        });
    });
});



// Auto-refresh for pending revisions
<?php if ($pendingCount > 0): ?>
setTimeout(() => {
    if (!document.hidden) {
        location.reload();
    }
}, 60000); // 60 seconds
<?php endif; ?>
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>