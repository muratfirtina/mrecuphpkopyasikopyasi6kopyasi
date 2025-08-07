<?php
/**
 * Mr ECU - Revize Detay Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// UUID validation function çakışmasını önlemek için config.php'deki fonksiyonu kullan
// Config.php'de zaten doğru UUID validation var, burada kendi fonksiyonumuzu tanımlamaya gerek yok

// Helper function for file size formatting
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';
        $k = 1024;
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB');
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

// DEBUG: Geçici bypass - session sorunu için
// TODO: Bu kısmı session sorunu çözüldükten sonra kaldır!
if (defined('DEBUG') && DEBUG) {
    // Debug modunda belirli bir kullanıcı ID'si ile test et
    $debugUserId = '3fbe9c59-53de-4bcd-a83b-21634f467203'; // Debug sayfasından aldığımız user_id
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $debugUserId;
        error_log('DEBUG MODE: Temporary user_id set for testing: ' . $debugUserId);
    }
}

// Giriş kontrolü
if (!isLoggedIn()) {
    if (defined('DEBUG') && DEBUG) {
        error_log('DEBUG: User not logged in. Session user_id: ' . ($_SESSION['user_id'] ?? 'NOT_SET'));
    }
    redirect('../login.php?redirect=user/revision-detail.php');
}

// Revize ID kontrolü
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Revize ID belirtilmedi.';
    redirect('revisions.php');
}

$revisionId = sanitize($_GET['id']);
$userId = $_SESSION['user_id'];

// Debug: Gelen ID'yi logla
if (defined('DEBUG') && DEBUG) {
    error_log('Revision Detail Debug - Original ID: ' . ($_GET['id'] ?? 'NO_ID'));
    error_log('Revision Detail Debug - Sanitized ID: ' . $revisionId);
    error_log('Revision Detail Debug - User ID: ' . $userId);
}

// UUID formatını kontrol et - config.php'den gelen fonksiyonu kullan
if (!isValidUUID($revisionId)) {
    if (defined('DEBUG') && DEBUG) {
        error_log('Revision Detail Debug - Invalid UUID format: ' . $revisionId);
    }
    $_SESSION['error'] = 'Geçersiz revize ID formatı: ' . substr($revisionId, 0, 20) . '...';
    redirect('revisions.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);

// Revize detaylarını getir
try {
    if (defined('DEBUG') && DEBUG) {
        error_log('Revision Detail Debug - About to query database with ID: ' . $revisionId . ', User ID: ' . $userId);
    }
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               fu.original_name, fu.filename, fu.file_size, fu.status as file_status, fu.upload_date as file_uploaded_at,
               fu.file_type, fu.hp_power, fu.nm_torque, fu.plate,
               u.username as admin_username, u.first_name as admin_first_name, u.last_name as admin_last_name,
               br.name as brand_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN users u ON r.admin_id = u.id
        LEFT JOIN brands br ON fu.brand_id = br.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$revisionId, $userId]);
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (defined('DEBUG') && DEBUG) {
        error_log('Revision Detail Debug - Query result: ' . ($revision ? 'FOUND' : 'NOT_FOUND'));
        if ($revision) {
            error_log('Revision Detail Debug - Found revision ID: ' . ($revision['id'] ?? 'NO_ID'));
        }
    }
    
    if (!$revision) {
        if (defined('DEBUG') && DEBUG) {
            error_log('Revision Detail Debug - No revision found for ID: ' . $revisionId . ', User: ' . $userId);
        }
        $_SESSION['error'] = 'Revize bulunamadı veya bu revizeyi görüntüleme yetkiniz yok. ID: ' . substr($revisionId, 0, 8) . '...';
        redirect('revisions.php');
    }
} catch(PDOException $e) {
    if (defined('DEBUG') && DEBUG) {
        error_log('Revision Detail Debug - Database error: ' . $e->getMessage());
    }
    $_SESSION['error'] = 'Veritabanı hatası oluştu: ' . ($e->getMessage());
    redirect('revisions.php');
}
// YENİ EKLENEN BLOK: Revize talep edilen doğru dosyayı bul
$targetFile = [
    'type' => 'Bilinmiyor',
    'name' => 'Dosya bilgisi alınamadı',
    'size' => 0,
    'date' => null,
    'is_found' => false
];

try {
    // Önce revizyonun bir "yanıt dosyası" için mi yapıldığını kontrol et
    // Bunun için ana sorguya 'r.response_id' eklememiz gerekiyor.
    // Eğer ana sorgunuzda 'r.*' varsa bu zaten dahildir.
    $stmt = $pdo->prepare("SELECT response_id FROM revisions WHERE id = ?");
    $stmt->execute([$revisionId]);
    $revisionDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $responseId = $revisionDetails['response_id'] ?? null;

    if ($responseId) {
        // Evet, bu bir yanıt dosyası için revize talebi. Yanıt dosyasının bilgilerini çekelim.
        $stmt = $pdo->prepare("SELECT original_name, file_size, upload_date FROM file_responses WHERE id = ?");
        $stmt->execute([$responseId]);
        $responseFileData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($responseFileData) {
            $targetFile = [
                'type' => 'Yanıt Dosyası',
                'name' => $responseFileData['original_name'],
                'size' => $responseFileData['file_size'],
                'date' => $responseFileData['upload_date'],
                'is_found' => true
            ];
        }
    } else {
        // Bu, ana dosya veya önceki bir revizyon dosyası için bir talep.
        // Bu talepten ÖNCE tamamlanmış son revizyon dosyasını bulmaya çalışalım.
        $stmt = $pdo->prepare("
            SELECT rf.original_name, rf.file_size, rf.upload_date
            FROM revisions r
            JOIN revision_files rf ON r.id = rf.revision_id
            WHERE r.upload_id = ? AND r.status = 'completed' AND r.requested_at < ?
            ORDER BY r.completed_at DESC
            LIMIT 1
        ");
        $stmt->execute([$revision['upload_id'], $revision['requested_at']]);
        $previousRevisionFile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($previousRevisionFile) {
            // Önceki bir revizyon dosyası bulundu. Hedefimiz bu.
            $targetFile = [
                'type' => 'Önceki Revizyon Dosyası',
                'name' => $previousRevisionFile['original_name'],
                'size' => $previousRevisionFile['file_size'],
                'date' => $previousRevisionFile['upload_date'],
                'is_found' => true
            ];
        } else {
            // Önceki bir revizyon dosyası yoksa, hedefimiz orijinal dosyadır.
            // Bu bilgiler zaten ana $revision sorgusunda mevcut.
            $targetFile = [
                'type' => 'Orijinal Dosya',
                'name' => $revision['original_name'],
                'size' => $revision['file_size'],
                'date' => $revision['file_uploaded_at'],
                'is_found' => true
            ];
        }
    }
} catch (PDOException $e) {
    // Hata durumunda logla, ama sayfayı bozma.
    error_log("Hedef dosya belirlenirken hata: " . $e->getMessage());
}
// YENİ BLOK SONU

// Revizyon dosyalarını getir (eğer revizyon tamamlanmışsa)
$revisionFiles = [];
if ($revision['status'] === 'completed') {
    $revisionFiles = $fileManager->getRevisionFiles($revisionId, $userId);
}

// Status konfigürasyonu
$statusConfig = [
    'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock', 'bg' => 'warning'],
    'in_progress' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cog', 'bg' => 'info'],
    'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle', 'bg' => 'success'],
    'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle', 'bg' => 'danger'],
    'cancelled' => ['class' => 'secondary', 'text' => 'İptal Edildi', 'icon' => 'ban', 'bg' => 'secondary']
];
$currentStatus = $statusConfig[$revision['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question', 'bg' => 'secondary'];

$pageTitle = 'Revize Detayı - #' . substr($revision['id'], 0, 8);

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="pt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Panel</a></li>
                    <li class="breadcrumb-item"><a href="revisions.php">Revize Taleplerim</a></li>
                    <li class="breadcrumb-item active">#<?php echo substr($revision['id'], 0, 8); ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-edit me-2 text-<?php echo $currentStatus['class']; ?>"></i>
                        Revize Detayı #<?php echo substr($revision['id'], 0, 8); ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <span class="badge bg-<?php echo $currentStatus['bg']; ?> me-2">
                            <i class="fas fa-<?php echo $currentStatus['icon']; ?> me-1"></i>
                            <?php echo $currentStatus['text']; ?>
                        </span>
                        <?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?> tarihinde talep edildi
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="revisions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Listeye Dön
                        </a>
                        <?php if ($revision['status'] === 'completed' && !empty($revisionFiles)): ?>
                            <?php if (count($revisionFiles) == 1): ?>
                                <a href="download-revision.php?id=<?php echo $revisionFiles[0]['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-download me-1"></i>Revize Dosyasını İndir
                                </a>
                            <?php else: ?>
                                <div class="btn-group">
                                    <a href="download-revision.php?id=<?php echo $revisionFiles[0]['id']; ?>" class="btn btn-success">
                                        <i class="fas fa-download me-1"></i>Ana Dosyayı İndir
                                    </a>
                                    <a href="download-revision.php?id=<?php echo $revision['id']; ?>" class="btn btn-outline-success">
                                        <i class="fas fa-download me-1"></i>Tümünü
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="files.php?view=<?php echo $revision['upload_id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-file me-1"></i>Orijinal Dosya
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Sol Kolon - Ana Bilgiler -->
                <div class="col-lg-8">
                    <!-- Revize Timeline -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Revize Sürecı
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <!-- Talep Oluşturuldu -->
                                <div class="timeline-item completed">
                                    <div class="timeline-marker">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize Talebi Oluşturuldu</h6>
                                        <p class="text-muted mb-1"><?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?></p>
                                        <small class="text-muted">Revize talebiniz sisteme kaydedildi ve inceleme kuyruğuna alındı.</small>
                                    </div>
                                </div>

                                <!-- Admin Atandı -->
                                <?php if ($revision['admin_id']): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker">
                                        <i class="fas fa-user-cog"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Admin Atandı</h6>
                                        <p class="text-muted mb-1">
                                            <strong><?php echo htmlspecialchars(($revision['admin_username'] ?? 'Admin bilgisi yok')); ?></strong>
                                            <?php if (($revision['admin_first_name'] ?? '')): ?>
                                                (<?php echo htmlspecialchars(($revision['admin_first_name'] ?? '') . ' ' . ($revision['admin_last_name'] ?? '')); ?>)
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted">Talebiniz uzman bir admin tarafından incelemeye alındı.</small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- İşlem Durumu -->
                                <?php if ($revision['status'] === 'in_progress'): ?>
                                <div class="timeline-item active">
                                    <div class="timeline-marker">
                                        <i class="fas fa-cog fa-spin"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize İşleniyor</h6>
                                        <p class="text-muted mb-1">Şu anda</p>
                                        <small class="text-muted">Dosyanız üzerinde revize işlemleri gerçekleştiriliyor.</small>
                                    </div>
                                </div>
                                <?php elseif ($revision['status'] === 'completed'): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize Tamamlandı</h6>
                                        <p class="text-muted mb-1"><?php echo $revision['completed_at'] ? date('d.m.Y H:i', strtotime($revision['completed_at'])) : 'Bilinmiyor'; ?></p>
                                        <small class="text-muted">Revize işlemi başarıyla tamamlandı ve dosyanız hazır.</small>
                                    </div>
                                </div>
                                <?php elseif ($revision['status'] === 'rejected'): ?>
                                <div class="timeline-item rejected">
                                    <div class="timeline-marker">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Revize Reddedildi</h6>
                                        <p class="text-muted mb-1"><?php echo $revision['completed_at'] ? date('d.m.Y H:i', strtotime($revision['completed_at'])) : 'Bilinmiyor'; ?></p>
                                        <small class="text-muted">Talep belirtilen nedenlerle reddedildi.</small>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="timeline-item pending">
                                    <div class="timeline-marker">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>İnceleme Bekliyor</h6>
                                        <p class="text-muted mb-1">Beklemede</p>
                                        <small class="text-muted">Talebiniz admin ekibimiz tarafından inceleniyor.</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Talep Detayları -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-comment-alt me-2"></i>Talep Detaylarınız
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($revision['request_notes']): ?>
                                <div class="request-notes">
                                    <?php echo nl2br(htmlspecialchars($revision['request_notes'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted fst-italic">Talep sırasında not belirtilmemiş.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Admin Yanıtı -->
                    <?php if (($revision['admin_notes'] ?? 'Admin notu yok')): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-cog me-2"></i>Admin Yanıtı
                                <?php if (($revision['admin_username'] ?? 'Admin bilgisi yok')): ?>
                                    <small class="text-muted">- <?php echo htmlspecialchars(($revision['admin_username'] ?? 'Admin bilgisi yok')); ?></small>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="admin-response">
                                <?php echo nl2br(htmlspecialchars(($revision['admin_notes'] ?? 'Admin notu yok'))); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Dosya Bilgileri -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Dosya Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Revize Edilmesi İstenen Dosya (Dinamik) -->
<div class="col-md-6">
    <h6 class="text-muted mb-3">
        <?php
            // Dosya tipine göre ikon ve renk belirleyelim
            $typeConfig = [
                'Orijinal Dosya'           => ['icon' => 'fa-file-alt', 'color' => 'primary'],
                'Yanıt Dosyası'            => ['icon' => 'fa-reply', 'color' => 'info'],
                'Önceki Revizyon Dosyası' => ['icon' => 'fa-edit', 'color' => 'warning'],
                'Bilinmiyor'               => ['icon' => 'fa-question-circle', 'color' => 'secondary']
            ];
            $currentType = $typeConfig[$targetFile['type']] ?? $typeConfig['Bilinmiyor'];
        ?>
        <i class="fas <?php echo $currentType['icon']; ?> text-<?php echo $currentType['color']; ?> me-2"></i>
        Revize Edilmesi İstenen Dosya
    </h6>
    
    <?php if ($targetFile['is_found']): ?>
        <div class="file-info">
            <div class="file-icon">
                <i class="fas fa-file-code text-primary"></i>
            </div>
            <div class="file-details">
                <h6 class="file-name"><?php echo htmlspecialchars($targetFile['name']); ?></h6>
                <div class="file-meta">
                    <span class="badge bg-light text-dark me-2">
                        <i class="fas fa-hdd me-1"></i>
                        <?php echo formatFileSize($targetFile['size']); ?>
                    </span>
                    <span class="badge bg-<?php echo $currentType['color']; ?>">
                        <i class="fas fa-info-circle me-1"></i>
                        <?php echo htmlspecialchars($targetFile['type']); ?>
                    </span>
                </div>
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    <?php echo $targetFile['date'] ? date('d.m.Y H:i', strtotime($targetFile['date'])) : 'Bilinmiyor'; ?>
                </small>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            Revize edilecek dosya bilgisi alınamadı.
        </div>
    <?php endif; ?>
</div>

                                <!-- Revize Edilmiş Dosya -->
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Revize Edilmiş Dosya</h6>
                                    <?php if ($revision['status'] === 'completed' && !empty($revisionFiles)): ?>
                                        <?php $firstRevisionFile = $revisionFiles[0]; // İlk revizyon dosyasını göster ?>
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="fas fa-file-code text-success"></i>
                                            </div>
                                            <div class="file-details">
                                                <h6 class="file-name"><?php echo htmlspecialchars($firstRevisionFile['original_name']); ?></h6>
                                                <div class="file-meta">
                                                    <span class="badge bg-light text-dark me-2">
                                                        <i class="fas fa-hdd me-1"></i>
                                                        <?php echo formatFileSize($firstRevisionFile['file_size']); ?>
                                                    </span>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>
                                                        Hazır
                                                    </span>
                                                    <?php if (count($revisionFiles) > 1): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-plus me-1"></i>
                                                        +<?php echo count($revisionFiles) - 1; ?> dosya daha
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d.m.Y H:i', strtotime($firstRevisionFile['upload_date'])); ?>
                                                </small>
                                                <div class="mt-2">
                                                    <a href="download-revision.php?id=<?php echo $firstRevisionFile['id']; ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-download me-1"></i>İndir
                                                    </a>
                                                    <?php if (count($revisionFiles) > 1): ?>
                                                        <a href="download-revision.php?id=<?php echo $revision['id']; ?>" class="btn btn-outline-success btn-sm ms-2">
                                                            <i class="fas fa-download me-1"></i>Tümünü İndir
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-clock text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2 mb-0">
                                            <?php 
                                            switch($revision['status']) {
                                                case 'pending':
                                                    echo 'Revize işlemi henüz başlamadı';
                                                    break;
                                                case 'in_progress':
                                                    echo 'Revize işlemi devam ediyor';
                                                    break;
                                                case 'rejected':
                                                    echo 'Revize talebi reddedildi';
                                                    break;
                                                default:
                                                    echo 'Revize dosyası hazır değil';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon - Özet Bilgiler -->
                <div class="col-lg-4">
                    <!-- Revize Özeti -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Revize Özeti
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-list">
                                <div class="summary-item">
                                    <span class="summary-label">Revize ID:</span>
                                    <span class="summary-value font-monospace"><?php echo substr($revision['id'], 0, 8); ?>...</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Durum:</span>
                                    <span class="summary-value">
                                        <span class="badge bg-<?php echo $currentStatus['bg']; ?>">
                                            <i class="fas fa-<?php echo $currentStatus['icon']; ?> me-1"></i>
                                            <?php echo $currentStatus['text']; ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Talep Tarihi:</span>
                                    <span class="summary-value"><?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?></span>
                                </div>
                                <?php if ($revision['completed_at']): ?>
                                <div class="summary-item">
                                    <span class="summary-label">Tamamlanma:</span>
                                    <span class="summary-value"><?php echo date('d.m.Y H:i', strtotime($revision['completed_at'])); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="summary-item">
                                    <span class="summary-label">Kredi:</span>
                                    <span class="summary-value <?php echo $revision['credits_charged'] > 0 ? 'text-warning' : 'text-success'; ?>">
                                        <?php echo $revision['credits_charged'] > 0 ? $revision['credits_charged'] . ' Kredi' : 'Ücretsiz'; ?>
                                    </span>
                                </div>
                                <?php if (($revision['admin_username'] ?? 'Admin bilgisi yok')): ?>
                                <div class="summary-item">
                                    <span class="summary-label">Admin:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars(($revision['admin_username'] ?? 'Admin bilgisi yok')); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- İşlemler -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools me-2"></i>İşlemler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($revision['status'] === 'completed' && !empty($revisionFiles)): ?>
                                    <?php if (count($revisionFiles) == 1): ?>
                                        <a href="download-revision.php?id=<?php echo $revisionFiles[0]['id']; ?>" class="btn btn-success">
                                            <i class="fas fa-download me-2"></i>Revize Dosyasını İndir
                                        </a>
                                    <?php else: ?>
                                        <a href="download-revision.php?id=<?php echo $revisionFiles[0]['id']; ?>" class="btn btn-success">
                                            <i class="fas fa-download me-2"></i>Ana Dosyayı İndir
                                        </a>
                                        <a href="download-revision.php?id=<?php echo $revision['id']; ?>" class="btn btn-outline-success">
                                            <i class="fas fa-download me-2"></i>Tüm Dosyaları İndir
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <a href="files.php?view=<?php echo $revision['upload_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-file me-2"></i>Orijinal Dosyayı Görüntüle
                                </a>
                                
                                <a href="revisions.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-list me-2"></i>Tüm Revize Taleplerim
                                </a>
                                
                                <?php if ($revision['status'] === 'completed'): ?>
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#newRevisionModal">
                                    <i class="fas fa-plus me-2"></i>Yeni Revize Talebi
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Yardım -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>Yardım
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Revize süreciyle ilgili sorularınız varsa:</p>
                            <div class="d-grid gap-2">
                                <a href="contact.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-envelope me-2"></i>İletişime Geç
                                </a>
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                                    <i class="fas fa-info me-2"></i>Revize Hakkında
                                </button>
                            </div>
                        
            <!-- Revizyon Dosyaları Bölümü -->
            <?php if (!empty($revisionFiles)): ?>
                <div class="col-12">
                    <div class="info-card">
                        <div class="info-header">
                            <h6 class="mb-0">
                                <i class="fas fa-download me-2 text-success"></i>
                                Revizyon Dosyaları (<?php echo count($revisionFiles); ?> adet)
                            </h6>
                        </div>
                        <div class="info-content">
                            <?php foreach ($revisionFiles as $revFile): ?>
                                <div class="revision-file-item mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="file-info">
                                            <h6 class="mb-1">
                                                <i class="fas fa-file-download me-1 text-success"></i>
                                                <?php echo htmlspecialchars($revFile['original_name']); ?>
                                            </h6>
                                            <div class="file-meta">
                                                <span class="badge bg-light text-dark me-2">
                                                    <?php echo formatFileSize($revFile['file_size']); ?>
                                                </span>
                                                <span class="text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($revFile['upload_date'])); ?>
                                                </span>
                                            </div>
                                            <?php if ($revFile['admin_notes']): ?>
                                                <div class="admin-notes mt-2">
                                                    <small class="text-muted">
                                                        <strong>Admin Notları:</strong> <?php echo nl2br(htmlspecialchars($revFile['admin_notes'])); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="file-actions">
                                            <a href="download-revision.php?id=<?php echo $revFile['id']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-1"></i>İndir
                                            </a>
                                            <button type="button" class="btn btn-outline-warning btn-sm ms-2" 
                                                    onclick="openRevisionFileModal('<?php echo $revFile['id']; ?>', '<?php echo htmlspecialchars($revFile['original_name']); ?>)">
                                                <i class="fas fa-edit me-1"></i>Revize Talep Et
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Yardım Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2 text-primary"></i>Revize Sistemi Hakkında
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="info-section">
                    <h6><i class="fas fa-question-circle me-2"></i>Revize Sistemi Nedir?</h6>
                    <p>Revize sistemi, tamamlanmış ECU dosyalarınızda değişiklik talep etmenizi sağlayan özelliğimizdir.</p>
                </div>
                
                <div class="info-section">
                    <h6><i class="fas fa-cog me-2"></i>Süreç Nasıl İşler?</h6>
                    <ol class="ps-3">
                        <li>Revize talebiniz sisteme kaydedilir</li>
                        <li>Uzman admin tarafından incelenir</li>
                        <li>Dosyanız üzerinde gerekli değişiklikler yapılır</li>
                        <li>Tamamlandığında size bildirim gönderilir</li>
                        <li>Revize dosyanızı indirebilirsiniz</li>
                    </ol>
                </div>
                
                <div class="info-section">
                    <h6><i class="fas fa-clock me-2"></i>İşlem Süresi</h6>
                    <p>Revize talepleri genellikle 24-72 saat içinde tamamlanır. Karmaşık değişiklikler daha uzun sürebilir.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Anladım</button>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Revize Talebi Modal -->
<div class="modal fade" id="newRevisionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2 text-warning"></i>Yeni Revize Talebi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newRevisionForm">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Mevcut Dosya:</strong> <?php echo htmlspecialchars($revision['original_name'] ?? 'Dosya bilgisi yok'); ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="revisionNotes" class="form-label">
                            <i class="fas fa-comment me-2"></i>Revize Talebi Açıklaması *
                        </label>
                        <textarea class="form-control" id="revisionNotes" name="revision_notes" rows="4" 
                                  placeholder="Lütfen dosyanızda hangi değişikliklerin yapılmasını istediğinizi detaylı bir şekilde açıklayın..." 
                                  required></textarea>
                        <div class="form-text">
                            Örnek: "Güç artışını 250 HP'ye çıkarın", "Tork limiti 350 Nm olsun", "Launch control ekleyin" gibi...
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Önemli:</strong> Revize talebi gönderildikten sonra admin ekibimiz tarafından değerlendirilecektir. 
                        Talebin işleme alınması durumunda kredi hesabınızdan düşüm yapılabilir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-warning" id="submitRevisionBtn">
                        <i class="fas fa-paper-plane me-1"></i>Revize Talebi Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Revize Detay Sayfası Stilleri */
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

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    top: 0;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    z-index: 1;
}

.timeline-item.completed .timeline-marker {
    background: #28a745;
}

.timeline-item.active .timeline-marker {
    background: #007bff;
}

.timeline-item.pending .timeline-marker {
    background: #ffc107;
    color: #212529;
}

.timeline-item.rejected .timeline-marker {
    background: #dc3545;
}

.timeline-content h6 {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.timeline-content p {
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.request-notes, .admin-response {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #007bff;
    line-height: 1.6;
}

.admin-response {
    border-left-color: #28a745;
}

.file-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.file-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    word-break: break-all;
}

.file-meta {
    margin-bottom: 0.5rem;
}

.summary-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f8f9fa;
}

.summary-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.summary-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.9rem;
}

.summary-value {
    font-weight: 600;
    color: #495057;
    text-align: right;
}

.info-section {
    margin-bottom: 1.5rem;
}

.info-section:last-child {
    margin-bottom: 0;
}

.info-section h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.info-section p, .info-section ol {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 767.98px) {
    .timeline {
        padding-left: 1.5rem;
    }
    
    .timeline::before {
        left: 0.75rem;
    }
    
    .timeline-marker {
        left: -0.25rem;
        width: 1.5rem;
        height: 1.5rem;
        font-size: 0.75rem;
    }
    
    .file-info {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .summary-value {
        text-align: left;
    }
}
</style>

<script type="text/javascript">

// Auto refresh configuration
var enableAutoRefresh = <?php echo ($revision['status'] === 'pending' || $revision['status'] === 'in_progress') ? 'true' : 'false'; ?>;
var revisionUploadId = '<?php echo $revision['upload_id']; ?>';

// Auto refresh for pending revisions
if (enableAutoRefresh) {
    setTimeout(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 60000); // 60 seconds
}

// DOM yüklendikten sonra çalışacak kodlar
document.addEventListener('DOMContentLoaded', function() {
    // Yeni Revize Talebi Form İşlevselliği
    var newRevisionForm = document.getElementById('newRevisionForm');
    var submitBtn = document.getElementById('submitRevisionBtn');
    var revisionNotes = document.getElementById('revisionNotes');
    
    if (newRevisionForm) {
        var newRevisionModal = new bootstrap.Modal(document.getElementById('newRevisionModal'));
        
        newRevisionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var notes = revisionNotes.value.replace(/^\s+|\s+$/g, ''); // trim alternative
            
            if (!notes) {
                showAlert('Revize talebi açıklaması gereklidir.', 'danger');
                return;
            }
            
            if (notes.length < 10) {
                showAlert('Revize talebi açıklaması en az 10 karakter olmalıdır.', 'danger');
                return;
            }
            
            // Buton durumunu değiştir
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gönderiliyor...';
            
            // XMLHttpRequest ile AJAX isteği (eski tarayıcı uyumlu)
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/create_revision.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    // Buton durumunu eski haline getir
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Revize Talebi Gönder';
                    
                    if (xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                // Başarılı
                                showAlert(data.message, 'success');
                                newRevisionModal.hide();
                                
                                // Formu temizle
                                revisionNotes.value = '';
                                
                                // 2 saniye sonra sayfayı yenile
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                // Hata
                                showAlert(data.message, 'danger');
                            }
                        } catch (error) {
                            console.error('JSON Parse Error:', error);
                            showAlert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.', 'danger');
                        }
                    } else {
                        console.error('HTTP Error:', xhr.status);
                        showAlert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.', 'danger');
                    }
                }
            };
            
            var requestData = JSON.stringify({
                upload_id: revisionUploadId,
                revision_notes: notes
            });
            
            xhr.send(requestData);
        });
        
        // Modal temizleme
        document.getElementById('newRevisionModal').addEventListener('hidden.bs.modal', function() {
            revisionNotes.value = '';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Revize Talebi Gönder';
        });
    }
});

/**
 * Alert mesajı gösterir
 * @param {string} message - Gösterilecek mesaj
 * @param {string} type - Alert tipi (success, danger, warning, info)
 */
var showAlert = function(message, type) {
    // Mevcut alertleri kaldır
    var existingAlerts = document.querySelectorAll('.alert-dynamic');
    for (var i = 0; i < existingAlerts.length; i++) {
        existingAlerts[i].remove();
    }
    
    // Yeni alert oluştur
    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show alert-dynamic';
    alertDiv.innerHTML = '<div class="d-flex align-items-center">' +
        '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + ' me-3 fa-lg"></i>' +
        '<div><strong>' + (type === 'success' ? 'Başarılı!' : 'Hata!') + '</strong> ' + message + '</div>' +
        '</div>' +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    
    // Alert'i sayfanın üstüne ekle
    var main = document.querySelector('main');
    var firstChild = main.firstElementChild;
    main.insertBefore(alertDiv, firstChild);
    
    // 5 saniye sonra otomatik kapat
    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
};

/**
 * Revizyon dosyası için modal açar
 * @param {string} revisionFileId - Revizyon dosya ID'si
 * @param {string} fileName - Dosya adı
 */
var openRevisionFileModal = function(revisionFileId, fileName) {
    // Modalı klonla ve revizyon dosyası için özelleştir
    var originalModal = document.getElementById('newRevisionModal');
    var revisionFileModal = originalModal.cloneNode(true);
    
    // ID'leri değiştir
    revisionFileModal.id = 'revisionFileModal';
    revisionFileModal.querySelector('form').id = 'revisionFileForm';
    revisionFileModal.querySelector('#revisionNotes').id = 'revisionFileNotes';
    revisionFileModal.querySelector('#submitRevisionBtn').id = 'submitRevisionFileBtn';
    
    // Modal başlık ve içeriğini güncelle
    revisionFileModal.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit me-2 text-warning"></i>Revizyon Dosyası İçin Yeni Talep';
    revisionFileModal.querySelector('.alert-info').innerHTML = '<i class="fas fa-info-circle me-2"></i><strong>Revizyon Dosyası:</strong> ' + fileName;
    
    // Modalı DOM'a ekle
    document.body.appendChild(revisionFileModal);
    
    // Bootstrap modalı başlat
    var modal = new bootstrap.Modal(revisionFileModal);
    
    // Form event listener'ı ekle
    var form = revisionFileModal.querySelector('#revisionFileForm');
    var submitBtn = revisionFileModal.querySelector('#submitRevisionFileBtn');
    var notesField = revisionFileModal.querySelector('#revisionFileNotes');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        var notes = notesField.value.replace(/^\s+|\s+$/g, ''); // trim alternative
        
        if (!notes) {
            showAlert('Revize talebi açıklaması gereklidir.', 'danger');
            return;
        }
        
        if (notes.length < 10) {
            showAlert('Revize talebi açıklaması en az 10 karakter olmalıdır.', 'danger');
            return;
        }
        
        // Buton durumunu değiştir
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gönderiliyor...';
        
        // XMLHttpRequest ile AJAX isteği (eski tarayıcı uyumlu)
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/create_revision_file.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                // Buton durumunu eski haline getir
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Revize Talebi Gönder';
                
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            // Başarılı
                            showAlert(data.message, 'success');
                            modal.hide();
                            
                            // 2 saniye sonra sayfayı yenile
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            // Hata
                            showAlert(data.message, 'danger');
                        }
                    } catch (error) {
                        console.error('JSON Parse Error:', error);
                        showAlert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.', 'danger');
                    }
                } else {
                    console.error('HTTP Error:', xhr.status);
                    showAlert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.', 'danger');
                }
            }
        };
        
        var requestData = JSON.stringify({
            revision_file_id: revisionFileId,
            revision_notes: notes
        });
        
        xhr.send(requestData);
    });
    
    // Modal kapandığında DOM'dan kaldır
    revisionFileModal.addEventListener('hidden.bs.modal', function() {
        revisionFileModal.remove();
    });
    
    // Modalı göster
    modal.show();
};
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>