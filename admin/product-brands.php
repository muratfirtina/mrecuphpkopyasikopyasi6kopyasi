<?php
/**
 * Mr ECU - Ürün Markalar Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Logo yükleme fonksiyonu
function uploadLogo($file) {
    if (!isset($file['tmp_name']) || !$file['tmp_name']) {
        return null;
    }
    
    $uploadDir = '../uploads/brands/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Sadece JPG, PNG, GIF ve WEBP formatları desteklenir.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz.');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'brand_' . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/brands/' . $filename;
    }
    
    throw new Exception('Dosya yüklenirken hata oluştu.');
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

// Marka ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $website = sanitize($_POST['website']);
    $metaTitle = sanitize($_POST['meta_title']);
    $metaDescription = sanitize($_POST['meta_description']);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = intval($_POST['sort_order']);
    
    if (empty($name)) {
        $error = 'Marka adı zorunludur.';
    } else {
        try {
            // Logo yükleme
            $logoPath = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = uploadLogo($_FILES['logo']);
            }
            
            // Slug oluştur
            $slug = createSlug($name);
            
            $stmt = $pdo->prepare("INSERT INTO product_brands (name, slug, description, logo, website, is_featured, is_active, sort_order, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $slug, $description, $logoPath, $website, $isFeatured, $isActive, $sortOrder, $metaTitle, $metaDescription]);
            
            if ($result) {
                $success = 'Marka başarıyla eklendi.';
            }
        } catch(Exception $e) {
            $error = 'Marka eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Marka güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brand'])) {
    $brandId = sanitize($_POST['brand_id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $website = sanitize($_POST['website']);
    $metaTitle = sanitize($_POST['meta_title']);
    $metaDescription = sanitize($_POST['meta_description']);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = intval($_POST['sort_order']);
    
    try {
        // Mevcut marka bilgilerini al
        $stmt = $pdo->prepare("SELECT logo FROM product_brands WHERE id = ?");
        $stmt->execute([$brandId]);
        $currentBrand = $stmt->fetch();
        
        // Marka bulunamadıysa hata ver
        if (!$currentBrand) {
            throw new Exception('Güncellenecek marka bulunamadı.');
        }
        
        // Logo yükleme
        $logoPath = $currentBrand['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            // Eski logoyu sil
            if ($logoPath && file_exists('../' . $logoPath)) {
                unlink('../' . $logoPath);
            }
            $logoPath = uploadLogo($_FILES['logo']);
        }
        
        $slug = createSlug($name);
        
        $stmt = $pdo->prepare("UPDATE product_brands SET name = ?, slug = ?, description = ?, logo = ?, website = ?, is_featured = ?, is_active = ?, sort_order = ?, meta_title = ?, meta_description = ? WHERE id = ?");
        $result = $stmt->execute([$name, $slug, $description, $logoPath, $website, $isFeatured, $isActive, $sortOrder, $metaTitle, $metaDescription, $brandId]);
        
        if ($result) {
            $success = 'Marka güncellendi.';
        }
    } catch(Exception $e) {
        $error = 'Marka güncellenirken hata oluştu: ' . $e->getMessage();
    }
}

// Marka silme
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $brandId = sanitize($_GET['delete']);
    
    try {
        // Logo dosyasını al ve sil
        $stmt = $pdo->prepare("SELECT logo FROM product_brands WHERE id = ?");
        $stmt->execute([$brandId]);
        $brand = $stmt->fetch();
        
        if ($brand && $brand['logo'] && file_exists('../' . $brand['logo'])) {
            unlink('../' . $brand['logo']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM product_brands WHERE id = ?");
        $result = $stmt->execute([$brandId]);
        
        if ($result) {
            $success = 'Marka başarıyla silindi.';
        }
    } catch(PDOException $e) {
        $error = 'Marka silinirken hata oluştu. Bu markaya bağlı ürünler olabilir.';
    }
}

// Markaları getir
try {
    $stmt = $pdo->query("
        SELECT pb.*, 
               COUNT(p.id) as product_count
        FROM product_brands pb 
        LEFT JOIN products p ON pb.id = p.brand_id 
        GROUP BY pb.id
        ORDER BY pb.sort_order, pb.name
    ");
    $brands = $stmt->fetchAll();
} catch(PDOException $e) {
    $brands = [];
}

$pageTitle = 'Ürün Markalar';
$pageDescription = 'Ürün markalarını yönetin';
$pageIcon = 'bi bi-award';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<style>
.brand-logo {
    max-width: 60px;
    max-height: 40px;
    object-fit: contain;
    border-radius: 4px;
}
.brand-card {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.brand-card:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-color: #5a5c69;
}
</style>

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

<!-- Marka İstatistikleri -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-award fa-2x text-primary mb-2"></i>
                <h4><?php echo count($brands); ?></h4>
                <small class="text-muted">Toplam Marka</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-star fa-2x text-warning mb-2"></i>
                <h4><?php echo count(array_filter($brands, function($b) { return $b['is_featured']; })); ?></h4>
                <small class="text-muted">Öne Çıkan</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-check-circle fa-2x text-success mb-2"></i>
                <h4><?php echo count(array_filter($brands, function($b) { return $b['is_active']; })); ?></h4>
                <small class="text-muted">Aktif Marka</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <i class="bi bi-box fa-2x text-info mb-2"></i>
                <h4><?php echo array_sum(array_column($brands, 'product_count')); ?></h4>
                <small class="text-muted">Toplam Ürün</small>
            </div>
        </div>
    </div>
</div>

<!-- Marka Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-award me-2"></i>Markalar (<?php echo count($brands); ?>)
        </h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
            <i class="bi bi-plus me-1"></i>Yeni Marka
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($brands)): ?>
            <div class="text-center py-4">
                <i class="bi bi-award fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Marka bulunamadı</h6>
                <p class="text-muted">Henüz marka eklenmemiş.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                    <i class="bi bi-plus me-1"></i>İlk Markayı Ekle
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>Marka</th>
                            <th>Açıklama</th>
                            <th>Ürün Sayısı</th>
                            <th>Durum</th>
                            <th>Sıralama</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($brand['logo']): ?>
                                            <img src="../<?php echo htmlspecialchars($brand['logo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                                                 class="brand-logo me-3">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 60px; height: 40px; font-size: 12px; color: white;">
                                                Logo Yok
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($brand['name']); ?></strong>
                                            <?php if ($brand['is_featured']): ?>
                                                <i class="bi bi-star text-warning ms-1" title="Öne Çıkan"></i>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($brand['slug']); ?></small>
                                            <?php if ($brand['website']): ?>
                                                <br>
                                                <a href="<?php echo htmlspecialchars($brand['website']); ?>" 
                                                   target="_blank" class="text-info text-decoration-none">
                                                    <i class="bi bi-external-link-alt"></i>
                                                    <?php echo parse_url($brand['website'], PHP_URL_HOST); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($brand['description']): ?>
                                        <small><?php echo mb_substr(htmlspecialchars($brand['description']), 0, 100); ?>
                                        <?php echo mb_strlen($brand['description']) > 100 ? '...' : ''; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Açıklama yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $brand['product_count']; ?> ürün</span>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-<?php echo $brand['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $brand['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                        <?php if ($brand['is_featured']): ?>
                                            <span class="badge bg-warning">Öne Çıkan</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?php echo $brand['sort_order']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editBrand(<?php echo htmlspecialchars(json_encode($brand)); ?>)">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="?delete=<?php echo $brand['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Bu markayı silmek istediğinizden emin misiniz? Bağlı ürünler etkilenebilir!')">
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

<!-- Marka Ekleme Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus me-2"></i>Yeni Marka Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Temel Bilgiler</h6>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Marka Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="logo" class="form-label">Marka Logosu</label>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                                <div class="form-text">Maksimum 5MB. JPG, PNG, GIF veya WEBP formatları desteklenir.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="website" class="form-label">Web Sitesi</label>
                                <input type="url" class="form-control" name="website" placeholder="https://example.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Açıklama</label>
                                <textarea class="form-control" name="description" rows="4"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="mb-3">Ayarlar</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label">Sıralama</label>
                                        <input type="number" class="form-control" name="sort_order" value="0" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Durum Ayarları</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" checked>
                                            <label class="form-check-label">Aktif</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_featured">
                                            <label class="form-check-label">Öne Çıkan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mb-3 mt-4">SEO Bilgileri</h6>
                            
                            <div class="mb-3">
                                <label for="meta_title" class="form-label">Meta Başlık</label>
                                <input type="text" class="form-control" name="meta_title" maxlength="255">
                                <div class="form-text">Arama motorları için sayfa başlığı</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Açıklama</label>
                                <textarea class="form-control" name="meta_description" rows="3" maxlength="160"></textarea>
                                <div class="form-text">Arama motorları için sayfa açıklaması (160 karakter)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_brand" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Marka Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Marka Düzenleme Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>Marka Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Temel Bilgiler</h6>
                            
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Marka Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_logo" class="form-label">Marka Logosu</label>
                                <div id="current_logo" class="mb-2" style="display: none;">
                                    <small class="text-muted">Mevcut logo:</small><br>
                                    <img id="current_logo_img" src="" alt="Mevcut logo" style="max-width: 150px; max-height: 100px; object-fit: contain;">
                                </div>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                                <div class="form-text">Yeni logo seçmezseniz mevcut logo korunur.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_website" class="form-label">Web Sitesi</label>
                                <input type="url" class="form-control" name="website" id="edit_website" placeholder="https://example.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Açıklama</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="4"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="mb-3">Ayarlar</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_sort_order" class="form-label">Sıralama</label>
                                        <input type="number" class="form-control" name="sort_order" id="edit_sort_order" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Durum Ayarları</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                            <label class="form-check-label">Aktif</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured">
                                            <label class="form-check-label">Öne Çıkan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mb-3 mt-4">SEO Bilgileri</h6>
                            
                            <div class="mb-3">
                                <label for="edit_meta_title" class="form-label">Meta Başlık</label>
                                <input type="text" class="form-control" name="meta_title" id="edit_meta_title" maxlength="255">
                                <div class="form-text">Arama motorları için sayfa başlığı</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_meta_description" class="form-label">Meta Açıklama</label>
                                <textarea class="form-control" name="meta_description" id="edit_meta_description" rows="3" maxlength="160"></textarea>
                                <div class="form-text">Arama motorları için sayfa açıklaması (160 karakter)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="update_brand" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageJS = "
function editBrand(brand) {
    document.getElementById('edit_brand_id').value = brand.id;
    document.getElementById('edit_name').value = brand.name || '';
    document.getElementById('edit_website').value = brand.website || '';
    document.getElementById('edit_description').value = brand.description || '';
    document.getElementById('edit_sort_order').value = brand.sort_order || 0;
    document.getElementById('edit_is_active').checked = brand.is_active == 1;
    document.getElementById('edit_is_featured').checked = brand.is_featured == 1;
    document.getElementById('edit_meta_title').value = brand.meta_title || '';
    document.getElementById('edit_meta_description').value = brand.meta_description || '';
    
    // Logo gösterimi
    const logoDiv = document.getElementById('current_logo');
    const logoImg = document.getElementById('current_logo_img');
    
    if (brand.logo) {
        logoImg.src = '../' + brand.logo;
        logoDiv.style.display = 'block';
    } else {
        logoDiv.style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('editBrandModal'));
    modal.show();
}

// Modal temizleme
document.getElementById('addBrandModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});

// Logo önizleme
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
";

// Footer include
include '../includes/admin_footer.php';
?>
