<?php
/**
 * Mr ECU - Kullanıcı Dosyaları Sayfası (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/files.php');
}

// FileManager ve userId'yi önce tanımla
$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
$userId = $_SESSION['user_id'];

// GUID format kontrolü - User ID
if (!isValidUUID($userId)) {
    redirect('../logout.php');
}

// Revize talep işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_revision'])) {
    $uploadId = sanitize($_POST['upload_id']);
    $revisionNotes = sanitize($_POST['revision_notes']);
    
    // GUID format kontrolü
    if (!isValidUUID($uploadId)) {
        $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    } elseif (empty($revisionNotes)) {
        $_SESSION['error'] = 'Revize talebi için açıklama gereklidir.';
    } else {
        $result = $fileManager->requestRevision($uploadId, $userId, $revisionNotes);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // POST işleminden sonra redirect yap (PRG pattern)
    $redirectUrl = 'files.php';
    if (isset($_POST['upload_id']) && !empty($_POST['upload_id'])) {
        $redirectUrl .= '?id=' . urlencode($_POST['upload_id']);
    }
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'status=' . urlencode($_GET['status']);
    }
    if (isset($_GET['page']) && $_GET['page'] > 1) {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'page=' . (int)$_GET['page'];
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Session'dan mesajları al ve temizle
$error = '';
$success = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
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

// Dosyaları getir (tarih filtresi ile)
$uploads = $fileManager->getUserUploads($userId, $page, $limit, $status, $dateFrom, $dateTo);

// Tek dosya detayı görüntüleme
$selectedUpload = null;
$responses = [];
if (isset($_GET['id'])) {
    $uploadId = sanitize($_GET['id']);
    
    // GUID format kontrolü
    if (isValidUUID($uploadId)) {
        $selectedUpload = $fileManager->getUploadById($uploadId);
        
        // Kullanıcının dosyası mı kontrol et
        if (!$selectedUpload || $selectedUpload['user_id'] != $userId) {
            $selectedUpload = null;
        } else {
            $responses = $fileManager->getResponsesByUploadId($uploadId);
        }
    } else {
        $error = 'Geçersiz dosya ID formatı.';
    }
}

$pageTitle = 'Dosyalarım';
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
                        <i class="fas fa-folder me-2"></i>Dosyalarım
                        <small class="text-muted">(GUID System)</small>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="upload.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-upload me-1"></i>Yeni Dosya Yükle
                            </a>
                        </div>
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

                <?php if ($selectedUpload): ?>
                    <!-- Dosya Detayı Modal -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-file-alt me-2"></i>Dosya Detayları
                                        <br><small class="text-muted">ID: <?php echo htmlspecialchars($selectedUpload['id']); ?></small>
                                    </h5>
                                    <a href="files.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Kapat
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Dosya Bilgileri</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Dosya ID:</strong></td>
                                                    <td><code class="text-muted small"><?php echo $selectedUpload['id']; ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Dosya Adı:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['original_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Boyut:</strong></td>
                                                    <td><?php echo number_format($selectedUpload['file_size'] / 1024, 2); ?> KB</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Yükleme Tarihi:</strong></td>
                                                    <td><?php echo formatDate($selectedUpload['upload_date']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Durum:</strong></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        $statusText = $selectedUpload['status'];
                                                        
                                                        switch ($selectedUpload['status']) {
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
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Araç Bilgileri</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Marka ID:</strong></td>
                                                    <td><code class="text-muted small"><?php echo $selectedUpload['brand_id']; ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Model ID:</strong></td>
                                                    <td><code class="text-muted small"><?php echo $selectedUpload['model_id']; ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Marka/Model:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['brand_name'] . ' ' . $selectedUpload['model_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Model Yılı:</strong></td>
                                                    <td><?php echo $selectedUpload['year']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>ECU Tipi:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['ecu_type'] ?: '-'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Motor Kodu:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['engine_code'] ?: '-'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Şanzıman:</strong></td>
                                                    <td><?php echo $selectedUpload['gearbox_type']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Yakıt:</strong></td>
                                                    <td><?php echo $selectedUpload['fuel_type']; ?></td>
                                                </tr>
                                                <?php if ($selectedUpload['hp_power']): ?>
                                                <tr>
                                                    <td><strong>Güç:</strong></td>
                                                    <td><?php echo $selectedUpload['hp_power']; ?> HP</td>
                                                </tr>
                                                <?php endif; ?>
                                                <?php if ($selectedUpload['nm_torque']): ?>
                                                <tr>
                                                    <td><strong>Tork:</strong></td>
                                                    <td><?php echo $selectedUpload['nm_torque']; ?> Nm</td>
                                                </tr>
                                                <?php endif; ?>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <?php if ($selectedUpload['upload_notes']): ?>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-muted">Yükleme Notları</h6>
                                                <div class="alert alert-light">
                                                    <?php echo nl2br(htmlspecialchars($selectedUpload['upload_notes'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($selectedUpload['admin_notes']): ?>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-muted">Admin Notları</h6>
                                                <div class="alert alert-info">
                                                    <?php echo nl2br(htmlspecialchars($selectedUpload['admin_notes'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Yanıt Dosyaları -->
                                    <?php if (!empty($responses)): ?>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <h6 class="text-muted">İşlenmiş Dosyalar</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Dosya</th>
                                                                <th>Boyut</th>
                                                                <th>Kredi</th>
                                                                <th>Tarih</th>
                                                                <th>İşlem</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($responses as $response): ?>
                                                                <tr>
                                                                    <td><code class="small"><?php echo substr($response['id'], 0, 8); ?>...</code></td>
                                                                    <td>
                                                                        <i class="fas fa-file text-success me-2"></i>
                                                                        <?php echo htmlspecialchars($response['original_name']); ?>
                                                                    </td>
                                                                    <td><?php echo number_format($response['file_size'] / 1024, 2); ?> KB</td>
                                                                    <td>
                                                                        <span class="badge bg-warning"><?php echo $response['credits_charged']; ?> Kredi</span>
                                                                    </td>
                                                                    <td><?php echo formatDate($response['upload_date']); ?></td>
                                                                    <td>
                                                                        <a href="download.php?id=<?php echo urlencode($response['id']); ?>" class="btn btn-sm btn-success">
                                                                            <i class="fas fa-download me-1"></i>İndir
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Revize Dosyaları -->
                                    <?php 
                                    $revisionFiles = $fileManager->getRevisionFilesByUploadId($selectedUpload['id']);
                                    if (!empty($revisionFiles)): 
                                    ?>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <h6 class="text-muted">
                                                    <i class="fas fa-edit me-2"></i>Revize Edilmiş Dosyalar
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Dosya</th>
                                                                <th>Boyut</th>
                                                                <th>Revize Durumu</th>
                                                                <th>Tarih</th>
                                                                <th>İşlem</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($revisionFiles as $revFile): ?>
                                                                <tr>
                                                                    <td><code class="small"><?php echo substr($revFile['id'], 0, 8); ?>...</code></td>
                                                                    <td>
                                                                        <i class="fas fa-file text-info me-2"></i>
                                                                        <strong><?php echo htmlspecialchars($revFile['original_name']); ?></strong>
                                                                    </td>
                                                                    <td><?php echo number_format($revFile['file_size'] / 1024, 2); ?> KB</td>
                                                                    <td>
                                                                        <?php
                                                                        $statusClass = 'warning';
                                                                        $statusText = 'Bekliyor';
                                                                        
                                                                        switch ($revFile['revision_status']) {
                                                                            case 'completed':
                                                                                $statusClass = 'success';
                                                                                $statusText = 'Tamam';
                                                                                break;
                                                                            case 'rejected':
                                                                                $statusClass = 'danger';
                                                                                $statusText = 'Reddedildi';
                                                                                break;
                                                                        }
                                                                        ?>
                                                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                                    </td>
                                                                    <td><?php echo formatDate($revFile['upload_date']); ?></td>
                                                                    <td>
                                                                        <a href="download-revision.php?id=<?php echo urlencode($revFile['id']); ?>" class="btn btn-sm btn-info">
                                                                            <i class="fas fa-download me-1"></i>Revize İndir
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Revize Sistemi -->
                                    <?php if ($selectedUpload['status'] === 'completed'): ?>
                                        <?php 
                                        $revisions = $fileManager->getRevisionsByUploadId($selectedUpload['id']);
                                        $hasPendingRevision = false;
                                        foreach ($revisions as $rev) {
                                            if ($rev['status'] === 'pending') {
                                                $hasPendingRevision = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <!-- Revize Talep Et -->
                                                <div class="card border-warning">
                                                    <div class="card-header bg-warning text-dark">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-edit me-2"></i>Revize Talep Et
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php if ($hasPendingRevision): ?>
                                                            <div class="alert alert-info">
                                                                <i class="fas fa-clock me-2"></i>Bu dosya için zaten bekleyen bir revize talebi var.
                                                            </div>
                                                        <?php else: ?>
                                                            <form method="POST" id="revisionForm">
                                                                <input type="hidden" name="upload_id" value="<?php echo htmlspecialchars($selectedUpload['id']); ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="revision_notes" class="form-label">Revize Açıklaması</label>
                                                                    <textarea class="form-control" name="revision_notes" id="revision_notes" rows="3" 
                                                                              placeholder="Lütfen hangi değişiklikler istediğinizi detaylı olarak açıklayın..." required></textarea>
                                                                    <small class="form-text text-muted">
                                                                        Revize talebi için ek ücret alınabilir.
                                                                    </small>
                                                                </div>
                                                                
                                                                <button type="submit" name="request_revision" class="btn btn-warning">
                                                                    <i class="fas fa-paper-plane me-1"></i>Revize Talep Et
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <!-- Revize Geçmişi -->
                                                <div class="card border-info">
                                                    <div class="card-header bg-info text-white">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-history me-2"></i>Revize Geçmişi
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php if (empty($revisions)): ?>
                                                            <p class="text-muted mb-0">Henüz revize talebi yok.</p>
                                                        <?php else: ?>
                                                            <div class="timeline">
                                                                <?php foreach ($revisions as $revision): ?>
                                                                    <div class="timeline-item mb-3">
                                                                        <div class="d-flex">
                                                                            <div class="flex-shrink-0">
                                                                                <?php
                                                                                $statusIcon = 'fas fa-clock text-warning';
                                                                                $statusText = 'Bekliyor';
                                                                                
                                                                                switch ($revision['status']) {
                                                                                    case 'completed':
                                                                                        $statusIcon = 'fas fa-check-circle text-success';
                                                                                        $statusText = 'Tamamlandı';
                                                                                        break;
                                                                                    case 'rejected':
                                                                                        $statusIcon = 'fas fa-times-circle text-danger';
                                                                                        $statusText = 'Reddedildi';
                                                                                        break;
                                                                                }
                                                                                ?>
                                                                                <i class="<?php echo $statusIcon; ?>"></i>
                                                                            </div>
                                                                            <div class="flex-grow-1 ms-3">
                                                                                <div class="fw-bold"><?php echo $statusText; ?></div>
                                                                                <small class="text-muted">
                                                                                    ID: <code><?php echo substr($revision['id'], 0, 8); ?>...</code>
                                                                                    <br><?php echo formatDate($revision['requested_at']); ?>
                                                                                </small>
                                                                                <?php if ($revision['status'] !== 'pending'): ?>
                                                                                    <br><small class="text-muted">Admin: <?php echo htmlspecialchars($revision['admin_username'] ?: 'Bilinmiyor'); ?></small>
                                                                                <?php endif; ?>
                                                                                
                                                                                <!-- Talep Notları -->
                                                                                <div class="mt-1">
                                                                                    <small><strong>Talep:</strong> <?php echo nl2br(htmlspecialchars($revision['request_notes'])); ?></small>
                                                                                </div>
                                                                                
                                                                                <!-- Admin Notları -->
                                                                                <?php if ($revision['admin_notes']): ?>
                                                                                    <div class="mt-1">
                                                                                        <small><strong>Yanıt:</strong> <?php echo nl2br(htmlspecialchars($revision['admin_notes'])); ?></small>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                                
                                                                                <!-- Kredi -->
                                                                                <?php if ($revision['credits_charged'] > 0): ?>
                                                                                    <div class="mt-1">
                                                                                        <span class="badge bg-warning"><?php echo $revision['credits_charged']; ?> Kredi</span>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filtreler -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="btn-group" role="group">
                            <a href="files.php" class="btn btn-outline-secondary <?php echo empty($status) ? 'active' : ''; ?>">
                                Tümü
                            </a>
                            <a href="files.php?status=pending" class="btn btn-outline-warning <?php echo $status === 'pending' ? 'active' : ''; ?>">
                                Bekleyen
                            </a>
                            <a href="files.php?status=processing" class="btn btn-outline-info <?php echo $status === 'processing' ? 'active' : ''; ?>">
                                İşleniyor
                            </a>
                            <a href="files.php?status=completed" class="btn btn-outline-success <?php echo $status === 'completed' ? 'active' : ''; ?>">
                                Tamamlanan
                            </a>
                            <a href="files.php?status=rejected" class="btn btn-outline-danger <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                                Reddedilen
                            </a>
                        </div>
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
                                            <a href="files.php<?php echo $status ? '?status=' . urlencode($status) : ''; ?>" class="btn btn-outline-secondary">
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

                <!-- Dosya Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Dosya Listesi
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($uploads)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-muted">Dosya bulunamadı</h4>
                                <p class="text-muted">
                                    <?php if ($status): ?>
                                        Bu durumda dosya bulunmuyor.
                                    <?php else: ?>
                                        Henüz dosya yüklenmemiş.
                                    <?php endif; ?>
                                </p>
                                <a href="upload.php" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i>İlk Dosyanı Yükle
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Dosya</th>
                                            <th>Araç</th>
                                            <th>ECU/Motor</th>
                                            <th>Durum</th>
                                            <th>Yükleme Tarihi</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($uploads as $upload): ?>
                                            <tr>
                                                <td>
                                                    <code class="small"><?php echo substr($upload['id'], 0, 8); ?>...</code>
                                                    <br><small class="text-muted">GUID</small>
                                                </td>
                                                <td>
                                                    <i class="fas fa-file me-2"></i>
                                                    <strong><?php echo htmlspecialchars($upload['original_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo number_format($upload['file_size'] / 1024, 2); ?> KB</small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($upload['brand_name'] . ' ' . $upload['model_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo $upload['year']; ?> • <?php echo $upload['fuel_type']; ?></small>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php if ($upload['ecu_type']): ?>
                                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($upload['ecu_type']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($upload['engine_code']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($upload['engine_code']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = 'secondary';
                                                    $statusText = $upload['status'];
                                                    $statusIcon = 'fas fa-circle';
                                                    
                                                    switch ($upload['status']) {
                                                        case 'pending':
                                                            $statusClass = 'warning';
                                                            $statusText = 'Bekliyor';
                                                            $statusIcon = 'fas fa-clock';
                                                            break;
                                                        case 'processing':
                                                            $statusClass = 'info';
                                                            $statusText = 'İşleniyor';
                                                            $statusIcon = 'fas fa-spinner';
                                                            break;
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
                                                    <?php if (isset($upload['has_response']) && $upload['has_response'] > 0): ?>
                                                        <br><small class="text-success">
                                                            <i class="fas fa-download me-1"></i>İndirilebilir
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo formatDate($upload['upload_date']); ?>
                                                    <?php if ($upload['processed_date']): ?>
                                                        <br><small class="text-muted">
                                                            İşlendi: <?php echo formatDate($upload['processed_date']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="files.php?id=<?php echo urlencode($upload['id']); ?>" class="btn btn-outline-primary" title="Detaylar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (isset($upload['has_response']) && $upload['has_response'] > 0): ?>
                                                            <a href="download.php?id=<?php echo urlencode($upload['response_id']); ?>" class="btn btn-outline-success" title="İndir">
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

                            <!-- Sayfalama -->
                            <?php if (count($uploads) >= $limit): ?>
                                <nav aria-label="Sayfa navigasyonu">
                                    <ul class="pagination justify-content-center">
                                        <?php
                                        $paginationParams = [];
                                        if ($status) $paginationParams[] = 'status=' . urlencode($status);
                                        if ($dateFrom) $paginationParams[] = 'date_from=' . urlencode($dateFrom);
                                        if ($dateTo) $paginationParams[] = 'date_to=' . urlencode($dateTo);
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
        // GUID validation function
        function isValidGUID(guid) {
            const guidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            return guidPattern.test(guid);
        }
        
        // Form validation with GUID checks
        document.addEventListener('DOMContentLoaded', function() {
            const revisionForm = document.getElementById('revisionForm');
            if (revisionForm) {
                revisionForm.addEventListener('submit', function(e) {
                    const uploadId = this.querySelector('input[name="upload_id"]').value;
                    
                    if (!isValidGUID(uploadId)) {
                        e.preventDefault();
                        alert('Geçersiz dosya GUID formatı: ' + uploadId);
                        return false;
                    }
                });
            }
        });
        
        // Auto refresh for pending files
        if (window.location.search.includes('status=pending') || window.location.search === '') {
            setTimeout(function() {
                location.reload();
            }, 30000); // 30 saniye
        }
    </script>
</body>
</html>
