<?php
/**
 * Mr ECU - Ürün Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $categoryId = sanitize($_POST['category_id']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Ürün adı zorunludur.';
    } else {
        try {
            // Slug oluştur
            $slug = createSlug($name);
            
            $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, price, category_id, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $slug, $description, $price, $categoryId, $isActive]);
            
            if ($result) {
                $success = 'Ürün başarıyla eklendi.';
            }
        } catch(PDOException $e) {
            $error = 'Ürün eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Ürün güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $productId = sanitize($_POST['product_id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $categoryId = sanitize($_POST['category_id']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $slug = createSlug($name);
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, slug = ?, description = ?, price = ?, category_id = ?, is_active = ? WHERE id = ?");
        $result = $stmt->execute([$name, $slug, $description, $price, $categoryId, $isActive, $productId]);
        
        if ($result) {
            $success = 'Ürün güncellendi.';
        }
    } catch(PDOException $e) {
        $error = 'Ürün güncellenirken hata oluştu.';
    }
}

// Ürün silme
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $productId = sanitize($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $result = $stmt->execute([$productId]);
        
        if ($result) {
            $success = 'Ürün başarıyla silindi.';
        }
    } catch(PDOException $e) {
        $error = 'Ürün silinirken hata oluştu.';
    }
}

// Ürünleri getir
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.name
    ");
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $products = [];
}

// Kategorileri getir
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

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

$pageTitle = 'Ürün Yönetimi';
$pageDescription = 'Ürünleri yönetin';
$pageIcon = 'fas fa-box';

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

<!-- Ürün Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-box me-2"></i>Ürünler (<?php echo count($products); ?>)
        </h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-1"></i>Yeni Ürün
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="text-center py-4">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Ürün bulunamadı</h6>
                <p class="text-muted">Henüz ürün eklenmemiş.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-1"></i>İlk Ürünü Ekle
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Kategori</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($product['slug']); ?></small>
                                        <?php if ($product['description']): ?>
                                            <br>
                                            <small class="text-muted"><?php echo mb_substr(htmlspecialchars($product['description']), 0, 50); ?>...</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($product['category_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Kategori yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 2); ?> TL</strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $product['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editProduct('<?php echo $product['id']; ?>', '<?php echo htmlspecialchars($product['name']); ?>', '<?php echo htmlspecialchars($product['description']); ?>', '<?php echo $product['price']; ?>', '<?php echo $product['category_id']; ?>', <?php echo $product['is_active']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?')">
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

<!-- Ürün Ekleme Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Yeni Ürün Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Açıklama</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price" class="form-label">Fiyat (TL) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Kategori Seçin</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" checked>
                                    <label class="form-check-label">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Ürün Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ürün Düzenleme Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Ürün Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Açıklama</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Fiyat (TL) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Kategori</label>
                                <select class="form-select" name="category_id" id="edit_category_id">
                                    <option value="">Kategori Seçin</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                    <label class="form-check-label">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="update_product" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageJS = "
function editProduct(id, name, description, price, categoryId, isActive) {
    document.getElementById('edit_product_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_category_id').value = categoryId || '';
    document.getElementById('edit_is_active').checked = isActive == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    modal.show();
}

// Modal temizleme
document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});
";

// Footer include
include '../includes/admin_footer.php';
?>
