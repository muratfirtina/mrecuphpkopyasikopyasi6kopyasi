<?php
/**
 * Mr ECU - Admin Dosya Yönetimi (GUID System)
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

// Dosya durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $uploadId = sanitize($_POST['upload_id']);
    $status = sanitize($_POST['status']);
    $adminNotes = sanitize($_POST['admin_notes']);
    
    // GUID format kontrolü
    if (!isValidUUID($uploadId)) {
        $error = 'Geçersiz dosya ID formatı.';
    } else {
        if ($fileManager->updateUploadStatus($uploadId, $status, $adminNotes)) {
            $success = 'Dosya durumu güncellendi.';
            
            // Log kaydı
            $user->logAction($_SESSION['user_id'], 'status_update', "Dosya #{$uploadId} durumu {$status} olarak güncellendi");
        } else {
            $error = 'Durum güncellenirken hata oluştu.';
        }
    }
}

// Yanıt dosyası yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['response_file'])) {
    $uploadId = sanitize($_POST['upload_id']);
    $creditsCharged = (float)$_POST['credits_charged'];
    $responseNotes = sanitize($_POST['response_notes']);
    
    // GUID format kontrolü
    if (!isValidUUID($uploadId)) {
        $error = 'Geçersiz dosya ID formatı.';
    } elseif ($creditsCharged < 0) {
        $error = 'Kredi miktarı negatif olamaz.';
    } else {
        $result = $fileManager->uploadResponseFile($uploadId, $_SESSION['user_id'], $_FILES['response_file'], $creditsCharged, $responseNotes);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
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

// Dosyaları getir (tarih filtresi ile)
$uploads = $fileManager->getAllUploads($page, $limit, $status, $dateFrom, $dateTo);

// Tek dosya detayı görüntüleme
$selectedUpload = null;
$responses = [];
if (isset($_GET['id'])) {
    $uploadId = sanitize($_GET['id']);
    
    // GUID format kontrolü
    if (isValidUUID($uploadId)) {
        $selectedUpload = $fileManager->getUploadById($uploadId);
        
        if ($selectedUpload) {
            $responses = $fileManager->getResponsesByUploadId($uploadId);
        }
    } else {
        $error = 'Geçersiz dosya ID formatı.';
    }
}

$pageTitle = 'Dosya Yönetimi';
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
                        <i class="fas fa-folder me-2"></i>Dosya Yönetimi
                        <small class="text-muted">(GUID System)</small>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>Yenile
                            </button>
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
                                    <a href="uploads.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Kapat
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Sol Kolon - Dosya ve Kullanıcı Bilgileri -->
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Kullanıcı Bilgileri</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Kullanıcı ID:</strong></td>
                                                    <td><code class="text-muted small"><?php echo $selectedUpload['user_id']; ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Kullanıcı:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['username']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Email:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['email']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Telefon:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['phone'] ?: '-'); ?></td>
                                                </tr>
                                            </table>
                                            
                                            <h6 class="text-muted mt-4">Dosya Bilgileri</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Dosya Adı:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['original_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Boyut:</strong></td>
                                                    <td><?php echo number_format($selectedUpload['file_size'] / 1024, 2); ?> KB</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tip:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUpload['file_type']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Yükleme Tarihi:</strong></td>
                                                    <td><?php echo formatDate($selectedUpload['upload_date']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        
                                        <!-- Sağ Kolon - Araç Bilgileri -->
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
                                    
                                    <!-- Notlar -->
                                    <?php if ($selectedUpload['upload_notes']): ?>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-muted">Kullanıcı Notları</h6>
                                                <div class="alert alert-light">
                                                    <?php echo nl2br(htmlspecialchars($selectedUpload['upload_notes'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Admin İşlemleri -->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <!-- Durum Güncelleme -->
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0">Durum Güncelle</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="upload_id" value="<?php echo htmlspecialchars($selectedUpload['id']); ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Mevcut Durum</label>
                                                            <div>
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
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Yeni Durum</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="pending" <?php echo $selectedUpload['status'] === 'pending' ? 'selected' : ''; ?>>Bekliyor</option>
                                                                <option value="processing" <?php echo $selectedUpload['status'] === 'processing' ? 'selected' : ''; ?>>İşleniyor</option>
                                                                <option value="completed" <?php echo $selectedUpload['status'] === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                                                                <option value="rejected" <?php echo $selectedUpload['status'] === 'rejected' ? 'selected' : ''; ?>>Reddedildi</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="admin_notes" class="form-label">Admin Notları</label>
                                                            <textarea class="form-control" name="admin_notes" rows="3"><?php echo htmlspecialchars($selectedUpload['admin_notes'] ?? ''); ?></textarea>
                                                        </div>
                                                        
                                                        <button type="submit" name="update_status" class="btn btn-primary">
                                                            <i class="fas fa-save me-1"></i>Güncelle
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <!-- Yanıt Dosyası Yükle -->
                                            <div class="card border-success">
                                                <div class="card-header bg-success text-white">
                                                    <h6 class="mb-0">Yanıt Dosyası Yükle</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST" enctype="multipart/form-data">
                                                        <input type="hidden" name="upload_id" value="<?php echo htmlspecialchars($selectedUpload['id']); ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="response_file" class="form-label">Dosya</label>
                                                            <input type="file" class="form-control" name="response_file" required
                                                                   accept=".bin,.hex,.ecu,.ori,.mod,.zip,.rar">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="credits_charged" class="form-label">Kredi Ücreti</label>
                                                            <input type="number" step="0.01" min="0" class="form-control" 
                                                                   name="credits_charged" value="<?php echo FILE_DOWNLOAD_COST; ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="response_notes" class="form-label">Notlar</label>
                                                            <textarea class="form-control" name="response_notes" rows="3" 
                                                                      placeholder="Dosya hakkında bilgi..."></textarea>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-upload me-1"></i>Yükle
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Yanıt Dosyaları -->
                                    <?php if (!empty($responses)): ?>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <h6 class="text-muted">Yanıt Dosyaları</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Dosya</th>
                                                                <th>Boyut</th>
                                                                <th>Kredi</th>
                                                                <th>Admin</th>
                                                                <th>Tarih</th>
                                                                <th>İndirildi</th>
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
                                                                        <span class="badge bg-warning"><?php echo $response['credits_charged']; ?></span>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($response['admin_username']); ?></td>
                                                                    <td><?php echo formatDate($response['upload_date']); ?></td>
                                                                    <td>
                                                                        <?php if ($response['downloaded']): ?>
                                                                            <span class="badge bg-success">Evet</span>
                                                                            <br><small><?php echo formatDate($response['download_date']); ?></small>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">Hayır</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <a href="download.php?id=<?php echo urlencode($response['id']); ?>&type=admin" 
                                                                           class="btn btn-sm btn-outline-primary" title="İndir">
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
                                    <?php endif; ?>
                                    
                                    <!-- Orijinal Dosya İndirme -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <h6 class="text-muted">Orijinal Dosya</h6>
                                            <div class="d-flex gap-2">
                                                <a href="download.php?id=<?php echo urlencode($selectedUpload['id']); ?>&type=original" 
                                                   class="btn btn-outline-secondary">
                                                    <i class="fas fa-download me-1"></i>Orijinal Dosyayı İndir
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
                            <a href="uploads.php" class="btn btn-outline-secondary <?php echo empty($status) ? 'active' : ''; ?>">
                                Tümü
                            </a>
                            <a href="uploads.php?status=pending" class="btn btn-outline-warning <?php echo $status === 'pending' ? 'active' : ''; ?>">
                                Bekleyen
                            </a>
                            <a href="uploads.php?status=processing" class="btn btn-outline-info <?php echo $status === 'processing' ? 'active' : ''; ?>">
                                İşleniyor
                            </a>
                            <a href="uploads.php?status=completed" class="btn btn-outline-success <?php echo $status === 'completed' ? 'active' : ''; ?>">
                                Tamamlanan
                            </a>
                            <a href="uploads.php?status=rejected" class="btn btn-outline-danger <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                                Reddedilen
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <small class="text-muted">Toplam: <?php echo count($uploads); ?> dosya</small>
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
                                            <a href="uploads.php<?php echo $status ? '?status=' . urlencode($status) : ''; ?>" class="btn btn-outline-secondary">
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
                            <?php if ($status): ?>
                                - <?php echo ucfirst($status); ?>
                            <?php endif; ?>
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
                                            <th>Tarih</th>
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
                                                    <strong><?php echo htmlspecialchars($upload['username']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($upload['email']); ?></small>
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
                                                    <?php if ($upload['response_count'] > 0): ?>
                                                        <br><small class="text-success">
                                                            <i class="fas fa-reply me-1"></i><?php echo $upload['response_count']; ?> yanıt
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
                                                        <a href="uploads.php?id=<?php echo urlencode($upload['id']); ?>" class="btn btn-outline-primary" title="Detaylar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="download.php?id=<?php echo urlencode($upload['id']); ?>&type=original" class="btn btn-outline-secondary" title="İndir">
                                                            <i class="fas fa-download"></i>
                                                        </a>
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
        // Auto refresh for pending status
        if (window.location.search.includes('status=pending')) {
            setTimeout(function() {
                location.reload();
            }, 60000); // 1 dakika
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
                    const uploadIdInputs = form.querySelectorAll('input[name="upload_id"]');
                    uploadIdInputs.forEach(function(input) {
                        if (input.value && !validateGUID(input.value)) {
                            e.preventDefault();
                            alert('Geçersiz GUID formatı: ' + input.value);
                            return false;
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
