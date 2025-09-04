<?php
/**
 * Mr ECU - Email Test Log Viewer
 * Email Test Loglarını Görüntüleme
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş ve admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pageTitle = 'Email Test Logları';
include '_header.php';

// Log dosyasını oku
$logFile = __DIR__ . '/../logs/email_test.log';
$logExists = file_exists($logFile);
$logContent = '';
$logSize = 0;

if ($logExists) {
    $logContent = file_get_contents($logFile);
    $logSize = filesize($logFile);
}

// Log temizleme
if (isset($_POST['clear_logs'])) {
    if ($logExists) {
        file_put_contents($logFile, '');
        $success = 'Email test logları temizlendi.';
        $logContent = '';
        $logSize = 0;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-folder2-open me-2 text-info"></i>Email Test Logları
                    </h1>
                    <p class="text-muted mb-0">
                        Test modunda gönderilen email'lerin logları
                        <?php if (EMAIL_TEST_MODE): ?>
                            <span class="badge bg-warning text-dark">Test Modu Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-success">Gerçek Email Modu</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($logExists && $logSize > 0): ?>
                    <form method="POST" class="me-2">
                        <button type="submit" name="clear_logs" class="btn btn-outline-danger" 
                                onclick="return confirm('Tüm email loglarını silmek istediğinizden emin misiniz?')">
                            <i class="bi bi-trash me-1"></i>Logları Temizle
                        </button>
                    </form>
                    <?php endif; ?>
                    <a href="email-settings.php" class="btn btn-outline-primary">
                        <i class="bi bi-gear me-1"></i>Email Ayarları
                    </a>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-envelope me-2"></i>Email Test Logları
                            </h5>
                            <?php if ($logExists): ?>
                            <small class="text-muted">
                                Dosya Boyutu: <?php echo formatFileSize($logSize); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!EMAIL_TEST_MODE): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Bilgi:</strong> Email test modu şu anda devre dışı. 
                                    Gerçek email gönderimi aktif. Test modunu aktifleştirmek için 
                                    <code>config/config.php</code> dosyasında <code>EMAIL_TEST_MODE</code> 
                                    değerini <code>true</code> yapın.
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$logExists || $logSize == 0): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fa-3x mb-3"></i>
                                    <h5>Henüz email log kaydı yok</h5>
                                    <p>Test email gönderdiğinizde loglar burada görünecek.</p>
                                    <a href="email-settings.php" class="btn btn-primary">
                                        <i class="bi bi-send me-1"></i>Test Email Gönder
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="log-viewer">
                                    <pre class="bg-dark text-light p-3 rounded" style="height: 600px; overflow-y: auto; font-size: 12px;"><?php echo htmlspecialchars($logContent); ?></pre>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshLogs()">
                                        <i class="bi bi-sync-alt me-1"></i>Yenile
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadLogs()">
                                        <i class="bi bi-download me-1"></i>İndir
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Email Test Modu Ayarları -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-toggle-on me-2"></i>Email Test Modu Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Önemli:</strong> MAMP ortamında PHP'nin <code>mail()</code> 
                                fonksiyonu genellikle çalışmaz. Gerçek email göndermek için:
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Seçenek 1: PHPMailer Kurulumu</h6>
                                    <p class="small text-muted">Composer ile PHPMailer kurarak gerçek SMTP kullanın:</p>
                                    <pre class="bg-light p-2 small"><code>cd /Applications/MAMP/htdocs<?php echo BASE_URL; ?>
composer require phpmailer/phpmailer</code></pre>
                                </div>
                                <div class="col-md-6">
                                    <h6>Seçenek 2: Test Modu</h6>
                                    <p class="small text-muted">Şu anki ayar:</p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" <?php echo EMAIL_TEST_MODE ? 'checked' : ''; ?> disabled>
                                        <label class="form-check-label">
                                            Test Modu <?php echo EMAIL_TEST_MODE ? 'Aktif' : 'Pasif'; ?>
                                        </label>
                                    </div>
                                    <small class="text-muted">
                                        Değiştirmek için <code>config/config.php</code> düzenleyin
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function refreshLogs() {
    location.reload();
}

function downloadLogs() {
    window.open('data:text/plain;charset=utf-8,' + encodeURIComponent(document.querySelector('.log-viewer pre').textContent), '_blank');
}
</script>

<?php include '../includes/admin_footer.php'; ?>
