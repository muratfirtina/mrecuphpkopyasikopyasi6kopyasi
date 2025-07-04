<?php
/**
 * Mr ECU - Error Log Viewer
 * PHP error log ve debug mesajlarını görüntülemek için
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pageTitle = 'Error Log Viewer';
$logContent = '';
$logFile = '';
$logSize = 0;

// PHP error log dosyasını bul
$possibleLogFiles = [
    ini_get('error_log'),
    '/Applications/MAMP/logs/php_error.log',
    '/var/log/php_error.log',
    sys_get_temp_dir() . '/php_errors.log',
    './error.log',
    '../error.log'
];

// Log temizleme
if (isset($_POST['clear_log']) && !empty($_POST['log_file'])) {
    $fileToClear = $_POST['log_file'];
    if (file_exists($fileToClear) && is_writable($fileToClear)) {
        file_put_contents($fileToClear, '');
        $success = 'Log dosyası temizlendi.';
    } else {
        $error = 'Log dosyası temizlenemedi.';
    }
}

// Mevcut log dosyasını bul
foreach ($possibleLogFiles as $file) {
    if ($file && file_exists($file) && is_readable($file)) {
        $logFile = $file;
        $logSize = filesize($file);
        
        // Son 1000 satırı al (büyük dosyalar için)
        if ($logSize > 1024 * 1024) { // 1MB'dan büyükse
            $lines = [];
            $handle = fopen($file, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $lines[] = $line;
                    if (count($lines) > 1000) {
                        array_shift($lines);
                    }
                }
                fclose($handle);
                $logContent = implode('', $lines);
            }
        } else {
            $logContent = file_get_contents($file);
        }
        break;
    }
}

// uploadResponseFile log'larını filtrele
$uploadResponseLogs = [];
if ($logContent) {
    $lines = explode("\n", $logContent);
    foreach ($lines as $line) {
        if (strpos($line, 'uploadResponseFile') !== false) {
            $uploadResponseLogs[] = $line;
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
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .log-content {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            background: #1e1e1e;
            color: #d4d4d4;
            max-height: 600px;
            overflow-y: auto;
        }
        .log-error { color: #f44747; }
        .log-warning { color: #ffcc02; }
        .log-info { color: #75beff; }
        .log-debug { color: #98d982; }
    </style>
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-alt me-2"></i>Error Log Viewer
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="fas fa-sync-alt me-1"></i>Yenile
                        </button>
                        <a href="uploads.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Geri Dön
                        </a>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Log Bilgileri -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Log Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Log Dosyası:</strong></td>
                                        <td>
                                            <?php if ($logFile): ?>
                                                <code><?php echo htmlspecialchars($logFile); ?></code>
                                                <span class="badge bg-success">Bulundu</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Bulunamadı</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dosya Boyutu:</strong></td>
                                        <td><?php echo $logSize ? number_format($logSize / 1024, 2) . ' KB' : '0 KB'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>uploadResponseFile Logları:</strong></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo count($uploadResponseLogs); ?> adet</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>PHP Error Log:</strong></td>
                                        <td><code><?php echo ini_get('error_log') ?: 'Belirtilmemiş'; ?></code></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-tools me-2"></i>İşlemler
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($logFile): ?>
                                    <form method="POST" onsubmit="return confirm('Log dosyası tamamen temizlenecek. Emin misiniz?')">
                                        <input type="hidden" name="log_file" value="<?php echo htmlspecialchars($logFile); ?>">
                                        <button type="submit" name="clear_log" class="btn btn-warning btn-sm w-100 mb-2">
                                            <i class="fas fa-trash me-1"></i>Log Temizle
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="database-debug.php" class="btn btn-outline-info btn-sm w-100">
                                    <i class="fas fa-database me-1"></i>DB Debug
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- uploadResponseFile Logları -->
                <?php if (!empty($uploadResponseLogs)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-upload me-2"></i>uploadResponseFile Debug Logları
                                <span class="badge bg-info"><?php echo count($uploadResponseLogs); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="log-content p-3">
                                <?php foreach (array_reverse($uploadResponseLogs) as $log): ?>
                                    <div class="mb-1 <?php 
                                        if (strpos($log, 'ERROR') !== false) echo 'log-error';
                                        elseif (strpos($log, 'WARNING') !== false) echo 'log-warning';
                                        elseif (strpos($log, 'SUCCESS') !== false) echo 'log-debug';
                                        else echo 'log-info';
                                    ?>">
                                        <?php echo htmlspecialchars($log); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tam Log İçeriği -->
                <?php if ($logContent): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>Tam Log İçeriği (Son Kısım)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="log-content p-3">
                                <?php 
                                $lines = explode("\n", $logContent);
                                $recentLines = array_slice($lines, -200); // Son 200 satır
                                foreach ($recentLines as $line): 
                                    if (trim($line)):
                                ?>
                                    <div class="mb-1 <?php 
                                        if (strpos($line, 'Fatal error') !== false || strpos($line, 'ERROR') !== false) echo 'log-error';
                                        elseif (strpos($line, 'Warning') !== false || strpos($line, 'WARNING') !== false) echo 'log-warning';
                                        elseif (strpos($line, 'uploadResponseFile') !== false) echo 'log-debug';
                                        else echo 'log-info';
                                    ?>">
                                        <?php echo htmlspecialchars($line); ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">Log Dosyası Bulunamadı</h4>
                            <p class="text-muted">PHP error log dosyası bulunamadı veya okunamadı.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Otomatik yenile (30 saniyede bir)
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Log içeriğini en alta kaydır
        document.addEventListener('DOMContentLoaded', function() {
            const logContents = document.querySelectorAll('.log-content');
            logContents.forEach(function(log) {
                log.scrollTop = log.scrollHeight;
            });
        });
    </script>
</body>
</html>
