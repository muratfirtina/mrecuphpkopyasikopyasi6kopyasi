<?php
/**
 * Mr ECU - File Detail Page (Updated for Response Files)
 * Yanıt dosyaları desteği ile güncellenmiş dosya detay sayfası
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
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload'; // 'upload' or 'response'
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
    
    // Revize yanıt dosyası yükleme (response dosyası için yeni yanıt dosyası)
    if (isset($_FILES['revised_response_file']) && isset($_POST['upload_revised_response'])) {
        $creditsCharged = floatval($_POST['revised_credits_charged']);
        $responseNotes = sanitize($_POST['revised_response_notes']);
        
        if ($creditsCharged < 0) {
            $error = 'Kredi miktarı negatif olamaz.';
        } else {
            $result = $fileManager->uploadResponseFile($uploadId, $_FILES['revised_response_file'], $creditsCharged, $responseNotes);
            
            if ($result['success']) {
                $success = 'Revize edilmiş yanıt dosyası başarıyla yüklendi.';
                
                // Revize durumunu completed yap
                try {
                    $stmt = $pdo->prepare("
                        UPDATE revisions 
                        SET status = 'completed', completed_at = NOW()
                        WHERE upload_id = ? AND response_id IS NOT NULL AND status = 'in_progress'
                        ORDER BY requested_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$uploadId]);
                } catch(PDOException $e) {
                    error_log('Revize durumu güncellenemedi: ' . $e->getMessage());
                }
                
                $user->logAction($_SESSION['user_id'], 'response_revision_upload', "Revize yanıt dosyası yüklendi: {$uploadId}");
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Dosya detaylarını al
try {
    if ($fileType === 'response') {
        // Response dosyası detaylarını al - upload_id'ye göre en son response dosyasını bul
        $stmt = $pdo->prepare("
            SELECT fr.*, fu.user_id, fu.original_name as original_upload_name,
                   fu.brand_id, fu.model_id, fu.year, fu.ecu_type, fu.engine_code,
                   fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                   b.name as brand_name, m.name as model_name,
                   a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                   u.username, u.email, u.first_name, u.last_name
            FROM file_responses fr
            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
            LEFT JOIN brands b ON fu.brand_id = b.id
            LEFT JOIN models m ON fu.model_id = m.id
            LEFT JOIN users a ON fr.admin_id = a.id
            LEFT JOIN users u ON fu.user_id = u.id
            WHERE fr.upload_id = ?
            ORDER BY fr.upload_date DESC
            LIMIT 1
        ");
        $stmt->execute([$uploadId]);
        $upload = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$upload) {
            $_SESSION['error'] = 'Response dosyası bulunamadı.';
            redirect('uploads.php');
        }
        
        // Response dosyası için file_path ayarla
        if (!empty($upload['filename'])) {
            $upload['file_path'] = '../uploads/response_files/' . $upload['filename'];
        }
        
        // Response dosyası ID'sini kaydet (indir butonu için)
        $responseId = $upload['id'];
        
        // Response dosyası için responseFiles'i boş bırak
        $responseFiles = [];
        
        // Bu response dosyası için onaylanmış revize talebi var mı?
        $stmt = $pdo->prepare("
            SELECT * FROM revisions 
            WHERE response_id = ? AND status = 'in_progress'
            ORDER BY requested_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$responseId]);
        $approvedRevision = $stmt->fetch();
        
    } else {
        // Normal upload dosyası detaylarını al
        $upload = $fileManager->getUploadById($uploadId);
        
        if (!$upload) {
            redirect('uploads.php');
        }
        
        $responseId = null;
        $approvedRevision = null;
        
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
    if (empty($filename)) {
        error_log('checkFileByName: Filename boş');
        return ['exists' => false, 'path' => ''];
    }
    
    $subdir = $type === 'response' ? 'response_files' : 'user_files';
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $subdir . '/' . $filename;
    
    $exists = file_exists($fullPath);
    
    // Debug info
    error_log('checkFileByName: filename=' . $filename . ', type=' . $type . ', path=' . $fullPath . ', exists=' . ($exists ? 'true' : 'false'));
    
    return [
        'exists' => $exists,
        'path' => $fullPath,
        'size' => $exists ? filesize($fullPath) : 0
    ];
}

// Güvenli HTML output fonksiyonu
function safeHtml($value) {
    return $value !== null ? htmlspecialchars($value) : '<em style="color: #999;">Belirtilmemiş</em>';
}

if ($fileType === 'response') {
    $originalFileCheck = checkFileByName($upload['filename'], 'response');
    $pageTitle = 'Yanıt Dosyası Detayları - ' . htmlspecialchars($upload['original_name']);
    $pageDescription = 'Yanıt dosyası detaylarını görüntüleyin ve yönetin';
    
    // Debug info
    error_log('Response file debug - ResponseId: ' . $responseId . ', Filename: ' . $upload['filename']);
} else {
    $originalFileCheck = checkFileByName($upload['filename'], 'user');
    $pageTitle = 'Dosya Detayları - ' . htmlspecialchars($upload['original_name']);
    $pageDescription = 'Dosya detaylarını görüntüleyin ve yönetin';
}

$pageIcon = 'fas fa-file-alt';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="uploads.php">Dosyalar</a></li>
        <li class="breadcrumb-item active" aria-current="page">
            <?php echo $fileType === 'response' ? 'Yanıt Dosyası' : 'Dosya'; ?> Detayı
        </li>
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

<!-- Dosya Detay Kartı -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $fileType === 'response' ? 'reply' : 'file-alt'; ?> me-2"></i>
                <?php echo $fileType === 'response' ? 'Yanıt Dosyası' : 'Dosya'; ?> Detayları
            </h5>
            <div class="d-flex gap-2">
                <?php if ($fileType === 'response'): ?>
                    <a href="file-detail.php?id=<?php echo $uploadId; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-file-alt me-1"></i>Orijinal Dosyayı Görüntüle
                    </a>
                <?php endif; ?>
                
                <?php if ($originalFileCheck['exists']): ?>
                    <?php if ($fileType === 'response'): ?>
                        <a href="download-file.php?id=<?php echo $responseId; ?>&type=response" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i>İndir
                        </a>
                    <?php else: ?>
                        <a href="download-file.php?id=<?php echo $uploadId; ?>&type=upload" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i>İndir
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <div class="row">
            <!-- Dosya Bilgileri -->
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Dosya Adı</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['original_name']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Dosya Boyutu</label>
                        <div class="form-control-plaintext">
                            <?php echo formatFileSize($upload['file_size'] ?? 0); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Yükleme Tarihi</label>
                        <div class="form-control-plaintext">
                            <?php echo date('d.m.Y H:i', strtotime($upload['upload_date'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($fileType === 'response'): ?>
                        <div class="col-sm-6">
                            <label class="form-label">Oluşturan Admin</label>
                            <div class="form-control-plaintext">
                                <?php echo safeHtml($upload['admin_first_name'] . ' ' . $upload['admin_last_name'] . ' (@' . $upload['admin_username'] . ')'); ?>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <label class="form-label">Orijinal Dosya</label>
                            <div class="form-control-plaintext">
                                <a href="file-detail.php?id=<?php echo $uploadId; ?>" class="text-primary">
                                    <?php echo safeHtml($upload['original_upload_name']); ?>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-sm-6">
                            <label class="form-label">Durum</label>
                            <div class="form-control-plaintext">
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
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?>">
                                    <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Araç Bilgileri -->
                    <div class="col-12">
                        <hr>
                        <h6 class="mb-3">Araç Bilgileri</h6>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Marka</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['brand_name']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Model</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['model_name']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Yıl</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['year']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">ECU Tipi</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['ecu_type']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Motor Kodu</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['engine_code']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Güç (HP)</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['hp_power']); ?>
                        </div>
                    </div>
                    
                    <!-- Notlar -->
                    <?php if (!empty($upload['admin_notes'])): ?>
                        <div class="col-12">
                            <label class="form-label">Admin Notları</label>
                            <div class="form-control-plaintext">
                                <div class="alert alert-info">
                                    <?php echo nl2br(htmlspecialchars($upload['admin_notes'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Kullanıcı Bilgileri -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Kullanıcı Bilgileri</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle me-3">
                                <?php echo strtoupper(substr($upload['first_name'], 0, 1) . substr($upload['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo safeHtml($upload['first_name'] . ' ' . $upload['last_name']); ?></h6>
                                <small class="text-muted">@<?php echo safeHtml($upload['username']); ?></small>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">E-posta:</small><br>
                            <a href="mailto:<?php echo $upload['email']; ?>"><?php echo safeHtml($upload['email']); ?></a>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Dosya Durumu:</small><br>
                            <?php if ($fileType === 'response'): ?>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-reply me-1"></i>Yanıt Dosyası
                                </span>
                            <?php else: ?>
                                <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?> fs-6">
                                    <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <a href="users.php?user_id=<?php echo $upload['user_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user me-1"></i>Kullanıcı Profili
                            </a>
                            <a href="uploads.php?user_id=<?php echo $upload['user_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-files me-1"></i>Diğer Dosyalar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yanıt Dosyası Yükleme (sadece normal dosyalar için) -->
<?php if ($fileType !== 'response' && ($upload['status'] === 'pending' || $upload['status'] === 'processing')): ?>
    <div class="card admin-card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-reply me-2"></i>Yanıt Dosyası Yükle
                </h6>
                <?php if ($upload['status'] === 'processing'): ?>
                    <span class="badge bg-info">
                        <i class="fas fa-cogs me-1"></i>Dosya İşleme Alındı
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if ($upload['status'] === 'processing'): ?>
                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <i class="fas fa-info-circle me-3 mt-1"></i>
                        <div>
                            <strong>Dosya işleme alındı!</strong><br>
                            <small class="text-muted">
                                Bu dosya indirildi ve düzenleme aşamasında. Düzenleme tamamlandıktan sonra 
                                buradan yanıt dosyasını yükleyebilirsiniz.
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="response_file" class="form-label">Yanıt Dosyası</label>
                        <input type="file" class="form-control" id="response_file" name="response_file" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="credits_charged" class="form-label">Düşürülecek Kredi</label>
                        <input type="number" class="form-control" id="credits_charged" name="credits_charged" 
                               value="0" min="0" step="0.01">
                    </div>
                    
                    <div class="col-12">
                        <label for="response_notes" class="form-label">Yanıt Notları</label>
                        <textarea class="form-control" id="response_notes" name="response_notes" rows="3"
                                  placeholder="Yanıt ile ilgili notlarınızı buraya yazın..."></textarea>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" name="upload_response" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i>Yanıt Dosyasını Yükle
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Debug Info (geliştirme için) -->
<?php if ($fileType === 'response'): ?>
    <div class="card admin-card mb-4" style="border-left: 4px solid #17a2b8;">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-bug me-2"></i>Debug Bilgisi
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Response ID:</strong> <?php echo $responseId; ?></p>
                    <p><strong>Upload ID:</strong> <?php echo $uploadId; ?></p>
                    <p><strong>Filename:</strong> <?php echo $upload['filename']; ?></p>
                    <p><strong>File Exists:</strong> <?php echo $originalFileCheck['exists'] ? '✅ Evet' : '❌ Hayır'; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Approved Revision:</strong> <?php echo $approvedRevision ? '✅ Var' : '❌ Yok'; ?></p>
                    <?php if ($approvedRevision): ?>
                        <p><strong>Revision Status:</strong> <?php echo $approvedRevision['status']; ?></p>
                        <p><strong>Revision Date:</strong> <?php echo $approvedRevision['requested_at']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Revize Yanıt Dosyası Yükleme (response dosyası için) -->
<?php if ($fileType === 'response'): ?>
    <?php if ($approvedRevision): ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-redo me-2 text-warning"></i>Revize Edilmiş Yanıt Dosyası Yükle
                    </h6>
                    <span class="badge bg-info">Revize Talebi İşleme Alındı</span>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <i class="fas fa-info-circle me-3 mt-1"></i>
                        <div>
                            <strong>Revize Talebi İşleme Alındı:</strong><br>
                            <span class="text-muted"><?php echo nl2br(htmlspecialchars($approvedRevision['request_notes'])); ?></span>
                            <br><small class="text-muted">Tarih: <?php echo formatDate($approvedRevision['requested_at']); ?></small>
                        </div>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="revised_response_file" class="form-label">Revize Edilmiş Yanıt Dosyası <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="revised_response_file" name="revised_response_file" required>
                            <div class="form-text">Revize edilen yanıt dosyasını seçin</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="revised_credits_charged" class="form-label">Düşürülecek Kredi</label>
                            <input type="number" class="form-control" id="revised_credits_charged" name="revised_credits_charged" 
                                   value="0" min="0" step="0.01">
                            <div class="form-text">Revize için kullanıcıdan düşürülecek kredi miktarı</div>
                        </div>
                        
                        <div class="col-12">
                            <label for="revised_response_notes" class="form-label">Revize Notları</label>
                            <textarea class="form-control" id="revised_response_notes" name="revised_response_notes" rows="3"
                                      placeholder="Revize edilen dosya ile ilgili notlarınızı buraya yazın..."></textarea>
                            <div class="form-text">Revize detayları ve değişiklikler hakkında bilgi verin</div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="upload_revised_response" class="btn btn-warning btn-lg">
                                <i class="fas fa-upload me-2"></i>Revize Edilmiş Dosyayı Yükle
                            </button>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Yeni dosya yüklendikten sonra kullanıcı bunu indirebilecek
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2 text-info"></i>Revize Durumu
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <div class="d-flex">
                        <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                        <div>
                            <strong>Bu yanıt dosyası için henüz onaylanmış revize talebi bulunmuyor.</strong><br>
                            <small class="text-muted">
                                Kullanıcı bu yanıt dosyası için revize talebi gönderdiyse ve siz onu onaylandıysanız, 
                                burada revize dosyası yükleme formu görünecektir.
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="revisions.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-1"></i>Revize Taleplerini Görüntüle
                    </a>
                    <a href="file-detail.php?id=<?php echo $uploadId; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-alt me-1"></i>Orijinal Dosyaya Dön
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Yanıt Dosyaları Listesi (sadece normal dosyalar için) -->
<?php if ($fileType !== 'response' && !empty($responseFiles)): ?>
    <div class="card admin-card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-reply me-2"></i>Yanıt Dosyaları (<?php echo count($responseFiles); ?>)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Dosya Adı</th>
                            <th>Boyut</th>
                            <th>Yükleme Tarihi</th>
                            <th>Admin</th>
                            <th>Kredi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responseFiles as $responseFile): ?>
                            <tr>
                                <td>
                                    <strong><?php echo safeHtml($responseFile['original_name']); ?></strong>
                                </td>
                                <td><?php echo formatFileSize($responseFile['file_size']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($responseFile['upload_date'])); ?></td>
                                <td>
                                    <?php if ($responseFile['admin_username']): ?>
                                        <?php echo safeHtml($responseFile['first_name'] . ' ' . $responseFile['last_name']); ?>
                                        <small class="text-muted d-block">@<?php echo $responseFile['admin_username']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Bilinmiyor</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $responseFile['credits_charged']; ?> kredi
                                </td>
                                <td>
                                    <a href="download-file.php?id=<?php echo $responseFile['id']; ?>&type=response" 
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-download me-1"></i>İndir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}
</style>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
