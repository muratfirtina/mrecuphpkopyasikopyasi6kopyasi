<?php
/**
 * Mr ECU - Admin Sistem Ayarları
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Ayar güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $pdo->beginTransaction();
        
        // Her ayarı güncelle
        foreach ($_POST as $key => $value) {
            if ($key !== 'update_settings') {
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $value]);
            }
        }
        
        $pdo->commit();
        $success = 'Ayarlar başarıyla güncellendi!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Mevcut ayarları getir
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch(PDOException $e) {
    $settings = [];
    $error = 'Ayarlar yüklenirken hata oluştu.';
}

// Varsayılan ayarlar
$defaultSettings = [
    'site_name' => 'Mr ECU',
    'site_description' => 'Profesyonel ECU Hizmetleri',
    'site_email' => 'info@mrecu.com',
    'site_phone' => '+90 (555) 123 45 67',
    'maintenance_mode' => '0',
    'user_registration' => '1',
    'email_verification' => '1',
    'max_file_size' => '10',
    'allowed_file_types' => 'bin,hex,a2l,kp,ori,mod',
    'default_credits' => '10',
    'credit_price' => '1.00',
    'auto_approve_uploads' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls'
];

// Eksik ayarları varsayılan değerlerle tamamla
foreach ($defaultSettings as $key => $defaultValue) {
    if (!isset($settings[$key])) {
        $settings[$key] = $defaultValue;
    }
}

$pageTitle = 'Sistem Ayarları';
$pageDescription = 'Sistem ayarlarını yönetin';
$pageIcon = 'bi bi-gear';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="row">
        <!-- Genel Ayarlar -->
        <div class="col-md-6 mb-4">
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-globe me-2"></i>Genel Ayarlar
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Adı</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Açıklaması</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_email" class="form-label">Site Email</label>
                        <input type="email" class="form-control" id="site_email" name="site_email" 
                               value="<?php echo htmlspecialchars($settings['site_email']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_phone" class="form-label">Site Telefon</label>
                        <input type="text" class="form-control" id="site_phone" name="site_phone" 
                               value="<?php echo htmlspecialchars($settings['site_phone']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                   value="1" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">
                                Bakım Modu
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kullanıcı Ayarları -->
        <div class="col-md-6 mb-4">
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2"></i>Kullanıcı Ayarları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="user_registration" name="user_registration" 
                                   value="1" <?php echo $settings['user_registration'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="user_registration">
                                Kullanıcı Kayıtlarına İzin Ver
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email_verification" name="email_verification" 
                                   value="1" <?php echo $settings['email_verification'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_verification">
                                Email Doğrulama Zorunlu
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="default_credits" class="form-label">Varsayılan Kredi (TL)</label>
                        <input type="number" class="form-control" id="default_credits" name="default_credits" 
                               value="<?php echo htmlspecialchars($settings['default_credits']); ?>" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="credit_price" class="form-label">Kredi Fiyatı (TL)</label>
                        <input type="number" class="form-control" id="credit_price" name="credit_price" 
                               value="<?php echo htmlspecialchars($settings['credit_price']); ?>" step="0.01" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Dosya Ayarları -->
        <div class="col-md-6 mb-4">
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-folder2-open me-2"></i>Dosya Ayarları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="max_file_size" class="form-label">Maksimum Dosya Boyutu (MB)</label>
                        <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                               value="<?php echo htmlspecialchars($settings['max_file_size']); ?>" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="allowed_file_types" class="form-label">İzin Verilen Dosya Türleri</label>
                        <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                               value="<?php echo htmlspecialchars($settings['allowed_file_types']); ?>"
                               placeholder="bin,hex,a2l,kp,ori,mod">
                        <div class="form-text">Virgülle ayırarak yazın</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_approve_uploads" name="auto_approve_uploads" 
                                   value="1" <?php echo $settings['auto_approve_uploads'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="auto_approve_uploads">
                                Dosyaları Otomatik Onayla
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMTP Ayarları -->
        <div class="col-md-6 mb-4">
            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-envelope me-2"></i>SMTP Ayarları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                               value="<?php echo htmlspecialchars($settings['smtp_host']); ?>"
                               placeholder="smtp.gmail.com">
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="smtp_port" class="form-label">Port</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                       value="<?php echo htmlspecialchars($settings['smtp_port']); ?>"
                                       placeholder="587">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="smtp_encryption" class="form-label">Şifreleme</label>
                                <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                    <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>Yok</option>
                                    <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                               value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                               value="<?php echo htmlspecialchars($settings['smtp_password']); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kaydet Butonu -->
    <div class="row">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-body text-center">
                    <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Ayarları Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$pageJS = "
// Form değişikliklerini kontrol et
let originalFormData = new FormData(document.querySelector('form'));

window.addEventListener('beforeunload', function(e) {
    const currentFormData = new FormData(document.querySelector('form'));
    let hasChanged = false;
    
    for (let [key, value] of originalFormData) {
        if (currentFormData.get(key) !== value) {
            hasChanged = true;
            break;
        }
    }
    
    if (hasChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Form gönderildiğinde uyarıyı kaldır
document.querySelector('form').addEventListener('submit', function() {
    window.removeEventListener('beforeunload', arguments.callee);
});
";

// Footer include
include '../includes/admin_footer.php';
?>
