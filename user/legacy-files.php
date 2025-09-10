<?php
/**
 * Mr ECU - User Panel - Legacy Files (Eski Dosyalarım)
 * 
 * ✔ index.php yapısına birebir uyumlu
 * ✔ user_sidebar.php ile uyumlu
 * ✔ Veri kaybolmuyor
 * ✔ Tasarım modern
 * ✔ Sayfa yukarıda başlıyor
 */

// Cache bypass
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/LegacyFilesManager.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/legacy-files.php');
}

$userId = $_SESSION['user_id'];
$selectedPlate = $_GET['plate'] ?? '';
$error = '';
$success = '';

// Session mesajları
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Filtreleme
$plateSearch = $_GET['plate_search'] ?? '';
$fileTypeFilter = $_GET['file_type'] ?? '';
$sortBy = $_GET['sort'] ?? 'date_desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;

// Değişkenleri tanımla
$singlePlateMode = false;
$userLegacyFiles = [];
$plateFiles = [];
$stats = ['total_plates' => 0, 'total_files' => 0, 'total_size' => 0, 'file_types' => []];
$totalPlates = 0;
$totalPages = 1;

// Veri al
$legacyManager = new LegacyFilesManager($pdo);

try {
    if ($selectedPlate) {
        $plateFiles = $legacyManager->getPlateFiles($userId, $selectedPlate);
        $plateFiles = is_array($plateFiles) ? $plateFiles : [];
        $singlePlateMode = true;
        $userLegacyFiles = [];
    } else {
        $userLegacyFiles = $legacyManager->getUserLegacyFiles($userId, $plateSearch, $fileTypeFilter, $sortBy);
        $userLegacyFiles = is_array($userLegacyFiles) ? $userLegacyFiles : [];
        $totalPlates = count($userLegacyFiles);
        $totalPages = ceil($totalPlates / $limit);
        $offset = ($page - 1) * $limit;
        $userLegacyFiles = array_slice($userLegacyFiles, $offset, $limit);
        $singlePlateMode = false;
        $plateFiles = [];
    }

    // İstatistikler
    $allUserFiles = $legacyManager->getUserLegacyFiles($userId);
    if (is_array($allUserFiles)) {
        $stats['total_plates'] = count($allUserFiles);
        foreach ($allUserFiles as $plateGroup) {
            if (isset($plateGroup['file_count'])) {
                $stats['total_files'] += (int)$plateGroup['file_count'];
                $files = $legacyManager->getPlateFiles($userId, $plateGroup['plate_number']);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (isset($file['file_size'])) $stats['total_size'] += (int)$file['file_size'];
                        $ext = strtoupper(pathinfo($file['original_filename'], PATHINFO_EXTENSION));
                        if ($ext && !in_array($ext, $stats['file_types'])) {
                            $stats['file_types'][] = $ext;
                        }
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log('Legacy Files Error: ' . $e->getMessage());
    $userLegacyFiles = [];
    $plateFiles = [];
    $stats = ['total_plates' => 0, 'total_files' => 0, 'total_size' => 0, 'file_types' => []];
    $singlePlateMode = false;
}

$pageTitle = 'Eski Dosyalarım';

// ✅ GLOBALS ile veri koru
$GLOBALS['legacy_data'] = compact(
    'userId', 'selectedPlate', 'singlePlateMode',
    'userLegacyFiles', 'plateFiles', 'stats',
    'totalPlates', 'totalPages', 'page', 'limit',
    'plateSearch', 'fileTypeFilter', 'sortBy',
    'error', 'success'
);

// ✅ Include'lar
include '../includes/user_header.php';

// ✅ Include'dan sonra veriyi geri al
extract($GLOBALS['legacy_data']);
?>

        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

            <!-- Sayfa Başlığı -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-archive me-2 text-primary"></i>Eski Dosyalarım
                        <?php if ($selectedPlate): ?>
                            <small class="badge bg-info ms-2"><?php echo htmlspecialchars($selectedPlate); ?></small>
                        <?php endif; ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php if ($selectedPlate): ?>
                            <?php echo htmlspecialchars($selectedPlate); ?> plakasına ait eski dosyalarınız
                        <?php else: ?>
                            Eski projenizden aktarılan dosyalarınızı görüntüleyin ve indirin
                        <?php endif; ?>
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($selectedPlate): ?>
                        <a href="legacy-files.php" class="btn btn-outline-primary me-2">
                            <i class="bi bi-arrow-left me-1"></i>Tüm Plakalar
                        </a>
                    <?php endif; ?>
                    <a href="../contact.php" class="btn btn-outline-success">
                        <i class="bi bi-headset me-1"></i>Destek
                    </a>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle me-3 fa-lg"></i>
                        <div><strong>Hata!</strong> <?php echo $error; ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle me-3 fa-lg"></i>
                        <div><strong>Başarılı!</strong> <?php echo $success; ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- İstatistik Kartları -->
            <?php if (!$selectedPlate): ?>
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card modern">
                            <div class="stat-card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number text-primary"><?php echo $stats['total_plates']; ?></div>
                                        <div class="stat-label">Toplam Plaka</div>
                                        <div class="stat-trend"><i class="bi bi-car-front text-primary"></i><span class="text-primary">Araç plakaları</span></div>
                                    </div>
                                    <div class="stat-icon bg-primary"><i class="bi bi-car-front"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card modern">
                            <div class="stat-card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number text-success"><?php echo $stats['total_files']; ?></div>
                                        <div class="stat-label">Toplam Dosya</div>
                                        <div class="stat-trend"><i class="bi bi-file-earmark text-success"></i><span class="text-success">Eski dosyalar</span></div>
                                    </div>
                                    <div class="stat-icon bg-success"><i class="bi bi-file-earmark"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card modern">
                            <div class="stat-card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number text-info"><?php echo count($stats['file_types']); ?></div>
                                        <div class="stat-label">Dosya Tipi</div>
                                        <div class="stat-trend"><i class="bi bi-tags text-info"></i><span class="text-info">Farklı format</span></div>
                                    </div>
                                    <div class="stat-icon bg-info"><i class="bi bi-tags"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card modern">
                            <div class="stat-card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number text-warning">
                                            <?php 
                                            if (function_exists('formatFileSize')) {
                                                echo formatFileSize($stats['total_size']);
                                            } else {
                                                echo round($stats['total_size'] / 1024 / 1024, 1) . ' MB';
                                            }
                                            ?>
                                        </div>
                                        <div class="stat-label">Toplam Boyut</div>
                                        <div class="stat-trend"><i class="bi bi-hdd text-warning"></i><span class="text-warning">Disk kullanımı</span></div>
                                    </div>
                                    <div class="stat-icon bg-warning"><i class="bi bi-hdd"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filtreleme -->
            <?php if (!$selectedPlate): ?>
                <div class="filter-card mb-4">
                    <div class="filter-header">
                        <h6 class="mb-0"><i class="bi bi-filter me-2"></i>Plaka Ara ve Filtrele</h6>
                    </div>
                    <div class="filter-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label><i class="bi bi-search me-1"></i>Plaka Ara</label>
                                <input type="text" name="plate_search" value="<?php echo htmlspecialchars($plateSearch); ?>" 
                                       placeholder="34ABC123..." class="form-control form-control-modern">
                            </div>
                            <div class="col-md-2">
                                <label><i class="bi bi-sort-down me-1"></i>Sıralama</label>
                                <select name="sort" class="form-select form-control-modern">
                                    <option value="date_desc" <?= $sortBy === 'date_desc' ? 'selected' : '' ?>>Yeni → Eski</option>
                                    <option value="date_asc" <?= $sortBy === 'date_asc' ? 'selected' : '' ?>>Eski → Yeni</option>
                                    <option value="plate_asc" <?= $sortBy === 'plate_asc' ? 'selected' : '' ?>>Plaka A → Z</option>
                                    <option value="plate_desc" <?= $sortBy === 'plate_desc' ? 'selected' : '' ?>>Plaka Z → A</option>
                                    <option value="files_desc" <?= $sortBy === 'files_desc' ? 'selected' : '' ?>>Dosya Sayısı ↓</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-modern mt-35"><i class="bi bi-search me-1"></i>Filtrele</button>
                                <a href="legacy-files.php" class="btn btn-outline-secondary btn-modern mt-35"><i class="bi bi-arrow-counterclockwise me-1"></i>Temizle</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- İçerik -->
            <?php if ($selectedPlate): ?>
                <?php if (empty($plateFiles)): ?>
                    <div class="empty-state-card">
                        <div class="empty-content">
                            <i class="bi bi-exclamation-triangle fa-3x text-warning"></i>
                            <h4><?php echo htmlspecialchars($selectedPlate); ?> - Dosya Yok</h4>
                            <p>Bu plakaya ait dosya bulunamadı.</p>
                            <a href="legacy-files.php" class="btn btn-primary"><i class="bi bi-arrow-left me-2"></i>Tüm Plakalar</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover files-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-file-earmark"></i></th>
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Tip</th>
                                    <th>Tarih</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plateFiles as $file): ?>
                                    <tr>
                                        <td><i class="bi bi-file-earmark text-primary"></i></td>
                                        <td><?php echo htmlspecialchars($file['original_filename']); ?></td>
                                        <td><?= formatFileSize($file['file_size']) ?? round($file['file_size']/1024,1).' KB' ?></td>
                                        <td><span class="badge bg-secondary"><?= strtoupper(pathinfo($file['original_filename'], PATHINFO_EXTENSION)) ?></span></td>
                                        <td><?= date('d.m.Y H:i', strtotime($file['upload_date'])) ?></td>
                                        <td>
                                            <a href="download-legacy-file.php?id=<?= $file['id'] ?>" class="btn btn-success btn-sm"><i class="bi bi-download"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if (empty($userLegacyFiles)): ?>
                    <div class="empty-state-card">
                        <div class="empty-content">
                            <i class="bi bi-archive fa-3x text-muted"></i>
                            <h4>Filtreye Uygun Plaka Yok</h4>
                            <p>Arama kriterlerinize uygun plaka bulunamadı.</p>
                            <a href="legacy-files.php" class="btn btn-primary">Tüm Plakalar</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4 mb-4">
                        <?php foreach ($userLegacyFiles as $plate): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="legacy-plate-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="plate-number mb-1">
                                                <i class="bi bi-car-front me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($plate['plate_number']); ?>
                                            </h5>
                                            <span class="badge bg-primary"><?= $plate['file_count'] ?> dosya</span>
                                        </div>
                                        <div class="plate-info mb-3">
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <i class="bi bi-files text-info"></i><br><small>Dosya</small><br><strong><?= $plate['file_count'] ?></strong>
                                                </div>
                                                <div class="col-6">
                                                    <?php
                                                    $size = 0;
                                                    $files = $legacyManager->getPlateFiles($userId, $plate['plate_number']);
                                                    foreach ($files as $f) $size += $f['file_size'] ?? 0;
                                                    ?>
                                                    <i class="bi bi-hdd text-warning"></i><br><small>Boyut</small><br><strong><?= formatFileSize($size) ?? '0 KB' ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="?plate=<?= urlencode($plate['plate_number']) ?>" class="btn btn-primary w-100"><i class="bi bi-eye me-1"></i>Görüntüle</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Sayfalama -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&plate_search=<?= urlencode($plateSearch) ?>&file_type=<?= urlencode($fileTypeFilter) ?>&sort=<?= $sortBy ?>">&laquo;</a></li>
                                <?php endif; ?>
                                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&plate_search=<?= urlencode($plateSearch) ?>&file_type=<?= urlencode($fileTypeFilter) ?>&sort=<?= $sortBy ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&plate_search=<?= urlencode($plateSearch) ?>&file_type=<?= urlencode($fileTypeFilter) ?>&sort=<?= $sortBy ?>">&raquo;</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Yardım -->
            <?php if (!$selectedPlate && !empty($userLegacyFiles)): ?>
                <div class="card mt-4" style="background: #f8f9fa; border-radius: 12px;">
                    <div class="card-body">
                        <p><strong>Eski Dosyalar:</strong> Eski projenizden aktarılan dosyalar bu alanda yer alır.</p>
                        <p><strong>İndirme:</strong> Dosyaları görüntülemek için Görüntüle butonuna tıklayın ve açılan sayfadan dosyayı indirin.</p>
                    </div>
                </div>
            <?php endif; ?>

        </main>

<!-- CSS (index.php uyumlu) -->
<style>
    /* Sadece legacy özel stiller */
    .legacy-plate-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: none;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .legacy-plate-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }
    .plate-number {
        font-weight: 700;
        color: #495057;
    }
    .files-table th {
        background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .files-table td {
        vertical-align: middle;
        padding: 1rem;
    }
    .empty-state-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        padding: 4rem 2rem;
        text-align: center;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('plate_search');
        if (input) input.addEventListener('input', e => e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ''));
    });
</script>

<?php include '../includes/user_footer.php'; ?>