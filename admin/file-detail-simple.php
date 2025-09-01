<?php
/**
 * Mr ECU - Admin File Detail Page
 * Tüm dosya tiplerini destekleyen admin dosya detay sayfası
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
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

// Parametreleri al
$fileId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload';

if (!isValidUUID($fileId)) {
    $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    redirect('uploads.php');
}

// Admin tarafından direkt dosya iptal etme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_cancel_file'])) {
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
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: file-detail.php?id={$fileId}&type={$fileType}");
    exit;
}

$pageTitle = 'Dosya Detayı';
$fileData = [];

try {
    switch ($fileType) {
        case 'upload':
            $stmt = $pdo->prepare("
                SELECT fu.*, 
                       u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name, s.name as series_name, e.name as engine_name,
                       d.name as device_name, ec.name as ecu_name
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                LEFT JOIN devices d ON fu.device_id = d.id
                LEFT JOIN ecus ec ON fu.ecu_id = ec.id
                WHERE fu.id = ?
            ");
            $stmt->execute([$fileId]);
            $fileData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fileData) {
                $fileData['file_path'] = '../uploads/' . $fileData['filename'];
                $fileData['download_url'] = "download-file.php?id={$fileId}&type=upload";
            }
            break;
            
        case 'response':
            $stmt = $pdo->prepare("
                SELECT fr.*, fu.original_name as main_file_name, fu.plate, fu.user_id,
                       u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name, s.name as series_name, e.name as engine_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN users a ON fr.admin_id = a.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                WHERE fr.id = ?
            ");
            $stmt->execute([$fileId]);
            $fileData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fileData) {
                $fileData['file_path'] = '../uploads/response_files/' . $fileData['filename'];
                $fileData['download_url'] = "download-file.php?id={$fileId}&type=response";
            }
            break;
            
        case 'revision':
            $stmt = $pdo->prepare("
                SELECT rf.*, r.upload_id, r.admin_notes as revision_admin_notes,
                       fu.original_name as main_file_name, fu.plate, fu.user_id,
                       u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name, s.name as series_name, e.name as engine_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN users a ON rf.admin_id = a.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                WHERE rf.id = ?
            ");
            $stmt->execute([$fileId]);
            $fileData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fileData) {
                $fileData['file_path'] = '../uploads/revision_files/' . $fileData['filename'];
                $fileData['download_url'] = "download-file.php?id={$fileId}&type=revision";
            }
            break;
            
        case 'additional':
            $stmt = $pdo->prepare("
                SELECT af.*, 
                       COALESCE(fu.original_name, fr.original_name, rf.original_name) as main_file_name, 
                       COALESCE(fu.plate, 
                               (SELECT fu2.plate FROM file_uploads fu2 WHERE fu2.id = (SELECT fr2.upload_id FROM file_responses fr2 WHERE fr2.id = af.related_file_id)),
                               (SELECT fu3.plate FROM file_uploads fu3 WHERE fu3.id = (SELECT r.upload_id FROM revisions r LEFT JOIN revision_files rf3 ON r.id = rf3.revision_id WHERE rf3.id = af.related_file_id))
                       ) as plate,
                       COALESCE(fu.user_id, af.receiver_id) as user_id,
                       u.username, u.email, u.first_name, u.last_name,
                       sender.username as sender_username, sender.first_name as sender_first_name, sender.last_name as sender_last_name,
                       receiver.username as receiver_username, receiver.first_name as receiver_first_name, receiver.last_name as receiver_last_name,
                       b.name as brand_name, m.name as model_name, s.name as series_name, e.name as engine_name
                FROM additional_files af
                LEFT JOIN file_uploads fu ON af.related_file_id = fu.id AND af.related_file_type = 'upload'
                LEFT JOIN file_responses fr ON af.related_file_id = fr.id AND af.related_file_type = 'response'
                LEFT JOIN revision_files rf ON af.related_file_id = rf.id AND af.related_file_type = 'revision'
                LEFT JOIN users u ON COALESCE(fu.user_id, af.receiver_id) = u.id
                LEFT JOIN users sender ON af.sender_id = sender.id
                LEFT JOIN users receiver ON af.receiver_id = receiver.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                WHERE af.id = ?
            ");
            $stmt->execute([$fileId]);
            $fileData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fileData) {
                $fileData['file_path'] = '../uploads/additional_files/' . $fileData['filename'];
                $fileData['download_url'] = "download-file.php?id={$fileId}&type=additional";
            }
            break;
            
        default:
            $_SESSION['error'] = 'Geçersiz dosya tipi.';
            redirect('uploads.php');
    }
    
    if (!$fileData) {
        $_SESSION['error'] = 'Dosya bulunamadı.';
        redirect('uploads.php');
    }
    
} catch (Exception $e) {
    error_log('File detail error: ' . $e->getMessage());
    $_SESSION['error'] = 'Dosya bilgileri alınırken hata oluştu.';
    redirect('uploads.php');
}

// Header include
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-folder2-open-alt me-2 text-primary"></i>
                        <?php echo strtoupper($fileType); ?> Dosya Detayı
                    </h1>
                    <p class="text-muted mb-0">
                        <?php echo htmlspecialchars($fileData['original_name'] ?? $fileData['filename'] ?? 'Bilinmeyen dosya'); ?>
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="uploads.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Geri Dön
                        </a>
                        <?php if (!empty($fileData['download_url'])): ?>
                            <a href="<?php echo $fileData['download_url']; ?>" class="btn btn-primary" target="_blank">
                                <i class="bi bi-download me-1"></i>İndir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Hata!</strong> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Başarılı!</strong> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Dosya Bilgileri -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2"></i>Dosya Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Dosya Adı</label>
                                    <div class="form-control-plaintext">
                                        <i class="bi bi-folder2-open me-2 text-primary"></i>
                                        <strong><?php echo htmlspecialchars($fileData['original_name'] ?? $fileData['filename'] ?? 'Bilinmeyen'); ?></strong>
                                    </div>
                                </div>
                                
                                <?php if (!empty($fileData['main_file_name']) && $fileType !== 'upload'): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Ana Dosya</label>
                                    <div class="form-control-plaintext">
                                        <i class="bi bi-link me-2 text-success"></i>
                                        <?php echo htmlspecialchars($fileData['main_file_name']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Dosya Tipi</label>
                                    <div class="form-control-plaintext">
                                        <span class="badge bg-secondary"><?php echo strtoupper($fileType); ?></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Dosya Boyutu</label>
                                    <div class="form-control-plaintext">
                                        <i class="bi bi-hdd me-2 text-info"></i>
                                        <?php echo formatFileSize($fileData['file_size'] ?? 0); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Yüklenme Tarihi</label>
                                    <div class="form-control-plaintext">
                                        <i class="bi bi-calendar me-2 text-warning"></i>
                                        <?php echo formatDate($fileData['upload_date'] ?? $fileData['created_at'] ?? 'Bilinmiyor'); ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($fileData['plate'])): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Araç Plakası</label>
                                    <div class="form-control-plaintext">
                                        <i class="bi bi-car me-2 text-info"></i>
                                        <span class="badge bg-info"><?php echo strtoupper(htmlspecialchars($fileData['plate'])); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-user me-2"></i>Kullanıcı Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted">Kullanıcı Adı</label>
                                <div class="form-control-plaintext">
                                    <strong><?php echo htmlspecialchars($fileData['username'] ?? 'Bilinmiyor'); ?></strong>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Ad Soyad</label>
                                <div class="form-control-plaintext">
                                    <?php echo htmlspecialchars(($fileData['first_name'] ?? '') . ' ' . ($fileData['last_name'] ?? '')); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">E-posta</label>
                                <div class="form-control-plaintext">
                                    <a href="mailto:<?php echo htmlspecialchars($fileData['email'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($fileData['email'] ?? 'Bilinmiyor'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($fileType === 'upload' && !empty($fileData['brand_name'])): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-car me-2"></i>Araç Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label text-muted">Marka</label>
                                <div class="form-control-plaintext">
                                    <?php echo htmlspecialchars($fileData['brand_name'] ?? 'Bilinmiyor'); ?>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label text-muted">Model</label>
                                <div class="form-control-plaintext">
                                    <?php echo htmlspecialchars($fileData['model_name'] ?? 'Bilinmiyor'); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($fileData['year'])): ?>
                            <div class="mb-2">
                                <label class="form-label text-muted">Yıl</label>
                                <div class="form-control-plaintext">
                                    <?php echo htmlspecialchars($fileData['year']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Admin İşlemleri -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cog me-2"></i>Admin İşlemleri
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2" role="group">
                        <?php if (!empty($fileData['download_url'])): ?>
                            <a href="<?php echo $fileData['download_url']; ?>" class="btn btn-success" target="_blank">
                                <i class="bi bi-download me-1"></i>Dosyayı İndir
                            </a>
                        <?php endif; ?>
                        
                        <!-- Admin İptal Butonu -->
                        <?php if (!isset($fileData['is_cancelled']) || !$fileData['is_cancelled']): ?>
                            <button type="button" class="btn btn-danger" 
                                    onclick="showCancelModal('<?php echo $fileId; ?>', '<?php echo $fileType; ?>', '<?php echo htmlspecialchars($fileData['original_name'] ?? $fileData['filename'] ?? 'Bilinmeyen dosya', ENT_QUOTES); ?>')">
                                <i class="bi bi-times me-1"></i>Dosyayı İptal Et
                            </button>
                        <?php else: ?>
                            <span class="btn btn-secondary disabled">
                                <i class="bi bi-ban me-1"></i>İptal Edilmiş
                            </span>
                        <?php endif; ?>
                        
                        <a href="uploads.php" class="btn btn-outline-secondary">
                            <i class="bi bi-list me-1"></i>Dosya Listesi
                        </a>
                        
                        <a href="user-details.php?id=<?php echo $fileData['user_id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-user me-1"></i>Kullanıcı Detayı
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Admin İptal Modalı -->
<div class="modal fade" id="adminCancelModal" tabindex="-1" aria-labelledby="adminCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="adminCancelModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Dosya İptal Onayı
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
                        <h6 class="mb-2 text-dark text-center">Bu dosyayı iptal etmek istediğinizden emin misiniz?</h6>
                        <p class="text-muted mb-3 text-center">
                            <strong>Dosya:</strong> <span id="cancelFileName"></span>
                        </p>
                        <div class="alert alert-warning d-flex align-items-center mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>Bu işlem dosyayı gizleyecek ve eğer ücretli ise kullanıcıya kredi iadesi yapacaktır.</small>
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
                        <i class="bi bi-times me-1"></i>
                        İptal
                    </button>
                    <button type="submit" class="btn btn-danger px-4" style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                        <i class="bi bi-check me-1"></i>
                        Evet, İptal Et
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-control-plaintext {
    padding: 0.375rem 0;
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding-left: 0.75rem;
    margin-bottom: 0;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 0.75rem;
}

.btn-group .btn {
    font-size: 0.9rem;
}

/* Admin Cancel Modal Styling */
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

#adminCancelModal .modal-content {
    border-radius: 1rem;
    overflow: hidden;
}

#adminCancelModal .modal-header {
    padding: 1.5rem 2rem 1rem;
    border-bottom: none;
}

#adminCancelModal .modal-body {
    padding: 1rem 2rem 1.5rem;
}

#adminCancelModal .modal-footer {
    padding: 0rem 3rem 3rem 0rem;
    background: #f8f9fa;
    margin: 0 -2rem -2rem;
    padding-top: 1.5rem;
}

#adminCancelModal .btn-danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    border-color: #c82333;
    transform: translateY(-2px);
}

#adminCancelModal .btn-secondary:hover {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border-color: #6c757d;
    transform: translateY(-2px);
}

/* Cancel modal animation */
#adminCancelModal.fade .modal-dialog {
    transition: transform 0.4s ease-out;
    transform: scale(0.8) translateY(-50px);
}

#adminCancelModal.show .modal-dialog {
    transform: scale(1) translateY(0);
}
</style>

<script>
// Admin Cancel Modal Functions
function showCancelModal(fileId, fileType, fileName) {
    document.getElementById('cancelFileId').value = fileId;
    document.getElementById('cancelFileType').value = fileType;
    document.getElementById('cancelFileName').textContent = fileName;
    document.getElementById('adminNotes').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
    modal.show();
}
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
