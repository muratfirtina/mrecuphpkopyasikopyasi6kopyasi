<?php
/**
 * Mr ECU - Admin Bildirimler Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php?error=access_denied');
}

// Gerekli dosyaları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}

// Notification config ve manager
require_once '../config/notification_config.php';
require_once '../includes/NotificationManager.php';

$notificationManager = new NotificationManager($pdo);
$error = '';
$success = '';

// Bildirim tablosunun olup olmadığını kontrol et
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $notificationsTableExists = $stmt->rowCount() > 0;
} catch(PDOException $e) {
    $notificationsTableExists = false;
}

// Eğer tablo yoksa kurulum yönlendirmesi
if (!$notificationsTableExists) {
    $error = 'Bildirim sistemi kurulu değil. Lütfen kurulum scriptini çalıştırın.';
    $installUrl = '../config/install-notifications.php';
}

// Bildirim işlemleri (sadece tablo varsa)
if ($notificationsTableExists && $_POST) {
    if (isset($_POST['mark_read']) && isset($_POST['notification_ids'])) {
        $count = 0;
        foreach ($_POST['notification_ids'] as $notificationId) {
            if ($notificationManager->markAsRead($notificationId, $_SESSION['user_id'])) {
                $count++;
            }
        }
        $success = "{$count} bildirim okundu olarak işaretlendi.";
    }
    
    if (isset($_POST['mark_all_read'])) {
        if ($notificationManager->markAllAsRead($_SESSION['user_id'])) {
            $success = 'Tüm bildirimler okundu olarak işaretlendi.';
        } else {
            $error = 'Bildirimler işaretlenirken hata oluştu.';
        }
    }
}

// Filtreleme parametreleri
$filter = $_GET['filter'] ?? 'all'; // all, unread, read
$type = $_GET['type'] ?? 'all'; // all, file_upload, revision_request, etc.
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = defined('NOTIFICATION_PAGE_LIMIT') ? NOTIFICATION_PAGE_LIMIT : 25;
$offset = ($page - 1) * $perPage;

$notifications = [];
$totalNotifications = 0;
$totalPages = 0;
$unreadCount = 0;

// Bildirimler verilerini getir (sadece tablo varsa)
if ($notificationsTableExists) {
    try {
        // Okunmamış sayısını al
        $unreadCount = $notificationManager->getUnreadCount($_SESSION['user_id']);
        
        // Toplam bildirim sayısı
        $countQuery = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
        $countParams = [$_SESSION['user_id']];

        if ($filter === 'unread') {
            $countQuery .= " AND is_read = FALSE";
        } elseif ($filter === 'read') {
            $countQuery .= " AND is_read = TRUE";
        }

        if ($type !== 'all') {
            $countQuery .= " AND type = ?";
            $countParams[] = $type;
        }

        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalNotifications = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalNotifications / $perPage);

        // Bildirimleri getir
        $query = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$_SESSION['user_id']];

        if ($filter === 'unread') {
            $query .= " AND is_read = FALSE";
        } elseif ($filter === 'read') {
            $query .= " AND is_read = TRUE";
        }

        if ($type !== 'all') {
            $query .= " AND type = ?";
            $params[] = $type;
        }

        // LIMIT ve OFFSET için integer kontrol ve direkt sorguya ekleme
        $limit = intval($perPage);
        $offset = intval($offset);
        $query .= " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error = 'Bildirimler alınırken hata oluştu: ' . $e->getMessage();
    }
}

// Bildirim türü adları
$typeNames = [
    'file_upload' => 'Dosya Yükleme',
    'file_status_update' => 'Dosya Durum Güncelleme',
    'revision_request' => 'Revize Talebi',
    'revision_response' => 'Revize Yanıtı',
    'system' => 'Sistem',
    'system_warning' => 'Sistem Uyarısı',
    'user_registration' => 'Kullanıcı Kaydı',
    'credit' => 'Kredi',
    'credit_update' => 'Kredi Güncelleme',
    'admin_message' => 'Admin Mesajı'
];

// Sayfa bilgileri
$pageTitle = 'Bildirimler';
$pageDescription = 'Admin panel bildirimleri ve sistem uyarıları';
$pageIcon = 'bi bi-bell';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        
        <?php if (isset($installUrl)): ?>
            <div class="mt-3">
                <a href="<?php echo $installUrl; ?>" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-gear me-1"></i>Kurulum Scriptini Çalıştır
                </a>
                <a href="../config/check-notification-system.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-check me-1"></i>Sistem Durumunu Kontrol Et
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($notificationsTableExists): ?>

<!-- İstatistik Kartları -->
<div class="row g-4 mb-4">
    <a class="col-lg-3 col-md-6" href="notifications.php" style="text-decoration: none; outline: none;">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format($totalNotifications); ?></div>
                    <div class="stat-label">Toplam Bildirim</div>
                    <small class="text-muted">Tüm bildirimler</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="bi bi-bell text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </a>
    
    <a class="col-lg-3 col-md-6" href="notifications.php?filter=unread" style="text-decoration: none; outline: none;">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo number_format($unreadCount); ?></div>
                    <div class="stat-label">Okunmamış</div>
                    <small class="text-muted">Bekleyen bildirimler</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="bi bi-bell-slash text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </a>
    
    <a class="col-lg-3 col-md-6" href="notifications.php?filter=read" style="text-decoration: none; outline: none;">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format($totalNotifications - $unreadCount); ?></div>
                    <div class="stat-label">Okunmuş</div>
                    <small class="text-muted">İşlenmiş bildirimler</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="bi bi-check-circle text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </a>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo count(array_unique(array_column($notifications, 'type'))); ?></div>
                    <div class="stat-label">Bildirim Türü</div>
                    <small class="text-muted">Farklı tür sayısı</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="bi bi-tags text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtre ve Arama -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="filter" class="form-label">
                    <i class="bi bi-filter me-1"></i>Durum Filtresi
                </label>
                <select class="form-select" id="filter" name="filter" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tüm Bildirimler</option>
                    <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Okunmamış</option>
                    <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Okunmuş</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="type" class="form-label">
                    <i class="bi bi-tag me-1"></i>Bildirim Türü
                </label>
                <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>Tüm Türler</option>
                    <?php foreach ($typeNames as $typeKey => $typeName): ?>
                        <option value="<?php echo $typeKey; ?>" <?php echo $type === $typeKey ? 'selected' : ''; ?>>
                            <?php echo $typeName; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Filtrele
                    </button>
                    <a href="notifications.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Sıfırla
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bildirimler -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-bell me-2"></i>
            Bildirimler
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-warning ms-2"><?php echo $unreadCount; ?> okunmamış</span>
            <?php endif; ?>
        </h5>
        <div>
            <?php if ($unreadCount > 0): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-sm btn-success" 
                            onclick="return confirm('Tüm bildirimler okundu olarak işaretlenecek. Emin misiniz?')">
                        <i class="bi bi-check-double me-1"></i>Tümünü Okundu İşaretle
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($filter !== 'all' || $type !== 'all'): ?>
                        Filtreye uygun bildirim bulunamadı
                    <?php else: ?>
                        Henüz bildirim bulunmuyor
                    <?php endif; ?>
                </h6>
                <p class="text-muted">Sistem etkinlikleri burada görüntülenecek.</p>
            </div>
        <?php else: ?>
            <form method="POST" id="notificationForm">
                <div class="table-responsive">
                    <table class="table table-admin table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll()">
                                </th>
                                <th>Bildirim</th>
                                <th width="120">Tür</th>
                                <th width="150">Tarih</th>
                                <th width="100">Durum</th>
                                <th width="120">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                                <tr class="<?php echo !$notification['is_read'] ? 'table-warning' : ''; ?>">
                                    <td>
                                        <input type="checkbox" name="notification_ids[]" 
                                               value="<?php echo $notification['id']; ?>" 
                                               class="form-check-input notification-checkbox" 
                                               onchange="updateActionButtons()">
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : 'fw-normal'; ?>">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary ms-1">Yeni</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="text-muted mb-0 small">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($notification['type']) {
                                                case 'file_upload': echo 'warning'; break;
                                                case 'file_status_update': echo 'primary'; break;
                                                case 'revision_request': echo 'danger'; break;
                                                case 'revision_response': echo 'success'; break;
                                                case 'user_registration': echo 'success'; break;
                                                case 'system_warning': echo 'danger'; break;
                                                case 'admin_message': echo 'info'; break;
                                                case 'credit': echo 'warning'; break;
                                                case 'credit_update': echo 'info'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo $typeNames[$notification['type']] ?? 'Diğer'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo date('d.m.Y', strtotime($notification['created_at'])); ?></strong><br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($notification['is_read']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check me-1"></i>Okundu
                                            </span>
                                            <?php if ($notification['read_at']): ?>
                                                <br><small class="text-muted"><?php echo date('d.m H:i', strtotime($notification['read_at'])); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="bi bi-clock me-1"></i>Okunmamış
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical btn-group-sm">
                                            <?php if ($notification['action_url']): ?>
                                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                                   class="btn btn-outline-primary btn-sm"
                                                   onclick="markAsRead('<?php echo $notification['id']; ?>')">
                                                    <i class="bi bi-external-link-alt me-1"></i>Görüntüle
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!$notification['is_read']): ?>
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="markSingleAsRead('<?php echo $notification['id']; ?>')">
                                                    <i class="bi bi-check me-1"></i>Okundu İşaretle
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Toplu İşlemler -->
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" name="mark_read" class="btn btn-success btn-sm" 
                                    id="markSelectedBtn" disabled>
                                <i class="bi bi-check me-1"></i>Seçilenleri Okundu İşaretle (<span id="selectedCount">0</span>)
                            </button>
                        </div>
                        <div class="text-muted small">
                            <?php 
                            $start = $offset + 1;
                            $end = min($offset + $perPage, $totalNotifications);
                            echo "Gösterilen: {$start} - {$end} / {$totalNotifications}";
                            ?>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Bildirim sayfalama">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&type=<?php echo $type; ?>&page=<?php echo $page - 1; ?>">Önceki</a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&type=<?php echo $type; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&type=<?php echo $type; ?>&page=<?php echo $page + 1; ?>">Sonraki</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- Kurulum Gerekli Uyarısı -->
<div class="card admin-card">
    <div class="card-body text-center py-5">
        <i class="bi bi-gear fa-4x text-muted mb-4"></i>
        <h4>Bildirim Sistemi Kurulum Gerekli</h4>
        <p class="text-muted mb-4">Bildirim sistemini kullanabilmek için önce kurulum yapmanız gerekiyor.</p>
        
        <div class="d-flex justify-content-center gap-3">
            <a href="../config/check-notification-system.php" class="btn btn-outline-primary">
                <i class="bi bi-check me-1"></i>Sistem Durumunu Kontrol Et
            </a>
            <a href="../config/install-notifications.php" class="btn btn-primary">
                <i class="bi bi-play me-1"></i>Kurulum Scriptini Çalıştır
            </a>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
let selectAllChecked = false;

function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    selectAllChecked = selectAllCheckbox.checked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllChecked;
    });
    
    updateActionButtons();
}

function updateActionButtons() {
    const checkboxes = document.querySelectorAll('.notification-checkbox:checked');
    const markSelectedBtn = document.getElementById('markSelectedBtn');
    const selectedCount = document.getElementById('selectedCount');
    
    const count = checkboxes.length;
    markSelectedBtn.disabled = count === 0;
    selectedCount.textContent = count;
}

function markSingleAsRead(notificationId) {
    fetch('ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Bildirim işaretlenemedi: ' + (data.message || 'Bilinmeyen hata'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu.');
    });
}

function markAsRead(notificationId) {
    // Link tıklandığında okundu olarak işaretle
    fetch('ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
    return true;
}

// Sayfa yüklendiğinde buton durumunu kontrol et
document.addEventListener('DOMContentLoaded', function() {
    updateActionButtons();
});
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
