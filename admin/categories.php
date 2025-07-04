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

// İşlem kontrolü
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

// Upload klasörünü oluştur
$uploadDir = '../uploads/categories/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Kategori ekleme
if ($_POST && $action === 'add') {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)$_POST['sort_order'];
        $metaTitle = trim($_POST['meta_title']);
        $metaDescription = trim($_POST['meta_description']);
        
        // Slug oluştur
        $slug = createSlug($name);
        
        // Fotoğraf yükleme
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (in_array($_FILES['image']['type'], $allowedTypes) && $_FILES['image']['size'] <= $maxSize) {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'category-' . uniqid() . '.' . $extension;
                $fullPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                    $imagePath = 'uploads/categories/' . $filename;
                }
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, image, parent_id, is_active, sort_order, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $imagePath, $parentId, $isActive, $sortOrder, $metaTitle, $metaDescription]);
        
        $message = 'Kategori başarıyla eklendi.';
        $messageType = 'success';
        $action = 'list';
        
    } catch(PDOException $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Kategori güncelleme
if ($_POST && $action === 'edit' && $id) {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)$_POST['sort_order'];
        $metaTitle = trim($_POST['meta_title']);
        $metaDescription = trim($_POST['meta_description']);
        
        // Slug oluştur
        $slug = createSlug($name);
        
        // Mevcut kategoriyi al
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        
        $imagePath = $category['image'];
        
        // Fotoğraf yükleme
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (in_array($_FILES['image']['type'], $allowedTypes) && $_FILES['image']['size'] <= $maxSize) {
                // Eski fotoğrafı sil
                if ($imagePath && file_exists('../' . $imagePath)) {
                    unlink('../' . $imagePath);
                }
                
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'category-' . uniqid() . '.' . $extension;
                $fullPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                    $imagePath = 'uploads/categories/' . $filename;
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, parent_id = ?, is_active = ?, sort_order = ?, meta_title = ?, meta_description = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $imagePath, $parentId, $isActive, $sortOrder, $metaTitle, $metaDescription, $id]);
        
        $message = 'Kategori başarıyla güncellendi.';
        $messageType = 'success';
        $action = 'list';
        
    } catch(PDOException $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Kategori silme
if ($action === 'delete' && $id) {
    try {
        // Önce alt kategorileri kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$id]);
        $hasChildren = $stmt->fetchColumn() > 0;
        
        // Ürünleri kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $hasProducts = $stmt->fetchColumn() > 0;
        
        if ($hasChildren) {
            $message = 'Bu kategorinin alt kategorileri var. Önce alt kategorileri silin.';
            $messageType = 'warning';
        } elseif ($hasProducts) {
            $message = 'Bu kategoride ürünler var. Önce ürünleri başka kategoriye taşıyın.';
            $messageType = 'warning';
        } else {
            // Kategoriyi al
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch();
            
            // Fotoğrafı sil
            if ($category && $category['image'] && file_exists('../' . $category['image'])) {
                unlink('../' . $category['image']);
            }
            
            // Kategoriyi sil
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = 'Kategori başarıyla silindi.';
            $messageType = 'success';
        }
        
        $action = 'list';
        
    } catch(PDOException $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Kategorileri getir
$stmt = $pdo->query("
    SELECT c.*, p.name as parent_name,
           (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count,
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) as products_count
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY c.sort_order, c.name
");
$categories = $stmt->fetchAll();

// Form için kategorileri getir (parent seçimi için)
$parentCategories = array_filter($categories, function($cat) {
    return $cat['parent_id'] === null;
});

// Düzenleme için kategori bilgilerini getir
$editCategory = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $editCategory = $stmt->fetch();
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

$pageTitle = 'Kategori Yönetimi';
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
                        <i class="fas fa-tags me-2"></i>Kategori Yönetimi
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php if ($action === 'list'): ?>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Yeni Kategori
                                </a>
                            <?php else: ?>
                                <a href="categories.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <!-- Kategori Listesi -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Kategoriler (<?php echo count($categories); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($categories)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-tags text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3">Henüz kategori eklenmemiş.</p>
                                    <a href="?action=add" class="btn btn-primary">İlk Kategoriyi Ekle</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fotoğraf</th>
                                                <th>Kategori</th>
                                                <th>Üst Kategori</th>
                                                <th>Alt Kategoriler</th>
                                                <th>Ürün Sayısı</th>
                                                <th>Sıra</th>
                                                <th>Durum</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($category['image']): ?>
                                                            <img src="../<?php echo $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($category['slug']); ?></small>
                                                        <?php if ($category['description']): ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo mb_substr(htmlspecialchars($category['description']), 0, 50); ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($category['parent_name']): ?>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($category['parent_name']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($category['children_count'] > 0): ?>
                                                            <span class="badge bg-secondary"><?php echo $category['children_count']; ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($category['products_count'] > 0): ?>
                                                            <span class="badge bg-primary"><?php echo $category['products_count']; ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">0</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $category['sort_order']; ?></td>
                                                    <td>
                                                        <?php if ($category['is_active']): ?>
                                                            <span class="badge bg-success">Aktif</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Pasif</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="?action=delete&id=<?php echo $category['id']; ?>" 
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

                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <!-- Kategori Ekleme/Düzenleme Formu -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo $action === 'add' ? 'Yeni Kategori Ekle' : 'Kategori Düzenle'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Kategori Adı *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Açıklama</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="parent_id" class="form-label">Üst Kategori</label>
                                                    <select class="form-select" id="parent_id" name="parent_id">
                                                        <option value="">Ana Kategori</option>
                                                        <?php foreach ($parentCategories as $parent): ?>
                                                            <?php if ($action === 'edit' && $parent['id'] == $editCategory['id']) continue; ?>
                                                            <option value="<?php echo $parent['id']; ?>" 
                                                                    <?php echo ($editCategory['parent_id'] ?? '') == $parent['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($parent['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="sort_order" class="form-label">Sıra Numarası</label>
                                                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                                           value="<?php echo $editCategory['sort_order'] ?? 0; ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                       <?php echo ($editCategory['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Aktif
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Kategori Fotoğrafı</label>
                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                            <small class="form-text text-muted">Maksimum 5MB, JPG/PNG/GIF/WEBP formatları desteklenir.</small>
                                            
                                            <?php if ($editCategory && $editCategory['image']): ?>
                                                <div class="mt-2">
                                                    <img src="../<?php echo $editCategory['image']; ?>" alt="Mevcut fotoğraf" class="img-thumbnail" style="max-width: 200px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEO Bilgileri -->
                                <hr>
                                <h6>SEO Bilgileri</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="meta_title" class="form-label">Meta Başlık</label>
                                            <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                                   value="<?php echo htmlspecialchars($editCategory['meta_title'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="meta_description" class="form-label">Meta Açıklama</label>
                                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($editCategory['meta_description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="categories.php" class="btn btn-secondary">İptal</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        <?php echo $action === 'add' ? 'Kategori Ekle' : 'Güncelle'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
