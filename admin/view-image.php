<?php
/**
 * Mr ECU - Admin Görüntü Görüntüleme Sayfası
 * Admin'in tüm görüntü dosyalarını görüntüleyebileceği sayfa
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php?error=access_denied');
}

// FileManager sınıfını dahil et
require_once '../includes/FileManager.php';
$fileManager = new FileManager($pdo);

$error = '';
$imageData = null;
$userInfo = null;

// Dosya ID'sini al
$fileId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload'; // upload, response, revision, additional

if (empty($fileId) || !isValidUUID($fileId)) {
    $error = 'Geçersiz dosya ID\'si.';
} else {
    try {
        if ($fileType === 'response') {
            // Yanıt dosyası
            $stmt = $pdo->prepare("
                SELECT fr.*, fu.user_id, fu.original_name as main_file_name,
                       u.username, u.first_name, u.last_name, u.email
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN users u ON fu.user_id = u.id
                WHERE fr.id = ?
            ");
            $stmt->execute([$fileId]);
            $fileDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fileDetails) {
                $error = 'Yanıt dosyası bulunamadı.';
            } else {
                $filePath = UPLOAD_PATH . 'response_files/' . $fileDetails['filename'];
                if (file_exists($filePath)) {
                    $imageData = [
                        'file_path' => $filePath,
                        'original_name' => $fileDetails['original_name'],
                        'file_size' => $fileDetails['file_size']
                    ];
                    $userInfo = [
                        'username' => $fileDetails['username'],
                        'full_name' => $fileDetails['first_name'] . ' ' . $fileDetails['last_name'],
                        'email' => $fileDetails['email'],
                        'main_file' => $fileDetails['main_file_name']
                    ];
                } else {
                    $error = 'Fiziksel dosya bulunamadı.';
                }
            }
            
        } elseif ($fileType === 'revision') {
            // Revizyon dosyası
            $stmt = $pdo->prepare("
                SELECT rf.*, fu.user_id, fu.original_name as main_file_name,
                       u.username, u.first_name, u.last_name, u.email
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users u ON fu.user_id = u.id
                WHERE rf.id = ?
            ");
            $stmt->execute([$fileId]);
            $fileDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fileDetails) {
                $error = 'Revizyon dosyası bulunamadı.';
            } else {
                $filePath = UPLOAD_PATH . 'revision_files/' . $fileDetails['filename'];
                if (file_exists($filePath)) {
                    $imageData = [
                        'file_path' => $filePath,
                        'original_name' => $fileDetails['original_name'],
                        'file_size' => $fileDetails['file_size']
                    ];
                    $userInfo = [
                        'username' => $fileDetails['username'],
                        'full_name' => $fileDetails['first_name'] . ' ' . $fileDetails['last_name'],
                        'email' => $fileDetails['email'],
                        'main_file' => $fileDetails['main_file_name']
                    ];
                } else {
                    $error = 'Fiziksel dosya bulunamadı.';
                }
            }
            
        } elseif ($fileType === 'additional') {
            // Ek dosya
            $stmt = $pdo->prepare("
                SELECT af.*, fu.user_id, fu.original_name as main_file_name,
                       sender.username as sender_username, sender.first_name as sender_first_name, sender.last_name as sender_last_name,
                       receiver.username as receiver_username, receiver.first_name as receiver_first_name, receiver.last_name as receiver_last_name
                FROM additional_files af
                LEFT JOIN file_uploads fu ON af.related_file_id = fu.id
                LEFT JOIN users sender ON af.sender_id = sender.id
                LEFT JOIN users receiver ON af.receiver_id = receiver.id
                WHERE af.id = ?
            ");
            $stmt->execute([$fileId]);
            $fileDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fileDetails) {
                $error = 'Ek dosya bulunamadı.';
            } else {
                $filePath = UPLOAD_PATH . 'additional_files/' . $fileDetails['file_name'];
                if (file_exists($filePath)) {
                    $imageData = [
                        'file_path' => $filePath,
                        'original_name' => $fileDetails['original_name'],
                        'file_size' => $fileDetails['file_size']
                    ];
                    $userInfo = [
                        'sender' => $fileDetails['sender_first_name'] . ' ' . $fileDetails['sender_last_name'] . ' (@' . $fileDetails['sender_username'] . ')',
                        'receiver' => $fileDetails['receiver_first_name'] . ' ' . $fileDetails['receiver_last_name'] . ' (@' . $fileDetails['receiver_username'] . ')',
                        'main_file' => $fileDetails['main_file_name']
                    ];
                } else {
                    $error = 'Fiziksel dosya bulunamadı.';
                }
            }
            
        } else {
            // Normal upload dosyası
            $uploadDetails = $fileManager->getUploadById($fileId);
            if (!$uploadDetails) {
                $error = 'Dosya bulunamadı.';
            } else {
                $filePath = UPLOAD_PATH . 'user_files/' . $uploadDetails['filename'];
                if (file_exists($filePath)) {
                    $imageData = [
                        'file_path' => $filePath,
                        'original_name' => $uploadDetails['original_name'],
                        'file_size' => $uploadDetails['file_size']
                    ];
                    
                    // Kullanıcı bilgilerini al
                    $stmt = $pdo->prepare("SELECT username, first_name, last_name, email FROM users WHERE id = ?");
                    $stmt->execute([$uploadDetails['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $userInfo = [
                            'username' => $user['username'],
                            'full_name' => $user['first_name'] . ' ' . $user['last_name'],
                            'email' => $user['email']
                        ];
                    }
                } else {
                    $error = 'Fiziksel dosya bulunamadı.';
                }
            }
        }
        
        if ($imageData && !isImageFile($imageData['original_name'])) {
            $error = 'Bu dosya bir görüntü dosyası değil.';
            $imageData = null;
        }
        
    } catch (Exception $e) {
        error_log('Admin image view error: ' . $e->getMessage());
        $error = 'Dosya görüntülenirken hata oluştu.';
    }
}

$pageTitle = 'Admin - Görüntü Görüntüle';
$pageDescription = 'Görüntü dosyasını görüntüle ve yönet';
$pageIcon = 'fas fa-image';

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-0">
            <i class="fas fa-image me-2 text-primary"></i>Görüntü Görüntüleme
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
            <i class="fas fa-arrow-left me-1"></i>Geri Dön
        </button>
        <?php if ($imageData): ?>
            <?php if ($fileType === 'additional'): ?>
                <a href="../download-additional.php?id=<?php echo urlencode($fileId); ?>" 
                   class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-download me-1"></i>İndir
                </a>
            <?php else: ?>
                <a href="download-file.php?id=<?php echo urlencode($fileId); ?>&type=<?php echo urlencode($fileType); ?>" 
                   class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-download me-1"></i>İndir
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
    <div class="text-center mt-4">
        <a href="uploads.php" class="btn btn-primary">
            <i class="fas fa-upload me-1"></i>Dosya Yönetimine Dön
        </a>
    </div>
<?php elseif ($imageData): ?>
    <div class="row">
        <!-- Kullanıcı Bilgileri -->
        <?php if ($userInfo): ?>
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            <?php echo $fileType === 'additional' ? 'Dosya Bilgileri' : 'Kullanıcı Bilgileri'; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($fileType === 'additional'): ?>
                            <div class="mb-3">
                                <strong>Gönderen:</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($userInfo['sender']); ?></small>
                            </div>
                            <div class="mb-3">
                                <strong>Alıcı:</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($userInfo['receiver']); ?></small>
                            </div>
                            <div class="mb-0">
                                <strong>Ana Dosya:</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($userInfo['main_file']); ?></small>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <strong>Kullanıcı Adı:</strong><br>
                                <small class="text-muted">@<?php echo htmlspecialchars($userInfo['username']); ?></small>
                            </div>
                            <div class="mb-3">
                                <strong>Ad Soyad:</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($userInfo['full_name']); ?></small>
                            </div>
                            <div class="mb-3">
                                <strong>E-posta:</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($userInfo['email']); ?></small>
                            </div>
                            <?php if (isset($userInfo['main_file'])): ?>
                                <div class="mb-0">
                                    <strong>Ana Dosya:</strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($userInfo['main_file']); ?></small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Dosya Türü Bilgisi -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Dosya Türü
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $typeLabels = [
                            'upload' => ['Kullanıcı Dosyası', 'primary'],
                            'response' => ['Yanıt Dosyası', 'success'], 
                            'revision' => ['Revizyon Dosyası', 'warning'],
                            'additional' => ['Ek Dosya', 'info']
                        ];
                        $typeInfo = $typeLabels[$fileType] ?? ['Bilinmeyen', 'secondary'];
                        ?>
                        <span class="badge bg-<?php echo $typeInfo[1]; ?> fs-6">
                            <?php echo $typeInfo[0]; ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Görüntü Alanı -->
        <div class="<?php echo $userInfo ? 'col-lg-9' : 'col-12'; ?>">
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
                                <i class="fas fa-file-image me-1"></i>
                                <?php echo htmlspecialchars($imageData['original_name']); ?>
                            </h6>
                            <small class="text-muted">
                                Boyut: <?php echo formatFileSize($imageData['file_size']); ?> • 
                                Tür: <?php echo strtoupper(pathinfo($imageData['original_name'], PATHINFO_EXTENSION)); ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="btn-group me-2" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomOut()">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetZoom()">
                                    <i class="fas fa-expand-arrows-alt"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomIn()">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                            </div>
                            <div class="btn-group" role="group">
                                <a href="uploads.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list me-1"></i>Dosya Listesi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.image-viewer-container {
    position: relative;
    overflow: auto;
    background: #f8f9fa;
    min-height: 500px;
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

<?php include '../includes/admin_footer.php'; ?>
