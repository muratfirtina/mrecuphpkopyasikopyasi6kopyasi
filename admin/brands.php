<?php
/**
 * Mr ECU - Admin Marka/Model Yönetimi - FIXED VERSION
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Marka ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $brandName = sanitize($_POST['brand_name']);
    
    if (empty($brandName)) {
        $error = 'Marka adı zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
            $result = $stmt->execute([$brandName]);
            
            if ($result) {
                $success = 'Marka başarıyla eklendi.';
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Bu marka zaten mevcut.';
            } else {
                $error = 'Marka eklenirken hata oluştu.';
            }
        }
    }
}

// Model ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_model'])) {
    $brandId = sanitize($_POST['brand_id']);
    $modelName = sanitize($_POST['model_name']);
    $yearStart = !empty($_POST['year_start']) ? (int)$_POST['year_start'] : null;
    $yearEnd = !empty($_POST['year_end']) ? (int)$_POST['year_end'] : null;
    
    if (empty($brandId) || empty($modelName)) {
        $error = 'Marka ve model adı zorunludur.';
    } else {
        try {
            $modelId = generateUUID();
            
            $stmt = $pdo->prepare("INSERT INTO models (id, brand_id, name, year_start, year_end) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$modelId, $brandId, $modelName, $yearStart, $yearEnd]);
            
            if ($result) {
                $success = 'Model başarıyla eklendi.';
            }
        } catch(PDOException $e) {
            $error = 'Model eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Marka güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brand'])) {
    $brandId = sanitize($_POST['brand_id']);
    $brandName = sanitize($_POST['brand_name']);
    $status = sanitize($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE brands SET name = ?, is_active = ? WHERE id = ?");
        $result = $stmt->execute([$brandName, $status === 'active' ? 1 : 0, $brandId]);
        
        if ($result) {
            $success = 'Marka güncellendi.';
        }
    } catch(PDOException $e) {
        $error = 'Marka güncellenirken hata oluştu.';
    }
}

// Model güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_model'])) {
    $modelId = sanitize($_POST['model_id']);
    $modelName = sanitize($_POST['model_name']);
    $yearStart = !empty($_POST['year_start']) ? (int)$_POST['year_start'] : null;
    $yearEnd = !empty($_POST['year_end']) ? (int)$_POST['year_end'] : null;
    $status = sanitize($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE models SET name = ?, year_start = ?, year_end = ?, is_active = ? WHERE id = ?");
        $result = $stmt->execute([$modelName, $yearStart, $yearEnd, $status === 'active' ? 1 : 0, $modelId]);
        
        if ($result) {
            $success = 'Model güncellendi.';
        }
    } catch(PDOException $e) {
        $error = 'Model güncellenirken hata oluştu.';
    }
}

// Markaları getir
try {
    $stmt = $pdo->query("
        SELECT b.*, COUNT(m.id) as model_count 
        FROM brands b 
        LEFT JOIN models m ON b.id = m.brand_id 
        GROUP BY b.id 
        ORDER BY b.name
    ");
    $brands = $stmt->fetchAll();
} catch(PDOException $e) {
    $brands = [];
}

// Seçili marka detayı
$selectedBrand = null;
$brandModels = [];
if (isset($_GET['brand_id'])) {
    $brandId = sanitize($_GET['brand_id']);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$brandId]);
        $selectedBrand = $stmt->fetch();
        
        if ($selectedBrand) {
            $stmt = $pdo->prepare("SELECT * FROM models WHERE brand_id = ? ORDER BY name");
            $stmt->execute([$brandId]);
            $brandModels = $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        $selectedBrand = null;
    }
}

$pageTitle = 'Marka/Model Yönetimi';
$pageDescription = 'Araç markalarını ve modellerini yönetin';
$pageIcon = 'fas fa-car';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Markalar Listesi -->
    <div class="col-md-4">
        <div class="card admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-tags me-2"></i>Markalar
                </h5>
                <div>
                    <span class="badge bg-secondary"><?php echo count($brands); ?></span>
                    <button type="button" class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($brands)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Marka bulunamadı</h6>
                        <p class="text-muted">Henüz hiç marka eklenmemiş.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                            <i class="fas fa-plus me-1"></i>İlk Markayı Ekle
                        </button>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($brands as $brand): ?>
                            <a href="?brand_id=<?php echo urlencode($brand['id']); ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ($selectedBrand && $selectedBrand['id'] == $brand['id']) ? 'active' : ''; ?>">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($brand['name']); ?></h6>
                                    <small class="<?php echo ($selectedBrand && $selectedBrand['id'] == $brand['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                        <?php echo $brand['model_count']; ?> model
                                    </small>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo (isset($brand['is_active']) && $brand['is_active']) ? 'success' : 'secondary'; ?> me-2">
                                        <?php echo (isset($brand['is_active']) && $brand['is_active']) ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                    <button type="button" class="btn btn-outline-warning btn-sm" 
                                            data-brand-id="<?php echo htmlspecialchars($brand['id']); ?>"
                                            data-brand-name="<?php echo htmlspecialchars($brand['name']); ?>"
                                            data-brand-status="<?php echo (isset($brand['is_active']) && $brand['is_active']) ? 'active' : 'inactive'; ?>"
                                            onclick="event.preventDefault(); event.stopPropagation(); editBrand(this);">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modeller Listesi -->
    <div class="col-md-8">
        <?php if ($selectedBrand): ?>
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-car-side me-2"></i><?php echo htmlspecialchars($selectedBrand['name']); ?> Modelleri
                    </h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-success me-2" data-bs-toggle="modal" data-bs-target="#addModelModal">
                            <i class="fas fa-plus me-1"></i>Yeni Model
                        </button>
                        <a href="brands.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($brandModels)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-car-side fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Model bulunamadı</h6>
                            <p class="text-muted">Bu markaya ait model bulunmuyor.</p>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModelModal">
                                <i class="fas fa-plus me-1"></i>İlk Modeli Ekle
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-admin table-hover">
                                <thead>
                                    <tr>
                                        <th>Model</th>
                                        <th>Yıl Aralığı</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($brandModels as $model): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($model['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($model['year_start'] && $model['year_end']): ?>
                                                    <?php echo $model['year_start']; ?> - <?php echo $model['year_end']; ?>
                                                <?php elseif ($model['year_start']): ?>
                                                    <?php echo $model['year_start']; ?>+
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $model['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $model['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-warning btn-sm" 
                                                        data-model-id="<?php echo htmlspecialchars($model['id']); ?>"
                                                        data-model-name="<?php echo htmlspecialchars($model['name']); ?>"
                                                        data-model-year-start="<?php echo htmlspecialchars($model['year_start'] ?? ''); ?>"
                                                        data-model-year-end="<?php echo htmlspecialchars($model['year_end'] ?? ''); ?>"
                                                        data-model-status="<?php echo $model['is_active'] ? 'active' : 'inactive'; ?>"
                                                        onclick="editModel(this)">
                                                    <i class="fas fa-edit me-1"></i>Düzenle
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
        <?php else: ?>
            <div class="card admin-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-car-side fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Marka Seçin</h5>
                    <p class="text-muted">Modelleri görüntülemek için bir marka seçin.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Marka Ekleme Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Yeni Marka Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="brand_name" class="form-label">Marka Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="brand_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_brand" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Marka Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Marka Düzenleme Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Marka Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    
                    <div class="mb-3">
                        <label for="edit_brand_name" class="form-label">Marka Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="brand_name" id="edit_brand_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_brand_status" class="form-label">Durum</label>
                        <select class="form-select" name="status" id="edit_brand_status">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="update_brand" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Model Ekleme Modal -->
<div class="modal fade" id="addModelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Yeni Model Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="brand_id" value="<?php echo $selectedBrand ? htmlspecialchars($selectedBrand['id']) : ''; ?>">
                    
                    <div class="mb-3">
                        <label for="model_name" class="form-label">Model Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="model_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="year_start" class="form-label">Başlangıç Yılı</label>
                            <input type="number" class="form-control" name="year_start" min="1900" max="2030">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="year_end" class="form-label">Bitiş Yılı</label>
                            <input type="number" class="form-control" name="year_end" min="1900" max="2030">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_model" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Model Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Model Düzenleme Modal -->
<div class="modal fade" id="editModelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Model Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="model_id" id="edit_model_id">
                    
                    <div class="mb-3">
                        <label for="edit_model_name" class="form-label">Model Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="model_name" id="edit_model_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="edit_year_start" class="form-label">Başlangıç Yılı</label>
                            <input type="number" class="form-control" name="year_start" id="edit_year_start" min="1900" max="2030">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="edit_year_end" class="form-label">Bitiş Yılı</label>
                            <input type="number" class="form-control" name="year_end" id="edit_year_end" min="1900" max="2030">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_model_status" class="form-label">Durum</label>
                        <select class="form-select" name="status" id="edit_model_status">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="update_model" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Seçili marka ID'sini JavaScript değişkenine al
var selectedBrandId = "<?php echo $selectedBrand ? addslashes($selectedBrand['id']) : ''; ?>";

function editBrand(button) {
    const id = button.getAttribute('data-brand-id');
    const name = button.getAttribute('data-brand-name');
    const status = button.getAttribute('data-brand-status');
    
    document.getElementById('edit_brand_id').value = id;
    document.getElementById('edit_brand_name').value = name;
    document.getElementById('edit_brand_status').value = status;
    
    const modal = new bootstrap.Modal(document.getElementById('editBrandModal'));
    modal.show();
}

function editModel(button) {
    const id = button.getAttribute('data-model-id');
    const name = button.getAttribute('data-model-name');
    const yearStart = button.getAttribute('data-model-year-start');
    const yearEnd = button.getAttribute('data-model-year-end');
    const status = button.getAttribute('data-model-status');
    
    document.getElementById('edit_model_id').value = id;
    document.getElementById('edit_model_name').value = name;
    document.getElementById('edit_year_start').value = yearStart || '';
    document.getElementById('edit_year_end').value = yearEnd || '';
    document.getElementById('edit_model_status').value = status;
    
    const modal = new bootstrap.Modal(document.getElementById('editModelModal'));
    modal.show();
}

// Modal temizleme
document.getElementById('addBrandModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});

document.getElementById('addModelModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
    // Seçili marka varsa brand_id'yi tekrar set et
    if (selectedBrandId) {
        const brandIdField = this.querySelector('input[name="brand_id"]');
        if (brandIdField) {
            brandIdField.value = selectedBrandId;
        }
    }
});
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
