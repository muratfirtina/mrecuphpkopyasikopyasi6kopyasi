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
$filterId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

// ID parametresi ile direkt dosya filtreleme
if ($filterId && isValidUUID($filterId)) {
    // Bildirimden gelinen dosyayı göster
    $singleFileMode = true;
} else {
    $singleFileMode = false;
}

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;

// Sadece ana dosyaları getir (file_uploads tablosundan)
if ($singleFileMode) {
    // Bildirimden gelen tek dosyayı göster
    $userFiles = $fileManager->getUserUploads($userId, $page, $limit, $status, $search, $filterId);
    $totalFiles = $fileManager->getUserUploadCount($userId, $status, $search, $filterId);
} else {
    // Normal listeleme - Revize durumunu da göz önünde bulundur
    if ($status === 'processing') {
        // Processing filtresi: Basit yaklaşım - her iki tür dosyayı da getir
        try {
            // 1. Normal processing dosyaları getir
            $normalFiles = $fileManager->getUserUploads($userId, $page, $limit, 'processing', $search);
            $normalCount = $fileManager->getUserUploadCount($userId, 'processing', $search);
            
            // 2. Revize işlenen dosyaları kontrol et
            $revisionCheckStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT fu.id) as count, GROUP_CONCAT(fu.original_name) as file_names
                FROM file_uploads fu
                INNER JOIN revisions r ON fu.id = r.upload_id
                WHERE fu.user_id = ? 
                AND fu.status = 'completed' 
                AND r.status = 'in_progress'
                AND r.user_id = ?
            ");
            $revisionCheckStmt->execute([$userId, $userId]);
            $revisionCheck = $revisionCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            // 3. Revize işlenen dosyaları getir (limit dahilinde)
            $revisionFiles = [];
            $revisionCount = 0;
            
            if ($revisionCheck['count'] > 0) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT fu.*, b.name as brand_name, m.name as model_name,
                           r.response_id, r.request_notes, r.requested_at as revision_date, r.id as revision_id
                    FROM file_uploads fu
                    LEFT JOIN brands b ON fu.brand_id = b.id
                    LEFT JOIN models m ON fu.model_id = m.id
                    INNER JOIN revisions r ON fu.id = r.upload_id
                    WHERE fu.user_id = ? 
                    AND fu.status = 'completed' 
                    AND r.status = 'in_progress'
                    AND r.user_id = ?
                    " . ($search ? "AND (fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ? OR fu.plate LIKE ?)" : "") . "
                    ORDER BY fu.upload_date DESC
                    LIMIT " . intval($limit)
                );
                
                $params = [$userId, $userId];
                if ($search) {
                    $searchParam = "%$search%";
                    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
                }
                
                $stmt->execute($params);
                $revisionFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Revize dosya sayısını hesapla
                $countStmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT fu.id) as count
                    FROM file_uploads fu
                    INNER JOIN revisions r ON fu.id = r.upload_id
                    WHERE fu.user_id = ? 
                    AND fu.status = 'completed' 
                    AND r.status = 'in_progress'
                    AND r.user_id = ?
                    " . ($search ? "AND (fu.original_name LIKE ? OR fu.plate LIKE ?)" : "") . "
                ");
                
                $countParams = [$userId, $userId];
                if ($search) {
                    $searchParam = "%$search%";
                    $countParams = array_merge($countParams, [$searchParam, $searchParam]);
                }
                
                $countStmt->execute($countParams);
                $revisionCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            }
            
            // 4. İki listeyi birleştir (tarih sırasına göre)
            $allFiles = array_merge($normalFiles, $revisionFiles);
            
            // Revize dosyalarını işaretle ve hedef dosyayı bul - revision-detail.php mantığı
            for ($i = 0; $i < count($allFiles); $i++) {
                if (isset($allFiles[$i]['response_id']) || isset($allFiles[$i]['revision_id'])) {
                    $allFiles[$i]['processing_type'] = 'revision';
                    
                    // Target file belirleme - revision-detail.php'den direkt kopyalandı
                    $targetFile = [
                        'type' => 'Bilinmiyor',
                        'name' => 'Dosya bilgisi alınamadı',
                        'size' => 0,
                        'date' => null,
                        'is_found' => false
                    ];
                    
                    try {
                        // Revision detail kontrolü
                        $revisionCheckStmt = $pdo->prepare("SELECT response_id FROM revisions WHERE id = ?");
                        $revisionCheckStmt->execute([$allFiles[$i]['revision_id']]);
                        $revisionDetails = $revisionCheckStmt->fetch(PDO::FETCH_ASSOC);
                        $responseId = $revisionDetails['response_id'] ?? null;
                        
                        if ($responseId) {
                            // Evet, bu bir yanıt dosyası için revize talebi. Yanıt dosyasının bilgilerini çekelim.
                            $responseStmt = $pdo->prepare("SELECT original_name, file_size, upload_date FROM file_responses WHERE id = ?");
                            $responseStmt->execute([$responseId]);
                            $responseFileData = $responseStmt->fetch(PDO::FETCH_ASSOC);
                            
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
                            $prevRevisionStmt = $pdo->prepare("
                                SELECT rf.original_name, rf.file_size, rf.upload_date
                                FROM revisions r
                                JOIN revision_files rf ON r.id = rf.revision_id
                                WHERE r.upload_id = ? AND r.status = 'completed' AND r.requested_at < ?
                                ORDER BY r.completed_at DESC
                                LIMIT 1
                            ");
                            $prevRevisionStmt->execute([$allFiles[$i]['id'], $allFiles[$i]['revision_date']]);
                            $previousRevisionFile = $prevRevisionStmt->fetch(PDO::FETCH_ASSOC);
                            
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
                                $targetFile = [
                                    'type' => 'Orijinal Dosya',
                                    'name' => $allFiles[$i]['original_name'],
                                    'size' => $allFiles[$i]['file_size'],
                                    'date' => $allFiles[$i]['upload_date'],
                                    'is_found' => true
                                ];
                            }
                        }
                    } catch (PDOException $e) {
                        // Hata durumunda logla, ama sayfayı bozma.
                        error_log("Files.php - Hedef dosya belirlenirken hata: " . $e->getMessage());
                        $targetFile = [
                            'type' => 'Orijinal Dosya',
                            'name' => $allFiles[$i]['original_name'],
                            'size' => $allFiles[$i]['file_size'] ?? 0,
                            'date' => $allFiles[$i]['upload_date'],
                            'is_found' => true
                        ];
                    }
                    
                    // Dosyaya target file bilgilerini ekle
                    $allFiles[$i]['target_file_name'] = $targetFile['name'];
                    $allFiles[$i]['target_file_type'] = $targetFile['type'];
                }
            }
            
            // 5. Tarihe göre sırala
            usort($allFiles, function($a, $b) {
                return strtotime($b['upload_date']) - strtotime($a['upload_date']);
            });
            
            // 6. Sadece limit kadar al
            $userFiles = array_slice($allFiles, 0, $limit);
            $totalFiles = $normalCount + $revisionCount;
            
        } catch (Exception $e) {
            error_log('Processing dosyaları getirme hatası: ' . $e->getMessage());
            // Hata durumunda sadece normal processing
            $userFiles = $fileManager->getUserUploads($userId, $page, $limit, 'processing', $search);
            $totalFiles = $fileManager->getUserUploadCount($userId, 'processing', $search);
        }
    } else {
        // Diğer durumlar için normal listeleme
        $userFiles = $fileManager->getUserUploads($userId, $page, $limit, $status, $search);
        $totalFiles = $fileManager->getUserUploadCount($userId, $status, $search);
    }
}
$totalPages = ceil($totalFiles / $limit);

// Processing filtresi için sayfalama özel durumu
if ($status === 'processing' && !$singleFileMode) {
    // Processing durumunda sadece ilk sayfa düzgün çalışır, çok sayıda dosya varsa daha basit yaklaşım
    // Bu durumda sayfalamayı sınırlayalım
    if ($totalFiles > $limit * 3) {
        $totalPages = 3; // Maksimum 3 sayfa
        if ($page > 3) {
            $page = 1;
        }
    }
}

// Her dosya için revize taleplerini getir
for ($j = 0; $j < count($userFiles); $j++) {
    try {
        // Bu dosya için revize taleplerini getir
        $stmt = $pdo->prepare("
            SELECT * FROM revisions 
            WHERE upload_id = ? AND user_id = ? 
            ORDER BY requested_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userFiles[$j]['id'], $userId]);
        $latestRevision = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $userFiles[$j]['latest_revision'] = $latestRevision;
        
        // Aktif revize durumu varsa ekle
        if ($latestRevision && in_array($latestRevision['status'], ['pending', 'in_progress'])) {
            $userFiles[$j]['has_active_revision'] = true;
            $userFiles[$j]['revision_status'] = $latestRevision['status'];
            $userFiles[$j]['revision_id'] = $latestRevision['id'];
            
            // Eğer bu dosya processing filtresinde ve revize işleniyorsa, dosya durumunu güncelle
            if ($status === 'processing' && $latestRevision['status'] === 'in_progress' && $userFiles[$j]['status'] === 'completed') {
                $userFiles[$j]['display_status'] = 'revision_processing';
                $userFiles[$j]['processing_type'] = 'revision';
            }
        } else {
            $userFiles[$j]['has_active_revision'] = false;
        }
    } catch (Exception $e) {
        error_log('Revize talebi getirme hatası: ' . $e->getMessage());
        $userFiles[$j]['latest_revision'] = null;
        $userFiles[$j]['has_active_revision'] = false;
    }
}

// İstatistikler - Revize durumunu da dahil et
if ($singleFileMode) {
    // Bildirimden gelen dosya için normal istatistikler
    $stats = $fileManager->getUserFileStats($userId);
} else {
    // Normal istatistikler + revize işlenen dosyalar
    $stats = $fileManager->getUserFileStats($userId);
    
    // Revize işlenen dosya sayısını processing'e ekle
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT fu.id) as revision_processing_count
            FROM file_uploads fu
            LEFT JOIN revisions r ON fu.id = r.upload_id
            WHERE fu.user_id = ? 
            AND fu.status = 'completed' 
            AND r.status = 'in_progress'
            AND r.user_id = ?
        ");
        $stmt->execute([$userId, $userId]);
        $revisionProcessingCount = $stmt->fetch(PDO::FETCH_ASSOC)['revision_processing_count'];
        
        // Processing sayısına ekle
        $stats['processing'] += $revisionProcessingCount;
        $stats['total'] += 0; // Total zaten hesaplanmış, sadece processing güncelledik
        
    } catch (Exception $e) {
        error_log('Revize istatistik hesaplama hatası: ' . $e->getMessage());
    }
}

$pageTitle = 'Dosyalarım';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-folder me-2 text-primary"></i>Dosyalarım
                        <?php if ($singleFileMode): ?>
                            <small class="badge bg-info ms-2">Bildirimden Filtrelendi</small>
                        <?php endif; ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php if ($singleFileMode): ?>
                            Bildirimden seçilen dosya gösteriliyor. <a href="files.php" class="text-primary">Tüm dosyaları göster</a>
                        <?php else: ?>
                            Yüklediğiniz ana dosyaları görüntüleyin ve yönetin
                        <?php endif; ?>
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="upload.php" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i>Yeni Dosya
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle me-3 fa-lg"></i>
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
                        <i class="bi bi-check-circle me-3 fa-lg"></i>
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
                                        <i class="bi bi-chart-line text-success"></i>
                                        <span class="text-success">Aktif koleksiyon</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-folder2-open"></i>
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
                                        <i class="bi bi-clock text-warning"></i>
                                        <span class="text-warning">İnceleme sırası</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-clock"></i>
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
                                        <i class="bi bi-gear-wide-connected text-info"></i>
                                        <span class="text-info">Aktif işlem</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-gear-wide-connected"></i>
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
                                        <i class="bi bi-download text-success"></i>
                                        <span class="text-success">İndirilmeye hazır</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
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
                        <i class="bi bi-filter me-2"></i>Filtrele ve Ara
                    </h6>
                </div>
                            <!-- Dosya ID Filtresi Uyarısı -->
            <?php if ($filterId && isValidUUID($filterId)): ?>
                <div class="alert-info mb-3" style="padding: 1rem; display: flex; align-items: center;background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border: 1px solid #81c784;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Belirli dosya görüntüleniyor:</strong> ID: <?php echo htmlspecialchars($filterId); ?>
                    <a href="files.php" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="bi bi-times me-1"></i>Filtreyi Kaldır
                    </a>
                </div>
            <?php endif; ?>
                <div class="filter-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <?php if ($filterId): ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($filterId); ?>">
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label for="search" class="form-label">
                                <i class="bi bi-search me-1"></i>Dosya Ara
                            </label>
                            <input type="text" class="form-control form-control-modern" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Dosya adı, marka, model, plaka...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">
                                <i class="bi bi-tag me-1"></i>Durum
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
                                    <i class="bi bi-search me-1"></i>Filtrele
                                </button>
                                <a href="files.php" class="btn btn-outline-secondary btn-modern">
                                    <i class="bi bi-undo me-1"></i><?php echo $filterId ? 'Tüm Dosyalar' : 'Temizle'; ?>
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-outline-info btn-modern dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-download me-1"></i>İşlemler
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="exportToExcel()">
                                            <i class="bi bi-folder2-open-excel me-2"></i>Excel Export
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="refreshPage()">
                                            <i class="bi bi-sync me-2"></i>Sayfayı Yenile
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
                            <i class="bi bi-folder-open"></i>
                        </div>
                        <h4>
                            <?php if ($filterId): ?>
                                Aranan dosya bulunamadı
                            <?php elseif ($search || $status): ?>
                                Filtreye uygun dosya bulunamadı
                            <?php else: ?>
                                Henüz dosya yüklenmemiş
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-4">
                            <?php if ($filterId): ?>
                                Bildirimden gelen dosya bulunamadı veya size ait değil. <a href="files.php" class="text-primary">Tüm dosyalar</a> sayfasına gidebilirsiniz.
                            <?php elseif ($search || $status): ?>
                                Farklı filtre kriterleri deneyebilir veya tüm dosyalarınızı görüntüleyebilirsiniz.
                            <?php else: ?>
                                İlk ECU dosyanızı yüklemek için butona tıklayın ve işlem sürecini başlatın.
                            <?php endif; ?>
                        </p>
                        <div class="empty-actions">
                            <?php if ($search || $status): ?>
                                <a href="files.php" class="btn btn-outline-primary btn-lg">
                                    <i class="bi bi-list me-2"></i>Tüm Dosyalar
                                </a>
                            <?php endif; ?>
                            <a href="upload.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-upload me-2"></i>Dosya Yükle
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
                                        <i class="bi bi-folder2-open-alt"></i>
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
                                                <i class="bi bi-folder2-open-alt text-primary"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="file-info">
                                                <?php if (isset($file['processing_type']) && $file['processing_type'] === 'revision'): ?>
                                                    <!-- Revize İşlenen Dosya Görünümü -->
                                                    <h6 class="file-name mb-1">
                                                        <i class="bi bi-folder2-open-alt text-primary me-1"></i>
                                                        <strong>Ana Dosya:</strong> <?php echo htmlspecialchars($file['original_name']); ?>
                                                        <span class="badge bg-info ms-2">
                                                            <i class="bi bi-sync-alt me-1"></i>Revize İşleniyor
                                                        </span>
                                                    </h6>
                                                    
                                                    <!-- Revize Edilmesi İstenen Dosya -->
                                                    <div class="mt-2">
                                                                                                            <?php if (isset($file['target_file_type']) && $file['target_file_type'] !== 'Orijinal Dosya'): ?>
                                                                <span class="badge bg-warning text-dark ms-1"><?php echo $file['target_file_type']; ?></span>
                                                            <?php endif; ?>
                                                        <small class="d-block text-warning">
                                                            <i class="bi bi-arrow-circle-right me-1"></i>
                                                            <strong>Revize Edilmesi İstenen:</strong> 
                                                            <?php echo htmlspecialchars($file['target_file_name'] ?? $file['original_name']); ?>
                                                            
                                                        </small>
                                                    </div>
                                                    
                                                    <!-- Revize dosyası henüz hazır değil - işleniyor -->
                                                    <div class="mt-2">
                                                        <small class="d-block text-info">
                                                            <i class="bi bi-gear-wide-connected me-1"></i>
                                                            <strong>Yeni Revize Dosyası:</strong> <em>Hazırlanıyor...</em>
                                                        </small>
                                                        <?php if (isset($file['request_notes']) && !empty($file['request_notes'])): ?>
                                                            <small class="d-block text-muted mt-1">
                                                                <i class="bi bi-comment-dots me-1"></i>
                                                                <em>"<?php echo htmlspecialchars(substr($file['request_notes'], 0, 60)) . (strlen($file['request_notes']) > 60 ? '...' : ''); ?>"</em>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Normal Dosya Görünümü -->
                                                    <h6 class="file-name mb-1" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                                        <?php echo htmlspecialchars($file['original_name']); ?>
                                                    </h6>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($file['upload_notes'])): ?>
                                                    <small class="text-muted">
                                                        <i class="bi bi-sticky-note me-1"></i>
                                                        <?php echo htmlspecialchars(substr($file['upload_notes'], 0, 50)) . (strlen($file['upload_notes']) > 50 ? '...' : ''); ?>
                                                    </small>
                                                <?php endif; ?>
                                                
                                            </div>
                                        </td>
                                        <td>
                                            <div class="vehicle-info" style="width: 300px;">
                                                    <div class="brand-model">
                                                        <strong><?php echo htmlspecialchars($file['brand_name'] ?? 'Bilinmiyor'); ?></strong>
                                                        <?php if (!empty($file['model_name'])): ?>
                                                            - <?php echo htmlspecialchars($file['model_name']); ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($file['series_name'])): ?>
                                                            <br><small class="text-muted">
                                                                <i class="bi bi-tag me-1"></i>
                                                                Seri: <?php echo htmlspecialchars($file['series_name']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($file['engine_name'])): ?>
                                                            <br><small class="text-muted">
                                                                <i class="bi bi-cog me-1"></i>
                                                                Motor: <?php echo htmlspecialchars($file['engine_name']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($file['plate'])): ?>
                                                        <div class="mt-1">
                                                            <span class="badge bg-dark text-white">
                                                                <i class="bi bi-id-card me-1"></i>
                                                                <?php echo strtoupper(htmlspecialchars($file['plate'])); ?>
                                                            </span>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted d-block mt-1">
                                                            <i class="bi bi-minus-circle me-1"></i>
                                                            Plaka belirtilmemiş
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
                                            
                                            <!-- Ana dosya durumu -->
                                            <span class="badge bg-<?php echo $config['class']; ?> status-badge">
                                                <i class="bi bi-<?php echo $config['icon']; ?> me-1"></i>
                                                <?php echo $config['text']; ?>
                                            </span>
                                            
                                            <!-- Revize talebi durumu -->
                                            <?php if ($file['has_active_revision']): ?>
                                                <?php
                                                $revisionConfig = [
                                                    'pending' => ['class' => 'warning', 'text' => 'Revize Bekliyor', 'icon' => 'edit'],
                                                    'in_progress' => ['class' => 'info', 'text' => 'Revize İşleniyor', 'icon' => 'cogs']
                                                ];
                                                $revConfig = $revisionConfig[$file['revision_status']] ?? ['class' => 'secondary', 'text' => 'Revize', 'icon' => 'edit'];
                                                ?>
                                                <br>
                                                <span class="badge bg-<?php echo $revConfig['class']; ?> status-badge mt-1" 
                                                      title="Bu dosya için revize talebi <?php echo $file['revision_status'] === 'in_progress' ? 'işleme alındı' : 'beklemede'; ?>">
                                                    <i class="bi bi-<?php echo $revConfig['icon']; ?> me-1"></i>
                                                    <?php echo $revConfig['text']; ?>
                                                </span>
                                            <?php endif; ?>
                                            
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
                                                    
                                                    // Revize durumu varsa progress'i ayarla
                                                    if ($file['has_active_revision'] && $file['revision_status'] === 'in_progress') {
                                                        $progressValue = 90; // Revize işleniyor
                                                        $progressClass = 'bg-warning';
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
                                                    <i class="bi bi-eye me-1"></i>Detay
                                                </a>
                                                
                                                <!-- Görüntü Dosyası için Görüntüle Butonu -->
                                                <?php if (isImageFile($file['original_name'])): ?>
                                                    <a href="view-image.php?id=<?php echo $file['id']; ?>&type=upload" 
                                                       class="btn btn-outline-info btn-sm" 
                                                       title="Görüntüyü büyük boyutta gör">
                                                        <i class="bi bi-image me-1"></i>Görüntüle
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($file['status'] === 'completed'): ?>
                                                    <a href="download.php?id=<?php echo $file['id']; ?>&type=upload" 
                                                       class="btn btn-success btn-sm">
                                                        <i class="bi bi-download me-1"></i>İndir
                                                    </a>
                                                    
                                                    <?php if (!$file['has_active_revision']): ?>
                                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                                onclick="requestRevision('<?php echo $file['id']; ?>', 'upload')">
                                                            <i class="bi bi-redo me-1"></i>Revize
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="revision-detail.php?id=<?php echo $file['revision_id']; ?>" class="btn btn-outline-info btn-sm"
                                                           title="Revize talebi <?php echo $file['revision_status'] === 'in_progress' ? 'işleme alındı' : 'beklemede'; ?>">
                                                            <i class="bi bi-eye me-1"></i>Revize Takip
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                
                                                <!-- İptal Butonu -->
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="requestCancellation('<?php echo $file['id']; ?>', 'upload', '<?php echo htmlspecialchars($file['original_name']); ?>')">
                                                    <i class="bi bi-times me-1"></i>İptal
                                                </button>
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
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filterId ? '&id=' . urlencode($filterId) : ''; ?>">
                                            <i class="bi bi-chevron-left"></i>
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
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filterId ? '&id=' . urlencode($filterId) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Sonraki sayfa -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filterId ? '&id=' . urlencode($filterId) : ''; ?>">
                                            <i class="bi bi-chevron-right"></i>
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
                    <i class="bi bi-redo me-2 text-warning"></i>Revize Talebi
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
                            <i class="bi bi-info-circle me-3 mt-1"></i>
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
                            <i class="bi bi-comment me-1"></i>
                            Revize Talebi Açıklaması <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-modern" id="revision_notes" name="revision_notes" 
                                  rows="5" required
                                  placeholder="Lütfen dosyada hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: 'Güç artırımı', 'EGR kapatma', 'DPF silme' gibi..."></textarea>
                        <div class="form-text">
                            <i class="bi bi-lightbulb me-1"></i>
                            Ne tür değişiklik istediğinizi mümkün olduğunca detaylı açıklayın.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-paper-plane me-2"></i>Revize Talebi Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dosya İptal Modal -->
<div class="modal fade" id="cancellationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2 text-danger"></i>Dosya İptal Talebi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cancellationFileId">
                <input type="hidden" id="cancellationFileType">
                
                <div class="alert alert-warning">
                    <div class="d-flex">
                        <i class="bi bi-exclamation-triangle me-3 mt-1"></i>
                        <div>
                            <strong>Dikkat!</strong>
                            <p class="mb-0 mt-1">
                                Bu dosya için iptal talebi göndermek üzeresiniz. İptal talebiniz admin tarafından değerlendirildikten sonra:
                            </p>
                            <ul class="mt-2 mb-0">
                                <li>Dosya kalıcı olarak silinecektir</li>
                                <li>Eğer ücretli bir işlemse kredi iadeniz yapılacaktır</li>
                                <li>Bu işlem geri alınamaz</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-folder2-open me-1"></i>İptal edilecek dosya:
                    </label>
                    <div class="form-control-plaintext" id="cancellationFileName" style="background: #f8f9fa; padding: 0.75rem; border-radius: 8px;"></div>
                </div>
                
                <div class="mb-3">
                    <label for="cancellation_reason" class="form-label fw-semibold">
                        <i class="bi bi-comment me-1"></i>
                        İptal Sebebi <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control form-control-modern" id="cancellation_reason" name="cancellation_reason" 
                              rows="4" required
                              placeholder="Lütfen dosyayı neden iptal etmek istediğinizi açıklayın. Örneğin: 'Yanlış dosya yükledim', 'Artık ihtiyacım yok', 'Başka seçenek tercih ediyorum' gibi..."></textarea>
                    <div class="form-text">
                        <i class="bi bi-lightbulb me-1"></i>
                        İptal sebebinizi belirtmeniz, gelecekteki hizmet kalitemizi artırmamıza yardımcı olur.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-danger" onclick="submitCancellation()">
                    <i class="bi bi-paper-plane me-2"></i>İptal Talebi Gönder
                </button>
            </div>
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
    display: inline-block;
    margin: 0.1rem 0;
}

.status-badge.mt-1 {
    margin-top: 0.25rem !important;
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

/* Dosya ID Filtresi Uyarısı */
.alert.alert-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border: 1px solid #81c784;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.alert.alert-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

.alert.alert-info strong {
    color: #1976d2;
}

.alert.alert-info .fas.fa-info-circle {
    color: #1976d2;
    font-size: 1.2rem;
}

.alert.alert-info .btn-outline-primary {
    border-color: #1976d2;
    color: #1976d2;
    font-weight: 500;
    transition: all 0.3s ease;
}

.alert.alert-info .btn-outline-primary:hover {
    background-color: #1976d2;
    border-color: #1976d2;
    color: white;
    transform: scale(1.05);
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
    
    /* Dosya ID Filtresi Uyarısı - Mobile */
    .alert.alert-info {
        flex-direction: column;
        align-items: flex-start !important;
        text-align: left;
    }
    
    .alert.alert-info .btn {
        margin-top: 0.5rem;
        margin-left: 0 !important;
        width: 100%;
    }
    
    .stat-card-body {
        padding: 1.25rem;
    }
    
    .stat-number {
        font-size: 1.75rem;
    }
    
    .status-badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.5rem;
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
        modalTitle.innerHTML = '<i class="bi bi-redo me-2 text-warning"></i>Yanıt Dosyası Revize Talebi';
        revisionInfoText.innerHTML = 'Yanıt dosyasında bir değişiklik veya düzenleme istiyorsanız bu formu kullanabilirsiniz. Admin ekibimiz dosyanızı yeniden gözden geçirecek ve geliştirilmiş bir sürüm hazırlayacaktır.';
        document.getElementById('revision_notes').placeholder = 'Yanıt dosyasında hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: "Daha fazla güç istiyorum", "Yakıt tüketimi daha iyi olsun", "Torku artmalı" gibi...';
    } else {
        modalTitle.innerHTML = '<i class="bi bi-redo me-2 text-warning"></i>Revize Talebi';
        revisionInfoText.innerHTML = 'Dosyanızda bir değişiklik veya düzenleme istiyorsanız bu formu kullanabilirsiniz. Talep incelendikten sonra size geri dönüş yapılacaktır.';
        document.getElementById('revision_notes').placeholder = 'Lütfen dosyada hangi değişiklikleri istediğinizi detaylı olarak açıklayın. Örneğin: "Güç artırımı", "EGR kapatma", "DPF silme" gibi...';
    }
    
    // Formu temizle
    document.getElementById('revision_notes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('revisionModal'));
    modal.show();
}

// Request Cancellation
function requestCancellation(fileId, fileType, fileName) {
    document.getElementById('cancellationFileId').value = fileId;
    document.getElementById('cancellationFileType').value = fileType;
    document.getElementById('cancellationFileName').textContent = fileName;
    
    // Formu temizle
    document.getElementById('cancellation_reason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('cancellationModal'));
    modal.show();
}

// Submit Cancellation
function submitCancellation() {
    const fileId = document.getElementById('cancellationFileId').value;
    const fileType = document.getElementById('cancellationFileType').value;
    const reason = document.getElementById('cancellation_reason').value.trim();
    
    if (!reason) {
        alert('Lütfen iptal sebebini belirtin.');
        document.getElementById('cancellation_reason').focus();
        return;
    }
    
    // Loading state
    const submitBtn = document.querySelector('#cancellationModal .btn-danger');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin me-2"></i>Gönderiliyor...';
    submitBtn.disabled = true;
    
    // AJAX request
    fetch('../ajax/file-cancellation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'request_cancellation',
            file_id: fileId,
            file_type: fileType,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancellationModal'));
            modal.hide();
            
            // Show success message
            showAlert('success', data.message);
            
            // Refresh page after a delay
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Error
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Sistem hatası oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show Alert
function showAlert(type, message) {
    const alertContainer = document.querySelector('.container-fluid .row main');
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="margin-top: 1rem;">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert after the page title
    const pageTitle = alertContainer.querySelector('.border-bottom');
    pageTitle.insertAdjacentHTML('afterend', alertHtml);
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
    // Cache buster - PHP referans hatası düzeltildi
    console.log('Files page loaded at:', new Date().toISOString());
    console.log('Page version: 3.0 - PHP Reference Bugs Fixed!');
    console.log('Both foreach reference bugs fixed with for loops');
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
