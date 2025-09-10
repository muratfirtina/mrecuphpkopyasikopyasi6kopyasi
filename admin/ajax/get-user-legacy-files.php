<?php
/**
 * Mr ECU - Admin Ajax - Get User Legacy Files
 * Kullanıcının eski dosyalarını getiren Ajax
 */

header('Content-Type: text/html; charset=utf-8');

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/LegacyFilesManager.php';

// Admin kontrolü
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo '<p class="text-danger">Yetkiniz yok.</p>';
    exit;
}

$userId = $_POST['user_id'] ?? '';

if (empty($userId) || !isValidUUID($userId)) {
    echo '<p class="text-danger">Geçersiz kullanıcı ID.</p>';
    exit;
}

$legacyManager = new LegacyFilesManager($pdo);
$userFiles = $legacyManager->getUserLegacyFiles($userId);

// Güvenlik kontrolü
if (!is_array($userFiles)) {
    echo '<p class="text-danger">Veri yükleme hatası oluştu.</p>';
    exit;
}

if (empty($userFiles)): ?>
    <p class="text-muted">Bu kullanıcının henüz eski dosyası bulunmuyor.</p>
<?php else: ?>
    <?php foreach ($userFiles as $plateGroup): ?>
        <div class="card mb-2">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?php echo htmlspecialchars($plateGroup['plate_number']); ?></strong>
                <span class="badge bg-primary"><?php echo $plateGroup['file_count']; ?> dosya</span>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    Son yükleme: <?php echo date('d.m.Y H:i', strtotime($plateGroup['last_upload'])); ?>
                </small>
                <br>
                <a href="legacy-files-detail.php?user_id=<?php echo $userId; ?>&plate=<?php echo urlencode($plateGroup['plate_number']); ?>" 
                   class="btn btn-sm btn-outline-primary mt-2">
                    Dosyaları Görüntüle
                </a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
