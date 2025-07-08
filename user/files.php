<?php
/**
 * Mr ECU - Modern Kullanıcı Dosyaları Sayfası (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/files.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
$userId = $_SESSION['user_id'];

// GUID format kontrolü - User ID
if (!isValidUUID($userId)) {
    redirect('../logout.php');
}

// AJAX response dosya detayları endpoint'i
if (isset($_GET['get_response_details']) && isset($_GET['file_id'])) {
    header('Content-Type: application/json');
    $fileId = sanitize($_GET['file_id']);
    
    if (!isValidUUID($fileId)) {
        echo json_encode(['error' => 'Geçersiz dosya ID formatı']);
        exit;
    }
    
    try {
        // Response dosyasını getir
        $stmt = $pdo->prepare("
            SELECT fr.*, fu.user_id, fu.original_name as original_upload_name,
                   fu.brand_id, fu.model_id, fu.year, fu.ecu_type, fu.engine_code,
                   b.name as brand_name, m.name as model_name,
                   a.username as admin_username
            FROM file_responses fr
            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
            LEFT JOIN brands b ON fu.brand_id = b.id
            LEFT JOIN models m ON fu.model_id = m.id
            LEFT JOIN users a ON fr.admin_id = a.id
            WHERE fr.id = ? AND fu.user_id = ?
        ");
        $stmt->execute([$fileId, $userId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$response) {
            echo json_encode(['error' => 'Yanıt dosyası bulunamadı veya yetkiniz yok']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'file' => $response
        ]);
        
    } catch(PDOException $e) {
        error_log('get_response_details error: ' . $e->getMessage());
        echo json_encode(['error' => 'Veritabanı hatası']);
    }
    
    exit;
}

// AJAX dosya detayları endpoint'i
if (isset($_GET['get_file_details']) && isset($_GET['file_id'])) {
    header('Content-Type: application/json');
    $fileId = sanitize($_GET['file_id']);
    
    if (!isValidUUID($fileId)) {
        echo json_encode(['error' => 'Geçersiz dosya ID formatı']);
        exit;
    }
    
    $upload = $fileManager->getUploadById($fileId);
    if (!$upload) {
        echo json_encode(['error' => 'Dosya bulunamadı']);
        exit;
    }
    
    // Kullanıcı kendi dosyasına erişmeye çalışıyor mu kontrol et
    if ($upload['user_id'] !== $userId) {
        echo json_encode(['error' => 'Bu dosyaya erişim yetkiniz yok']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'file' => $upload
    ]);
    exit;
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
$limit = 12;

// Kullanıcının dosyalarını getir (yüklenen + yanıt dosyaları birleşik)
$userFiles = $fileManager->getUserAllFiles($userId, $page, $limit, $status, $search);

// Sayfalama için tüm dosyaları al (limit olmadan)
$allUserFiles = $fileManager->getUserAllFiles($userId, 1, 1000, $status, $search); // Büyük limit ile tüm dosyaları al
$totalFiles = count($allUserFiles);
$totalPages = ceil($totalFiles / $limit);

// İstatistikler
$stats = $fileManager->getUserFileStats($userId);

$pageTitle = 'Dosyalarım';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-folder me-2 text-primary"></i>Dosyalarım
                    </h1>
                    <p class="text-muted mb-0">Yüklediğiniz dosyaları görüntüleyin, indirin ve yönetin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="upload.php" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i>Yeni Dosya
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                        <div>
                            <strong>Hata!</strong> <?php echo $error; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fa-lg"></i>
                        <div>
                            <strong>Başarılı!</strong> <?php echo $success; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- İstatistik Kartları -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card modern">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                                    <div class="stat-label">Toplam Dosya</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-chart-line text-success"></i>
                                        <span class="text-success">Aktif koleksiyon</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-file"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card modern">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
                                    <div class="stat-label">Bekleyen</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-clock text-warning"></i>
                                        <span class="text-warning">İnceleme sırası</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card modern">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-info"><?php echo $stats['processing']; ?></div>
                                    <div class="stat-label">İşleniyor</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-cogs text-info"></i>
                                        <span class="text-info">Aktif işlem</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-cogs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card modern">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-success"><?php echo $stats['completed']; ?></div>
                                    <div class="stat-label">Tamamlanan</div>
                                    <div class="stat-trend">
                                        <i class="fas fa-download text-success"></i>
                                        <span class="text-success">İndirilmeye hazır</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtre ve Arama -->
            <div class="filter-card mb-4">
                <div class="filter-header">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filtrele ve Ara
                    </h6>
                </div>
                <div class="filter-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">
                                <i class="fas fa-search me-1"></i>Dosya Ara
                            </label>
                            <input type="text" class="form-control form-control-modern" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Dosya adı, marka veya model...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-tag me-1"></i>Durum
                            </label>
                            <select class="form-select form-control-modern" id="status" name="status">
                                <option value="">Tüm Durumlar</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>İşleniyor</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Tamamlanan</option>
                                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                                <option value="response" <?php echo $status === 'response' ? 'selected' : ''; ?>>Yanıt Dosyaları</option>
                            </select>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-modern bg-success">
                                    <i class="fas fa-search me-1"></i>Filtrele
                                </button>
                                <a href="files.php" class="btn btn-outline-secondary btn-modern">
                                    <i class="fas fa-undo me-1"></i>Temizle
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-outline-info btn-modern dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-download me-1"></i>İşlemler
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel me-2"></i>Excel Export
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="refreshPage()">
                                            <i class="fas fa-sync me-2"></i>Sayfayı Yenile
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Dosya Listesi -->
            <?php if (empty($userFiles)): ?>
                <div class="empty-state-card">
                    <div class="empty-content">
                        <div class="empty-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h4>
                            <?php if ($search || $status): ?>
                                Filtreye uygun dosya bulunamadı
                            <?php else: ?>
                                Henüz dosya yüklenmemiş
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-4">
                            <?php if ($search || $status): ?>
                                Farklı filtre kriterleri deneyebilir veya tüm dosyalarınızı görüntüleyebilirsiniz.
                            <?php else: ?>
                                İlk ECU dosyanızı yüklemek için butona tıklayın ve işlem sürecini başlatın.
                            <?php endif; ?>
                        </p>
                        <div class="empty-actions">
                            <?php if ($search || $status): ?>
                                <a href="files.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-list me-2"></i>Tüm Dosyalar
                                </a>
                            <?php endif; ?>
                            <a href="upload.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload me-2"></i>Dosya Yükle
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Dosya Grid -->
                <div class="files-grid">
                    <?php foreach ($userFiles as $file): ?>
                        <div class="file-card <?php echo $file['file_type'] === 'response' ? 'response-file' : 'upload-file'; ?>">
                            <div class="file-card-header">
                                <div class="file-icon-large <?php echo $file['file_type'] === 'response' ? 'response-icon' : ''; ?>">
                                    <i class="fas <?php echo $file['file_type'] === 'response' ? 'fa-reply' : 'fa-file-alt'; ?>"></i>
                                </div>
                                <div class="file-status">
                                    <?php
                                    if ($file['file_type'] === 'response') {
                                        $config = ['class' => 'success', 'text' => 'Yanıt Dosyası', 'icon' => 'reply'];
                                    } else {
                                        $statusConfig = [
                                            'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock'],
                                            'processing' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cogs'],
                                            'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle'],
                                            'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle']
                                        ];
                                        $config = $statusConfig[$file['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $config['class']; ?> status-badge">
                                        <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                                        <?php echo $config['text']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="file-card-body">
                                <h6 class="file-name" title="<?php echo htmlspecialchars($file['original_name'] ?? 'Bilinmeyen dosya'); ?>">
                                    <?php echo htmlspecialchars($file['original_name'] ?? 'Bilinmeyen dosya'); ?>
                                    <?php if ($file['file_type'] === 'response'): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-arrow-left me-1"></i>
                                            Yanıt: <?php echo htmlspecialchars($file['original_upload_name'] ?? ''); ?>
                                        </small>
                                    <?php endif; ?>
                                </h6>
                                
                                <div class="file-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-car me-1"></i>
                                        <span><?php echo htmlspecialchars($file['brand_name'] ?? 'Bilinmiyor'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-cog me-1"></i>
                                        <span><?php echo htmlspecialchars($file['model_name'] ?? 'Model belirtilmemiş'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar me-1"></i>
                                        <span><?php echo date('d.m.Y', strtotime($file['upload_date'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-hdd me-1"></i>
                                        <span><?php echo formatFileSize($file['file_size'] ?? 0); ?></span>
                                    </div>
                                    <?php if ($file['file_type'] === 'response' && !empty($file['admin_username'])): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-user-cog me-1"></i>
                                            <span>Admin: <?php echo htmlspecialchars($file['admin_username']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($file['admin_notes'])): ?>
                                    <div class="admin-note">
                                        <i class="fas fa-comment-dots me-1"></i>
                                        <small>Admin notu mevcut</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="file-card-footer">
                                <div class="file-actions">
                                    <?php if ($file['file_type'] === 'response' || $file['status'] === 'completed'): ?>
                                        <a href="download.php?id=<?php echo $file['id']; ?>&type=<?php echo $file['file_type'] === 'response' ? 'response' : 'upload'; ?>" 
                                           class="btn btn-success btn-sm action-btn">
                                            <i class="fas fa-download me-1"></i>İndir
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-outline-primary btn-sm action-btn" 
                                            onclick="viewFileDetails('<?php echo $file['id']; ?>', '<?php echo $file['file_type']; ?>')">
                                        <i class="fas fa-eye me-1"></i>Detay
                                    </button>
                                    
                                    <?php if ($file['file_type'] === 'upload' && $file['status'] === 'completed'): ?>
                                        <button type="button" class="btn btn-outline-warning btn-sm action-btn" 
                                                onclick="requestRevision('<?php echo $file['id']; ?>')">
                                            <i class="fas fa-redo me-1"></i>Revize
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($file['file_type'] !== 'response'): ?>
                                    <div class="file-progress">
                                        <?php 
                                        $progressValue = 0;
                                        $progressClass = 'bg-secondary';
                                        switch($file['status']) {
                                            case 'pending':
                                                $progressValue = 25;
                                                $progressClass = 'bg-warning';
                                                break;
                                            case 'processing':
                                                $progressValue = 75;
                                                $progressClass = 'bg-info';
                                                break;
                                            case 'completed':
                                                $progressValue = 100;
                                                $progressClass = 'bg-success';
                                                break;
                                            case 'rejected':
                                                $progressValue = 100;
                                                $progressClass = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar <?php echo $progressClass; ?>" 
                                                 style="width: <?php echo $progressValue; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <nav aria-label="Dosya sayfalama">
                            <ul class="pagination justify-content-center">
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
                        
                        <div class="pagination-info">
                            <small class="text-muted">
                                Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> 
                                (Toplam <?php echo $totalFiles; ?> dosya)
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Dosya Detay Modal -->
<div class="modal fade" id="fileDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt me-2 text-primary"></i>Dosya Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fileDetailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                    <p class="mt-2 text-muted">Dosya detayları yükleniyor...</p>
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
                    <i class="fas fa-redo me-2 text-warning"></i>Revize Talebi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="revisionForm">
                <div class="modal-body">
                    <input type="hidden" name="upload_id" id="revisionUploadId">
                    <input type="hidden" name="request_revision" value="1">
                    
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-3 mt-1"></i>
                            <div>
                                <strong>Revize Talebi Hakkında</strong>
                                <p class="mb-0 mt-1">
                                    Dosyanızda bir değişiklik veya düzenleme istiyorsanız bu formu kullanabilirsiniz. 
                                    Talep incelendikten sonra size geri dönüş yapılacaktır.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="revision_notes" class="form-label fw-semibold">
                            <i class="fas fa-comment me-1"></i>
                            Revize Talebi Açıklaması <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-modern" id="revision_notes" name="revision_notes" 
                                  rows="5" required
                                  placeholder="Lütfen dosyada hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: 'Güç artırımı', 'EGR kapatma', 'DPF silme' gibi..."></textarea>
                        <div class="form-text">
                            <i class="fas fa-lightbulb me-1"></i>
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

<style>
/* Modern Files Page Styles */
.stat-card.modern {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.stat-card.modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.stat-card-body {
    padding: 1.75rem;
}

.stat-number {
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 0.75rem;
}

.stat-trend {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
}

.stat-trend i {
    margin-right: 0.375rem;
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

/* Filter Card */
.filter-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
}

.filter-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    border-radius: 12px 12px 0 0;
}

.filter-body {
    padding: 1.5rem;
}

/* Empty State */
.empty-state-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 4rem 2rem;
    text-align: center;
}

.empty-content {
    max-width: 400px;
    margin: 0 auto;
}

.empty-icon {
    font-size: 5rem;
    color: #e9ecef;
    margin-bottom: 2rem;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Files Grid */
.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.file-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    overflow: hidden;
}

.file-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.file-card-header {
    padding: 1.5rem 1.5rem 1rem;
    position: relative;
}

.file-icon-large {
    width: 64px;
    height: 64px;
    background: grey;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.file-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
}

.status-badge {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
}

.file-card-body {
    padding: 0 1.5rem 1rem;
}

.file-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.file-meta {
    display: grid;
    gap: 0.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
    color: #6c757d;
}

.meta-item i {
    color: #9ca3af;
    width: 16px;
}

.admin-note {
    margin-top: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: #fff3cd;
    border-radius: 6px;
    color: #856404;
    font-size: 0.8rem;
}

.file-card-footer {
    padding: 1rem 1.5rem 1.5rem;
    border-top: 1px solid #f8f9fa;
}

.file-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
}

.action-btn {
    flex: 1;
    min-width: 80px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
}

.file-progress {
    margin-top: 0.75rem;
}

.progress-sm {
    height: 4px;
    border-radius: 2px;
}

/* Pagination */
.pagination-wrapper {
    margin-top: 2rem;
    text-align: center;
}

.pagination-info {
    margin-top: 1rem;
}

.pagination .page-link {
    border-radius: 8px;
    margin: 0 2px;
    border: 1px solid #e9ecef;
    color: #495057;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    border-color: transparent;
    color: white;
}

.pagination .page-link:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

/* Modern Form Controls */
.form-control-modern {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-modern {
    border-radius: 8px;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-modern:hover {
    transform: translateY(-1px);
}

/* Alert Modern */
.alert-modern {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Responsive */
@media (max-width: 767.98px) {
    .files-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .file-card-header {
        padding: 1.25rem 1.25rem 0.75rem;
    }
    
    .file-card-body {
        padding: 0 1.25rem 0.75rem;
    }
    
    .file-card-footer {
        padding: 0.75rem 1.25rem 1.25rem;
    }
    
    .file-actions {
        flex-direction: column;
    }
    
    .action-btn {
        flex: none;
    }
    
    .empty-state-card {
        padding: 2rem 1rem;
    }
    
    .empty-actions {
        flex-direction: column;
        align-items: center;
    }
}

/* File Detail Modal Styles */
.file-detail-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1rem;
}

.file-detail-header .file-icon-large {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.75rem;
}

.detail-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    font-weight: 500;
    color: #6c757d;
    min-width: 120px;
    flex-shrink: 0;
}

.detail-item .value {
    color: #495057;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
}

.notes-content, .admin-notes-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    border-left: 3px solid #667eea;
    line-height: 1.6;
    color: #495057;
}

.admin-notes-content {
    border-left-color: #28a745;
    background: #f8fff9;
}

/* Responsive modal content */
@media (max-width: 767.98px) {
    .file-detail-header .d-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .detail-item .value {
        text-align: left;
    }
}

/* Response File Styles */
.response-file {
    border-left: 4px solid #28a745;
    background: linear-gradient(145deg, #ffffff 0%, #f8fff9 100%);
}

.response-file:hover {
    border-left-color: #1e7e34;
    box-shadow: 0 8px 30px rgba(40, 167, 69, 0.15);
}

.response-icon {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.upload-file {
    border-left: 4px solid #007bff;
}

.upload-file:hover {
    border-left-color: #0056b3;
}
</style>

<script>
// File Details Modal
function viewFileDetails(uploadId, fileType = 'upload') {
    const modal = new bootstrap.Modal(document.getElementById('fileDetailModal'));
    const content = document.getElementById('fileDetailContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <p class="mt-2 text-muted">Dosya detayları yükleniyor...</p>
        </div>
    `;
    
    modal.show();
    
    // Response dosyaları için farklı endpoint
    let endpoint = '';
    if (fileType === 'response') {
        endpoint = `?get_response_details=1&file_id=${encodeURIComponent(uploadId)}`;
    } else {
        endpoint = `?get_file_details=1&file_id=${encodeURIComponent(uploadId)}`;
    }
    
    // AJAX ile dosya detaylarını getir
    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.error}
                    </div>
                `;
                return;
            }
            
            if (data.success && data.file) {
                const file = data.file;
                const isResponse = fileType === 'response';
                
                const statusConfig = {
                    'pending': { class: 'warning', text: 'Bekliyor', icon: 'clock' },
                    'processing': { class: 'info', text: 'İşleniyor', icon: 'cogs' },
                    'completed': { class: 'success', text: 'Tamamlandı', icon: 'check-circle' },
                    'rejected': { class: 'danger', text: 'Reddedildi', icon: 'times-circle' }
                };
                
                const status = isResponse ? 
                    { class: 'success', text: 'Yanıt Dosyası', icon: 'reply' } :
                    (statusConfig[file.status] || { class: 'secondary', text: 'Bilinmiyor', icon: 'question' });
                
                content.innerHTML = `
                    <div class="file-detail-content">
                        <!-- Dosya Başlık -->
                        <div class="file-detail-header mb-4">
                            <div class="d-flex align-items-center">
                                <div class="file-icon-large me-3 ${isResponse ? 'response-icon' : ''}">
                                    <i class="fas fa-${isResponse ? 'reply' : 'file-alt'}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">${file.original_name || 'Bilinmeyen dosya'}</h5>
                                    ${isResponse && file.original_upload_name ? `
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-arrow-left me-1"></i>
                                            Yanıt: ${file.original_upload_name}
                                        </small>
                                    ` : ''}
                                    <span class="badge bg-${status.class}">
                                        <i class="fas fa-${status.icon} me-1"></i>${status.text}
                                    </span>
                                </div>
                                ${(isResponse || file.status === 'completed') ? `
                                    <a href="download.php?id=${file.id}&type=${isResponse ? 'response' : 'upload'}" class="btn btn-success">
                                        <i class="fas fa-download me-2"></i>İndir
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                        
                        <!-- Dosya Bilgileri -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Dosya Bilgileri
                                </h6>
                                <div class="detail-list">
                                    <div class="detail-item">
                                        <span class="label">Dosya ID:</span>
                                        <span class="value font-monospace">${file.id}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Orijinal Ad:</span>
                                        <span class="value">${file.original_name || 'Belirtilmemiş'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Dosya Boyutu:</span>
                                        <span class="value">${formatFileSize(file.file_size || 0)}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">${isResponse ? 'Oluşturulma' : 'Yüklenme'} Tarihi:</span>
                                        <span class="value">${formatDate(file.upload_date)}</span>
                                    </div>
                                    ${file.processed_date && !isResponse ? `
                                        <div class="detail-item">
                                            <span class="label">Tamamlanma Tarihi:</span>
                                            <span class="value">${formatDate(file.processed_date)}</span>
                                        </div>
                                    ` : ''}
                                    ${isResponse && file.admin_username ? `
                                        <div class="detail-item">
                                            <span class="label">Oluşturan Admin:</span>
                                            <span class="value">${file.admin_username}</span>
                                        </div>
                                    ` : ''}
                                    ${file.credits_charged ? `
                                        <div class="detail-item">
                                            <span class="label">Ücret:</span>
                                            <span class="value">${file.credits_charged} kredi</span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-car me-2"></i>Araç Bilgileri
                                </h6>
                                <div class="detail-list">
                                    <div class="detail-item">
                                        <span class="label">Marka:</span>
                                        <span class="value">${file.brand_name || 'Belirtilmemiş'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Model:</span>
                                        <span class="value">${file.model_name || 'Belirtilmemiş'}</span>
                                    </div>
                                    ${file.year ? `
                                        <div class="detail-item">
                                            <span class="label">Yıl:</span>
                                            <span class="value">${file.year}</span>
                                        </div>
                                    ` : ''}
                                    ${file.ecu_type ? `
                                        <div class="detail-item">
                                            <span class="label">ECU Tipi:</span>
                                            <span class="value">${file.ecu_type}</span>
                                        </div>
                                    ` : ''}
                                    ${file.engine_code ? `
                                        <div class="detail-item">
                                            <span class="label">Motor Kodu:</span>
                                            <span class="value">${file.engine_code}</span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        ${file.notes && file.notes !== file.admin_notes ? `
                            <div class="mt-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-comment me-2"></i>${isResponse ? 'Orijinal' : ''} Notlar
                                </h6>
                                <div class="notes-content">
                                    ${file.notes.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        ` : ''}
                        
                        ${file.admin_notes ? `
                            <div class="mt-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-user-cog me-2"></i>Admin Notları
                                </h6>
                                <div class="admin-notes-content">
                                    ${file.admin_notes.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        ` : ''}
                        
                        ${!isResponse && file.status === 'completed' ? `
                            <div class="mt-4 text-center">
                                <button type="button" class="btn btn-warning me-2" onclick="requestRevision('${file.id}'); bootstrap.Modal.getInstance(document.getElementById('fileDetailModal')).hide();">
                                    <i class="fas fa-redo me-2"></i>Revize Talep Et
                                </button>
                            </div>
                        ` : ''}
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Dosya detayları alınamadı.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Dosya detayları yüklenirken hata oluştu.
                </div>
            `;
        });
}

// Helper functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    if (!dateString) return 'Belirtilmemiş';
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR') + ' ' + date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
}

// Request Revision
function requestRevision(uploadId) {
    document.getElementById('revisionUploadId').value = uploadId;
    const modal = new bootstrap.Modal(document.getElementById('revisionModal'));
    modal.show();
}

// Export Functions
function exportToExcel() {
    window.location.href = 'export-files.php?format=excel';
}

function refreshPage() {
    location.reload();
}

// Auto-refresh for processing files
<?php if (!empty($userFiles)): ?>
const hasProcessingFiles = <?php echo json_encode(array_filter($userFiles, function($u) { return $u['status'] === 'processing'; })); ?>.length > 0;
if (hasProcessingFiles) {
    setTimeout(() => {
        if (!document.hidden) {
            location.reload();
        }
    }, 30000); // 30 seconds
}
<?php endif; ?>
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>