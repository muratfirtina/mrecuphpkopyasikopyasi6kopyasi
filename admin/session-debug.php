<?php
/**
 * Mr ECU - Session Debug & User Check
 * Session ve kullanıcı durumu kontrolü
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/SessionValidator.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$sessionValidator = new SessionValidator($pdo);
$currentUser = null;
$sessionInfo = [];
$userCount = 0;

// Mevcut session bilgilerini al
if (isset($_SESSION['user_id'])) {
    $validation = $sessionValidator->validateSessionUser();
    $sessionInfo = $validation;
    
    if ($validation['valid']) {
        $currentUser = $validation['user'];
    }
}

// Tüm kullanıcıları listele
try {
    $stmt = $pdo->query("
        SELECT id, username, email, first_name, last_name, is_admin, status, created_at, last_login 
        FROM users 
        ORDER BY is_admin DESC, username
    ");
    $allUsers = $stmt->fetchAll();
    $userCount = count($allUsers);
} catch(PDOException $e) {
    $allUsers = [];
    $error = "Kullanıcılar alınırken hata: " . $e->getMessage();
}

// Session temizle
if (isset($_POST['clear_session'])) {
    session_destroy();
    session_start();
    $_SESSION = [];
    header('Location: session-debug.php?cleared=1');
    exit;
}

$pageTitle = 'Session Debug';
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
                        <i class="fas fa-bug me-2"></i>Session Debug
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <form method="POST" class="me-2">
                            <button type="submit" name="clear_session" class="btn btn-warning btn-sm">
                                <i class="fas fa-trash me-1"></i>Session Temizle
                            </button>
                        </form>
                        <a href="uploads.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Geri Dön
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['cleared'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>Session temizlendi.
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Mevcut Session Bilgileri -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-circle me-2"></i>Mevcut Session
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($currentUser): ?>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td><strong>User ID:</strong></td>
                                            <td><code><?php echo htmlspecialchars($currentUser['id']); ?></code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Username:</strong></td>
                                            <td><?php echo htmlspecialchars($currentUser['username']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Admin:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $currentUser['is_admin'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $currentUser['is_admin'] ? 'Evet' : 'Hayır'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $currentUser['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo $currentUser['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Session Geçerli:</strong></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>Evet
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Session geçersiz veya kullanıcı bulunamadı.
                                        
                                        <?php if (isset($sessionInfo['message'])): ?>
                                            <br><small><?php echo htmlspecialchars($sessionInfo['message']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <strong>Session Değişkenleri:</strong>
                                        <pre class="bg-light p-2 small"><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Sistem Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Session ID:</strong></td>
                                        <td><code class="small"><?php echo session_id(); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Toplam Kullanıcı:</strong></td>
                                        <td><?php echo $userCount; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Veritabanı:</strong></td>
                                        <td><?php echo $pdo ? '<span class="badge bg-success">Bağlı</span>' : '<span class="badge bg-danger">Hata</span>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>PHP Version:</strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Server Time:</strong></td>
                                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tüm Kullanıcılar -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Tüm Kullanıcılar (<?php echo $userCount; ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($allUsers)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Ad Soyad</th>
                                            <th>Admin</th>
                                            <th>Status</th>
                                            <th>Son Giriş</th>
                                            <th>Kayıt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allUsers as $user): ?>
                                            <tr <?php echo (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $user['id']) ? 'class="table-primary"' : ''; ?>>
                                                <td>
                                                    <code class="small"><?php echo substr($user['id'], 0, 8); ?>...</code>
                                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $user['id']): ?>
                                                        <i class="fas fa-arrow-left text-primary ms-1" title="Mevcut kullanıcı"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['is_admin'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo $user['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $user['last_login'] ? formatDate($user['last_login']) : '-'; ?>
                                                </td>
                                                <td>
                                                    <?php echo formatDate($user['created_at']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users text-muted" style="font-size: 3rem;"></i>
                                <h4 class="mt-3 text-muted">Kullanıcı bulunamadı</h4>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
