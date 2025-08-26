<?php
/**
 * Mr ECU - Kullanıcı Kayıt Sayfası (Geliştirilmiş Tasarım)
 */
require_once 'config/config.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/' : 'user/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone'])
    ];

    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
        empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Tüm zorunlu alanları doldurun.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $user = new User($pdo);
        $result = $user->register($data);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Kayıt Ol';
$pageDescription = 'Mr ECU hesabı oluşturun ve profesyonel ECU hizmetlerimizden faydalanmaya başlayın.';
$pageKeywords = 'kayıt ol, hesap oluştur, üye ol, ECU hizmetleri';
$bodyClass = 'bg-dark';
include 'includes/header.php';
?>

<section class="py-5" style="min-height: 100vh; display: flex; align-items: center; background: url('https://images.unsplash.com/photo-1593508512255-86ab42a8e620?w=1920&h=1080&fit=crop') center/cover fixed;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(44, 62, 80, 0.7); z-index: 1;"></div>
    <div class="container" style="position: relative; z-index: 2;">
        <div class="row justify-content-center">
            <!-- Register Form -->
            <div class="col-lg-5 col-md-7 mb-4">
                <div class="card border-0 shadow-lg" style="
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(12px);
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
                ">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                        <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i> Hesap Oluştur</h3>
                        <p class="mb-0 mt-1" style="opacity: 0.85;">Ücretsiz üye olun</p>
                    </div>
                    <div class="card-body p-5">
                        <?php if ($error): ?>
                            <div class="alert alert-danger border-0 rounded-4 text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success border-0 rounded-4 text-center">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-success rounded-4 px-4">Giriş Yap</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" id="registerForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ad *</label>
                                        <input type="text" name="first_name" class="form-control rounded-4" required
                                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Soyad *</label>
                                        <input type="text" name="last_name" class="form-control rounded-4" required
                                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Kullanıcı Adı *</label>
                                        <input type="text" name="username" class="form-control rounded-4" required
                                               pattern="[a-zA-Z0-9_]+"
                                               title="Sadece harf, rakam ve _ kullanabilirsiniz"
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">E-posta *</label>
                                        <input type="email" name="email" class="form-control rounded-4" required
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Telefon</label>
                                        <input type="tel" name="phone" class="form-control rounded-4"
                                               placeholder="+90 (555) 123 45 67">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Şifre *</label>
                                        <input type="password" name="password" id="password" class="form-control rounded-4" required minlength="6">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Şifre Tekrar *</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control rounded-4" required>
                                    </div>
                                </div>

                                <div class="form-check mt-4">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-none text-primary">Kullanım Şartları</a> ve
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal" class="text-decoration-none text-primary">Gizlilik Politikası</a>'nı kabul ediyorum.
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 mt-4 rounded-4 py-3 fw-bold"
                                        style="background: linear-gradient(135deg, #28a745, #20c997); border: none;">
                                    <i class="fas fa-user-plus me-2"></i> Hesap Oluştur
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <p class="text-muted small">Zaten hesabınız var mı?
                                <a href="login.php" class="text-success fw-bold text-decoration-none">Giriş yap</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-lg h-100" style="
                    background: rgba(255, 255, 255, 0.15);
                    backdrop-filter: blur(10px);
                    border-radius: 16px;
                    color: white;
                    overflow: hidden;
                ">
                    <div class="card-body p-4">
                        <h4 class="text-white mb-4">Neden <?php echo SITE_NAME; ?>?</h4>
                        <div class="d-flex align-items-start mb-4 text-white">
                            <i class="fas fa-shield-alt fa-2x me-3 opacity-75"></i>
                            <div>
                                <h6>Güvenli Platform</h6>
                                <small>SSL şifreleme ile korunan tüm verileriniz.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-4 text-white">
                            <i class="fas fa-rocket fa-2x me-3 opacity-75"></i>
                            <div>
                                <h6>Hızlı İşlem</h6>
                                <small>ECU dosyalarınız 24 saat içinde işlenir.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-4 text-white">
                            <i class="fas fa-headset fa-2x me-3 opacity-75"></i>
                            <div>
                                <h6>7/24 Destek</h6>
                                <small>Uzman ekibimiz her zaman yanınızda.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms & Privacy Modals -->
<div class="modal fade" id="termsModal">...</div>
<div class="modal fade" id="privacyModal">...</div>

<script>
document.getElementById('confirm_password').addEventListener('input', function () {
    const match = this.value === document.getElementById('password').value;
    this.classList.toggle('is-invalid', !match && this.value);
    this.classList.toggle('is-valid', match && this.value);
});
</script>

<style>
    @media (max-width: 768px) {
        .card-body { padding: 2rem 1.5rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>