<?php
/**
 * Mr ECU - Email Template Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Email template'lerini getir
function getEmailTemplates() {
    $templateDir = __DIR__ . '/../email_templates/';
    $templates = [];
    
    if (is_dir($templateDir)) {
        $files = scandir($templateDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
                $templateKey = pathinfo($file, PATHINFO_FILENAME);
                $templates[$templateKey] = [
                    'file' => $file,
                    'path' => $templateDir . $file,
                    'name' => ucwords(str_replace('_', ' ', $templateKey)),
                    'size' => filesize($templateDir . $file),
                    'modified' => filemtime($templateDir . $file)
                ];
            }
        }
    }
    
    return $templates;
}

// Template içeriğini getir
function getTemplateContent($templateKey) {
    $templatePath = __DIR__ . '/../email_templates/' . $templateKey . '.html';
    if (file_exists($templatePath)) {
        return file_get_contents($templatePath);
    }
    return null;
}

// Template'i güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_template') {
        $templateKey = sanitize($_POST['template_key']);
        $templateContent = $_POST['template_content']; // HTML içerik olduğu için sanitize etme
        
        $templatePath = __DIR__ . '/../email_templates/' . $templateKey . '.html';
        
        if (file_exists($templatePath)) {
            // Backup oluştur
            $backupPath = __DIR__ . '/../email_templates/backups/';
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $backupFile = $backupPath . $templateKey . '_' . date('Y-m-d_H-i-s') . '.html';
            copy($templatePath, $backupFile);
            
            // Template'i güncelle
            if (file_put_contents($templatePath, $templateContent)) {
                $success = "Template '$templateKey' başarıyla güncellendi.";
            } else {
                $error = 'Template güncellenemedi.';
            }
        } else {
            $error = 'Template bulunamadı.';
        }
    }
    
    elseif ($action === 'preview_template') {
        $templateKey = sanitize($_POST['template_key']);
        $templateContent = $_POST['template_content'];
        
        // Preview için test verileri
        $testVariables = [
            'site_name' => 'Mr ECU',
            'user_name' => 'Test Kullanıcı',
            'verification_url' => 'http://localhost/verify.php?token=test123',
            'reset_code' => '123456',
            'reset_url' => 'http://localhost/reset-password.php',
            'file_name' => 'test_file.bin',
            'brand' => 'BMW',
            'model' => '3 Series',
            'year' => '2020',
            'admin_notes' => 'Bu bir test notu.',
            'download_url' => 'http://localhost/download.php?id=test',
            'current_date' => date('d.m.Y'),
            'current_datetime' => date('d.m.Y H:i:s'),
            'contact_email' => 'mr.ecu@outlook.com',
            'site_url' => 'http://localhost',
            'expiry_minutes' => '15',
            'user_ip' => '127.0.0.1',
            'request_time' => date('d.m.Y H:i:s')
        ];
        
        // Değişkenleri değiştir
        foreach ($testVariables as $key => $value) {
            $templateContent = str_replace('{{' . $key . '}}', $value, $templateContent);
        }
        
        // Preview modunda göster
        echo $templateContent;
        exit;
    }
}

$templates = getEmailTemplates();
$selectedTemplate = $_GET['template'] ?? '';
$templateContent = $selectedTemplate ? getTemplateContent($selectedTemplate) : '';

$pageTitle = 'Email Template Yönetimi';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Email Template Yönetimi
                </h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Template Listesi -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-list me-2"></i>
                                Email Template'leri
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($templates as $key => $template): ?>
                                <a href="?template=<?php echo $key; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $selectedTemplate === $key ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo $template['name']; ?></h6>
                                        <small><?php echo round($template['size'] / 1024, 1); ?> KB</small>
                                    </div>
                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <?php echo $template['file']; ?>
                                        </small>
                                    </p>
                                    <small class="text-muted">
                                        Son güncelleme: <?php echo date('d.m.Y H:i', $template['modified']); ?>
                                    </small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Template Değişkenleri
                            </h6>
                        </div>
                        <div class="card-body">
                            <small class="text-muted">
                                Template'lerde kullanabileceğiniz değişkenler:
                            </small>
                            <div class="mt-2">
                                <code class="small d-block mb-1">{{site_name}}</code>
                                <code class="small d-block mb-1">{{user_name}}</code>
                                <code class="small d-block mb-1">{{verification_url}}</code>
                                <code class="small d-block mb-1">{{reset_code}}</code>
                                <code class="small d-block mb-1">{{file_name}}</code>
                                <code class="small d-block mb-1">{{admin_notes}}</code>
                                <code class="small d-block mb-1">{{download_url}}</code>
                                <code class="small d-block mb-1">{{current_date}}</code>
                                <code class="small d-block">{{contact_email}}</code>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Template Editörü -->
                <div class="col-md-8">
                    <?php if ($selectedTemplate && $templateContent): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-code-square me-2"></i>
                                <?php echo $templates[$selectedTemplate]['name']; ?> Template'i
                            </h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="previewTemplate()">
                                    <i class="bi bi-eye me-1"></i>Önizleme
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="resetTemplate()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Sıfırla
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <form method="POST" id="templateForm">
                                <input type="hidden" name="action" value="update_template">
                                <input type="hidden" name="template_key" value="<?php echo $selectedTemplate; ?>">
                                
                                <textarea name="template_content" id="templateEditor" style="width: 100%; height: 500px; border: none; font-family: monospace; font-size: 14px; padding: 15px;"><?php echo htmlspecialchars($templateContent); ?></textarea>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-2"></i>
                                        Template'i Kaydet
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="location.reload()">
                                        <i class="bi bi-x-lg me-1"></i>
                                        İptal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">Template Seçin</h5>
                            <p class="text-muted">Düzenlemek için sol taraftan bir email template'i seçin.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// Template önizleme
function previewTemplate() {
    const form = document.getElementById('templateForm');
    const formData = new FormData(form);
    formData.set('action', 'preview_template');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const previewFrame = document.getElementById('previewFrame');
        const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        
        // Iframe'e HTML içeriği yükle
        previewFrame.srcdoc = html;
        previewModal.show();
    })
    .catch(error => {
        alert('Önizleme yüklenirken hata oluştu: ' + error);
    });
}

// Template'i sıfırla
function resetTemplate() {
    if (confirm('Template içeriğini orijinal haline döndürmek istediğinizden emin misiniz?')) {
        location.reload();
    }
}

// Değişiklik takibi
let originalContent = '';
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('templateEditor');
    if (editor) {
        originalContent = editor.value;
        
        editor.addEventListener('input', function() {
            const saveBtn = document.querySelector('button[type="submit"]');
            if (this.value !== originalContent) {
                saveBtn.classList.remove('btn-primary');
                saveBtn.classList.add('btn-success');
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Değişiklikleri Kaydet';
            } else {
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-primary');
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Template\'i Kaydet';
            }
        });
    }
});

// Ctrl+S ile kaydet
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('templateForm').submit();
    }
});
</script>

<style>
.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

#templateEditor {
    resize: vertical;
    min-height: 400px;
}

.code {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}
</style>

<?php include '../includes/admin_footer.php'; ?>
