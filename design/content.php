<?php
/**
 * Design Panel - İçerik Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Sayfa bilgileri
$pageTitle = 'İçerik Yönetimi';
$pageDescription = 'Site içeriklerini düzenleyin';
$breadcrumbs = [
    ['title' => 'Design Panel', 'url' => 'index.php'],
    ['title' => 'İçerik Yönetimi']
];

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO content_management (section, key_name, value, type, created_at, updated_at)
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $_POST['section'],
                        $_POST['key_name'],
                        $_POST['value'],
                        $_POST['type']
                    ]);
                    
                    header('Location: content.php?success=İçerik başarıyla eklendi');
                    exit;
                    
                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE content_management SET
                            section = ?, key_name = ?, value = ?, type = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['section'],
                        $_POST['key_name'],
                        $_POST['value'],
                        $_POST['type'],
                        (int)$_POST['id']
                    ]);
                    
                    header('Location: content.php?success=İçerik başarıyla güncellendi');
                    exit;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM content_management WHERE id = ?");
                    $stmt->execute([(int)$_POST['id']]);
                    
                    header('Location: content.php?success=İçerik başarıyla silindi');
                    exit;
                    
                case 'bulk_update':
                    foreach ($_POST['contents'] as $id => $data) {
                        $stmt = $pdo->prepare("
                            UPDATE content_management SET value = ?, updated_at = NOW() WHERE id = ?
                        ");
                        $stmt->execute([$data['value'], (int)$id]);
                    }
                    
                    header('Location: content.php?success=İçerikler toplu olarak güncellendi');
                    exit;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// İçerikleri al
try {
    $stmt = $pdo->query("SELECT * FROM content_management ORDER BY section, key_name");
    $contents = $stmt->fetchAll();
    
    // Bölümlere göre grupla
    $contentsBySection = [];
    foreach ($contents as $content) {
        $contentsBySection[$content['section']][] = $content;
    }
} catch (Exception $e) {
    $contents = [];
    $contentsBySection = [];
    $error = "İçerikler alınamadı: " . $e->getMessage();
}

// Düzenleme için içerik al
$editContent = null;
if (isset($_GET['edit']) && $_GET['edit']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM content_management WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $editContent = $stmt->fetch();
    } catch (Exception $e) {
        $error = "İçerik bulunamadı";
    }
}

// Header include
include '../includes/design_header.php';
?>

<!-- İçerik Yönetimi -->
<div class="row">
    <div class="col-12">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Toplu Güncelleme Formu -->
<form method="POST" id="bulkUpdateForm">
    <input type="hidden" name="action" value="bulk_update">
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="design-card">
                <div class="design-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-edit me-2"></i>İçerik Yönetimi
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-save me-2"></i>Tümünü Kaydet
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contentModal" onclick="resetForm()">
                            <i class="bi bi-plus me-2"></i>Yeni İçerik
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($contentsBySection)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-file-alt text-muted" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mt-3">Henüz içerik bulunmuyor</h4>
                            <p class="text-muted">İlk içeriğinizi oluşturmak için "Yeni İçerik" butonuna tıklayın.</p>
                            <button type="button" class="btn btn-design-primary" data-bs-toggle="modal" data-bs-target="#contentModal" onclick="resetForm()">
                                <i class="bi bi-plus me-2"></i>İlk İçeriği Oluştur
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- İçerik Bölümleri -->
                        <div class="accordion" id="contentAccordion">
                            <?php foreach ($contentsBySection as $section => $sectionContents): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo md5($section); ?>">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo md5($section); ?>" aria-expanded="true">
                                            <i class="bi bi-folder-open me-2"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $section)); ?> Bölümü
                                            <span class="badge bg-primary ms-2"><?php echo count($sectionContents); ?></span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo md5($section); ?>" class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <?php foreach ($sectionContents as $content): ?>
                                                    <div class="col-lg-6">
                                                        <div class="card h-100">
                                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($content['key_name']); ?></h6>
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                            onclick="editContent(<?php echo $content['id']; ?>)"
                                                                            data-bs-toggle="modal" data-bs-target="#contentModal">
                                                                        <i class="bi bi-edit"></i>
                                                                    </button>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="action" value="delete">
                                                                        <input type="hidden" name="id" value="<?php echo $content['id']; ?>">
                                                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                                onclick="return confirm('Bu içeriği silmek istediğinizden emin misiniz?')">
                                                                            <i class="bi bi-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <?php if ($content['type'] === 'textarea'): ?>
                                                                    <textarea class="form-control" name="contents[<?php echo $content['id']; ?>][value]" 
                                                                              rows="4" data-auto-resize><?php echo htmlspecialchars($content['value']); ?></textarea>
                                                                <?php elseif ($content['type'] === 'image'): ?>
                                                                    <div class="mb-2">
                                                                        <?php if ($content['value']): ?>
                                                                            <img src="<?php echo htmlspecialchars($content['value']); ?>" 
                                                                                 class="img-fluid rounded" style="max-height: 150px;" alt="Content Image">
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <input type="url" class="form-control" name="contents[<?php echo $content['id']; ?>][value]" 
                                                                           value="<?php echo htmlspecialchars($content['value']); ?>" placeholder="Resim URL'si">
                                                                <?php elseif ($content['type'] === 'color'): ?>
                                                                    <div class="d-flex align-items-center">
                                                                        <input type="color" class="form-control form-control-color" 
                                                                               name="contents[<?php echo $content['id']; ?>][value]" 
                                                                               value="<?php echo htmlspecialchars($content['value']); ?>">
                                                                        <div class="color-preview ms-2" style="background-color: <?php echo htmlspecialchars($content['value']); ?>;"></div>
                                                                    </div>
                                                                <?php elseif ($content['type'] === 'json'): ?>
                                                                    <textarea class="form-control" name="contents[<?php echo $content['id']; ?>][value]" 
                                                                              rows="6" style="font-family: monospace; font-size: 0.9rem;"><?php echo htmlspecialchars($content['value']); ?></textarea>
                                                                    <small class="text-muted">JSON format</small>
                                                                <?php else: ?>
                                                                    <input type="text" class="form-control" name="contents[<?php echo $content['id']; ?>][value]" 
                                                                           value="<?php echo htmlspecialchars($content['value']); ?>">
                                                                <?php endif; ?>
                                                                
                                                                <small class="text-muted d-block mt-2">
                                                                    <i class="bi bi-tag me-1"></i><?php echo ucfirst($content['type']); ?> türü |
                                                                    <i class="bi bi-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($content['updated_at'])); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- İçerik Modal -->
<div class="modal fade" id="contentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="contentForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni İçerik Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="contentId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="section" class="form-label">Bölüm *</label>
                            <select class="form-select" id="section" name="section" required>
                                <option value="">Bölüm Seçin</option>
                                <option value="homepage">Ana Sayfa</option>
                                <option value="about">Hakkımızda</option>
                                <option value="services">Hizmetler</option>
                                <option value="contact">İletişim</option>
                                <option value="footer">Footer</option>
                                <option value="header">Header</option>
                                <option value="general">Genel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">İçerik Türü *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="text">Metin</option>
                                <option value="textarea">Uzun Metin</option>
                                <option value="image">Resim URL</option>
                                <option value="color">Renk</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="key_name" class="form-label">Anahtar Adı *</label>
                            <input type="text" class="form-control" id="key_name" name="key_name" required>
                            <small class="form-text text-muted">Örnek: site_title, contact_phone, hero_text</small>
                        </div>
                        <div class="col-12">
                            <label for="value" class="form-label">Değer *</label>
                            <div id="valueContainer">
                                <input type="text" class="form-control" id="value" name="value" required>
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

<!-- Hazır İçerik Şablonları -->
<div class="row mt-4">
    <div class="col-12">
        <div class="design-card">
            <div class="design-card-header">
                <h5 class="mb-0">
                    <i class="bi bi-magic me-2"></i>Hazır İçerik Şablonları
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Aşağıdaki şablonları kullanarak hızlıca içerik oluşturabilirsiniz:</p>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="bi bi-home text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6>Ana Sayfa Şablonu</h6>
                                <p class="small text-muted">Hero, hizmetler ve iletişim içerikleri</p>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="createTemplate('homepage')">
                                    <i class="bi bi-plus me-1"></i>Oluştur
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="bi bi-info-circle text-success mb-2" style="font-size: 2rem;"></i>
                                <h6>Hakkımızda Şablonu</h6>
                                <p class="small text-muted">Şirket bilgileri ve değerler</p>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="createTemplate('about')">
                                    <i class="bi bi-plus me-1"></i>Oluştur
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="bi bi-envelope text-info mb-2" style="font-size: 2rem;"></i>
                                <h6>İletişim Şablonu</h6>
                                <p class="small text-muted">İletişim formu ve bilgileri</p>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="createTemplate('contact')">
                                    <i class="bi bi-plus me-1"></i>Oluştur
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// İçerik verileri JSON olarak
const contents = <?php echo json_encode($contents); ?>;

function resetForm() {
    document.getElementById('contentForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Yeni İçerik Ekle';
    document.getElementById('contentId').value = '';
    updateValueField();
}

function editContent(id) {
    const content = contents.find(c => c.id == id);
    if (!content) return;
    
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'İçerik Düzenle';
    document.getElementById('contentId').value = content.id;
    document.getElementById('section').value = content.section;
    document.getElementById('key_name').value = content.key_name;
    document.getElementById('type').value = content.type;
    document.getElementById('value').value = content.value;
    
    updateValueField();
}

function updateValueField() {
    const type = document.getElementById('type').value;
    const container = document.getElementById('valueContainer');
    const currentValue = document.getElementById('value').value;
    
    let html = '';
    
    switch (type) {
        case 'textarea':
            html = `<textarea class="form-control" id="value" name="value" rows="4" required>${currentValue}</textarea>`;
            break;
        case 'image':
            html = `
                <input type="url" class="form-control" id="value" name="value" value="${currentValue}" placeholder="Resim URL'si" required>
                <small class="form-text text-muted">Geçerli bir resim URL'si girin</small>
            `;
            break;
        case 'color':
            html = `
                <div class="d-flex align-items-center">
                    <input type="color" class="form-control form-control-color" id="value" name="value" value="${currentValue || '#000000'}" required>
                    <div class="color-preview ms-2" style="background-color: ${currentValue || '#000000'};"></div>
                </div>
            `;
            break;
        case 'json':
            html = `
                <textarea class="form-control" id="value" name="value" rows="6" style="font-family: monospace;" required>${currentValue}</textarea>
                <small class="form-text text-muted">Geçerli JSON formatında girin</small>
            `;
            break;
        default:
            html = `<input type="text" class="form-control" id="value" name="value" value="${currentValue}" required>`;
            break;
    }
    
    container.innerHTML = html;
    
    // Color picker event listener
    if (type === 'color') {
        const colorInput = document.getElementById('value');
        const preview = container.querySelector('.color-preview');
        colorInput.addEventListener('change', function() {
            preview.style.backgroundColor = this.value;
        });
    }
}

// Type değiştiğinde value field'ını güncelle
document.getElementById('type').addEventListener('change', updateValueField);

// Şablon oluşturma
function createTemplate(templateType) {
    let templates = [];
    
    switch (templateType) {
        case 'homepage':
            templates = [
                {section: 'homepage', key_name: 'hero_title', value: 'Profesyonel ECU Hizmetleri', type: 'text'},
                {section: 'homepage', key_name: 'hero_subtitle', value: 'Güvenli, Hızlı ve Kaliteli Çözümler', type: 'text'},
                {section: 'homepage', key_name: 'hero_description', value: 'ECU programlama ve chip tuning alanında uzman ekibimizle hizmetinizdeyiz.', type: 'textarea'},
                {section: 'homepage', key_name: 'services_title', value: 'Hizmetlerimiz', type: 'text'},
                {section: 'homepage', key_name: 'services_description', value: 'Sunduğumuz profesyonel ECU hizmetleri', type: 'text'}
            ];
            break;
        case 'about':
            templates = [
                {section: 'about', key_name: 'company_name', value: 'Mr ECU', type: 'text'},
                {section: 'about', key_name: 'company_description', value: 'ECU alanında 10+ yıl deneyim', type: 'textarea'},
                {section: 'about', key_name: 'mission', value: 'Müşterilerimize en kaliteli hizmeti sunmak', type: 'textarea'},
                {section: 'about', key_name: 'vision', value: 'ECU alanında lider olmak', type: 'textarea'}
            ];
            break;
        case 'contact':
            templates = [
                {section: 'contact', key_name: 'office_address', value: 'İstanbul, Türkiye', type: 'text'},
                {section: 'contact', key_name: 'phone_number', value: '+90 (555) 123 45 67', type: 'text'},
                {section: 'contact', key_name: 'email_address', value: 'info@mrecu.com', type: 'text'},
                {section: 'contact', key_name: 'working_hours', value: '7/24 Hizmet', type: 'text'}
            ];
            break;
    }
    
    // Şablonları sırayla ekle
    templates.forEach((template, index) => {
        setTimeout(() => {
            // Modal'ı aç ve formu doldur
            document.getElementById('section').value = template.section;
            document.getElementById('key_name').value = template.key_name;
            document.getElementById('type').value = template.type;
            updateValueField();
            document.getElementById('value').value = template.value;
            
            // Son şablonda modal'ı aç
            if (index === templates.length - 1) {
                new bootstrap.Modal(document.getElementById('contentModal')).show();
            }
        }, index * 100);
    });
    
    showToast(`${templateType} şablonu hazırlandı`, 'info');
}

// Form submission
document.getElementById('contentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('content.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes('success=')) {
            showToast('İçerik başarıyla kaydedildi', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Kaydetme sırasında hata oluştu', 'error');
        }
    })
    .catch(error => {
        showToast('İşlem başarısız', 'error');
    });
});

// Auto-resize textareas
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
        autoResize(textarea);
        textarea.addEventListener('input', function() {
            autoResize(this);
        });
    });
});
</script>

<?php
// Footer include
include '../includes/design_footer.php';
?>
