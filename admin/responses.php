<?php
/**
 * Mr ECU - Admin Yanıt Dosyaları Yönetimi
 * Response files management with admin cancel functionality
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php?error=access_denied');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);
$error = '';
$success = '';

// URL'den mesajları al
if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = sanitize($_GET['error']);
}

// Session mesajlarını al ve temizle
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
    
    // Admin tarafından direkt dosya iptal etme
    if (isset($_POST['admin_cancel_file'])) {
        $cancelFileId = sanitize($_POST['file_id']);
        $cancelFileType = sanitize($_POST['file_type']);
        $adminNotes = sanitize($_POST['admin_notes']);
        
        if (!isValidUUID($cancelFileId)) {
            $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
        } else {
            // FileCancellationManager'ı yükle
            require_once '../includes/FileCancellationManager.php';
            $cancellationManager = new FileCancellationManager($pdo);
            
            $result = $cancellationManager->adminDirectCancellation($cancelFileId, $cancelFileType, $_SESSION['user_id'], $adminNotes);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                $user->logAction($_SESSION['user_id'], 'admin_direct_cancel', "Yanıt dosyası doğrudan iptal edildi: {$cancelFileId}");
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        
        // Redirect to prevent form resubmission
        header("Location: responses.php");
        exit;
    }
}

// Filtreleme parametreleri
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(10, min(100, intval($_GET['per_page']))) : 20;
$limit = $per_page;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Yanıt dosyalarını getir
try {
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(fr.filename LIKE ? OR fr.original_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%{$search}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($status)) {
        if ($status === 'cancelled') {
            $whereConditions[] = "fr.is_cancelled = 1";
        } else if ($status === 'active') {
            $whereConditions[] = "(fr.is_cancelled = 0 OR fr.is_cancelled IS NULL)";
        }
    }
    
    if (!empty($dateFrom)) {
        $whereConditions[] = "DATE(fr.upload_date) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $whereConditions[] = "DATE(fr.upload_date) <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Toplam kayıt sayısı
    $totalQuery = "
        SELECT COUNT(*) 
        FROM file_responses fr
        LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
        LEFT JOIN users u ON fu.user_id = u.id
        $whereClause
    ";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute($params);
    $totalRecords = $totalStmt->fetchColumn();
    
    // Sayfalama
    $totalPages = ceil($totalRecords / $limit);
    
    // Ana sorgu
    $query = "
        SELECT fr.*,
               fu.original_name as upload_name,
               fu.user_id,
               u.username,
               u.email,
               admin_user.username as admin_username,
               admin_user.first_name as admin_first_name,
               admin_user.last_name as admin_last_name
        FROM file_responses fr
        LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
        LEFT JOIN users u ON fu.user_id = u.id
        LEFT JOIN users admin_user ON fr.admin_id = admin_user.id
        $whereClause
        ORDER BY fr.upload_date DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Yanıt dosyaları getirilirken hata oluştu: ' . $e->getMessage();
    $responses = [];
    $totalRecords = 0;
    $totalPages = 0;
}

// Sayfa başlığı
$pageTitle = 'Yanıt Dosyaları Yönetimi';
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="container-fluid">
    <!-- Başlık ve İstatistikler -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-reply text-primary me-2"></i>
                        Yanıt Dosyaları Yönetimi
                    </h1>
                    <p class="text-muted mb-0">Admin tarafından yüklenen yanıt dosyalarını yönetin</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-info fs-6">Toplam: <?php echo number_format($totalRecords); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bildirimler -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="bi bi-filter me-2"></i>Filtreler ve Arama
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Arama</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Dosya adı, kullanıcı..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="">Tümü</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>İptal Edilmiş</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Filtrele
                    </button>
                    <a href="responses.php" class="btn btn-outline-secondary">
                        <i class="bi bi-times me-1"></i>Temizle
                    </a>
                </div>
                <!-- Per Page Seçimi -->
            <div class="col-md-12">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2">
                            <label for="per_page" class="form-label mb-0 fw-bold">
                                <i class="bi bi-list me-1 text-primary"></i>Sayfa başına:
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
                            <i class="bi bi-info-circle me-1"></i>
                            Toplam <?php echo number_format ($totalRecords); ?> kayıt
                        </span>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>

    <!-- Yanıt Dosyaları Tablosu -->
    <div class="card">
        <div class="card-body">
            <?php if (!empty($responses)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Dosya Bilgileri</th>
                                <th>Ana Dosya</th>
                                <th>Kullanıcı</th>
                                <th>Admin</th>
                                <th>Kredi</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responses as $response): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-file-download text-success me-2"></i>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($response['original_name']); ?></div>
                                                <small class="text-muted"><?php echo formatFileSize($response['file_size']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?php echo htmlspecialchars($response['upload_name'] ?? 'Bilinmiyor'); ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($response['username'] ?? 'Bilinmiyor'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($response['email'] ?? ''); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($response['admin_username'] ?? 'Bilinmiyor'); ?></div>
                                            <small class="text-muted">
                                                <?php 
                                                if ($response['admin_first_name'] || $response['admin_last_name']) {
                                                    echo htmlspecialchars(trim($response['admin_first_name'] . ' ' . $response['admin_last_name']));
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <?php echo number_format($response['credits_charged'], 2); ?> kredi
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo formatDate($response['upload_date']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($response['is_cancelled']): ?>
                                            <span class="badge bg-danger">İptal Edilmiş</span>
                                            <?php if ($response['cancelled_at']): ?>
                                                <br><small class="text-muted"><?php echo formatDate($response['cancelled_at']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($response['is_cancelled']): ?>
                                                <span class="btn btn-sm btn-secondary disabled">
                                                    <i class="bi bi-ban me-1"></i>İptal Edilmiş
                                                </span>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="showCancelModal('<?php echo $response['id']; ?>', 'response', '<?php echo htmlspecialchars($response['original_name']); ?>')">
                                                    <i class="bi bi-times me-1"></i>İptal Et
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="file-detail.php?id=<?php echo $response['upload_id']; ?>&type=response" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i>Detay
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo max(1, $page-1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&per_page=<?php echo $per_page; ?>">Önceki</a>
                            </li>
                            
                            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&per_page=<?php echo $per_page; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo min($totalPages, $page+1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&per_page=<?php echo $per_page; ?>">Sonraki</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-download fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Yanıt dosyası bulunamadı</h5>
                    <p class="text-muted">Arama kriterlerinizi değiştirerek tekrar deneyin.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Admin İptal Modal -->
<div class="modal fade" id="adminCancelModal" tabindex="-1" aria-labelledby="adminCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="adminCancelModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Yanıt Dosyası İptal Onayı
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form method="POST" id="adminCancelForm">
                <div class="modal-body py-4">
                    <input type="hidden" name="admin_cancel_file" value="1">
                    <input type="hidden" name="file_id" id="cancelFileId">
                    <input type="hidden" name="file_type" id="cancelFileType">
                    
                    <div class="mb-4">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: linear-gradient(135deg, #dc3545, #c82333); border-radius: 50%;">
                            <i class="bi bi-times text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-2 text-dark text-center">Bu yanıt dosyasını iptal etmek istediğinizden emin misiniz?</h6>
                        <p class="text-muted mb-3 text-center">
                            <strong>Dosya:</strong> <span id="cancelFileName"></span>
                        </p>
                        <div class="alert alert-warning d-flex align-items-center mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>Bu işlem dosyayı gizleyecek ve varsa ücret iadesi yapacaktır.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adminNotes" class="form-label">
                            <i class="bi bi-sticky-note me-1"></i>
                            İptal Sebebi (Opsiyonel)
                        </label>
                        <textarea class="form-control" id="adminNotes" name="admin_notes" rows="3" 
                                  placeholder="İptal sebebinizi yazabilirsiniz..."></textarea>
                        <small class="text-muted">Bu not kullanıcıya gönderilecek bildirimde yer alacaktır.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="bi bi-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-check me-1"></i>Evet, İptal Et
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}
</style>

<script>
function showCancelModal(fileId, fileType, fileName) {
    document.getElementById('cancelFileId').value = fileId;
    document.getElementById('cancelFileType').value = fileType;
    document.getElementById('cancelFileName').textContent = fileName;
    document.getElementById('adminNotes').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
    modal.show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>
