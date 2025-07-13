<?php
/**
 * Mr ECU - Admin Dosya Yönetimi - DÜZELTILMIŞ VERSION
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php?error=access_denied');
}

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

// Admin kontrolü otomatik yapılır
$fileManager = new FileManager($pdo);
$user = new User($pdo);
$error = '';
$success = '';

// Dosya durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $uploadId = sanitize($_POST['upload_id']);
    $status = sanitize($_POST['status']);
    $adminNotes = sanitize($_POST['admin_notes']);
    
    if (!isValidUUID($uploadId)) {
        $error = 'Geçersiz dosya ID formatı.';
    } else {
        if ($fileManager->updateUploadStatus($uploadId, $status, $adminNotes)) {
            $success = 'Dosya durumu başarıyla güncellendi.';
            $user->logAction($_SESSION['user_id'], 'status_update', "Dosya #{$uploadId} durumu {$status} olarak güncellendi");
        } else {
            $error = 'Durum güncellenirken hata oluştu.';
        }
    }
}

// Yanıt dosyası yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['response_file'])) {
    $uploadId = sanitize($_POST['upload_id']);
    $creditsCharged = floatval($_POST['credits_charged']);
    $responseNotes = sanitize($_POST['response_notes']);
    
    if (!isValidUUID($uploadId)) {
        $error = 'Geçersiz dosya ID formatı.';
    } elseif ($creditsCharged < 0) {
        $error = 'Kredi miktarı negatif olamaz.';
    } else {
        $result = $fileManager->uploadResponseFile($uploadId, $_FILES['response_file'], $creditsCharged, $responseNotes);
        
        if ($result['success']) {
            $success = $result['message'];
            $user->logAction($_SESSION['user_id'], 'response_upload', "Yanıt dosyası yüklendi: {$uploadId}");
        } else {
            $error = $result['message'];
        }
    }
}

// Filtreleme ve arama parametreleri
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$brand = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'upload_date';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// Dosyaları getir
try {
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $whereClause .= " AND (u.original_name LIKE ? OR users.username LIKE ? OR users.email LIKE ? OR u.plate LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if ($status) {
        $whereClause .= " AND u.status = ?";
        $params[] = $status;
    }
    
    if ($brand) {
        $whereClause .= " AND b.id = ?";
        $params[] = $brand;
    }
    
    if ($dateFrom) {
        $whereClause .= " AND DATE(u.upload_date) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereClause .= " AND DATE(u.upload_date) <= ?";
        $params[] = $dateTo;
    }
    
    // Toplam dosya sayısı
    $countQuery = "
        SELECT COUNT(*) 
        FROM file_uploads u
        LEFT JOIN users ON u.user_id = users.id
        LEFT JOIN brands b ON u.brand_id = b.id
        $whereClause
    ";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUploads = $stmt->fetchColumn();
    
    // Dosyaları getir
    $query = "
        SELECT u.*, 
               users.username, users.email, users.first_name, users.last_name,
               b.name as brand_name,
               m.name as model_name
        FROM file_uploads u
        LEFT JOIN users ON u.user_id = users.id
        LEFT JOIN brands b ON u.brand_id = b.id
        LEFT JOIN models m ON u.model_id = m.id
        $whereClause 
        ORDER BY u.$sortBy $sortOrder 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $uploads = $stmt->fetchAll();
    
    $totalPages = ceil($totalUploads / $limit);
} catch(PDOException $e) {
    $uploads = [];
    $totalUploads = 0;
    $totalPages = 0;
}

// İstatistikler
try {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(COUNT(*), 0) as total_uploads,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
            COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing_count,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
            COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected_count,
            COALESCE(SUM(CASE WHEN upload_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END), 0) as today_uploads,
            COALESCE(AVG(file_size), 0) as avg_file_size
        FROM file_uploads
    ");
    $stats = $stmt->fetch();
    
    // Null değerleri 0 ile değiştir
    $stats = array_map(function($value) {
        return $value === null ? 0 : $value;
    }, $stats);
    
} catch(PDOException $e) {
    $stats = ['total_uploads' => 0, 'pending_count' => 0, 'processing_count' => 0, 'completed_count' => 0, 'rejected_count' => 0, 'today_uploads' => 0, 'avg_file_size' => 0];
}

// Markalar listesi (filtre için)
try {
    $stmt = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");
    $brands = $stmt->fetchAll();
} catch(PDOException $e) {
    $brands = [];
}

// Sayfa bilgileri
$pageTitle = 'Dosya Yüklemeleri';
$pageDescription = 'Kullanıcı dosya yüklemelerini yönetin ve işleyin.';
$pageIcon = 'fas fa-upload';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo safe_number_format($stats['total_uploads']); ?></div>
                    <div class="stat-label">Toplam Dosya</div>
                    <small class="text-success">+<?php echo $stats['today_uploads']; ?> bugün</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-file text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo safe_number_format($stats['pending_count']); ?></div>
                    <div class="stat-label">Bekleyen</div>
                    <small class="text-muted">İşlem bekliyor</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="fas fa-clock text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo safe_number_format($stats['processing_count']); ?></div>
                    <div class="stat-label">İşleniyor</div>
                    <small class="text-muted">Aktif işlemde</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="fas fa-cogs text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo safe_number_format($stats['completed_count']); ?></div>
                    <div class="stat-label">Tamamlanan</div>
                    <small class="text-danger"><?php echo safe_number_format($stats['rejected_count']); ?> reddedilen</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="fas fa-check-circle text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtre ve Arama -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="search" class="form-label">
                    <i class="fas fa-search me-1"></i>Arama
                </label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Dosya adı, kullanıcı adı, plaka...">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">
                    <i class="fas fa-filter me-1"></i>Durum
                </label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>İşleniyor</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Tamamlanan</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="brand" class="form-label">
                    <i class="fas fa-car me-1"></i>Marka
                </label>
                <select class="form-select" id="brand" name="brand">
                    <option value="">Tüm Markalar</option>
                    <?php foreach ($brands as $brandOption): ?>
                        <option value="<?php echo $brandOption['id']; ?>" 
                                <?php echo $brand === $brandOption['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($brandOption['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">
                    <i class="fas fa-calendar me-1"></i>Başlangıç
                </label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">
                    <i class="fas fa-calendar me-1"></i>Bitiş
                </label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="col-md-1">
                <div class="d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="uploads.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Dosya Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-file-upload me-2"></i>
            Dosya Yüklemeleri (<?php echo $totalUploads; ?> dosya)
        </h5>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($uploads)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-upload fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($search || $status || $brand || $dateFrom || $dateTo): ?>
                        Filtreye uygun dosya bulunamadı
                    <?php else: ?>
                        Henüz dosya yüklenmemiş
                    <?php endif; ?>
                </h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover mb-0" id="uploadsTable">
                    <thead>
                        <tr>
                            <th>Dosya Bilgileri</th>
                            <th>Kullanıcı</th>
                            <th>Araç Bilgileri</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploads as $upload): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="file-icon me-3">
                                            <i class="fas fa-file-alt fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-truncate" style="max-width: 200px;" 
                                                title="<?php echo htmlspecialchars($upload['original_name']); ?>">
                                                <?php echo htmlspecialchars($upload['original_name']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo formatFileSize($upload['file_size'] ?? 0); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($upload['first_name'] . ' ' . $upload['last_name']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            @<?php echo htmlspecialchars($upload['username']); ?>
                                        </small><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($upload['email']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($upload['brand_name'] ?? 'Bilinmiyor'); ?></strong><br>
                                        <small class="text-muted">
                                            <?php 
                                            $modelDisplay = htmlspecialchars($upload['model_name'] ?? 'Model belirtilmemiş');
                                            if (!empty($upload['year'])) {
                                                $modelDisplay .= ' (' . $upload['year'] . ')';
                                            }
                                            echo $modelDisplay;
                                            ?>
                                        </small>
                                        <?php if (!empty($upload['plate'])): ?>
                                            <br><small class="text-primary">
                                                <i class="fas fa-id-card me-1"></i><?php echo strtoupper(htmlspecialchars($upload['plate'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $statusText = [
                                        'pending' => 'Bekliyor',
                                        'processing' => 'İşleniyor',
                                        'completed' => 'Tamamlandı',
                                        'rejected' => 'Reddedildi'
                                    ];
                                    $statusIcon = [
                                        'pending' => 'clock',
                                        'processing' => 'cogs',
                                        'completed' => 'check-circle',
                                        'rejected' => 'times-circle'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?> d-flex align-items-center" style="width: fit-content;">
                                        <i class="fas fa-<?php echo $statusIcon[$upload['status']] ?? 'question'; ?> me-1"></i>
                                        <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                                    </span>
                                    
                                    <?php if (!empty($upload['admin_notes'])): ?>
                                        <div class="mt-1">
                                            <small class="text-muted" title="<?php echo htmlspecialchars($upload['admin_notes']); ?>">
                                                <i class="fas fa-comment fa-sm"></i> Admin notu
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo date('d.m.Y', strtotime($upload['upload_date'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($upload['upload_date'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm" style="width: 140px;">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="window.location.href='file-detail.php?id=<?php echo $upload['id']; ?>'">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </button>
                                        
                                        <?php if ($upload['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-outline-success btn-sm" 
                                                    onclick="processFile('<?php echo $upload['id']; ?>')">
                                                <i class="fas fa-play me-1"></i>İşle
                                            </button>
                                        <?php endif; ?>
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

<script>
// Process file function
function processFile(uploadId) {
    console.log('ProcessFile başlatıldı - Upload ID:', uploadId);
    
    if (confirm('Bu dosyayı işleme almak istediğinizden emin misiniz?')) {
        console.log('Kullanıcı onayladı, durum güncelleniyor...');
        
        // Loading indicator göster
        var button = event.target;
        var originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Yükleniyor...';
        button.disabled = true;
        
        updateFileStatus(uploadId, 'processing', 'Dosya işleme alındı', true);
    }
}

// Update file status function
function updateFileStatus(uploadId, status, notes, redirectToDetail) {
    notes = notes || '';
    redirectToDetail = redirectToDetail || false;
    
    console.log('UpdateFileStatus başlatıldı:', uploadId, status, notes, redirectToDetail);
    
    var formData = new FormData();
    formData.append('update_status', '1');
    formData.append('upload_id', uploadId);
    formData.append('status', status);
    formData.append('admin_notes', notes);
    
    console.log('AJAX isteği gönderiliyor...');
    
    fetch('uploads.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        console.log('Response alındı:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error('HTTP hatası: ' + response.status);
        }
        
        return response.text();
    })
    .then(function(data) {
        console.log('Response data:', data.substring(0, 200) + '...');
        
        if (redirectToDetail) {
            console.log('Detay sayfasına yönlendiriliyor...');
            window.location.href = 'file-detail.php?id=' + uploadId;
        } else {
            console.log('Sayfa yenileniyor...');
            location.reload();
        }
    })
    .catch(function(error) {
        console.error('UpdateFileStatus hatası:', error);
        alert('Güncelleme sırasında hata oluştu: ' + error.message);
        
        // Button'ı eski haline döndür
        var buttons = document.querySelectorAll('button:disabled');
        for (var i = 0; i < buttons.length; i++) {
            var btn = buttons[i];
            if (btn.innerHTML.includes('Yükleniyor')) {
                btn.innerHTML = '<i class="fas fa-play me-1"></i>İşle';
                btn.disabled = false;
            }
        }
    });
}
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
