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

// Filtreleme parametreleri
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;

// Kullanıcının revize taleplerini getir
$revisions = $fileManager->getUserRevisions($userId, $page, $limit, $dateFrom, $dateTo, $status);
$totalRevisions = $fileManager->getUserRevisionCount($userId, $dateFrom, $dateTo, $status);
$totalPages = ceil($totalRevisions / $limit);

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
        <?php include '_sidebar.php'; ?>
        
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
                                <div class="stat-icon bg-primary">
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
                                <div class="stat-icon bg-warning">
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
                                <div class="stat-icon bg-success">
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
                                <div class="stat-icon bg-danger">
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
                            <?php if ($status || $dateFrom || $dateTo): ?>
                                Filtreye uygun revize talebi bulunamadı
                            <?php else: ?>
                                Henüz revize talebi göndermemişsiniz
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-4">
                            <?php if ($status || $dateFrom || $dateTo): ?>
                                Farklı filtre kriterleri deneyebilir veya tüm revize taleplerinizi görüntüleyebilirsiniz.
                            <?php else: ?>
                                Tamamlanmış dosyalarınız için revize talep edebilir ve değişiklik isteyebilirsiniz.
                            <?php endif; ?>
                        </p>
                        <div class="empty-actions">
                            <?php if ($status || $dateFrom || $dateTo): ?>
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
                <!-- Revize Grid -->
                <div class="revisions-grid">
                    <?php foreach ($revisions as $revision): ?>
                        <div class="revision-card">
                            <div class="revision-card-header">
                                <div class="revision-id">
                                    <i class="fas fa-hashtag"></i>
                                    <span><?php echo substr($revision['id'], 0, 8); ?></span>
                                </div>
                                <div class="revision-status">
                                    <?php
                                    $statusConfig = [
                                        'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock'],
                                        'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle'],
                                        'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle']
                                    ];
                                    $config = $statusConfig[$revision['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                                    ?>
                                    <span class="badge bg-<?php echo $config['class']; ?> status-badge">
                                        <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                                        <?php echo $config['text']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="revision-card-body">
                                <h6 class="revision-file" title="<?php echo htmlspecialchars($revision['original_filename'] ?? 'Bilinmeyen dosya'); ?>">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <?php echo htmlspecialchars($revision['original_filename'] ?? 'Bilinmeyen dosya'); ?>
                                </h6>
                                
                                <div class="revision-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar me-1"></i>
                                        <span>Talep: <?php echo date('d.m.Y', strtotime($revision['requested_at'])); ?></span>
                                    </div>
                                    
                                    <?php if ($revision['completed_at']): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-check me-1"></i>
                                            <span>İşlem: <?php echo date('d.m.Y', strtotime($revision['completed_at'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($revision['credits_charged'] > 0): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-coins me-1"></i>
                                            <span class="text-warning"><?php echo $revision['credits_charged']; ?> Kredi</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="meta-item">
                                            <i class="fas fa-gift me-1"></i>
                                            <span class="text-success">Ücretsiz</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($revision['admin_username']): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-user-cog me-1"></i>
                                            <span><?php echo htmlspecialchars($revision['admin_username']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="revision-notes">
                                    <h6 class="notes-title">Talebiniz:</h6>
                                    <p class="notes-content">
                                        <?php echo nl2br(htmlspecialchars(substr($revision['request_notes'], 0, 150))); ?>
                                        <?php if (strlen($revision['request_notes']) > 150): ?>
                                            <span class="text-muted">...</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <?php if ($revision['admin_notes']): ?>
                                    <div class="admin-response">
                                        <h6 class="response-title">Admin Yanıtı:</h6>
                                        <p class="response-content">
                                            <?php echo nl2br(htmlspecialchars(substr($revision['admin_notes'], 0, 150))); ?>
                                            <?php if (strlen($revision['admin_notes']) > 150): ?>
                                                <span class="text-muted">...</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="revision-card-footer">
                                <div class="revision-actions">
                                    <button type="button" class="btn btn-outline-primary btn-sm action-btn" 
                                            onclick="viewRevisionDetails('<?php echo $revision['id']; ?>')">
                                        <i class="fas fa-eye me-1"></i>Detay
                                    </button>
                                    
                                    <a href="files.php?view=<?php echo $revision['upload_id']; ?>" 
                                       class="btn btn-outline-secondary btn-sm action-btn">
                                        <i class="fas fa-file me-1"></i>Dosya
                                    </a>
                                    
                                    <?php if ($revision['status'] === 'completed'): ?>
                                        <button type="button" class="btn btn-success btn-sm action-btn" 
                                                onclick="downloadRevision('<?php echo $revision['id']; ?>')">
                                            <i class="fas fa-download me-1"></i>İndir
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="revision-progress">
                                    <?php 
                                    $progressValue = 33;
                                    $progressClass = 'bg-warning';
                                    switch($revision['status']) {
                                        case 'completed':
                                            $progressValue = 100;
                                            $progressClass = 'bg-success';
                                            break;
                                        case 'rejected':
                                            $progressValue = 100;
                                            $progressClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar <?php echo $progressClass; ?>" 
                                             style="width: <?php echo $progressValue; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <nav aria-label="Revize sayfalama">
                            <ul class="pagination justify-content-center">
                                <!-- Önceki sayfa -->
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
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
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Sonraki sayfa -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
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

<!-- Revize Detay Modal -->
<div class="modal fade" id="revisionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2 text-warning"></i>Revize Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="revisionDetailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                    <p class="mt-2 text-muted">Revize detayları yükleniyor...</p>
                </div>
            </div>
        </div>
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

/* Revisions Grid */
.revisions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.revision-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    overflow: hidden;
}

.revision-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.revision-card-header {
    padding: 1.5rem 1.5rem 1rem;
    display: flex;
    justify-content: between;
    align-items: flex-start;
}

.revision-id {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
}

.revision-id i {
    margin-right: 0.5rem;
}

.revision-status {
    margin-left: auto;
}

.status-badge {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
}

.revision-card-body {
    padding: 0 1.5rem 1rem;
}

.revision-file {
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.revision-meta {
    display: grid;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
    color: #6c757d;
}

.meta-item i {
    color: #9ca3af;
    width: 16px;
}

.revision-notes, .admin-response {
    margin-bottom: 1rem;
}

.notes-title, .response-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.notes-content, .response-content {
    font-size: 0.85rem;
    color: #6c757d;
    line-height: 1.4;
    margin: 0;
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
}

.admin-response {
    border-left: 3px solid #28a745;
    padding-left: 0.75rem;
}

.revision-card-footer {
    padding: 1rem 1.5rem 1.5rem;
    border-top: 1px solid #f8f9fa;
}

.revision-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
}

.action-btn {
    flex: 1;
    min-width: 80px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
}

.revision-progress {
    margin-top: 0.75rem;
}

.progress-sm {
    height: 4px;
    border-radius: 2px;
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
// View Revision Details
function viewRevisionDetails(revisionId) {
    const modal = new bootstrap.Modal(document.getElementById('revisionDetailModal'));
    const content = document.getElementById('revisionDetailContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <p class="mt-2 text-muted">Revize detayları yükleniyor...</p>
        </div>
    `;
    
    modal.show();
    
    // Simulate API call (you'll need to implement this)
    setTimeout(() => {
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Revize Bilgileri</h6>
                    <div class="mb-2"><strong>Revize ID:</strong> ${revisionId}</div>
                    <div class="mb-2"><strong>Durum:</strong> <span class="badge bg-success">Tamamlandı</span></div>
                    <div class="mb-2"><strong>Talep Tarihi:</strong> ${new Date().toLocaleDateString('tr-TR')}</div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Dosya Bilgileri</h6>
                    <div class="mb-2"><strong>Dosya:</strong> sample_ecu.bin</div>
                    <div class="mb-2"><strong>Boyut:</strong> 512 KB</div>
                    <div class="mb-2"><strong>Tip:</strong> ECU Binary</div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12">
                    <h6 class="text-muted mb-3">Talep Detayları</h6>
                    <div class="bg-light p-3 rounded">
                        <p>Bu revize talep detayları buraya gelecek...</p>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}

// Download Revision
function downloadRevision(revisionId) {
    // Simulate download
    window.location.href = `download-revision.php?id=${revisionId}`;
}

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