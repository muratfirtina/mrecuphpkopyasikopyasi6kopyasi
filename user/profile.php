<?php
/**
 * Mr ECU - Modern Kullanıcı Profil Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/profile.php');
}

$user = new User($pdo);
$userId = $_SESSION['user_id'];

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($userId);

$error = '';
$success = '';

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
            }
        } catch(PDOException $e) {
            $error = 'Güncelleme sırasında hata oluştu.';
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
        FROM file_uploads 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kredi istatistikleri
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_loaded,
            SUM(CASE WHEN type IN ('withdraw', 'file_charge') THEN amount ELSE 0 END) as total_spent,
            COUNT(*) as total_transactions
        FROM credit_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $creditStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $stats = ['total_uploads' => 0, 'pending_uploads' => 0, 'processing_uploads' => 0, 'completed_uploads' => 0, 'rejected_uploads' => 0];
    $creditStats = ['total_loaded' => 0, 'total_spent' => 0, 'total_transactions' => 0];
}

$pageTitle = 'Profil Ayarları';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-user-cog me-2 text-primary"></i>Profil Ayarları
                    </h1>
                    <p class="text-muted mb-0">Hesap bilgilerinizi görüntüleyin ve güncelleyin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="files.php" class="btn btn-outline-primary">
                            <i class="fas fa-folder me-1"></i>Dosyalarım
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                        <div>
                            <strong>Hata!</strong> <?php echo $error; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fa-lg"></i>
                        <div>
                            <strong>Başarılı!</strong> <?php echo $success; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Profil Özeti Banner -->
            <div class="profile-banner mb-4">
                <div class="profile-content">
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="status-indicator <?php echo ($userData['is_active'] ?? 1) ? 'active' : 'inactive'; ?>"></div>
                    </div>
                    <div class="profile-info">
                        <h3 class="profile-name">
                            <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                        </h3>
                        <div class="profile-details">
                            <span class="detail-item">
                                <i class="fas fa-at me-1"></i>
                                <?php echo htmlspecialchars($userData['username']); ?>
                            </span>
                            <span class="detail-item">
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($userData['email']); ?>
                            </span>
                            <span class="detail-item">
                                <i class="fas fa-calendar me-1"></i>
                                Üye: <?php echo date('M Y', strtotime($userData['created_at'])); ?>
                            </span>
                        </div>
                        <div class="profile-badges">
                            <span class="badge bg-<?php echo ($userData['is_active'] ?? 1) ? 'success' : 'danger'; ?>">
                                <?php echo ($userData['is_active'] ?? 1) ? 'Aktif Hesap' : 'Pasif Hesap'; ?>
                            </span>
                            <span class="badge bg-<?php echo ($userData['email_verified'] ?? 0) ? 'primary' : 'warning'; ?>">
                                <?php echo ($userData['email_verified'] ?? 0) ? 'Doğrulanmış' : 'Doğrulanmamış'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="profile-stats">
                        <div class="stat-card-mini">
                            <div class="stat-value"><?php echo $stats['total_uploads']; ?></div>
                            <div class="stat-label" style="color: #e9ecef;">Dosya</div>
                        </div>
                        
                        <div class="stat-card-mini">
                            <div class="stat-value"><?php echo $creditStats['total_transactions']; ?></div>
                            <div class="stat-label" style="color: #e9ecef;">İşlem</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Sol Kolon - Form Alanları -->
                <div class="col-lg-8">
                    <!-- Profil Bilgileri -->
                    <div class="settings-card mb-4">
                        <div class="settings-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>Kişisel Bilgiler
                            </h5>
                            <p class="text-muted mb-0">Profil bilgilerinizi güncelleyin</p>
                        </div>
                        
                        <div class="settings-body">
                            <form method="POST" id="profileForm" class="modern-form">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="first_name" class="form-label">
                                            <i class="fas fa-user me-1"></i>Ad <span class="required">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-modern" 
                                               id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($userData['first_name']); ?>" 
                                               placeholder="Adınızı girin" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="last_name" class="form-label">
                                            <i class="fas fa-user me-1"></i>Soyad <span class="required">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-modern" 
                                               id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($userData['last_name']); ?>" 
                                               placeholder="Soyadınızı girin" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-at me-1"></i>Kullanıcı Adı
                                        </label>
                                        <input type="text" class="form-control form-control-modern" 
                                               id="username" value="<?php echo htmlspecialchars($userData['username']); ?>" 
                                               disabled>
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>Kullanıcı adı değiştirilemez
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>E-posta Adresi
                                        </label>
                                        <input type="email" class="form-control form-control-modern" 
                                               id="email" value="<?php echo htmlspecialchars($userData['email']); ?>" 
                                               disabled>
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>E-posta adresi değiştirilemez
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Telefon Numarası
                                    </label>
                                    <input type="tel" class="form-control form-control-modern" 
                                           id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>"
                                           placeholder="+90 (555) 123 45 67">
                                    <div class="form-help">
                                        <i class="fas fa-info-circle me-1"></i>SMS bildirimleri için kullanılır
                                    </div>
                                </div>
                                
                                <!-- Bildirim Ayarları -->
                                <div class="form-section">
                                    <h6 class="section-title">
                                        <i class="fas fa-bell me-2"></i>Bildirim Tercihleri
                                    </h6>
                                    
                                    <div class="notification-settings">
                                        <div class="notification-item">
                                            <div class="notification-info">
                                                <div class="notification-title">
                                                    <i class="fas fa-envelope text-primary me-2"></i>
                                                    E-posta Bildirimleri
                                                </div>
                                                <div class="notification-desc">
                                                    Dosya durumu güncellemeleri ve önemli bildirimler
                                                </div>
                                            </div>
                                            <div class="notification-toggle">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="notification_email" name="notification_email" 
                                                           <?php echo ($userData['notification_email'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="notification_email"></label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-item">
                                            <div class="notification-info">
                                                <div class="notification-title">
                                                    <i class="fas fa-sms text-success me-2"></i>
                                                    SMS Bildirimleri
                                                </div>
                                                <div class="notification-desc">
                                                    Acil durumlar ve kritik güncellemeler için SMS
                                                </div>
                                            </div>
                                            <div class="notification-toggle">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="notification_sms" name="notification_sms"
                                                           <?php echo ($userData['notification_sms'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="notification_sms"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="reset" class="btn btn-outline-secondary btn-modern">
                                        <i class="fas fa-undo me-2"></i>Sıfırla
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-modern">
                                        <i class="fas fa-save me-2"></i>Bilgileri Güncelle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Şifre Değiştirme -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h5 class="mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Güvenlik Ayarları
                            </h5>
                            <p class="text-muted mb-0">Hesabınızın güvenliğini artırın</p>
                        </div>
                        
                        <div class="settings-body">
                            <form method="POST" id="passwordForm" class="modern-form">
                                <input type="hidden" name="change_password" value="1">
                                
                                <div class="form-group">
                                    <label for="current_password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Mevcut Şifre <span class="required">*</span>
                                    </label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control form-control-modern" 
                                               id="current_password" name="current_password" 
                                               placeholder="Mevcut şifrenizi girin" required>
                                        <button type="button" class="password-toggle" 
                                                onclick="togglePasswordVisibility('current_password')">
                                            <i class="fas fa-eye" id="current_password_icon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password" class="form-label">
                                        <i class="fas fa-key me-1"></i>Yeni Şifre <span class="required">*</span>
                                    </label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control form-control-modern" 
                                               id="new_password" name="new_password" 
                                               placeholder="Yeni şifrenizi girin" 
                                               minlength="6" required>
                                        <button type="button" class="password-toggle" 
                                                onclick="togglePasswordVisibility('new_password')">
                                            <i class="fas fa-eye" id="new_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="form-help">
                                        <i class="fas fa-info-circle me-1"></i>En az 6 karakter, harf ve rakam içermeli
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-check-double me-1"></i>Yeni Şifre (Tekrar) <span class="required">*</span>
                                    </label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control form-control-modern" 
                                               id="confirm_password" name="confirm_password" 
                                               placeholder="Yeni şifrenizi tekrar girin" required>
                                        <button type="button" class="password-toggle" 
                                                onclick="togglePasswordVisibility('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Şifre Güvenlik Göstergesi -->
                                <div class="password-strength">
                                    <label class="form-label">Şifre Güvenlik Seviyesi</label>
                                    <div class="strength-meter">
                                        <div class="strength-bar" id="passwordStrength"></div>
                                    </div>
                                    <div class="strength-text" id="passwordStrengthText">Şifre girin</div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-warning btn-modern">
                                        <i class="fas fa-shield-alt me-2"></i>Şifremi Değiştir
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon - İstatistikler -->
                <div class="col-lg-4">
                    <!-- Hesap İstatistikleri -->
                    <div class="info-card mb-4">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>Hesap İstatistikleri
                            </h5>
                        </div>
                        <div class="info-body">
                            <div class="stats-overview">
                                <div class="stat-row">
                                    <div class="stat-icon bg-primary">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-value"><?php echo $stats['total_uploads']; ?></div>
                                        <div class="stat-label">Toplam Dosya</div>
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-icon bg-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-value"><?php echo $stats['completed_uploads']; ?></div>
                                        <div class="stat-label">Tamamlanan</div>
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-icon bg-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-value"><?php echo $stats['pending_uploads']; ?></div>
                                        <div class="stat-label">Bekleyen</div>
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-icon bg-info">
                                        <i class="fas fa-cogs"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-value"><?php echo $stats['processing_uploads']; ?></div>
                                        <div class="stat-label">İşleniyor</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hesap Bilgileri -->
                    <div class="info-card mb-4">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Hesap Detayları
                            </h5>
                        </div>
                        <div class="info-body">
                            <div class="account-details">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-calendar-plus text-primary me-2"></i>
                                        Üyelik Tarihi
                                    </div>
                                    <div class="detail-value">
                                        <?php echo date('d.m.Y', strtotime($userData['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-sign-in-alt text-success me-2"></i>
                                        Son Giriş
                                    </div>
                                    <div class="detail-value">
                                    <?php echo $userData['last_login'] ? date('d.m.Y H:i', strtotime($userData['last_login'])) : 'İlk giriş'; ?>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-coins text-warning me-2"></i>
                                        Mevcut Kredi
                                    </div>
                                    <div class="detail-value">
                                        <span class="text-success fw-bold"><?php echo number_format($_SESSION['credits'], 2); ?> TL</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-exchange-alt text-info me-2"></i>
                                        Toplam İşlem
                                    </div>
                                    <div class="detail-value">
                                        <?php echo $creditStats['total_transactions']; ?> işlem
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hızlı İşlemler -->
                    <div class="info-card">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Hızlı İşlemler
                            </h5>
                        </div>
                        <div class="info-body">
                            <div class="quick-actions">
                                <a href="upload.php" class="quick-action">
                                    <div class="action-icon bg-primary">
                                        <i class="fas fa-upload"></i>
                                    </div>
                                    <div class="action-text">
                                        <div class="action-title">Dosya Yükle</div>
                                        <div class="action-desc">Yeni ECU dosyası</div>
                                    </div>
                                </a>
                                
                                <a href="files.php" class="quick-action">
                                    <div class="action-icon bg-info">
                                        <i class="fas fa-folder"></i>
                                    </div>
                                    <div class="action-text">
                                        <div class="action-title">Dosyalarım</div>
                                        <div class="action-desc">Yüklediğim dosyalar</div>
                                    </div>
                                </a>
                                
                                <a href="credits.php" class="quick-action">
                                    <div class="action-icon bg-success">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="action-text">
                                        <div class="action-title">Kredi Yükle</div>
                                        <div class="action-desc">Hesabıma kredi ekle</div>
                                    </div>
                                </a>
                                
                                <a href="transactions.php" class="quick-action">
                                    <div class="action-icon bg-secondary">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <div class="action-text">
                                        <div class="action-title">İşlem Geçmişi</div>
                                        <div class="action-desc">Tüm aktiviteler</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Modern Profile Page Styles */
.profile-banner {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.profile-content {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.profile-avatar {
    position: relative;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    border: 3px solid rgba(255,255,255,0.3);
}

.status-indicator {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
}

.status-indicator.active {
    background: #28a745;
}

.status-indicator.inactive {
    background: #dc3545;
}

.profile-info {
    flex: 1;
}

.profile-name {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.profile-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.detail-item {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.profile-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.profile-stats {
    display: flex;
    gap: 1rem;
}

.stat-card-mini {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.stat-card-mini .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.stat-card-mini .stat-label {
    font-size: 0.8rem;
    opacity: 0.8;
    margin-top: 0.25rem;
}

/* Settings Cards */
.settings-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.settings-header {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e9ecef;
}

.settings-header h5 {
    margin: 0;
    color: #495057;
    font-weight: 600;
}

.settings-body {
    padding: 2rem;
}

/* Modern Form */
.modern-form {
    max-width: 100%;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.required {
    color: #dc3545;
    margin-left: 0.25rem;
}

.form-control-modern {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-control-modern:disabled {
    background: #f8f9fa;
    border-color: #e9ecef;
    color: #6c757d;
}

.form-help {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Form Section */
.form-section {
    margin: 2rem 0;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Notification Settings */
.notification-settings {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.notification-info {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}

.notification-desc {
    font-size: 0.85rem;
    color: #6c757d;
}

.notification-toggle {
    margin-left: 1rem;
}

.form-check-input {
    width: 3rem;
    height: 1.5rem;
    border-radius: 1rem;
}

/* Password Input */
.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0.25rem;
    z-index: 10;
}

.password-toggle:hover {
    color: #495057;
}

/* Password Strength */
.password-strength {
    margin: 1.5rem 0;
}

.strength-meter {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin: 0.5rem 0;
}

.strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 4px;
}

.strength-text {
    font-size: 0.85rem;
    font-weight: 500;
    text-align: center;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.btn-modern {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-modern:hover {
    transform: translateY(-1px);
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.info-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.info-body {
    padding: 1.5rem;
}

/* Stats Overview */
.stats-overview {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stat-row {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.stat-details {
    flex: 1;
}

.stat-details .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #495057;
    line-height: 1;
}

.stat-details .stat-label {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Account Details */
.account-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.account-details .detail-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.account-details .detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-size: 0.9rem;
    color: #6c757d;
    flex: 1;
}

.detail-value {
    font-weight: 600;
    color: #495057;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-action {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.quick-action:hover {
    background: #e9ecef;
    transform: translateX(4px);
    color: #495057;
    text-decoration: none;
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 1rem;
}

.action-text {
    flex: 1;
}

.action-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.125rem;
}

.action-desc {
    font-size: 0.8rem;
    color: #6c757d;
}

/* Alert Modern */
.alert-modern {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Responsive */
@media (max-width: 767.98px) {
    .profile-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .profile-details {
        justify-content: center;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .profile-stats {
        justify-content: center;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .settings-header,
    .settings-body {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .notification-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .notification-toggle {
        margin-left: 0;
        align-self: flex-end;
    }
}
</style>

<script>
// Password Visibility Toggle
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

// Password Strength Checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    
    let strength = 0;
    let feedback = '';
    let className = '';
    
    // Length check
    if (password.length >= 6) strength += 20;
    if (password.length >= 8) strength += 10;
    
    // Character variety checks
    if (password.match(/[a-z]/)) strength += 15;
    if (password.match(/[A-Z]/)) strength += 15;
    if (password.match(/[0-9]/)) strength += 20;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
    
    strengthBar.style.width = strength + '%';
    
    if (strength < 30) {
        className = 'bg-danger';
        feedback = 'Çok Zayıf';
    } else if (strength < 50) {
        className = 'bg-warning';
        feedback = 'Zayıf';
    } else if (strength < 70) {
        className = 'bg-info';
        feedback = 'Orta';
    } else if (strength < 90) {
        className = 'bg-success';
        feedback = 'Güçlü';
    } else {
        className = 'bg-success';
        feedback = 'Çok Güçlü';
    }
    
    strengthBar.className = 'strength-bar ' + className;
    strengthText.textContent = feedback;
    strengthText.className = 'strength-text text-' + className.replace('bg-', '');
});

// Password Confirmation Check
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.classList.add('is-invalid');
        this.classList.remove('is-valid');
    } else if (confirmPassword && newPassword === confirmPassword) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-invalid', 'is-valid');
    }
});

// Form Validation
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
    
    if (!confirm('Şifrenizi değiştirmek istediğinizden emin misiniz?')) {
        e.preventDefault();
        return false;
    }
});

// Form Reset Handler
document.querySelector('button[type="reset"]').addEventListener('click', function() {
    setTimeout(() => {
        // Reset notification switches to original values
        document.getElementById('notification_email').checked = <?php echo ($userData['notification_email'] ?? 1) ? 'true' : 'false'; ?>;
        document.getElementById('notification_sms').checked = <?php echo ($userData['notification_sms'] ?? 0) ? 'true' : 'false'; ?>;
        
        // Reset validation classes
        document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
            el.classList.remove('is-valid', 'is-invalid');
        });
        
        // Reset password strength
        document.getElementById('passwordStrength').style.width = '0%';
        document.getElementById('passwordStrengthText').textContent = 'Şifre girin';
    }, 100);
});

// Phone number formatting
document.getElementById('phone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    
    if (value.startsWith('90')) {
        value = value.substring(2);
    }
    
    if (value.length >= 10) {
        value = value.substring(0, 10);
        this.value = '+90 (' + value.substring(0, 3) + ') ' + value.substring(3, 6) + ' ' + value.substring(6, 8) + ' ' + value.substring(8, 10);
    }
});

// Toast Notification Function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    const iconClass = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle';
    
    toast.innerHTML = `
        <i class="fas fa-${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>