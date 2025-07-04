<?php
/**
 * Mr ECU - Session Temizleyici
 * Sorunlu session'ları temizlemek için
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Session debug bilgileri
$sessionInfo = [
    'before_clear' => $_SESSION ?? [],
    'session_id' => session_id(),
    'session_status' => session_status()
];

// Session'ı tamamen temizle
session_destroy();

// Yeni session başlat
session_start();
session_regenerate_id(true);

// Cookie'leri temizle
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Diğer cookie'leri de temizle
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'remember') !== false || strpos($name, 'auth') !== false) {
        setcookie($name, '', time() - 3600, '/');
    }
}

$sessionInfo['after_clear'] = $_SESSION ?? [];
$sessionInfo['new_session_id'] = session_id();

$pageTitle = 'Session Temizlendi';
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
            <div class="col-md-8 col-lg-6">
                <div class="text-center mt-5 mb-4">
                    <h1 class="h3 mb-3 fw-normal">
                        <i class="fas fa-broom text-success"></i>
                        Session Temizlendi
                    </h1>
                </div>
                
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Session başarıyla temizlendi!</strong>
                            <p class="mb-0 mt-2">Artık tekrar giriş yapabilirsiniz.</p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Ana Sayfa
                            </a>
                        </div>
                        
                        <!-- Debug Bilgileri (Development için) -->
                        <?php if (error_reporting() > 0): ?>
                            <hr class="my-4">
                            <div class="text-center">
                                <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#debugInfo">
                                    <i class="fas fa-code me-1"></i>Debug Bilgileri
                                </button>
                            </div>
                            
                            <div class="collapse mt-3" id="debugInfo">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>Session Durumu:</h6>
                                        <pre class="small"><?php echo htmlspecialchars(print_r($sessionInfo, true)); ?></pre>
                                        
                                        <h6 class="mt-3">Server Bilgileri:</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>PHP Version:</strong></td>
                                                <td><?php echo PHP_VERSION; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Session Save Path:</strong></td>
                                                <td><?php echo session_save_path(); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Session GC Maxlifetime:</strong></td>
                                                <td><?php echo ini_get('session.gc_maxlifetime'); ?>s</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Current Time:</strong></td>
                                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Session sorunları yaşıyorsanız bu sayfayı kullanarak temizleyebilirsiniz.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
