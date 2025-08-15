<?php
/**
 * Mr ECU - Admin Universal File Detail Page
 * Tüm dosya tiplerini destekleyen admin dosya detay sayfası
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Parametreleri al
$fileId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload';

if (!isValidUUID($fileId)) {
    $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    redirect('file-cancellations.php');
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
            $fileData['file_path'] = '../uploads/' . $fileData['filename'];
            $fileData['download_url'] = "download-file.php?id={$fileId}&type=upload";
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
                SELECT af.*, fu.original_name as main_file_name, fu.plate, fu.user_id,
                       u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name, s.name as series_name, e.name as engine_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM additional_files af
                LEFT JOIN file_uploads fu ON af.related_file_id = fu.id AND af.related_file_type = 'upload'
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN users a ON af.admin_id = a.id
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
            redirect('file-cancellations.php');
    }
    
    if (!$fileData) {
        $_SESSION['error'] = 'Dosya bulunamadı.';
        redirect('file-cancellations.php');
    }
    
} catch (Exception $e) {
    error_log('File detail error: ' . $e->getMessage());
    $_SESSION['error'] = 'Dosya bilgileri alınırken hata oluştu.';
    redirect('file-cancellations.php');
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
                        <i class="fas fa-file-alt me-2 text-primary"></i>
                        <?php echo strtoupper($fileType); ?> Dosya Detayı
                    </h1>
                    <p class="text-muted mb-0">
                        <?php echo htmlspecialchars($fileData['original_name'] ?? $fileData['filename'] ?? 'Bilinmeyen dosya'); ?>
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="file-cancellations.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Geri Dön
                        </a>
                        <?php if (!empty($fileData['download_url'])): ?>
                            <a href="<?php echo $fileData['download_url']; ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-download me-1"></i>İndir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Dosya Bilgileri -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Dosya Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Dosya Adı</label>
                                    <div class="form-control-plaintext">
                                        <i class="fas fa-file me-2 text-primary"></i>
                                        <strong><?php echo htmlspecialchars($fileData['original_name'] ?? $fileData['filename'] ?? 'Bilinmeyen'); ?></strong>
                                    </div>
                                </div>
                                
                                <?php if (!empty($fileData['main_file_name']) && $fileType !== 'upload'): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Ana Dosya</label>
                                    <div class="form-control-plaintext">
                                        <i class="fas fa-link me-2 text-success"></i>
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
                                        <i class="fas fa-hdd me-2 text-info"></i>
                                        <?php echo formatFileSize($fileData['file_size'] ?? 0); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Yüklenme Tarihi</label>
                                    <div class="form-control-plaintext">
                                        <i class="fas fa-calendar me-2 text-warning"></i>
                                        <?php echo formatDate($fileData['upload_date'] ?? $fileData['created_at'] ?? 'Bilinmiyor'); ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($fileData['plate'])): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Araç Plakası</label>
                                    <div class="form-control-plaintext">
                                        <i class="fas fa-car me-2 text-info"></i>
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
                                <i class="fas fa-user me-2"></i>Kullanıcı Bilgileri
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
                                <i class="fas fa-car me-2"></i>Araç Bilgileri
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
                        <i class="fas fa-cog me-2"></i>Admin İşlemleri
                    </h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="file-detail.php?id=<?php echo $fileId; ?>&type=<?php echo $fileType; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i>Detaylı Düzenleme
                        </a>
                        
                        <?php if (!empty($fileData['download_url'])): ?>
                            <a href="<?php echo $fileData['download_url']; ?>" class="btn btn-success" target="_blank">
                                <i class="fas fa-download me-1"></i>Dosyayı İndir
                            </a>
                        <?php endif; ?>
                        
                        <a href="file-cancellations.php" class="btn btn-secondary">
                            <i class="fas fa-list me-1"></i>İptal Talepleri
                        </a>
                    </div>
                </div>
            </div>
        </main>
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
</style>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
