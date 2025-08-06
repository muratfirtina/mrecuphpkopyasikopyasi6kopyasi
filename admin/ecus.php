<?php
/**
 * Mr ECU - Admin ECU Yönetimi
 * ECU'ları listeleme, ekleme, düzenleme ve silme
 */

session_start();
require_once '../config/config.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Model dahil et
require_once '../includes/EcuModel.php';
$ecuModel = new EcuModel($pdo);

// Debug: Model oluşturuldu mu?
error_log("ECU ADMIN DEBUG: EcuModel created successfully");

// Debug: PDO bağlantı durumu
try {
    $testQuery = $pdo->query("SELECT COUNT(*) as count FROM ecus");
    $testResult = $testQuery->fetch(PDO::FETCH_ASSOC);
    error_log("ECU ADMIN DEBUG: Direct database test - ECU count: " . $testResult['count']);
} catch (Exception $e) {
    error_log("ECU ADMIN DEBUG: Database test failed: " . $e->getMessage());
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
                $result = $ecuModel->addEcu($name);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'edit':
                $id = sanitize($_POST['id'] ?? '');
                $name = sanitize($_POST['name'] ?? '');
                $result = $ecuModel->updateEcu($id, $name);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'delete':
                $id = sanitize($_POST['id'] ?? '');
                $result = $ecuModel->deleteEcu($id);
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

// ECU'ları getir
if (!empty($search)) {
    $ecus = $ecuModel->searchEcus($search, 100);
    $totalEcus = count($ecus);
    $totalPages = 1;
    error_log("ECU DEBUG: Search mode, found " . count($ecus) . " ECUs for term: " . $search);
} else {
    $paginatedData = $ecuModel->getEcusPaginated($page, $perPage);
    $ecus = $paginatedData['data'];
    $totalEcus = $paginatedData['total'];
    $totalPages = $paginatedData['totalPages'];
    error_log("ECU DEBUG: Paginated mode, found " . count($ecus) . " ECUs, total: " . $totalEcus);
}

// Debug: ECU listesini log'a yaz
error_log("ECU DEBUG: Final ECU array: " . print_r($ecus, true));

$pageTitle = 'ECU Yönetimi';
$pageIcon = 'fas fa-microchip';
$pageDescription = 'ECU birimlerini yönetin, ekleyin ve düzenleyin.';

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <div class="page-header-actions">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEcuModal">
                            <i class="fas fa-plus"></i> Yeni ECU Ekle
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
                                <i class="fas fa-microchip text-primary"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $totalEcus ?></h3>
                                <p>Toplam ECU</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Arama ve Filtreler -->
                <div class="card mb-4" style="width: 30%;">
                    <div class="card-header">
                        <h5><i class="fas fa-search"></i> Arama ve Filtreler</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ECU Adı</label>
                                <input type="text" name="search" class="form-control" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="ECU adında ara...">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Ara
                                </button>
                                <a href="ecus.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Temizle
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ECU Listesi -->
                <div class="card" style="width: 30%;">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> ECU Listesi (<?= $totalEcus ?> adet)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ecus)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-microchip fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Henüz ECU bulunmuyor.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ECU Adı</th>
                                        <th width="120">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ecus as $ecu): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($ecu['name']) ?></strong>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm edit-ecu-btn" 
                                                    data-id="<?= $ecu['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($ecu['name']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editEcuModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-ecu-btn" 
                                                    data-id="<?= $ecu['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($ecu['name']) ?>">
                                                <i class="fas fa-trash"></i>
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

<!-- Yeni ECU Ekleme Modal -->
<div class="modal fade" id="addEcuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni ECU Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">ECU Adı *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">ECU Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ECU Düzenleme Modal -->
<div class="modal fade" id="editEcuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ECU Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="id" id="edit_ecu_id">
                    
                    <div class="mb-3">
                        <label class="form-label">ECU Adı *</label>
                        <input type="text" name="name" id="edit_ecu_name" class="form-control" required>
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
    // ECU düzenleme
    document.querySelectorAll('.edit-ecu-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_ecu_id').value = this.dataset.id;
            document.getElementById('edit_ecu_name').value = this.dataset.name;
        });
    });
    
    // ECU silme
    document.querySelectorAll('.delete-ecu-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const ecuName = this.dataset.name;
            const ecuId = this.dataset.id;
            
            if (confirm(`"${ecuName}" ECU'sunu silmek istediğinizden emin misiniz?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="id" value="${ecuId}">
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
    const listContainer = document.querySelector('.table-responsive');
    const pagination = document.querySelector('nav[aria-label="Sayfa navigasyonu"]');
    const stats = document.querySelector('.stat-content h3, .card-header h5');

    // Arama input değiştiğinde
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();

        // 300ms bekleyip, kullanıcı yazmayı bitirince ara
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            if (query.length === 0) {
                // Boşsa tüm listeyi göster (sayfalı)
                location.href = 'ecus.php'; // veya AJAX ile tüm listeyi getir
                return;
            }

            // AJAX ile arama yap
            fetch('ajax/ecu-api.php?action=search&term=' + encodeURIComponent(query) + '&limit=100')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        updateTable(data.data);
                        updateStats(data.count);
                    } else {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="fas fa-microchip text-muted"></i>
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
                    console.error('Arama hatası:', err);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-danger">Arama yapılırken hata oluştu.</td>
                        </tr>
                    `;
                });
        }, 300); // 300ms debounce
    });

    function updateTable(ecus) {
        tableBody.innerHTML = ecus.map(ecu => `
            <tr>
                <td><strong>${escapeHtml(ecu.name)}</strong></td>
                <td>${formatDate(ecu.created_at)}</td>
                <td>${formatDate(ecu.updated_at)}</td>
                <td>
                    <button class="btn btn-sm btn-primary edit-ecu-btn"
                            data-id="${ecu.id}"
                            data-name="${escapeHtml(ecu.name)}"
                            data-bs-toggle="modal" data-bs-target="#editEcuModal">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-ecu-btn"
                            data-id="${ecu.id}"
                            data-name="${escapeHtml(ecu.name)}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        attachEventListeners(); // Yeni butonlara event ekle
    }

    function updateStats(count) {
        if (stats[0]) stats[0].textContent = count;
        if (stats[1]) stats[1].textContent = `ECU Listesi (${count} adet)`;
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
        // Düzenle butonları
        document.querySelectorAll('.edit-ecu-btn').forEach(btn => {
            btn.removeEventListener('click', editHandler);
            btn.addEventListener('click', editHandler);
        });

        // Sil butonları
        document.querySelectorAll('.delete-ecu-btn').forEach(btn => {
            btn.removeEventListener('click', deleteHandler);
            btn.addEventListener('click', deleteHandler);
        });
    }

    function editHandler() {
        document.getElementById('edit_ecu_id').value = this.dataset.id;
        document.getElementById('edit_ecu_name').value = this.dataset.name;
    }

    function deleteHandler() {
        const ecuName = this.dataset.name;
        const ecuId = this.dataset.id;
        if (confirm(`"${ecuName}" ECU'sunu silmek istediğinizden emin misiniz?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" value="${ecuId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Başlangıçta event listener'ları bağla
    attachEventListeners();
});
</script>

<style>
    /* Yükleme animasyonu */
.lds-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}
@keyframes spin { 100% { transform: rotate(360deg); } }
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
    background: rgba(0,123,255, 0.1);
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
