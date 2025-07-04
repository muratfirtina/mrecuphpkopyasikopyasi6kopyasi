<?php
/**
 * Mr ECU - Admin Marka/Model Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Marka ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $brandName = sanitize($_POST['brand_name']);
    
    if (empty($brandName)) {
        $_SESSION['error'] = 'Marka adı zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
            $result = $stmt->execute([$brandName]);
            
            if ($result) {
                $_SESSION['success'] = 'Marka başarıyla eklendi.';
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['error'] = 'Bu marka zaten mevcut.';
            } else {
                $_SESSION['error'] = 'Marka eklenirken hata oluştu.';
            }
        }
    }
    
    // POST işleminden sonra redirect yap (PRG pattern)
    $redirectUrl = 'brands.php';
    if (isset($_GET['brand_id']) && !empty($_GET['brand_id'])) {
        $redirectUrl .= '?brand_id=' . urlencode($_GET['brand_id']);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Model ekleme - UUID desteği
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_model'])) {
    $brandId = sanitize($_POST['brand_id']);
    $modelName = sanitize($_POST['model_name']);
    $yearStart = !empty($_POST['year_start']) ? (int)$_POST['year_start'] : null;
    $yearEnd = !empty($_POST['year_end']) ? (int)$_POST['year_end'] : null;
    
    // Debug için
    error_log("Model ekleme: brandId=$brandId, modelName=$modelName");
    
    if (empty($brandId) || empty($modelName)) {
        $_SESSION['error'] = 'Marka ve model adı zorunludur.';
        error_log("Model ekleme hatası: Boş değerler - brandId=$brandId, modelName=$modelName");
    } else {
        try {
            // UUID generate et
            $modelId = generateUUID();
            error_log("Generated Model ID: $modelId");
            
            // ID ile birlikte INSERT et
            $stmt = $pdo->prepare("INSERT INTO models (id, brand_id, name, year_start, year_end) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$modelId, $brandId, $modelName, $yearStart, $yearEnd]);
            
            if ($result) {
                $_SESSION['success'] = 'Model başarıyla eklendi.';
                error_log("✅ Model başarıyla eklendi: $modelName (ID: $modelId)");
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("❌ Model ekleme başarısız: " . json_encode($errorInfo));
                $_SESSION['error'] = 'Model eklenirken hata oluştu: ' . $errorInfo[2];
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Model eklenirken hata oluştu: ' . $e->getMessage();
            error_log("❌ Model ekleme PDO hatası: " . $e->getMessage());
        }
    }
    
    // POST işleminden sonra redirect yap (PRG pattern)
    $redirectUrl = 'brands.php';
    if (isset($_POST['brand_id']) && !empty($_POST['brand_id'])) {
        $redirectUrl .= '?brand_id=' . urlencode($_POST['brand_id']);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Marka güncelleme - UUID desteği
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brand'])) {
    $brandId = sanitize($_POST['brand_id']); // UUID desteği için int casting kaldırıldı
    $brandName = sanitize($_POST['brand_name']);
    $status = sanitize($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE brands SET name = ?, status = ? WHERE id = ?");
        $result = $stmt->execute([$brandName, $status, $brandId]);
        
        if ($result) {
            $_SESSION['success'] = 'Marka güncellendi.';
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Marka güncellenirken hata oluştu.';
    }
    
    // POST işleminden sonra redirect yap (PRG pattern)
    $redirectUrl = 'brands.php';
    if (isset($_GET['brand_id']) && !empty($_GET['brand_id'])) {
        $redirectUrl .= '?brand_id=' . urlencode($_GET['brand_id']);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Model güncelleme - UUID desteği
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_model'])) {
    $modelId = sanitize($_POST['model_id']); // UUID desteği için int casting kaldırıldı
    $modelName = sanitize($_POST['model_name']);
    $yearStart = !empty($_POST['year_start']) ? (int)$_POST['year_start'] : null;
    $yearEnd = !empty($_POST['year_end']) ? (int)$_POST['year_end'] : null;
    $status = sanitize($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE models SET name = ?, year_start = ?, year_end = ?, status = ? WHERE id = ?");
        $result = $stmt->execute([$modelName, $yearStart, $yearEnd, $status, $modelId]);
        
        if ($result) {
            $_SESSION['success'] = 'Model güncellendi.';
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Model güncellenirken hata oluştu.';
    }
    
    // POST işleminden sonra redirect yap (PRG pattern)
    $redirectUrl = 'brands.php';
    if (isset($_GET['brand_id']) && !empty($_GET['brand_id'])) {
        $redirectUrl .= '?brand_id=' . urlencode($_GET['brand_id']);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Session'dan mesajları al ve temizle
$error = '';
$success = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
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
    
    // Çift kayıt kontrolü
    $stmt = $pdo->query("
        SELECT name, COUNT(*) as count 
        FROM brands 
        GROUP BY name 
        HAVING COUNT(*) > 1
    ");
    $duplicateBrands = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $brands = [];
    $duplicateBrands = [];
}

// Seçili marka detayı - UUID desteği
$selectedBrand = null;
$brandModels = [];
if (isset($_GET['brand_id'])) {
    $brandId = sanitize($_GET['brand_id']); // UUID desteği için int casting kaldırıldı
    
    // Debug için
    error_log("Brand ID alındı: " . $brandId);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$brandId]);
        $selectedBrand = $stmt->fetch();
        
        // Debug için
        error_log("Selected Brand: " . ($selectedBrand ? 'Bulundu' : 'Bulunamadı'));
        if ($selectedBrand) {
            error_log("Brand Name: " . $selectedBrand['name']);
        }
        
        if ($selectedBrand) {
            $stmt = $pdo->prepare("SELECT * FROM models WHERE brand_id = ? ORDER BY name");
            $stmt->execute([$brandId]);
            $brandModels = $stmt->fetchAll();
            
            // Debug için
            error_log("Model sayısı: " . count($brandModels));
        }
    } catch(PDOException $e) {
        error_log("Brand sorgu hatası: " . $e->getMessage());
        $selectedBrand = null;
    }
}

$pageTitle = 'Marka/Model Yönetimi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-car me-2"></i>Marka/Model Yönetimi
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                <i class="fas fa-plus me-1"></i>Yeni Marka
                            </button>
                            <?php if ($selectedBrand): ?>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addModelModal">
                                    <i class="fas fa-plus me-1"></i>Yeni Model
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($duplicateBrands)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dikkat!</strong> Veritabanında çift marka kayıtları bulundu.
                        <a href="brands-clean.php" class="alert-link">Temizlemek için tıklayın</a>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>



                <div class="row">
                    <!-- Markalar Listesi -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-tags me-2"></i>Markalar
                                </h5>
                                <span class="badge bg-secondary"><?php echo count($brands); ?></span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($brands)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tags text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3 text-muted">Marka bulunamadı</h5>
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
                                                    <span class="badge bg-<?php echo $brand['status'] === 'active' ? 'success' : 'secondary'; ?> me-2">
                                                        <?php echo $brand['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                                    </span>
                                                    <button type="button" class="btn btn-outline-warning btn-sm edit-brand-btn" 
                                                            data-brand-id="<?php echo htmlspecialchars($brand['id']); ?>"
                                                            data-brand-name="<?php echo htmlspecialchars($brand['name']); ?>"
                                                            data-brand-status="<?php echo htmlspecialchars($brand['status']); ?>"
                                                            onclick="event.preventDefault(); event.stopPropagation(); editBrandSafe(this);">
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
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-car-side me-2"></i><?php echo htmlspecialchars($selectedBrand['name']); ?> Modelleri
                                    </h5>
                                    <a href="brands.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($brandModels)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-car-side text-muted" style="font-size: 3rem;"></i>
                                            <h5 class="mt-3 text-muted">Model bulunamadı</h5>
                                            <p class="text-muted">Bu markaya ait model bulunmuyor.</p>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModelModal">
                                                <i class="fas fa-plus me-1"></i>İlk Modeli Ekle
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Model</th>
                                                        <th>Yıl Aralığı</th>
                                                        <th>Durum</th>
                                                        <th>İşlem</th>
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
                                                                <span class="badge bg-<?php echo $model['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                    <?php echo $model['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-outline-warning btn-sm edit-model-btn" 
                                                                        data-model-id="<?php echo htmlspecialchars($model['id']); ?>"
                                                                        data-model-name="<?php echo htmlspecialchars($model['name']); ?>"
                                                                        data-model-year-start="<?php echo htmlspecialchars($model['year_start'] ?? ''); ?>"
                                                                        data-model-year-end="<?php echo htmlspecialchars($model['year_end'] ?? ''); ?>"
                                                                        data-model-status="<?php echo htmlspecialchars($model['status']); ?>"
                                                                        onclick="editModelSafe(this)">
                                                                    <i class="fas fa-edit"></i>
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
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-car-side text-muted" style="font-size: 4rem;"></i>
                                    <h5 class="mt-3 text-muted">Marka Seçin</h5>
                                    <p class="text-muted">Modelleri görüntülemek için bir marka seçin.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
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
                            <label for="brand_name" class="form-label">Marka Adı *</label>
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
                            <label for="edit_brand_name" class="form-label">Marka Adı *</label>
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
                            <label for="model_name" class="form-label">Model Adı *</label>
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
                            <label for="edit_model_name" class="form-label">Model Adı *</label>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        function editBrand(id, name, status) {
            document.getElementById('edit_brand_id').value = id;
            document.getElementById('edit_brand_name').value = name;
            document.getElementById('edit_brand_status').value = status;
            
            const modal = new bootstrap.Modal(document.getElementById('editBrandModal'));
            modal.show();
        }

        function editBrandSafe(button) {
            const id = button.getAttribute('data-brand-id');
            const name = button.getAttribute('data-brand-name');
            const status = button.getAttribute('data-brand-status');
            
            console.log('editBrandSafe çağrıldı:', {id, name, status});
            
            editBrand(id, name, status);
        }

        function editModel(id, name, yearStart, yearEnd, status) {
            console.log('editModel çağrıldı:', {id, name, yearStart, yearEnd, status});
            
            document.getElementById('edit_model_id').value = id;
            document.getElementById('edit_model_name').value = name;
            document.getElementById('edit_year_start').value = (yearStart !== null && yearStart !== undefined && yearStart !== '') ? yearStart : '';
            document.getElementById('edit_year_end').value = (yearEnd !== null && yearEnd !== undefined && yearEnd !== '') ? yearEnd : '';
            document.getElementById('edit_model_status').value = status;
            
            const modal = new bootstrap.Modal(document.getElementById('editModelModal'));
            modal.show();
        }

        function editModelSafe(button) {
            const id = button.getAttribute('data-model-id');
            const name = button.getAttribute('data-model-name');
            const yearStart = button.getAttribute('data-model-year-start');
            const yearEnd = button.getAttribute('data-model-year-end');
            const status = button.getAttribute('data-model-status');
            
            console.log('editModelSafe çağrıldı:', {id, name, yearStart, yearEnd, status});
            
            editModel(id, name, yearStart, yearEnd, status);
        }

        // Modal açıldığında form temizle
        document.getElementById('addBrandModal').addEventListener('shown.bs.modal', function () {
            this.querySelector('form').reset();
        });

        document.getElementById('addModelModal').addEventListener('shown.bs.modal', function () {
            this.querySelector('form').reset();
            // brand_id hidden field'ını tekrar set et
            const brandId = '<?php echo $selectedBrand ? $selectedBrand['id'] : ''; ?>';
            if (brandId) {
                this.querySelector('input[name="brand_id"]').value = brandId;
            }
        });
    </script>

    <!-- Error Handler -->
    <script>
        // Global hata yakalayıcı
        window.addEventListener('error', function(e) {
            console.error('🚨 JavaScript Hatası:', e.error);
            console.error('📍 Dosya:', e.filename);
            console.error('📝 Satır:', e.lineno);
            console.error('💬 Mesaj:', e.message);
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const addModelModal = document.getElementById('addModelModal');
            if (addModelModal) {
                const form = addModelModal.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const formData = new FormData(this);
                        const brandId = formData.get('brand_id');
                        if (!brandId) {
                            e.preventDefault();
                            alert('Brand ID eksik! Lütfen bir marka seçin.');
                            return false;
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>