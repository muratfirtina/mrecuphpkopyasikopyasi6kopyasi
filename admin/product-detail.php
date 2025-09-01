<?php
/**
 * Mr ECU - Admin Ürün Detay
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$product = null;
$productImages = [];

// Ürün ID'sini al
$productId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($productId)) {
    redirect('products.php');
}

// Ürün bilgilerini getir
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.name as category_name, c.slug as category_slug,
               pb.name as brand_name, pb.slug as brand_slug, pb.logo as brand_logo
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        redirect('products.php');
    }
} catch(PDOException $e) {
    $error = 'Ürün bilgileri alınamadı.';
}

// Ürün resimlerini getir
if ($product) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
        $stmt->execute([$productId]);
        $productImages = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Images yok, boş bırak
    }
}

// Kategorileri getir (modal için gerekli)
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Markaları getir (modal için gerekli)
try {
    $stmt = $pdo->query("SELECT * FROM product_brands WHERE is_active = 1 ORDER BY name");
    $brands = $stmt->fetchAll();
} catch(PDOException $e) {
    $brands = [];
}

$pageTitle = $product ? $product['name'] . ' - Ürün Detay' : 'Ürün Detay';
$pageDescription = 'Ürün detay bilgileri';
$pageIcon = 'bi bi-eye';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<style>
.product-detail-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.product-main-image {
    width: 100%;
    max-width: 400px;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

.product-thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.product-thumbnail:hover,
.product-thumbnail.active {
    border-color: #007bff;
}

.product-info-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.product-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.badge-featured { background: #ffc107; color: #000; }
.badge-active { background: #28a745; color: white; }
.badge-inactive { background: #dc3545; color: white; }
.badge-sale { background: #17a2b8; color: white; }

/* Modal için ek stiller (products.php'den kopyalandı) */
/* Fotoğraf yönetimi stilleri */
#current-product-images .card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}

#current-product-images .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

#current-product-images .card img {
    border-radius: 0.375rem 0.375rem 0 0;
}

#current-product-images .btn-group-sm .btn {
    padding: 0.25rem 0.375rem;
    font-size: 0.75rem;
}

.image-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    transition: border-color 0.15s ease-in-out;
}

.image-upload-area:hover {
    border-color: #007bff;
    background-color: #f8f9ff;
}

.image-upload-area.dragover {
    border-color: #007bff;
    background-color: #e7f3ff;
}

#current-image-count {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Ana resim badge'i */
.badge.bg-primary {
    background-color: #007bff !important;
}

/* Loading durumu */
.btn.loading {
    position: relative;
    color: transparent !important;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<?php if ($error): ?>
    <div class="alert alert-admin alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($product): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">
                <i class="bi bi-eye me-2 text-primary"></i>
                Ürün Detay
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Ürünler</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="products.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Geri Dön
            </a>
            <button type="button" class="btn btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)">
                <i class="bi bi-pencil-square me-1"></i>Düzenle
            </button>
        </div>
    </div>

    <div class="product-detail-container">
        <div class="row">
            <!-- Ürün Resimleri -->
            <div class="col-lg-5 p-4">
                <?php if (!empty($productImages)): ?>
                    <div class="mb-3">
                        <img src="../<?php echo htmlspecialchars($productImages[0]['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-main-image" id="mainProductImage">
                    </div>
                    
                    <?php if (count($productImages) > 1): ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($productImages as $index => $image): ?>
                                <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?> - <?php echo $index + 1; ?>"
                                     class="product-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('../<?php echo htmlspecialchars($image['image_path']); ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="product-main-image bg-light d-flex align-items-center justify-content-center">
                        <div class="text-center text-muted">
                            <i class="bi bi-image fa-3x mb-3"></i>
                            <p>Ürün görseli bulunmuyor</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ürün Bilgileri -->
            <div class="col-lg-7 p-4">
                <div class="product-info-card">
                    <h2 class="h4 mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>
                    
                    <!-- Durum Badge'leri -->
                    <div class="mb-3">
                        <span class="product-badge badge-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $product['is_active'] ? 'Aktif' : 'Pasif'; ?>
                        </span>
                        <?php if ($product['featured']): ?>
                            <span class="product-badge badge-featured">Öne Çıkan</span>
                        <?php endif; ?>
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="product-badge badge-sale">İndirimli</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Fiyat Bilgisi -->
                    <div class="mb-3">
                        <h5 class="text-success mb-1">
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <del class="text-muted me-2"><?php echo number_format($product['price'], 2); ?> TL</del>
                                <?php echo number_format($product['sale_price'], 2); ?> TL
                                <?php 
                                $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                                echo "<small class='text-danger'>({$discount}% indirim)</small>";
                                ?>
                            <?php else: ?>
                                <?php echo number_format($product['price'], 2); ?> TL
                            <?php endif; ?>
                        </h5>
                    </div>
                    
                    <!-- Marka ve Kategori -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Marka:</strong>
                            <?php if ($product['brand_name']): ?>
                                <span class="badge bg-warning"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Belirlenmemiş</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Kategori:</strong>
                            <?php if ($product['category_name']): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Belirlenmemiş</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Teknik Bilgiler -->
                    <div class="row mb-3">
                        <?php if ($product['sku']): ?>
                            <div class="col-md-6">
                                <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <strong>Stok:</strong> 
                            <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $product['stock_quantity']; ?> adet
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($product['weight'] || $product['dimensions']): ?>
                        <div class="row mb-3">
                            <?php if ($product['weight']): ?>
                                <div class="col-md-6">
                                    <strong>Ağırlık:</strong> <?php echo $product['weight']; ?> kg
                                </div>
                            <?php endif; ?>
                            <?php if ($product['dimensions']): ?>
                                <div class="col-md-6">
                                    <strong>Boyutlar:</strong> <?php echo htmlspecialchars($product['dimensions']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Kısa Açıklama -->
                    <?php if ($product['short_description']): ?>
                        <div class="mb-3">
                            <strong>Kısa Açıklama:</strong>
                            <p class="text-muted"><?php echo htmlspecialchars($product['short_description']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- SEO Bilgileri -->
                <?php if ($product['meta_title'] || $product['meta_description']): ?>
                    <div class="product-info-card">
                        <h5 class="mb-3"><i class="bi bi-search me-2"></i>SEO Bilgileri</h5>
                        
                        <?php if ($product['meta_title']): ?>
                            <div class="mb-2">
                                <strong>Meta Başlık:</strong>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($product['meta_title']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['meta_description']): ?>
                            <div class="mb-2">
                                <strong>Meta Açıklama:</strong>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($product['meta_description']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <strong>SEO URL:</strong>
                            <code>/urun/<?php echo htmlspecialchars($product['slug']); ?></code>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Detaylı Açıklama -->
        <?php if ($product['description']): ?>
            <div class="row">
                <div class="col-12">
                    <div class="product-info-card">
                        <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Detaylı Açıklama</h5>
                        <div class="product-description">
                            <?php echo $product['description']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alert alert-admin alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Ürün bulunamadı.
    </div>
<?php endif; ?>

<!-- Düzenleme Modal (products.php'den kopyalandı) -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>Ürün Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    
                    <!-- Temel Bilgiler -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Ürün Adı *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_slug" class="form-label">URL Slug</label>
                                <input type="text" class="form-control" id="edit_slug" name="slug">
                                <div class="form-text">URL'de görünecek format (otomatik oluşturulur)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_sku" class="form-label">SKU (Stok Kodu)</label>
                                <input type="text" class="form-control" id="edit_sku" name="sku">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_short_description" class="form-label">Kısa Açıklama</label>
                                <textarea class="form-control" id="edit_short_description" name="short_description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_price" class="form-label">Fiyat (TL) *</label>
                                        <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_sale_price" class="form-label">İndirimli Fiyat (TL)</label>
                                        <input type="number" class="form-control" id="edit_sale_price" name="sale_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_stock_quantity" class="form-label">Stok Miktarı</label>
                                <input type="number" class="form-control" id="edit_stock_quantity" name="stock_quantity" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Kategori Seçin</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_brand_id" class="form-label">Marka</label>
                                <select class="form-select" id="edit_brand_id" name="brand_id">
                                    <option value="">Marka Seçin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Açıklama -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Detaylı Açıklama</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="8"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ek Bilgiler -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_weight" class="form-label">Ağırlık (kg)</label>
                                <input type="number" class="form-control" id="edit_weight" name="weight" step="0.01" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_dimensions" class="form-label">Boyutlar</label>
                                <input type="text" class="form-control" id="edit_dimensions" name="dimensions" placeholder="Örn: 10x20x30 cm">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_sort_order" class="form-label">Sıralama</label>
                                <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durum Ayarları</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">Aktif</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_featured" name="featured">
                                    <label class="form-check-label" for="edit_featured">Öne Çıkan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mevcut Ürün Resimleri -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">
                                <i class="bi bi-images me-2"></i>Mevcut Resimler
                                <span class="badge bg-info ms-2" id="current-image-count">0</span>
                            </h6>
                            <div id="current-product-images" class="row g-2 mb-3"></div>
                            
                            <!-- Yeni Resim Ekleme -->
                            <div class="mt-3">
                                <label class="form-label">Yeni Resim Ekle</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="edit_new_images" 
                                           accept="image/*" multiple>
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="addNewProductImages()">
                                        <i class="bi bi-upload me-1"></i>Yükle
                                    </button>
                                </div>
                                <div class="form-text">Birden fazla resim seçebilirsiniz. Maksimum 10MB per dosya.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Bölümü -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">SEO Bilgileri</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_meta_title" class="form-label">Meta Başlık</label>
                                        <input type="text" class="form-control" id="edit_meta_title" name="meta_title" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_meta_description" class="form-label">Meta Açıklama</label>
                                        <textarea class="form-control" id="edit_meta_description" name="meta_description" rows="3" maxlength="160"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Kodları (products.php'den kopyalandı) -->
<script>
// Ürün düzenleme formunu AJAX ile gönder
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editProductForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Sayfanın yeniden yüklenmesini engelle

        const formData = new FormData(this);

        // update_product parametresini ekle (sunucu tarafı kontrolü için)
        formData.append('update_product', '1');

        fetch('ajax/update-product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ağ hatası: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message || 'Ürün başarıyla güncellendi.');
                // Modalı kapat
                bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                // Sayfayı yenile
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('AJAX hatası:', error);
            alert('Bir hata oluştu: ' + error.message);
        });
    });
});

// Global değişkenler
let currentProductId = null;

// Ürün resmi değiştirme fonksiyonu (mevcut)
function changeMainImage(imageSrc, thumbnail) {
    document.getElementById('mainProductImage').src = imageSrc;
    
    // Thumbnail aktifliğini değiştir
    document.querySelectorAll('.product-thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    thumbnail.classList.add('active');
}

// Ürün düzenleme modalını aç
function editProduct(productId) {
    currentProductId = productId;
    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    
    fetch('ajax/get-product-details.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                
                document.getElementById('edit_product_id').value = product.id;
                document.getElementById('edit_name').value = product.name || '';
                document.getElementById('edit_slug').value = product.slug || '';
                document.getElementById('edit_sku').value = product.sku || '';
                document.getElementById('edit_short_description').value = product.short_description || '';
                document.getElementById('edit_description').value = product.description || '';
                document.getElementById('edit_price').value = product.price || '';
                document.getElementById('edit_sale_price').value = product.sale_price || '';
                document.getElementById('edit_stock_quantity').value = product.stock_quantity || 0;
                document.getElementById('edit_weight').value = product.weight || '';
                document.getElementById('edit_dimensions').value = product.dimensions || '';
                document.getElementById('edit_sort_order').value = product.sort_order || 0;
                document.getElementById('edit_meta_title').value = product.meta_title || '';
                document.getElementById('edit_meta_description').value = product.meta_description || '';
                
                document.getElementById('edit_is_active').checked = product.is_active == 1;
                document.getElementById('edit_featured').checked = product.featured == 1;
                
                const categorySelect = document.getElementById('edit_category_id');
                categorySelect.innerHTML = '<option value="">Kategori Seçin</option>';
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    option.selected = category.id == product.category_id;
                    categorySelect.appendChild(option);
                });
                
                const brandSelect = document.getElementById('edit_brand_id');
                brandSelect.innerHTML = '<option value="">Marka Seçin</option>';
                data.brands.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.id;
                    option.textContent = brand.name;
                    option.selected = brand.id == product.brand_id;
                    brandSelect.appendChild(option);
                });
                
                // Fotoğrafları yükle
                loadProductImages(data.images);
                
                modal.show();
            } else {
                alert('Ürün bilgileri alınamadı: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ürün bilgileri alınamadı:', error);
            alert('Bir hata oluştu.');
        });
}

// Fotoğrafları yükle ve göster
function loadProductImages(images) {
    const container = document.getElementById('current-product-images');
    const countBadge = document.getElementById('current-image-count');
    
    if (!container) return;
    
    container.innerHTML = '';
    countBadge.textContent = images.length;
    
    if (images.length === 0) {
        container.innerHTML = '<div class="col-12 text-center py-4"><p class="text-muted mb-0"><i class="bi bi-image fa-2x mb-2"></i><br>Henüz resim eklenmemiş</p></div>';
        return;
    }
    
    images.forEach(image => {
        const imageCol = document.createElement('div');
        imageCol.classList.add('col-md-3', 'col-sm-4', 'col-6');
        
        const primaryBadge = image.is_primary == 1 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1"><i class="bi bi-star"></i> Ana</span>' : '';
        const primaryButton = image.is_primary != 1 ? 
            '<button type="button" class="btn btn-outline-warning" onclick="setPrimaryImage(' + image.id + ')" title="Ana Resim Yap"><i class="bi bi-star"></i></button>' : 
            '<button type="button" class="btn btn-warning" disabled><i class="bi bi-star"></i></button>';
        
        imageCol.innerHTML = 
            '<div class="card position-relative">' +
                '<img src="../' + image.image_path + '" class="card-img-top" style="height: 120px; object-fit: cover;">' +
                primaryBadge +
                '<div class="card-body p-2">' +
                    '<div class="btn-group btn-group-sm w-100">' +
                        primaryButton +
                        '<button type="button" class="btn btn-outline-danger" onclick="deleteProductImage(' + image.id + ')" title="Sil">' +
                            '<i class="bi bi-trash"></i>' +
                        '</button>' +
                    '</div>' +
                    '<small class="text-muted d-block mt-1">' + (image.alt_text || 'Resim') + '</small>' +
                '</div>' +
            '</div>';
        
        container.appendChild(imageCol);
    });
}

// Yeni fotoğraf ekleme
function addNewProductImages() {
    if (!currentProductId) {
        alert('Geçersiz ürün ID');
        return;
    }
    
    const fileInput = document.getElementById('edit_new_images');
    const files = fileInput.files;
    
    if (files.length === 0) {
        alert('Lütfen en az bir resim seçin');
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', currentProductId);
    
    for (let i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
    }
    
    fetch('ajax/add-product-images.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            fileInput.value = '';
            refreshProductImages();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Resim yükleme hatası:', error);
        alert('Bir hata oluştu.');
    });
}

// Fotoğrafı sil
function deleteProductImage(imageId) {
    if (!confirm('Bu resmi silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('ajax/delete-product-image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'image_id=' + imageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshProductImages();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Resim silme hatası:', error);
        alert('Bir hata oluştu.');
    });
}

// Ana resim belirle
function setPrimaryImage(imageId) {
    fetch('ajax/set-primary-image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'image_id=' + imageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshProductImages();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Ana resim belirleme hatası:', error);
        alert('Bir hata oluştu.');
    });
}

// Fotoğrafları yeniden yükle
function refreshProductImages() {
    if (!currentProductId) return;
    
    fetch('ajax/get-product-details.php?id=' + currentProductId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadProductImages(data.images);
            }
        })
        .catch(error => {
            console.error('Fotoğraflar yeniden yüklenemedi:', error);
        });
}

console.log('Product-detail.js yüklendi - modal fonksiyonları hazır!');
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
