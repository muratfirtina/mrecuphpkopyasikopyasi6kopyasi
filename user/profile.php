<?php
/**
 * Mr ECU - Kullanıcı Profil Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/profile.php');
}

$user = new User($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);

$error = '';
$success = '';
$userId = $_SESSION['user_id'];

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone'])
    ];
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Ad ve soyad alanları zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$data['first_name'], $data['last_name'], $data['phone'], $userId]);
            
            if ($result) {
                $success = 'Profil bilgileri güncellendi.';
                
                // Log kaydı
                $user->logAction($userId, 'profile_update', 'Profil bilgileri güncellendi');
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
    } else {
        // Mevcut şifre kontrolü
        $userData = $user->getUserById($userId);
        
        if (!password_verify($currentPassword, $userData['password'])) {
            $error = 'Mevcut şifre hatalı.';
        } else {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $result = $stmt->execute([$hashedPassword, $userId]);
                
                if ($result) {
                    $success = 'Şifre başarıyla değiştirildi.';
                    
                    // Log kaydı
                    $user->logAction($userId, 'password_change', 'Şifre değiştirildi');
                }
            } catch(PDOException $e) {
                $error = 'Şifre değiştirme sırasında hata oluştu.';
            }
        }
    }
}

// Kullanıcı bilgilerini getir
$userData = $user->getUserById($userId);

$pageTitle = 'Profil Ayarları';
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
                        <i class="fas fa-user me-2"></i>Profil Ayarları
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Panele Dön
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Sol Kolon - Profil Bilgileri -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-edit me-2"></i>Kişisel Bilgiler
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">Ad *</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">Soyad *</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">Kullanıcı Adı</label>
                                            <input type="text" class="form-control" id="username" 
                                                   value="<?php echo htmlspecialchars($userData['username']); ?>" readonly>
                                            <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Adresi</label>
                                            <input type="email" class="form-control" id="email" 
                                                   value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                                            <div class="form-text">Email adresi değiştirilemez.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Telefon</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" 
                                                   placeholder="+90 555 123 45 67">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Bilgileri Güncelle
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Şifre Değiştirme -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>Şifre Değiştir
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mevcut Şifre *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('current_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Yeni Şifre *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                                   minlength="6" required>
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('new_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">En az 6 karakter olmalıdır.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Yeni Şifre Tekrar *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               minlength="6" required>
                                        <div class="invalid-feedback">
                                            Şifreler eşleşmiyor.
                                        </div>
                                    </div>
                                    
                                    <!-- Şifre Güçlülük Göstergesi -->
                                    <div class="mb-3">
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small id="passwordHelp" class="form-text text-muted">Şifre gücü: Zayıf</small>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-1"></i>Şifre Değiştir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sağ Kolon - Hesap Bilgileri -->
                    <div class="col-lg-4">
                        <!-- Hesap Özeti -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Hesap Özeti
                                </h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Üyelik Tarihi:</strong></td>
                                        <td><?php echo formatDate($userData['created_at']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Son Güncelleme:</strong></td>
                                        <td><?php echo formatDate($userData['updated_at']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hesap Durumu:</strong></td>
                                        <td>
                                            <span class="badge bg-success">Aktif</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email Doğrulandı:</strong></td>
                                        <td>
                                            <?php if ($userData['email_verified']): ?>
                                                <span class="badge bg-success">Evet</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Hayır</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kredi Bakiyesi:</strong></td>
                                        <td>
                                            <span class="badge bg-primary fs-6"><?php echo number_format($userData['credits'], 2); ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Güvenlik Önerileri -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>Güvenlik Önerileri
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Güçlü şifre kullanın
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Şifrenizi düzenli olarak değiştirin
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Şifrenizi kimseyle paylaşmayın
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Hesabınızdan çıkmayı unutmayın
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Hızlı İşlemler -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Hızlı İşlemler
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="upload.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-upload me-1"></i>Dosya Yükle
                                    </a>
                                    <a href="files.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-folder me-1"></i>Dosyalarım
                                    </a>
                                    <a href="credits.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-coins me-1"></i>Kredi Yükle
                                    </a>
                                    <a href="transactions.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-history me-1"></i>İşlem Geçmişi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Şifre görünürlük toggle
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Şifre güçlülük kontrolü
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordHelp');
            
            let strength = 0;
            let text = 'Çok Zayıf';
            let color = 'bg-danger';
            
            if (password.length >= 6) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;
            
            switch (strength) {
                case 0:
                case 1:
                    text = 'Çok Zayıf';
                    color = 'bg-danger';
                    break;
                case 2:
                    text = 'Zayıf';
                    color = 'bg-warning';
                    break;
                case 3:
                    text = 'Orta';
                    color = 'bg-info';
                    break;
                case 4:
                    text = 'Güçlü';
                    color = 'bg-success';
                    break;
                case 5:
                    text = 'Çok Güçlü';
                    color = 'bg-success';
                    break;
            }
            
            strengthBar.className = `progress-bar ${color}`;
            strengthBar.style.width = `${(strength / 5) * 100}%`;
            strengthText.textContent = `Şifre gücü: ${text}`;
        });
        
        // Şifre eşleştirme kontrolü
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Şifreler eşleşmiyor');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                const forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
