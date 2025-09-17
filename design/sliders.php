<?php
/**
 * Design Panel - Hero Slider Yönetimi (Responsive Images)
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

// Boş değerleri NULL'a çeviren yardımcı fonksiyon
function nullable($value) {
    return $value === '' || $value === null ? null : $value;
}

// Sayfa bilgileri
$pageTitle = 'Hero Slider Yönetimi (Responsive)';
$pageDescription = 'Ana sayfa hero slider\'larını responsive resimlerle düzenleyin';
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
                            background_image, mobile_image, tablet_image, background_color, text_color, 
                            is_active, sort_order, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $id,
                        nullable($_POST['title']),
                        nullable($_POST['subtitle']),
                        nullable($_POST['description']),
                        nullable($_POST['button_text']),
                        nullable($_POST['button_link']),
                        nullable($_POST['background_image']),
                        nullable($_POST['mobile_image']),
                        nullable($_POST['tablet_image']),
                        $_POST['background_color'] ?? '#667eea',
                        $_POST['text_color'] ?? '#ffffff',
                        isset($_POST['is_active']) ? 1 : 0,
                        (int)$_POST['sort_order']
                    ]);
                    
                    setFlashMessage('Responsive slider başarıyla eklendi!', 'success');
                    header('Location: sliders.php');
                    exit;
                    
                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE design_sliders SET
                            title = ?, subtitle = ?, description = ?, button_text = ?, button_link = ?,
                            background_image = ?, mobile_image = ?, tablet_image = ?, 
                            background_color = ?, text_color = ?, is_active = ?, 
                            sort_order = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        nullable($_POST['title']),
                        nullable($_POST['subtitle']),
                        nullable($_POST['description']),
                        nullable($_POST['button_text']),
                        nullable($_POST['button_link']),
                        nullable($_POST['background_image']),
                        nullable($_POST['mobile_image']),
                        nullable($_POST['tablet_image']),
                        $_POST['background_color'] ?? '#667eea',
                        $_POST['text_color'] ?? '#ffffff',
                        isset($_POST['is_active']) ? 1 : 0,
                        (int)$_POST['sort_order'],
                        $_POST['id']
                    ]);
                    
                    setFlashMessage('Responsive slider başarıyla güncellendi!', 'success');
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
                    <i class="bi bi-images me-2"></i>Hero Slider Yönetimi (Responsive)
                </h5>
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#sliderModal" onclick="resetForm()">
                    <i class="bi bi-plus me-2"></i>Yeni Slider
                </button>
            </div>
            <div class="card-body">
                <!-- Flash Messages -->
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type']; ?> alert-dismissible fade show">
                        <i class="bi bi-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                        <?php echo htmlspecialchars($flashMessage['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Responsive Images Info -->
                <div class="alert alert-info mb-4">
                    <h6><i class="bi bi-info-circle me-2"></i>Responsive Resim Sistemi</h6>
                    <p class="mb-2">Her slider için 3 farklı boyutta resim yükleyebilirsiniz:</p>
                    <div class="row small">
                        <div class="col-md-4">
                            <strong>🖥️ Desktop:</strong> 1920x800px (önerilen)
                        </div>
                        <div class="col-md-4">
                            <strong>📱 Tablet:</strong> 1024x600px (önerilen)
                        </div>
                        <div class="col-md-4">
                            <strong>📲 Mobil:</strong> 768x400px (önerilen)
                        </div>
                    </div>
                </div>

                <?php if (empty($sliders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-images text-muted" style="font-size: 4rem;"></i>
                        <h4 class="text-muted mt-3">Henüz responsive slider bulunmuyor</h4>
                        <p class="text-muted">İlk responsive slider'ınızı oluşturmak için "Yeni Slider" butonuna tıklayın.</p>
                        <button type="button" class="btn btn-design-primary" data-bs-toggle="modal" data-bs-target="#sliderModal" onclick="resetForm()">
                            <i class="bi bi-plus me-2"></i>İlk Responsive Slider'ı Oluştur
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60">Sıra</th>
                                    <th>Görseller</th>
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
                                        <div class="d-flex gap-2">
                                            <!-- Desktop -->
                                            <div class="text-center">
                                                <img src="../<?php echo htmlspecialchars($slider['background_image'] ?? ''); ?>" 
                                                     alt="Desktop"
                                                     class="rounded border" style="width: 50px; height: 35px; object-fit: cover;"
                                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iMzUiIHZpZXdCb3g9IjAgMCA1MCAzNSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjM1IiBmaWxsPSIjZTllY2VmIi8+Cjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjNmM3NTdkIiBmb250LXNpemU9IjhweCIgZm9udC1mYW1pbHk9IkFyaWFsIj7wn5a05Y+4PC90ZXh0Pgo8L3N2Zz4='">
                                                <small class="d-block text-muted" style="font-size: 10px;">Desktop</small>
                                            </div>
                                            <!-- Tablet -->
                                            <div class="text-center">
                                                <img src="../<?php echo htmlspecialchars($slider['tablet_image'] ?? ''); ?>" 
                                                     alt="Tablet"
                                                     class="rounded border" style="width: 45px; height: 35px; object-fit: cover;"
                                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDUiIGhlaWdodD0iMzUiIHZpZXdCb3g9IjAgMCA0NSAzNSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ1IiBoZWlnaHQ9IjM1IiBmaWxsPSIjZjhmOWZhIi8+Cjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjNmM3NTdkIiBmb250LXNpemU9IjhweCIgZm9udC1mYW1pbHk9IkFyaWFsIj7wn5OxPC90ZXh0Pgo8L3N2Zz4='">
                                                <small class="d-block text-muted" style="font-size: 10px;">Tablet</small>
                                            </div>
                                            <!-- Mobile -->
                                            <div class="text-center">
                                                <img src="../<?php echo htmlspecialchars($slider['mobile_image'] ?? ''); ?>" 
                                                     alt="Mobile"
                                                     class="rounded border" style="width: 35px; height: 35px; object-fit: cover;"
                                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzUiIGhlaWdodD0iMzUiIHZpZXdCb3g9IjAgMCAzNSAzNSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjM1IiBoZWlnaHQ9IjM1IiBmaWxsPSIjZGNmNGZmIi8+Cjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjNmM3NTdkIiBmb250LXNpemU9IjhweCIgZm9udC1mYW1pbHk9IkFyaWFsIj7wn5OxPC90ZXh0Pgo8L3N2Zz4='">
                                                <small class="d-block text-muted" style="font-size: 10px;">Mobil</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($slider['title'] ?? 'Başlıksız'); ?></strong>
                                            <small class="d-block text-muted">
                                                <?php echo htmlspecialchars(substr($slider['description'] ?? '', 0, 50)) . '...'; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($slider['subtitle'] ?? '-'); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-<?php echo $slider['is_active'] ? 'success' : 'secondary'; ?>" 
                                                    onclick="return confirm('Durumu değiştirmek istediğinizden emin misiniz?')">
                                                <i class="bi bi-<?php echo $slider['is_active'] ? 'check' : 'times'; ?>"></i>
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
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Bu slider\'ı silmek istediğinizden emin misiniz?')">
                                                    <i class="bi bi-trash"></i>
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

<!-- Responsive Slider Modal -->
<div class="modal fade" id="sliderModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" id="sliderForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni Responsive Slider Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="sliderId">
                    
                    <!-- Temel Bilgiler -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">📝 Temel Bilgiler</h6>
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Ana Başlık</label>
                            <input type="text" class="form-control" id="title" name="title">
                        </div>
                        <div class="col-md-6">
                            <label for="subtitle" class="form-label">Alt Başlık</label>
                            <input type="text" class="form-control" id="subtitle" name="subtitle">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="button_text" class="form-label">Buton Metni</label>
                            <input type="text" class="form-control" id="button_text" name="button_text">
                        </div>
                        <div class="col-md-6">
                            <label for="button_link" class="form-label">Buton Linki</label>
                            <input type="text" class="form-control" id="button_link" name="button_link">
                        </div>
                    </div>

                    <!-- Responsive Resimler -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">🖼️ Responsive Resimler</h6>
                        </div>
                        
                        <!-- Desktop Resmi -->
                        <div class="col-md-4">
                            <label class="form-label">🖥️ Desktop Resmi <small class="text-muted">(2000x900px)</small></label>
                            <input type="hidden" id="background_image" name="background_image">
                            <div class="responsive-image-upload" data-target="background_image" data-type="desktop">
                                <div class="upload-area" id="desktopUploadArea">
                                    <i class="bi bi-display text-primary"></i>
                                    <p>Desktop resmi yükleyin</p>
                                    <button type="button" class="btn btn-outline-primary btn-sm">Resim Seç</button>
                                </div>
                                <div class="image-preview" id="desktopPreview" style="display: none;">
                                    <img class="preview-img" alt="Desktop preview">
                                    <div class="preview-info">
                                        <div class="preview-name"></div>
                                        <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="clearResponsiveImage('background_image', 'desktop')">Kaldır</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tablet Resmi -->
                        <div class="col-md-4">
                            <label class="form-label">📱 Tablet Resmi <small class="text-muted">(1024x600px)</small></label>
                            <input type="hidden" id="tablet_image" name="tablet_image">
                            <div class="responsive-image-upload" data-target="tablet_image" data-type="tablet">
                                <div class="upload-area" id="tabletUploadArea">
                                    <i class="bi bi-tablet text-info"></i>
                                    <p>Tablet resmi yükleyin</p>
                                    <button type="button" class="btn btn-outline-info btn-sm">Resim Seç</button>
                                </div>
                                <div class="image-preview" id="tabletPreview" style="display: none;">
                                    <img class="preview-img" alt="Tablet preview">
                                    <div class="preview-info">
                                        <div class="preview-name"></div>
                                        <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="clearResponsiveImage('tablet_image', 'tablet')">Kaldır</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mobile Resmi -->
                        <div class="col-md-4">
                            <label class="form-label">📲 Mobil Resmi <small class="text-muted">(700x525px)</small></label>
                            <input type="hidden" id="mobile_image" name="mobile_image">
                            <div class="responsive-image-upload" data-target="mobile_image" data-type="mobile">
                                <div class="upload-area" id="mobileUploadArea">
                                    <i class="bi bi-phone text-success"></i>
                                    <p>Mobil resmi yükleyin</p>
                                    <button type="button" class="btn btn-outline-success btn-sm">Resim Seç</button>
                                </div>
                                <div class="image-preview" id="mobilePreview" style="display: none;">
                                    <img class="preview-img" alt="Mobile preview">
                                    <div class="preview-info">
                                        <div class="preview-name"></div>
                                        <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="clearResponsiveImage('mobile_image', 'mobile')">Kaldır</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stil Ayarları -->
                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">🎨 Stil Ayarları</h6>
                        </div>
                        <div class="col-md-4">
                            <label for="background_color" class="form-label">Arkaplan Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="background_color" name="background_color" value="#667eea">
                                <div class="color-preview ms-2" id="bgColorPreview" style="background-color: #667eea;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="text_color" class="form-label">Metin Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="text_color" name="text_color" value="#ffffff">
                                <div class="color-preview ms-2" id="textColorPreview" style="background-color: #ffffff;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="sort_order" class="form-label">Sıra Numarası</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" min="1" value="1">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
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
                        <i class="bi bi-save me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gizli File Input'lar -->
<input type="file" id="hiddenFileInput" accept="image/*" style="display: none;">

<style>
.responsive-image-upload {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.upload-area {
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area i {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

.upload-area:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
}

.image-preview {
    padding: 15px;
    text-align: center;
}

.image-preview .preview-img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

.preview-info {
    margin-top: 10px;
}

.preview-name {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 5px;
    word-break: break-all;
}

.color-preview {
    width: 30px;
    height: 30px;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

.responsive-image-upload.dragover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

/* Toast Sistemi için Custom Styles */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 350px;
}

.custom-toast {
    margin-bottom: 10px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: none;
}

.custom-toast.alert-success {
    background: linear-gradient(45deg, #28a745, #34ce57);
    color: white;
}

.custom-toast.alert-error,
.custom-toast.alert-danger {
    background: linear-gradient(45deg, #dc3545, #e74c3c);
    color: white;
}

.custom-toast.alert-info {
    background: linear-gradient(45deg, #17a2b8, #1fc8db);
    color: white;
}

.custom-toast .btn-close {
    filter: brightness(0) invert(1);
}

/* Upload Area Disable Effect */
.upload-area.disabled {
    opacity: 0.5;
    pointer-events: none;
    position: relative;
}

.upload-area.disabled::after {
    content: "Yükleniyor...";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
}

@media (max-width: 768px) {
    .modal-xl {
        max-width: 95%;
    }
    
    .responsive-image-upload {
        margin-bottom: 20px;
    }
    
    .upload-area {
        padding: 20px 15px;
    }
    
    .upload-area i {
        font-size: 1.5rem;
    }
}
</style>

<!-- Slider verileri JSON olarak -->
<script>
const sliders = <?php echo json_encode($sliders); ?>;
let currentUploadTarget = null;

// Form reset fonksiyonu
function resetForm() {
    document.getElementById('sliderForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Yeni Responsive Slider Ekle';
    document.getElementById('sliderId').value = '';
    
    // Tüm resim field'larını temizle
    clearResponsiveImage('background_image', 'desktop');
    clearResponsiveImage('tablet_image', 'tablet');
    clearResponsiveImage('mobile_image', 'mobile');
    
    updateColorPreview(document.getElementById('background_color'), document.getElementById('bgColorPreview'));
    updateColorPreview(document.getElementById('text_color'), document.getElementById('textColorPreview'));
}

// Slider düzenleme fonksiyonu
function editSlider(id) {
    const slider = sliders.find(s => s.id === id);
    if (!slider) return;
    
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Responsive Slider Düzenle';
    document.getElementById('sliderId').value = slider.id;
    document.getElementById('title').value = slider.title || '';
    document.getElementById('subtitle').value = slider.subtitle || '';
    document.getElementById('description').value = slider.description || '';
    document.getElementById('button_text').value = slider.button_text || '';
    document.getElementById('button_link').value = slider.button_link || '';
    
    // Mevcut resimleri göster
    if (slider.background_image) {
        showExistingResponsiveImage(slider.background_image, 'background_image', 'desktop');
    }
    if (slider.tablet_image) {
        showExistingResponsiveImage(slider.tablet_image, 'tablet_image', 'tablet');
    }
    if (slider.mobile_image) {
        showExistingResponsiveImage(slider.mobile_image, 'mobile_image', 'mobile');
    }
    
    document.getElementById('background_color').value = slider.background_color || '#667eea';
    document.getElementById('text_color').value = slider.text_color || '#ffffff';
    document.getElementById('sort_order').value = slider.sort_order || 1;
    document.getElementById('is_active').checked = slider.is_active == 1;
    
    updateColorPreview(document.getElementById('background_color'), document.getElementById('bgColorPreview'));
    updateColorPreview(document.getElementById('text_color'), document.getElementById('textColorPreview'));
}

// Mevcut responsive resmi gösterme
function showExistingResponsiveImage(imageUrl, inputId, type) {
    if (!imageUrl) return;
    
    const input = document.getElementById(inputId);
    const uploadArea = document.getElementById(type + 'UploadArea');
    const preview = document.getElementById(type + 'Preview');
    const img = preview.querySelector('.preview-img');
    const nameDiv = preview.querySelector('.preview-name');
    
    input.value = imageUrl;
    
    let fullImageUrl = imageUrl;
    if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
        fullImageUrl = '../' + imageUrl;
    }
    
    img.src = fullImageUrl;
    nameDiv.textContent = 'Mevcut ' + type + ' resmi';
    
    uploadArea.style.display = 'none';
    preview.style.display = 'block';
}

// Responsive resim temizleme
function clearResponsiveImage(inputId, type) {
    const input = document.getElementById(inputId);
    const uploadArea = document.getElementById(type + 'UploadArea');
    const preview = document.getElementById(type + 'Preview');
    
    input.value = '';
    uploadArea.style.display = 'block';
    preview.style.display = 'none';
}

// Color preview update fonksiyonu
function updateColorPreview(inputElement, previewElement) {
    if (inputElement && previewElement) {
        previewElement.style.backgroundColor = inputElement.value;
    }
}

// Responsive upload sistemi
function initResponsiveImageUpload() {
    // Her responsive upload area için event listener'lar ekle
    document.querySelectorAll('.responsive-image-upload').forEach(container => {
        const target = container.dataset.target;
        const type = container.dataset.type;
        const uploadArea = container.querySelector('.upload-area');
        
        // Click event
        uploadArea.addEventListener('click', () => {
            console.log('Upload area clicked for type:', type);
            
            // Session monitoring
            console.log('Pre-upload session check:', {
                sessionExists: document.cookie.indexOf('MRECU_SECURE_SESSION') !== -1,
                timestamp: new Date().toISOString()
            });
            
            currentUploadTarget = { target, type };
            console.log('Set currentUploadTarget:', currentUploadTarget);
            document.getElementById('hiddenFileInput').click();
        });
        
        // Drag & Drop events
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            container.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            container.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                currentUploadTarget = { target, type };
                handleResponsiveFileUpload(files[0]);
            }
        });
    
    // Page unload warning - session korunması için
    let formSubmitting = false;
    
    // Form submit başladığında flag set et
    document.getElementById('sliderForm').addEventListener('submit', function() {
        formSubmitting = true;
    });
    
    // Sayfa kapanırken/yenilenirken uyarı
    window.addEventListener('beforeunload', function(e) {
        if (!formSubmitting) {
            // Eğer resim upload'ı sırasındaysa uyar
            if (currentUploadTarget) {
                e.preventDefault();
                e.returnValue = 'Resim yükleme devam ediyor. Sayfayı kapatmak istediğinizden emin misiniz?';
                return e.returnValue;
            }
        }
    });
});
    
    // Hidden file input change event
    document.getElementById('hiddenFileInput').addEventListener('change', (e) => {
        console.log('Hidden file input changed. Files:', e.target.files.length, 'Target:', currentUploadTarget);
        if (e.target.files.length > 0 && currentUploadTarget) {
            console.log('Starting file upload for:', e.target.files[0].name);
            handleResponsiveFileUpload(e.target.files[0]);
        } else {
            console.warn('No files selected or no upload target');
        }
    });
}

// Responsive dosya yükleme işlemi
function handleResponsiveFileUpload(file) {
    console.log('handleResponsiveFileUpload called with:', file.name, 'target:', currentUploadTarget);
    
    if (!currentUploadTarget) {
        console.error('No upload target set!');
        return;
    }
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/avif'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!allowedTypes.includes(file.type)) {
        console.error('File type not allowed:', file.type);
        showToast('Sadece JPEG, PNG ve WebP formatları desteklenir!', 'error');
        return;
    }
    
    if (file.size > maxSize) {
        console.error('File too large:', file.size);
        showToast('Dosya boyutu 5MB\'dan büyük olamaz!', 'error');
        return;
    }
    
    console.log('Starting upload for type:', currentUploadTarget.type);
    
    // Progress toast göster (modal yerine)
    showToast(currentUploadTarget.type + ' resmi yükleniyor...', 'info');
    
    // Upload area'yı disable et
    const uploadArea = document.getElementById(currentUploadTarget.type + 'UploadArea');
    if (uploadArea) {
        uploadArea.style.opacity = '0.5';
        uploadArea.style.pointerEvents = 'none';
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_responsive_image');
    formData.append('image', file);
    formData.append('type', currentUploadTarget.type);
    
    console.log('FormData created:', {
        action: 'upload_responsive_image',
        filename: file.name,
        type: currentUploadTarget.type,
        size: file.size
    });
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            console.log('Upload progress:', Math.round(percentComplete) + '%');
            // Progress sadece console'da, görsel progress bar yok
        }
    });
    
    xhr.addEventListener('load', () => {
        console.log('Upload completed. Status:', xhr.status, 'Response:', xhr.responseText);
        
        // Upload area'ı tekrar aktif et
        enableUploadArea(currentUploadTarget.type);
        
        try {
            const response = JSON.parse(xhr.responseText);
            console.log('Parsed response:', response);
            
            if (response.success) {
                setResponsiveUploadedImage(response.url, file.name, currentUploadTarget.target, currentUploadTarget.type);
                showToast(currentUploadTarget.type + ' resmi başarıyla yüklendi!', 'success');
                
                // Session durumunu kontrol et
                if (response.debug && response.debug.session_id) {
                    console.log('Upload sonrası session OK:', response.debug.session_id);
                }
            } else {
                console.error('Upload failed:', response.message);
                showToast('Yükleme başarısız: ' + response.message, 'error');
                
                // Eğer yetkisiz erişim hatasıysa session problemi var
                if (response.message.includes('Yetkisiz')) {
                    console.error('Session lost! Debug info:', response.debug);
                    showToast('Oturum süresi dolmuş! Sayfayı yenileyin.', 'warning');
                    
                    // 3 saniye sonra sayfayı yenile
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                }
            }
        } catch (e) {
            console.error('JSON parse error:', e, 'Raw response:', xhr.responseText);
            showToast('Yükleme sırasında bir hata oluştu!', 'error');
        }
        currentUploadTarget = null;
    });
    
    xhr.addEventListener('error', () => {
        console.error('XHR Error occurred');
        
        // Upload area'ı tekrar aktif et
        enableUploadArea(currentUploadTarget.type);
        
        showToast('Yükleme başarısız!', 'error');
        currentUploadTarget = null;
    });
    
    console.log('Sending XHR request to ajax.php');
    xhr.open('POST', 'ajax.php');
    xhr.send(formData);
}

// Yüklenen responsive resmi set etme
function setResponsiveUploadedImage(imageUrl, fileName, inputId, type) {
    console.log('setResponsiveUploadedImage called:', {
        imageUrl: imageUrl,
        fileName: fileName,
        inputId: inputId,
        type: type
    });
    
    const input = document.getElementById(inputId);
    const uploadArea = document.getElementById(type + 'UploadArea');
    const preview = document.getElementById(type + 'Preview');
    
    if (!input) {
        console.error('Input element not found:', inputId);
        return;
    }
    
    if (!uploadArea) {
        console.error('Upload area not found:', type + 'UploadArea');
        return;
    }
    
    if (!preview) {
        console.error('Preview element not found:', type + 'Preview');
        return;
    }
    
    const img = preview.querySelector('.preview-img');
    const nameDiv = preview.querySelector('.preview-name');
    
    if (!img || !nameDiv) {
        console.error('Preview img or name div not found in:', preview);
        return;
    }
    
    // Hidden input'ı set et
    input.value = imageUrl;
    console.log('Set input value:', inputId, '=', imageUrl);
    
    let fullImageUrl = imageUrl;
    if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
        fullImageUrl = '../' + imageUrl;
    }
    
    img.src = fullImageUrl;
    nameDiv.textContent = fileName;
    
    uploadArea.style.display = 'none';
    preview.style.display = 'block';
    
    console.log('Responsive image set successfully for type:', type);
    
    // Final verification
    console.log('Final input value check:', document.getElementById(inputId).value);
}

// Upload area helper functions
function disableUploadArea(type) {
    const uploadArea = document.getElementById(type + 'UploadArea');
    if (uploadArea) {
        uploadArea.classList.add('disabled');
    }
}

function enableUploadArea(type) {
    const uploadArea = document.getElementById(type + 'UploadArea');
    if (uploadArea) {
        uploadArea.classList.remove('disabled');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateColorPreview(document.getElementById('background_color'), document.getElementById('bgColorPreview'));
    updateColorPreview(document.getElementById('text_color'), document.getElementById('textColorPreview'));
    
    document.getElementById('background_color').addEventListener('change', function() {
        updateColorPreview(this, document.getElementById('bgColorPreview'));
    });
    
    document.getElementById('text_color').addEventListener('change', function() {
        updateColorPreview(this, document.getElementById('textColorPreview'));
    });
    
    initResponsiveImageUpload();
    
    // Form submit
    document.getElementById('sliderForm').addEventListener('submit', function(e) {
        // Session check before submit
        console.log('Pre-submit session check...');
        
        // Debug: Form submit öncesi değerleri kontrol et
        console.log('Form submit - Debug values:');
        console.log('background_image:', document.getElementById('background_image').value);
        console.log('tablet_image:', document.getElementById('tablet_image').value);
        console.log('mobile_image:', document.getElementById('mobile_image').value);
        
        // Boş resim field'ları varsa uyar
        const bgImage = document.getElementById('background_image').value;
        const tabletImage = document.getElementById('tablet_image').value;
        const mobileImage = document.getElementById('mobile_image').value;
        
        if (!bgImage && !tabletImage && !mobileImage) {
            console.warn('No images uploaded!');
            alert('En az bir resim yüklemelisiniz!');
            e.preventDefault();
            return false;
        }
        
        console.log('Form data being submitted:', {
            action: document.getElementById('formAction').value,
            title: document.getElementById('title').value,
            background_image: bgImage,
            tablet_image: tabletImage,
            mobile_image: mobileImage
        });
        
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin me-2"></i>Kaydediliyor...';
        }
    });
    
    const flashAlert = document.querySelector('.alert-dismissible');
    if (flashAlert) {
        setTimeout(() => {
            const closeBtn = flashAlert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    }
});

// Toast bildirimi
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `alert custom-toast alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    toast.innerHTML = `
        <div style="display: flex; align-items: center;">
            <i class="bi bi-${getToastIcon(type)} me-2"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 150);
        }
    }, 5000);
}

function getToastIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error':
        case 'danger': return 'exclamation-triangle';
        case 'info': return 'info-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}
</script>

<?php
// Footer include
include '../includes/design_footer.php';
?>
