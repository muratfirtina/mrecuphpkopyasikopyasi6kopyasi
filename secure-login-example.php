<?php
/**
 * Mr ECU - Güvenli Login Örneği
 * SQL Injection, Brute Force ve CSRF koruması ile
 */

require_once 'config/config.php';

// Rate limiting kontrolü
if (!checkRateLimit('login_attempt', $_SERVER['REMOTE_ADDR'], 10, 300)) {
    logSecurityEvent('login_rate_limit_exceeded', $_SERVER['REMOTE_ADDR']);
    $error = 'Çok fazla giriş denemesi. Lütfen 5 dakika bekleyin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($error)) {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_token_invalid', 'Login form');
        $error = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
    } else {
        // Input sanitization
        $email = sanitize($_POST['email'] ?? '', 'email');
        $password = $_POST['password'] ?? '';
        
        // Boş alan kontrolü
        if (empty($email) || empty($password)) {
            $error = 'Email ve şifre alanları boş bırakılamaz.';
        } else {
            // Brute force kontrolü
            $identifier = $email . '_' . $_SERVER['REMOTE_ADDR'];
            
            if (!checkBruteForce($identifier)) {
                recordBruteForceAttempt($identifier);
                logSecurityEvent('brute_force_blocked', $identifier);
                $error = 'Çok fazla başarısız deneme. Hesabınız geçici olarak bloklandı.';
            } else {
                try {
                    // Güvenli database sorgusu
                    if (isset($secureDb) && SECURITY_ENABLED) {
                        $users = $secureDb->secureSelect('users', '*', ['email' => $email, 'status' => 'active'], null, 1);
                        $user = $users->fetch();
                    } else {
                        // Fallback normal PDO
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
                        $stmt->execute([$email]);
                        $user = $stmt->fetch();
                    }
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Başarılı giriş
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['login_time'] = time();
                        
                        // Güvenlik logu
                        logSecurityEvent('successful_login', [
                            'user_id' => $user['id'],
                            'email' => $email,
                            'ip' => $_SERVER['REMOTE_ADDR']
                        ]);
                        
                        // Son giriş tarihini güncelle
                        try {
                            if (isset($secureDb) && SECURITY_ENABLED) {
                                $secureDb->secureUpdate('users', 
                                    ['last_login' => date('Y-m-d H:i:s')], 
                                    ['id' => $user['id']]
                                );
                            } else {
                                $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                                $stmt->execute([$user['id']]);
                            }
                        } catch (Exception $e) {
                            error_log('Last login update failed: ' . $e->getMessage());
                        }
                        
                        // Redirect
                        if ($user['role'] === 'admin') {
                            redirect('admin/');
                        } else {
                            redirect('user/');
                        }
                    } else {
                        // Başarısız giriş
                        recordBruteForceAttempt($identifier);
                        logSecurityEvent('failed_login', [
                            'email' => $email,
                            'ip' => $_SERVER['REMOTE_ADDR'],
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                        $error = 'Email veya şifre hatalı.';
                    }
                    
                } catch (Exception $e) {
                    error_log('Login error: ' . $e->getMessage());
                    logSecurityEvent('login_system_error', $e->getMessage());
                    $error = 'Sistem hatası. Lütfen tekrar deneyin.';
                }
            }
        }
    }
}

// HTML başlıkları ve güvenlik headers
if (SECURITY_ENABLED) {
    SecurityHeaders::setAllHeaders();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Giriş</title>
    
    <?php 
    $nonce = renderSecurityMeta(); 
    securityStyleTag(null, "
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .login-container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type=email], input[type=password] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: #dc3545; margin-bottom: 15px; padding: 10px; border: 1px solid #dc3545; border-radius: 4px; background: #f8d7da; }
        .security-info { margin-top: 20px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; font-size: 12px; color: #0066cc; }
    ");
    ?>
</head>
<body>
    <div class="login-container">
        <h2><?php echo SITE_NAME; ?> - Güvenli Giriş</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit">Giriş Yap</button>
        </form>
        
        <div class="security-info">
            🛡️ Bu sayfa güvenlik önlemleri ile korunmaktadır:
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li>SQL Injection koruması aktif</li>
                <li>Brute force koruması aktif</li>
                <li>CSRF token koruması aktif</li>
                <li>Rate limiting aktif</li>
                <li>Güvenlik logları aktif</li>
            </ul>
        </div>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="register.php">Hesap oluştur</a> | 
            <a href="forgot-password.php">Şifremi unuttum</a>
        </p>
    </div>
    
    <?php includeSecurityScript(); ?>
    
    <?php
    securityScriptTag(null, "
        // Form submit güvenlik kontrolü
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Boş alan kontrolü
            if (!email.trim() || !password.trim()) {
                e.preventDefault();
                alert('Lütfen tüm alanları doldurun.');
                return;
            }
            
            // Email format kontrolü
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Geçerli bir email adresi girin.');
                return;
            }
            
            // XSS kontrolü
            if (SecurityGuard.containsMaliciousContent(email) || 
                SecurityGuard.containsMaliciousContent(password)) {
                e.preventDefault();
                alert('Güvenlik hatası tespit edildi.');
                return;
            }
        });
        
        // Sayfa yüklendiğinde CSRF token kontrolü
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('input[name=\"csrf_token\"]').value;
            if (!csrfToken) {
                console.warn('CSRF token eksik!');
                location.reload();
            }
        });
    ");
    ?>
</body>
</html>
