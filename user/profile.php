<?php
/**
 * Mr ECU - Kullanıcı Profil Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü otomatik yapılır
$user = new User($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);

$error = '';
$success = '';
$userId = $_SESSION['user_id'];

// Kullanıcı bilgilerini getir
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        redirect('../logout.php');
    }
} catch(PDOException $e) {
    redirect('../logout.php');
}

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone']),
        'notification_email' => isset($_POST['notification_email']) ? 1 : 0,
        'notification_sms' => isset($_POST['notification_sms']) ? 1 : 0
    ];
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Ad ve soyad alanları zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, notification_email = ?, notification_sms = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $data['first_name'], 
                $data['last_name'], 
                $data['phone'], 
                $data['notification_email'],
                $data['notification_sms'],
                $userId
            ]);
            
            if ($result) {
                $success = 'Profil bilgileri başarıyla güncellendi.';
                
                // Güncellenmiş verileri al
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Log kaydı
                $user->logAction($userId, 'profile_update', 'Profil bilgileri güncellendi');
            }
        } catch(PDOException $e) {
            $error = 'Güncelleme sırasında hata oluştu: ' . $e->getMessage();
        }
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Tüm şifre alanları zorunludur.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalıdır.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Yeni şifreler eşleşmiyor.';
    } elseif (!password_verify($currentPassword, $userData['password'])) {
        $error = 'Mevcut şifre hatalı.';
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$hashedPassword, $userId]);
            
            if ($result) {
                $success = 'Şifreniz başarıyla değiştirildi.';
                
                // Log kaydı
                $user->logAction($userId, 'password_change', 'Şifre değiştirildi');
            }
        } catch(PDOException $e) {
            $error = 'Şifre değiştirme sırasında hata oluştu.';
        }
    }
}

// Kullanıcı istatistikleri
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_uploads,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_uploads,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_uploads,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_uploads,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_uploads
        FROM uploads 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Son giriş zamanı
    $stmt = $pdo->prepare("SELECT last_login FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $lastLogin = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $stats = ['total_uploads' => 0, 'pending_uploads' => 0, 'processing_uploads' => 0, 'completed_uploads' => 0, 'rejected_uploads' => 0];
    $lastLogin = null;
}

// Sayfa bilgileri
$pageTitle = 'Profil Ayarları';
$pageDescription = 'Hesap bilgilerinizi görüntüleyin ve güncelleyin.';

// Header ve Sidebar include
include '../includes/user_header.php';
include '../includes/user_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
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

<div class="row">
    <!-- Sol Kolon - Profil Bilgileri -->
    <div class="col-lg-8">
        <!-- Profil Bilgileri Kartı -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Profil Bilgileri
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="profileForm" data-loading="true">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Ad <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Soyad <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-at me-1"></i>
                                    Kullanıcı Adı
                                </label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                                <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>
                                    E-posta Adresi
                                </label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                                <div class="form-text">E-posta adresi değiştirilemez.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone me-1"></i>
                            Telefon Numarası
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>"
                               placeholder="+90 (555) 123 45 67">
                    </div>
                    
                    <!-- Bildirim Ayarları -->
                    <hr>
                    <h6 class="mb-3">
                        <i class="fas fa-bell me-2"></i>Bildirim Ayarları
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notification_email" 
                                       name="notification_email" 
                                       <?php echo ($userData['notification_email'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notification_email">
                                    <i class="fas fa-envelope me-1"></i>
                                    E-posta Bildirimleri
                                </label>
                                <div class="form-text">Dosya durumu güncellemeleri için e-posta alın</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notification_sms" 
                                       name="notification_sms"
                                       <?php echo ($userData['notification_sms'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notification_sms">
                                    <i class="fas fa-sms me-1"></i>
                                    SMS Bildirimleri
                                </label>
                                <div class="form-text">Önemli güncellemeler için SMS alın</div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="d-flex justify-content-between">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>Sıfırla
                        </button>
                        <button type="submit" class="btn btn-primary" data-original-text="Bilgileri Güncelle">
                            <i class="fas fa-save me-2"></i>Bilgileri Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Şifre Değiştirme Kartı -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-lock me-2"></i>
                    Şifre Değiştir
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="passwordForm" data-loading="true">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>
                            Mevcut Şifre <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="togglePasswordVisibility('current_password')">
                                <i class="fas fa-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="fas fa-key me-1"></i>
                            Yeni Şifre <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" minlength="6" required>
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="togglePasswordVisibility('new_password')">
                                <i class="fas fa-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                        <div class="form-text">En az 6 karakter olmalıdır.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-key me-1"></i>
                            Yeni Şifre (Tekrar) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Şifre Güvenlik Göstergesi -->
                    <div class="mb-3">
                        <label class="form-label">Şifre Güvenliği</label>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" id="passwordStrength" role="progressbar" 
                                 style="width: 0%"></div>
                        </div>
                        <small class="text-muted" id="passwordStrengthText">Şifre girin</small>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning" data-original-text="Şifremi Değiştir">
                            <i class="fas fa-key me-2"></i>Şifremi Değiştir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sağ Kolon - İstatistikler ve Bilgiler -->
    <div class="col-lg-4">
        <!-- Hesap İstatistikleri -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Hesap İstatistikleri
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <h4 class="text-primary mb-1"><?php echo $stats['total_uploads']; ?></h4>
                        <small class="text-muted">Toplam Dosya</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1"><?php echo $stats['completed_uploads']; ?></h4>
                        <small class="text-muted">Tamamlanan</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-4">
                        <h5 class="text-warning mb-1"><?php echo $stats['pending_uploads']; ?></h5>
                        <small class="text-muted">Bekleyen</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-info mb-1"><?php echo $stats['processing_uploads']; ?></h5>
                        <small class="text-muted">İşleniyor</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-danger mb-1"><?php echo $stats['rejected_uploads']; ?></h5>
                        <small class="text-muted">Reddedilen</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hesap Bilgileri -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Hesap Bilgileri
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Üyelik Tarihi:</strong><br>
                    <small class="text-muted">
                        <?php echo date('d.m.Y H:i', strtotime($userData['created_at'])); ?>
                    </small>
                </div>
                
                <div class="mb-3">
                    <strong>Son Giriş:</strong><br>
                    <small class="text-muted">
                        <?php echo $lastLogin ? date('d.m.Y H:i', strtotime($lastLogin)) : 'Henüz giriş yapılmamış'; ?>
                    </small>
                </div>
                
                <div class="mb-3">
                    <strong>Hesap Durumu:</strong><br>
                    <span class="badge bg-<?php echo $userData['is_active'] ? 'success' : 'danger'; ?>">
                        <?php echo $userData['is_active'] ? 'Aktif' : 'Pasif'; ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <strong>Doğrulama Durumu:</strong><br>
                    <span class="badge bg-<?php echo $userData['email_verified'] ? 'success' : 'warning'; ?>">
                        <?php echo $userData['email_verified'] ? 'Doğrulanmış' : 'Doğrulanmamış'; ?>
                    </span>
                </div>
                
                <div class="mb-0">
                    <strong>Mevcut Kredi:</strong><br>
                    <h5 class="text-success mb-0">
                        <?php echo number_format($_SESSION['credits'], 2); ?> TL
                    </h5>
                </div>
            </div>
        </div>
        
        <!-- Hızlı İşlemler -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Hızlı İşlemler
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="upload.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload me-2"></i>Dosya Yükle
                    </a>
                    
                    <a href="files.php" class="btn btn-info btn-sm">
                        <i class="fas fa-folder me-2"></i>Dosyalarım
                    </a>
                    
                    <a href="credits.php" class="btn btn-success btn-sm">
                        <i class="fas fa-credit-card me-2"></i>Kredi Yükle
                    </a>
                    
                    <a href="transactions.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-receipt me-2"></i>İşlem Geçmişi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Password visibility toggle
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    
    let strength = 0;
    let feedback = '';
    
    if (password.length >= 6) strength += 20;
    if (password.match(/[a-z]/)) strength += 20;
    if (password.match(/[A-Z]/)) strength += 20;
    if (password.match(/[0-9]/)) strength += 20;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
    
    strengthBar.style.width = strength + '%';
    
    if (strength < 40) {
        strengthBar.className = 'progress-bar bg-danger';
        feedback = 'Zayıf';
    } else if (strength < 60) {
        strengthBar.className = 'progress-bar bg-warning';
        feedback = 'Orta';
    } else if (strength < 80) {
        strengthBar.className = 'progress-bar bg-info';
        feedback = 'İyi';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        feedback = 'Güçlü';
    }
    
    strengthText.textContent = feedback;
});

// Password confirmation check
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
        if (confirmPassword && newPassword === confirmPassword) {
            this.classList.add('is-valid');
        }
    }
});

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        showToast('Yeni şifreler eşleşmiyor!', 'error');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        showToast('Şifre en az 6 karakter olmalıdır!', 'error');
        return false;
    }
});

// Form reset handler
document.querySelector('button[type=\"reset\"]').addEventListener('click', function() {
    setTimeout(() => {
        // Reset notification switches to original values
        document.getElementById('notification_email').checked = " . (($userData['notification_email'] ?? 1) ? 'true' : 'false') . ";
        document.getElementById('notification_sms').checked = " . (($userData['notification_sms'] ?? 0) ? 'true' : 'false') . ";
    }, 100);
});
";

// Footer include
include '../includes/user_footer.php';
?>
