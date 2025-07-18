<?php
/**
 * Mr ECU - Modern Kullanıcı Dosyaları Sayfası (GUID System) - Liste Görünümü
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
    $fileId = sanitize($_POST['file_id']);
    $fileType = sanitize($_POST['file_type']);
    $revisionNotes = sanitize($_POST['revision_notes']);
    
    // GUID format kontrolü
    if (!isValidUUID($fileId)) {
        $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
        header('Location: files.php');
        exit;
    } elseif (empty($revisionNotes)) {
        $_SESSION['error'] = 'Revize talebi için açıklama gereklidir.';
        header('Location: files.php');
        exit;
    } else {
        if ($fileType === 'response') {
            // Yanıt dosyası için revize talebi
            $result = $fileManager->requestResponseRevision($fileId, $userId, $revisionNotes);
        } else {
            // Upload dosyası için revize talebi
            $result = $fileManager->requestRevision($fileId, $userId, $revisionNotes);
        }
        
        if ($result['success']) {
            // Başarılı revize talebi sonrası redirect - PRG pattern
            $_SESSION['success'] = $result['message'];
            header('Location: files.php');
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: files.php');
            exit;
        }
    }
}

// Filtreleme parametreleri
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;

// Sadece ana dosyaları getir (file_uploads tablosundan)
$userFiles = $fileManager->getUserUploads($userId, $page, $limit, $status, $search);
$totalFiles = $fileManager->getUserUploadCount($userId, $status, $search);
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
                    <p class="text-muted mb-0">Yüklediğiniz ana dosyaları görüntüleyin ve yönetin</p>
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
                                   placeholder="Dosya adı, marka, model, plaka...">
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
                <!-- Dosya Tablosu -->
                <div class="table-responsive">
                    <div class="table-container">
                        <table class="table table-hover files-table">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <i class="fas fa-file-alt"></i>
                                    </th>
                                    <th>Dosya Adı</th>
                                    <th>Araç Bilgileri</th>
                                    <th>Durum</th>
                                    <th>Boyut</th>
                                    <th>Tarih</th>
                                    <th width="200" class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userFiles as $file): ?>
                                    <tr class="file-row" data-file-id="<?php echo $file['id']; ?>">
                                        <td>
                                            <div class="file-icon">
                                                <i class="fas fa-file-alt text-primary"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="file-info">
                                                <h6 class="file-name mb-1" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                                    <?php echo htmlspecialchars($file['original_name']); ?>
                                                </h6>
                                                <?php if (!empty($file['upload_notes'])): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-sticky-note me-1"></i>
                                                        <?php echo htmlspecialchars(substr($file['upload_notes'], 0, 50)) . (strlen($file['upload_notes']) > 50 ? '...' : ''); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="vehicle-info">
                                                <div class="brand-model">
                                                    <strong><?php echo htmlspecialchars($file['brand_name'] ?? 'Bilinmiyor'); ?></strong>
                                                    <?php if (!empty($file['model_name'])): ?>
                                                        - <?php echo htmlspecialchars($file['model_name']); ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($file['year'])): ?>
                                                        (<?php echo $file['year']; ?>)
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($file['plate'])): ?>
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-id-card me-1"></i>
                                                        <?php echo strtoupper(htmlspecialchars($file['plate'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusConfig = [
                                                'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock'],
                                                'processing' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cogs'],
                                                'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle'],
                                                'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle']
                                            ];
                                            $config = $statusConfig[$file['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                                            ?>
                                            <span class="badge bg-<?php echo $config['class']; ?> status-badge">
                                                <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                                                <?php echo $config['text']; ?>
                                            </span>
                                            
                                            <?php if ($file['status'] !== 'pending'): ?>
                                                <div class="progress progress-sm mt-2">
                                                    <?php 
                                                    $progressValue = 0;
                                                    $progressClass = 'bg-secondary';
                                                    switch($file['status']) {
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
                                                    <div class="progress-bar <?php echo $progressClass; ?>" 
                                                         style="width: <?php echo $progressValue; ?>%"></div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="file-size"><?php echo formatFileSize($file['file_size'] ?? 0); ?></span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <span class="upload-date"><?php echo date('d.m.Y', strtotime($file['upload_date'])); ?></span>
                                                <small class="text-muted d-block"><?php echo date('H:i', strtotime($file['upload_date'])); ?></small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="action-buttons">
                                                <a href="file-detail.php?id=<?php echo $file['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Detay
                                                </a>
                                                
                                                <?php if ($file['status'] === 'completed'): ?>
                                                    <a href="download.php?id=<?php echo $file['id']; ?>&type=upload" 
                                                       class="btn btn-success btn-sm">
                                                        <i class="fas fa-download me-1"></i>İndir
                                                    </a>
                                                    
                                                    <button type="button" class="btn btn-outline-warning btn-sm" 
                                                            onclick="requestRevision('<?php echo $file['id']; ?>', 'upload')">
                                                        <i class="fas fa-redo me-1"></i>Revize
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
                    <input type="hidden" name="file_id" id="revisionFileId">
                    <input type="hidden" name="file_type" id="revisionFileType">
                    <input type="hidden" name="request_revision" value="1">
                    
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-3 mt-1"></i>
                            <div>
                                <strong>Revize Talebi Hakkında</strong>
                                <p class="mb-0 mt-1" id="revisionInfoText">
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

/* Table Styles */
.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.files-table {
    margin-bottom: 0;
}

.files-table thead {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    color: white;
}

.files-table thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.files-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f8f9fa;
}

.files-table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.files-table td {
    padding: 1rem;
    vertical-align: middle;
    border: none;
}

.file-icon {
    width: 40px;
    height: 40px;
    background: #e3f2fd;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.file-info .file-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vehicle-info .brand-model {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.25rem;
}

.status-badge {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
}

.progress-sm {
    height: 4px;
    border-radius: 2px;
}

.file-size {
    font-weight: 500;
    color: #6c757d;
}

.date-info .upload-date {
    font-weight: 500;
    color: #495057;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.action-buttons .btn {
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    padding: 0.4rem 0.8rem;
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
    .files-table {
        font-size: 0.85rem;
    }
    
    .files-table td {
        padding: 0.75rem 0.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .empty-state-card {
        padding: 2rem 1rem;
    }
    
    .empty-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .stat-card-body {
        padding: 1.25rem;
    }
    
    .stat-number {
        font-size: 1.75rem;
    }
}

/* File row click effect */
.file-row {
    cursor: pointer;
}

.file-row:hover .file-name {
    color: #007bff;
}
</style>

<script>
// Request Revision
function requestRevision(fileId, fileType = 'upload') {
    document.getElementById('revisionFileId').value = fileId;
    document.getElementById('revisionFileType').value = fileType;
    
    // Modal içeriğini dosya tipine göre ayarla
    const revisionInfoText = document.getElementById('revisionInfoText');
    const modalTitle = document.querySelector('#revisionModal .modal-title');
    
    if (fileType === 'response') {
        modalTitle.innerHTML = '<i class="fas fa-redo me-2 text-warning"></i>Yanıt Dosyası Revize Talebi';
        revisionInfoText.innerHTML = 'Yanıt dosyasında bir değişiklik veya düzenleme istiyorsanız bu formu kullanabilirsiniz. Admin ekibimiz dosyanızı yeniden gözden geçirecek ve geliştirilmiş bir sürüm hazırlayacaktır.';
        document.getElementById('revision_notes').placeholder = 'Yanıt dosyasında hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: "Daha fazla güç istiyorum", "Yakıt tüketimi daha iyi olsun", "Torku artmalı" gibi...';
    } else {
        modalTitle.innerHTML = '<i class="fas fa-redo me-2 text-warning"></i>Revize Talebi';
        revisionInfoText.innerHTML = 'Dosyanızda bir değişiklik veya düzenleme istiyorsanız bu formu kullanabilirsiniz. Talep incelendikten sonra size geri dönüş yapılacaktır.';
        document.getElementById('revision_notes').placeholder = 'Lütfen dosyada hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: "Güç artırımı", "EGR kapatma", "DPF silme" gibi...';
    }
    
    // Formu temizle
    document.getElementById('revision_notes').value = '';
    
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

// File row click handler - detay sayfasına gitmek için
document.addEventListener('DOMContentLoaded', function() {
    const fileRows = document.querySelectorAll('.file-row');
    fileRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Eğer buton üzerine tıklandıysa, detay sayfasına gitme
            if (e.target.closest('.btn') || e.target.closest('button')) {
                return;
            }
            
            const fileId = this.dataset.fileId;
            if (fileId) {
                window.location.href = `file-detail.php?id=${fileId}`;
            }
        });
    });
});

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
