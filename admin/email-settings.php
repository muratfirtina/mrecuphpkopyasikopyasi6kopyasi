<?php
/**
 * Mr ECU - Email Settings Management
 * Email Ayarları Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/EmailManager.php';
require_once '../includes/NotificationManager.php';

// Giriş ve admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Email ayarlarını kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_email_settings'])) {
    try {
        $smtpPassword = sanitize($_POST['smtp_password']);
        $testEmail = sanitize($_POST['test_email'], 'email');
        
        // SMTP şifresini veritabanına kaydet
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, description) 
            VALUES ('smtp_password', ?, 'SMTP Email şifresi')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$smtpPassword]);
        
        // Test email adresini kaydet
        if (!empty($testEmail)) {
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value, description) 
                VALUES ('admin_test_email', ?, 'Admin test email adresi')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$testEmail]);
        }
        
        $success = 'Email ayarları başarıyla kaydedildi.';
        
    } catch (PDOException $e) {
        $error = 'Ayarlar kaydedilirken hata oluştu: ' . $e->getMessage();
    }
}

// Test email gönder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_email'])) {
    try {
        $testEmail = sanitize($_POST['test_email_address'], 'email');
        
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Geçerli bir email adresi girin.';
        } else {
            $emailManager = new EmailManager($pdo);
            $result = $emailManager->sendTestEmail($testEmail);
            
            if ($result) {
                $success = 'Test email başarıyla gönderildi: ' . $testEmail;
            } else {
                $error = 'Test email gönderilemedi. SMTP ayarlarını kontrol edin.';
            }
        }
        
    } catch (Exception $e) {
        $error = 'Test email gönderilirken hata oluştu: ' . $e->getMessage();
    }
}

// Mevcut ayarları getir
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_password', 'admin_test_email')");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $settings = [];
}

// Email istatistikleri
try {
    $emailManager = new EmailManager($pdo);
    $emailStats = $emailManager->getEmailStats();
} catch (Exception $e) {
    $emailStats = ['total' => 0, 'pending' => 0, 'sent' => 0, 'failed' => 0];
}

$pageTitle = 'Email Ayarları';
include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 admin-content">
                <div class="p-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-envelope-open-text me-2 text-primary"></i>Email Ayarları
                    </h1>
                    <p class="text-muted mb-0">Email bildirim sistemi yapılandırması</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-outline-success" onclick="processEmailQueue()">
                        <i class="fas fa-sync-alt me-1"></i>Email Kuyruğunu İşle
                    </button>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Email Ayarları -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cog me-2"></i>SMTP Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" value="<?php echo SMTP_HOST; ?>" readonly>
                                    <small class="text-muted">config.php'de tanımlı</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="text" class="form-control" value="<?php echo SMTP_PORT; ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Gönderen Email</label>
                                    <input type="email" class="form-control" value="<?php echo SMTP_FROM_EMAIL; ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Şifre</label>
                                    <input type="password" class="form-control" name="smtp_password" 
                                           value="<?php echo isset($settings['smtp_password']) ? '••••••••' : ''; ?>" 
                                           placeholder="Email şifresini girin">
                                    <small class="text-muted">Outlook için app password kullanın</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Test Email Adresi</label>
                                    <input type="email" class="form-control" name="test_email" 
                                           value="<?php echo $settings['admin_test_email'] ?? ''; ?>" 
                                           placeholder="test@example.com">
                                </div>
                                
                                <button type="submit" name="save_email_settings" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Ayarları Kaydet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Test Email -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-paper-plane me-2"></i>Email Test
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Test Email Adresi</label>
                                    <input type="email" class="form-control" name="test_email_address" 
                                           value="<?php echo $settings['admin_test_email'] ?? ''; ?>" 
                                           placeholder="Test göndermek için email adresi" required>
                                </div>
                                
                                <button type="submit" name="send_test_email" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-1"></i>Test Email Gönder
                                </button>
                            </form>
                            
                            <hr>
                            
                            <h6>Email İstatistikleri</h6>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <div class="h5 mb-0 text-primary"><?php echo $emailStats['total']; ?></div>
                                        <small>Toplam</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <div class="h5 mb-0 text-warning"><?php echo $emailStats['pending']; ?></div>
                                        <small>Bekleyen</small>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="p-2 bg-light rounded">
                                        <div class="h5 mb-0 text-success"><?php echo $emailStats['sent']; ?></div>
                                        <small>Gönderilen</small>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="p-2 bg-light rounded">
                                        <div class="h5 mb-0 text-danger"><?php echo $emailStats['failed']; ?></div>
                                        <small>Başarısız</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Email Şablonları -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Email Şablonları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Şablon</th>
                                            <th>Başlık</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT * FROM email_templates ORDER BY template_key");
                                            $templates = $stmt->fetchAll();
                                        } catch (PDOException $e) {
                                            $templates = [];
                                        }
                                        
                                        foreach ($templates as $template):
                                        ?>
                                        <tr>
                                            <td>
                                                <code><?php echo htmlspecialchars($template['template_key']); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($template['subject']); ?></td>
                                            <td>
                                                <?php if ($template['is_active']): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pasif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="previewTemplate('<?php echo htmlspecialchars($template['id']); ?>')">
                                                    <i class="fas fa-eye"></i> Önizle
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($templates)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Email şablonu bulunamadı</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>

<script>
function processEmailQueue() {
    if (!confirm('Email kuyruğunu şimdi işlemek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('../process_email_queue.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email kuyruğu başarıyla işlendi. İşlenen email sayısı: ' + data.processed);
            location.reload();
        } else {
            alert('Email kuyruğu işlenirken hata oluştu: ' + (data.error || 'Bilinmeyen hata'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Email kuyruğu işlenirken hata oluştu.');
    });
}

function previewTemplate(templateId) {
    // Email şablonu önizleme - gelecekte eklenebilir
    // templateId artık GUID formatında
    alert('Email şablonu önizleme özelliği yakında eklenecek. Template ID: ' + templateId);
}
</script>

<?php include '../includes/admin_footer.php'; ?>
