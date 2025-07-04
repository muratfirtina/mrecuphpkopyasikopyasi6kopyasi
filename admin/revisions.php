<?php
/**
 * Mr ECU - Admin Revize Yönetimi (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$fileManager = new FileManager($pdo);
$user = new User($pdo);
$error = '';
$success = '';

// Revize talebini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_revision'])) {
    $revisionId = sanitize($_POST['revision_id']); // GUID olarak al
    $status = sanitize($_POST['status']);
    $adminNotes = sanitize($_POST['admin_notes']);
    $creditsCharged = (float)$_POST['credits_charged'];
    
    // GUID format kontrolü
    if (!isValidUUID($revisionId)) {
        $error = 'Geçersiz revize ID formatı.';
    } elseif (!in_array($status, ['completed', 'rejected'])) {
        $error = 'Geçersiz durum seçimi.';
    } elseif ($creditsCharged < 0) {
        $error = 'Kredi miktarı negatif olamaz.';
    } else {
        // Önce revize dosyası yükle (eğer varsa)
        $revisionFileId = null;
        if (isset($_FILES['revision_file']) && $_FILES['revision_file']['error'] === UPLOAD_ERR_OK) {
            $fileResult = $fileManager->uploadRevisionFile($revisionId, $_SESSION['user_id'], $_FILES['revision_file'], $adminNotes);
            
            if ($fileResult['success']) {
                $revisionFileId = $fileResult['file_id'];
                $success = 'Revize dosyası başarıyla yüklendi. ';
            } else {
                $error = 'Revize dosyası yükleme hatası: ' . $fileResult['message'];
            }
        }
        
        // Revize talebini işle
        if (!$error) {
            $result = $fileManager->processRevision($revisionId, $_SESSION['user_id'], $status, $adminNotes, $creditsCharged);
            
            if ($result['success']) {
                $success .= $result['message'];
                // İşlem başarılıysa detail_id'yi kaldır
                redirect('revisions.php?success=1');
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

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

// Revize taleplerini getir (tarih filtresi ile)
$revisions = $fileManager->getAllRevisions($page, $limit, $status, $dateFrom, $dateTo);

// Seçili revize detayı
$selectedRevision = null;
$revisionFiles = [];
if (isset($_GET['detail_id'])) {
    $revisionId = sanitize($_GET['detail_id']);
    
    if (isValidUUID($revisionId)) {
        // Tüm revize taleplerini al ve eşleşen revizeyi bul
        $allRevisions = $fileManager->getAllRevisions(1, 1000); // Büyük limit ile tümünü al
        
        foreach ($allRevisions as $revision) {
            if ($revision['id'] === $revisionId) {
                $selectedRevision = $revision;
                
                // Revize dosyalarını al
                $revisionFiles = $fileManager->getRevisionFilesByRevisionId($revisionId);
                break;
            }
        }
        
        // Eğer revize bulunamadıysa hata mesajı
        if (!$selectedRevision) {
            $error = 'Revize talebi bulunamadı veya geçersiz ID.';
        }
    } else {
        $error = 'Geçersiz revize ID formatı.';
    }
}

$pageTitle = 'Revize Yönetimi';
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
                        <i class="fas fa-edit me-2"></i>Revize Yönetimi
                        <small class="text-muted">(GUID System)</small>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="uploads.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-upload me-1"></i>Dosya Yönetimi
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Seçili Revize Detayı -->
                <?php if ($selectedRevision): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-edit me-2"></i>Revize Detayları (Admin)
                                        <small class="d-block mt-1 opacity-75"><?php echo htmlspecialchars($selectedRevision['original_name']); ?></small>
                                    </h5>
                                    <a href="revisions.php" class="btn btn-light btn-sm">
                                        <i class="fas fa-times me-1"></i>Kapat
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Kullanıcı Bilgileri</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Kullanıcı:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedRevision['username']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Email:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedRevision['email']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Dosya:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedRevision['original_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Araç:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedRevision['brand_name'] . ' ' . $selectedRevision['model_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Talep Tarihi:</strong></td>
                                                    <td><?php echo formatDate($selectedRevision['requested_at']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Revize Bilgileri</h6>
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
                                                <?php if ($selectedRevision['completed_at']): ?>
                                                <tr>
                                                    <td><strong>Tamamlanma:</strong></td>
                                                    <td><?php echo formatDate($selectedRevision['completed_at']); ?></td>
                                                </tr>
                                                <?php endif; ?>
                                                <?php if ($selectedRevision['credits_charged'] > 0): ?>
                                                <tr>
                                                    <td><strong>Ücret:</strong></td>
                                                    <td><span class="badge bg-warning"><?php echo $selectedRevision['credits_charged']; ?> Kredi</span></td>
                                                </tr>
                                                <?php endif; ?>
                                            </table>
                                            
                                            <h6 class="text-muted mt-3">Kullanıcı Talebi</h6>
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
                                                            <i class="fas fa-file-download me-2"></i>Yüklenmiş Revize Dosyaları
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Dosya</th>
                                                                        <th>Boyut</th>
                                                                        <th>Yükleyen</th>
                                                                        <th>Tarih</th>
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
                                                                            <td><?php echo htmlspecialchars($revFile['admin_username'] ?: 'Bilinmiyor'); ?></td>
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
                                                                                <a href="../user/download-revision.php?id=<?php echo urlencode($revFile['id']); ?>&admin=1" 
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
                                    
                                    <!-- Revize İşleme Formu -->
                                    <?php if ($selectedRevision['status'] === 'pending'): ?>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card border-warning">
                                                    <div class="card-header bg-warning text-dark">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-cogs me-2"></i>Revize Talebini İşle
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="revision_id" value="<?php echo htmlspecialchars($selectedRevision['id']); ?>">
                                                            
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label for="status" class="form-label">Durum</label>
                                                                        <select class="form-select" name="status" id="status" required>
                                                                            <option value="">Seçin...</option>
                                                                            <option value="completed">Tamamlandı</option>
                                                                            <option value="rejected">Reddedildi</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label for="credits_charged" class="form-label">Ücret (Kredi)</label>
                                                                        <input type="number" class="form-control" name="credits_charged" id="credits_charged" 
                                                                               min="0" step="0.01" value="0" placeholder="0.00">
                                                                        <small class="form-text text-muted">
                                                                            Revize için alınacak kredi miktarı (0 = ücretsiz)
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label for="revision_file" class="form-label">
                                                                            <i class="fas fa-upload me-2"></i>Revize Edilmiş Dosya
                                                                        </label>
                                                                        <input type="file" class="form-control" name="revision_file" id="revision_file"
                                                                               accept=".zip,.rar,.7z,.bin,.hex,.ecu,.ori">
                                                                        <small class="form-text text-muted">
                                                                            Revize edilmiş dosyayı yükleyin
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="admin_notes" class="form-label">Admin Notları</label>
                                                                <textarea class="form-control" name="admin_notes" id="admin_notes" rows="3" 
                                                                          placeholder="Revize hakkındaki açıklamalarınızı yazın..."></textarea>
                                                            </div>
                                                            
                                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                                <button type="submit" name="process_revision" class="btn btn-primary">
                                                                    <i class="fas fa-check me-1"></i>Revize Talebini İşle
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Hızlı İşlemler -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="d-flex gap-2">
                                                <a href="uploads.php?id=<?php echo $selectedRevision['upload_id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-file me-1"></i>Orijinal Dosyayı Görüntüle
                                                </a>
                                                <a href="revisions.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-list me-1"></i>Tüm Revize Talepleri
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filtreler -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="btn-group" role="group">
                            <a href="revisions.php" class="btn btn-outline-secondary <?php echo empty($status) ? 'active' : ''; ?>">
                                Tümü
                            </a>
                            <a href="revisions.php?status=pending" class="btn btn-outline-warning <?php echo $status === 'pending' ? 'active' : ''; ?>">
                                Bekleyen
                            </a>
                            <a href="revisions.php?status=completed" class="btn btn-outline-success <?php echo $status === 'completed' ? 'active' : ''; ?>">
                                Tamamlanan
                            </a>
                            <a href="revisions.php?status=rejected" class="btn btn-outline-danger <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                                Reddedilen
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <small class="text-muted">Toplam: <?php echo count($revisions); ?> revize talebi</small>
                    </div>
                </div>

                <!-- Tarih Filtresi -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Tarih Filtresi
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <?php if ($status): ?>
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                                    <?php endif; ?>
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
                                            <a href="revisions.php<?php echo $status ? '?status=' . urlencode($status) : ''; ?>" class="btn btn-outline-secondary">
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

                <!-- Revize Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Revize Talepleri
                            <?php if ($status): ?>
                                - <?php echo ucfirst($status); ?>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($revisions)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-muted">Revize talebi bulunamadı</h4>
                                <p class="text-muted">
                                    <?php if ($status): ?>
                                        Bu durumda revize talebi bulunmuyor.
                                    <?php else: ?>
                                        Henüz revize talebi gelmemiş.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Kullanıcı</th>
                                            <th>Dosya</th>
                                            <th>Araç</th>
                                            <th>Durum</th>
                                            <th>Kredi</th>
                                            <th>Talep Tarihi</th>
                                            <th>İşlem Tarihi</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($revisions as $revision): ?>
                                            <tr>
                                                <td>
                                                    <code class="small"><?php echo substr($revision['id'], 0, 8); ?>...</code>
                                                    <br><small class="text-muted">GUID</small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($revision['username']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($revision['email']); ?></small>
                                                </td>
                                                <td>
                                                    <i class="fas fa-file me-2"></i>
                                                    <strong><?php echo htmlspecialchars($revision['original_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($revision['brand_name'] . ' ' . $revision['model_name']); ?></strong>
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
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="revisions.php?detail_id=<?php echo urlencode($revision['id']); ?>" 
                                                           class="btn btn-outline-primary" title="Detaylar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="uploads.php?id=<?php echo urlencode($revision['upload_id']); ?>" 
                                                           class="btn btn-outline-secondary" title="Orijinal Dosya">
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
                                        if ($status) $paginationParams[] = 'status=' . urlencode($status);
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
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto refresh for pending revisions
        if (window.location.search.includes('status=pending') || window.location.search === '') {
            setTimeout(function() {
                location.reload();
            }, 60000); // 60 saniye
        }
        
        // GUID validation for forms
        function validateGUID(input) {
            const guidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            return guidPattern.test(input);
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const revisionIdInputs = form.querySelectorAll('input[name="revision_id"]');
                    revisionIdInputs.forEach(function(input) {
                        if (input.value && !validateGUID(input.value)) {
                            e.preventDefault();
                            alert('Geçersiz GUID formatı: ' + input.value);
                            return false;
                        }
                    });
                });
            });
            
            // Sayfa yüklendiğinde seçili detaya scroll
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
