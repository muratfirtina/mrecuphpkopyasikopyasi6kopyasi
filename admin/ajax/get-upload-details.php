<?php
/**
 * Mr ECU - Upload Details AJAX Endpoint
 * Dosya detaylarını modal için getiren endpoint
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Upload ID kontrol
if (!isset($_GET['id']) || !isValidUUID($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz dosya ID']);
    exit;
}

$uploadId = $_GET['id'];
$fileManager = new FileManager($pdo);

try {
    // Dosya detaylarını al
    $upload = $fileManager->getUploadById($uploadId);
    
    if (!$upload) {
        echo json_encode(['success' => false, 'message' => 'Dosya bulunamadı']);
        exit;
    }
    
    // Kullanıcı kredi geçmişini al
    $stmt = $pdo->prepare("
        SELECT * FROM credit_transactions 
        WHERE user_id = ? AND description LIKE ?
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$upload['user_id'], '%' . $uploadId . '%']);
    $creditHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcının diğer dosyalarını al
    $stmt = $pdo->prepare("
        SELECT id, original_name, status, upload_date 
        FROM file_uploads 
        WHERE user_id = ? AND id != ? 
        ORDER BY upload_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$upload['user_id'], $uploadId]);
    $otherFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // file_responses tablosundan yanıt dosyalarını al
    $stmt = $pdo->prepare("
        SELECT fr.*, u.username as admin_username
        FROM file_responses fr
        LEFT JOIN users u ON fr.admin_id = u.id
        WHERE fr.upload_id = ?
        ORDER BY fr.upload_date DESC
        LIMIT 5
    ");
    $stmt->execute([$uploadId]);
    $responseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dosya path'i kontrol et
    $fileExists = false;
    if (!empty($upload['filename'])) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '<?php echo BASE_URL; ?>/uploads/user_files/' . $upload['filename'];
        $fileExists = file_exists($fullPath);
    }
    
    // Response dosyası kontrol et
    $responseFileExists = false;
    if (!empty($responseFiles)) {
        // Birden fazla response dosyası olabilir, ilkini kontrol et
        $responseFileExists = count($responseFiles) > 0;
    }
    
    // HTML içeriği oluştur
    ob_start();
    ?>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Dosya Bilgileri -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Dosya Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <strong>Dosya Adı:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($upload['original_name']); ?></span>
                        </div>
                        <div class="col-6">
                            <strong>Dosya Boyutu:</strong><br>
                            <span class="text-muted"><?php echo formatFileSize($upload['file_size'] ?? 0); ?></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <strong>Yükleme Tarihi:</strong><br>
                            <span class="text-muted"><?php echo date('d.m.Y H:i:s', strtotime($upload['upload_date'])); ?></span>
                        </div>
                        <div class="col-6">
                            <strong>Son Güncelleme:</strong><br>
                            <span class="text-muted">
                                <?php echo isset($upload['updated_at']) && $upload['updated_at'] ? date('d.m.Y H:i:s', strtotime($upload['updated_at'])) : 'Güncellenmemiş'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (isset($upload['completed_at']) && !empty($upload['completed_at'])): ?>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <strong>Tamamlanma Tarihi:</strong><br>
                                <span class="text-success"><?php echo date('d.m.Y H:i:s', strtotime($upload['completed_at'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong>Durum:</strong><br>
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
                            <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?> fs-6">
                                <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (isset($upload['notes']) && !empty($upload['notes'])): ?>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <strong>Kullanıcı Notları:</strong><br>
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
                                <strong>Admin Notları:</strong><br>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($upload['admin_notes'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($upload['response_notes']) && !empty($upload['response_notes'])): ?>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <strong>Yanıt Notları:</strong><br>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($upload['response_notes'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Araç Bilgileri -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-car me-2"></i>Araç Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <strong>Marka:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($upload['brand_name'] ?? 'Belirtilmemiş'); ?></span>
                        </div>
                        <div class="col-6">
                            <strong>Model:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($upload['model_name'] ?? 'Belirtilmemiş'); ?></span>
                        </div>
                    </div>
                    
                    <?php if ((isset($upload['vehicle_year']) && !empty($upload['vehicle_year'])) || (isset($upload['engine_code']) && !empty($upload['engine_code']))): ?>
                        <hr>
                        <div class="row">
                            <?php if (isset($upload['vehicle_year']) && !empty($upload['vehicle_year'])): ?>
                                <div class="col-6">
                                    <strong>Model Yılı:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($upload['vehicle_year']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($upload['engine_code']) && !empty($upload['engine_code'])): ?>
                                <div class="col-6">
                                    <strong>Motor Kodu:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($upload['engine_code']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Dosya İndirme -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-download me-2"></i>Dosya İndirme</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <strong>Orijinal Dosya:</strong><br>
                            <?php if ($fileExists): ?>
                                <a href="download.php?type=original&id=<?php echo $upload['id']; ?>" 
                                   class="btn btn-outline-primary btn-sm mt-2">
                                    <i class="bi bi-download me-1"></i>İndir
                                </a>
                            <?php else: ?>
                                <span class="text-danger">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Dosya bulunamadı
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <strong>İşlenmiş Dosyalar:</strong><br>
                            <?php if (!empty($responseFiles)): ?>
                                <?php foreach ($responseFiles as $response): ?>
                                    <?php 
                                    $responseFilePath = $_SERVER['DOCUMENT_ROOT'] . '<?php echo BASE_URL; ?>/uploads/response_files/' . $response['filename'];
                                    $responseExists = file_exists($responseFilePath);
                                    ?>
                                    <div class="mb-1">
                                        <?php if ($responseExists): ?>
                                            <a href="download.php?type=response&file_id=<?php echo $response['id']; ?>" 
                                               class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-download me-1"></i><?php echo htmlspecialchars($response['filename']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-warning">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Dosya bulunamadı
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">Henüz yok</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Kullanıcı Bilgileri -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i>Kullanıcı Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 60px; height: 60px; font-size: 24px;">
                            <?php echo strtoupper(substr($upload['first_name'], 0, 1) . substr($upload['last_name'], 0, 1)); ?>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <h6><?php echo htmlspecialchars($upload['first_name'] . ' ' . $upload['last_name']); ?></h6>
                        <p class="text-muted mb-2">@<?php echo htmlspecialchars($upload['username']); ?></p>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($upload['email']); ?></p>
                        
                        <a href="user-details.php?id=<?php echo $upload['user_id']; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-person me-1"></i>Kullanıcı Detayları
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Kredi Bilgileri -->
            <?php if (!empty($creditHistory)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-coin me-2"></i>İlgili Kredi İşlemleri</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($creditHistory as $credit): ?>
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
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-folder2-opens me-2"></i>Kullanıcının Diğer Dosyaları</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($otherFiles as $file): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 text-truncate" style="max-width: 200px;" 
                                                title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                                <?php echo htmlspecialchars($file['original_name']); ?>
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
    
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    error_log('Upload details error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>
