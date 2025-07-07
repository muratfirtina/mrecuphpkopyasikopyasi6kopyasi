<?php
/**
 * Mr ECU - File Detail Page (Comprehensive)
 * Kapsamlı dosya detay sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

// Admin kontrolü otomatik yapılır
$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Upload ID kontrolü
if (!isset($_GET['id']) || !isValidUUID($_GET['id'])) {
    redirect('uploads.php');
}

$uploadId = $_GET['id'];
$error = '';
$success = '';

// İşlem kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Durum güncelleme
    if (isset($_POST['update_status'])) {
        $status = sanitize($_POST['status']);
        $adminNotes = sanitize($_POST['admin_notes']);
        
        if ($fileManager->updateUploadStatus($uploadId, $status, $adminNotes)) {
            $success = 'Dosya durumu başarıyla güncellendi.';
            $user->logAction($_SESSION['user_id'], 'status_update', "Dosya #{$uploadId} durumu {$status} olarak güncellendi");
        } else {
            $error = 'Durum güncellenirken hata oluştu.';
        }
    }
    
    // Yanıt dosyası yükleme
    if (isset($_FILES['response_file']) && isset($_POST['upload_response'])) {
        $creditsCharged = floatval($_POST['credits_charged']);
        $responseNotes = sanitize($_POST['response_notes']);
        
        if ($creditsCharged < 0) {
            $error = 'Kredi miktarı negatif olamaz.';
        } else {
            $result = $fileManager->uploadResponseFile($uploadId, $_FILES['response_file'], $creditsCharged, $responseNotes);
            
            if ($result['success']) {
                $success = $result['message'];
                $user->logAction($_SESSION['user_id'], 'response_upload', "Yanıt dosyası yüklendi: {$uploadId}");
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Dosya detaylarını al
try {
    $upload = $fileManager->getUploadById($uploadId);
    
    if (!$upload) {
        redirect('uploads.php');
    }
    
    // file_responses tablosundan yanıt dosyalarını al
    $stmt = $pdo->prepare("
        SELECT fr.*, u.username as admin_username, u.first_name, u.last_name
        FROM file_responses fr
        LEFT JOIN users u ON fr.admin_id = u.id
        WHERE fr.upload_id = ?
        ORDER BY fr.upload_date DESC
    ");
    $stmt->execute([$uploadId]);
    $responseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Response dosyalarına file_path ekle
    foreach ($responseFiles as &$response) {
        if (!empty($response['filename'])) {
            $response['file_path'] = '../uploads/response_files/' . $response['filename'];
            $response['response_file'] = $response['filename']; // Uyumluluk için
        }
    }
    
    // Kredi geçmişini al
    $stmt = $pdo->prepare("
        SELECT * FROM credit_transactions 
        WHERE user_id = ? AND description LIKE ?
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$upload['user_id'], '%' . $uploadId . '%']);
    $creditHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcının diğer dosyalarını al
    $stmt = $pdo->prepare("
        SELECT id, original_name, status, upload_date 
        FROM file_uploads 
        WHERE user_id = ? AND id != ? 
        ORDER BY upload_date DESC 
        LIMIT 10
    ");
    $stmt->execute([$upload['user_id'], $uploadId]);
    $otherFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // System logs - Kolon adını kontrol et
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM system_logs LIKE 'details'");
        $hasDetailsColumn = $stmt->fetch();
        
        if ($hasDetailsColumn) {
            $stmt = $pdo->prepare("
                SELECT * FROM system_logs 
                WHERE (details LIKE ? OR details LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute(['%' . $uploadId . '%', '%' . $upload['original_name'] . '%']);
        } else {
            // 'details' kolonu yok, 'description' kullan
            $stmt = $pdo->prepare("
                SELECT * FROM system_logs 
                WHERE (description LIKE ? OR description LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute(['%' . $uploadId . '%', '%' . $upload['original_name'] . '%']);
        }
        $systemLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('System logs query error: ' . $e->getMessage());
        $systemLogs = [];
    }
    
} catch (Exception $e) {
    error_log('File detail error: ' . $e->getMessage());
    redirect('uploads.php');
}

// Dosya path kontrolü
function checkFilePath($filePath) {
    if (empty($filePath)) return ['exists' => false, 'path' => ''];
    
    $fullPath = $filePath;
    
    // Path düzeltmeleri
    if (strpos($fullPath, '../uploads/') === 0) {
        $fullPath = str_replace('../uploads/', $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/', $fullPath);
    } elseif (strpos($fullPath, $_SERVER['DOCUMENT_ROOT']) !== 0) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . ltrim($fullPath, '/');
    }
    
    return [
        'exists' => file_exists($fullPath),
        'path' => $fullPath,
        'size' => file_exists($fullPath) ? filesize($fullPath) : 0
    ];
}

// Filename'den path oluştur
function checkFileByName($filename, $type = 'user') {
    if (empty($filename)) return ['exists' => false, 'path' => ''];
    
    $subdir = $type === 'response' ? 'response_files' : 'user_files';
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $subdir . '/' . $filename;
    
    return [
        'exists' => file_exists($fullPath),
        'path' => $fullPath,
        'size' => file_exists($fullPath) ? filesize($fullPath) : 0
    ];
}

// Güvenli HTML output fonksiyonu
function safeHtml($value) {
    return $value !== null ? htmlspecialchars($value) : '<em style="color: #999;">Belirtilmemiş</em>';
}

$originalFileCheck = checkFileByName($upload['filename'], 'user');

$pageTitle = 'Dosya Detayları - ' . htmlspecialchars($upload['original_name']);
$pageDescription = 'Dosya detaylarını görüntüleyin ve yönetin';
$pageIcon = 'fas fa-file-alt';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="uploads.php">Dosya Yüklemeleri</a></li>
        <li class="breadcrumb-item active">Dosya Detayları</li>
    </ol>
</nav>

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

<div class="row">
    <!-- Sol Kolon - Ana Bilgiler -->
    <div class="col-lg-8">
        <!-- Dosya Bilgileri -->
        <div class="card admin-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>Dosya Bilgileri
                </h5>
                
                <!-- Durum Badge -->
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
                <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?> fs-6">
                    <i class="fas fa-<?php echo $statusIcon[$upload['status']] ?? 'question'; ?> me-1"></i>
                    <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Dosya Adı:</strong></td>
                                <td><?php echo htmlspecialchars($upload['original_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Dosya Boyutu:</strong></td>
                                <td><?php echo formatFileSize($upload['file_size'] ?? 0); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Dosya Tipi:</strong></td>
                                <td>
                                    <?php 
                                    $extension = strtolower(pathinfo($upload['original_name'], PATHINFO_EXTENSION));
                                    echo strtoupper($extension) . ' Dosyası';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Yükleme Tarihi:</strong></td>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($upload['upload_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Son Güncelleme:</strong></td>
                                <td>
                                    <?php echo isset($upload['updated_at']) && $upload['updated_at'] ? 
                                        date('d.m.Y H:i:s', strtotime($upload['updated_at'])) : 'Güncellenmemiş'; ?>
                                </td>
                            </tr>
                            <?php if (isset($upload['completed_at']) && !empty($upload['completed_at'])): ?>
                                <tr>
                                    <td><strong>Tamamlanma:</strong></td>
                                    <td class="text-success"><?php echo date('d.m.Y H:i:s', strtotime($upload['completed_at'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Kredi Ücreti:</strong></td>
                                <td>
                                    <?php if (!empty($upload['credits_charged'])): ?>
                                        <span class="text-danger"><?php echo number_format($upload['credits_charged'], 2); ?> TL</span>
                                    <?php else: ?>
                                        <span class="text-muted">Henüz ücretlendirilmemiş</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Dosya Durumu:</strong></td>
                                <td>
                                    <?php if ($originalFileCheck['exists']): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> Dosya mevcut
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i> Dosya bulunamadı
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Notlar -->
                <?php if (isset($upload['notes']) && !empty($upload['notes'])): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="fas fa-comment me-2"></i>Kullanıcı Notları:</h6>
                            <div class="bg-light p-3 rounded">
                                <?php echo nl2br(htmlspecialchars($upload['notes'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($upload['admin_notes']) && !empty($upload['admin_notes'])): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="fas fa-user-shield me-2"></i>Admin Notları:</h6>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <?php echo nl2br(htmlspecialchars($upload['admin_notes'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Araç Bilgileri -->
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-car me-2"></i>Araç Bilgileri
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Marka:</strong></td>
                                <td><?php echo htmlspecialchars($upload['brand_name'] ?? 'Belirtilmemiş'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Model:</strong></td>
                                <td><?php echo htmlspecialchars($upload['model_name'] ?? 'Belirtilmemiş'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <?php if (isset($upload['vehicle_year']) && !empty($upload['vehicle_year'])): ?>
                                <tr>
                                    <td><strong>Model Yılı:</strong></td>
                                    <td><?php echo htmlspecialchars($upload['vehicle_year']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($upload['engine_code']) && !empty($upload['engine_code'])): ?>
                                <tr>
                                    <td><strong>Motor Kodu:</strong></td>
                                    <td><?php echo htmlspecialchars($upload['engine_code']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dosya İndirme -->
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-download me-2"></i>Dosya İndirme
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                            <div>
                                <h6 class="mb-1">Orijinal Dosya</h6>
                                <small class="text-muted"><?php echo htmlspecialchars($upload['original_name']); ?></small>
                            </div>
                            <div>
                                <?php if ($originalFileCheck['exists']): ?>
                                    <a href="download.php?type=original&id=<?php echo $upload['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i>İndir
                                    </a>
                                <?php else: ?>
                                    <span class="text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Bulunamadı
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                            <div>
                                <h6 class="mb-1">İşlenmiş Dosyalar</h6>
                                <small class="text-muted"><?php echo count($responseFiles); ?> adet yanıt dosyası</small>
                            </div>
                            <div>
                                <?php if (!empty($responseFiles)): ?>
                                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#responseFilesModal">
                                        <i class="fas fa-list me-1"></i>Listele
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Henüz yok</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sistem Logları -->
        <?php if (!empty($systemLogs)): ?>
            <div class="card admin-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-alt me-2"></i>Sistem Logları
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>İşlem</th>
                                    <th>Detaylar</th>
                                    <th>Kullanıcı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($systemLogs as $log): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($log['action'] ?? 'Unknown'); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($log['details'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Sağ Kolon - Yan Bilgiler -->
    <div class="col-lg-4">
        <!-- Kullanıcı Bilgileri -->
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>Kullanıcı Bilgileri
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                     style="width: 60px; height: 60px; font-size: 24px;">
                    <?php echo strtoupper(substr($upload['first_name'], 0, 1) . substr($upload['last_name'], 0, 1)); ?>
                </div>
                
                <h6><?php echo htmlspecialchars($upload['first_name'] . ' ' . $upload['last_name']); ?></h6>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($upload['username']); ?></p>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($upload['email']); ?></p>
                
                <a href="user-details.php?id=<?php echo $upload['user_id']; ?>" 
                   class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-user me-1"></i>Kullanıcı Detayları
                </a>
            </div>
        </div>
        
        <!-- Hızlı İşlemler -->
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tools me-2"></i>Hızlı İşlemler
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        <i class="fas fa-edit me-2"></i>Durum Güncelle
                    </button>
                    
                    <?php if ($upload['status'] === 'processing'): ?>
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#uploadResponseModal">
                            <i class="fas fa-upload me-2"></i>Yanıt Yükle
                        </button>
                    <?php endif; ?>
                    
                    <a href="mailto:<?php echo $upload['email']; ?>" class="btn btn-outline-info">
                        <i class="fas fa-envelope me-2"></i>E-posta Gönder
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Kredi Geçmişi -->
        <?php if (!empty($creditHistory)): ?>
            <div class="card admin-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-coins me-2"></i>İlgili Kredi İşlemleri
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($creditHistory, 0, 5) as $credit): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="text-<?php echo in_array($credit['type'], ['deposit', 'refund']) ? 'success' : 'danger'; ?>">
                                            <?php echo in_array($credit['type'], ['deposit', 'refund']) ? '+' : '-'; ?>
                                            <?php echo number_format($credit['amount'], 2); ?> TL
                                        </strong><br>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($credit['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($credit['description']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Diğer Dosyalar -->
        <?php if (!empty($otherFiles)): ?>
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-files me-2"></i>Kullanıcının Diğer Dosyaları
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($otherFiles, 0, 5) as $file): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-truncate" style="max-width: 200px;" 
                                            title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                            <a href="file-detail.php?id=<?php echo $file['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($file['original_name']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y', strtotime($file['upload_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $statusClass[$file['status']] ?? 'secondary'; ?> ms-2">
                                        <?php echo $statusText[$file['status']] ?? 'Bilinmiyor'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Durum Güncelleme Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Durum Güncelle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Yeni Durum <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" <?php echo $upload['status'] === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                            <option value="processing" <?php echo $upload['status'] === 'processing' ? 'selected' : ''; ?>>İşleniyor</option>
                            <option value="completed" <?php echo $upload['status'] === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                            <option value="rejected" <?php echo $upload['status'] === 'rejected' ? 'selected' : ''; ?>>Reddedildi</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notları</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" 
                                  rows="4" placeholder="Durum değişikliği hakkında notlar..."><?php echo htmlspecialchars($upload['admin_notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Yanıt Dosyası Yükleme Modal -->
<div class="modal fade" id="uploadResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2"></i>Yanıt Dosyası Yükle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="upload_response" value="1">
                    
                    <div class="mb-3">
                        <label for="response_file" class="form-label">İşlenmiş Dosya <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="response_file" name="response_file" 
                               accept=".bin,.hex,.a2l,.kp,.ori,.mod,.ecu,.tun" required>
                        <div class="form-text">Desteklenen formatlar: .bin, .hex, .a2l, .kp, .ori, .mod, .ecu, .tun</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="credits_charged" class="form-label">Kesilen Kredi (TL) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="credits_charged" name="credits_charged" 
                               min="0" step="0.01" value="5.00" required>
                        <div class="form-text">Bu işlem için kullanıcıdan kesilecek kredi miktarı</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="response_notes" class="form-label">İşlem Notları</label>
                        <textarea class="form-control" id="response_notes" name="response_notes" 
                                  rows="4" placeholder="İşlem hakkında notlar ve açıklamalar..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Tamamla & Yükle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Yanıt Dosyaları Modal -->
<div class="modal fade" id="responseFilesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-download me-2"></i>Yanıt Dosyaları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($responseFiles)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Yükleyen Admin</th>
                                    <th>Yükleme Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responseFiles as $response): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($response['filename'] ?? 'Bilinmiyor'); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $responseCheck = checkFileByName($response['filename'] ?? '', 'response');
                                            echo $responseCheck['exists'] ? formatFileSize($responseCheck['size']) : 'Bulunamadı';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($response['admin_username'] ?? 'Bilinmiyor'); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y H:i', strtotime($response['upload_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($response['filename'])): ?>
                                                <?php $responseFileCheck = checkFileByName($response['filename'], 'response'); ?>
                                                <?php if ($responseFileCheck['exists']): ?>
                                                    <a href="download.php?type=response&file_id=<?php echo $response['id']; ?>" 
                                                       class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-danger">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-download fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Henüz yanıt dosyası yüklenmemiş</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Form validation
document.querySelector('#updateStatusModal form').addEventListener('submit', function(e) {
    const status = document.getElementById('status').value;
    if (!status) {
        e.preventDefault();
        alert('Lütfen yeni durumu seçin!');
        return false;
    }
});

document.querySelector('#uploadResponseModal form').addEventListener('submit', function(e) {
    const file = document.getElementById('response_file').files[0];
    const credits = parseFloat(document.getElementById('credits_charged').value);
    
    if (!file) {
        e.preventDefault();
        alert('Lütfen yanıt dosyasını seçin!');
        return false;
    }
    
    if (credits < 0) {
        e.preventDefault();
        alert('Kredi miktarı negatif olamaz!');
        return false;
    }
    
    // Dosya boyutu kontrolü (50MB)
    if (file.size > 50 * 1024 * 1024) {
        e.preventDefault();
        alert('Dosya boyutu 50MB\'dan büyük olamaz!');
        return false;
    }
});

// Auto-refresh if status is processing
if ('" . $upload['status'] . "' === 'processing') {
    setTimeout(() => {
        if (!document.hidden) {
            location.reload();
        }
    }, 30000); // 30 saniye sonra yenile
}
";

// Footer include
include '../includes/admin_footer.php';
?>
