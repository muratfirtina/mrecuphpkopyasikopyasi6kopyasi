<?php
/**
 * Design Panel - Hizmet Düzenleme (Güncellenmiş)
 */

require_once '../config/config.php';
require_once '../config/database.php';

$pageTitle = 'Hizmet Düzenle';
$pageDescription = 'Hizmet bilgilerini düzenle';
$pageIcon = 'bi bi-pencil-square';

$message = '';
$messageType = '';
$service = null;

// Hizmet ID kontrolü
$serviceId = (int)($_GET['id'] ?? 0);
if (!$serviceId) {
    header('Location: services.php');
    exit;
}

// Hizmeti getir
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        header('Location: services.php');
        exit;
    }
} catch (Exception $e) {
    error_log('Service fetch error: ' . $e->getMessage());
    header('Location: services.php');
    exit;
}

$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'index.php'],
    ['title' => 'Hizmet Yönetimi', 'url' => 'services.php'],
    ['title' => 'Düzenle: ' . htmlspecialchars($service['name'])]
];

// Features'ları decode et
$existingFeatures = [];
if ($service['features']) {
    $existingFeatures = json_decode($service['features'], true) ?? [];
}

// Form submit işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Form verilerini al
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $detailed_content = trim($_POST['detailed_content'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $price_from = !empty($_POST['price_from']) ? (float)$_POST['price_from'] : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';
    $sort_order = !empty($_POST['sort_order']) ? (int)$_POST['sort_order'] : null;
    
    // Features array'ini oluştur
    $features = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        foreach ($_POST['features'] as $feature) {
            $feature = trim($feature);
            if (!empty($feature)) {
                $features[] = $feature;
            }
        }
    }
    
    $slug = createSlug($name);
    
    // Validations
    if (empty($name)) {
        $errors[] = "Hizmet adı gereklidir.";
    }
    if (empty($description)) {
        $errors[] = "Açıklama gereklidir.";
    }
    if (empty($icon)) {
        $errors[] = "İkon gereklidir.";
    }
    if (empty($slug)) {
        $errors[] = "Geçerli bir hizmet adı giriniz.";
    }

    // Slug benzersizlik kontrolü
    if ($slug !== $service['slug']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $serviceId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Bu hizmet adı zaten kullanılıyor.";
        }
    }

    // Mevcut slug korunur
    if ($name === $service['name']) {
        $slug = $service['slug'];
    }

    // === ANA RESİM YÜKLEME ===
    $imagePath = $service['image'];
    $deleteOldImage = false;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileInfo = pathinfo($_FILES['image']['name']);
        $fileName = $slug . '_main_' . time() . '.' . strtolower($fileInfo['extension']);
        $targetPath = $uploadDir . $fileName;

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array(strtolower($fileInfo['extension']), $allowed)) {
            $errors[] = "Ana resim: JPG, PNG, WebP, GIF desteklenir.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Ana resim 5MB'dan büyük olamaz.";
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $deleteOldImage = true;
            $imagePath = 'uploads/services/' . $fileName;
        } else {
            $errors[] = "Ana resim yüklenemedi.";
        }
    }

    if (isset($_POST['delete_image']) && $_POST['delete_image'] === '1') {
        $deleteOldImage = true;
        $imagePath = null;
    }

    // === İKON RESMİ YÜKLEME (YENİ) ===
    $iconPicturePath = $service['icon_picture'];
    $deleteOldIcon = false;

    if (isset($_FILES['icon_picture_file']) && $_FILES['icon_picture_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/icons/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileInfo = pathinfo($_FILES['icon_picture_file']['name']);
        $fileName = $slug . '_icon_' . time() . '.' . strtolower($fileInfo['extension']);
        $targetPath = $uploadDir . $fileName;

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
        if (!in_array(strtolower($fileInfo['extension']), $allowed)) {
            $errors[] = "İkon resmi: JPG, PNG, WebP, SVG desteklenir.";
        } elseif ($_FILES['icon_picture_file']['size'] > 2 * 1024 * 1024) {
            $errors[] = "İkon resmi 2MB'dan büyük olamaz.";
        } elseif (move_uploaded_file($_FILES['icon_picture_file']['tmp_name'], $targetPath)) {
            $deleteOldIcon = true;
            $iconPicturePath = 'uploads/icons/' . $fileName;
        } else {
            $errors[] = "İkon resmi yüklenemedi.";
        }
    }

    if (isset($_POST['delete_icon_picture']) && $_POST['delete_icon_picture'] === '1') {
        $deleteOldIcon = true;
        $iconPicturePath = null;
    }

    // Güncelleme
    if (empty($errors)) {
        try {
            // Eski dosyaları sil
            if ($deleteOldImage && $service['image'] && file_exists('../' . $service['image'])) {
                unlink('../' . $service['image']);
            }
            if ($deleteOldIcon && $service['icon_picture'] && file_exists('../' . $service['icon_picture'])) {
                unlink('../' . $service['icon_picture']);
            }

            $stmt = $pdo->prepare("
                UPDATE services 
                SET name = ?, slug = ?, description = ?, detailed_content = ?, features = ?, image = ?, icon = ?, icon_picture = ?,
                    price_from = ?, is_featured = ?, status = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute([
                $name, $slug, $description, $detailed_content,
                !empty($features) ? json_encode($features, JSON_UNESCAPED_UNICODE) : null,
                $imagePath, $icon, $iconPicturePath,
                $price_from, $is_featured, $status, $sort_order, $serviceId
            ]);

            $message = "✅ Hizmet başarıyla güncellendi!";
            $messageType = "success";

            // Yeniden yükle
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();
            $existingFeatures = $service['features'] ? json_decode($service['features'], true) ?? [] : [];

        } catch (Exception $e) {
            $message = "❌ Güncelleme hatası: " . $e->getMessage();
            $messageType = "error";
            error_log('Service update error: ' . $e->getMessage());
        }
    } else {
        $message = "⚠️ " . implode('<br>⚠️ ', $errors);
        $messageType = "error";
    }
}

// Header
require_once '../includes/design_header.php';
?>

<!-- Modern Design - Services Edit -->
<style>
    .card-header {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem 1.25rem;
    }
    .btn-design-primary {
        background: #0d6efd;
        border: none;
        color: white;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
    }
    .btn-design-primary:hover {
        background: #0b5ed7;
        transform: translateY(-1px);
    }
    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        border-color: #0d6efd;
    }
    .input-group-text {
        background: #e9ecef;
    }
    .feature-item .form-control {
        border-radius: 8px 0 0 8px;
    }
    .feature-item .btn-outline-danger {
        border-radius: 0 8px 8px 0;
    }
    .img-thumbnail {
        border-radius: 12px;
    }
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .alert {
        border-radius: 8px;
    }
    #imagePreview img, #iconPreviewImg img {
        border-radius: 12px;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">

        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center">
                    <i class="<?= $pageIcon ?> fs-4 me-3 text-primary"></i>
                    <h4 class="mb-0"><?= $pageTitle ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> fade show" role="alert">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">Hizmet Adı *</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                   value="<?= htmlspecialchars($service['name']) ?>" required maxlength="100">
                            <div class="text-muted small mt-1">Slug: <code><?= htmlspecialchars($service['slug']) ?></code></div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Açıklama *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required 
                                      maxlength="1000"><?= htmlspecialchars($service['description']) ?></textarea>
                            <div class="form-text">Kısa açıklama (maksimum 1000 karakter)</div>
                        </div>

                        <div class="mb-4">
                            <label for="detailed_content" class="form-label fw-bold">Detaylı İçerik (HTML Destekli)</label>
                            <textarea class="form-control" id="detailed_content" name="detailed_content" rows="10" 
                                      style="font-family: 'Courier New', monospace;"><?= htmlspecialchars($service['detailed_content'] ?? '') ?></textarea>
                            <div class="form-text">HTML kodları desteklenir. Detaylı açıklama, özellikler ve diğer içerikler için kullanın.</div>
                        </div>

                        <div class="mb-4">
                            <label for="icon" class="form-label fw-bold">FontAwesome İkon *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i id="iconPreview" class="<?= htmlspecialchars($service['icon']) ?> fs-4"></i>
                                </span>
                                <input type="text" class="form-control" id="icon" name="icon" 
                                       value="<?= htmlspecialchars($service['icon']) ?>" required placeholder="bi bi-gear-wide-connected">
                            </div>
                            <div class="form-text">Örnek: bi bi-cpu</div>
                        </div>

                        <!-- Özellikler -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Özellikler</label>
                            <div id="featuresContainer">
                                <?php foreach ($existingFeatures as $feature): ?>
                                    <div class="feature-item mb-2">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="features[]" 
                                                   value="<?= htmlspecialchars($feature) ?>" placeholder="Özellik...">
                                            <button type="button" class="btn btn-outline-danger remove-feature">
                                                <i class="bi bi-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($existingFeatures)): ?>
                                    <div class="feature-item mb-2">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="features[]" placeholder="Özellik...">
                                            <button type="button" class="btn btn-outline-danger remove-feature" style="display:none;">
                                                <i class="bi bi-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addFeature">
                                <i class="bi bi-plus me-1"></i>Özellik Ekle
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Yayınlama Ayarları -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Yayınlama</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Durum</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $service['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $service['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                   <?= $service['is_featured'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">
                                <i class="bi bi-star text-warning me-1"></i>Öne Çıkan
                            </label>
                        </div>
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sıralama</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                   value="<?= $service['sort_order'] ?>" min="1">
                        </div>
                    </div>
                </div>

                <!-- Fiyat -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-tag me-2"></i>Fiyat</h6>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="number" class="form-control" id="price_from" name="price_from" 
                                   value="<?= $service['price_from'] ?>" min="0" step="0.01">
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                </div>

                <!-- Ana Resim -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-image me-2"></i>Ana Resim</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($service['image']): ?>
                            <img src="../<?= htmlspecialchars($service['image']) ?>" class="img-fluid rounded mb-3" style="max-height: 150px;">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image" value="1">
                                <label class="form-check-label text-danger" for="delete_image">Sil</label>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text small mt-2">JPG/PNG/WebP - max 5MB</div>
                        <div id="imagePreview" class="mt-3" style="display:none;">
                            <img id="previewImg" src="" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                    </div>
                </div>

                <!-- İkon Resmi (YENİ) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-icons me-2"></i>İkon Resmi</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($service['icon_picture']): ?>
                            <img src="../<?= htmlspecialchars($service['icon_picture']) ?>" class="img-fluid rounded mb-3" style="max-height: 100px;">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="delete_icon_picture" name="delete_icon_picture" value="1">
                                <label class="form-check-label text-danger" for="delete_icon_picture">Sil</label>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="icon_picture_file" name="icon_picture_file" accept="image/*">
                        <div class="form-text small mt-2">İkon resmi (SVG/PNG/WebP) - max 2MB</div>
                        <div id="iconPicturePreview" class="mt-3" style="display:none;">
                            <img id="iconPreviewImg" src="" class="img-fluid rounded" style="max-height: 100px;">
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-design-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// İkon önizleme
document.getElementById('icon').addEventListener('input', function() {
    document.getElementById('iconPreview').className = this.value.trim() || 'bi bi-gear-wide-connected';
});

// Özellik ekle/sil
document.getElementById('addFeature').addEventListener('click', function() {
    const container = document.getElementById('featuresContainer');
    const item = document.createElement('div');
    item.className = 'feature-item mb-2';
    item.innerHTML = `
        <div class="input-group">
            <input type="text" class="form-control" name="features[]" placeholder="Özellik...">
            <button type="button" class="btn btn-outline-danger remove-feature">
                <i class="bi bi-minus"></i>
            </button>
        </div>
    `;
    container.appendChild(item);
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-feature')) {
        const container = document.getElementById('featuresContainer');
        if (container.children.length > 1) {
            e.target.closest('.feature-item').remove();
        }
    }
});

// Resim önizleme
['image', 'icon_picture_file'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const previewId = id === 'image' ? 'previewImg' : 'iconPreviewImg';
        const previewContainer = id === 'image' ? 'imagePreview' : 'iconPicturePreview';
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(previewContainer).style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
});

// Form validation
document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php require_once '../includes/design_footer.php'; ?>