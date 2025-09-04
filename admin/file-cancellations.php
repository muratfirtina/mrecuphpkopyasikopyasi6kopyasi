<?php
/**
 * Mr ECU - Admin Dosya İptal Talepleri Yönetimi
 * Admin File Cancellation Requests Management
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// FileCancellationManager'ı yükle
require_once '../includes/FileCancellationManager.php';
$cancellationManager = new FileCancellationManager($pdo);

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

// İşlem kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cancellationId = sanitize($_POST['cancellation_id'] ?? '');
    $adminNotes = sanitize($_POST['admin_notes'] ?? '');
    
    if (!isValidUUID($cancellationId)) {
        $_SESSION['error'] = 'Geçersiz iptal talebi ID.';
    } else {
        switch ($action) {
            case 'approve':
                if (empty(trim($adminNotes))) {
                    $adminNotes = 'İptal talebi onaylandı ve dosya silindi.';
                }
                $result = $cancellationManager->approveCancellation($cancellationId, $_SESSION['user_id'], $adminNotes);
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                } else {
                    $_SESSION['error'] = $result['message'];
                }
                break;
                
            case 'reject':
                if (empty(trim($adminNotes))) {
                    $_SESSION['error'] = 'Red sebebi gereklidir.';
                } else {
                    $result = $cancellationManager->rejectCancellation($cancellationId, $_SESSION['user_id'], $adminNotes);
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                    } else {
                        $_SESSION['error'] = $result['message'];
                    }
                }
                break;
                
            default:
                $_SESSION['error'] = 'Geçersiz işlem.';
                break;
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: file-cancellations.php');
    exit;
}

// Filtreleme parametreleri
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$fileType = isset($_GET['file_type']) ? sanitize($_GET['file_type']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;

// İptal taleplerini getir
$cancellations = $cancellationManager->getAllCancellations($page, $limit, $status, $fileType, $search);

// Debug için iptal talepleri sayısını kontrol edelim
if (isset($_GET['debug'])) {
    echo "<div class='alert alert-info'>";
    echo "<strong>Debug Bilgileri:</strong><br>";
    echo "Toplam İptal Talebi: " . count($cancellations) . "<br>";
    echo "Sayfa: $page, Limit: $limit<br>";
    echo "Status: '$status', FileType: '$fileType', Search: '$search'<br>";
    
    // Direkt veritabanı kontrolü
    try {
        $directStmt = $pdo->query("SELECT COUNT(*) as total FROM file_cancellations");
        $directCount = $directStmt->fetchColumn();
        echo "Veritabanında Toplam Talep: $directCount<br>";
        
        if ($directCount > 0) {
            $sampleStmt = $pdo->query("SELECT id, status, file_type, requested_at FROM file_cancellations ORDER BY requested_at DESC LIMIT 3");
            $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Son 3 Talep:<br>";
            foreach ($samples as $sample) {
                echo "- ID: {$sample['id']}, Durum: {$sample['status']}, Tip: {$sample['file_type']}, Tarih: {$sample['requested_at']}<br>";
            }
        }
    } catch (Exception $e) {
        echo "Veritabanı kontrol hatası: " . $e->getMessage() . "<br>";
    }
    
    if (!empty($cancellations)) {
        echo "İlk Talep ID: " . $cancellations[0]['id'] . "<br>";
        echo "İlk Kullanıcı: " . ($cancellations[0]['username'] ?? 'NULL') . "<br>";
        echo "İlk Dosya Tipi: " . ($cancellations[0]['file_type'] ?? 'NULL') . "<br>";
    }
    echo "</div>";
}

// İstatistikleri getir
$stats = $cancellationManager->getCancellationStats();

$pageTitle = 'Dosya İptal Talepleri';

// Header include
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-clock-history me-2 text-danger"></i>Dosya İptal Talepleri
                    </h1>
                    <p class="text-muted mb-0">Kullanıcıların dosya iptal taleplerini yönetin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="bi bi-sync me-1"></i>Yenile
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Hata!</strong> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Başarılı!</strong> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- İstatistik Kartları -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                                    <div class="stat-label">Toplam Talep</div>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-list"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
                                    <div class="stat-label">Bekleyen</div>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-success"><?php echo $stats['approved']; ?></div>
                                    <div class="stat-label">Onaylanan</div>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-info"><?php echo $stats['total_refunded']; ?></div>
                                    <div class="stat-label">İade Edilen Kredi</div>
                                </div>
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-coin"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtre -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-filter me-2"></i>Filtrele
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Ara</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Kullanıcı adı, email, dosya adı...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">Durum</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tüm Durumlar</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Onaylanan</option>
                                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                            </select>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>Filtrele
                                </button>
                                <a href="file-cancellations.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Temizle
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filtreleme Formu -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Durum</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Tüm Durumlar</option>
                                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Onaylanan</option>
                                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="file_type" class="form-label">Dosya Tipi</label>
                                    <select class="form-select" id="file_type" name="file_type">
                                        <option value="">Tüm Tipler</option>
                                        <option value="upload" <?php echo isset($_GET['file_type']) && $_GET['file_type'] === 'upload' ? 'selected' : ''; ?>>Ana Dosyalar</option>
                                        <option value="response" <?php echo isset($_GET['file_type']) && $_GET['file_type'] === 'response' ? 'selected' : ''; ?>>Yanıt Dosyaları</option>
                                        <option value="revision" <?php echo isset($_GET['file_type']) && $_GET['file_type'] === 'revision' ? 'selected' : ''; ?>>Revize Dosyaları</option>
                                        <option value="additional" <?php echo isset($_GET['file_type']) && $_GET['file_type'] === 'additional' ? 'selected' : ''; ?>>Ek Dosyalar</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Arama</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Kullanıcı adı, dosya adı veya plaka...">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-search me-1"></i>Filtrele
                                    </button>
                                    <a href="file-cancellations.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-times me-1"></i>Temizle
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İptal Talepleri Listesi -->
            <?php if (empty($cancellations)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox fa-3x text-muted mb-3"></i>
                        <h5>
                            <?php if ($search || $status): ?>
                                Filtreye uygun iptal talebi bulunamadı
                            <?php else: ?>
                                Henüz iptal talebi bulunmuyor
                            <?php endif; ?>
                        </h5>
                        <p class="text-muted">
                            <?php if ($search || $status): ?>
                                Farklı filtre kriterleri deneyebilirsiniz.
                            <?php else: ?>
                                Kullanıcıların dosya iptal talepleri burada görüntülenecektir.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th width="170">Kullanıcı</th>
                                    <th width="350">İptal Edilen Dosya Bilgileri</th>
                                    <th width="220">İptal Sebebi</th>
                                    <th width="110">Kredi İadesi</th>
                                    <th width="90">Durum</th>
                                    <th width="110">Tarih</th>
                                    <th width="120" class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cancellations as $cancellation): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <strong><?php echo htmlspecialchars($cancellation['username']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($cancellation['email']); ?></small>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($cancellation['first_name'] . ' ' . $cancellation['last_name']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="file-info">
                                                <?php
                                                // Dosya tipi için dosya adını ve diğer bilgileri belirle
                                                $fileName = '';
                                                $mainFileName = '';
                                                $plate = '';
                                                $fileDate = '';
                                                $fileTypeDisplay = '';
                                                $fileTypeIcon = '';
                                                $fileTypeColor = '';
                                                
                                                switch ($cancellation['file_type']) {
                                                    case 'upload':
                                                        $fileName = $cancellation['upload_file_name'] ?? '';
                                                        $plate = $cancellation['upload_plate'] ?? '';
                                                        $fileDate = $cancellation['upload_date'] ?? '';
                                                        $fileTypeDisplay = 'ANA DOSYA';
                                                        $fileTypeIcon = 'bi bi-folder2-open-upload';
                                                        $fileTypeColor = 'bg-primary';
                                                        break;
                                                    case 'response':
                                                        $fileName = $cancellation['response_file_name'] ?? '';
                                                        $mainFileName = $cancellation['response_main_file_name'] ?? '';
                                                        $plate = $cancellation['response_main_plate'] ?? '';
                                                        $fileDate = $cancellation['response_date'] ?? '';
                                                        $fileTypeDisplay = 'YANIT DOSYASI';
                                                        $fileTypeIcon = 'bi bi-reply';
                                                        $fileTypeColor = 'bg-success';
                                                        break;
                                                    case 'revision':
                                                        $fileName = $cancellation['revision_file_name'] ?? '';
                                                        $mainFileName = $cancellation['revision_main_file_name'] ?? '';
                                                        $plate = $cancellation['revision_main_plate'] ?? '';
                                                        $fileDate = $cancellation['revision_date'] ?? '';
                                                        $fileTypeDisplay = 'REVİZYON DOSYASI';
                                                        $fileTypeIcon = 'bi bi-pencil-square';
                                                        $fileTypeColor = 'bg-warning';
                                                        break;
                                                    case 'additional':
                                                        $fileName = $cancellation['additional_file_name'] ?? '';
                                                        $mainFileName = $cancellation['additional_main_file_name'] ?? '';
                                                        $plate = $cancellation['additional_main_plate'] ?? '';
                                                        $fileDate = $cancellation['additional_date'] ?? '';
                                                        $fileTypeDisplay = 'EK DOSYA';
                                                        $fileTypeIcon = 'bi bi-paperclip';
                                                        $fileTypeColor = 'bg-info';
                                                        break;
                                                }
                                                ?>
                                                
                                                <!-- Dosya Tipi Badge -->
                                                <div class="file-type-badge mb-2">
                                                    <span class="badge <?php echo $fileTypeColor; ?> fs-6">
                                                        <i class="<?php echo $fileTypeIcon; ?> me-1"></i>
                                                        <?php echo $fileTypeDisplay; ?>
                                                    </span>
                                                </div>
                                                
                                                <!-- İptal Edilen Dosya Adı -->
                                                <?php if (!empty($fileName)): ?>
                                                    <div class="cancelled-file-name mb-2">
                                                        <div class="fw-bold text-dark mb-1">
                                                            <i class="bi bi-clock-history text-danger me-1"></i>
                                                            İptal Edilen Dosya:
                                                        </div>
                                                        <div class="file-name-display p-2 bg-light border-start border-danger border-3">
                                                            <strong class="text-primary"><?php echo htmlspecialchars($fileName); ?></strong>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Bağlı Ana Dosya (Varsa) -->
                                                <?php if (!empty($mainFileName) && $mainFileName !== $fileName): ?>
                                                    <div class="linked-file mb-2">
                                                        <div class="fw-bold text-success mb-1">
                                                            <i class="bi bi-link me-1"></i>
                                                            Bağlı Ana Dosya:
                                                        </div>
                                                        <div class="linked-file-display p-2 bg-light border-start border-success border-2">
                                                            <small class="text-success"><?php echo htmlspecialchars($mainFileName); ?></small>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Araç Plaka Bilgisi -->
                                                <?php if (!empty($plate)): ?>
                                                    <div class="vehicle-info mb-2">
                                                        <div class="fw-bold text-info mb-1">
                                                            <i class="bi bi-car me-1"></i>
                                                            Araç Plakası:
                                                        </div>
                                                        <div class="plate-display">
                                                            <span class="badge bg-dark text-white fs-6 px-3 py-2" style="font-family: monospace; letter-spacing: 1px;">
                                                                <?php echo strtoupper(htmlspecialchars($plate)); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="vehicle-info mb-2">
                                                        <div class="fw-bold text-muted mb-1">
                                                            <i class="bi bi-car me-1"></i>
                                                            Araç Plakası:
                                                        </div>
                                                        <div class="plate-display">
                                                            <span class="badge bg-secondary text-white fs-6 px-3 py-2">
                                                                Belirtilmemiş
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Dosya Meta Bilgileri -->
                                                <div class="file-meta pt-2 border-top">
                                                    <div class="row g-0">
                                                        <div class="col-6">
                                                            <small class="text-muted">
                                                                <strong>Dosya ID:</strong><br>
                                                                <code><?php echo htmlspecialchars(substr($cancellation['file_id'], 0, 8)); ?>...</code>
                                                            </small>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">
                                                                <strong>Talep ID:</strong><br>
                                                                <code><?php echo htmlspecialchars(substr($cancellation['id'], 0, 8)); ?>...</code>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($fileDate)): ?>
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar me-1"></i>
                                                                <strong>Dosya Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($fileDate)); ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="reason-text">
                                                <?php echo htmlspecialchars(strlen($cancellation['reason']) > 100 ? substr($cancellation['reason'], 0, 100) . '...' : $cancellation['reason']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($cancellation['credits_to_refund'] > 0): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-coin me-1"></i>
                                                    <?php echo number_format($cancellation['credits_to_refund'], 2); ?> kredi
                                                </span>
                                                <?php if ($cancellation['status'] === 'approved'): ?>
                                                    <small class="text-success d-block">
                                                        <i class="bi bi-check-circle me-1"></i>İade Edildi
                                                    </small>
                                                <?php elseif ($cancellation['status'] === 'pending'): ?>
                                                    <small class="text-warning d-block">
                                                        <i class="bi bi-clock me-1"></i>İade Bekliyor
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Ücretsiz</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusConfig = [
                                                'pending' => ['class' => 'warning', 'text' => 'Bekleyen', 'icon' => 'clock'],
                                                'approved' => ['class' => 'success', 'text' => 'Onaylandı', 'icon' => 'check'],
                                                'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times']
                                            ];
                                            $config = $statusConfig[$cancellation['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                                            ?>
                                            <span class="badge bg-<?php echo $config['class']; ?>">
                                                <i class="bi bi-<?php echo $config['icon']; ?> me-1"></i>
                                                <?php echo $config['text']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <strong><?php echo date('d.m.Y', strtotime($cancellation['requested_at'])); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($cancellation['requested_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group-vertical" role="group">
                                                <?php if ($cancellation['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-success btn-sm mb-1" 
                                                            onclick="showActionModal('<?php echo $cancellation['id']; ?>', 'approve', '<?php echo htmlspecialchars($cancellation['reason']); ?>', <?php echo $cancellation['credits_to_refund']; ?>)">
                                                        <i class="bi bi-check me-1"></i>Onayla
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm mb-1" 
                                                            onclick="showActionModal('<?php echo $cancellation['id']; ?>', 'reject', '<?php echo htmlspecialchars($cancellation['reason']); ?>', <?php echo $cancellation['credits_to_refund']; ?>)">
                                                        <i class="bi bi-times me-1"></i>Reddet
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-info btn-sm mb-1" 
                                                            onclick="showDetailsModal('<?php echo htmlspecialchars($cancellation['reason']); ?>', '<?php echo htmlspecialchars($cancellation['admin_notes'] ?? ''); ?>', '<?php echo htmlspecialchars($cancellation['admin_username'] ?? ''); ?>')">
                                                        <i class="bi bi-eye me-1"></i>Detay
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Dosya Detay Butonu -->
                                                <?php
                                                // Admin için dosya detay URL'ini belirle
                                                $detailUrl = '';
                                                $detailTitle = 'Dosya Detayı';
                                                
                                                // Tüm dosya tiplerini yeni universal admin dosya detay sayfasına yönlendir
                                                if (!empty($cancellation['file_id'])) {
                                                    $detailUrl = "file-detail-universal.php?id={$cancellation['file_id']}&type={$cancellation['file_type']}";
                                                    
                                                    switch ($cancellation['file_type']) {
                                                        case 'upload':
                                                            $detailTitle = 'Ana Dosya Detayı (Admin)';
                                                            break;
                                                        case 'response':
                                                            $detailTitle = 'Yanıt Dosyası Detayı (Admin)';
                                                            break;
                                                        case 'revision':
                                                            $detailTitle = 'Revizyon Dosyası Detayı (Admin)';
                                                            break;
                                                        case 'additional':
                                                            $detailTitle = 'Ek Dosya Detayı (Admin)';
                                                            break;
                                                    }
                                                }
                                                ?>
                                                
                                                <?php if (!empty($detailUrl)): ?>
                                                    <a href="<?php echo $detailUrl; ?>" target="_blank" class="btn btn-outline-primary btn-sm" 
                                                       title="<?php echo $detailTitle; ?>">
                                                        <i class="bi bi-external-link-alt me-1"></i>Dosya
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Dosya detayı bulunamadı">
                                                        <i class="bi bi-ban me-1"></i>Dosya
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- İşlem Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="actionForm">
                <div class="modal-body">
                    <input type="hidden" name="cancellation_id" id="actionCancellationId">
                    <input type="hidden" name="action" id="actionType">
                    
                    <div class="mb-3">
                        <label class="form-label">İptal Sebebi:</label>
                        <div class="form-control-plaintext bg-light p-2 rounded" id="actionReason"></div>
                    </div>
                    
                    <div class="mb-3" id="refundInfo" style="display: none;">
                        <label class="form-label">İade Edilecek Kredi:</label>
                        <div class="form-control-plaintext bg-success text-white p-2 rounded" id="actionRefund"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">
                            <span id="adminNotesLabel">Admin Notu:</span>
                            <span class="text-danger" id="adminNotesRequired" style="display: none;">*</span>
                        </label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Bu işlemle ilgili notunuzu yazın..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn" id="actionSubmitBtn">Onayla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detay Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>İptal Talebi Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kullanıcının İptal Sebebi:</label>
                    <div class="form-control-plaintext bg-light p-2 rounded" id="detailsReason"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Admin Notu:</label>
                    <div class="form-control-plaintext bg-light p-2 rounded" id="detailsAdminNotes"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">İşlem Yapan Admin:</label>
                    <div class="form-control-plaintext bg-light p-2 rounded" id="detailsAdmin"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card-body {
    padding: 1.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.user-info strong {
    color: #495057;
}

.file-info {
    max-width: 320px;
}

.file-info .file-type-badge .badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
}

.file-info .cancelled-file-name .file-name-display {
    font-size: 0.9rem;
    border-radius: 0.375rem;
}

.file-info .linked-file .linked-file-display {
    font-size: 0.8rem;
    border-radius: 0.375rem;
}

.file-info .vehicle-info .plate-display .badge {
    font-size: 0.8rem;
    font-weight: 700;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.file-info .file-meta {
    font-size: 0.75rem;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e9ecef;
}

.file-info .file-meta code {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
}

.reason-text {
    max-width: 200px;
    word-wrap: break-word;
}

.btn-group-vertical .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    margin-bottom: 0.25rem;
}

.btn-group-vertical .btn:last-child {
    margin-bottom: 0;
}
</style>

<script>
function showActionModal(cancellationId, action, reason, refundAmount) {
    document.getElementById('actionCancellationId').value = cancellationId;
    document.getElementById('actionType').value = action;
    document.getElementById('actionReason').textContent = reason;
    
    const modal = document.getElementById('actionModal');
    const title = document.getElementById('actionModalTitle');
    const submitBtn = document.getElementById('actionSubmitBtn');
    const adminNotesLabel = document.getElementById('adminNotesLabel');
    const adminNotesRequired = document.getElementById('adminNotesRequired');
    const adminNotesField = document.getElementById('admin_notes');
    const refundInfo = document.getElementById('refundInfo');
    const actionRefund = document.getElementById('actionRefund');
    
    if (action === 'approve') {
        title.innerHTML = '<i class="bi bi-check me-2 text-success"></i>İptal Talebini Onayla';
        submitBtn.className = 'btn btn-success';
        submitBtn.textContent = 'Onayla ve Dosyayı Sil';
        adminNotesLabel.textContent = 'Onay Notu (Opsiyonel):';
        adminNotesRequired.style.display = 'none';
        adminNotesField.required = false;
        adminNotesField.placeholder = 'İptal onayı hakkında not ekleyebilirsiniz...';
        
        if (refundAmount > 0) {
            refundInfo.style.display = 'block';
            actionRefund.innerHTML = '<i class="bi bi-coin me-1"></i>' + refundAmount.toFixed(2) + ' kredi iade edilecek';
        } else {
            refundInfo.style.display = 'none';
        }
    } else {
        title.innerHTML = '<i class="bi bi-times me-2 text-danger"></i>İptal Talebini Reddet';
        submitBtn.className = 'btn btn-danger';
        submitBtn.textContent = 'Reddet';
        adminNotesLabel.textContent = 'Red Sebebi:';
        adminNotesRequired.style.display = 'inline';
        adminNotesField.required = true;
        adminNotesField.placeholder = 'İptal talebinin neden reddedildiğini açıklayın...';
        refundInfo.style.display = 'none';
    }
    
    // Form'u temizle
    adminNotesField.value = '';
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

function showDetailsModal(reason, adminNotes, adminUsername) {
    document.getElementById('detailsReason').textContent = reason;
    document.getElementById('detailsAdminNotes').textContent = adminNotes || 'Belirtilmemiş';
    document.getElementById('detailsAdmin').textContent = adminUsername || 'Bilinmiyor';
    
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
