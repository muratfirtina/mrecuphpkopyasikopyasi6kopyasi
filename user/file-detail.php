<?php
/**
 * Mr ECU - Dosya Detay Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/ChatManager.php';

// Admin notlarını filtreleyen fonksiyon - sadece kesin sistem mesajlarını filtreler
function filterAdminNotes($adminNotes) {
    if (empty($adminNotes)) {
        return false;
    }
    
    $trimmedNotes = trim($adminNotes);
    
    // Sadece kesin sistem mesajlarını filtrele
    $exactSystemMessages = [
        'Revize talebi işleme alındı.',
        'Dosya işleme alındı',
        'Dosya başarıyla yüklendi.'
    ];
    
    // Eğer tam olarak bu mesajlardan biriyse filtrele
    if (in_array($trimmedNotes, $exactSystemMessages)) {
        return false;
    }
    
    // "Yanıt dosyası yüklendi:" ile başlayıp dosya adı içeren otomatik mesajları filtrele
    if (strpos($trimmedNotes, 'Yanıt dosyası yüklendi:') === 0 && strpos($trimmedNotes, '.zip') !== false) {
        return false;
    }
    
    // Diğer her şey gerçek admin notu
    return true;
}

// Filtrelenmiş admin notlarını gösteren fonksiyon
function displayAdminNotes($adminNotes, $emptyMessage = 'Henüz admin notu eklenmedi.') {
    if (empty($adminNotes)) {
        return '<em class="text-muted">' . $emptyMessage . '</em>';
    }
    
    if (filterAdminNotes($adminNotes)) {
        return nl2br(htmlspecialchars($adminNotes));
    } else {
        return '<em class="text-muted">' . $emptyMessage . '</em>';
    }
}

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/file-detail.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user'; // Kullanıcı rolü

// GUID format kontrolü - User ID
if (!isValidUUID($userId)) {
    redirect('../logout.php');
}

// File ID parametresini al
$fileId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload';

if (empty($fileId) || !isValidUUID($fileId)) {
    $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    redirect('files.php');
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
    $revisionFileId = sanitize($_POST['file_id']);
    $revisionFileType = sanitize($_POST['file_type']);
    $revisionNotes = sanitize($_POST['revision_notes']);
    
    // GUID format kontrolü
    if (!isValidUUID($revisionFileId)) {
        $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
        header('Location: file-detail.php?id=' . urlencode($fileId) . '&type=' . urlencode($fileType));
        exit;
    } elseif (empty($revisionNotes)) {
        $_SESSION['error'] = 'Revize talebi için açıklama gereklidir.';
        header('Location: file-detail.php?id=' . urlencode($fileId) . '&type=' . urlencode($fileType));
        exit;
    } else {
        if ($revisionFileType === 'response') {
            // Yanıt dosyası için revize talebi
            $result = $fileManager->requestResponseRevision($revisionFileId, $userId, $revisionNotes);
        } elseif ($revisionFileType === 'revision') {
            // Revize dosyası için revize talebi
            $result = $fileManager->requestRevisionFileRevision($revisionFileId, $userId, $revisionNotes);
        } else {
            // Upload dosyası için revize talebi
            $result = $fileManager->requestRevision($revisionFileId, $userId, $revisionNotes);
        }
        
        if ($result['success']) {
            // Başarılı revize talebi sonrası redirect - PRG pattern
            $_SESSION['success'] = $result['message'];
            header('Location: file-detail.php?id=' . urlencode($fileId) . '&type=' . urlencode($fileType));
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: file-detail.php?id=' . urlencode($fileId) . '&type=' . urlencode($fileType));
            exit;
        }
    }
}

// Dosya tipine göre detayları getir
if ($fileType === 'response') {
    // Yanıt dosyası detayları
    $stmt = $pdo->prepare("
        SELECT fr.*, fu.user_id, fu.original_name as original_upload_name,
               fu.brand_id, fu.model_id, fu.series_id, fu.engine_id, fu.plate, fu.ecu_id, fu.device_id,
               fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
               b.name as brand_name, m.name as model_name,
               s.name as series_name,
               eng.name as engine_name,
               d.name as device_name,
               e.name as ecu_name,
               a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
               fu.upload_notes as original_notes
        FROM file_responses fr
        LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        LEFT JOIN series s ON fu.series_id = s.id
        LEFT JOIN engines eng ON fu.engine_id = eng.id
        LEFT JOIN devices d ON fu.device_id = d.id
        LEFT JOIN ecus e ON fu.ecu_id = e.id
        LEFT JOIN users a ON fr.admin_id = a.id
        WHERE fr.id = ? AND fu.user_id = ?
    ");
    $stmt->execute([$fileId, $userId]);
    $fileDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fileDetail) {
        $_SESSION['error'] = 'Yanıt dosyası bulunamadı veya yetkiniz yok.';
        redirect('files.php');
    }
    
    $originalUploadId = $fileDetail['upload_id'];
    $responses = []; // Yanıt dosyasının kendisi için boş
    $allRevisionFiles = []; // Yanıt dosyası için revizyon dosyaları yok
    $communicationHistory = []; // Yanıt dosyası için basit geçmiş
    
    // Yanıt dosyası için revize taleplerini al
    $detailedRevisions = [];
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.username, u.first_name, u.last_name,
                   a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                   fr.original_name as response_file_name
            FROM revisions r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN users a ON r.admin_id = a.id
            LEFT JOIN file_responses fr ON r.response_id = fr.id
            WHERE r.response_id = ? AND r.user_id = ?
            ORDER BY r.requested_at DESC
        ");
        $stmt->execute([$fileId, $userId]);
        $detailedRevisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Response detailed revisions query error: ' . $e->getMessage());
        $detailedRevisions = [];
    }
    
    $revisions = []; // Yanıt dosyası için revize talepleri ayrı olabilir
} else {
    // Ana dosya detayları
    $fileDetail = $fileManager->getUploadById($fileId);
    
    if (!$fileDetail || $fileDetail['user_id'] !== $userId) {
        $_SESSION['error'] = 'Dosya bulunamadı veya size ait değil.';
        redirect('files.php');
    }
    
    // Ana dosyaya ait yanıt dosyalarını getir
    $responses = $fileManager->getFileResponses($fileId, $userId);
    
    // Ana dosyaya ait revize taleplerini getir
    $revisions = $fileManager->getFileRevisions($fileId, $userId);
    
    // Ana dosya için tüm revizyon dosyalarını al
    $allRevisionFiles = [];
    if (!empty($revisions)) {
        foreach ($revisions as $revision) {
            if ($revision['status'] === 'completed') {
                $revisionFiles = $fileManager->getRevisionFiles($revision['id'], $userId);
                if (!empty($revisionFiles)) {
                    foreach ($revisionFiles as $revFile) {
                        $revFile['revision_id'] = $revision['id'];
                        $revFile['revision_notes'] = $revision['request_notes'];
                        $revFile['revision_date'] = $revision['requested_at'];
                        $allRevisionFiles[] = $revFile;
                    }
                }
            }
        }
    }
    
    // Kullanıcının bu dosya ile ilgili tüm revize taleplerini al (detaylı)
    $detailedRevisions = [];
    try {
        // Ana dosya için revize taleplerini al
        $stmt = $pdo->prepare("
            SELECT r.*, u.username, u.first_name, u.last_name,
                   a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                   fr.original_name as response_file_name
            FROM revisions r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN users a ON r.admin_id = a.id
            LEFT JOIN file_responses fr ON r.response_id = fr.id
            WHERE r.upload_id = ? AND r.user_id = ?
            ORDER BY r.requested_at DESC
        ");
        $stmt->execute([$fileId, $userId]);
        $detailedRevisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Detailed revisions query error: ' . $e->getMessage());
        $detailedRevisions = [];
    }
    
    // Kullanıcıyla olan tüm iletişim geçmişini topla (kronolojik sırada)
    $communicationHistory = [];
    
    try {
        // 1. Ana dosya yükleme notları
        if (!empty($fileDetail['upload_notes'])) {
            $communicationHistory[] = [
                'type' => 'user_upload',
                'date' => $fileDetail['upload_date'],
                'user_notes' => $fileDetail['upload_notes'],
                'admin_notes' => '',
                'status' => 'info',
                'file_name' => $fileDetail['original_name']
            ];
        }
        
        // 2. Yanıt dosyaları ve admin notları (sadece ana dosya için)
        if ($fileType !== 'response' && !empty($responses)) {
            foreach ($responses as $response) {
                if (!empty($response['admin_notes']) && filterAdminNotes($response['admin_notes'])) {
                    $communicationHistory[] = [
                        'type' => 'admin_response',
                        'date' => $response['upload_date'],
                        'user_notes' => '',
                        'admin_notes' => $response['admin_notes'],
                        'admin_username' => $response['admin_username'] ?? '',
                        'status' => 'success',
                        'file_name' => $response['original_name'],
                        'credits_charged' => $response['credits_charged'] ?? 0,
                        'response_id' => $response['id']
                    ];
                }
            }
        }
        
        // 3. Revize talepleri (detaylı olanlar)
        foreach ($detailedRevisions as $revision) {
            // Kullanıcının revize talebi
            $communicationHistory[] = [
                'type' => 'user_revision_request',
                'date' => $revision['requested_at'],
                'user_notes' => $revision['request_notes'],
                'admin_notes' => '',
                'status' => $revision['status'],
                'revision_id' => $revision['id'],
                'response_file_name' => $revision['response_file_name'] ?? ''
            ];
            
            // Admin'in cevabı (varsa)
            if (!empty($revision['admin_notes'])) {
                // Bu revize için dosyaları al
                $revisionResponseFiles = [];
                if ($revision['status'] === 'completed') {
                    $revisionResponseFiles = $fileManager->getRevisionFiles($revision['id'], $userId);
                }
                
                $communicationHistory[] = [
                    'type' => 'admin_revision_response',
                    'date' => $revision['completed_at'] ?: $revision['requested_at'],
                    'user_notes' => '',
                    'admin_notes' => $revision['admin_notes'],
                    'admin_username' => $revision['admin_username'] ?? '',
                    'admin_name' => ($revision['admin_first_name'] ?? '') . ' ' . ($revision['admin_last_name'] ?? ''),
                    'status' => $revision['status'],
                    'revision_id' => $revision['id'],
                    'credits_charged' => $revision['credits_charged'] ?? 0,
                    'revision_files' => $revisionResponseFiles
                ];
            }
        }
        
        // Yanıt dosyası durumunda, sadece o yanıt için admin notlarını ekle
        if ($fileType === 'response') {
            if (!empty($fileDetail['admin_notes']) && filterAdminNotes($fileDetail['admin_notes'])) {
                $communicationHistory[] = [
                    'type' => 'admin_response',
                    'date' => $fileDetail['upload_date'],
                    'user_notes' => '',
                    'admin_notes' => $fileDetail['admin_notes'],
                    'admin_username' => $fileDetail['admin_username'] ?? '',
                    'status' => 'success',
                    'file_name' => $fileDetail['original_name'],
                    'credits_charged' => $fileDetail['credits_charged'] ?? 0
                ];
            }
        }
        
        // Tarihe göre sırala (en eskiden yeniye)
        usort($communicationHistory, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
    } catch (Exception $e) {
        error_log('Communication history error: ' . $e->getMessage());
        $communicationHistory = [];
    }
    
    // Ana dosya için admin notlarını yanıt dosyasından al
    $mainFileAdminNotes = '';
    if (!empty($responses)) {
        // İlk (en son) yanıt dosyasındaki admin notunu al
        $mainFileAdminNotes = $responses[0]['admin_notes'] ?? '';
    }
    
    $originalUploadId = $fileId;
}

// Bu dosya için harcanan tüm kredilerin toplamını hesapla
$totalCreditsSpent = 0;
try {
    // Ana dosya veya yanıt dosyası için harcanan kredileri al
    if ($fileType === 'response') {
        // Yanıt dosyası ise, bu yanıt dosyası için harcanan krediyi al
        $stmt = $pdo->prepare("SELECT COALESCE(credits_charged, 0) as credits FROM file_responses WHERE id = ?");
        $stmt->execute([$fileId]);
        $responseCredits = $stmt->fetchColumn() ?: 0;
        $totalCreditsSpent += $responseCredits;
        
        // Bu yanıt dosyası için yapılan revizyon talepleri için harcanan kredileri al
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM revisions WHERE response_id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        $responseRevisionCredits = $stmt->fetchColumn() ?: 0;
        $totalCreditsSpent += $responseRevisionCredits;
    } else {
        // Ana dosya ise, bu ana dosya ile ilgili tüm harcamaları al
        
        // 1. Ana dosya için yanıt dosyalarında harcanan krediler
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM file_responses WHERE upload_id = ?");
        $stmt->execute([$fileId]);
        $responseCredits = $stmt->fetchColumn() ?: 0;
        $totalCreditsSpent += $responseCredits;
        
        // 2. Ana dosya için revizyon talepleri ve cevaplarında harcanan krediler
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM revisions WHERE upload_id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        $revisionCredits = $stmt->fetchColumn() ?: 0;
        $totalCreditsSpent += $revisionCredits;
        
        // 3. Ana dosya ile ilgili yanıt dosyalarının revizyon talepleri için harcanan krediler
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(r.credits_charged), 0) as total_credits 
            FROM revisions r 
            INNER JOIN file_responses fr ON r.response_id = fr.id 
            WHERE fr.upload_id = ? AND r.user_id = ?
        ");
        $stmt->execute([$fileId, $userId]);
        $responseRevisionCredits = $stmt->fetchColumn() ?: 0;
        $totalCreditsSpent += $responseRevisionCredits;
    }
} catch(PDOException $e) {
    error_log('Total credits calculation error: ' . $e->getMessage());
    $totalCreditsSpent = 0;
}

$pageTitle = $fileDetail['original_name'] ?? 'Dosya Detayı';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Breadcrumb ve Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="files.php">Dosyalarım</a></li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo $fileType === 'response' ? 'Yanıt Dosyası' : 'Ana Dosya'; ?> Detayı
                            </li>
                        </ol>
                    </nav>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-<?php echo $fileType === 'response' ? 'reply' : 'file-alt'; ?> me-2 text-primary"></i>
                        <?php echo htmlspecialchars($fileDetail['original_name'] ?? 'Bilinmeyen dosya'); ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php echo $fileType === 'response' ? 'Yanıt dosyası detayları ve bilgileri' : 'Ana dosya detayları, yanıt dosyaları ve revize talepleri'; ?>
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="files.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Geri Dön
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

            <!-- Ana Dosya Detayları -->
            <div class="detail-card main-file-card mb-4">
                <div class="detail-card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php echo $fileType === 'response' ? 'reply' : 'file-alt'; ?> me-2"></i>
                        <?php echo $fileType === 'response' ? 'Yanıt Dosyası Bilgileri' : 'Ana Dosya Bilgileri'; ?>
                    </h5>
                    <div class="file-status">
                        <?php
                        if ($fileType === 'response') {
                            $config = ['class' => 'success', 'text' => 'Yanıt Dosyası', 'icon' => 'reply'];
                        } else {
                            $statusConfig = [
                                'pending' => ['class' => 'warning', 'text' => 'Bekliyor', 'icon' => 'clock'],
                                'processing' => ['class' => 'info', 'text' => 'İşleniyor', 'icon' => 'cogs'],
                                'completed' => ['class' => 'success', 'text' => 'Tamamlandı', 'icon' => 'check-circle'],
                                'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times-circle']
                            ];
                            $config = $statusConfig[$fileDetail['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                        }
                        ?>
                        <span class="badge bg-<?php echo $config['class']; ?> status-badge">
                            <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                            <?php echo $config['text']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-info-circle me-2"></i>Dosya Bilgileri
                            </h6>
                            <div class="detail-list">
                                <div class="detail-item">
                                    <span class="label">Dosya ID:</span>
                                    <span class="value font-monospace"><?php echo $fileDetail['id']; ?></span>
                                </div>
                            <?php if ($totalCreditsSpent > 0): ?>
                                <div class="detail-item total-credits-item">
                                    <span class="label">
                                        <i class="fas fa-coins text-warning me-1"></i>
                                        <?php echo $fileType === 'response' ? 'Bu Dosya için Toplam Harcama:' : 'Bu Dosya için Toplam Harcama:'; ?>
                                    </span>
                                    <span class="value total-credits-value">
                                        <?php echo number_format($totalCreditsSpent, 2); ?> kredi
                                        <?php if ($totalCreditsSpent != $fileDetail['credits_charged']): ?>
                                            <small class="text-muted d-block">
                                                <?php 
                                                if ($fileType === 'response') {
                                                    echo '(Dosya + revizyon talepleri)';
                                                } else {
                                                    echo '(Yanıt + revizyon talepleri)';
                                                }
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                                <div class="detail-item">
                                    <span class="label">Orijinal Ad:</span>
                                    <span class="value"><?php echo htmlspecialchars($fileDetail['original_name'] ?? 'Belirtilmemiş'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Cihaz Tipi:</span>
                                    <span class="value"><?php echo htmlspecialchars($fileDetail['device_name'] ?? 'Belirtilmemiş'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Dosya Boyutu:</span>
                                    <span class="value"><?php echo formatFileSize($fileDetail['file_size'] ?? 0); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label"><?php echo $fileType === 'response' ? 'Oluşturulma' : 'Yüklenme'; ?> Tarihi:</span>
                                    <span class="value"><?php echo date('d.m.Y H:i', strtotime($fileDetail['upload_date'])); ?></span>
                                </div>
                                <?php if (!empty($fileDetail['processed_date']) && $fileType !== 'response'): ?>
                                    <div class="detail-item">
                                        <span class="label">Tamamlanma Tarihi:</span>
                                        <span class="value"><?php echo date('d.m.Y H:i', strtotime($fileDetail['processed_date'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($fileType === 'response' && !empty($fileDetail['admin_username'])): ?>
                                    <div class="detail-item">
                                        <span class="label">Oluşturan Admin:</span>
                                        <span class="value"><?php echo htmlspecialchars($fileDetail['admin_username']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (($fileType === 'response') || ($fileType === 'upload' && $fileDetail['status'] === 'completed')): ?>
                                    <a href="download.php?id=<?php echo $fileDetail['id']; ?>&type=<?php echo $fileType; ?>" class="btn btn-success">
                                        <i class="fas fa-download me-1"></i>İndir
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-car me-2"></i>Araç Bilgileri
                            </h6>
                            <div class="detail-list">
                                <div class="detail-item">
                                    <span class="label">Marka:</span>
                                    <span class="value"><?php echo htmlspecialchars($fileDetail['brand_name'] ?? 'Belirtilmemiş'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Model:</span>
                                    <span class="value"><?php echo htmlspecialchars($fileDetail['model_name'] ?? 'Belirtilmemiş'); ?></span>
                                </div>
                                <?php if (!empty($fileDetail['series_name'])): ?>
                                    <div class="detail-item">
                                        <span class="label">Seri:</span>
                                        <span class="value"><?php echo $fileDetail['series_name']; ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($fileDetail['engine_name'])): ?>
                                    <div class="detail-item">
                                        <span class="label">Motor:</span>
                                        <span class="value"><?php echo htmlspecialchars($fileDetail['engine_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($fileDetail['plate'])): ?>
                                    <div class="detail-item">
                                        <span class="label">Plaka:</span>
                                        <span class="value"><?php echo strtoupper(htmlspecialchars($fileDetail['plate'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($fileDetail['ecu_name'])): ?>
                                    <div class="detail-item">
                                        <span class="label">ECU:</span>
                                        <span class="value"><?php echo htmlspecialchars($fileDetail['ecu_name']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($fileDetail['fuel_type'])): ?>
                                    <div class="detail-item">
                                        <span class="label">Yakıt Tipi:</span>
                                        <span class="value"><?php echo htmlspecialchars($fileDetail['fuel_type']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($fileDetail['hp_power'])): ?>
                                    <div class="detail-item">
                                        <span class="label">Güç:</span>
                                        <span class="value"><?php echo $fileDetail['hp_power']; ?> HP</span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($fileDetail['nm_torque'])): ?>
                                    <div class="detail-item">
                                        <span class="label">Tork:</span>
                                        <span class="value"><?php echo $fileDetail['nm_torque']; ?> Nm</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notlar -->
                    <?php if ($fileType === 'response'): ?>
                        <!-- Yanıt dosyası için orijinal dosya notları -->
                        <?php if (!empty($fileDetail['original_notes'])): ?>
                            <div class="mt-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-comment me-2"></i>Orijinal Dosya Notları
                                </h6>
                                <div class="notes-content">
                                    <?php echo nl2br(htmlspecialchars($fileDetail['original_notes'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Admin notları (yanıt dosyası için) -->
                        <?php if (!empty($fileDetail['admin_notes'])): ?>
                            <div class="mt-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-user-cog me-2"></i>Admin Notları
                                </h6>
                                <div class="admin-notes-content">
                                    <?php echo displayAdminNotes($fileDetail['admin_notes']); ?>
                                </div>
                            </div>
                        <?php endif; ?>       
                    <?php endif; ?>
                    
                    <!-- İşlem Butonları -->
                    <!-- <div class="mt-4 text-center">
                        <?php if ($fileType === 'response' || ($fileType === 'upload' && $fileDetail['status'] === 'completed')): ?>
                            <button type="button" class="btn btn-warning me-2" 
                                    onclick="requestRevision('<?php echo $fileDetail['id']; ?>', '<?php echo $fileType; ?>')">
                                <i class="fas fa-redo me-2"></i>Revize Talep Et
                            </button>
                        <?php endif; ?>
                    </div> -->
                </div>
            </div>

            <?php if ($fileType === 'upload'): ?>
                <!-- Yanıt Dosyaları -->
                <?php if (!empty($responses)): ?>
                    <div class="detail-card mb-4">
                        <div class="detail-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-reply me-2"></i>Yanıt Dosyaları (<?php echo count($responses); ?>)
                            </h5>
                        </div>
                        <div class="detail-card-body">
                            <div class="response-files-list">
                                <?php foreach ($responses as $response): ?>
                                    <div class="response-file-item">
                                        <div class="file-icon">
                                            <i class="fas fa-reply text-success"></i>
                                        </div>
                                        <div class="file-info">
                                            <h6 class="file-name">
                                                <?php echo htmlspecialchars($response['original_name']); ?>
                                            </h6>
                                            <div class="file-meta">
                                                <span class="meta-item">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d.m.Y H:i', strtotime($response['upload_date'])); ?>
                                                </span>
                                                <span class="meta-item">
                                                    <i class="fas fa-hdd me-1"></i>
                                                    <?php echo formatFileSize($response['file_size']); ?>
                                                </span>
                                                <?php if (!empty($response['admin_username'])): ?>
                                                    <span class="meta-item">
                                                        <i class="fas fa-user-cog me-1"></i>
                                                        <?php echo htmlspecialchars($response['admin_username']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($response['admin_notes'])): ?>
                                                <div class="file-notes mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-comment-dots me-1"></i>
                                                        <?php 
                                                        if (filterAdminNotes($response['admin_notes'])) {
                                                            echo htmlspecialchars(substr($response['admin_notes'], 0, 100)) . (strlen($response['admin_notes']) > 100 ? '...' : '');
                                                        } else {
                                                            echo 'Admin notu mevcut';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="file-actions">
                                            <a href="file-detail.php?id=<?php echo $response['id']; ?>&type=response" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>Detay
                                            </a>
                                            <a href="download.php?id=<?php echo $response['id']; ?>&type=response" class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-1"></i>İndir
                                            </a>
                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                    onclick="requestRevision('<?php echo $response['id']; ?>', 'response')">
                                                <i class="fas fa-redo me-1"></i>Revize
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Revize Dosyaları -->
                <?php if (!empty($allRevisionFiles)): ?>
                    <div class="detail-card mb-4">
                        <div class="detail-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-edit me-2"></i>Revize Dosyaları (<?php echo count($allRevisionFiles); ?>)
                            </h5>
                        </div>
                        <div class="detail-card-body">
                            <div class="revision-files-list">
                                <?php foreach ($allRevisionFiles as $revFile): ?>
                                    <div class="revision-file-item">
                                        <div class="file-icon">
                                            <i class="fas fa-edit text-warning"></i>
                                        </div>
                                        <div class="file-info">
                                            <h6 class="file-name">
                                                <?php echo htmlspecialchars($revFile['original_name']); ?>
                                            </h6>
                                            <div class="file-meta">
                                                <span class="meta-item">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d.m.Y H:i', strtotime($revFile['upload_date'])); ?>
                                                </span>
                                                <span class="meta-item">
                                                    <i class="fas fa-hdd me-1"></i>
                                                    <?php echo formatFileSize($revFile['file_size']); ?>
                                                </span>
                                                <?php if (!empty($revFile['admin_username'])): ?>
                                                    <span class="meta-item">
                                                        <i class="fas fa-user-cog me-1"></i>
                                                        <?php echo htmlspecialchars($revFile['admin_username']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-hashtag me-1"></i>
                                                    Revize #<?php echo substr($revFile['revision_id'], 0, 8); ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($revFile['revision_notes'])): ?>
                                                <div class="file-notes mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-comment-dots me-1"></i>
                                                        <strong>Talep:</strong> <?php echo htmlspecialchars(substr($revFile['revision_notes'], 0, 100)) . (strlen($revFile['revision_notes']) > 100 ? '...' : ''); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($revFile['admin_notes'])): ?>
                                                <div class="file-notes mt-1">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-shield me-1"></i>
                                                        <strong>Admin Notu:</strong> <?php echo htmlspecialchars(substr($revFile['admin_notes'], 0, 100)) . (strlen($revFile['admin_notes']) > 100 ? '...' : ''); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="file-actions">
                                            <a href="revision-detail.php?id=<?php echo $revFile['revision_id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>Detay
                                            </a>
                                            <a href="download-revision.php?id=<?php echo $revFile['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-download me-1"></i>İndir
                                            </a>
                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                    onclick="requestRevision('<?php echo $revFile['id']; ?>', 'revision')">
                                                <i class="fas fa-redo me-1"></i>Yeniden Revize
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- İletişim Geçmişi (Tüm Kullanıcı Admin Etkileşimleri) -->
                <?php if (!empty($communicationHistory)): ?>
                    <div class="detail-card mb-4">
                        <div class="detail-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-comments me-2 text-primary"></i>İletişim Geçmişi (<?php echo count($communicationHistory); ?>)
                            </h5>
                            <span class="badge bg-primary">
                                Ben ↔ Admin Konuşmalarım
                            </span>
                        </div>
                        <div class="detail-card-body">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Bu dosya ile ilgili tüm konuşmalarım:</strong> 
                                Yükleme notlarım, admin yanıtları, revize taleplerim ve cevapları kronolojik sırada.
                            </div>
                            
                            <div class="communication-timeline">
                                <?php foreach ($communicationHistory as $index => $comm): ?>
                                    <div class="timeline-item communication-item <?php echo $comm['status']; ?>">
                                        <div class="timeline-marker">
                                            <?php 
                                            $typeConfig = [
                                                'user_upload' => ['icon' => 'fas fa-upload text-primary', 'color' => 'primary'],
                                                'admin_response' => ['icon' => 'fas fa-reply text-success', 'color' => 'success'],
                                                'user_revision_request' => ['icon' => 'fas fa-edit text-warning', 'color' => 'warning'],
                                                'admin_revision_response' => ['icon' => 'fas fa-user-shield text-info', 'color' => 'info']
                                            ];
                                            $config = $typeConfig[$comm['type']] ?? ['icon' => 'fas fa-comment text-secondary', 'color' => 'secondary'];
                                            ?>
                                            <i class="<?php echo $config['icon']; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <?php if ($comm['type'] === 'user_upload'): ?>
                                                            <span class="badge bg-primary">
                                                                <i class="fas fa-user me-1"></i>Dosya Yükleme Notum
                                                            </span>
                                                        <?php elseif ($comm['type'] === 'admin_response'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-user-shield me-1"></i>Admin'in Yanıt Dosyası Notu
                                                            </span>
                                                        <?php elseif ($comm['type'] === 'user_revision_request'): ?>
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-edit me-1"></i>Revize Talebim
                                                            </span>
                                                        <?php elseif ($comm['type'] === 'admin_revision_response'): ?>
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-reply me-1"></i>Admin'in Cevabı
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (isset($comm['file_name'])): ?>
                                                            <span class="badge bg-secondary ms-2">
                                                                <i class="fas fa-file me-1"></i><?php echo htmlspecialchars(substr($comm['file_name'], 0, 20)) . (strlen($comm['file_name']) > 20 ? '...' : ''); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo date('d.m.Y H:i', strtotime($comm['date'])); ?>
                                                        
                                                        <?php if (isset($comm['admin_username']) && !empty($comm['admin_username'])): ?>
                                                            <span class="ms-2">
                                                                <i class="fas fa-user-shield me-1"></i>
                                                                Admin: <?php echo htmlspecialchars($comm['admin_username']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <!-- Hangi dosya için revize talebi olduğunu belirt -->
                                            <?php if (!empty($comm['response_file_name'])): ?>
                                            <div class="mb-3">
                                            <div class="file-reference">
                                            <i class="fas fa-arrow-right text-primary me-2"></i>
                                            <strong>Revize ettiğim dosya:</strong> 
                                            <span class="text-primary"><?php echo htmlspecialchars($comm['response_file_name']); ?></span>
                                                <small class="text-muted ms-2">(Yanıt Dosyası)</small>
                                                </div>
                                                </div>
                        <?php elseif ($comm['type'] === 'user_revision_request'): ?>
                            <?php
                            // Eğer bu revize talebinden önce başka revizyon dosyaları varsa, son revizyon dosyasını bul
                            $targetFileName = 'Ana Dosya';
                            $targetFileType = 'Orijinal Yüklenen Dosya';
                            
                            if (isset($comm['revision_id'])) {
                                try {
                                    // Bu revize talebinden önce tamamlanmış revize taleplerini bul
                                    $stmt = $pdo->prepare("
                                        SELECT rf.original_name 
                                        FROM revisions r1
                                        JOIN revision_files rf ON r1.id = rf.revision_id
                                        WHERE r1.upload_id = ? 
                                        AND r1.status = 'completed'
                                        AND r1.requested_at < (
                                            SELECT r2.requested_at 
                                            FROM revisions r2 
                                            WHERE r2.id = ?
                                        )
                                        ORDER BY r1.requested_at DESC 
                                        LIMIT 1
                                    ");
                                    $stmt->execute([$originalUploadId, $comm['revision_id']]);
                                    $previousRevisionFile = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($previousRevisionFile) {
                                        $targetFileName = $previousRevisionFile['original_name'];
                                        $targetFileType = 'Revizyon Dosyası';
                                    }
                                } catch (Exception $e) {
                                    error_log('Previous revision file query error: ' . $e->getMessage());
                                }
                            }
                            ?>
                            <div class="mb-3">
                                <div class="file-reference">
                                    <?php if ($targetFileType === 'Revizyon Dosyası'): ?>
                                        <i class="fas fa-arrow-right text-warning me-2"></i>
                                        <strong>Revize talep ettiğim dosya:</strong> 
                                        <span class="text-warning"><?php echo htmlspecialchars($targetFileName); ?></span>
                                        <small class="text-muted ms-2">(<?php echo $targetFileType; ?>)</small>
                                    <?php else: ?>
                                        <i class="fas fa-arrow-right text-success me-2"></i>
                                        <strong>Revize talep ettiğim dosya:</strong> 
                                        <span class="text-success"><?php echo $targetFileName; ?></span>
                                        <small class="text-muted ms-2">(<?php echo $targetFileType; ?>)</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                                            
                                            <!-- Kullanıcı Notları -->
                                            <?php if (!empty($comm['user_notes'])): ?>
                                                <div class="revision-note user-note mb-3">
                                                    <div class="note-header">
                                                        <i class="fas fa-user me-2 text-primary"></i>
                                                        <strong>
                                                            <?php if ($comm['type'] === 'user_upload'): ?>
                                                                Yükleme sırasında yazdığım notlar:
                                                            <?php else: ?>
                                                                Revize talebim:
                                                            <?php endif; ?>
                                                        </strong>
                                                    </div>
                                                    <div class="note-content">
                                                        <?php echo nl2br(htmlspecialchars($comm['user_notes'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Admin Cevabı -->
                                            <?php if (!empty($comm['admin_notes'])): ?>
                                                <div class="revision-note admin-note mb-2">
                                                    <div class="note-header">
                                                        <i class="fas fa-user-shield me-2 text-success"></i>
                                                        <strong>
                                                            <?php if ($comm['type'] === 'admin_response'): ?>
                                                                Admin'in yanıt dosyası notları:
                                                            <?php else: ?>
                                                                Admin'in cevabı:
                                                            <?php endif; ?>
                                                        </strong>
                                                    </div>
                                                    <div class="note-content">
                                                        <?php echo nl2br(htmlspecialchars($comm['admin_notes'])); ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Admin'in yüklediği revizyon dosyaları -->
                                                <?php if (!empty($comm['revision_files'])): ?>
                                                    <div class="admin-files mt-3">
                                                        <h6 class="text-success mb-2">
                                                            <i class="fas fa-file-download me-2"></i>
                                                            Admin'in Yükledıği Revizyon Dosyaları:
                                                        </h6>
                                                        <div class="admin-files-list">
                                                            <?php foreach ($comm['revision_files'] as $revFile): ?>
                                                                <div class="admin-file-item">
                                                                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                                                                        <div class="file-info">
                                                                            <i class="fas fa-file-download text-success me-2"></i>
                                                                            <strong><?php echo htmlspecialchars($revFile['original_name']); ?></strong>
                                                                            <span class="text-muted ms-2">
                                                                                (<?php echo formatFileSize($revFile['file_size']); ?>)
                                                                            </span>
                                                                        </div>
                                                                        <div class="d-flex gap-2">
                                                                            <a href="revision-detail.php?id=<?php echo $revFile['revision_id']; ?>" 
                                                                               class="btn btn-outline-primary btn-sm">
                                                                                <i class="fas fa-eye me-1"></i>Detay
                                                                            </a>
                                                                        <a href="download-revision.php?id=<?php echo $revFile['id']; ?>" 
                                                                           class="btn btn-success btn-sm">
                                                                            <i class="fas fa-download me-1"></i>İndir
                                                                        </a>
                                                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                                                onclick="requestRevision('<?php echo $revFile['id']; ?>', 'revision')">
                                                                            <i class="fas fa-redo me-1"></i>Revize Talep Et
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Admin'in yüklediği response dosyaları -->
                                                <?php if ($comm['type'] === 'admin_response' && !empty($comm['response_id'])): ?>
                                                    <div class="admin-files mt-3">
                                                        <h6 class="text-success mb-2">
                                                            <i class="fas fa-file-download me-2"></i>
                                                            Admin'in Yüklediği Yanıt Dosyası:
                                                        </h6>
                                                        <div class="admin-files-list">
                                                            <div class="admin-file-item">
                                                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                                                                    <div class="file-info">
                                                                        <i class="fas fa-file-download text-success me-2"></i>
                                                                        <strong><?php echo htmlspecialchars($comm['file_name']); ?></strong>
                                                                        <span class="text-muted ms-2">
                                                                            (Yanıt Dosyası)
                                                                        </span>
                                                                    </div>
                                                                    <div class="d-flex gap-2">
                                                                        <a href="file-detail.php?id=<?php echo $comm['response_id']; ?>&type=response" 
                                                                           class="btn btn-outline-primary btn-sm">
                                                                            <i class="fas fa-eye me-1"></i>Detay
                                                                        </a>
                                                                        <a href="download.php?id=<?php echo $comm['response_id']; ?>&type=response" 
                                                                           class="btn btn-success btn-sm">
                                                                            <i class="fas fa-download me-1"></i>İndir
                                                                        </a>
                                                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                                                onclick="requestRevision('<?php echo $comm['response_id']; ?>', 'response')">
                                                                            <i class="fas fa-redo me-1"></i>Revize Talep Et
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($comm['type'] === 'user_revision_request' && $comm['status'] === 'pending'): ?>
                                                    <div class="revision-note admin-note mb-2 pending-response">
                                                        <div class="note-header">
                                                            <i class="fas fa-hourglass-half me-2 text-muted"></i>
                                                            <strong>Admin Cevabı:</strong>
                                                        </div>
                                                        <div class="note-content">
                                                            <em class="text-muted">
                                                                Talebiniz inceleniyor, lütfen bekleyin...
                                                            </em>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <!-- Ek Bilgiler -->
                                            <div class="communication-meta">
                                                <?php if (isset($comm['credits_charged']) && $comm['credits_charged'] > 0): ?>
                                                    <span class="meta-item text-warning">
                                                        <i class="fas fa-coins me-1"></i>
                                                        <?php echo $comm['credits_charged']; ?> kredi düşürüldü
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($comm['response_id'])): ?>
                                                    <a href="file-detail.php?id=<?php echo $comm['response_id']; ?>&type=response" 
                                                       class="meta-item text-primary" style="text-decoration: none;">
                                                        <i class="fas fa-external-link-alt me-1"></i>
                                                        Dosyayı Görüntüle
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($comm['revision_id'])): ?>
                                                    <a href="revision-detail.php?id=<?php echo $comm['revision_id']; ?>" 
                                                       class="meta-item text-info" style="text-decoration: none;">
                                                        <i class="fas fa-history me-1"></i>
                                                        Revizyon Detayları
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($index < count($communicationHistory) - 1): ?>
                                        <div class="timeline-divider"></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            
                        </div>
                        <!-- İletişim Özeti -->
                            <div class="communication-summary mt-4 p-3 bg-light rounded">
                                <h6 class="mb-2">
                                    <i class="fas fa-chart-line me-2 text-info"></i>İletişim Özeti
                                </h6>
                                <div class="row text-center">
                                    <?php 
                                    $typeCounts = array_count_values(array_column($communicationHistory, 'type'));
                                    ?>
                                    <div class="col-3">
                                        <span class="badge bg-primary fs-6"><?php echo $typeCounts['user_upload'] ?? 0; ?></span>
                                        <br><small>Yükleme Notum</small>
                                    </div>
                                    <div class="col-3">
                                        <span class="badge bg-success fs-6"><?php echo $typeCounts['admin_response'] ?? 0; ?></span>
                                        <br><small>Admin Yanıt</small>
                                    </div>
                                    <div class="col-3">
                                        <span class="badge bg-warning fs-6"><?php echo $typeCounts['user_revision_request'] ?? 0; ?></span>
                                        <br><small>Revize Talebim</small>
                                    </div>
                                    <div class="col-3">
                                        <span class="badge bg-info fs-6"><?php echo $typeCounts['admin_revision_response'] ?? 0; ?></span>
                                        <br><small>Admin Cevap</small>
                                    </div>
                                </div>
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
                    
                    <div class="alert-info" style="padding: 1rem; display: flex; align-items: center; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border: 1px solid #81c784;
    border-radius: 12px;">
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
/* Detail Page Styles */
.detail-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    overflow: hidden;
}

/* Toplam Kredi Gösterimi */
.total-credits-item {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 2px solid #f39c12;
    border-radius: 12px;
    padding: 0.75rem 1rem !important;
    margin: 0.5rem 0;
}

.total-credits-item .label {
    font-weight: 700;
    color: #d68910;
    display: flex;
    align-items: center;
}

.total-credits-value {
    font-weight: 700;
    color: #b7950b;
    font-size: 1.1rem;
}

.total-credits-value small {
    font-weight: 500;
    font-size: 0.8rem;
    color: #85651d;
    margin-top: 0.25rem;
}

.detail-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-card-header h5 {
    margin: 0;
    font-weight: 600;
    color: #495057;
}

.detail-card-body {
    padding: 2rem;
}

.main-file-card .detail-card-header {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    color: white;
}

.main-file-card .detail-card-header h5 {
    color: white;
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
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    font-weight: 500;
    color: #6c757d;
    min-width: 150px;
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
    border-radius: 12px;
    padding: 1.5rem;
    border-left: 4px solid #667eea;
    line-height: 1.6;
    color: #495057;
}

.admin-notes-content {
    border-left-color: #28a745;
    background: #f8fff9;
}

.status-badge {
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 8px;
}

/* Response Files List */
.response-files-list, .revision-files-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.response-file-item, .revision-file-item {
    background: #f8fff9;
    border: 1px solid #d4edda;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: all 0.3s ease;
}

.revision-file-item {
    background: #fff3cd;
    border-color: #ffeaa7;
}

.response-file-item:hover, .revision-file-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
}

.revision-file-item:hover {
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.15);
}

.response-file-item .file-icon, .revision-file-item .file-icon {
    width: 48px;
    height: 48px;
    background: #28a745;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.revision-file-item .file-icon {
    background: #ffe9a7;
    color: #212529;
}

.response-file-item .file-info, .revision-file-item .file-info {
    flex: 1;
}

.response-file-item .file-name, .revision-file-item .file-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.file-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.meta-item {
    font-size: 0.85rem;
    color: #6c757d;
    display: flex;
    align-items: center;
}

.file-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.file-actions .btn {
    border-radius: 8px;
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
}

.communication-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    margin-top: 0.75rem;
}

.communication-meta .meta-item {
    display: flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: rgba(0,0,0,0.05);
    border-radius: 0.25rem;
    text-decoration: none;
}

.communication-meta .meta-item:hover {
    background: rgba(0,0,0,0.1);
}

/* Admin Files Styles */
.admin-files {
    border: 1px solid #c3e6cb;
    border-radius: 0.5rem;
    padding: 1rem;
    background: #f8fff9;
}

.admin-files h6 {
    margin-bottom: 1rem;
    color: #198754;
    font-weight: 600;
}

.admin-files-list {
    margin: 0;
}

.admin-file-item {
    margin-bottom: 0.5rem;
}

.admin-file-item:last-child {
    margin-bottom: 0;
}

.admin-file-item .file-info {
    flex: 1;
}

.admin-file-item .file-info strong {
    color: #198754;
    font-weight: 600;
}

.admin-file-item .btn {
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

/* User Side Timeline Styles - Admin Communications */
.communication-timeline {
    position: relative;
    padding: 0;
}

.communication-timeline .timeline-item {
    position: relative;
    display: block;
    margin-bottom: 2rem;
    width: 100%;
    clear: both;
}

.communication-timeline .timeline-marker {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 3px solid #e9ecef;
    border-radius: 50%;
    float: left;
    margin-right: 1rem;
    z-index: 2;
    position: relative;
}

.communication-timeline .timeline-item.pending .timeline-marker {
    border-color: #ffc107;
    background: #fff3cd;
}

.communication-timeline .timeline-item.in_progress .timeline-marker {
    border-color: #0dcaf0;
    background: #cff4fc;
}

.communication-timeline .timeline-item.completed .timeline-marker {
    border-color: #198754;
    background: #d1e7dd;
}

.communication-timeline .timeline-item.rejected .timeline-marker {
    border-color: #dc3545;
    background: #f8d7da;
}

.communication-timeline .timeline-content {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1.25rem;
    position: relative;
    margin-left: 56px;
    overflow: hidden;
}

.communication-timeline .timeline-content::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 20px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #e9ecef transparent transparent;
}

.communication-timeline .timeline-content::after {
    content: '';
    position: absolute;
    left: -7px;
    top: 20px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #f8f9fa transparent transparent;
}

.communication-timeline .timeline-divider {
    width: 2px;
    height: 2rem;
    background: #e9ecef;
    margin-left: 19px;
    margin-top: -1rem;
    margin-bottom: -1rem;
}

.communication-timeline .revision-note {
    border-radius: 0.375rem;
    overflow: hidden;
    margin-bottom: 1rem;
}

.communication-timeline .revision-note.user-note {
    background: #e7f3ff;
    border: 1px solid #b8daff;
}

.communication-timeline .revision-note.admin-note {
    background: #d4edda;
    border: 1px solid #c3e6cb;
}

.communication-timeline .revision-note.pending-response {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
}

.communication-timeline .revision-note .note-header {
    background: rgba(0,0,0,0.05);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    font-weight: 600;
}

.communication-timeline .revision-note .note-content {
    padding: 0.75rem;
    white-space: normal;
    word-wrap: break-word;
    line-height: 1.6;
}

.communication-timeline .user-note .note-header {
    background: rgba(13, 110, 253, 0.1);
}

.communication-timeline .admin-note .note-header {
    background: rgba(25, 135, 84, 0.1);
}

.communication-timeline .pending-response .note-header {
    background: rgba(255, 193, 7, 0.1);
}

.file-reference {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    font-size: 0.9rem;
}

.communication-summary {
    border: 1px solid #dee2e6;
}

.communication-summary .badge {
    min-width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .communication-timeline .timeline-marker {
        float: none;
        display: block;
        margin: 0 auto 0.5rem auto;
    }
    
    .communication-timeline .timeline-content {
        margin-left: 0;
    }
    
    .communication-timeline .timeline-content::before,
    .communication-timeline .timeline-content::after {
        display: none;
    }
    
    .communication-timeline .timeline-divider {
        display: none;
    }
}

.breadcrumb-item a {
    color: #007bff;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

/* Alert Modern */
.alert-modern {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Form Controls */
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

/* Responsive */
@media (max-width: 767.98px) {
    .detail-card-body {
        padding: 1.5rem;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .detail-item .value {
        text-align: left;
    }
    
    .response-file-item, .revision-file-item {
        flex-direction: column;
        text-align: center;
    }
    
    .response-file-item .file-info, .revision-file-item .file-info {
        text-align: left;
    }
    
    .file-actions {
        justify-content: center;
        width: 100%;
    }
    
    .file-actions .btn {
        flex: 1;
    }
    
    .file-meta {
        justify-content: center;
    }
}
</style>

<script>
// DOM yüklendikten sonra çalıştır
document.addEventListener('DOMContentLoaded', function() {
    // Request Revision Function
    window.requestRevision = function(fileId, fileType = 'upload') {
        try {
            // Element kontrolü yap
            const revisionFileIdElement = document.getElementById('revisionFileId');
            const revisionFileTypeElement = document.getElementById('revisionFileType');
            const revisionInfoText = document.getElementById('revisionInfoText');
            const modalTitle = document.querySelector('#revisionModal .modal-title');
            const revisionNotesElement = document.getElementById('revision_notes');
            const revisionModal = document.getElementById('revisionModal');
            
            // Elementlerin varlığını kontrol et
            if (!revisionFileIdElement) {
                console.error('revisionFileId elementi bulunamadı!');
                alert('Modal yüklenirken hata oluştu. Lütfen sayfayı yenileyin.');
                return;
            }
            
            if (!revisionFileTypeElement) {
                console.error('revisionFileType elementi bulunamadı!');
                alert('Modal yüklenirken hata oluştu. Lütfen sayfayı yenileyin.');
                return;
            }
            
            if (!revisionInfoText) {
                console.error('revisionInfoText elementi bulunamadı!');
                alert('Modal yüklenirken hata oluştu. Lütfen sayfayı yenileyin.');
                return;
            }
            
            if (!modalTitle) {
                console.error('modal title elementi bulunamadı!');
                alert('Modal yüklenirken hata oluştu. Lütfen sayfayı yenileyin.');
                return;
            }
            
            if (!revisionNotesElement) {
                console.error('revision_notes elementi bulunamadı!');
                alert('Modal yüklenirken hata oluştu. Lütfen sayfayı yenileyin.');
                return;
            }
            
            if (!revisionModal) {
                console.error('revisionModal elementi bulunamadı!');
                alert('Modal yüklenirken hata oluştu. Lütfen sayfayı yenileyin.');
                return;
            }
            
            // Elementler mevcut, değerleri set et
            revisionFileIdElement.value = fileId;
            revisionFileTypeElement.value = fileType;
            
            // Modal içeriğini dosya tipine göre ayarla
            if (fileType === 'response') {
                modalTitle.innerHTML = '<i class="fas fa-redo me-2 text-warning"></i>Yanıt Dosyası Revize Talebi';
                revisionInfoText.innerHTML = 'Yanıt dosyasında bir değişiklik veya düzenleme istiyorsanız bu formu kullanabilirsiniz. Admin ekibimiz dosyanızı yeniden gözden geçirecek ve geliştirilmiş bir sürüm hazırlayacaktır.';
                revisionNotesElement.placeholder = 'Yanıt dosyasında hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: "Daha fazla güç istiyorum", "Yakıt tüketimi daha iyi olsun", "Torku artmalı" gibi...';
            } else if (fileType === 'revision') {
                modalTitle.innerHTML = '<i class="fas fa-redo me-2 text-warning"></i>Revize Dosyası İçin Yeni Revize Talebi';
                revisionInfoText.innerHTML = 'Mevcut revize dosyasında ek değişiklikler istiyorsanız bu formu kullanabilirsiniz. Admin ekibimiz dosyanızı tekrar gözden geçirecek ve istekleriniz doğrultusunda düzenleyecektir.';
                revisionNotesElement.placeholder = 'Revize dosyasında hangi ek değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: "Daha fazla performans", "Farklı ayarlar", "Ek özellikler" gibi...';
            } else {
                modalTitle.innerHTML = '<i class="fas fa-redo me-2 text-warning"></i>Revize Talebi';
                revisionInfoText.innerHTML = 'Dosyanızda bir değişiklik veya düzenleme istiyorsanız bu formu kullanabilirsiniz. Talep incelendikten sonra size geri dönüş yapılacaktır.';
                revisionNotesElement.placeholder = 'Lütfen dosyada hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: "Güç artırımı", "EGR kapatma", "DPF silme" gibi...';
            }
            
            // Formu temizle
            revisionNotesElement.value = '';
            
            // Modal'ı göster
            const modal = new bootstrap.Modal(revisionModal);
            modal.show();
            
        } catch (error) {
            console.error('requestRevision fonksiyonunda hata:', error);
            alert('Revize talebi modalı açılırken bir hata oluştu: ' + error.message);
        }
    }
});
</script>

<!-- Chat Penceresi -->
<div class="chat-container" id="chatContainer">
    <div class="chat-header">
        <h5 class="mb-0">
            <i class="fas fa-comments me-2"></i>Dosya Hakkında Konuş
        </h5>
        <span class="badge bg-danger" id="unreadCount" style="display: none;">0</span>
    </div>
    <div class="chat-messages" id="chatMessages">
        <!-- Mesajlar buraya gelecek -->
        <div class="text-center text-muted py-4">
            <i class="fas fa-spinner fa-spin me-2"></i>Mesajlar yükleniyor...
        </div>
    </div>
    <div class="chat-input">
        <form id="chatForm">
            <div class="input-group">
                <input type="text" class="form-control" id="chatMessage" placeholder="Mesajınızı yazın..." required>
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Chat Styles -->
<style>
.chat-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    border: 1px solid #e0e0e0;
}

.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    background: #f8f9fa;
}

.chat-message {
    margin-bottom: 1rem;
    display: flex;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-message.user {
    justify-content: flex-end;
}

.chat-message.admin {
    justify-content: flex-start;
}

.chat-message-content {
    max-width: 70%;
    padding: 0.75rem;
    border-radius: 12px;
    position: relative;
}

.chat-message.user .chat-message-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-message.admin .chat-message-content {
    background: white;
    border: 1px solid #e0e0e0;
    border-bottom-left-radius: 4px;
}

.chat-message-header {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
    opacity: 0.8;
}

.chat-message.user .chat-message-header {
    text-align: right;
    color: rgba(255,255,255,0.9);
}

.chat-message.admin .chat-message-header {
    color: #6c757d;
}

.chat-message-text {
    word-wrap: break-word;
    line-height: 1.5;
}

.chat-message-time {
    font-size: 0.7rem;
    margin-top: 0.25rem;
    opacity: 0.7;
}

.chat-message.user .chat-message-time {
    text-align: right;
    color: rgba(255,255,255,0.8);
}

.chat-message.admin .chat-message-time {
    color: #6c757d;
}

.chat-input {
    padding: 1rem;
    border-top: 1px solid #e0e0e0;
    background: white;
    border-radius: 0 0 12px 12px;
}

.chat-input .form-control {
    border-radius: 20px;
    border: 1px solid #e0e0e0;
    padding: 0.5rem 1rem;
}

.chat-input .btn {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mobile Responsive */
@media (max-width: 576px) {
    .chat-container {
        width: calc(100% - 20px);
        right: 10px;
        bottom: 10px;
        height: 400px;
    }
}

/* Scroll Styles */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Typing Indicator */
.typing-indicator {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: #e9ecef;
    border-radius: 12px;
    margin-bottom: 0.5rem;
    width: fit-content;
}

.typing-indicator span {
    height: 8px;
    width: 8px;
    background: #6c757d;
    border-radius: 50%;
    display: inline-block;
    margin: 0 2px;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.7;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}
</style>

<!-- Chat JavaScript -->
<script>
// Chat değişkenleri
let lastMessageId = null;
let chatMessages = [];
const fileId = '<?php echo $fileId; ?>';
const fileType = '<?php echo $fileType; ?>';
const currentUserId = '<?php echo $userId; ?>';
const isAdmin = <?php echo json_encode($userRole === 'admin'); ?>;

// Chat fonksiyonları
function formatChatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // saniye farkı
    
    if (diff < 60) return 'Az önce';
    if (diff < 3600) return Math.floor(diff / 60) + ' dk önce';
    if (diff < 86400) return Math.floor(diff / 3600) + ' saat önce';
    
    return date.toLocaleDateString('tr-TR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function renderMessage(message) {
    const isCurrentUser = message.sender_id === currentUserId;
    const messageClass = isCurrentUser ? 'user' : 'admin';
    const senderName = isCurrentUser ? 'Ben' : (message.role === 'admin' ? 'Admin' : message.first_name + ' ' + message.last_name);
    
    return `
        <div class="chat-message ${messageClass}" data-message-id="${message.id}">
            <div class="chat-message-content">
                <div class="chat-message-header">
                    ${senderName}
                </div>
                <div class="chat-message-text">
                    ${escapeHtml(message.message)}
                </div>
                <div class="chat-message-time">
                    ${formatChatTime(message.created_at)}
                </div>
            </div>
        </div>
    `;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function loadMessages() {
    fetch(`../ajax/chat.php?action=get_messages&file_id=${fileId}&file_type=${fileType}${lastMessageId ? '&last_message_id=' + lastMessageId : ''}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                const messagesContainer = document.getElementById('chatMessages');
                
                // İlk yükleme ise temizle
                if (!lastMessageId) {
                    messagesContainer.innerHTML = '';
                }
                
                // Mesajları ekle
                data.messages.forEach(message => {
                    // Mesaj zaten yoksa ekle
                    if (!document.querySelector(`[data-message-id="${message.id}"]`)) {
                        messagesContainer.insertAdjacentHTML('beforeend', renderMessage(message));
                    }
                });
                
                // Son mesaj ID'sini güncelle
                if (data.messages.length > 0) {
                    lastMessageId = data.messages[data.messages.length - 1].id;
                    
                    // Otomatik scroll
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
                
                // Okunmamış sayacını sıfırla
                document.getElementById('unreadCount').style.display = 'none';
                document.getElementById('unreadCount').textContent = '0';
            } else if (!lastMessageId) {
                // Hiç mesaj yoksa
                document.getElementById('chatMessages').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-comment-slash me-2"></i>
                        Henüz mesaj yok. İlk mesajı siz gönderin!
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Mesajlar yüklenirken hata:', error);
            if (!lastMessageId) {
                document.getElementById('chatMessages').innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Mesajlar yüklenemedi.
                    </div>
                `;
            }
        });
}

function sendMessage(message) {
    // Typing göstergesi ekle
    const messagesContainer = document.getElementById('chatMessages');
    const typingHtml = '<div class="typing-indicator" id="typingIndicator"><span></span><span></span><span></span></div>';
    messagesContainer.insertAdjacentHTML('beforeend', typingHtml);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    fetch('../ajax/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=send_message&file_id=${fileId}&file_type=${fileType}&message=${encodeURIComponent(message)}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        // Typing göstergesini kaldır
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
        
        if (data.success && data.data) {
            // Mesajı ekle
            messagesContainer.insertAdjacentHTML('beforeend', renderMessage(data.data));
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            // Son mesaj ID'sini güncelle
            lastMessageId = data.data.id;
            
            // Input'u temizle
            document.getElementById('chatMessage').value = '';
        } else {
            alert('Mesaj gönderilemedi: ' + (data.message || 'Bilinmeyen hata'));
        }
    })
    .catch(error => {
        // Typing göstergesini kaldır
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
        
        console.error('Mesaj gönderme hatası:', error);
        console.error('FileID:', fileId);
        console.error('UserID:', currentUserId);
        console.error('IsAdmin:', isAdmin);
        alert('Mesaj gönderilemedi. Lütfen konsolu kontrol edin.');
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // İlk mesajları yükle
    loadMessages();
    
    // Her 3 saniyede bir yeni mesajları kontrol et
    setInterval(loadMessages, 3000);
    
    // Mesaj gönderme formu
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const messageInput = document.getElementById('chatMessage');
        const message = messageInput.value.trim();
        
        if (message) {
            sendMessage(message);
        }
    });
    
    // Enter tuşu ile gönderme
    document.getElementById('chatMessage').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').dispatchEvent(new Event('submit'));
        }
    });
});
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>
