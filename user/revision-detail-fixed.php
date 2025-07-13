<?php
/**
 * Mr ECU - Revize Detay Sayfası (Düzeltilmiş)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// UUID validation function
if (!function_exists('isValidUUID')) {
    function isValidUUID($uuid) {
        return !empty($uuid) && is_string($uuid) && strlen($uuid) >= 32;
    }
}

// Helper function for file size formatting
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';
        $k = 1024;
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB');
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/revision-detail.php');
}

// Revize ID kontrolü
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Revize ID belirtilmedi.';
    redirect('revisions.php');
}

$revisionId = sanitize($_GET['id']);
$userId = $_SESSION['user_id'];

if (!isValidUUID($revisionId)) {
    $_SESSION['error'] = 'Geçersiz revize ID formatı.';
    redirect('revisions.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);

// Revize detaylarını getir - DÜZELTİLMİŞ SQL
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               fu.original_name, fu.filename, fu.file_size, fu.status as file_status, fu.upload_date as file_uploaded_at,
               fu.file_type, fu.hp_power, fu.nm_torque, fu.plate,
               u.username as admin_username, u.first_name as admin_first_name, u.last_name as admin_last_name,
               br.name as brand_name, br.logo as brand_logo
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN users u ON r.admin_id = u.id
        LEFT JOIN brands br ON fu.brand_id = br.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$revisionId, $userId]);
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$revision) {
        $_SESSION['error'] = 'Revize bulunamadı veya bu revizeyi görüntüleme yetkiniz yok.';
        redirect('revisions.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Veritabanı hatası oluştu: ' . $e->getMessage();
    redirect('revisions.php');
}

// Status konfigürasyonu
$statusConfig = [
    'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock', 'bg' => 'warning'],
    'in_progress' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cog', 'bg' => 'info'],
    'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle', 'bg' => 'success'],
    'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle', 'bg' => 'danger'],
    'cancelled' => ['class' => 'secondary', 'text' => 'İptal Edildi', 'icon' => 'ban', 'bg' => 'secondary']
];
$currentStatus = $statusConfig[$revision['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question', 'bg' => 'secondary'];

$pageTitle = 'Revize Detayı - #' . substr($revision['id'], 0, 8);

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Success Alert -->
            <div class="alert alert-success mt-3">
                <h4>🎉 Revize Detay Sayfası Çalışıyor!</h4>
                <p>Database sorunu çözüldü. Artık revize detaylarını görüntüleyebilirsiniz.</p>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="pt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Panel</a></li>
                    <li class="breadcrumb-item"><a href="revisions.php">Revize Taleplerim</a></li>
                    <li class="breadcrumb-item active">#<?php echo substr($revision['id'], 0, 8); ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-edit me-2 text-<?php echo $currentStatus['class']; ?>"></i>
                        Revize Detayı #<?php echo substr($revision['id'], 0, 8); ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <span class="badge bg-<?php echo $currentStatus['bg']; ?> me-2">
                            <i class="fas fa-<?php echo $currentStatus['icon']; ?> me-1"></i>
                            <?php echo $currentStatus['text']; ?>
                        </span>
                        <?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?> tarihinde talep edildi
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="revisions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Listeye Dön
                        </a>
                        <?php if ($revision['status'] === 'completed'): ?>
                            <a href="download-revision.php?id=<?php echo $revision['id']; ?>" class="btn btn-success">
                                <i class="fas fa-download me-1"></i>Revize Dosyasını İndir
                            </a>
                        <?php endif; ?>
                        <a href="files.php?view=<?php echo $revision['upload_id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-file me-1"></i>Orijinal Dosya
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Sol Kolon - Ana Bilgiler -->
                <div class="col-lg-8">
                    <!-- Revize Özeti -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Revize Özeti
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Revize Bilgileri</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Revize ID:</strong> <code><?php echo htmlspecialchars($revision['id']); ?></code></li>
                                        <li><strong>Durum:</strong> 
                                            <span class="badge bg-<?php echo $currentStatus['bg']; ?>">
                                                <i class="fas fa-<?php echo $currentStatus['icon']; ?> me-1"></i>
                                                <?php echo $currentStatus['text']; ?>
                                            </span>
                                        </li>
                                        <li><strong>Talep Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?></li>
                                        <?php if ($revision['completed_at']): ?>
                                        <li><strong>Tamamlanma:</strong> <?php echo date('d.m.Y H:i', strtotime($revision['completed_at'])); ?></li>
                                        <?php endif; ?>
                                        <li><strong>Kredi:</strong> 
                                            <span class="<?php echo $revision['credits_charged'] > 0 ? 'text-warning' : 'text-success'; ?>">
                                                <?php echo $revision['credits_charged'] > 0 ? $revision['credits_charged'] . ' Kredi' : 'Ücretsiz'; ?>
                                            </span>
                                        </li>
                                        <?php if ($revision['admin_username']): ?>
                                        <li><strong>Admin:</strong> <?php echo htmlspecialchars($revision['admin_username']); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Dosya Bilgileri</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Dosya Adı:</strong> <?php echo htmlspecialchars($revision['original_name'] ?? 'Bilinmeyen'); ?></li>
                                        <li><strong>Dosya Boyutu:</strong> <?php echo formatFileSize($revision['file_size'] ?? 0); ?></li>
                                        <?php if ($revision['brand_name']): ?>
                                        <li><strong>Marka:</strong> <?php echo htmlspecialchars($revision['brand_name']); ?></li>
                                        <?php endif; ?>
                                        <?php if ($revision['plate']): ?>
                                        <li><strong>Plaka:</strong> <?php echo htmlspecialchars($revision['plate']); ?></li>
                                        <?php endif; ?>
                                        <?php if ($revision['hp_power']): ?>
                                        <li><strong>Güç:</strong> <?php echo htmlspecialchars($revision['hp_power']); ?> HP</li>
                                        <?php endif; ?>
                                        <?php if ($revision['nm_torque']): ?>
                                        <li><strong>Tork:</strong> <?php echo htmlspecialchars($revision['nm_torque']); ?> Nm</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Talep Notları -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-comment-alt me-2"></i>Talep Detaylarınız
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($revision['request_notes']): ?>
                                <div class="bg-light p-3 rounded border-start border-primary border-3">
                                    <?php echo nl2br(htmlspecialchars($revision['request_notes'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted fst-italic">Talep sırasında not belirtilmemiş.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Admin Yanıtı -->
                    <?php if ($revision['admin_notes']): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-cog me-2"></i>Admin Yanıtı
                                <?php if ($revision['admin_username']): ?>
                                    <small class="text-muted">- <?php echo htmlspecialchars($revision['admin_username']); ?></small>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="bg-light p-3 rounded border-start border-success border-3">
                                <?php echo nl2br(htmlspecialchars($revision['admin_notes'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Sağ Kolon - İşlemler -->
                <div class="col-lg-4">
                    <!-- İşlemler -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools me-2"></i>İşlemler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($revision['status'] === 'completed'): ?>
                                <a href="download-revision.php?id=<?php echo $revision['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>Revize Dosyasını İndir
                                </a>
                                <?php endif; ?>
                                
                                <a href="files.php?view=<?php echo $revision['upload_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-file me-2"></i>Orijinal Dosyayı Görüntüle
                                </a>
                                
                                <a href="revisions.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-list me-2"></i>Tüm Revize Taleplerim
                                </a>
                                
                                <?php if ($revision['status'] === 'completed'): ?>
                                <a href="files.php?status=completed" class="btn btn-outline-warning">
                                    <i class="fas fa-plus me-2"></i>Yeni Revize Talebi
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Revize Süreci
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <!-- Talep Oluşturuldu -->
                                <div class="timeline-item completed">
                                    <div class="timeline-marker">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize Talebi Oluşturuldu</h6>
                                        <p class="text-muted mb-0"><?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?></p>
                                    </div>
                                </div>

                                <!-- Admin Atandı -->
                                <?php if ($revision['admin_id']): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker">
                                        <i class="fas fa-user-cog"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Admin Atandı</h6>
                                        <p class="text-muted mb-0">
                                            <strong><?php echo htmlspecialchars($revision['admin_username']); ?></strong>
                                        </p>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- İşlem Durumu -->
                                <div class="timeline-item <?php echo $revision['status'] === 'completed' ? 'completed' : ($revision['status'] === 'rejected' ? 'rejected' : 'active'); ?>">
                                    <div class="timeline-marker">
                                        <i class="fas fa-<?php echo $currentStatus['icon']; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6><?php echo $currentStatus['text']; ?></h6>
                                        <?php if ($revision['completed_at']): ?>
                                        <p class="text-muted mb-0"><?php echo date('d.m.Y H:i', strtotime($revision['completed_at'])); ?></p>
                                        <?php else: ?>
                                        <p class="text-muted mb-0">İşlem devam ediyor</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    top: 0;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    z-index: 1;
}

.timeline-item.completed .timeline-marker {
    background: #28a745;
}

.timeline-item.active .timeline-marker {
    background: #007bff;
}

.timeline-item.rejected .timeline-marker {
    background: #dc3545;
}

.timeline-content h6 {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Responsive */
@media (max-width: 767.98px) {
    .timeline {
        padding-left: 1.5rem;
    }
    
    .timeline::before {
        left: 0.75rem;
    }
    
    .timeline-marker {
        left: -0.25rem;
        width: 1.5rem;
        height: 1.5rem;
        font-size: 0.75rem;
    }
}
</style>

<script>
// Auto refresh for pending revisions
<?php if ($revision['status'] === 'pending' || $revision['status'] === 'in_progress'): ?>
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
