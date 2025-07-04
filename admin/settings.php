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

$message = '';
$messageType = '';

// Ayar güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
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
            $message = 'Ayarlar başarıyla güncellendi!';
            $messageType = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Hata: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Mevcut ayarları getir
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Varsayılan değerler
$defaults = [
    'site_name' => SITE_NAME,
    'site_email' => SITE_EMAIL,
    'site_maintenance' => '0',
    'max_file_size' => MAX_FILE_SIZE,
    'default_credits' => DEFAULT_CREDITS,
    'file_download_cost' => FILE_DOWNLOAD_COST,
    'admin_email' => 'admin@mrecu.com',
    'smtp_host' => SMTP_HOST,
    'smtp_port' => SMTP_PORT,
    'smtp_username' => SMTP_USERNAME,
    'smtp_password' => '',
    'smtp_encryption' => SMTP_ENCRYPTION,
    'allowed_extensions' => implode(',', ALLOWED_EXTENSIONS),
    'registration_enabled' => '1',
    'email_verification_required' => '1',
    'auto_process_files' => '0',
    'backup_enabled' => '1',
    'log_level' => 'info'
];

// Ayarları birleştir
foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}

// Sistem bilgileri
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'disk_free_space' => disk_free_space('.'),
    'disk_total_space' => disk_total_space('.')
];

// Database istatistikleri
$db_stats = [];
$tables = ['users', 'file_uploads', 'credit_transactions', 'brands', 'models', 'categories', 'products'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $db_stats[$table] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $db_stats[$table] = 'N/A';
    }
}

$pageTitle = 'Sistem Ayarları';
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
                        <i class="fas fa-cog me-2"></i><?php echo $pageTitle; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('settingsForm').submit();">
                            <i class="fas fa-save me-1"></i>Kaydet
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Ayarlar Formu -->
                    <div class="col-lg-8">
                        <form method="POST" id="settingsForm">
                            <!-- Genel Ayarlar -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-globe me-2"></i>Genel Ayarlar
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Site Adı</label>
                                                <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Site Email</label>
                                                <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($settings['site_email']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Admin Email</label>
                                                <input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($settings['admin_email']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="site_maintenance" value="1" 
                                                           <?php echo $settings['site_maintenance'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Bakım Modu</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dosya Ayarları -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-file me-2"></i>Dosya Ayarları
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Maksimum Dosya Boyutu (bytes)</label>
                                                <input type="number" name="max_file_size" class="form-control" value="<?php echo $settings['max_file_size']; ?>">
                                                <small class="form-text text-muted">Şu an: <?php echo number_format($settings['max_file_size'] / 1024 / 1024, 2); ?> MB</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">İzin Verilen Dosya Uzantıları</label>
                                                <input type="text" name="allowed_extensions" class="form-control" value="<?php echo htmlspecialchars($settings['allowed_extensions']); ?>">
                                                <small class="form-text text-muted">Virgül ile ayırın: bin,hex,ecu</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="auto_process_files" value="1" 
                                                           <?php echo $settings['auto_process_files'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Otomatik Dosya İşleme</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kredi Ayarları -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-coins me-2"></i>Kredi Sistemi
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Varsayılan Kredi (Yeni Kullanıcı)</label>
                                                <input type="number" step="0.01" name="default_credits" class="form-control" value="<?php echo $settings['default_credits']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Dosya İndirme Maliyeti</label>
                                                <input type="number" step="0.01" name="file_download_cost" class="form-control" value="<?php echo $settings['file_download_cost']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kullanıcı Ayarları -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-users me-2"></i>Kullanıcı Ayarları
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="registration_enabled" value="1" 
                                                           <?php echo $settings['registration_enabled'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Kayıt Açık</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="email_verification_required" value="1" 
                                                           <?php echo $settings['email_verification_required'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Email Doğrulaması Gerekli</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SMTP Ayarları -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-envelope me-2"></i>SMTP Ayarları
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Host</label>
                                                <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" name="smtp_port" class="form-control" value="<?php echo $settings['smtp_port']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Kullanıcı Adı</label>
                                                <input type="text" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Şifre</label>
                                                <input type="password" name="smtp_password" class="form-control" placeholder="Değiştirmek için yeni şifre girin">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Şifreleme</label>
                                                <select name="smtp_encryption" class="form-select">
                                                    <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>Yok</option>
                                                    <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                    <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sistem Ayarları -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-server me-2"></i>Sistem Ayarları
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Log Seviyesi</label>
                                                <select name="log_level" class="form-select">
                                                    <option value="debug" <?php echo $settings['log_level'] === 'debug' ? 'selected' : ''; ?>>Debug</option>
                                                    <option value="info" <?php echo $settings['log_level'] === 'info' ? 'selected' : ''; ?>>Info</option>
                                                    <option value="warning" <?php echo $settings['log_level'] === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                                    <option value="error" <?php echo $settings['log_level'] === 'error' ? 'selected' : ''; ?>>Error</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="backup_enabled" value="1" 
                                                           <?php echo $settings['backup_enabled'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Otomatik Yedekleme</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Tüm Ayarları Kaydet
                            </button>
                        </form>
                    </div>

                    <!-- Sistem Bilgileri -->
                    <div class="col-lg-4">
                        <!-- Sistem Bilgileri -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Sistem Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>PHP Versiyonu:</strong></td>
                                        <td><?php echo $system_info['php_version']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>MySQL Versiyonu:</strong></td>
                                        <td><?php echo $system_info['mysql_version']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Web Server:</strong></td>
                                        <td><?php echo $system_info['server_software']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Upload Max:</strong></td>
                                        <td><?php echo $system_info['upload_max_filesize']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Post Max:</strong></td>
                                        <td><?php echo $system_info['post_max_size']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Memory Limit:</strong></td>
                                        <td><?php echo $system_info['memory_limit']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Execution Time:</strong></td>
                                        <td><?php echo $system_info['max_execution_time']; ?>s</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Disk Kullanımı -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-hdd me-2"></i>Disk Kullanımı
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $free_gb = round($system_info['disk_free_space'] / 1024 / 1024 / 1024, 2);
                                $total_gb = round($system_info['disk_total_space'] / 1024 / 1024 / 1024, 2);
                                $used_gb = $total_gb - $free_gb;
                                $usage_percent = round(($used_gb / $total_gb) * 100, 1);
                                ?>
                                <div class="progress mb-2">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $usage_percent; ?>%">
                                        <?php echo $usage_percent; ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Kullanılan: <?php echo $used_gb; ?> GB / <?php echo $total_gb; ?> GB<br>
                                    Boş: <?php echo $free_gb; ?> GB
                                </small>
                            </div>
                        </div>

                        <!-- Veritabanı İstatistikleri -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-database me-2"></i>Veritabanı İstatistikleri
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Kullanıcılar:</strong></td>
                                        <td><?php echo number_format($db_stats['users']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dosya Yüklemeleri:</strong></td>
                                        <td><?php echo number_format($db_stats['file_uploads']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kredi İşlemleri:</strong></td>
                                        <td><?php echo number_format($db_stats['credit_transactions']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Markalar:</strong></td>
                                        <td><?php echo number_format($db_stats['brands']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Modeller:</strong></td>
                                        <td><?php echo number_format($db_stats['models']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kategoriler:</strong></td>
                                        <td><?php echo number_format($db_stats['categories']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ürünler:</strong></td>
                                        <td><?php echo number_format($db_stats['products']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form değişiklik kontrolü
        let originalFormData = new FormData(document.getElementById('settingsForm'));
        
        window.addEventListener('beforeunload', function(e) {
            let currentFormData = new FormData(document.getElementById('settingsForm'));
            let hasChanges = false;
            
            for (let [key, value] of currentFormData.entries()) {
                if (originalFormData.get(key) !== value) {
                    hasChanges = true;
                    break;
                }
            }
            
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Form gönderildiğinde uyarıyı kaldır
        document.getElementById('settingsForm').addEventListener('submit', function() {
            window.removeEventListener('beforeunload', arguments.callee);
        });
    </script>
</body>
</html>
