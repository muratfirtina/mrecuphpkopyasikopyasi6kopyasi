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
            // Slug oluştur
            $slug = createSlug($name);
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $slug, $description, $parentId, $isActive, $sortOrder]);
            
            if ($result) {
                $success = 'Kategori başarıyla eklendi.';
            }
        } catch(PDOException $e) {
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
    
    try {
        $slug = createSlug($name);
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, is_active = ?, sort_order = ? WHERE id = ?");
        $result = $stmt->execute([$name, $slug, $description, $parentId, $isActive, $sortOrder, $categoryId]);
        
        if ($result) {
            $success = 'Kategori güncellendi.';
        }
    } catch(PDOException $e) {
        $error = 'Kategori güncellenirken hata oluştu.';
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
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$categoryId]);
            
            if ($result) {
                $success = 'Kategori başarıyla silindi.';
            }
        }
    } catch(PDOException $e) {
        $error = 'Kategori silinirken hata oluştu.';
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

// Slug oluşturma fonksiyonu
function createSlug($text) {
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    
    // Türkçe karakterleri değiştir
    $tr = array('ş','Ş','ı','I','İ','ğ','Ğ','ü','Ü','ö','Ö','Ç','ç');
    $en = array('s','s','i','i','i','g','g','u','u','o','o','c','c');
    $text = str_replace($tr, $en, $text);
    
    // Sadece harf, rakam ve tire bırak
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

$pageTitle = 'Kategori Yönetimi';
$pageDescription = 'Ürün kategorilerini yönetin';
$pageIcon = 'fas fa-tags';

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

<!-- Kategori Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-tags me-2"></i>Kategoriler (<?php echo count($categories); ?>)
        </h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus me-1"></i>Yeni Kategori
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="text-center py-4">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Kategori bulunamadı</h6>
                <p class="text-muted">Henüz kategori eklenmemiş.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-1"></i>İlk Kategoriyi Ekle
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>Kategori</th>
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
                                                onclick="editCategory('<?php echo $category['id']; ?>', '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description']); ?>', '<?php echo $category['parent_id']; ?>', <?php echo $category['is_active']; ?>, <?php echo $category['sort_order']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $category['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i>
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
                    <i class="fas fa-plus me-2"></i>Yeni Kategori Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
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
                        <i class="fas fa-save me-1"></i>Kategori Ekle
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
                    <i class="fas fa-edit me-2"></i>Kategori Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
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
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageJS = "
function editCategory(id, name, description, parentId, isActive, sortOrder) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_parent_id').value = parentId || '';
    document.getElementById('edit_is_active').checked = isActive == 1;
    document.getElementById('edit_sort_order').value = sortOrder;
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

// Modal temizleme
document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});
";

// Footer include
include '../includes/admin_footer.php';
?>
