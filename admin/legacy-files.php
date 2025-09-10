<?php
/**
 * Mr ECU - Admin Panel - Legacy Files Management
 * Eski dosyaları yönetme sayfası
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
        // Tablo yok, kurulum sayfasına yönlendir
        redirect('setup-legacy-files.php');
    }
} catch (PDOException $e) {
    // Hata durumunda kurulum sayfasına yönlendir
    redirect('setup-legacy-files.php');
}

$message = '';
$messageType = '';
$selectedUserId = '';
$selectedPlateNumber = '';

// Formdan gelen işlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_files':
                $userId = $_POST['user_id'] ?? '';
                $plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
                
                if (empty($userId) || empty($plateNumber)) {
                    $message = 'Kullanıcı ve plaka seçimi zorunludur.';
                    $messageType = 'error';
                } elseif (!$legacyManager->userExists($userId)) {
                    $message = 'Seçilen kullanıcı bulunamadı.';
                    $messageType = 'error';
                } elseif (empty($_FILES['legacy_files']['tmp_name'][0])) {
                    $message = 'En az bir dosya seçmelisiniz.';
                    $messageType = 'error';
                } else {
                    $result = $legacyManager->uploadFileForUser($userId, $plateNumber, $_FILES['legacy_files'], $_SESSION['user_id']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                    
                    if ($result['success']) {
                        $selectedUserId = $userId;
                        $selectedPlateNumber = $plateNumber;
                    }
                }
                break;
                
            case 'delete_file':
                $fileId = $_POST['file_id'] ?? '';
                if (!empty($fileId)) {
                    $result = $legacyManager->deleteFile($fileId, $_SESSION['user_id']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
        }
    }
}

// Kullanıcı listesi
$users = $user->getAllUsers(1, 1000);
$usersWithFiles = $legacyManager->getAllUsersWithLegacyFiles();
$stats = $legacyManager->getStats();

// Güvenlik kontrolleri
if (!is_array($users)) {
    $users = [];
}
if (!is_array($usersWithFiles)) {
    $usersWithFiles = [];
}
if (!is_array($stats)) {
    $stats = ['total_files' => 0, 'users_with_files' => 0, 'total_plates' => 0, 'total_size' => 0];
}

// Seçilen kullanıcının dosyaları
$userFiles = [];
if (!empty($selectedUserId)) {
    $userFiles = $legacyManager->getUserLegacyFiles($selectedUserId);
    if (!is_array($userFiles)) {
        $userFiles = [];
    }
}

// Sayfa bilgileri
$pageTitle = 'Eski Dosyalar Yönetimi';
$pageDescription = 'Kullanıcılara eski dosya yükleme ve yönetimi';
$pageIcon = 'bi bi-archive';

include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="row g-4 mb-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Eski Dosyalar Yönetimi</h1>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- İstatistikler -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Toplam Dosya</h5>
                            <h3><?php echo number_format($stats['total_files']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Dosyası Olan Kullanıcı</h5>
                            <h3><?php echo number_format($stats['users_with_files']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Toplam Plaka</h5>
                            <h3><?php echo number_format($stats['total_plates']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Toplam Boyut</h5>
                            <h3><?php echo formatFileSize($stats['total_size']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Dosya Yükleme Formu -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Yeni Dosya Yükle</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                <input type="hidden" name="action" value="upload_files">
                                
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Kullanıcı Seç *</label>
                                    <select class="form-select" id="user_id" name="user_id" required onchange="loadUserFiles()">
                                        <option value="">-- Kullanıcı Seçin --</option>
                                        <?php foreach ($users as $u): ?>
                                            <?php if ($u['role'] === 'user'): ?>
                                                <option value="<?php echo $u['id']; ?>" 
                                                        <?php echo $selectedUserId === $u['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($u['username'] . ' (' . $u['email'] . ')'); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="plate_number" class="form-label">Araç Plakası *</label>
                                    <input type="text" class="form-control" id="plate_number" name="plate_number" 
                                           value="<?php echo htmlspecialchars($selectedPlateNumber); ?>"
                                           placeholder="34ABC123" required>
                                    <div class="form-text">Plaka harfleri büyük yazılacaktır.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="legacy_files" class="form-label">Dosyalar *</label>
                                    <input type="file" class="form-control" id="legacy_files" name="legacy_files[]" 
                                           multiple required>
                                    <div class="form-text">Birden fazla dosya seçebilirsiniz. Tüm dosya tiplerini destekler.</div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Dosyaları Yükle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Kullanıcı Dosyaları -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Seçilen Kullanıcının Dosyaları</h5>
                        </div>
                        <div class="card-body" id="userFilesContainer">
                            <?php if (empty($userFiles)): ?>
                                <p class="text-muted">Kullanıcı seçin ve dosyalarını görün.</p>
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
                                            <a href="legacy-files-detail.php?user_id=<?php echo $selectedUserId; ?>&plate=<?php echo urlencode($plateGroup['plate_number']); ?>" 
                                               class="btn btn-sm btn-outline-primary mt-2">
                                                Dosyaları Görüntüle
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tüm Kullanıcılar Tablosu -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Tüm Kullanıcılar ve Dosya Durumları</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>Email</th>
                                    <th>Ad Soyad</th>
                                    <th>Dosya Sayısı</th>
                                    <th>Plaka Sayısı</th>
                                    <th>Son Yükleme</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersWithFiles as $userWithFiles): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($userWithFiles['username']); ?></td>
                                        <td><?php echo htmlspecialchars($userWithFiles['email']); ?></td>
                                        <td><?php echo htmlspecialchars(trim($userWithFiles['first_name'] . ' ' . $userWithFiles['last_name'])); ?></td>
                                        <td>
                                            <?php if ($userWithFiles['legacy_file_count'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $userWithFiles['legacy_file_count']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($userWithFiles['plate_count'] > 0): ?>
                                                <span class="badge bg-info"><?php echo $userWithFiles['plate_count']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($userWithFiles['last_upload']): ?>
                                                <?php echo date('d.m.Y H:i', strtotime($userWithFiles['last_upload'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="selectUser('<?php echo $userWithFiles['id']; ?>', '<?php echo htmlspecialchars($userWithFiles['username']); ?>')">
                                                Seç
                                            </button>
                                            <?php if ($userWithFiles['legacy_file_count'] > 0): ?>
                                                <a href="legacy-files-detail.php?user_id=<?php echo $userWithFiles['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    Detay
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function selectUser(userId, username) {
    document.getElementById('user_id').value = userId;
    loadUserFiles();
}

function loadUserFiles() {
    const userId = document.getElementById('user_id').value;
    const container = document.getElementById('userFilesContainer');
    
    if (!userId) {
        container.innerHTML = '<p class="text-muted">Kullanıcı seçin ve dosyalarını görün.</p>';
        return;
    }
    
    container.innerHTML = '<p class="text-muted">Yükleniyor...</p>';
    
    fetch('ajax/get-user-legacy-files.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + encodeURIComponent(userId)
    })
    .then(response => response.text())
    .then(data => {
        container.innerHTML = data;
    })
    .catch(error => {
        container.innerHTML = '<p class="text-danger">Dosyalar yüklenirken hata oluştu.</p>';
        console.error('Error:', error);
    });
}

// Sayfa yüklendiğinde seçili kullanıcının dosyalarını yükle
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($selectedUserId): ?>
        loadUserFiles();
    <?php endif; ?>
});
</script>

<?php include '../includes/admin_footer.php'; ?>
