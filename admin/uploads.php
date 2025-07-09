<?php
/**
 * Mr ECU - Admin Dosya Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

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

// Toplu işlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = sanitize($_POST['bulk_action']);
    $selectedUploads = $_POST['selected_uploads'] ?? [];
    
    if (empty($selectedUploads)) {
        $error = 'Lütfen işlem yapmak için dosya seçin.';
    } else {
        $affectedFiles = 0;
        $errors = [];
        
        foreach ($selectedUploads as $uploadId) {
            if (!isValidUUID($uploadId)) continue;
            
            try {
                switch ($action) {
                    case 'approve':
                        if ($fileManager->updateUploadStatus($uploadId, 'processing', 'Toplu onaylama')) {
                            $affectedFiles++;
                        }
                        break;
                    case 'reject':
                        if ($fileManager->updateUploadStatus($uploadId, 'rejected', 'Toplu reddetme')) {
                            $affectedFiles++;
                        }
                        break;
                    case 'delete':
                        if ($fileManager->deleteUpload($uploadId)) {
                            $affectedFiles++;
                        }
                        break;
                }
            } catch(Exception $e) {
                $errors[] = "Dosya $uploadId: " . $e->getMessage();
            }
        }
        
        if ($affectedFiles > 0) {
            $success = "$affectedFiles dosya başarıyla güncellendi.";
            if (!empty($errors)) {
                $success .= " Bazı dosyalarda hata oluştu.";
            }
        } else {
            $error = "Hiçbir dosya güncellenemedi.";
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
    $whereClause = "WHERE 1=1"; // Tüm dosyaları göster
    $params = [];
    
    if ($search) {
        $whereClause .= " AND (u.original_name LIKE ? OR users.username LIKE ? OR users.email LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
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
            COUNT(*) as total_uploads,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN upload_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as today_uploads,
            AVG(file_size) as avg_file_size
        FROM file_uploads
    ");
    $stats = $stmt->fetch();
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

// Sidebar için istatistikler
$totalUsers = 0; // Bu değer başka bir yerden gelecek
$totalUploads = $stats['total_uploads'];

// Hızlı eylemler
$quickActions = [
    [
        'text' => 'Bekleyen Dosyalar',
        'url' => 'uploads.php?status=pending',
        'icon' => 'fas fa-clock',
        'class' => 'warning'
    ],
    [
        'text' => 'Excel Export',
        'url' => 'export-uploads.php',
        'icon' => 'fas fa-file-excel',
        'class' => 'success'
    ],
    [
        'text' => 'İstatistikler',
        'url' => 'reports.php?type=uploads',
        'icon' => 'fas fa-chart-line',
        'class' => 'info'
    ]
];

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format($stats['total_uploads']); ?></div>
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
                    <div class="stat-number text-warning"><?php echo number_format($stats['pending_count']); ?></div>
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
                    <div class="stat-number text-info"><?php echo number_format($stats['processing_count']); ?></div>
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
                    <div class="stat-number text-success"><?php echo number_format($stats['completed_count']); ?></div>
                    <div class="stat-label">Tamamlanan</div>
                    <small class="text-danger"><?php echo number_format($stats['rejected_count']); ?> reddedilen</small>
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
                       placeholder="Dosya adı, kullanıcı adı...">
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
        
        <?php if (!empty($uploads)): ?>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog me-1"></i>Toplu İşlemler
                </button>
                <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Seçili dosyalar için:</h6></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('approve')">
                        <i class="fas fa-check me-2 text-success"></i>Onayla & İşle
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('reject')">
                        <i class="fas fa-times me-2 text-danger"></i>Reddet
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('delete')">
                        <i class="fas fa-trash me-2 text-danger"></i>Sil
                    </a></li>
                </ul>
            </div>
        <?php endif; ?>
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
                            <th style="width: 30px;">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllUploads(this)">
                                </div>
                            </th>
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
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input upload-checkbox" 
                                               value="<?php echo $upload['id']; ?>">
                                    </div>
                                </td>
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
                                                <?php if (!empty($upload['service_type'])): ?>
                                                    • <?php echo ucfirst(str_replace('_', ' ', $upload['service_type'])); ?>
                                                <?php endif; ?>
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
                                        <small class="text-muted"><?php echo htmlspecialchars($upload['model_name'] ?? 'Model belirtilmemiş'); ?></small>
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
                                        
                                        <?php 
                                        $originalFileExists = false;
                                        if (!empty($upload['filename'])) {
                                            // filename'den tam path oluştur
                                            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'];
                                            $originalFileExists = file_exists($fullPath);
                                        }
                                        
                                        if ($originalFileExists): ?>
                                            <a href="download.php?type=original&id=<?php echo $upload['id']; ?>" 
                                               class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-download me-1"></i>İndir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Dosya sayfalama">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <!-- Önceki sayfa -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $brand ? '&brand=' . $brand : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $brand ? '&brand=' . $brand : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Sonraki sayfa -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $brand ? '&brand=' . $brand : ''; ?><?php echo $dateFrom ? '&date_from=' . $dateFrom : ''; ?><?php echo $dateTo ? '&date_to=' . $dateTo : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> 
                            (Toplam <?php echo $totalUploads; ?> dosya)
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Toplu İşlemler için Hidden Form -->
<form method="POST" id="bulkActionForm" style="display: none;">
    <input type="hidden" name="bulk_action" id="bulk_action_type">
    <div id="bulk_selected_uploads"></div>
</form>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Toggle all checkboxes
function toggleAllUploads(source) {
    const checkboxes = document.querySelectorAll('.upload-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

// View file details
function viewFileDetails(uploadId) {
    const modal = new bootstrap.Modal(document.getElementById('fileDetailModal'));
    const content = document.getElementById('fileDetailContent');
    
    content.innerHTML = `
        <div class=\"text-center py-5\">
            <div class=\"spinner-border text-primary\" role=\"status\">
                <span class=\"visually-hidden\">Yükleniyor...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // AJAX call to get file details
    fetch('ajax/get-upload-details.php?id=' + uploadId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `
                    <div class=\"alert alert-danger\">
                        <i class=\"fas fa-exclamation-triangle me-2\"></i>
                        Dosya detayları yüklenirken hata oluştu.
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class=\"alert alert-danger\">
                    <i class=\"fas fa-exclamation-triangle me-2\"></i>
                    Bağlantı hatası oluştu.
                </div>
            `;
        });
}

// Process file (approve and start processing)
function processFile(uploadId) {
    if (confirmAdminAction('Bu dosyayı işleme almak istediğinizden emin misiniz?')) {
        updateFileStatus(uploadId, 'processing', 'Dosya işleme alındı', true);
    }
}

// Update status modal
function updateStatus(uploadId) {
    document.getElementById('status_upload_id').value = uploadId;
    document.getElementById('status').value = '';
    document.getElementById('admin_notes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

// Upload response modal
function uploadResponse(uploadId) {
    document.getElementById('response_upload_id').value = uploadId;
    document.getElementById('response_file').value = '';
    document.getElementById('credits_charged').value = '5.00';
    document.getElementById('response_notes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('uploadResponseModal'));
    modal.show();
}

// Update file status via AJAX
function updateFileStatus(uploadId, status, notes = '', redirectToDetail = false) {
    const formData = new FormData();
    formData.append('update_status', '1');
    formData.append('upload_id', uploadId);
    formData.append('status', status);
    formData.append('admin_notes', notes);
    
    fetch('uploads.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (redirectToDetail) {
            // Redirect to file detail page
            window.location.href = 'file-detail.php?id=' + uploadId;
        } else {
            // Reload page to see changes
            location.reload();
        }
    })
    .catch(error => {
        showAdminNotification('Güncelleme sırasında hata oluştu!', 'danger');
    });
}

// Bulk actions
function bulkAction(action) {
    const checkboxes = document.querySelectorAll('.upload-checkbox:checked');
    if (checkboxes.length === 0) {
        showAdminNotification('Lütfen işlem yapmak için dosya seçin!', 'warning');
        return;
    }
    
    const uploadIds = Array.from(checkboxes).map(cb => cb.value);
    
    let confirmMessage = '';
    switch (action) {
        case 'approve':
            confirmMessage = 'Seçili dosyaları onaylayıp işleme almak istediğinizden emin misiniz?';
            break;
        case 'reject':
            confirmMessage = 'Seçili dosyaları reddetmek istediğinizden emin misiniz?';
            break;
        case 'delete':
            confirmMessage = 'Seçili dosyaları silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!';
            break;
    }
    
    if (confirmAdminAction(confirmMessage)) {
        document.getElementById('bulk_action_type').value = action;
        
        const selectedUploadsDiv = document.getElementById('bulk_selected_uploads');
        selectedUploadsDiv.innerHTML = '';
        
        uploadIds.forEach(uploadId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_uploads[]';
            input.value = uploadId;
            selectedUploadsDiv.appendChild(input);
        });
        
        document.getElementById('bulkActionForm').submit();
    }
}

// File size formatter
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Auto-refresh for pending/processing files
const pendingProcessingFiles = document.querySelectorAll('tr:has(.badge.bg-warning), tr:has(.badge.bg-info)');
if (pendingProcessingFiles.length > 0) {
    setTimeout(() => {
        if (!document.hidden) {
            location.reload();
        }
    }, 60000); // 1 dakika sonra yenile
}

// Form validation
document.getElementById('updateStatusForm').addEventListener('submit', function(e) {
    const status = document.getElementById('status').value;
    if (!status) {
        e.preventDefault();
        showAdminNotification('Lütfen yeni durumu seçin!', 'error');
        return false;
    }
});

document.getElementById('uploadResponseForm').addEventListener('submit', function(e) {
    const file = document.getElementById('response_file').files[0];
    const credits = parseFloat(document.getElementById('credits_charged').value);
    
    if (!file) {
        e.preventDefault();
        showAdminNotification('Lütfen yanıt dosyasını seçin!', 'error');
        return false;
    }
    
    if (credits < 0) {
        e.preventDefault();
        showAdminNotification('Kredi miktarı negatif olamaz!', 'error');
        return false;
    }
});
";

// Footer include
include '../includes/admin_footer.php';
?>
