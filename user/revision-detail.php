<?php
/**
 * Mr ECU - Revize Detay Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// UUID validation function - Daha esnek hale getirelim
if (!function_exists('isValidUUID')) {
    function isValidUUID($uuid) {
        // Basit string kontrolü - sadece boş olmamasını kontrol edelim
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

// Revize detaylarını getir
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
    $_SESSION['error'] = 'Veritabanı hatası oluştu.';
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
                    <!-- Revize Timeline -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Revize Sürecı
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
                                        <p class="text-muted mb-1"><?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?></p>
                                        <small class="text-muted">Revize talebiniz sisteme kaydedildi ve inceleme kuyruğuna alındı.</small>
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
                                        <p class="text-muted mb-1">
                                            <strong><?php echo htmlspecialchars($revision['admin_username']); ?></strong>
                                            <?php if ($revision['admin_first_name']): ?>
                                                (<?php echo htmlspecialchars($revision['admin_first_name'] . ' ' . $revision['admin_last_name']); ?>)
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted">Talebiniz uzman bir admin tarafından incelemeye alındı.</small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- İşlem Durumu -->
                                <?php if ($revision['status'] === 'in_progress'): ?>
                                <div class="timeline-item active">
                                    <div class="timeline-marker">
                                        <i class="fas fa-cog fa-spin"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize İşleniyor</h6>
                                        <p class="text-muted mb-1">Şu anda</p>
                                        <small class="text-muted">Dosyanız üzerinde revize işlemleri gerçekleştiriliyor.</small>
                                    </div>
                                </div>
                                <?php elseif ($revision['status'] === 'completed'): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize Tamamlandı</h6>
                                        <p class="text-muted mb-1"><?php echo $revision['completed_at'] ? date('d.m.Y H:i', strtotime($revision['completed_at'])) : 'Bilinmiyor'; ?></p>
                                        <small class="text-muted">Revize işlemi başarıyla tamamlandı ve dosyanız hazır.</small>
                                    </div>
                                </div>
                                <?php elseif ($revision['status'] === 'rejected'): ?>
                                <div class="timeline-item rejected">
                                    <div class="timeline-marker">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize Reddedildi</h6>
                                        <p class="text-muted mb-1"><?php echo $revision['completed_at'] ? date('d.m.Y H:i', strtotime($revision['completed_at'])) : 'Bilinmiyor'; ?></p>
                                        <small class="text-muted">Talep belirtilen nedenlerle reddedildi.</small>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="timeline-item pending">
                                    <div class="timeline-marker">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>İnceleme Bekliyor</h6>
                                        <p class="text-muted mb-1">Beklemede</p>
                                        <small class="text-muted">Talebiniz admin ekibimiz tarafından inceleniyor.</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Talep Detayları -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-comment-alt me-2"></i>Talep Detaylarınız
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($revision['request_notes']): ?>
                                <div class="request-notes">
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
                            <div class="admin-response">
                                <?php echo nl2br(htmlspecialchars($revision['admin_notes'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Dosya Bilgileri -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Dosya Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Orijinal Dosya -->
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Orijinal Dosya</h6>
                                    <div class="file-info">
                                        <div class="file-icon">
                                            <i class="fas fa-file-code text-primary"></i>
                                        </div>
                                        <div class="file-details">
                                            <h6 class="file-name"><?php echo htmlspecialchars($revision['original_name'] ?? 'Bilinmeyen dosya'); ?></h6>
                                            <div class="file-meta">
                                                <span class="badge bg-light text-dark me-2">
                                                    <i class="fas fa-hdd me-1"></i>
                                                    <?php echo formatFileSize($revision['file_size'] ?? 0); ?>
                                                </span>
                                                <?php if ($revision['brand_name']): ?>
                                                <span class="badge bg-primary me-2">
                                                    <i class="fas fa-car me-1"></i>
                                                    <?php echo htmlspecialchars($revision['brand_name']); ?>
                                                </span>
                                                <?php endif; ?>
                                                <?php if ($revision['category_name']): ?>
                                                <span class="badge bg-secondary me-2">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo htmlspecialchars($revision['category_name']); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo $revision['file_uploaded_at'] ? date('d.m.Y H:i', strtotime($revision['file_uploaded_at'])) : 'Bilinmiyor'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Revize Dosya -->
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Revize Dosya</h6>
                                    <?php if ($revision['status'] === 'completed' && $revision['revision_filename']): ?>
                                    <div class="file-info">
                                        <div class="file-icon">
                                            <i class="fas fa-file-code text-success"></i>
                                        </div>
                                        <div class="file-details">
                                            <h6 class="file-name"><?php echo htmlspecialchars($revision['revision_filename']); ?></h6>
                                            <div class="file-meta">
                                                <span class="badge bg-light text-dark me-2">
                                                    <i class="fas fa-hdd me-1"></i>
                                                    <?php echo formatFileSize($revision['revision_file_size'] ?? 0); ?>
                                                </span>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>
                                                    Hazır
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo $revision['revision_uploaded_at'] ? date('d.m.Y H:i', strtotime($revision['revision_uploaded_at'])) : 'Bilinmiyor'; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-clock text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2 mb-0">
                                            <?php 
                                            switch($revision['status']) {
                                                case 'pending':
                                                    echo 'Revize işlemi henüz başlamadı';
                                                    break;
                                                case 'in_progress':
                                                    echo 'Revize işlemi devam ediyor';
                                                    break;
                                                case 'rejected':
                                                    echo 'Revize talebi reddedildi';
                                                    break;
                                                default:
                                                    echo 'Revize dosyası hazır değil';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon - Özet Bilgiler -->
                <div class="col-lg-4">
                    <!-- Revize Özeti -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Revize Özeti
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-list">
                                <div class="summary-item">
                                    <span class="summary-label">Revize ID:</span>
                                    <span class="summary-value font-monospace"><?php echo substr($revision['id'], 0, 8); ?>...</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Durum:</span>
                                    <span class="summary-value">
                                        <span class="badge bg-<?php echo $currentStatus['bg']; ?>">
                                            <i class="fas fa-<?php echo $currentStatus['icon']; ?> me-1"></i>
                                            <?php echo $currentStatus['text']; ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Talep Tarihi:</span>
                                    <span class="summary-value"><?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?></span>
                                </div>
                                <?php if ($revision['completed_at']): ?>
                                <div class="summary-item">
                                    <span class="summary-label">Tamamlanma:</span>
                                    <span class="summary-value"><?php echo date('d.m.Y H:i', strtotime($revision['completed_at'])); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="summary-item">
                                    <span class="summary-label">Kredi:</span>
                                    <span class="summary-value <?php echo $revision['credits_charged'] > 0 ? 'text-warning' : 'text-success'; ?>">
                                        <?php echo $revision['credits_charged'] > 0 ? $revision['credits_charged'] . ' Kredi' : 'Ücretsiz'; ?>
                                    </span>
                                </div>
                                <?php if ($revision['admin_username']): ?>
                                <div class="summary-item">
                                    <span class="summary-label">Admin:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($revision['admin_username']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- İşlemler -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools me-2"></i>İşlemler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($revision['status'] === 'completed' && $revision['revision_file_path']): ?>
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

                    <!-- Yardım -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>Yardım
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Revize süreciyle ilgili sorularınız varsa:</p>
                            <div class="d-grid gap-2">
                                <a href="contact.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-envelope me-2"></i>İletişime Geç
                                </a>
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                                    <i class="fas fa-info me-2"></i>Revize Hakkında
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Yardım Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
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
                    <p>Revize sistemi, tamamlanmış ECU dosyalarınızda değişiklik talep etmenizi sağlayan özelliğimizdir.</p>
                </div>
                
                <div class="info-section">
                    <h6><i class="fas fa-cog me-2"></i>Süreç Nasıl İşler?</h6>
                    <ol class="ps-3">
                        <li>Revize talebiniz sisteme kaydedilir</li>
                        <li>Uzman admin tarafından incelenir</li>
                        <li>Dosyanız üzerinde gerekli değişiklikler yapılır</li>
                        <li>Tamamlandığında size bildirim gönderilir</li>
                        <li>Revize dosyanızı indirebilirsiniz</li>
                    </ol>
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
/* Revize Detay Sayfası Stilleri */
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

.timeline-item.pending .timeline-marker {
    background: #ffc107;
    color: #212529;
}

.timeline-item.rejected .timeline-marker {
    background: #dc3545;
}

.timeline-content h6 {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.timeline-content p {
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.request-notes, .admin-response {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #007bff;
    line-height: 1.6;
}

.admin-response {
    border-left-color: #28a745;
}

.file-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.file-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    word-break: break-all;
}

.file-meta {
    margin-bottom: 0.5rem;
}

.summary-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f8f9fa;
}

.summary-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.summary-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.9rem;
}

.summary-value {
    font-weight: 600;
    color: #495057;
    text-align: right;
}

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
    
    .file-info {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .summary-value {
        text-align: left;
    }
}
</style>

<script>
// Formatters
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

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