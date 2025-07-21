<?php
/**
 * Mr ECU - Admin Notifications Page
 * Admin Bildirimler Sayfası
 */

$pageTitle = 'Bildirimler';
$pageDescription = 'Admin panel bildirimleri ve sistem uyarıları';

// Gerekli dosyaları dahil et
require_once '../includes/admin_header.php';

// NotificationManager'ı dahil et
if (!class_exists('NotificationManager')) {
    require_once '../includes/NotificationManager.php';
}

$notificationManager = new NotificationManager($pdo);

// Filtreleme parametreleri
$filter = $_GET['filter'] ?? 'all'; // all, unread, read
$type = $_GET['type'] ?? 'all'; // all, file_upload, revision_request, user_registration, system_warning
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Bildirim işlemleri
if ($_POST) {
    if (isset($_POST['mark_read']) && isset($_POST['notification_ids'])) {
        foreach ($_POST['notification_ids'] as $notificationId) {
            $notificationManager->markAsRead($notificationId, $_SESSION['user_id']);
        }
        $successMessage = 'Seçilen bildirimler okundu olarak işaretlendi.';
    }
    
    if (isset($_POST['mark_all_read'])) {
        $notificationManager->markAllAsRead($_SESSION['user_id']);
        $successMessage = 'Tüm bildirimler okundu olarak işaretlendi.';
    }
}

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

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bildirim türü adları
$typeNames = [
    'file_upload' => 'Dosya Yükleme',
    'revision_request' => 'Revize Talebi',
    'user_registration' => 'Kullanıcı Kaydı',
    'system_warning' => 'Sistem Uyarısı',
    'file_status_update' => 'Dosya Durumu',
    'revision_response' => 'Revize Yanıtı'
];
?>

<div class="col-md-12">
    <div class="admin-content p-4">
        <!-- Başlık ve Eylemler -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Bildirimler</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Bildirimler</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <form method="post" class="d-inline">
                        <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                            <i class="fas fa-check-double me-1"></i>Tümünü Okundu İşaretle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filtreler -->
        <div class="card admin-card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="card-title">Filtreler</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="?filter=all&type=<?php echo $type; ?>&page=1" 
                               class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                Tümü (<?php echo $totalNotifications; ?>)
                            </a>
                            <a href="?filter=unread&type=<?php echo $type; ?>&page=1" 
                               class="btn btn-sm <?php echo $filter === 'unread' ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                                Okunmamış (<?php echo $notificationManager->getUnreadCount($_SESSION['user_id']); ?>)
                            </a>
                            <a href="?filter=read&type=<?php echo $type; ?>&page=1" 
                               class="btn btn-sm <?php echo $filter === 'read' ? 'btn-success' : 'btn-outline-secondary'; ?>">
                                Okunmuş
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="card-title">Bildirim Türü</h6>
                        <select class="form-select form-select-sm" onchange="location.href='?filter=<?php echo $filter; ?>&type=' + this.value + '&page=1'">
                            <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>Tüm Türler</option>
                            <?php foreach ($typeNames as $typeKey => $typeName): ?>
                            <option value="<?php echo $typeKey; ?>" <?php echo $type === $typeKey ? 'selected' : ''; ?>><?php echo $typeName; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bildirimler -->
        <form method="post" id="notificationForm">
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Bildirimler</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleSelectAll()">
                            <i class="fas fa-check-square me-1"></i>Tümünü Seç
                        </button>
                        <button type="submit" name="mark_read" class="btn btn-sm btn-success" disabled id="markReadBtn">
                            <i class="fas fa-check me-1"></i>Seçilenleri Okundu İşaretle
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Bildirim Bulunamadı</h5>
                        <p class="text-muted">Seçilen kriterlere uygun bildirim bulunmuyor.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                    <div class="border-bottom p-3 <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <input type="checkbox" name="notification_ids[]" value="<?php echo $notification['id']; ?>" 
                                       class="form-check-input notification-checkbox" onchange="updateMarkReadButton()">
                            </div>
                            <div class="me-3">
                                <div class="<?php 
                                    switch($notification['type']) {
                                        case 'user_registration':
                                            echo 'bg-success bg-opacity-10 p-2 rounded-circle';
                                            break;
                                        case 'file_upload':
                                            echo 'bg-warning bg-opacity-10 p-2 rounded-circle';
                                            break;
                                        case 'revision_request':
                                            echo 'bg-info bg-opacity-10 p-2 rounded-circle';
                                            break;
                                        case 'system_warning':
                                            echo 'bg-danger bg-opacity-10 p-2 rounded-circle';
                                            break;
                                        default:
                                            echo 'bg-info bg-opacity-10 p-2 rounded-circle';
                                    }
                                ?>">
                                    <i class="<?php 
                                        switch($notification['type']) {
                                            case 'user_registration':
                                                echo 'fas fa-user-plus text-success';
                                                break;
                                            case 'file_upload':
                                                echo 'fas fa-upload text-warning';
                                                break;
                                            case 'revision_request':
                                                echo 'fas fa-edit text-info';
                                                break;
                                            case 'system_warning':
                                                echo 'fas fa-exclamation-triangle text-danger';
                                                break;
                                            default:
                                                echo 'fas fa-info-circle text-info';
                                        }
                                    ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 <?php echo !$notification['is_read'] ? 'fw-bold' : 'fw-normal'; ?>">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary"><?php echo $typeNames[$notification['type']] ?? 'Diğer'; ?></span>
                                        <?php if (!$notification['is_read']): ?>
                                        <span class="bg-primary rounded-circle" style="width: 8px; height: 8px; display: inline-block;"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                        <?php if ($notification['is_read'] && $notification['read_at']): ?>
                                        · Okundu: <?php echo date('d.m.Y H:i', strtotime($notification['read_at'])); ?>
                                        <?php endif; ?>
                                    </small>
                                    <div>
                                        <?php if ($notification['action_url']): ?>
                                        <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                           class="btn btn-sm btn-outline-primary me-2"
                                           onclick="markNotificationAsRead('<?php echo $notification['id']; ?>')">
                                            <i class="fas fa-external-link-alt me-1"></i>Görüntüle
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$notification['is_read']): ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                onclick="markSingleAsRead('<?php echo $notification['id']; ?>')">
                                            <i class="fas fa-check me-1"></i>Okundu İşaretle
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Sayfalama -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Bildirim sayfalama" class="mt-4">
            <ul class="pagination justify-content-center">
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
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script>
let selectAllChecked = false;

function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    selectAllChecked = !selectAllChecked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllChecked;
    });
    
    updateMarkReadButton();
}

function updateMarkReadButton() {
    const checkboxes = document.querySelectorAll('.notification-checkbox:checked');
    const markReadBtn = document.getElementById('markReadBtn');
    
    markReadBtn.disabled = checkboxes.length === 0;
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
            alert('Bildirim işaretlenemedi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu.');
    });
}

function markNotificationAsRead(notificationId) {
    // Bildirim linkine tıklandığında okundu olarak işaretle
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
        // Sonuç ne olursa olsun, link çalışsın
        return true;
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        return true;
    });
}

// Sayfa yüklendiğinde buton durumunu kontrol et
document.addEventListener('DOMContentLoaded', function() {
    updateMarkReadButton();
});
</script>

<?php require_once '../includes/footer.php'; ?>
