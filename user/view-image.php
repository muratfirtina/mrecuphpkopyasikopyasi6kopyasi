<?php
/**
 * Mr ECU - Görüntü Görüntüleme Sayfası (Kullanıcı)
 * Kullanıcının yüklediği görüntü dosyalarını güvenli şekilde görüntüler
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/view-image.php');
}

// FileManager sınıfını dahil et
require_once '../includes/FileManager.php';
$fileManager = new FileManager($pdo);

$error = '';
$imageData = null;

// Dosya ID'sini al
$fileId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload'; // upload, response, revision

if (empty($fileId) || !isValidUUID($fileId)) {
    $error = 'Geçersiz dosya ID\'si.';
} else {
    try {
        if ($fileType === 'response') {
            // Yanıt dosyası
            $file = $fileManager->downloadFile($fileId, $_SESSION['user_id'], 'response');
        } elseif ($fileType === 'revision') {
            // Revizyon dosyası
            $file = $fileManager->downloadRevisionFile($fileId, $_SESSION['user_id']);
        } elseif ($fileType === 'additional') {
            // Ek dosya
            $stmt = $pdo->prepare("
                SELECT * FROM additional_files 
                WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
                AND (is_cancelled IS NULL OR is_cancelled = 0)
            ");
            $stmt->execute([$fileId, $_SESSION['user_id'], $_SESSION['user_id']]);
            $additionalFile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$additionalFile) {
                $error = 'Ek dosya bulunamadı veya yetkiniz yok.';
            } else {
                $filePath = UPLOAD_PATH . 'additional_files/' . $additionalFile['file_name'];
                if (file_exists($filePath)) {
                    $file = [
                        'success' => true,
                        'file_path' => $filePath,
                        'original_name' => $additionalFile['original_name'],
                        'file_size' => $additionalFile['file_size']
                    ];
                } else {
                    $error = 'Fiziksel dosya bulunamadı.';
                }
            }
        } else {
            // Normal upload dosyası
            $uploadDetails = $fileManager->getUploadById($fileId);
            if (!$uploadDetails || $uploadDetails['user_id'] !== $_SESSION['user_id']) {
                $error = 'Dosya bulunamadı veya yetkiniz yok.';
            } else {
                $filePath = UPLOAD_PATH . 'user_files/' . $uploadDetails['filename'];
                if (file_exists($filePath)) {
                    $file = [
                        'success' => true,
                        'file_path' => $filePath,
                        'original_name' => $uploadDetails['original_name'],
                        'file_size' => $uploadDetails['file_size']
                    ];
                } else {
                    $error = 'Fiziksel dosya bulunamadı.';
                }
            }
        }
        
        if (isset($file) && $file['success']) {
            // Dosyanın görüntü olup olmadığını kontrol et
            if (!isImageFile($file['original_name'])) {
                $error = 'Bu dosya bir görüntü dosyası değil.';
            } else {
                $imageData = $file;
            }
        } else {
            $error = isset($file['message']) ? $file['message'] : 'Dosya yüklenirken hata oluştu.';
        }
        
    } catch (Exception $e) {
        error_log('Image view error: ' . $e->getMessage());
        $error = 'Dosya görüntülenirken hata oluştu.';
    }
}

$pageTitle = 'Görüntü Görüntüle';
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-image me-2 text-primary"></i>Görüntü Görüntüle
                    </h1>
                    <?php if ($imageData): ?>
                        <p class="text-muted mb-0">
                            <?php echo htmlspecialchars($imageData['original_name']); ?>
                            <span class="badge bg-light text-dark ms-2">
                                <?php echo formatFileSize($imageData['file_size']); ?>
                            </span>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-outline-secondary me-2" onclick="history.back()">
                        <i class="bi bi-arrow-left me-1"></i>Geri Dön
                    </button>
                    <?php if ($imageData): ?>
                        <?php if ($fileType === 'additional'): ?>
                            <a href="../download-additional.php?id=<?php echo urlencode($fileId); ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="bi bi-download me-1"></i>İndir
                            </a>
                        <?php else: ?>
                            <a href="download.php?id=<?php echo urlencode($fileId); ?>&type=<?php echo urlencode($fileType); ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="bi bi-download me-1"></i>İndir
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="files.php" class="btn btn-primary">
                        <i class="bi bi-folder me-1"></i>Dosyalarıma Dön
                    </a>
                </div>
            <?php elseif ($imageData): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-0">
                                <div class="image-viewer-container">
                                    <?php
                                    // Güvenli görüntü gösterimi için base64 encoding
                                    $imageContent = file_get_contents($imageData['file_path']);
                                    $imageMimeType = mime_content_type($imageData['file_path']);
                                    $base64Image = base64_encode($imageContent);
                                    ?>
                                    <img 
                                        src="data:<?php echo $imageMimeType; ?>;base64,<?php echo $base64Image; ?>" 
                                        alt="<?php echo htmlspecialchars($imageData['original_name']); ?>"
                                        class="img-fluid w-100"
                                        style="max-height: 80vh; object-fit: contain;"
                                        id="viewerImage"
                                    >
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-1">
                                            <i class="bi bi-folder2-open-image me-1"></i>
                                            <?php echo htmlspecialchars($imageData['original_name']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            Boyut: <?php echo formatFileSize($imageData['file_size']); ?> • 
                                            Tür: <?php echo strtoupper(pathinfo($imageData['original_name'], PATHINFO_EXTENSION)); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomOut()">
                                                <i class="bi bi-search-minus"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetZoom()">
                                                <i class="bi bi-expand-arrows-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomIn()">
                                                <i class="bi bi-search-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.image-viewer-container {
    position: relative;
    overflow: auto;
    background: #f8f9fa;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: zoom-in;
}

.image-viewer-container img {
    transition: transform 0.3s ease;
    border-radius: 8px;
}

.image-viewer-container.zoomed {
    cursor: zoom-out;
}

.image-viewer-container.zoomed img {
    cursor: move;
}
</style>

<script>
let currentZoom = 1;
let isDragging = false;
let startX, startY, scrollLeft, scrollTop;

const image = document.getElementById('viewerImage');
const container = document.querySelector('.image-viewer-container');

function zoomIn() {
    currentZoom = Math.min(currentZoom * 1.2, 5);
    image.style.transform = `scale(${currentZoom})`;
    updateCursor();
}

function zoomOut() {
    currentZoom = Math.max(currentZoom / 1.2, 0.1);
    image.style.transform = `scale(${currentZoom})`;
    updateCursor();
}

function resetZoom() {
    currentZoom = 1;
    image.style.transform = `scale(${currentZoom})`;
    container.scrollLeft = 0;
    container.scrollTop = 0;
    updateCursor();
}

function updateCursor() {
    if (currentZoom > 1) {
        container.classList.add('zoomed');
    } else {
        container.classList.remove('zoomed');
    }
}

// Resme tıklayınca zoom
image.addEventListener('click', function(e) {
    if (currentZoom === 1) {
        zoomIn();
    } else {
        resetZoom();
    }
});

// Mouse wheel ile zoom
container.addEventListener('wheel', function(e) {
    e.preventDefault();
    if (e.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }
});

// Sürükleme işlevselliği
container.addEventListener('mousedown', function(e) {
    if (currentZoom > 1) {
        isDragging = true;
        startX = e.pageX - container.offsetLeft;
        startY = e.pageY - container.offsetTop;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;
        container.style.cursor = 'grabbing';
    }
});

container.addEventListener('mouseleave', function() {
    isDragging = false;
    updateCursor();
});

container.addEventListener('mouseup', function() {
    isDragging = false;
    updateCursor();
});

container.addEventListener('mousemove', function(e) {
    if (!isDragging || currentZoom <= 1) return;
    e.preventDefault();
    const x = e.pageX - container.offsetLeft;
    const y = e.pageY - container.offsetTop;
    const walkX = (x - startX) * 2;
    const walkY = (y - startY) * 2;
    container.scrollLeft = scrollLeft - walkX;
    container.scrollTop = scrollTop - walkY;
});

// Klavye kısayolları
document.addEventListener('keydown', function(e) {
    switch(e.key) {
        case '+':
        case '=':
            e.preventDefault();
            zoomIn();
            break;
        case '-':
            e.preventDefault();
            zoomOut();
            break;
        case '0':
            e.preventDefault();
            resetZoom();
            break;
        case 'Escape':
            history.back();
            break;
    }
});
</script>

<?php include '../includes/user_footer.php'; ?>
