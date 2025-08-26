<?php
/**
 * Design Panel - Hero Slider Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Flash message fonksiyonları
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        // Mesajı göster ve sil
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

// Sayfa bilgileri
$pageTitle = 'Hero Slider Yönetimi';
$pageDescription = 'Ana sayfa hero slider\'larını düzenleyin';
$breadcrumbs = [
    ['title' => 'Design Panel', 'url' => 'index.php'],
    ['title' => 'Hero Slider']
];

// Flash message'ı al
$flashMessage = getFlashMessage();

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: POST verilerini logla
        error_log('Slider POST Data: ' . print_r($_POST, true));
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $id = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO design_sliders (
                            id, title, subtitle, description, button_text, button_link,
                            background_image, background_color, text_color, is_active, sort_order,
                            created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $id,
                        $_POST['title'],
                        $_POST['subtitle'],
                        $_POST['description'],
                        $_POST['button_text'],
                        $_POST['button_link'],
                        $_POST['background_image'],
                        $_POST['background_color'],
                        $_POST['text_color'],
                        isset($_POST['is_active']) ? 1 : 0,
                        (int)$_POST['sort_order']
                    ]);
                    
                    setFlashMessage('Slider başarıyla eklendi!', 'success');
                    header('Location: sliders.php');
                    exit;
                    
                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE design_sliders SET
                            title = ?, subtitle = ?, description = ?, button_text = ?, button_link = ?,
                            background_image = ?, background_color = ?, text_color = ?, is_active = ?, 
                            sort_order = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['subtitle'],
                        $_POST['description'],
                        $_POST['button_text'],
                        $_POST['button_link'],
                        $_POST['background_image'],
                        $_POST['background_color'],
                        $_POST['text_color'],
                        isset($_POST['is_active']) ? 1 : 0,
                        (int)$_POST['sort_order'],
                        $_POST['id']
                    ]);
                    
                    setFlashMessage('Slider başarıyla güncellendi!', 'success');
                    header('Location: sliders.php');
                    exit;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM design_sliders WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    setFlashMessage('Slider başarıyla silindi!', 'success');
                    header('Location: sliders.php');
                    exit;
                    
                case 'toggle_status':
                    $stmt = $pdo->prepare("UPDATE design_sliders SET is_active = !is_active, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    setFlashMessage('Slider durumu güncellendi!', 'success');
                    header('Location: sliders.php');
                    exit;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        setFlashMessage('Bir hata oluştu: ' . $e->getMessage(), 'error');
    }
}

// Slider'ları al
try {
    $stmt = $pdo->query("SELECT * FROM design_sliders ORDER BY sort_order ASC, created_at DESC");
    $sliders = $stmt->fetchAll();
} catch (Exception $e) {
    $sliders = [];
    $error = "Slider'lar alınamadı: " . $e->getMessage();
}

// Düzenleme için slider al
$editSlider = null;
if (isset($_GET['edit']) && $_GET['edit']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM design_sliders WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editSlider = $stmt->fetch();
    } catch (Exception $e) {
        $error = "Slider bulunamadı";
    }
}

// Header include
include '../includes/design_header.php';
?>

<!-- Slider Yönetimi -->
<div class="row">
    <div class="col-12">
        <div class="design-card">
            <div class="design-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-images me-2"></i>Hero Slider Yönetimi
                </h5>
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#sliderModal" onclick="resetForm()">
                    <i class="fas fa-plus me-2"></i>Yeni Slider
                </button>
            </div>
            <div class="card-body">
                <!-- Flash Messages -->
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type']; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                        <?php echo htmlspecialchars($flashMessage['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($sliders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-images text-muted" style="font-size: 4rem;"></i>
                        <h4 class="text-muted mt-3">Henüz slider bulunmuyor</h4>
                        <p class="text-muted">İlk slider'ınızı oluşturmak için "Yeni Slider" butonuna tıklayın.</p>
                        <button type="button" class="btn btn-design-primary" data-bs-toggle="modal" data-bs-target="#sliderModal" onclick="resetForm()">
                            <i class="fas fa-plus me-2"></i>İlk Slider'ı Oluştur
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60">Sıra</th>
                                    <th>Görsel</th>
                                    <th>Başlık</th>
                                    <th>Alt Başlık</th>
                                    <th>Durum</th>
                                    <th>Son Güncelleme</th>
                                    <th width="150">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sliders as $slider): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $slider['sort_order']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../<?php echo htmlspecialchars($slider['background_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($slider['title']); ?>"
                                                 class="rounded" style="width: 60px; height: 40px; object-fit: cover;"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjZjhmOWZhIi8+Cjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjNmM3NTdkIiBmb250LXNpemU9IjEwIj7inYw8L3RleHQ+Cjwvc3ZnPg=='">
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($slider['title']); ?></strong>
                                            <small class="d-block text-muted">
                                                <?php echo htmlspecialchars(substr($slider['description'], 0, 50)) . '...'; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($slider['subtitle']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-<?php echo $slider['is_active'] ? 'success' : 'secondary'; ?>" 
                                                    onclick="return confirm('Durumu değiştirmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-<?php echo $slider['is_active'] ? 'check' : 'times'; ?>"></i>
                                                <?php echo $slider['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($slider['updated_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editSlider('<?php echo $slider['id']; ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#sliderModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Bu slider\'ı silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
    </div>
</div>

<!-- Slider Modal -->
<div class="modal fade" id="sliderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="sliderForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni Slider Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="sliderId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Ana Başlık *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label for="subtitle" class="form-label">Alt Başlık *</label>
                            <input type="text" class="form-control" id="subtitle" name="subtitle" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Açıklama *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="button_text" class="form-label">Buton Metni *</label>
                            <input type="text" class="form-control" id="button_text" name="button_text" required>
                        </div>
                        <div class="col-md-6">
                            <label for="button_link" class="form-label">Buton Linki *</label>
                            <input type="text" class="form-control" id="button_link" name="button_link" required>
                        </div>
                        <div class="col-12">
                            <label for="background_image" class="form-label">Arkaplan Resmi *</label>
                            
                            <!-- Ana background image field (gizli) -->
                            <input type="hidden" id="background_image" name="background_image" required>
                            
                            <!-- Dosya Yükleme Alanı -->
                            <div class="upload-area" id="imageUploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h5>Resmi buraya sürükleyin veya tıklayın</h5>
                                <p class="text-muted mb-2">JPEG, PNG, WebP formatları desteklenir</p>
                                <p class="small text-muted">Maksimum dosya boyutu: 5MB</p>
                                <input type="file" class="d-none" id="imageFileInput" accept="image/*">
                                <button type="button" class="btn btn-design-primary btn-sm mt-3" onclick="document.getElementById('imageFileInput').click()">
                                    <i class="fas fa-folder-open me-2"></i>Bilgisayardan Resim Seç
                                </button>
                            </div>
                            
                            <!-- Upload Progress -->
                            <div class="upload-progress mt-3" id="uploadProgress" style="display: none;">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">Yükleniyor...</small>
                            </div>
                            
                            <!-- Seçili Resim Önizlemesi -->
                            <div class="image-preview-container mt-3" id="imagePreviewContainer" style="display: none;">
                                <div class="card border-success">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="preview-image-wrapper" style="width: 120px; height: 80px; border-radius: 8px; overflow: hidden; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                    <img id="currentImagePreview" 
                                                         style="width: 100%; height: 100%; object-fit: cover; display: none;" 
                                                         alt="Resim önizlemesi">
                                                    <div id="imageLoadingSpinner" style="display: none;">
                                                        <i class="fas fa-spinner fa-spin text-muted"></i>
                                                    </div>
                                                </div>
                                                <div class="ms-3">
                                                    <h6 class="text-success mb-1">
                                                        <i class="fas fa-check-circle me-2"></i>Resim Seçildi
                                                    </h6>
                                                    <div class="small text-muted" id="currentImageName">-</div>
                                                    <div class="small text-muted" id="currentImageSize">-</div>
                                                    <div class="small text-primary" id="currentImagePath" style="max-width: 200px; word-break: break-all;">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearImage()">
                                                <i class="fas fa-times me-1"></i>Kaldır
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="background_color" class="form-label">Arkaplan Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="background_color" name="background_color" value="#667eea">
                                <div class="color-preview ms-2" id="bgColorPreview" style="background-color: #667eea;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="text_color" class="form-label">Metin Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="text_color" name="text_color" value="#ffffff">
                                <div class="color-preview ms-2" id="textColorPreview" style="background-color: #ffffff;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="sort_order" class="form-label">Sıra Numarası *</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-design-primary">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Slider verileri JSON olarak -->
<script>
const sliders = <?php echo json_encode($sliders); ?>;

// Form reset fonksiyonu
function resetForm() {
    document.getElementById('sliderForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Yeni Slider Ekle';
    document.getElementById('sliderId').value = '';
    
    // Resim field'larını temizle
    clearImage();
    
    updateColorPreview(document.getElementById('background_color'), document.getElementById('bgColorPreview'));
    updateColorPreview(document.getElementById('text_color'), document.getElementById('textColorPreview'));
}

// Slider düzenleme fonksiyonu
function editSlider(id) {
    const slider = sliders.find(s => s.id === id);
    if (!slider) return;
    
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Slider Düzenle';
    document.getElementById('sliderId').value = slider.id;
    document.getElementById('title').value = slider.title;
    document.getElementById('subtitle').value = slider.subtitle;
    document.getElementById('description').value = slider.description;
    document.getElementById('button_text').value = slider.button_text;
    document.getElementById('button_link').value = slider.button_link;
    
    // Mevcut resmi göster (sadece önizleme için)
    if (slider.background_image) {
        showExistingImage(slider.background_image);
    }
    
    document.getElementById('background_color').value = slider.background_color;
    document.getElementById('text_color').value = slider.text_color;
    document.getElementById('sort_order').value = slider.sort_order;
    document.getElementById('is_active').checked = slider.is_active == 1;
    
    updateColorPreview(document.getElementById('background_color'), document.getElementById('bgColorPreview'));
    updateColorPreview(document.getElementById('text_color'), document.getElementById('textColorPreview'));
}

// Mevcut resmi gösterme (düzenleme için)
function showExistingImage(imageUrl) {
    console.log('showExistingImage called with:', imageUrl);
    
    // Ana field'a mevcut URL'i set et
    document.getElementById('background_image').value = imageUrl;
    
    // Elements
    const preview = document.getElementById('currentImagePreview');
    const container = document.getElementById('imagePreviewContainer');
    const nameDisplay = document.getElementById('currentImageName');
    const sizeDisplay = document.getElementById('currentImageSize');
    const pathDisplay = document.getElementById('currentImagePath');
    const spinner = document.getElementById('imageLoadingSpinner');
    
    // Show container and spinner
    container.style.display = 'block';
    spinner.style.display = 'block';
    preview.style.display = 'none';
    
    // Resim yükleme events
    preview.onerror = function() {
        console.error('Mevcut resim yüklenemedi:', imageUrl);
        spinner.style.display = 'none';
        nameDisplay.textContent = 'Resim yüklenemedi';
        sizeDisplay.textContent = 'Hata';
        pathDisplay.textContent = imageUrl;
        showToast('Mevcut resim görüntülenemedi', 'warning');
    };
    
    preview.onload = function() {
        console.log('Mevcut resim başarıyla yüklendi:', imageUrl);
        spinner.style.display = 'none';
        preview.style.display = 'block';
    };
    
    // Set data
    preview.src = imageUrl;
    nameDisplay.textContent = 'Mevcut resim';
    sizeDisplay.textContent = 'Var olan dosya';
    pathDisplay.textContent = imageUrl;
}

// Yeni yüklenen resmi set etme
function setUploadedImage(imageUrl, fileName, fileSize) {
    console.log('setUploadedImage called with:', imageUrl, fileName, fileSize);
    
    // Ana field'a URL'i set et
    document.getElementById('background_image').value = imageUrl;
    
    // Elements
    const preview = document.getElementById('currentImagePreview');
    const container = document.getElementById('imagePreviewContainer');
    const nameDisplay = document.getElementById('currentImageName');
    const sizeDisplay = document.getElementById('currentImageSize');
    const pathDisplay = document.getElementById('currentImagePath');
    const spinner = document.getElementById('imageLoadingSpinner');
    
    if (!preview || !container) {
        console.error('Preview elementleri bulunamadı!');
        return;
    }
    
    // Full URL oluştur - Eğer relative path ise full path yap
    let fullImageUrl = imageUrl;
    if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
        // design/ klasöründen assets/ klasörüne erişim için ../
        fullImageUrl = '../' + imageUrl;
    }
    
    console.log('Full image URL:', fullImageUrl);
    
    // Show container and spinner
    container.style.display = 'block';
    if (spinner) {
        spinner.style.display = 'block';
    }
    preview.style.display = 'none';
    
    // Resim yükleme events
    preview.onerror = function() {
        console.error('Yüklenen resim görüntülenemedi:', fullImageUrl);
        if (spinner) spinner.style.display = 'none';
        showToast('Resim yüklendi ama önizleme gösterilemedi', 'warning');
        if (nameDisplay) nameDisplay.textContent = fileName + ' (Önizleme hatası)';
        if (sizeDisplay) sizeDisplay.textContent = formatFileSize(fileSize);
        if (pathDisplay) pathDisplay.textContent = imageUrl;
    };
    
    preview.onload = function() {
        console.log('Yüklenen resim başarıyla görüntülendi:', fullImageUrl);
        if (spinner) spinner.style.display = 'none';
        preview.style.display = 'block';
        showToast('Resim başarıyla yüklendi!', 'success');
    };
    
    // Set data
    preview.src = fullImageUrl;
    if (nameDisplay) nameDisplay.textContent = fileName;
    if (sizeDisplay) sizeDisplay.textContent = formatFileSize(fileSize);
    if (pathDisplay) pathDisplay.textContent = imageUrl;
}

// Resim temizleme
function clearImage() {
    console.log('clearImage called');
    
    // Ana field'ı temizle
    document.getElementById('background_image').value = '';
    
    // Preview container'ı gizle
    document.getElementById('imagePreviewContainer').style.display = 'none';
    
    // Preview elements'leri sıfırla
    const preview = document.getElementById('currentImagePreview');
    const spinner = document.getElementById('imageLoadingSpinner');
    const nameDisplay = document.getElementById('currentImageName');
    const sizeDisplay = document.getElementById('currentImageSize');
    const pathDisplay = document.getElementById('currentImagePath');
    
    preview.src = '';
    preview.style.display = 'none';
    spinner.style.display = 'none';
    nameDisplay.textContent = '-';
    sizeDisplay.textContent = '-';
    pathDisplay.textContent = '-';
    
    // Upload area'yı sıfırla
    const uploadArea = document.getElementById('imageUploadArea');
    uploadArea.classList.remove('dragover');
    
    // Progress'i gizle
    document.getElementById('uploadProgress').style.display = 'none';
    
    showToast('Resim kaldırıldı', 'info');
}

// Color preview update fonksiyonu
function updateColorPreview(inputElement, previewElement) {
    if (inputElement && previewElement) {
        previewElement.style.backgroundColor = inputElement.value;
    }
}

// Dosya boyutu formatlama
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Drag & Drop İşlemleri
function initImageUpload() {
    const uploadArea = document.getElementById('imageUploadArea');
    const fileInput = document.getElementById('imageFileInput');
    
    // Drag & Drop events
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files[0]);
        }
    });
    
    // Click to upload
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileUpload(e.target.files[0]);
        }
    });
}

// Dosya yükleme işlemi
function handleFileUpload(file) {
    // Dosya validasyonu
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!allowedTypes.includes(file.type)) {
        showToast('Sadece JPEG, PNG ve WebP formatları desteklenir!', 'error');
        return;
    }
    
    if (file.size > maxSize) {
        showToast('Dosya boyutu 5MB\'dan büyük olamaz!', 'error');
        return;
    }
    
    // Progress göster
    const progressContainer = document.getElementById('uploadProgress');
    const progressBar = progressContainer.querySelector('.progress-bar');
    
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';
    
    // FormData oluştur
    const formData = new FormData();
    formData.append('action', 'upload_image');
    formData.append('image', file);
    
    // AJAX upload
    const xhr = new XMLHttpRequest();
    
    // Progress tracking
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBar.style.width = percentComplete + '%';
        }
    });
    
    // Upload complete
    xhr.addEventListener('load', () => {
        progressContainer.style.display = 'none';
        
        try {
            const response = JSON.parse(xhr.responseText);
            console.log('Upload Response:', response); // DEBUG
            
            if (response.success) {
                // Upload başarılı - direkt set et
                console.log('Setting uploaded image:', response.url); // DEBUG
                setUploadedImage(response.url, response.original_name, response.size);
            } else {
                console.error('Upload failed:', response.message); // DEBUG
                showToast('Yükleme başarısız: ' + response.message, 'error');
            }
        } catch (e) {
            console.error('JSON Parse Error:', e, 'Response:', xhr.responseText); // DEBUG
            showToast('Yükleme sırasında bir hata oluştu!', 'error');
        }
    });
    
    // Upload error
    xhr.addEventListener('error', () => {
        progressContainer.style.display = 'none';
        showToast('Yükleme başarısız!', 'error');
    });
    
    // Start upload
    xhr.open('POST', 'ajax.php');
    xhr.send(formData);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateColorPreview(document.getElementById('background_color'), document.getElementById('bgColorPreview'));
    updateColorPreview(document.getElementById('text_color'), document.getElementById('textColorPreview'));
    
    // Color preview events
    document.getElementById('background_color').addEventListener('change', function() {
        updateColorPreview(this, document.getElementById('bgColorPreview'));
    });
    
    document.getElementById('text_color').addEventListener('change', function() {
        updateColorPreview(this, document.getElementById('textColorPreview'));
    });
    
    // Initialize upload functionality
    initImageUpload();
    
    // Form validation
    document.getElementById('sliderForm').addEventListener('submit', function(e) {
        const backgroundImage = document.getElementById('background_image').value.trim();
        
        if (!backgroundImage) {
            e.preventDefault();
            showToast('Lütfen bilgisayarınızdan bir resim yükleyin!', 'error');
            return false;
        }
        
        // Form gönderilmeden önce buton'ı disable et
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Kaydediliyor...';
        }
    });
    
    // Flash message'ları otomatik kapat (5 saniye sonra)
    const flashAlert = document.querySelector('.alert-dismissible');
    if (flashAlert) {
        setTimeout(() => {
            const closeBtn = flashAlert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 5000);
    }
});

// Mevcut resmi gösterme (düzenleme için)
function showExistingImage(imageUrl) {
    console.log('showExistingImage called with:', imageUrl);
    
    // Ana field'a mevcut URL'i set et
    document.getElementById('background_image').value = imageUrl;
    
    // Elements
    const preview = document.getElementById('currentImagePreview');
    const container = document.getElementById('imagePreviewContainer');
    const nameDisplay = document.getElementById('currentImageName');
    const sizeDisplay = document.getElementById('currentImageSize');
    const pathDisplay = document.getElementById('currentImagePath');
    const spinner = document.getElementById('imageLoadingSpinner');
    
    if (!preview || !container) {
        console.error('showExistingImage: Preview elementleri bulunamadı!');
        return;
    }
    
    // Full URL oluştur
    let fullImageUrl = imageUrl;
    if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
        // design/ klasöründen assets/ klasörüne erişim için ../
        fullImageUrl = '../' + imageUrl;
    }
    
    console.log('showExistingImage: Full URL:', fullImageUrl);
    
    // Show container and spinner
    container.style.display = 'block';
    if (spinner) spinner.style.display = 'block';
    preview.style.display = 'none';
    
    // Resim yükleme events
    preview.onerror = function() {
        console.error('Mevcut resim yüklenemedi:', fullImageUrl);
        if (spinner) spinner.style.display = 'none';
        if (nameDisplay) nameDisplay.textContent = 'Resim yüklenemedi';
        if (sizeDisplay) sizeDisplay.textContent = 'Hata';
        if (pathDisplay) pathDisplay.textContent = imageUrl;
        showToast('Mevcut resim görüntülenemedi', 'warning');
    };
    
    preview.onload = function() {
        console.log('Mevcut resim başarıyla yüklendi:', fullImageUrl);
        if (spinner) spinner.style.display = 'none';
        preview.style.display = 'block';
    };
    
    // Set data
    preview.src = fullImageUrl;
    if (nameDisplay) nameDisplay.textContent = 'Mevcut resim';
    if (sizeDisplay) sizeDisplay.textContent = 'Var olan dosya';
    if (pathDisplay) pathDisplay.textContent = imageUrl;
}

// Toast bildirimi
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    // 5 saniye sonra otomatik kapat
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;';
    document.body.appendChild(container);
    return container;
}
</script>

<?php
// Footer include
include '../includes/design_footer.php';
?>