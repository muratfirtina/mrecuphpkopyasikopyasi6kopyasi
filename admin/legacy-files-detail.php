<?php
/**
 * Mr ECU - Admin Panel - Legacy Files Detail
 * Kullanıcının eski dosyalarının detayı
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/LegacyFilesManager.php';

// Admin kontrolü
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$legacyManager = new LegacyFilesManager($pdo);
$user = new User($pdo);

// Sistem kurulmuş mu kontrol et
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'legacy_files'");
    if ($stmt->rowCount() === 0) {
        redirect('setup-legacy-files.php');
    }
} catch (PDOException $e) {
    redirect('setup-legacy-files.php');
}

$userId = $_GET['user_id'] ?? '';
$plateNumber = $_GET['plate'] ?? '';
$message = '';
$messageType = '';

if (empty($userId) || !isValidUUID($userId)) {
    redirect('legacy-files.php');
}

// Kullanıcı bilgilerini al
$userData = $user->getUserById($userId);
if (!$userData || $userData['role'] !== 'user') {
    redirect('legacy-files.php');
}

// Dosya silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    $fileId = $_POST['file_id'] ?? '';
    if (!empty($fileId)) {
        $result = $legacyManager->deleteFile($fileId, $_SESSION['user_id']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

// Kullanıcının tüm eski dosyalarını al
$userFiles = $legacyManager->getUserLegacyFiles($userId);

// Güvenlik kontrolü
if (!is_array($userFiles)) {
    $userFiles = [];
}

// Eğer belirli bir plaka seçildiyse o plakaya ait dosyaları al
$plateFiles = [];
if (!empty($plateNumber)) {
    $plateFiles = $legacyManager->getPlateFiles($userId, $plateNumber);
    if (!is_array($plateFiles)) {
        $plateFiles = [];
    }
}

// Sayfa bilgileri
$pageTitle = 'Eski Dosyalar Yönetimi';
$pageDescription = 'Kullanıcı eski dosyaları detayı';
$pageIcon = 'bi bi-archive';

include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    Eski Dosyalar - <?php echo htmlspecialchars($userData['username']); ?>
                    <?php if ($plateNumber): ?>
                        / <?php echo htmlspecialchars($plateNumber); ?>
                    <?php endif; ?>
                </h1>
                <a href="legacy-files.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Kullanıcı Bilgileri -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Kullanıcı Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Kullanıcı Adı:</strong><br>
                            <?php echo htmlspecialchars($userData['username']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($userData['email']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Ad Soyad:</strong><br>
                            <?php echo htmlspecialchars(trim($userData['first_name'] . ' ' . $userData['last_name'])); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Toplam Dosya:</strong><br>
                            <span class="badge bg-primary fs-6"><?php echo count($userFiles); ?> plaka</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($plateNumber)): ?>
                <!-- Plaka Grupları -->
                <div class="card">
                    <div class="card-header">
                        <h5>Plaka Grupları</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userFiles)): ?>
                            <div class="alert alert-info">
                                Bu kullanıcının henüz yüklenmiş eski dosyası bulunmuyor.
                                <br><br>
                                <a href="legacy-files.php?select_user=<?php echo $userId; ?>" class="btn btn-primary">
                                    Dosya Yükle
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($userFiles as $plateGroup): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <strong><?php echo htmlspecialchars($plateGroup['plate_number']); ?></strong>
                                                <span class="badge bg-primary"><?php echo $plateGroup['file_count']; ?> dosya</span>
                                            </div>
                                            <div class="card-body">
                                                <small class="text-muted">
                                                    Son yükleme: <?php echo date('d.m.Y H:i', strtotime($plateGroup['last_upload'])); ?>
                                                </small>
                                                <br><br>
                                                <a href="?user_id=<?php echo $userId; ?>&plate=<?php echo urlencode($plateGroup['plate_number']); ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i> Dosyaları Görüntüle
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Belirli Plakaya Ait Dosyalar -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?php echo htmlspecialchars($plateNumber); ?> Plakasına Ait Dosyalar</h5>
                        <a href="?user_id=<?php echo $userId; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list"></i> Tüm Plakalar
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($plateFiles)): ?>
                            <div class="alert alert-warning">
                                Bu plakaya ait dosya bulunamadı.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Dosya Adı</th>
                                            <th>Boyut</th>
                                            <th>Tip</th>
                                            <th>Yükleyen Admin</th>
                                            <th>Yükleme Tarihi</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($plateFiles as $file): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-file"></i>
                                                    <?php echo htmlspecialchars($file['original_filename']); ?>
                                                </td>
                                                <td><?php echo formatFileSize($file['file_size']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($file['file_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($file['uploaded_by_name'] ?? 'Bilinmiyor'); ?></td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($file['upload_date'])); ?></td>
                                                <td>
                                                    <a href="download-legacy-file.php?id=<?php echo $file['id']; ?>" 
                                                       class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="deleteFile('<?php echo $file['id']; ?>', '<?php echo htmlspecialchars($file['original_filename']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Silme Modalı -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dosya Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu dosyayı silmek istediğinizden emin misiniz?</p>
                <p><strong id="deleteFileName"></strong></p>
                <p class="text-danger">Bu işlem geri alınamaz!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="file_id" id="deleteFileId">
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteFile(fileId, fileName) {
    document.getElementById('deleteFileId').value = fileId;
    document.getElementById('deleteFileName').textContent = fileName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>
