<?php
/**
 * Mr ECU - Session & Admin Debug
 * Session ve admin durumunu kontrol etmek için
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Admin Debug';
$debugInfo = [];

// Session bilgilerini topla
$debugInfo['session'] = $_SESSION ?? [];
$debugInfo['logged_in'] = isLoggedIn();
$debugInfo['is_admin_check'] = isAdmin();

// Eğer giriş yapmışsa kullanıcı bilgilerini al
if (isLoggedIn()) {
    $user = new User($pdo);
    $userDetails = $user->getUserById($_SESSION['user_id']);
    $debugInfo['user_from_db'] = $userDetails;
}

// Tüm admin kullanıcıları listele
try {
    $stmt = $pdo->query("SELECT id, username, email, role, status FROM users WHERE role = 'admin'");
    $adminUsers = $stmt->fetchAll();
    $debugInfo['admin_users'] = $adminUsers;
} catch(PDOException $e) {
    $debugInfo['admin_users_error'] = $e->getMessage();
}

// POST ile admin güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_admin'])) {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $result = $stmt->execute([$_SESSION['user_id']]);
        
        if ($result) {
            $_SESSION['role'] = 'admin'; // Session'ı da güncelle
            $_SESSION['is_admin'] = 1; // Compat için
            $debugInfo['admin_update'] = 'Role admin olarak güncellendi ve session yenilendi.';
        } else {
            $debugInfo['admin_update'] = 'Role güncellenemedi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="text-center mt-3 mb-4">
                    <h1 class="h3 mb-3 fw-normal">
                        <i class="fas fa-bug text-warning"></i>
                        Admin Debug Panel
                    </h1>
                    <p class="text-muted">Session ve admin yetkisi kontrolü</p>
                </div>
                
                <!-- Hızlı Eylemler -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-sign-in-alt text-primary" style="font-size: 2rem;"></i>
                                <h5 class="mt-2">Giriş Durumu</h5>
                                <span class="badge bg-<?php echo isLoggedIn() ? 'success' : 'danger'; ?> fs-6">
                                    <?php echo isLoggedIn() ? 'Giriş Yapılmış' : 'Giriş Yapılmamış'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-crown text-warning" style="font-size: 2rem;"></i>
                                <h5 class="mt-2">Admin Durumu</h5>
                                <span class="badge bg-<?php echo isAdmin() ? 'success' : 'danger'; ?> fs-6">
                                    <?php echo isAdmin() ? 'Admin' : 'User'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tools text-info" style="font-size: 2rem;"></i>
                                <h5 class="mt-2">Hızlı Düzelt</h5>
                                <?php if (isLoggedIn()): ?>
                                    <form method="POST">
                                        <button type="submit" name="force_admin" class="btn btn-warning btn-sm">
                                            <i class="fas fa-magic me-1"></i>Admin Yap
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Güncellenme Mesajı -->
                <?php if (isset($debugInfo['admin_update'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i><?php echo $debugInfo['admin_update']; ?>
                        <a href="?" class="btn btn-sm btn-outline-primary ms-2">Sayfayı Yenile</a>
                    </div>
                <?php endif; ?>

                <!-- Debug Bilgileri -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-code me-2"></i>Debug Bilgileri
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Session Bilgileri -->
                            <div class="col-md-6">
                                <h6>Session Bilgileri:</h6>
                                <pre class="bg-light p-3 small"><?php echo htmlspecialchars(print_r($debugInfo['session'], true)); ?></pre>
                                
                                <h6 class="mt-3">isLoggedIn() ve isAdmin() Sonuçları:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>isLoggedIn():</strong></td>
                                        <td><span class="badge bg-<?php echo $debugInfo['logged_in'] ? 'success' : 'danger'; ?>"><?php echo $debugInfo['logged_in'] ? 'true' : 'false'; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>isAdmin():</strong></td>
                                        <td><span class="badge bg-<?php echo $debugInfo['is_admin_check'] ? 'success' : 'danger'; ?>"><?php echo $debugInfo['is_admin_check'] ? 'true' : 'false'; ?></span></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Veritabanı Bilgileri -->
                            <div class="col-md-6">
                                <?php if (isset($debugInfo['user_from_db'])): ?>
                                    <h6>Veritabanından Kullanıcı:</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>ID:</strong></td>
                                            <td><code class="small"><?php echo htmlspecialchars($debugInfo['user_from_db']['id']); ?></code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Username:</strong></td>
                                            <td><?php echo htmlspecialchars($debugInfo['user_from_db']['username']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($debugInfo['user_from_db']['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Role (Session):</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'success' : 'primary'; ?>">
                                                    <?php 
                                                    if (isset($_SESSION['role'])) {
                                                        echo $_SESSION['role'];
                                                    } else {
                                                        echo 'Not Set';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Role (DB):</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($debugInfo['user_from_db']['role'] === 'admin') ? 'success' : 'primary'; ?>">
                                                    <?php echo $debugInfo['user_from_db']['role']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="badge bg-<?php echo $debugInfo['user_from_db']['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo $debugInfo['user_from_db']['status']; ?></span></td>
                                        </tr>
                                    </table>
                                <?php endif; ?>
                                
                                <h6 class="mt-3">Tüm Admin Kullanıcılar:</h6>
                                <?php if (isset($debugInfo['admin_users'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($debugInfo['admin_users'] as $admin): ?>
                                                    <tr <?php echo (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $admin['id']) ? 'class="table-primary"' : ''; ?>>
                                                        <td>
                                                            <?php echo htmlspecialchars($admin['username']); ?>
                                                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $admin['id']): ?>
                                                                <i class="fas fa-arrow-left text-primary ms-1" title="Siz"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                                        <td><span class="badge bg-success"><?php echo $admin['role']; ?></span></td>
                                                        <td><span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo $admin['status']; ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning small">
                                        Admin kullanıcı bulunamadı veya hata: <?php echo $debugInfo['admin_users_error'] ?? 'Bilinmeyen hata'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hızlı Linkler -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6>Hızlı Linkler:</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="index.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-home me-1"></i>Ana Sayfa
                            </a>
                            <a href="login.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                            <a href="clear-session.php" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-broom me-1"></i>Session Temizle
                            </a>
                            <a href="admin/" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-tachometer-alt me-1"></i>Admin Panel
                            </a>
                            <a href="user/" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-user me-1"></i>User Panel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
