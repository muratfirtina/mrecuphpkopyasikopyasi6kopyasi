<?php
/**
 * Mr ECU - Admin Revize Yönetimi
 * 
 * @global PDO|null $pdo Global database connection
 * @global FileManager|null $fileManager File manager instance
 * @global User|null $user User management instance
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
/** @var FileManager $fileManager */
$fileManager = new FileManager($pdo);
/** @var User $user */
$user = new User($pdo);
$error = '';
$success = '';

// URL'den success mesajını al
if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

// URL'den error mesajını al
if (isset($_GET['error'])) {
    $error = sanitize($_GET['error']);
}

// Session mesajlarını al ve temizle - PRG pattern için
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received on revisions.php");
    error_log("POST data: " . print_r($_POST, true));
    
    try {
        // Revize talebini onaylama (işleme alma)
        if (isset($_POST['approve_revision_direct'])) {
            error_log("Direct revision approval started");
            $revisionId = sanitize($_POST['revision_id']);
            
            if (!isValidUUID($revisionId)) {
                $error = 'Geçersiz revize ID formatı.';
                error_log("Invalid revision ID format: " . $revisionId);
            } else {
                error_log("Processing revision approval for ID: " . $revisionId);
                error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
                error_log("Is admin: " . (isAdmin() ? 'yes' : 'no'));
                
                $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'in_progress', 'Revize talebi işleme alındı.', 0);
                
                error_log("UpdateRevisionStatus result: " . print_r($result, true));
                
                if ($result['success']) {
                    $success = 'Revize talebi işleme alındı. Dosya detay sayfasında revize edilmiş dosyayı yükleyebilirsiniz.';
                    $user->logAction($_SESSION['user_id'], 'revision_approved', "Revize talebi işleme alındı: {$revisionId}");
                    
                    // Başarılı işlem sonrası redirect
                    header("Location: revisions.php?success=" . urlencode($success));
                    exit;
                } else {
                    $error = $result['message'];
                    error_log("UpdateRevisionStatus failed: " . $error);
                }
            }
        }
        
        // Revize talebini reddetme
        if (isset($_POST['reject_revision_direct'])) {
            error_log("Direct revision rejection started");
            $revisionId = sanitize($_POST['revision_id']);
            $adminNotes = sanitize($_POST['admin_notes']) ?: 'Revize talebi reddedildi.';
            
            if (!isValidUUID($revisionId)) {
                $error = 'Geçersiz revize ID formatı.';
                error_log("Invalid revision ID format: " . $revisionId);
            } elseif (strlen(trim($adminNotes)) < 10) {
                $error = 'Reddetme sebebi en az 10 karakter olmalıdır.';
                error_log("Admin notes too short: " . $adminNotes);
            } else {
                error_log("Processing revision rejection for ID: " . $revisionId . " with notes: " . $adminNotes);
                
                $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'rejected', $adminNotes, 0);
                
                error_log("UpdateRevisionStatus (reject) result: " . print_r($result, true));
                
                if ($result['success']) {
                    $success = 'Revize talebi reddedildi.';
                    $user->logAction($_SESSION['user_id'], 'revision_rejected', "Revize talebi reddedildi: {$revisionId}");
                    
                    // Başarılı işlem sonrası redirect
                    header("Location: revisions.php?success=" . urlencode($success));
                    exit;
                } else {
                    $error = $result['message'];
                    error_log("UpdateRevisionStatus (reject) failed: " . $error);
                }
            }
        }
        
        // Revize talebini işle (modal ile - sadece özel durumlar için)
        if (isset($_POST['process_revision'])) {
            $revisionId = sanitize($_POST['revision_id']);
            $status = sanitize($_POST['status']);
            $adminNotes = sanitize($_POST['admin_notes']);
            $creditsCharged = (float)$_POST['credits_charged'];
            
            if (!isValidUUID($revisionId)) {
                $error = 'Geçersiz revize ID formatı.';
            } elseif (!in_array($status, ['in_progress', 'rejected'])) {
                $error = 'Geçersiz durum seçimi.';
            } elseif ($creditsCharged < 0) {
                $error = 'Kredi miktarı negatif olamaz.';
            } else {
                // FileManager metodu ile revize talebini güncelle
                $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], $status, $adminNotes, $creditsCharged);
                
                if ($result['success']) {
                    $success = ($status === 'in_progress') ? 'Revize talebi işleme alındı.' : 'Revize talebi reddedildi.';
                    $user->logAction($_SESSION['user_id'], 'revision_processed', "Revize talebi işlendi: {$revisionId}, Durum: {$status}");
                    
                    // Başarılı işlem sonrası redirect
                    header("Location: revisions.php?success=" . urlencode($success));
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
        
    } catch (Exception $e) {
        $error = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
        error_log('POST processing error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
    }
    
    // Hata varsa session'a koy ve redirect et
    if ($error) {
        $_SESSION['error'] = $error;
        header("Location: revisions.php");
        exit;
    }
}

// Filtreleme ve arama parametreleri
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(10, min(100, intval($_GET['per_page']))) : 25; // 10-100 arası limit, default 20
$limit = $per_page; // Backward compatibility

// Pagination URL builder fonksiyonu  
function buildPaginationUrl($pageNum, $search = '', $status = '', $dateFrom = '', $dateTo = '', $per_page = 25) {
    $params = array(
        'page' => $pageNum,
        'per_page' => $per_page
    );
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    if (!empty($status)) {
        $params['status'] = $status;
    }
    if (!empty($dateFrom)) {
        $params['date_from'] = $dateFrom;
    }
    if (!empty($dateTo)) {
        $params['date_to'] = $dateTo;
    }
    
    return 'revisions.php?' . http_build_query($params);
}

// Revize taleplerini getir (FileManager metodu ile)
/** @var array $revisions Array of revision data */
$revisions = $fileManager->getAllRevisions($page, $per_page, $status, $dateFrom, $dateTo, $search);

// Toplam revize sayısı için ayrı sorgu
try {
    $countQuery = "SELECT COUNT(*) FROM revisions r";
    $countParams = [];
    
    if ($status) {
        $countQuery .= " WHERE r.status = ?";
        $countParams[] = $status;
    }
    
    if ($search) {
        $operator = $status ? " AND" : " WHERE";
        $countQuery .= "$operator (r.id IN (SELECT r2.id FROM revisions r2 LEFT JOIN file_uploads fu ON r2.upload_id = fu.id LEFT JOIN users u ON r2.user_id = u.id LEFT JOIN brands b ON fu.brand_id = b.id LEFT JOIN models m ON fu.model_id = m.id WHERE fu.original_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR b.name LIKE ? OR m.name LIKE ? OR fu.plate LIKE ?))";
        $searchParam = "%$search%";
        $countParams = array_merge($countParams, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if ($dateFrom) {
        $operator = ($status || $search) ? " AND" : " WHERE";
        $countQuery .= "$operator DATE(r.requested_at) >= ?";
        $countParams[] = $dateFrom;
    }
    
    if ($dateTo) {
        $operator = ($status || $search || $dateFrom) ? " AND" : " WHERE";
        $countQuery .= "$operator DATE(r.requested_at) <= ?";
        $countParams[] = $dateTo;
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRevisions = $stmt->fetchColumn();
    $totalPages = ceil($totalRevisions / $per_page);
    
} catch(PDOException $e) {
    $totalRevisions = count($revisions);
    $totalPages = 1;
}

// İstatistikler
try {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(COUNT(*), 0) as total_revisions,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
            COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as processing_count,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
            COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected_count,
            COALESCE(SUM(CASE WHEN requested_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END), 0) as today_requests
        FROM revisions
    ");
    /** @var array $stats Statistics array with guaranteed integer values */
    $stats = $stmt->fetch();
    
    // Null değerleri 0 ile değiştir
    $stats = array_map(function($value) {
        return $value === null ? 0 : $value;
    }, $stats);
    
} catch(PDOException $e) {
    /** @var array $stats Fallback statistics array */
    $stats = [
        'total_revisions' => 0,
        'pending_count' => 0, 
        'processing_count' => 0,
        'completed_count' => 0,
        'rejected_count' => 0,
        'today_requests' => 0
    ];
}

// Seçili revize detayı
$selectedRevision = null;
if (isset($_GET['detail_id'])) {
    $revisionId = sanitize($_GET['detail_id']);
    
    if (isValidUUID($revisionId)) {
        foreach ($revisions as $revision) {
            if ($revision['id'] === $revisionId) {
                $selectedRevision = $revision;
                break;
            }
        }
    }
}

$pageTitle = 'Revize Yönetimi';
$pageDescription = 'Kullanıcı revize taleplerini yönetin';
$pageIcon = 'fas fa-edit';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="row g-4 mb-4">
    <a class="col-lg-3 col-md-6" href="revisions.php" style="text-decoration: none; outline: none;">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo safe_number_format($stats['total_revisions']); ?></div>
                    <div class="stat-label">Toplam Revize</div>
                    <small class="text-success">+<?php echo $stats['today_requests']; ?> bugün</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-edit text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </a>
    
    <a class="col-lg-3 col-md-6" href="revisions.php?status=pending" style="text-decoration: none; outline: none;">
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
    </a>
    
    <a class="col-lg-3 col-md-6" href="revisions.php?status=in_progress" style="text-decoration: none; outline: none;">
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
    </a>
    
    <a class="col-lg-3 col-md-6" href="revisions.php?status=completed" style="text-decoration: none; outline: none;">
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
    </a>
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
                       placeholder="Dosya adı, kullanıcı, marka, plaka...">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Durum</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>İşleniyor</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Tamamlanan</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">Başlangıç</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">Bitiş</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filtrele
                    </button>
                    <a href="revisions.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i>Temizle
                    </a>
                </div>
            </div>

            <!-- Per Page Seçimi -->
            <div class="col-md-12">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2">
                            <label for="per_page" class="form-label mb-0 fw-bold">
                                <i class="fas fa-list me-1 text-primary"></i>Sayfa başına:
                            </label>
                            <select class="form-select form-select-sm px-3 py-2" id="per_page" name="per_page" style="width: 120px; border: 2px solid #e9ecef;" onchange="this.form.submit()">
                                <option value="10" <?php echo $per_page === 10 ? 'selected' : ''; ?>>10 kayıt</option>
                                <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25 kayıt</option>
                                <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50 kayıt</option>
                                <option value="100" <?php echo $per_page === 100 ? 'selected' : ''; ?>>100 kayıt</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Toplam <?php echo number_format($totalRevisions); ?> kayıt bulundu
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Revize Listesi -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Revize Talepleri (<?php echo $totalRevisions; ?> adet)
        </h5>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($revisions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($status || $dateFrom || $dateTo): ?>
                        Filtreye uygun revize talebi bulunamadı
                    <?php else: ?>
                        Henüz revize talebi yok
                    <?php endif; ?>
                </h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kullanıcı</th>
                            <th>Dosya</th>
                            <th>Araç Bilgileri</th>
                            <th>Talep Notları</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisions as $revision): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name']); ?></strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($revision['username']); ?></small><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($revision['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php 
                                        // Hangi dosyaya revize talep edildiğini belirle
                                        $targetFileName = 'Ana Dosya';
                                        $targetFileType = 'Orijinal Yüklenen Dosya';
                                        $targetFileColor = 'success';
                                        $targetFileIcon = 'file-alt';
                                        
                                        if ($revision['response_id']): 
                                            // Yanıt dosyasına revize talebi
                                            $targetFileName = $revision['response_original_name'] ?? 'Yanıt Dosyası';
                                            $targetFileType = 'Yanıt Dosyası';
                                            $targetFileColor = 'primary';
                                            $targetFileIcon = 'reply';
                                        else:
                                            // Ana dosya veya revizyon dosyasına revize talebi
                                            // Önceki revizyon dosyaları var mı kontrol et
                                            try {
                                                $stmt = $pdo->prepare("
                                                    SELECT rf.original_name 
                                                    FROM revisions r1
                                                    JOIN revision_files rf ON r1.id = rf.revision_id
                                                    WHERE r1.upload_id = ? 
                                                    AND r1.status = 'completed'
                                                    AND r1.requested_at < ?
                                                    ORDER BY r1.requested_at DESC 
                                                    LIMIT 1
                                                ");
                                                $stmt->execute([$revision['upload_id'], $revision['requested_at']]);
                                                $previousRevisionFile = $stmt->fetch(PDO::FETCH_ASSOC);
                                                
                                                if ($previousRevisionFile) {
                                                    $targetFileName = $previousRevisionFile['original_name'];
                                                    $targetFileType = 'Revizyon Dosyası';
                                                    $targetFileColor = 'warning';
                                                    $targetFileIcon = 'edit';
                                                } else {
                                                    $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
                                                }
                                            } catch (Exception $e) {
                                                error_log('Previous revision file query error: ' . $e->getMessage());
                                                $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
                                            }
                                        endif;
                                        ?>
                                        
                                        <!-- Revize Talep Edilen Dosya -->
                                        <div class="mb-2">
                                            <i class="fas fa-<?php echo $targetFileIcon; ?> text-<?php echo $targetFileColor; ?> me-2"></i>
                                            <strong class="text-<?php echo $targetFileColor; ?>"><?php echo $targetFileType; ?>:</strong><br>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($targetFileName); ?></span>
                                        </div>
                                        
                                        <!-- Ana Proje Dosyası Bilgisi -->
                                        <?php if ($revision['response_id'] || $targetFileType === 'Revizyon Dosyası'): ?>
                                            <div class="text-muted small">
                                                <i class="fas fa-level-up-alt me-1"></i>
                                                <strong>Ana Proje:</strong> <?php echo htmlspecialchars($revision['original_name'] ?? 'Bilinmiyor'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="vehicle-info" style="width: 300px;">
                                        <?php if (!empty($revision['brand_name']) || !empty($revision['model_name'])): ?>
                                            <div class="brand-model mb-1">
                                                <strong><?php echo htmlspecialchars($revision['brand_name'] ?? 'Bilinmiyor'); ?></strong>
                                                <?php if (!empty($revision['model_name'])): ?>
                                                    - <?php echo htmlspecialchars($revision['model_name']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($revision['series_name'])): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i>
                                                        Seri: <?php echo htmlspecialchars($revision['series_name']); ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($revision['engine_name'])): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-cog me-1"></i>
                                                        Motor: <?php echo htmlspecialchars($revision['engine_name']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($revision['plate'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-dark text-white">
                                                    <i class="fas fa-id-card me-1"></i>
                                                    <?php echo strtoupper(htmlspecialchars($revision['plate'])); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted mt-1">
                                                <i class="fas fa-minus-circle me-1"></i>
                                                Plaka belirtilmemiş
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 200px;">
                                        <?php echo htmlspecialchars(substr($revision['request_notes'], 0, 100)); ?>
                                        <?php if (strlen($revision['request_notes']) > 100): ?>...<?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $statusText = [
                                        'pending' => 'Bekliyor',
                                        'in_progress' => 'İşleniyor',
                                        'completed' => 'Tamamlandı',
                                        'rejected' => 'Reddedildi'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$revision['status']] ?? 'secondary'; ?>">
                                        <?php echo $statusText[$revision['status']] ?? 'Bilinmiyor'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo date('d.m.Y', strtotime($revision['requested_at'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($revision['requested_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm" style="min-width: 120px;">
                                        <?php if ($revision['status'] === 'pending'): ?>
                                            <!-- Onayla Butonu -->
                                            <button type="button" class="btn btn-success btn-sm w-100 mb-1" 
                                                    onclick="showApproveModal('<?php echo $revision['id']; ?>', '<?php echo htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-check me-1"></i>Onayla
                                            </button>
                                            
                                            <!-- Reddet Butonu -->
                                            <button type="button" class="btn btn-danger btn-sm w-100 mb-1" 
                                                    onclick="showRejectModal('<?php echo $revision['id']; ?>')">
                                                <i class="fas fa-times me-1"></i>Reddet
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">İşlenmiş</span>
                                        <?php endif; ?>
                                        
                                        <!-- Dosya Detayı Butonu -->
                                        <a href="revision-detail.php?id=<?php echo $revision['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-info-circle me-1"></i>Detay Gör
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Advanced Pagination Navigation -->
            <div class="pagination-wrapper bg-light border-top p-4">
                <!-- Sayfa Bilgileri ve Kontroller -->
                <div class="row align-items-center">
                    <!-- Sol taraf - Bilgi ve Hızlı Atlama -->
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="row align-items-center g-3">
                            <div class="col-auto">
                                <div class="pagination-info">
                                    <span class="badge bg-primary fs-6 px-3 py-2">
                                        <i class="fas fa-list-ol me-2"></i>
                                        <?php 
                                        $offset = ($page - 1) * $per_page;
                                        $start = $offset + 1;
                                        $end = min($offset + $per_page, $totalRevisions);
                                        echo "$start - $end / " . number_format($totalRevisions);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Hızlı Sayfa Atlama -->
                            <?php if ($totalPages > 5): ?>
                            <div class="col-auto">
                                <div class="quick-jump-container">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input type="number" class="form-control border-start-0" 
                                               id="quickJump" 
                                               min="1" 
                                               max="<?php echo $totalPages; ?>" 
                                               value="<?php echo $page; ?>"
                                               placeholder="Sayfa"
                                               style="width: 80px;"
                                               onkeypress="if(event.key==='Enter') quickJumpToPage()"
                                               title="Sayfa numarası girin ve Enter'a basın">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="quickJumpToPage()" 
                                                title="Sayfaya git">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">/ <?php echo $totalPages; ?> sayfa</small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Sağ taraf - Pagination Kontrolleri -->
                    <div class="col-md-6">
                        <nav aria-label="Sayfa navigasyonu" class="d-flex justify-content-md-end justify-content-center">
                            <ul class="pagination pagination-lg mb-0 shadow-sm">
                                <!-- İlk Sayfa -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-start" 
                                       href="<?php echo $page > 1 ? buildPaginationUrl(1, $search, $status, $dateFrom, $dateTo, $per_page) : '#'; ?>" 
                                       title="İlk Sayfa" 
                                       <?php echo $page <= 1 ? 'tabindex="-1"' : ''; ?>>
                                        <i class="fas fa-angle-double-left"></i>
                                        <span class="d-none d-sm-inline ms-1">İlk</span>
                                    </a>
                                </li>
                                
                                <!-- Önceki Sayfa -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="<?php echo $page > 1 ? buildPaginationUrl($page - 1, $search, $status, $dateFrom, $dateTo, $per_page) : '#'; ?>" 
                                       title="Önceki Sayfa"
                                       <?php echo $page <= 1 ? 'tabindex="-1"' : ''; ?>>
                                        <i class="fas fa-angle-left"></i>
                                        <span class="d-none d-sm-inline ms-1">Önceki</span>
                                    </a>
                                </li>
                                
                                <!-- Sayfa Numaraları -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($totalPages, $page + 2);
                                
                                // Mobilde daha az sayfa göster
                                if ($totalPages > 7) {
                                    $start_page = max(1, $page - 1);
                                    $end_page = min($totalPages, $page + 1);
                                }
                                
                                // İlk sayfa elipsisi
                                if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo buildPaginationUrl(1, $search, $status, $dateFrom, $dateTo, $per_page); ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled d-none d-md-block">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Sayfa numaraları -->
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link <?php echo $i === $page ? 'bg-primary border-primary' : ''; ?>" 
                                           href="<?php echo buildPaginationUrl($i, $search, $status, $dateFrom, $dateTo, $per_page); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Son sayfa elipsisi -->
                                <?php if ($end_page < $totalPages): ?>
                                    <?php if ($end_page < $totalPages - 1): ?>
                                        <li class="page-item disabled d-none d-md-block">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo buildPaginationUrl($totalPages, $search, $status, $dateFrom, $dateTo, $per_page); ?>"><?php echo $totalPages; ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Sonraki Sayfa -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="<?php echo $page < $totalPages ? buildPaginationUrl($page + 1, $search, $status, $dateFrom, $dateTo, $per_page) : '#'; ?>" 
                                       title="Sonraki Sayfa"
                                       <?php echo $page >= $totalPages ? 'tabindex="-1"' : ''; ?>>
                                        <span class="d-none d-sm-inline me-1">Sonraki</span>
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                
                                <!-- Son Sayfa -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-end" 
                                       href="<?php echo $page < $totalPages ? buildPaginationUrl($totalPages, $search, $status, $dateFrom, $dateTo, $per_page) : '#'; ?>" 
                                       title="Son Sayfa"
                                       <?php echo $page >= $totalPages ? 'tabindex="-1"' : ''; ?>>
                                        <span class="d-none d-sm-inline me-1">Son</span>
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                
                <!-- Alt bilgi çubuğu -->
                <div class="row mt-3 pt-3 border-top">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Sayfa <strong><?php echo $page; ?></strong> / <strong><?php echo $totalPages; ?></strong> - 
                            Sayfa başına <strong><?php echo $per_page; ?></strong> kayıt gösteriliyor
                        </small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            <i class="fas fa-database me-1"></i>
                            Toplam <strong><?php echo number_format($totalRevisions); ?></strong> revize talebi bulundu
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Advanced Pagination Styling */
.pagination-wrapper {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination-info .badge {
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.quick-jump-container .input-group {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 0.375rem;
    overflow: hidden;
}

.quick-jump-container .form-control {
    border: 2px solid #e9ecef;
    transition: all 0.15s ease-in-out;
}

.quick-jump-container .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Enhanced Pagination Controls */
.pagination-lg .page-link {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 2px solid #dee2e6;
    color: #495057;
    margin: 0 3px;
    border-radius: 0.5rem;
    transition: all 0.2s ease-in-out;
    font-weight: 500;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.pagination-lg .page-link:hover {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border-color: #0d6efd;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
}

.pagination-lg .page-item.active .page-link {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border-color: #0d6efd;
    color: white;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
    transform: scale(1.05);
}

.pagination-lg .page-item.disabled .page-link {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    color: #6c757d;
    opacity: 0.6;
    cursor: not-allowed;
    box-shadow: none;
}

.pagination-lg .page-link i {
    font-size: 0.9rem;
}

/* Per page selector enhanced styling */
.form-select {
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.15s ease-in-out;
    font-weight: 500;
}

.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Badge enhancements */
.badge.bg-light {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
    border: 2px solid #e9ecef;
    color: #495057 !important;
    font-weight: 500;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .pagination-lg .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        margin: 0 1px;
    }
    
    .pagination-wrapper {
        padding: 1rem !important;
    }
    
    .quick-jump-container {
        display: none;
    }
    
    .pagination-info .badge {
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .pagination-lg .page-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
    }
    
    .pagination-lg .page-link span {
        display: none !important;
    }
}

/* Animation for page changes */
.pagination-lg .page-link {
    position: relative;
    overflow: hidden;
}

.pagination-lg .page-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.pagination-lg .page-link:hover::before {
    left: 100%;
}

/* Loading state for quick jump */
.quick-jump-container.loading .btn {
    pointer-events: none;
}

.quick-jump-container.loading .btn i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Modern Process Confirmation Modal Styles */
.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
}

#processConfirmModal .modal-content {
    border-radius: 1rem;
    overflow: hidden;
}

#processConfirmModal .modal-header {
    padding: 1.5rem 2rem 1rem;
    border-bottom: none;
}

#processConfirmModal .modal-body {
    padding: 1rem 2rem 1.5rem;
}

#processConfirmModal .modal-footer {
    padding: 0rem 3rem 3rem 0rem;
    background: #f8f9fa;
    margin: 0 -2rem -2rem;
    padding-top: 1.5rem;
}

#processConfirmModal .btn-lg {
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

#processConfirmModal .btn-success:hover {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: #28a745;
    transform: translateY(-2px);
}

#processConfirmModal .btn-secondary:hover {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border-color: #6c757d;
    transform: translateY(-2px);
}

#processConfirmModal .alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border: 1px solid #b6d4da;
    border-radius: 0.5rem;
}

/* Modal animation enhancements */
#processConfirmModal.fade .modal-dialog {
    transition: transform 0.4s ease-out;
    transform: scale(0.8) translateY(-50px);
}

#processConfirmModal.show .modal-dialog {
    transform: scale(1) translateY(0);
}

/* Icon pulse animation */
#processConfirmModal .fas.fa-file-alt {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Mobile responsiveness for modal */
@media (max-width: 576px) {
    #processConfirmModal .modal-header {
        padding: 1rem 1.5rem 0.5rem;
    }
    
    #processConfirmModal .modal-body {
        padding: 0.5rem 1.5rem 1rem;
    }
    
    #processConfirmModal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        margin: 0 -1.5rem -1.5rem;
    }
    
    #processConfirmModal .btn-lg {
        padding: 0.6rem 1.5rem;
        font-size: 0.9rem;
    }
}
</style>

<!-- Revize Onaylama Modal -->
<div class="modal fade" id="approveRevisionModal" tabindex="-1" aria-labelledby="approveRevisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveRevisionForm" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveRevisionModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Revize Talebini Onayla
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="revision_id" id="approve_revision_id">
                    <input type="hidden" name="approve_revision_direct" value="1">
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong id="user-name-display"></strong> kullanıcısının revize talebi işleme alınacak.
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        <div>
                            <strong>Bilgi:</strong> Revize talebi onaylandıktan sonra dosya detay sayfasında revize edilmiş dosyayı yükleyebilirsiniz.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Vazgeç
                    </button>
                    <button type="submit" class="btn btn-success" id="approve-submit-btn">
                        <i class="fas fa-check me-1"></i>Onayla ve İşleme Al
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Revize Reddetme Modal -->
<div class="modal fade" id="rejectRevisionModal" tabindex="-1" aria-labelledby="rejectRevisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectRevisionForm" method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectRevisionModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Revize Talebini Reddet
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="revision_id" id="reject_revision_id">
                    <input type="hidden" name="reject_revision_direct" value="1">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dikkat!</strong> Bu revizyon talebi reddedilecek ve kullanıcıya bildirilecek.
                    </div>
                    
                    <div class="mb-3">
                        <label for="reject_admin_notes" class="form-label">
                            <i class="fas fa-comment me-1"></i>
                            Reddetme Sebebi <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="reject_admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Revize talebinin neden reddedildiğini açıklayın..." required minlength="10"></textarea>
                        <div class="form-text">Bu mesaj kullanıcıya gönderilecektir. En az 10 karakter olmalıdır.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Vazgeç
                    </button>
                    <button type="submit" class="btn btn-danger" id="reject-submit-btn">
                        <i class="fas fa-times me-1"></i>Reddet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Hızlı sayfa atlama fonksiyonu
function quickJumpToPage() {
    const pageInput = document.getElementById('quickJump');
    const targetPage = parseInt(pageInput.value);
    const maxPage = parseInt(pageInput.getAttribute('max'));
    
    if (targetPage && targetPage >= 1 && targetPage <= maxPage) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('page', targetPage);
        
        window.location.href = '?' + urlParams.toString();
    } else {
        alert('Lütfen 1 ile ' + maxPage + ' arasında geçerli bir sayfa numarası girin.');
        pageInput.focus();
    }
}

// Global değişken - beforeunload'u geçici olarak devre dışı bırakmak için
let allowPageUnload = false;

// Revize onaylama modal gösterme
function showApproveModal(revisionId, userName) {
    if (!revisionId) {
        alert('Geçersiz revize ID!');
        return;
    }
    
    console.log('Opening approve modal for revision:', revisionId);
    
    // Modal input alanlarını doldur
    document.getElementById('approve_revision_id').value = revisionId;
    document.getElementById('user-name-display').textContent = userName || 'Bilinmeyen Kullanıcı';
    
    // Modal'ı göster
    const modal = new bootstrap.Modal(document.getElementById('approveRevisionModal'));
    modal.show();
}

// Revize reddetme modal gösterme
function showRejectModal(revisionId) {
    if (!revisionId) {
        alert('Geçersiz revize ID!');
        return;
    }
    
    console.log('Opening reject modal for revision:', revisionId);
    
    // Modal input alanlarını doldur
    document.getElementById('reject_revision_id').value = revisionId;
    document.getElementById('reject_admin_notes').value = '';
    
    // Modal'ı göster
    const modal = new bootstrap.Modal(document.getElementById('rejectRevisionModal'));
    modal.show();
}

// Sayfa yüklendiğinde kontroller
document.addEventListener('DOMContentLoaded', function() {
    console.log('Revisions page loaded - setting up modal event handlers');
    
    // Onaylama formu için event listener
    const approveForm = document.getElementById('approveRevisionForm');
    if (approveForm) {
        approveForm.addEventListener('submit', function(e) {
            console.log('Approve form submitted');
            
            // Sayfa değişikliğine izin ver
            allowPageUnload = true;
            
            // Submit butonunu disable et
            const submitBtn = document.getElementById('approve-submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-1\"></i>İşleniyor...';
            
            // Modal'ı kapat
            const modal = bootstrap.Modal.getInstance(document.getElementById('approveRevisionModal'));
            if (modal) {
                modal.hide();
            }
            
            return true;
        });
    }
    
    // Reddetme formu için doğrulama
    const rejectForm = document.getElementById('rejectRevisionForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            const notes = document.getElementById('reject_admin_notes').value.trim();
            console.log('Reject form submitted with notes length:', notes.length);
            
            if (notes.length < 10) {
                e.preventDefault();
                alert('Reddetme sebebi en az 10 karakter olmalıdır.');
                return false;
            }
            
            // Sayfa değişikliğine izin ver
            allowPageUnload = true;
            
            // Submit butonunu disable et
            const submitBtn = document.getElementById('reject-submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-1\"></i>İşleniyor...';
            
            // Modal'ı kapat
            const modal = bootstrap.Modal.getInstance(document.getElementById('rejectRevisionModal'));
            if (modal) {
                modal.hide();
            }
            
            console.log('Reject form validation passed, submitting...');
            return true;
        });
    }
    
    // Alert mesajlarını otomatik kapat
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }
        }, 8000); // 8 saniye
    });
    
    console.log('Modal event handlers set up successfully');
});

// Konsol hatalarını yakala
window.addEventListener('error', function(e) {
    console.error('JavaScript Error on revisions page:', e.error);
});

// Sayfa kapanmadan önce kontrol et - SADECE gerekli durumlarda
window.addEventListener('beforeunload', function(e) {
    // Eğer allowPageUnload true ise veya form submit edilmişse engellenmesin
    if (allowPageUnload) {
        return;
    }
    
    // Sadece disabled butonlar varsa ve form submit edilmemişse uyar
    const disabledButtons = document.querySelectorAll('button[disabled]:not([data-bs-dismiss])');
    const activeModals = document.querySelectorAll('.modal.show');
    
    // Modal açıksa veya işlem devam ediyorsa uyar
    if ((disabledButtons.length > 0 || activeModals.length > 0) && !allowPageUnload) {
        e.preventDefault();
        e.returnValue = 'İşlem devam ediyor, sayfayı kapatmak istediğinizden emin misiniz?';
        return e.returnValue;
    }
});

// Sayfa href değişikliklerinde allowPageUnload'u sıfırla
window.addEventListener('unload', function() {
    allowPageUnload = false;
});
";

// Footer include
include '../includes/admin_footer.php';
?>
