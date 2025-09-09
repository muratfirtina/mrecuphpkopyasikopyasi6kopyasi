<?php
/**
 * Mr ECU - Admin Email Ayarları ve İstatistikler
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/EmailManager.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Email istatistiklerini getir
function getEmailStatistics($pdo) {
    try {
        $stats = [];
        
        // Günlük istatistikler (son 30 gün)
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM email_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ");
        $stats['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Genel toplam istatistikler
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_emails,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending
            FROM email_queue
        ");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Bu ayın istatistikleri
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as monthly_total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as monthly_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as monthly_failed
            FROM email_queue 
            WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())
        ");
        $stats['monthly'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Bugünün istatistikleri
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as daily_total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as daily_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as daily_failed
            FROM email_queue 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    } catch (PDOException $e) {
        error_log('getEmailStatistics error: ' . $e->getMessage());
        return null;
    }
}

// Email ayarlarını getir
function getEmailSettings($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key LIKE 'email_%'
        ");
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log('getEmailSettings error: ' . $e->getMessage());
        return [];
    }
}

// Ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_settings') {
        try {
            $emailEnabled = isset($_POST['email_enabled']) ? 'true' : 'false';
            $notificationsEnabled = isset($_POST['email_notifications_enabled']) ? 'true' : 'false';
            $adminNotifications = isset($_POST['email_admin_notifications']) ? 'true' : 'false';
            $userNotifications = isset($_POST['email_user_notifications']) ? 'true' : 'false';
            $dailyLimit = (int)($_POST['email_max_daily_limit'] ?? 1000);
            
            $settings = [
                'email_enabled' => $emailEnabled,
                'email_notifications_enabled' => $notificationsEnabled,
                'email_admin_notifications' => $adminNotifications,
                'email_user_notifications' => $userNotifications,
                'email_max_daily_limit' => $dailyLimit
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    UPDATE settings 
                    SET setting_value = ?, updated_at = NOW() 
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $key]);
            }
            
            $success = 'Email ayarları başarıyla güncellendi.';
        } catch (PDOException $e) {
            error_log('Update email settings error: ' . $e->getMessage());
            $error = 'Ayarları güncellerken hata oluştu.';
        }
    }
    
    elseif ($action === 'send_test_email') {
        $testEmail = sanitize($_POST['test_email']);
        
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Geçerli bir email adresi girin.';
        } else {
            try {
                $emailManager = new EmailManager($pdo);
                $result = $emailManager->sendTestEmail($testEmail);
                
                if ($result) {
                    $success = "Test emaili başarıyla gönderildi: $testEmail";
                } else {
                    $error = 'Test emaili gönderilemedi. Log dosyalarını kontrol edin.';
                }
            } catch (Exception $e) {
                $error = 'Email gönderme hatası: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'clear_email_queue') {
        try {
            $stmt = $pdo->prepare("DELETE FROM email_queue WHERE status IN ('sent', 'failed')");
            $result = $stmt->execute();
            
            if ($result) {
                $success = 'Email kuyruğu temizlendi.';
            } else {
                $error = 'Email kuyruğu temizlenemedi.';
            }
        } catch (PDOException $e) {
            error_log('Clear email queue error: ' . $e->getMessage());
            $error = 'Email kuyruğu temizlenirken hata oluştu.';
        }
    }
}

$emailSettings = getEmailSettings($pdo);
$emailStats = getEmailStatistics($pdo);

$pageTitle = 'Email Ayarları';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-envelope-gear me-2"></i>
                    Email Sistemi Yönetimi
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="../email-test.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-bug me-1"></i>Email Test
                    </a>
                </div>
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
            
            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Bugün</h6>
                                    <h3 class="mb-0"><?php echo $emailStats['today']['daily_sent'] ?? 0; ?></h3>
                                    <small>Gönderilen Email</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-envelope-check" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Bu Ay</h6>
                                    <h3 class="mb-0"><?php echo $emailStats['monthly']['monthly_sent'] ?? 0; ?></h3>
                                    <small>Başarılı Gönderim</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-graph-up" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Başarısız</h6>
                                    <h3 class="mb-0"><?php echo $emailStats['total']['total_failed'] ?? 0; ?></h3>
                                    <small>Toplam Hata</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Toplam</h6>
                                    <h3 class="mb-0"><?php echo $emailStats['total']['total_sent'] ?? 0; ?></h3>
                                    <small>Gönderilen Email</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-envelope-fill" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Email Ayarları -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-gear me-2"></i>
                                Email Sistemi Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_settings">
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_enabled" 
                                               name="email_enabled"
                                               <?php echo ($emailSettings['email_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_enabled">
                                            <strong>Email Sistemi Aktif</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Email sistemini tamamen aktif/pasif yapın</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications_enabled" 
                                               name="email_notifications_enabled"
                                               <?php echo ($emailSettings['email_notifications_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_notifications_enabled">
                                            <strong>Email Bildirimleri</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Otomatik email bildirimlerini aktif/pasif yapın</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_admin_notifications" 
                                               name="email_admin_notifications"
                                               <?php echo ($emailSettings['email_admin_notifications'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_admin_notifications">
                                            <strong>Admin Bildirimleri</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Admin'lere gönderilen bildirimleri kontrol edin</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_user_notifications" 
                                               name="email_user_notifications"
                                               <?php echo ($emailSettings['email_user_notifications'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_user_notifications">
                                            <strong>Kullanıcı Bildirimleri</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Kullanıcılara gönderilen bildirimleri kontrol edin</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email_max_daily_limit" class="form-label">Günlük Email Limiti</label>
                                    <input type="number" class="form-control" id="email_max_daily_limit" 
                                           name="email_max_daily_limit" min="0" max="10000"
                                           value="<?php echo $emailSettings['email_max_daily_limit'] ?? 1000; ?>">
                                    <small class="text-muted">Günlük maksimum gönderilebilecek email sayısı</small>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-2"></i>
                                        Ayarları Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Test Email -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-send me-2"></i>
                                Test Email Gönder
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="send_test_email">
                                
                                <div class="mb-3">
                                    <label for="test_email" class="form-label">Test Email Adresi</label>
                                    <input type="email" class="form-control" id="test_email" name="test_email" 
                                           placeholder="test@example.com" required>
                                </div>
                                
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-send me-1"></i>
                                    Test Email Gönder
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- SMTP Bilgileri ve İstatistikler -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-server me-2"></i>
                                SMTP Konfigürasyonu
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>SMTP Host:</strong></td>
                                    <td><code><?php echo getenv('SMTP_HOST') ?: 'smtp-mail.outlook.com'; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>SMTP Port:</strong></td>
                                    <td><code><?php echo getenv('SMTP_PORT') ?: '587'; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Kullanıcı:</strong></td>
                                    <td><code><?php echo getenv('SMTP_USERNAME') ?: 'mr.ecu@outlook.com'; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Şifreleme:</strong></td>
                                    <td><code><?php echo getenv('SMTP_ENCRYPTION') ?: 'tls'; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Gönderen Email:</strong></td>
                                    <td><code><?php echo getenv('SMTP_FROM_EMAIL') ?: 'mr.ecu@outlook.com'; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Debug Modu:</strong></td>
                                    <td>
                                        <span class="badge <?php echo getenv('DEBUG') === 'true' ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo getenv('DEBUG') === 'true' ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    SMTP ayarlarını değiştirmek için .env dosyasını düzenleyin.
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Kuyruğu -->
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-list-task me-2"></i>
                                Email Kuyruğu
                            </h6>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear_email_queue">
                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                        onclick="return confirm('Email kuyruğunu temizlemek istediğinizden emin misiniz?')">
                                    <i class="bi bi-trash me-1"></i>Temizle
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-warning"><?php echo $emailStats['total']['total_pending'] ?? 0; ?></h4>
                                        <small class="text-muted">Bekleyen</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-success"><?php echo $emailStats['total']['total_sent'] ?? 0; ?></h4>
                                        <small class="text-muted">Gönderilen</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-danger"><?php echo $emailStats['total']['total_failed'] ?? 0; ?></h4>
                                    <small class="text-muted">Başarısız</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Son Email Aktiviteleri -->
            <?php if (!empty($emailStats['daily'])): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-activity me-2"></i>
                        Son 7 Günün Email Aktivitesi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Toplam</th>
                                    <th>Gönderilen</th>
                                    <th>Başarısız</th>
                                    <th>Başarı Oranı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($emailStats['daily'], 0, 7) as $day): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['total']; ?></td>
                                    <td><span class="badge bg-success"><?php echo $day['sent']; ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo $day['failed']; ?></span></td>
                                    <td>
                                        <?php 
                                        $successRate = $day['total'] > 0 ? round(($day['sent'] / $day['total']) * 100, 1) : 0;
                                        echo $successRate . '%';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Ayar değişikliklerini takip et
document.addEventListener('DOMContentLoaded', function() {
    const switches = document.querySelectorAll('.form-check-input[type="checkbox"]');
    
    switches.forEach(switchElement => {
        switchElement.addEventListener('change', function() {
            // Değişiklik olduğunda save butonunu vurgula
            const saveBtn = document.querySelector('button[type="submit"]');
            if (saveBtn && saveBtn.textContent.includes('Ayarları Kaydet')) {
                saveBtn.classList.remove('btn-primary');
                saveBtn.classList.add('btn-success');
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Değişiklikleri Kaydet';
            }
        });
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>
