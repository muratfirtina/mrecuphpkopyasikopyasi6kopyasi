<?php
/**
 * Design Panel - Medya Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Sayfa bilgileri
$pageTitle = 'Medya Yönetimi';
$pageDescription = 'Site medya dosyalarını yönetin';
$breadcrumbs = [
    ['title' => 'Design Panel', 'url' => 'index.php'],
    ['title' => 'Medya Yönetimi']
];

// Upload klasörü kontrolü
$uploadPath = '../assets/images/';
if (!file_exists($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'upload':
                    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['file'];
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        
                        if (!in_array($file['type'], $allowedTypes)) {
                            throw new Exception('Sadece resim dosyaları yüklenebilir (JPEG, PNG, GIF, WebP)');
                        }
                        
                        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                            throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz');
                        }
                        
                        $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $file['name']);
                        $filePath = $uploadPath . $fileName;
                        
                        if (move_uploaded_file($file['tmp_name'], $filePath)) {
                            // Veritabanına kaydet
                            $stmt = $pdo->prepare("
                                INSERT INTO media_files (filename, original_name, file_path, file_size, mime_type, file_type, created_at)
                                VALUES (?, ?, ?, ?, ?, 'image', NOW())
                            ");
                            
                            $stmt->execute([
                                $fileName,
                                $file['name'],
                                'assets/images/' . $fileName,
                                $file['size'],
                                $file['type']
                            ]);
                            
                            header('Location: media.php?success=Dosya başarıyla yüklendi');
                            exit;
                        } else {
                            throw new Exception('Dosya yüklenemedi');
                        }
                    }
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("SELECT file_path FROM media_files WHERE id = ?");
                    $stmt->execute([(int)$_POST['id']]);
                    $media = $stmt->fetch();
                    
                    if ($media) {
                        // Dosyayı sil
                        $fullPath = '../' . $media['file_path'];
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                        
                        // Veritabanından sil
                        $stmt = $pdo->prepare("DELETE FROM media_files WHERE id = ?");
                        $stmt->execute([(int)$_POST['id']]);
                        
                        header('Location: media.php?success=Medya dosyası silindi');
                        exit;
                    }
                    break;
                    
                case 'update_alt':
                    $stmt = $pdo->prepare("UPDATE media_files SET alt_text = ?, caption = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['alt_text'],
                        $_POST['caption'],
                        (int)$_POST['id']
                    ]);
                    
                    header('Location: media.php?success=Medya bilgileri güncellendi');
                    exit;
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Medya dosyalarını al
try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 12;
    $offset = ($page - 1) * $perPage;
    
    // Toplam sayıyı al
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM media_files");
    $totalFiles = $totalStmt->fetchColumn();
    $totalPages = ceil($totalFiles / $perPage);
    
    // Dosyaları al
    $stmt = $pdo->query("SELECT * FROM media_files ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $mediaFiles = $stmt->fetchAll();
    
    // Boyut istatistikleri
    $statsStmt = $pdo->query("SELECT 
        COUNT(*) as total_files,
        SUM(file_size) as total_size,
        AVG(file_size) as avg_size,
        MAX(file_size) as max_size
    FROM media_files");
    $stats = $statsStmt->fetch();
    
} catch (Exception $e) {
    $mediaFiles = [];
    $stats = ['total_files' => 0, 'total_size' => 0, 'avg_size' => 0, 'max_size' => 0];
    $error = "Medya dosyaları alınamadı: " . $e->getMessage();
}

// Header include
include '../includes/design_header.php';
?>

<!-- Medya Yönetimi -->
<div class="row mb-4">
    <div class="col-12">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- İstatistikler -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-primary text-white rounded-circle p-3">
                        <i class="bi bi-images fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo $stats['total_files']; ?></div>
                    <div class="stat-label">Toplam Dosya</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-info text-white rounded-circle p-3">
                        <i class="bi bi-hdd fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo formatBytes($stats['total_size']); ?></div>
                    <div class="stat-label">Toplam Boyut</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-success text-white rounded-circle p-3">
                        <i class="bi bi-chart-bar fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo formatBytes($stats['avg_size']); ?></div>
                    <div class="stat-label">Ortalama Boyut</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-warning text-white rounded-circle p-3">
                        <i class="bi bi-weight fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo formatBytes($stats['max_size']); ?></div>
                    <div class="stat-label">En Büyük Dosya</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dosya Yükleme ve Medya Listesi -->
<div class="row">
    <div class="col-12">
        <div class="design-card">
            <div class="design-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-photo-video me-2"></i>Medya Yönetimi
                </h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="bi bi-upload me-2"></i>Dosya Yükle
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($mediaFiles)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-photo-video text-muted" style="font-size: 4rem;"></i>
                        <h4 class="text-muted mt-3">Henüz medya dosyası bulunmuyor</h4>
                        <p class="text-muted">İlk medya dosyanızı yüklemek için "Dosya Yükle" butonuna tıklayın.</p>
                        <button type="button" class="btn btn-design-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-upload me-2"></i>İlk Dosyayı Yükle
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Medya Grid -->
                    <div class="row g-3" id="mediaGrid">
                        <?php foreach ($mediaFiles as $media): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="card media-card h-100">
                                    <div class="position-relative">
                                        <img src="../<?php echo htmlspecialchars($media['file_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($media['alt_text'] ?: $media['original_name']); ?>"
                                             class="card-img-top media-image" style="height: 200px; object-fit: cover;">
                                        
                                        <!-- Dosya Boyutu Badge -->
                                        <span class="position-absolute top-0 end-0 badge bg-dark m-2">
                                            <?php echo formatBytes($media['file_size']); ?>
                                        </span>
                                        
                                        <!-- Hover Overlay -->
                                        <div class="media-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-light btn-sm" onclick="viewMedia(<?php echo $media['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-primary btn-sm" onclick="editMedia(<?php echo $media['id']; ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#editModal">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-info btn-sm" onclick="copyUrl('<?php echo '../' . $media['file_path']; ?>')">
                                                    <i class="bi bi-copy"></i>
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $media['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Bu dosyayı silmek istediğinizden emin misiniz?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($media['original_name']); ?></h6>
                                        <p class="card-text small text-muted mb-2">
                                            <?php echo htmlspecialchars($media['alt_text'] ?: 'Alt metin yok'); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y', strtotime($media['created_at'])); ?>
                                            </small>
                                            <span class="badge bg-secondary"><?php echo strtoupper(pathinfo($media['original_name'], PATHINFO_EXTENSION)); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Medya sayfalaması" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="media.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="modal-header">
                    <h5 class="modal-title">Dosya Yükle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload">
                    
                    <!-- Drag & Drop Area -->
                    <div class="upload-area" id="uploadArea">
                        <i class="bi bi-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <h5>Dosyaları sürükleyin veya tıklayın</h5>
                        <p class="text-muted">JPEG, PNG, GIF, WebP formatları desteklenir</p>
                        <p class="small text-muted">Maksimum dosya boyutu: 5MB</p>
                        <input type="file" class="d-none" id="fileInput" name="file" accept="image/*" required>
                    </div>
                    
                    <!-- Preview -->
                    <div id="preview" class="mt-3" style="display: none;">
                        <img id="previewImage" class="img-fluid rounded" style="max-height: 200px;" alt="Preview">
                        <div class="mt-2">
                            <strong id="fileName"></strong>
                            <span id="fileSize" class="text-muted ms-2"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-design-primary" id="uploadBtn" disabled>
                        <i class="bi bi-upload me-2"></i>Yükle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editForm">
                <div class="modal-header">
                    <h5 class="modal-title">Medya Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_alt">
                    <input type="hidden" name="id" id="editId">
                    
                    <div class="text-center mb-3">
                        <img id="editPreview" class="img-fluid rounded" style="max-height: 200px;" alt="Edit Preview">
                    </div>
                    
                    <div class="mb-3">
                        <label for="alt_text" class="form-label">Alt Metin</label>
                        <input type="text" class="form-control" id="alt_text" name="alt_text" 
                               placeholder="Resim açıklaması...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="caption" class="form-label">Başlık</label>
                        <textarea class="form-control" id="caption" name="caption" rows="3" 
                                  placeholder="Resim başlığı veya açıklaması..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-design-primary">
                        <i class="bi bi-save me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTitle">Medya Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="viewImage" class="img-fluid" alt="View Image">
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Dosya Adı:</strong>
                            <p id="viewFileName" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Boyut:</strong>
                            <p id="viewFileSize" class="text-muted"></p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <strong>URL:</strong>
                        <div class="input-group">
                            <input type="text" class="form-control" id="viewUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyText('viewUrl')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.media-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.media-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.media-image {
    transition: all 0.3s ease;
}

.media-overlay {
    background: rgba(0,0,0,0.7);
    opacity: 0;
    transition: all 0.3s ease;
}

.media-card:hover .media-overlay {
    opacity: 1;
}

.upload-area.dragover {
    border-color: var(--design-primary);
    background: rgba(102, 126, 234, 0.1);
}
</style>

<script>
// Medya verileri JSON olarak
const mediaFiles = <?php echo json_encode($mediaFiles); ?>;

// Drag & Drop functionality
initDragDrop('#uploadArea', '#fileInput');

// File input change event
document.getElementById('fileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        previewFile(file);
        document.getElementById('uploadBtn').disabled = false;
    }
});

function previewFile(file) {
    const preview = document.getElementById('preview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    
    const reader = new FileReader();
    reader.onload = function(e) {
        previewImage.src = e.target.result;
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function viewMedia(id) {
    const media = mediaFiles.find(m => m.id == id);
    if (!media) return;
    
    document.getElementById('viewTitle').textContent = media.original_name;
    document.getElementById('viewImage').src = '../' + media.file_path;
    document.getElementById('viewFileName').textContent = media.original_name;
    document.getElementById('viewFileSize').textContent = formatBytes(media.file_size);
    document.getElementById('viewUrl').value = window.location.origin + '/' + media.file_path;
    
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

function editMedia(id) {
    const media = mediaFiles.find(m => m.id == id);
    if (!media) return;
    
    document.getElementById('editId').value = media.id;
    document.getElementById('editPreview').src = '../' + media.file_path;
    document.getElementById('alt_text').value = media.alt_text || '';
    document.getElementById('caption').value = media.caption || '';
}

function copyUrl(url) {
    const fullUrl = window.location.origin + '/' + url;
    navigator.clipboard.writeText(fullUrl).then(function() {
        showToast('URL kopyalandı', 'success');
    });
}

function copyText(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    document.execCommand('copy');
    showToast('Metin kopyalandı', 'success');
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Upload form submission
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const uploadBtn = document.getElementById('uploadBtn');
    
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="bi bi-spinner fa-spin me-2"></i>Yükleniyor...';
    
    fetch('media.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes('success=')) {
            showToast('Dosya başarıyla yüklendi', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Yükleme sırasında hata oluştu', 'error');
        }
    })
    .catch(error => {
        showToast('Yükleme başarısız', 'error');
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Yükle';
    });
});
</script>

<?php
function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

// Footer include
include '../includes/design_footer.php';
?>
