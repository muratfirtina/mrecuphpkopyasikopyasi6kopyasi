<?php

/**
 * Mr ECU - Revizyon Detay Sayfası
 * 
 * @global PDO|null $pdo Global database connection
 * @global FileManager|null $fileManager File manager instance
 * @global User|null $user User management instance
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
/** @var FileManager $fileManager */
$fileManager = new FileManager($pdo);
/** @var User $user */
$user = new User($pdo);
$error = '';
$success = '';

// Revision ID'yi al ve kontrol et
$revisionId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (!$revisionId || !isValidUUID($revisionId)) {
    $_SESSION['error'] = 'Geçersiz revizyon ID.';
    header('Location: revisions.php');
    exit;
}

// URL'den success mesajını al
if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

// URL'den error mesajını al
if (isset($_GET['error'])) {
    $error = sanitize($_GET['error']);
}

// Session mesajlarını al ve temizle
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Revize talebini onaylama
        if (isset($_POST['approve_revision'])) {
            $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'in_progress', 'Revize talebi işleme alındı.', 0);

            if ($result['success']) {
                $success = 'Revize talebi işleme alındı. Revize edilmiş dosyayı yükleyebilirsiniz.';
                $user->logAction($_SESSION['user_id'], 'revision_approved', "Revize talebi işleme alındı: {$revisionId}");
            } else {
                $error = $result['message'];
            }
        }

        // Revize talebini reddetme
        if (isset($_POST['reject_revision'])) {
            $adminNotes = sanitize($_POST['admin_notes']) ?: 'Revize talebi reddedildi.';

            if (strlen(trim($adminNotes)) < 10) {
                $error = 'Reddetme sebebi en az 10 karakter olmalıdır.';
            } else {
                $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'rejected', $adminNotes, 0);

                if ($result['success']) {
                    $success = 'Revize talebi reddedildi.';
                    $user->logAction($_SESSION['user_id'], 'revision_rejected', "Revize talebi reddedildi: {$revisionId}");
                } else {
                    $error = $result['message'];
                }
            }
        }
        
        // Admin tarafından direkt dosya iptal etme
        if (isset($_POST['admin_cancel_file'])) {
            error_log("Admin direct cancel request on revision-detail page");
            $cancelFileId = sanitize($_POST['file_id']);
            $cancelFileType = sanitize($_POST['file_type']);
            $adminNotes = sanitize($_POST['admin_notes']);
            
            if (!isValidUUID($cancelFileId)) {
                $error = 'Geçersiz dosya ID formatı.';
                error_log("Invalid file ID for cancel: {$cancelFileId}");
            } else {
                // FileCancellationManager'ı yükle
                require_once '../includes/FileCancellationManager.php';
                $cancellationManager = new FileCancellationManager($pdo);
                
                $result = $cancellationManager->adminDirectCancellation($cancelFileId, $cancelFileType, $_SESSION['user_id'], $adminNotes);
                
                if ($result['success']) {
                    $success = $result['message'];
                    $user->logAction($_SESSION['user_id'], 'admin_direct_cancel', "Dosya doğrudan iptal edildi: {$cancelFileId} ({$cancelFileType})");
                    
                    // Başarılı işlem sonrası redirect
                    header("Location: revision-detail.php?id={$revisionId}&success=" . urlencode($success));
                    exit;
                } else {
                    $error = $result['message'];
                    error_log("Admin cancel failed: {$result['message']}");
                }
            }
        }

        // Revize dosyası yükleme
        if (isset($_POST['upload_revision']) && isset($_FILES['revision_file'])) {
            $creditsUsed = isset($_POST['credits_used']) ? (int)$_POST['credits_used'] : 5;
            $completionNotes = isset($_POST['completion_notes']) ? sanitize($_POST['completion_notes']) : '';
            // Kredi kontrolü - Sadece negatif olmamalı
            if ($creditsUsed < 0) {
                $error = 'Kredi miktarı negatif olamaz.';
            } else {
                $uploadResult = $fileManager->uploadRevisionFile($revisionId, $_FILES['revision_file'], $_SESSION['user_id'], $creditsUsed, $completionNotes);
                if ($uploadResult['success']) {
                    $success = 'Revize dosyası başarıyla yüklendi ve revizyon tamamlandı.';
                    $user->logAction($_SESSION['user_id'], 'revision_file_uploaded', "Revize dosyası yüklendi: {$revisionId}");
                } else {
                    $error = $uploadResult['message'];
                }
            }
        }
    } catch (Exception $e) {
        $error = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
        error_log('Revision detail POST error: ' . $e->getMessage());
    }
}

// Revizyon detaylarını getir
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.username, u.email, u.first_name, u.last_name, u.phone,
            fu.original_name, fu.file_path, fu.file_size, fu.created_at, fu.plate,
            b.name as brand_name,
            m.name as model_name,
            fu.year,
            fr.id as response_id, fr.original_name as response_original_name,
            fr.filename as response_file_path, fr.file_size as response_file_size, fr.upload_date as response_upload_date,
            admin_user.username as admin_username, admin_user.first_name as admin_first_name, admin_user.last_name as admin_last_name
        FROM revisions r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        LEFT JOIN file_responses fr ON r.response_id = fr.id
        LEFT JOIN users admin_user ON r.admin_id = admin_user.id
        WHERE r.id = ?
    ");
    $stmt->execute([$revisionId]);
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$revision) {
        $_SESSION['error'] = 'Revizyon bulunamadı.';
        header('Location: revisions.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Revision detail query error: ' . $e->getMessage());
    error_log('SQL Query failed for revision ID: ' . $revisionId);
    $_SESSION['error'] = 'Revizyon bilgileri yüklenemedi: ' . $e->getMessage();
    header('Location: revisions.php');
    exit;
}

// Revizyon dosyalarını getir
try {
    // Önce revision_files tablosunun varlığını kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'revision_files'");
    $revisionFilesTableExists = $stmt->rowCount() > 0;

    if ($revisionFilesTableExists) {
        $stmt = $pdo->prepare("
            SELECT rf.*, 
                   admin_user.username as admin_username, 
                   admin_user.first_name as admin_first_name, 
                   admin_user.last_name as admin_last_name,
                   admin_user.email as admin_email
            FROM revision_files rf
            LEFT JOIN users admin_user ON rf.admin_id = admin_user.id
            WHERE rf.revision_id = ? 
            ORDER BY rf.created_at DESC
        ");
        $stmt->execute([$revisionId]);
        $revisionFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Eğer revision_files tablosu yoksa boş array döndür
        $revisionFiles = [];
        error_log('revision_files table does not exist');
    }
} catch (PDOException $e) {
    error_log('Revision files query error: ' . $e->getMessage());
    $revisionFiles = [];
}

// Revizyon geçmişini getir (aynı upload_id için tüm revizyonlar)
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.username, u.first_name, u.last_name,
            admin_user.username as admin_username, admin_user.first_name as admin_first_name, admin_user.last_name as admin_last_name
        FROM revisions r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users admin_user ON r.admin_id = admin_user.id
        WHERE r.upload_id = ?
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute([$revision['upload_id']]);
    $revisionHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Revision history query error: ' . $e->getMessage());
    $revisionHistory = [];
}
// Hangi dosyaya revize talep edildiğini belirle
$targetFileName = 'Ana Dosya';
$targetFileType = 'Orijinal Yüklenen Dosya';
$targetFileColor = 'success';
$targetFileIcon = 'file-alt';
$targetFileInfo = null;
$targetDownloadUrl = null;


if ($revision['response_id']):
    // Yanıt dosyasına revize talebi
    $targetFileName = $revision['response_original_name'] ?? 'Yanıt Dosyası';
    $targetFileType = 'Yanıt Dosyası';
    $targetFileColor = 'primary';
    $targetFileIcon = 'reply';
    $targetFileInfo = [
        'name' => $revision['response_original_name'],
        'size' => $revision['response_file_size'],
        'date' => $revision['response_upload_date'],
        'type' => 'Yanıt Dosyası'
    ];
    $targetDownloadUrl = 'download.php?file_id=' . $revision['response_id'];
else:
    // Ana dosya veya revizyon dosyasına revize talebi
    // Önceki revizyon dosyaları var mı kontrol et
    try {
        $stmt = $pdo->prepare("SELECT rf.original_name 
                               FROM revisions r1
                               JOIN revision_files rf ON r1.id = rf.revision_id
                               WHERE r1.upload_id = ? 
                               AND r1.status = 'completed'
                               AND r1.requested_at < ?
                               ORDER BY r1.requested_at DESC 
                               LIMIT 1");
        $stmt->execute([$revision['upload_id'], $revision['requested_at']]);
        $previousRevisionFile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($previousRevisionFile) {
            // Önceki revizyon dosyası var - revizyon dosyası bilgilerini al
            $stmt2 = $pdo->prepare("SELECT rf.* 
                                   FROM revisions r1
                                   JOIN revision_files rf ON r1.id = rf.revision_id
                                   WHERE r1.upload_id = ? 
                                   AND r1.status = 'completed'
                                   AND r1.requested_at < ?
                                   ORDER BY r1.requested_at DESC 
                                   LIMIT 1");
            $stmt2->execute([$revision['upload_id'], $revision['requested_at']]);
            $revisionFileDetail = $stmt2->fetch(PDO::FETCH_ASSOC);

            $targetFileName = $previousRevisionFile['original_name'];
            $targetFileType = 'Revizyon Dosyası';
            $targetFileColor = 'warning';
            $targetFileIcon = 'edit';
            $targetFileInfo = [
                'name' => $revisionFileDetail['original_name'] ?? $previousRevisionFile['original_name'],
                'size' => $revisionFileDetail['file_size'] ?? $revision['file_size'],
                'date' => $revisionFileDetail['created_at'] ?? $revision['created_at'],
                'type' => 'Revizyon Dosyası'
            ];
            $targetDownloadUrl = 'download.php?type=revision&id=' . $revisionFileDetail['id'];
        } else {
            // Ana dosyaya revizyon talebi - ana dosya bilgilerini göster
            $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
            $targetFileType = 'Orijinal Yüklenen Dosya';
            $targetFileColor = 'success';
            $targetFileIcon = 'file-alt';
            $targetFileInfo = [
                'name' => $revision['original_name'],
                'size' => $revision['file_size'],
                'date' => $revision['created_at'],
                'type' => 'Orijinal Yüklenen Dosya'
            ];
            $targetDownloadUrl = 'download.php?id=' . $revision['upload_id'];
        }
    } catch (Exception $e) {
        error_log('Previous revision file query error: ' . $e->getMessage());
        // Hata durumunda ana dosya bilgilerini göster
        $targetFileName = $revision['original_name'] ?? 'Ana Dosya';
        $targetFileType = 'Orijinal Yüklenen Dosya';
        $targetFileColor = 'success';
        $targetFileIcon = 'file-alt';
        $targetFileInfo = [
            'name' => $revision['original_name'],
            'size' => $revision['file_size'],
            'date' => $revision['created_at'],
            'type' => 'Orijinal Yüklenen Dosya'
        ];
        $targetDownloadUrl = 'download.php?id=' . $revision['upload_id'];
    }
endif;


$pageTitle = 'Revizyon Detayı';
$pageDescription = 'Revizyon talebi detayları ve işlemleri';
$pageIcon = 'bi bi-pencil-square';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Navigation Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
        <li class="breadcrumb-item"><a href="revisions.php">Revizyon Yönetimi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Revizyon Detayı</li>
    </ol>
</nav>

<!-- Revizyon Durum Kartı -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Revizyon Talebi #<?php echo substr($revision['id'], 0, 8); ?>
                </h5>
                <div>
                    <?php
                    $statusClass = [
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger'
                    ];
                    $statusText = [
                        'pending' => 'Bekliyor',
                        'in_progress' => 'İşleniyor',
                        'completed' => 'Tamamlandı',
                        'rejected' => 'Reddedildi'
                    ];
                    ?>
                    <span class="badge bg-<?php echo $statusClass[$revision['status']] ?? 'secondary'; ?> fs-6 px-3 py-2">
                        <?php echo $statusText[$revision['status']] ?? 'Bilinmiyor'; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="bi bi-info-circle me-2"></i>Revizyon Bilgileri</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Talep Tarihi:</strong></td>
                                <td><?php echo formatDate($revision['requested_at']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Son Güncelleme:</strong></td>
                                <td><?php echo $revision['updated_at'] ? formatDate($revision['updated_at']) : 'Güncellenmemiş'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Kredi Ücreti:</strong></td>
                                <td><?php echo $revision['credits_charged']; ?> kredi</td>
                            </tr>
                            <?php if ($revision['admin_id']): ?>
                                <tr>
                                    <td><strong>İşleyen Admin:</strong></td>
                                    <td><?php echo htmlspecialchars($revision['admin_first_name'] . ' ' . $revision['admin_last_name']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-success"><i class="bi bi-user me-2"></i>Kullanıcı Bilgileri</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Ad Soyad:</strong></td>
                                <td><?php echo htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Kullanıcı Adı:</strong></td>
                                <td>@<?php echo htmlspecialchars($revision['username']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>E-posta:</strong></td>
                                <td><?php echo htmlspecialchars($revision['email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telefon:</strong></td>
                                <td><?php echo htmlspecialchars($revision['phone'] ?: 'Belirtilmemiş'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dosya Bilgileri -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-file-alt me-2"></i>Dosya Bilgileri
                </h5>
            </div>
            <div class="card-body">
                <!-- Revizyon Talep Edilen Dosya -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert-<?php echo $targetFileColor; ?> border-<?php echo $targetFileColor; ?> border-2 shadow-sm" style="background-color: var(--bs-<?php echo $targetFileColor; ?>-bg-subtle); padding: 20px; border-radius: 0.375rem;">
                            <h5 class="text-<?php echo $targetFileColor; ?> mb-3 fw-bold">
                                <i class="bi bi-<?php echo $targetFileIcon; ?> me-2 fa-lg"></i>
                                REVİZYON TALEP EDİLEN DOSYA
                            </h5>
                            <div class="badge bg-<?php echo $targetFileColor; ?> text-white mb-3 px-3 py-2">
                                <i class="bi bi-info-circle me-1"></i>
                                <?php echo $targetFileType; ?>
                            </div>

                            <?php if ($targetFileInfo): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td><strong>Dosya Adı:</strong></td>
                                                <td><?php echo htmlspecialchars($targetFileInfo['name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Dosya Türü:</strong></td>
                                                <td><?php echo htmlspecialchars($targetFileInfo['type']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Kullanıcı Talep Notu:</strong></td>
                                                <td><?php echo nl2br(htmlspecialchars($revision['request_notes'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Dosya Boyutu:</strong></td>
                                                <td><?php echo formatFileSize($targetFileInfo['size']); ?></td>
                                            </tr>
                                            <?php if ($targetFileInfo['date']): ?>
                                                <tr>
                                                    <td><strong>Dosya Tarihi:</strong></td>
                                                    <td><?php echo formatDate($targetFileInfo['date']); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center h-100">
                                            <div class="ms-3">
                                                <?php if ($targetFileType === 'Revizyon Dosyası'): ?>
                                                    <div class="alert-warning mb-0 border-warning" style="background-color: #fff3cd; padding: 10px; border-radius: 0.375rem;">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        <strong>DİKKAT:</strong> Bu bir revizyon dosyasına yapılan yeni revizyon talebidir!
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-<?php echo $targetFileColor; ?> fs-6 px-3 py-2">
                                                        Bu dosya için revizyon talep edildi
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dosya İndirme Butonu -->
                                <?php if ($targetDownloadUrl): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-center gap-3">
                                                <a href="<?php echo $targetDownloadUrl; ?>"
                                                    class="btn btn-<?php echo $targetFileColor; ?> btn-lg shadow">
                                                    <i class="bi bi-download me-2"></i>
                                                    Revizyon Talep Edilen Dosyayı İndir
                                                </a>
                                                <?php if ($targetFileType === 'Revizyon Dosyası'): ?>
                                                    <button type="button" class="btn btn-outline-info btn-lg" data-bs-toggle="modal" data-bs-target="#revisionHistoryModal">
                                                        <i class="bi bi-history me-2"></i>
                                                        Önceki Revizyonları Gör
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <p class="mb-0">Dosya bilgileri yüklenemedi.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="bi bi-upload me-2"></i>Ana Proje Dosyası</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Dosya Adı:</strong></td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($revision['original_name']); ?></span>
                                        <a href="file-detail.php?id=<?php echo $revision['upload_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm ms-2" 
                                           title="Ana projeye git">
                                            <i class="bi bi-external-link-alt me-1"></i>
                                            Ana Projeye Git
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Dosya Boyutu:</strong></td>
                                <td><?php echo formatFileSize($revision['file_size']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Yüklenme Tarihi:</strong></td>
                                <td><?php echo formatDate($revision['created_at']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Plaka:</strong></td>
                                <td><?php echo htmlspecialchars($revision['plate'] ?: 'Belirtilmemiş'); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-secondary"><i class="bi bi-car me-2"></i>Araç Bilgileri</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Marka:</strong></td>
                                <td><?php echo htmlspecialchars($revision['brand_name'] ?: 'Belirtilmemiş'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Model:</strong></td>
                                <td><?php echo htmlspecialchars($revision['model_name'] ?: 'Belirtilmemiş'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Yıl:</strong></td>
                                <td><?php echo htmlspecialchars($revision['year'] ?: 'Belirtilmemiş'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Yanıt Dosyası Bilgisi (varsa) -->
                <!-- <?php if ($revision['response_id']): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-info"><i class="bi bi-reply me-2"></i>Revize Talep Edilen Yanıt Dosyası</h6>
                            <div class="alert alert-info">
                                <strong>Dosya:</strong> <?php echo htmlspecialchars($revision['response_original_name']); ?><br>
                                <strong>Boyut:</strong> <?php echo formatFileSize($revision['response_file_size']); ?><br>
                                <small class="text-muted">Bu revizyon talebi yanıt dosyası için yapılmıştır.</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?> -->
            </div>
        </div>
    </div>
</div>

<!-- Admin İşlemleri -->
<?php if ($revision['status'] === 'pending'): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card admin-card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-gear-wide-connected me-2"></i>Admin İşlemleri
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Onayla -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="approve_revision" value="1">
                                <button type="submit" class="btn btn-success me-2 mb-2" onclick="return confirm('Revizyon talebini onaylamak istediğinizden emin misiniz?')">
                                    <i class="bi bi-check me-1"></i>Onayla ve İşleme Al
                                </button>
                            </form>
                        </div>

                        <div class="col-md-6">
                            <!-- Reddet -->
                            <button type="button" class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-times me-1"></i>Reddet
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Revize Dosyası Yükleme (in_progress durumunda) -->
<?php if ($revision['status'] === 'in_progress'): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card admin-card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-upload me-2"></i>Revize Dosyası Yükleme ve Tamamlama
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_revision" value="1">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="revision_file" class="form-label">
                                        <i class="bi bi-file me-1"></i>Revize Edilmiş Dosya <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="revision_file" name="revision_file" required>
                                    <div class="form-text">Desteklenen formatlar: .bin, .hex, .ecu, .map</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="credits_used" class="form-label">
                                        <i class="bi bi-coins me-1"></i>Kullanılan Kredi Miktarı <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="credits_used" name="credits_used"
                                        min="0" value="<?php echo $revision['credits_charged'] ?: 5; ?>" required>
                                    <div class="form-text">Bu revizyon için kullanılacak kredi miktarı</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="completion_notes" class="form-label">
                                <i class="bi bi-comment-alt me-1"></i>Tamamlama Notları
                            </label>
                            <textarea class="form-control" id="completion_notes" name="completion_notes" rows="4"
                                placeholder="Revizyon hakkında kullanıcıya iletilecek notlar (isteğe bağlı)..."></textarea>
                            <div class="form-text">Bu notlar kullanıcıya e-posta ile gönderilecek ve revizyon geçmişinde görünecektir.</div>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Revizyon Dosyasını Yükle ve Tamamla
                            </button>

                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#previewNotesModal">
                                <i class="bi bi-eye me-1"></i>Notları Önizle
                            </button>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Bilgi:</strong> Dosya yüklendiğinde revizyon otomatik olarak "Tamamlandı" durumuna geçer ve kullanıcıya bildirim gönderilir.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Revizyon Dosyaları -->
<?php if (!empty($revisionFiles)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-files me-2"></i>Yüklenen Revizyon Dosyaları (<?php echo count($revisionFiles); ?> adet)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Yüklenme Tarihi</th>
                                    <th>Yükleyen Admin</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($revisionFiles as $file): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-code text-success me-2"></i>
                                            <div class="fw-medium"><?php echo htmlspecialchars($file['original_name']); ?></div>
                                            <?php if (!empty($file['admin_notes'])): ?>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="bi bi-sticky-note me-1"></i>
                                                    <?php echo htmlspecialchars(substr($file['admin_notes'], 0, 100)); ?>
                                                    <?php if (strlen($file['admin_notes']) > 100): ?>...<?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                            <div class="mt-1">
                                                <?php if ($file['credits_charged'] > 0): ?>
                                                    <span class="badge bg-warning"><?php echo $file['credits_charged']; ?> kredi</span>
                                                <?php endif; ?>
                                                <span class="badge bg-success">Revizyon Tamamlandı</span>
                                            </div>
                                        </td>
                                        <td><?php echo formatFileSize($file['file_size']); ?></td>
                                        <td><?php echo formatDate($file['created_at']); ?></td>
                                        <td>
                                            <?php if ($file['admin_username']): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle avatar-sm me-2 bg-primary text-white">
                                                        <?php echo strtoupper(substr($file['admin_first_name'], 0, 1) . substr($file['admin_last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($file['admin_first_name'] . ' ' . $file['admin_last_name']); ?></div>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($file['admin_username']); ?></small>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Bilinmiyor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="download.php?type=revision&id=<?php echo $file['id']; ?>"
                                                    class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-download me-1"></i>İndir
                                                </a>
                                                <?php if (!isset($file['is_cancelled']) || !$file['is_cancelled']): ?>
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="showCancelModal('<?php echo $file['id']; ?>', 'revision_file', '<?php echo htmlspecialchars($file['original_name'], ENT_QUOTES); ?>')" 
                                                            title="Revizyon Dosyasını İptal Et">
                                                        <i class="bi bi-times me-1"></i>İptal
                                                    </button>
                                                <?php else: ?>
                                                    <span class="btn btn-sm btn-secondary disabled">
                                                        <i class="bi bi-ban me-1"></i>İptal Edilmiş
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Revizyon Geçmişi -->
<?php if (count($revisionHistory) > 1): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-history me-2"></i>Bu Dosyanın Revizyon Geçmişi (<?php echo count($revisionHistory); ?> adet)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($revisionHistory as $index => $historyItem): ?>
                            <div class="timeline-item <?php echo $historyItem['id'] === $revisionId ? 'current' : ''; ?>">
                                <div class="timeline-marker">
                                    <?php if ($historyItem['id'] === $revisionId): ?>
                                        <i class="bi bi-eye text-primary"></i>
                                    <?php else: ?>
                                        <span class="badge bg-<?php echo $statusClass[$historyItem['status']] ?? 'secondary'; ?>">
                                            <?php echo $index + 1; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                Revizyon #<?php echo substr($historyItem['id'], 0, 8); ?>
                                                <?php if ($historyItem['id'] === $revisionId): ?>
                                                    <span class="badge bg-primary ms-2">Şu anda görüntülenen</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                <?php echo htmlspecialchars($historyItem['first_name'] . ' ' . $historyItem['last_name']); ?>
                                                tarafından talep edildi
                                            </p>
                                            <small class="text-muted">
                                                <?php echo formatDate($historyItem['requested_at']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo $statusClass[$historyItem['status']] ?? 'secondary'; ?>">
                                                <?php echo $statusText[$historyItem['status']] ?? 'Bilinmiyor'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if ($historyItem['request_notes']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted d-block">Talep Notları:</small>
                                            <div class="bg-light p-2 rounded small">
                                                <?php echo nl2br(htmlspecialchars(substr($historyItem['request_notes'], 0, 200))); ?>
                                                <?php if (strlen($historyItem['request_notes']) > 200): ?>...<?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($historyItem['id'] !== $revisionId): ?>
                                        <div class="mt-2">
                                            <a href="revision-detail.php?id=<?php echo $historyItem['id']; ?>"
                                                class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye me-1"></i>Detay
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Reddetme Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="bi bi-times-circle me-2"></i>Revizyon Talebini Reddet
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reject_revision" value="1">

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Dikkat!</strong> Bu revizyon talebi reddedilecek ve kullanıcıya bildirilecek.
                    </div>

                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">
                            <i class="bi bi-comment me-1"></i>
                            Reddetme Sebebi <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4"
                            placeholder="Revizyon talebinin neden reddedildiğini açıklayın..." required minlength="10"></textarea>
                        <div class="form-text">Bu mesaj kullanıcıya gönderilecektir. En az 10 karakter olmalıdır.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-times me-1"></i>Vazgeç
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-times me-1"></i>Reddet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Revizyon Geçmişi Modal -->
<div class="modal fade" id="revisionHistoryModal" tabindex="-1" aria-labelledby="revisionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="revisionHistoryModalLabel">
                    <i class="bi bi-history me-2"></i>Bu Dosyanın Revizyon Geçmişi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($revisionHistory)): ?>
                    <div class="timeline-modal">
                        <?php
                        $revisionCount = 0;
                        foreach ($revisionHistory as $index => $historyItem):
                            if ($historyItem['status'] === 'completed'):
                                $revisionCount++;
                        ?>
                                <div class="timeline-item-modal <?php echo $historyItem['id'] === $revisionId ? 'current' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <span class="badge bg-warning me-2">Revizyon #<?php echo $revisionCount; ?></span>
                                                <?php if ($historyItem['id'] === $revisionId): ?>
                                                    <span class="badge bg-danger">Bu revizyona talep edildi</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-1">
                                                <strong>Talep Eden:</strong> <?php echo htmlspecialchars($historyItem['first_name'] . ' ' . $historyItem['last_name']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar me-1"></i><?php echo formatDate($historyItem['requested_at']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if ($historyItem['id'] !== $revisionId): ?>
                                                <a href="revision-detail.php?id=<?php echo $historyItem['id']; ?>"
                                                    class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-eye me-1"></i>Detay
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($historyItem['request_notes']): ?>
                                        <div class="bg-light p-2 rounded small mt-2">
                                            <strong>Talep Notları:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($historyItem['request_notes'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">Henüz tamamlanmış revizyon bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-times me-1"></i>Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Notları Önizleme Modal -->
<div class="modal fade" id="previewNotesModal" tabindex="-1" aria-labelledby="previewNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="previewNotesModalLabel">
                    <i class="bi bi-eye me-2"></i>Kullanıcıya Gönderilecek E-posta Önizlemesi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Bilgi:</strong> Bu önizleme, kullanıcıya gönderilecek e-postanın içeriğini gösterir.
                </div>

                <div class="border rounded p-3" style="background-color: #f8f9fa;">
                    <h6 class="text-primary">Konu: Revizyon Talebiniz Tamamlandı</h6>
                    <hr>

                    <p><strong>Sayın <?php echo htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name']); ?>,</strong></p>

                    <p>Revizyon talebiniz başarıyla tamamlanmıştır.</p>

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Revizyon Bilgileri:</strong>
                            <ul class="list-unstyled ms-3">
                                <li><i class="bi bi-hashtag me-1"></i> ID: #<?php echo substr($revisionId, 0, 8); ?></li>
                                <li><i class="bi bi-calendar me-1"></i> Talep Tarihi: <?php echo formatDate($revision['requested_at']); ?></li>
                                <li><i class="bi bi-file me-1"></i> Dosya: <?php echo htmlspecialchars($revision['original_name']); ?></li>
                                <li><i class="bi bi-coins me-1"></i> Kullanılan Kredi: <span id="preview-credits">5</span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <strong>Araç Bilgileri:</strong>
                            <ul class="list-unstyled ms-3">
                                <li><i class="bi bi-car me-1"></i> Marka: <?php echo htmlspecialchars($revision['brand_name'] ?: 'Belirtilmemiş'); ?></li>
                                <li><i class="bi bi-gear-wide-connected me-1"></i> Model: <?php echo htmlspecialchars($revision['model_name'] ?: 'Belirtilmemiş'); ?></li>
                                <li><i class="bi bi-calendar-alt me-1"></i> Yıl: <?php echo htmlspecialchars($revision['year'] ?: 'Belirtilmemiş'); ?></li>
                                <li><i class="bi bi-id-card me-1"></i> Plaka: <?php echo htmlspecialchars($revision['plate'] ?: 'Belirtilmemiş'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <div id="notes-preview-section" style="display: none;">
                        <hr>
                        <strong>Admin Notları:</strong>
                        <div class="bg-light p-2 rounded mt-2">
                            <div id="preview-notes-content"></div>
                        </div>
                    </div>

                    <hr>
                    <p>Revize edilmiş dosyanızı hesabınızdan indirebilirsiniz.</p>
                    <p class="text-muted"><small>Mr ECU Ekibi</small></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-times me-1"></i>Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 2rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }

    .timeline-item.current .timeline-content {
        border: 2px solid #0d6efd;
        background: #f8f9ff;
    }

    .timeline-marker {
        position: absolute;
        left: -2rem;
        top: 0;
        width: 2rem;
        height: 2rem;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        z-index: 1;
    }

    .timeline-content {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-left: 1rem;
    }

    /* Modal Timeline Stilleri */
    .timeline-modal {
        max-height: 500px;
        overflow-y: auto;
    }

    .timeline-item-modal {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .timeline-item-modal:hover {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border-color: #0d6efd;
    }

    .timeline-item-modal.current {
        background: #ffeaa7;
        border: 2px solid #fdcb6e;
    }

    /* Revizyon Talep Edilen Dosya Alert Özel Stilleri */
    .alert-warning.border-warning.border-2 {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe5a0 100%);
    }

    .alert-info.border-info.border-2 {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    }

    .alert-success.border-success.border-2 {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    }

    /* Avatar Circle Stilleri */
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .avatar-circle.avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 12px;
    }
</style>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Alert mesajlarını otomatik kapat
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }
        }, 8000); // 8 saniye
    });
    
    // Notları önizleme modalı için event listener
    const completionNotesTextarea = document.getElementById('completion_notes');
    const creditsUsedInput = document.getElementById('credits_used');
    const previewNotesModal = document.getElementById('previewNotesModal');
    
    if (previewNotesModal) {
        previewNotesModal.addEventListener('show.bs.modal', function() {
            // Kredi miktarını güncelle
            const creditsValue = creditsUsedInput ? creditsUsedInput.value : '5';
            const previewCreditsSpan = document.getElementById('preview-credits');
            if (previewCreditsSpan) {
                previewCreditsSpan.textContent = creditsValue;
            }
            
            // Notları güncelle
            const notesValue = completionNotesTextarea ? completionNotesTextarea.value.trim() : '';
            const notesPreviewSection = document.getElementById('notes-preview-section');
            const previewNotesContent = document.getElementById('preview-notes-content');
            
            if (notesValue && notesPreviewSection && previewNotesContent) {
                previewNotesContent.innerHTML = notesValue.replace(/\n/g, '<br>');
                notesPreviewSection.style.display = 'block';
            } else if (notesPreviewSection) {
                notesPreviewSection.style.display = 'none';
            }
        });
    }
    
    // Kredi miktarı değiştiğinde real-time önizleme güncellemesi
    if (creditsUsedInput) {
        creditsUsedInput.addEventListener('input', function() {
            const previewCreditsSpan = document.getElementById('preview-credits');
            if (previewCreditsSpan) {
                previewCreditsSpan.textContent = this.value;
            }
        });
    }
    
    // Form submit öncesi onay
    const uploadForm = document.querySelector('form[method=\"POST\"][enctype=\"multipart/form-data\"]');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('revision_file');
            const creditsInput = document.getElementById('credits_used');
            
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Lütfen bir dosya seçin.');
                return;
            }
            
            const credits = parseInt(creditsInput.value);
            if (credits < 0 || credits > 100) {
                e.preventDefault();
                alert('Kredi miktarı 0-100 arasında olmalıdır.');
                return;
            }
            
            if (!confirm('Revizyon dosyasını yükleyip tamamlamak istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                e.preventDefault();
                return;
            }
        });
    }
});
";

// Footer include
include '../includes/admin_footer.php';
?>

<!-- Admin İptal Modal -->
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
                            <small>Bu işlem dosyayı gizleyecek ve varsa ücret iadesi yapacaktır.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adminCancelNotes" class="form-label">
                            <i class="bi bi-sticky-note me-1"></i>
                            İptal Sebebi (Opsiyonel)
                        </label>
                        <textarea class="form-control" id="adminCancelNotes" name="admin_notes" rows="3" 
                                  placeholder="İptal sebebinizi yazabilirsiniz..."></textarea>
                        <small class="text-muted">Bu not kullanıcıya gönderilecek bildirimde yer alacaktır.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="bi bi-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-check me-1"></i>Evet, İptal Et
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}
</style>

<script>
    // Admin iptal modal gösterme
    function showCancelModal(fileId, fileType, fileName) {
        document.getElementById('cancelFileId').value = fileId;
        document.getElementById('cancelFileType').value = fileType;
        document.getElementById('cancelFileName').textContent = fileName;
        document.getElementById('adminCancelNotes').value = '';
        
        var modal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
        modal.show();
    }
</script>