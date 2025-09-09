<?php
/**
 * Mr ECU - Kategori Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Kategori resim yükleme fonksiyonu
function uploadCategoryImage($file, $categoryId) {
    if (empty($file['tmp_name'])) {
        return null;
    }
    
    $uploadDir = '../uploads/categories/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
    $maxSize = 25 * 1024 * 1024; // 5MB
    
    $fileType = $file['type'];
    $fileSize = $file['size'];
    $fileName = $file['name'];
    
    // Dosya türü kontrolü
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Sadece JPG, PNG, GIF, AVIF ve WEBP formatları desteklenir.');
    }
    
    // Dosya boyutu kontrolü
    if ($fileSize > $maxSize) {
        throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz.');
    }
    
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = 'category_' . $categoryId . '_' . uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return 'uploads/categories/' . $newFileName;
    }
    
    throw new Exception('Dosya yüklenirken hata oluştu.');
}

// Eski kategori resmini sil
function deleteCategoryImage($imagePath) {
    if (!empty($imagePath) && file_exists('../' . $imagePath)) {
        unlink('../' . $imagePath);
    }
}

// Kategori ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $parentId = !empty($_POST['parent_id']) ? sanitize($_POST['parent_id']) : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)$_POST['sort_order'];
    
    if (empty($name)) {
        $error = 'Kategori adı zorunludur.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Slug oluştur
            $slug = createSlug($name);
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $slug, $description, $parentId, $isActive, $sortOrder]);
            
            if ($result) {
                $categoryId = $pdo->lastInsertId();
                
                // Resim yükleme işlemi
                $imagePath = null;
                if (isset($_FILES['category_image']) && !empty($_FILES['category_image']['tmp_name'])) {
                    $imagePath = uploadCategoryImage($_FILES['category_image'], $categoryId);
                    
                    // Resim yolunu veritabanında güncelle
                    if ($imagePath) {
                        $stmt = $pdo->prepare("UPDATE categories SET image = ? WHERE id = ?");
                        $stmt->execute([$imagePath, $categoryId]);
                    }
                }
                
                $pdo->commit();
                $success = 'Kategori başarıyla eklendi.';
            }
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = 'Kategori eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Kategori güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $categoryId = sanitize($_POST['category_id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $parentId = !empty($_POST['parent_id']) ? sanitize($_POST['parent_id']) : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)$_POST['sort_order'];
    $deleteImage = isset($_POST['delete_image']) ? 1 : 0;
    
    try {
        $pdo->beginTransaction();
        
        // Mevcut kategori bilgilerini al
        $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $currentCategory = $stmt->fetch();
        $currentImagePath = $currentCategory['image'] ?? null;
        
        $slug = createSlug($name);
        $newImagePath = $currentImagePath;
        
        // Resim silme işlemi
        if ($deleteImage && $currentImagePath) {
            deleteCategoryImage($currentImagePath);
            $newImagePath = null;
        }
        
        // Yeni resim yükleme işlemi
        if (isset($_FILES['category_image']) && !empty($_FILES['category_image']['tmp_name'])) {
            // Eski resmi sil
            if ($currentImagePath) {
                deleteCategoryImage($currentImagePath);
            }
            $newImagePath = uploadCategoryImage($_FILES['category_image'], $categoryId);
        }
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, is_active = ?, sort_order = ?, image = ? WHERE id = ?");
        $result = $stmt->execute([$name, $slug, $description, $parentId, $isActive, $sortOrder, $newImagePath, $categoryId]);
        
        if ($result) {
            $pdo->commit();
            $success = 'Kategori güncellendi.';
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = 'Kategori güncellenirken hata oluştu: ' . $e->getMessage();
    }
}

// Kategori silme
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $categoryId = sanitize($_GET['delete']);
    
    try {
        // Alt kategorileri kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$categoryId]);
        $hasChildren = $stmt->fetchColumn() > 0;
        
        if ($hasChildren) {
            $error = 'Bu kategorinin alt kategorileri var. Önce alt kategorileri silin.';
        } else {
            $pdo->beginTransaction();
            
            // Kategori resim yolunu al
            $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch();
            
            // Kategoriyi sil
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$categoryId]);
            
            if ($result) {
                // Kategori resmini sil
                if ($category && !empty($category['image'])) {
                    deleteCategoryImage($category['image']);
                }
                
                $pdo->commit();
                $success = 'Kategori başarıyla silindi.';
            }
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = 'Kategori silinirken hata oluştu: ' . $e->getMessage();
    }
}

// Kategorileri getir
try {
    $stmt = $pdo->query("
        SELECT c.*, p.name as parent_name,
               (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.id 
        ORDER BY c.sort_order, c.name
    ");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Form için parent kategoriler
$parentCategories = array_filter($categories, function($cat) {
    return $cat['parent_id'] === null;
});

// createSlug fonksiyonu config.php dosyasından kullanılıyor

$pageTitle = 'Kategori Yönetimi';
$pageDescription = 'Ürün kategorilerini yönetin';
$pageIcon = 'bi bi-tags';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Kategori Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-tags me-2"></i>Kategoriler (<?php echo count($categories); ?>)
        </h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus me-1"></i>Yeni Kategori
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="text-center py-4">
                <i class="bi bi-tags fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Kategori bulunamadı</h6>
                <p class="text-muted">Henüz kategori eklenmemiş.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus me-1"></i>İlk Kategoriyi Ekle
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Resim</th>
                            <th>Üst Kategori</th>
                            <th>Alt Kategoriler</th>
                            <th>Sıra</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($category['slug']); ?></small>
                                        <?php if ($category['description']): ?>
                                            <br>
                                            <small class="text-muted"><?php echo mb_substr(htmlspecialchars($category['description']), 0, 50); ?>...</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($category['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($category['name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <span class="text-muted">Resim yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($category['parent_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($category['parent_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Ana Kategori</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($category['children_count'] > 0): ?>
                                        <span class="badge bg-secondary"><?php echo $category['children_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $category['sort_order']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $category['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-warning" 
        onclick='editCategory(
            <?php echo json_encode($category["id"]); ?>,
            <?php echo json_encode($category["name"]); ?>,
            <?php echo json_encode($category["description"]); ?>,
            <?php echo json_encode($category["parent_id"] ?: ""); ?>,
            <?php echo json_encode((bool)$category["is_active"]); ?>,
            <?php echo (int)$category["sort_order"]; ?>,
            <?php echo json_encode($category["image"] ?? ""); ?>
        )'>
    <i class="bi bi-pencil-square"></i>
</button>
        <a href="?delete=<?php echo $category['id']; ?>" 
           class="btn btn-outline-danger"
           onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">
            <i class="bi bi-trash"></i>
        </a>
    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Kategori Ekleme Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus me-2"></i>Yeni Kategori Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Kategori Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_image" class="form-label">Kategori Resmi</label>
                        <input type="file" class="form-control" name="category_image" accept="image/*">
                        <small class="text-muted">Desteklenen formatlar: JPG, PNG, GIF, WEBP (Maksimum: 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Üst Kategori</label>
                        <select class="form-select" name="parent_id">
                            <option value="">Ana Kategori</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sıra Numarası</label>
                                <input type="number" class="form-control" name="sort_order" value="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" checked>
                                    <label class="form-check-label">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Kategori Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kategori Düzenleme Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>Kategori Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Kategori Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <!-- Mevcut Resim Gösterimi -->
                    <div class="mb-3" id="current-image-section" style="display: none;">
                        <label class="form-label">Mevcut Resim</label>
                        <div>
                            <img id="current-image" src="" alt="Kategori Resmi" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                            <div class="mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="delete_image" id="delete_image">
                                    <label class="form-check-label text-danger" for="delete_image">
                                        Mevcut resmi sil
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category_image" class="form-label">Yeni Kategori Resmi</label>
                        <input type="file" class="form-control" name="category_image" id="edit_category_image" accept="image/*">
                        <small class="text-muted">Desteklenen formatlar: JPG, PNG, GIF, WEBP (Maksimum: 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_parent_id" class="form-label">Üst Kategori</label>
                        <select class="form-select" name="parent_id" id="edit_parent_id">
                            <option value="">Ana Kategori</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="edit_sort_order" class="form-label">Sıra Numarası</label>
                                <input type="number" class="form-control" name="sort_order" id="edit_sort_order">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                    <label class="form-check-label">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="update_category" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageJS = <<<'JS'
function editCategory(id, name, description, parentId, isActive, sortOrder, imagePath) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_parent_id').value = parentId || '';
    document.getElementById('edit_is_active').checked = isActive == 1;
    document.getElementById('edit_sort_order').value = sortOrder;

    const currentImageSection = document.getElementById('current-image-section');
    const currentImage = document.getElementById('current-image');
    const deleteImageCheckbox = document.getElementById('delete_image');

    if (imagePath && imagePath.trim() !== '') {
        currentImage.src = '../' + imagePath;
        currentImageSection.style.display = 'block';
        deleteImageCheckbox.checked = false;
    } else {
        currentImageSection.style.display = 'none';
    }

    document.getElementById('edit_category_image').value = '';

    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});

function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const addImageInput = document.querySelector('#addCategoryModal input[name="category_image"]');
    if (addImageInput) {
        addImageInput.addEventListener('change', function() {
            let preview = this.parentNode.querySelector('.image-preview');
            if (!preview && this.files && this.files[0]) {
                preview = document.createElement('div');
                preview.className = 'image-preview mt-2';
                preview.innerHTML = '<img style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
                this.parentNode.appendChild(preview);
            }
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.querySelector('img').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            } else if (preview) {
                preview.remove();
            }
        });
    }

    const editImageInput = document.getElementById('edit_category_image');
    if (editImageInput) {
        editImageInput.addEventListener('change', function() {
            let preview = this.parentNode.querySelector('.image-preview');
            if (!preview && this.files && this.files[0]) {
                preview = document.createElement('div');
                preview.className = 'image-preview mt-2';
                preview.innerHTML = '<img style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;"><div class="text-muted small mt-1">Yeni resim önizlemesi</div>';
                this.parentNode.appendChild(preview);
            }
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.querySelector('img').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            } else if (preview) {
                preview.remove();
            }
        });
    }
});
JS;

include '../includes/admin_footer.php';
