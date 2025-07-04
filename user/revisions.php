<?php
/**
 * Mr ECU - Kullanıcı Revize Talepleri
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

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;

// Tarih filtresi parametreleri
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Tarih validasyonu
if ($dateFrom && !DateTime::createFromFormat('Y-m-d', $dateFrom)) {
    $dateFrom = '';
}
if ($dateTo && !DateTime::createFromFormat('Y-m-d', $dateTo)) {
    $dateTo = '';
}

// Kullanıcının revize taleplerini getir (tarih filtresi ile)
$revisions = $fileManager->getUserRevisions($userId, $page, $limit, $dateFrom, $dateTo);

// Seçili revize detayı
$selectedRevision = null;
$revisionFiles = [];
if (isset($_GET['detail_id'])) {
    $revisionId = sanitize($_GET['detail_id']);
    
    if (isValidUUID($revisionId)) {
        foreach ($revisions as $revision) {
            if ($revision['id'] === $revisionId) {
                $selectedRevision = $revision;
                $revisionFiles = $fileManager->getRevisionFilesByUploadId($revision['upload_id']);
                break;
            }
        }
    }
}

$pageTitle = 'Revize Taleplerim';
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
                        <i class="fas fa-edit me-2"></i>Revize Taleplerim
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="files.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-folder me-1"></i>Dosyalarıma Dön
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Seçili Revize Detayı -->
                <?php if ($selectedRevision): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-edit me-2"></i>Revize Detayları
                                        <small class="d-block mt-1 opacity-75"><?php echo htmlspecialchars($selectedRevision['original_name']); ?></small>
                                    </h5>
                                    <a href="revisions.php" class="btn btn-light btn-sm">
                                        <i class="fas fa-times me-1"></i>Kapat
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Dosya Bilgileri</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Talep ID:</strong></td>
                                                    <td><code class="small"><?php echo $selectedRevision['id']; ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Dosya:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedRevision['original_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Talep Tarihi:</strong></td>
                                                    <td><?php echo formatDate($selectedRevision['requested_at']); ?></td>
                                                </tr>
                                                <?php if ($selectedRevision['completed_at']): ?>
                                                <tr>
                                                    <td><strong>İşlem Tarihi:</strong></td>
                                                    <td><?php echo formatDate($selectedRevision['completed_at']); ?></td>
                                                </tr>
                                                <?php endif; ?>
                                                <?php if ($selectedRevision['admin_username']): ?>
                                                <tr>
                                                    <td><strong>İşleyen Admin:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedRevision['admin_username']); ?></td>
                                                </tr>
                                                <?php endif; ?>
                                            </table>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Revize Durumu</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Durum:</strong></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'warning';
                                                        $statusText = 'Bekliyor';
                                                        
                                                        switch ($selectedRevision['status']) {
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
                                                    </td>
                                                </tr>
                                                <?php if ($selectedRevision['credits_charged'] > 0): ?>
                                                <tr>
                                                    <td><strong>Ücret:</strong></td>
                                                    <td><span class="badge bg-warning"><?php echo $selectedRevision['credits_charged']; ?> Kredi</span></td>
                                                </tr>
                                                <?php endif; ?>
                                            </table>
                                            
                                            <h6 class="text-muted mt-3">Talebiniz</h6>
                                            <div class="bg-light p-3 rounded" style="max-height: 150px; overflow-y: auto;">
                                                <?php echo nl2br(htmlspecialchars($selectedRevision['request_notes'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <?php if ($selectedRevision['admin_notes']): ?>
                                                <h6 class="text-muted">Admin Yanıtı</h6>
                                                <div class="bg-info bg-opacity-10 p-3 rounded border border-info border-opacity-25" style="max-height: 200px; overflow-y: auto;">
                                                    <?php echo nl2br(htmlspecialchars($selectedRevision['admin_notes'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <h6 class="text-muted">Admin Yanıtı</h6>
                                                <div class="text-muted p-3">
                                                    <i class="fas fa-clock me-2"></i>Henüz yanıt verilmedi...
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Yüklenmiş Revize Dosyaları -->
                                    <?php if (!empty($revisionFiles)): ?>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card border-success">
                                                    <div class="card-header bg-success text-white">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-file-download me-2"></i>Revize Edilmiş Dosyalarınız
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Dosya</th>
                                                                        <th>Boyut</th>
                                                                        <th>Revize Tarihi</th>
                                                                        <th>İndirilme</th>
                                                                        <th>İşlem</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($revisionFiles as $revFile): ?>
                                                                        <tr>
                                                                            <td>
                                                                                <i class="fas fa-file text-success me-2"></i>
                                                                                <strong><?php echo htmlspecialchars($revFile['original_name']); ?></strong>
                                                                            </td>
                                                                            <td><?php echo number_format($revFile['file_size'] / 1024, 2); ?> KB</td>
                                                                            <td><?php echo formatDate($revFile['upload_date']); ?></td>
                                                                            <td>
                                                                                <?php if ($revFile['downloaded']): ?>
                                                                                    <span class="badge bg-success">
                                                                                        <i class="fas fa-check me-1"></i>İndirildi
                                                                                    </span>
                                                                                    <br><small class="text-muted"><?php echo formatDate($revFile['download_date']); ?></small>
                                                                                <?php else: ?>
                                                                                    <span class="badge bg-secondary">Henüz indirilmedi</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <a href="download-revision.php?id=<?php echo urlencode($revFile['id']); ?>" 
                                                                                   class="btn btn-sm btn-success" title="İndir">
                                                                                    <i class="fas fa-download"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Hızlı İşlemler -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="d-flex gap-2">
                                                <a href="files.php?id=<?php echo $selectedRevision['upload_id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-file me-1"></i>Orijinal Dosyayı Görüntüle
                                                </a>
                                                <a href="revisions.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-list me-1"></i>Tüm Revize Taleplerim
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Revize Detay Alanı -->
                <div id="revisionDetailsArea" class="row mb-4" style="display: none;">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>Revize Detayları
                                    <small class="d-block" id="detailFileName"></small>
                                </h5>
                                <button type="button" class="btn btn-outline-light btn-sm" onclick="closeRevisionDetails()">
                                    <i class="fas fa-times"></i> Kapat
                                </button>
                            </div>
                            <div class="card-body" id="revisionDetailsContent">
                                <!-- Detaylar buraya yüklenecek -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarih Filtresi -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Tarih Filtresi
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <?php if (isset($_GET['detail_id'])): ?>
                                        <input type="hidden" name="detail_id" value="<?php echo htmlspecialchars($_GET['detail_id']); ?>">
                                    <?php endif; ?>
                                    <div class="col-md-3">
                                        <label for="date_from" class="form-label">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date_to" class="form-label">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i>Filtrele
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <a href="revisions.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-1"></i>Temizle
                                            </a>
                                        </div>
                                    </div>
                                </form>
                                <?php if ($dateFrom || $dateTo): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Aktif filtre: 
                                            <?php if ($dateFrom && $dateTo): ?>
                                                <?php echo date('d.m.Y', strtotime($dateFrom)); ?> - <?php echo date('d.m.Y', strtotime($dateTo)); ?>
                                            <?php elseif ($dateFrom): ?>
                                                <?php echo date('d.m.Y', strtotime($dateFrom)); ?> tarihinden itibaren
                                            <?php elseif ($dateTo): ?>
                                                <?php echo date('d.m.Y', strtotime($dateTo)); ?> tarihine kadar
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revize Sistemi Açıklaması -->
                <?php if (!$selectedRevision): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h5 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>Revize Sistemi Hakkında
                            </h5>
                            <p class="mb-2">
                                <strong>Revize sistemi,</strong> tamamlanmış dosyalarınızda değişiklik talep etmenizi sağlar. 
                                Revize talebi gönderdiğinizde, admin ekibimiz talebinizi inceleyerek uygun revizeyi gerçekleştirir.
                            </p>
                            <ul class="mb-0">
                                <li>Sadece <span class="badge bg-success">tamamlanmış</span> dosyalar için revize talep edebilirsiniz</li>
                                <li>Revize talepleri için ek ücret alınabilir</li>
                                <li>Revize geçmişinizi bu sayfadan takip edebilirsiniz</li>
                                <li>Bekleyen revize taleplerinizi iptal edemezsiniz</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Revize Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Revize Taleplerim
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($revisions)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-edit text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-muted">Revize talebi bulunamadı</h4>
                                <p class="text-muted">
                                    Henüz revize talebi göndermemişsiniz. Tamamlanmış dosyalarınız için revize talep edebilirsiniz.
                                </p>
                                <a href="files.php?status=completed" class="btn btn-primary">
                                    <i class="fas fa-folder me-1"></i>Tamamlanmış Dosyalarıma Git
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Dosya</th>
                                            <th>Durum</th>
                                            <th>Kredi</th>
                                            <th>Talep Tarihi</th>
                                            <th>İşlem Tarihi</th>
                                            <th>İşleyen Admin</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($revisions as $revision): ?>
                                            <tr>
                                                <td>
                                                    <code class="small"><?php echo substr($revision['id'], 0, 8); ?>...</code>
                                                </td>
                                                <td>
                                                    <i class="fas fa-file me-2"></i>
                                                    <strong><?php echo htmlspecialchars($revision['original_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = 'warning';
                                                    $statusText = 'Bekliyor';
                                                    $statusIcon = 'fas fa-clock';
                                                    
                                                    switch ($revision['status']) {
                                                        case 'completed':
                                                            $statusClass = 'success';
                                                            $statusText = 'Tamamlandı';
                                                            $statusIcon = 'fas fa-check-circle';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'danger';
                                                            $statusText = 'Reddedildi';
                                                            $statusIcon = 'fas fa-times-circle';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                                        <i class="<?php echo $statusIcon; ?> me-1"></i><?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($revision['credits_charged'] > 0): ?>
                                                        <span class="badge bg-warning"><?php echo $revision['credits_charged']; ?> Kredi</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Ücretsiz</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDate($revision['requested_at']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($revision['completed_at']): ?>
                                                        <small><?php echo formatDate($revision['completed_at']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($revision['admin_username']): ?>
                                                        <small><?php echo htmlspecialchars($revision['admin_username']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="revisions.php?detail_id=<?php echo urlencode($revision['id']); ?>" 
                                                           class="btn btn-outline-primary" title="Detaylar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="files.php?id=<?php echo $revision['upload_id']; ?>" 
                                                           class="btn btn-outline-secondary" title="Dosyayı Görüntüle">
                                                            <i class="fas fa-file"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Sayfalama -->
                            <?php if (count($revisions) >= $limit): ?>
                                <nav aria-label="Sayfa navigasyonu">
                                    <ul class="pagination justify-content-center">
                                        <?php
                                        $paginationParams = [];
                                        if ($dateFrom) $paginationParams[] = 'date_from=' . urlencode($dateFrom);
                                        if ($dateTo) $paginationParams[] = 'date_to=' . urlencode($dateTo);
                                        if (isset($_GET['detail_id'])) $paginationParams[] = 'detail_id=' . urlencode($_GET['detail_id']);
                                        $paginationQuery = !empty($paginationParams) ? '&' . implode('&', $paginationParams) : '';
                                        ?>
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $paginationQuery; ?>">Önceki</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $page; ?></span>
                                        </li>
                                        
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $paginationQuery; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hızlı İstatistikler -->
                <?php if (!$selectedRevision): ?>
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning">
                                    <?php
                                    $pendingCount = 0;
                                    foreach ($revisions as $rev) {
                                        if ($rev['status'] === 'pending') $pendingCount++;
                                    }
                                    echo $pendingCount;
                                    ?>
                                </h3>
                                <p class="card-text">Bekleyen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success">
                                    <?php
                                    $completedCount = 0;
                                    foreach ($revisions as $rev) {
                                        if ($rev['status'] === 'completed') $completedCount++;
                                    }
                                    echo $completedCount;
                                    ?>
                                </h3>
                                <p class="card-text">Tamamlanan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-danger">
                                    <?php
                                    $rejectedCount = 0;
                                    foreach ($revisions as $rev) {
                                        if ($rev['status'] === 'rejected') $rejectedCount++;
                                    }
                                    echo $rejectedCount;
                                    ?>
                                </h3>
                                <p class="card-text">Reddedilen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo count($revisions); ?></h3>
                                <p class="card-text">Toplam</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto refresh for pending revisions
        let pendingCount = <?php echo isset($pendingCount) ? $pendingCount : 0; ?>;
        if (pendingCount > 0) {
            setTimeout(function() {
                location.reload();
            }, 60000); // 60 saniye
        }
        
        // Sayfa yüklendiğinde seçili detaya scroll
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($selectedRevision): ?>
                // Detay alanına scroll
                document.querySelector('.card.border-primary').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
