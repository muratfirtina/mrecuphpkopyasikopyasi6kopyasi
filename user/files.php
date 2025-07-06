<?php
/**
 * Mr ECU - Kullanıcı Dosyaları Sayfası (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü otomatik yapılır
$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
$userId = $_SESSION['user_id'];

// GUID format kontrolü - User ID
if (!isValidUUID($userId)) {
    redirect('../logout.php');
}

$error = '';
$success = '';

// Session mesajlarını al
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Revize talep işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_revision'])) {
    $uploadId = sanitize($_POST['upload_id']);
    $revisionNotes = sanitize($_POST['revision_notes']);
    
    // GUID format kontrolü
    if (!isValidUUID($uploadId)) {
        $error = 'Geçersiz dosya ID formatı.';
    } elseif (empty($revisionNotes)) {
        $error = 'Revize talebi için açıklama gereklidir.';
    } else {
        $result = $fileManager->requestRevision($uploadId, $userId, $revisionNotes);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Filtreleme parametreleri
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;

// Kullanıcının dosyalarını getir
$userUploads = $fileManager->getUserUploads($userId, $page, $limit, $status, $search);
$totalUploads = $fileManager->getUserUploadCount($userId, $status, $search);
$totalPages = ceil($totalUploads / $limit);

// İstatistikler
$stats = $fileManager->getUserFileStats($userId);

// Sayfa bilgileri
$pageTitle = 'Dosyalarım';
$pageDescription = 'Yüklediğiniz dosyaları görüntüleyin, indirin ve yönetin.';

// Header ve Sidebar include
include '../includes/user_header.php';
include '../includes/user_sidebar.php';
?>

<!-- Dosya İstatistikleri -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Toplam Dosya</div>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-file text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Bekleyen</div>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="fas fa-clock text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo $stats['processing']; ?></div>
                    <div class="stat-label">İşleniyor</div>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="fas fa-cogs text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo $stats['completed']; ?></div>
                    <div class="stat-label">Tamamlanan</div>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="fas fa-check-circle text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtre ve Arama -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">
                    <i class="fas fa-search me-1"></i>Dosya Ara
                </label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Dosya adı ile ara...">
            </div>
            
            <div class="col-md-4">
                <label for="status" class="form-label">
                    <i class="fas fa-filter me-1"></i>Durum Filtresi
                </label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>İşleniyor</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Tamamlanan</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filtrele
                    </button>
                    <a href="files.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i>Temizle
                    </a>
                    <a href="upload.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Yeni Dosya
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Dosya Listesi -->
<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-folder me-2"></i>
            Dosyalarım (<?php echo $totalUploads; ?> dosya)
        </h5>
        
        <?php if (!empty($userUploads)): ?>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-1"></i>Toplu İşlemler
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="downloadCompleted()">
                        <i class="fas fa-download me-2"></i>Tamamlananları İndir
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportList()">
                        <i class="fas fa-file-excel me-2"></i>Excel Export
                    </a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($userUploads)): ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($search || $status): ?>
                        Filtreye uygun dosya bulunamadı
                    <?php else: ?>
                        Henüz dosya yüklenmemiş
                    <?php endif; ?>
                </h6>
                <p class="text-muted mb-3">İlk dosyanızı yüklemek için butona tıklayın</p>
                <a href="upload.php" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Dosya Yükle
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="filesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 30px;">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllFiles(this)">
                                </div>
                            </th>
                            <th>Dosya Bilgileri</th>
                            <th>Araç Bilgileri</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userUploads as $upload): ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input file-checkbox" 
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
                                                title="<?php echo htmlspecialchars($upload['original_filename']); ?>">
                                                <?php echo htmlspecialchars($upload['original_filename']); ?>
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
                                                <i class="fas fa-comment fa-sm"></i> Admin notu var
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
                                    <div class="btn-group-vertical btn-group-sm d-flex gap-1" style="width: 140px;">
                                        <!-- Ana İşlemler -->
                                        <?php if ($upload['status'] === 'completed' && !empty($upload['processed_file_path'])): ?>
                                            <a href="download.php?id=<?php echo $upload['id']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-1"></i>İndir
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="viewFileDetails('<?php echo $upload['id']; ?>')">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </button>
                                        
                                        <!-- Durum bazlı işlemler -->
                                        <?php if ($upload['status'] === 'completed'): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                    onclick="requestRevision('<?php echo $upload['id']; ?>')">
                                                <i class="fas fa-redo me-1"></i>Revize Talep
                                            </button>
                                        <?php elseif ($upload['status'] === 'rejected'): ?>
                                            <small class="text-danger mt-1">
                                                <i class="fas fa-info-circle"></i> Reddedildi
                                            </small>
                                        <?php elseif ($upload['status'] === 'pending'): ?>
                                            <small class="text-warning mt-1">
                                                <i class="fas fa-clock"></i> Sırada
                                            </small>
                                        <?php else: ?>
                                            <small class="text-info mt-1">
                                                <i class="fas fa-cogs"></i> İşleniyor
                                            </small>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Sonraki sayfa -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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

<!-- Dosya Detay Modal -->
<div class="modal fade" id="fileDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt me-2"></i>Dosya Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fileDetailContent">
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revize Talep Modal -->
<div class="modal fade" id="revisionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-redo me-2"></i>Revize Talebi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="revisionForm">
                <div class="modal-body">
                    <input type="hidden" name="upload_id" id="revisionUploadId">
                    <input type="hidden" name="request_revision" value="1">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Revize Talebi Hakkında:</strong><br>
                        Dosyanızda bir değişiklik istiyorsanız bu formu kullanabilirsiniz. 
                        Talep incelendikten sonra size geri dönüş yapılacaktır.
                    </div>
                    
                    <div class="mb-3">
                        <label for="revision_notes" class="form-label">
                            <i class="fas fa-comment me-1"></i>
                            Revize Talebi Açıklaması <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="revision_notes" name="revision_notes" 
                                  rows="4" required
                                  placeholder="Lütfen dosyada hangi değişiklikleri istediğinizi detaylı olarak açıklayın..."></textarea>
                        <div class="form-text">
                            Ne tür değişiklik istediğinizi mümkün olduğunca detaylı açıklayın.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane me-2"></i>Revize Talebi Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Sayfa özel JavaScript
$pageJS = "
// File size formatter
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Toggle all checkboxes
function toggleAllFiles(source) {
    const checkboxes = document.querySelectorAll('.file-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

// View file details
function viewFileDetails(uploadId) {
    const modal = new bootstrap.Modal(document.getElementById('fileDetailModal'));
    const content = document.getElementById('fileDetailContent');
    
    content.innerHTML = `
        <div class=\"text-center py-3\">
            <div class=\"spinner-border text-primary\" role=\"status\">
                <span class=\"visually-hidden\">Yükleniyor...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // AJAX call to get file details (you'll need to implement this endpoint)
    fetch('ajax/get-file-details.php?id=' + uploadId)
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

// Request revision
function requestRevision(uploadId) {
    document.getElementById('revisionUploadId').value = uploadId;
    const modal = new bootstrap.Modal(document.getElementById('revisionModal'));
    modal.show();
}

// Download completed files
function downloadCompleted() {
    const completedFiles = [];
    const checkboxes = document.querySelectorAll('.file-checkbox:checked');
    
    checkboxes.forEach(checkbox => {
        // Check if the file is completed (you might need to add data attributes)
        completedFiles.push(checkbox.value);
    });
    
    if (completedFiles.length === 0) {
        showToast('Lütfen indirmek için dosya seçin!', 'warning');
        return;
    }
    
    // Create download links for each completed file
    completedFiles.forEach(fileId => {
        const link = document.createElement('a');
        link.href = 'download.php?id=' + fileId;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}

// Export list to Excel
function exportList() {
    window.open('export-files.php?format=excel', '_blank');
}

// Auto-refresh for processing files
const processingFiles = document.querySelectorAll('tr:has(.badge.bg-info)');
if (processingFiles.length > 0) {
    setTimeout(() => {
        if (!document.hidden) {
            location.reload();
        }
    }, 30000); // 30 saniye sonra yenile
}
";

// Footer include
include '../includes/user_footer.php';
?>
