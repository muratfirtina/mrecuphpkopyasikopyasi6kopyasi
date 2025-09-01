<?php
/**
 * Mr ECU - Admin Device Yönetimi
 * Device'ları listeleme, ekleme, düzenleme ve silme
 */

session_start();
require_once '../config/config.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Model dahil et
require_once '../includes/DeviceModel.php';
$deviceModel = new DeviceModel($pdo);

// Debug: Model oluşturuldu mu?
error_log("DEVICE ADMIN DEBUG: DeviceModel created successfully");

// Debug: PDO bağlantı durumu
try {
    $testQuery = $pdo->query("SELECT COUNT(*) as count FROM devices");
    $testResult = $testQuery->fetch(PDO::FETCH_ASSOC);
    error_log("DEVICE ADMIN DEBUG: Direct database test - Device count: " . $testResult['count']);
} catch (Exception $e) {
    error_log("DEVICE ADMIN DEBUG: Database test failed: " . $e->getMessage());
}

// İşlem kontrolü
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CSRF token kontrolü
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Güvenlik hatası oluştu.';
        $messageType = 'error';
    } else {
        switch ($action) {
            case 'add':
                $name = sanitize($_POST['name'] ?? '');
                $result = $deviceModel->addDevice($name);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'edit':
                $id = sanitize($_POST['id'] ?? '');
                $name = sanitize($_POST['name'] ?? '');
                $result = $deviceModel->updateDevice($id, $name);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'delete':
                $id = sanitize($_POST['id'] ?? '');
                $result = $deviceModel->deleteDevice($id);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Sayfalama ayarları
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$search = sanitize($_GET['search'] ?? '');

// Device'ları getir
if (!empty($search)) {
    $devices = $deviceModel->searchDevices($search, 100);
    $totalDevices = count($devices);
    $totalPages = 1;
    error_log("DEVICE DEBUG: Search mode, found " . count($devices) . " devices for term: " . $search);
} else {
    $paginatedData = $deviceModel->getDevicesPaginated($page, $perPage);
    $devices = $paginatedData['data'];
    $totalDevices = $paginatedData['total'];
    $totalPages = $paginatedData['totalPages'];
    error_log("DEVICE DEBUG: Paginated mode, found " . count($devices) . " devices, total: " . $totalDevices);
}

// Debug: Device listesini log'a yaz
error_log("DEVICE DEBUG: Final device array: " . print_r($devices, true));

$pageTitle = 'Device Yönetimi';
$pageIcon = 'bi bi-tools';
$pageDescription = 'Tuning device\'larını yönetin, ekleyin ve düzenleyin.';

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <div class="page-header-actions">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                            <i class="bi bi-plus"></i> Yeni Device Ekle
                        </button>
                    </div>
                </div>

                <!-- Mesaj -->
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-tools text-success"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $totalDevices ?></h3>
                                <p>Toplam Device</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Arama ve Filtreler -->
                <div class="card mb-4" style="width: 30%;">
                    <div class="card-header">
                        <h5><i class="bi bi-search"></i> Arama ve Filtreler</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Device Adı</label>
                                <input type="text" name="search" class="form-control" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Device adında ara...">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Ara
                                </button>
                                <a href="devices.php" class="btn btn-secondary">
                                    <i class="bi bi-times"></i> Temizle
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Device Listesi -->
                <div class="card" style="width: 30%;">
                    <div class="card-header">
                        <h5><i class="bi bi-list"></i> Device Listesi (<?= $totalDevices ?> adet)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($devices)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-tools fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Henüz device bulunmuyor.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Device Adı</th>
                                        <th width="120">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $device): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($device['name']) ?></strong>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm edit-device-btn" 
                                                    data-id="<?= $device['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($device['name']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editDeviceModal">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-device-btn" 
                                                    data-id="<?= $device['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($device['name']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Sayfalama -->
                        <?php if (!empty($search) || $totalPages > 1): ?>
                        <nav aria-label="Sayfa navigasyonu" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">İlk</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Önceki</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sonraki</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalPages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Son</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Device Ekleme Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Device Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Device Adı *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Device Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Device Düzenleme Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Device Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="id" id="edit_device_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Device Adı *</label>
                        <input type="text" name="name" id="edit_device_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Device düzenleme
    document.querySelectorAll('.edit-device-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_device_id').value = this.dataset.id;
            document.getElementById('edit_device_name').value = this.dataset.name;
        });
    });
    
    // Device silme
    document.querySelectorAll('.delete-device-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const deviceName = this.dataset.name;
            const deviceId = this.dataset.id;
            
            if (confirm(`"${deviceName}" device'ını silmek istediğinizden emin misiniz?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="id" value="${deviceId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const tableBody = document.querySelector('table tbody');
    const pagination = document.querySelector('nav[aria-label="Sayfa navigasyonu"]');
    const stats = document.querySelector('.stat-content h3, .card-header h5');

    // Arama input değiştiğinde
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();

        // 300ms debounce
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            if (query.length === 0) {
                // Boşsa sayfalamalı listeyi göster
                location.href = 'devices.php';
                return;
            }

            // AJAX ile arama yap
            fetch('ajax/device-api.php?action=search&term=' + encodeURIComponent(query) + '&limit=100')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        updateTable(data.data);
                        updateStats(data.count);
                    } else {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="bi bi-tools text-muted"></i>
                                    <p class="text-muted mb-0">Arama sonucu bulunamadı.</p>
                                </td>
                            </tr>
                        `;
                        updateStats(0);
                    }
                    // Sayfalamayı gizle
                    if (pagination) pagination.style.display = 'none';
                })
                .catch(err => {
                    console.error('Device arama hatası:', err);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-danger">Arama yapılırken hata oluştu.</td>
                        </tr>
                    `;
                });
        }, 300);
    });

    function updateTable(devices) {
        tableBody.innerHTML = devices.map(device => `
            <tr>
                <td><strong>${escapeHtml(device.name)}</strong></td>
                <td>${formatDate(device.created_at)}</td>
                <td>${formatDate(device.updated_at)}</td>
                <td>
                    <button class="btn btn-sm btn-primary edit-device-btn"
                            data-id="${device.id}"
                            data-name="${escapeHtml(device.name)}"
                            data-bs-toggle="modal" data-bs-target="#editDeviceModal">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-device-btn"
                            data-id="${device.id}"
                            data-name="${escapeHtml(device.name)}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        attachEventListeners();
    }

    function updateStats(count) {
        if (stats[0]) stats[0].textContent = count;
        if (stats[1]) stats[1].textContent = `Device Listesi (${count} adet)`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('tr-TR') + ' ' + date.toLocaleTimeString('tr-TR', {hour12: false});
    }

    function attachEventListeners() {
        document.querySelectorAll('.edit-device-btn').forEach(btn => {
            btn.removeEventListener('click', editHandler);
            btn.addEventListener('click', editHandler);
        });

        document.querySelectorAll('.delete-device-btn').forEach(btn => {
            btn.removeEventListener('click', deleteHandler);
            btn.addEventListener('click', deleteHandler);
        });
    }

    function editHandler() {
        document.getElementById('edit_device_id').value = this.dataset.id;
        document.getElementById('edit_device_name').value = this.dataset.name;
    }

    function deleteHandler() {
        const deviceName = this.dataset.name;
        const deviceId = this.dataset.id;
        if (confirm(`"${deviceName}" device'ını silmek istediğinizden emin misiniz?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" value="${deviceId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    attachEventListeners();
});
</script>

<style>
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(40,167,69, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stat-icon i {
    font-size: 24px;
}

.stat-content h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.page-header h1 {
    margin: 0;
    color: #333;
}

.page-header-actions {
    display: flex;
    gap: 10px;
}
</style>

<?php include '../includes/admin_footer.php'; ?>
