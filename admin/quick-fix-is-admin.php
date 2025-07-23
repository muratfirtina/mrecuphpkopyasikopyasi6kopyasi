<?php
/**
 * Mr ECU - Quick Fix for is_admin issue
 * is_admin alanını role ile değiştirmek için
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pageTitle = 'Quick Fix is_admin';
$results = [];

// Tüm PHP dosyalarında is_admin aramak
function findIsAdminUsage($directory) {
    $results = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getRealPath());
            if (strpos($content, 'is_admin') !== false && strpos($content, 'WHERE') !== false) {
                // is_admin ve WHERE içeren satırları bul
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (stripos($line, 'is_admin') !== false && stripos($line, 'WHERE') !== false) {
                        $results[] = [
                            'file' => $file->getRealPath(),
                            'line' => $lineNum + 1,
                            'content' => trim($line)
                        ];
                    }
                }
            }
        }
    }
    
    return $results;
}

// Manuel fix işlemi
if (isset($_POST['manual_fix'])) {
    $searchResults = findIsAdminUsage('/Applications/MAMP/htdocs/mrecuphpkopyasi');
    
    foreach ($searchResults as $result) {
        $filePath = $result['file'];
        $content = file_get_contents($filePath);
        
        // is_admin = 1 veya is_admin = TRUE tarzı kullanımları role = 'admin' ile değiştir
        $patterns = [
            "/is_admin\s*=\s*1/i" => "role = 'admin'",
            "/is_admin\s*=\s*TRUE/i" => "role = 'admin'",
            "/is_admin\s*=\s*'1'/i" => "role = 'admin'",
            "/AND\s+is_admin\s+AND/i" => "AND role = 'admin' AND"
        ];
        
        $originalContent = $content;
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $results[] = "Fixed: " . basename($filePath);
        }
    }
}

// Mevcut is_admin kullanımlarını ara
$searchResults = findIsAdminUsage('/Applications/MAMP/htdocs/mrecuphpkopyasi');
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
    <?php include '../includes/user_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/user_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-wrench me-2"></i>Quick Fix is_admin
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="uploads.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Geri Dön
                        </a>
                    </div>
                </div>

                <?php if (!empty($results)): ?>
                    <div class="alert alert-success">
                        <h5>✅ Düzeltme Sonuçları:</h5>
                        <ul>
                            <?php foreach ($results as $result): ?>
                                <li><?php echo htmlspecialchars($result); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- is_admin Kullanımları -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i>is_admin Kullanımları
                            <span class="badge bg-<?php echo empty($searchResults) ? 'success' : 'warning'; ?>">
                                <?php echo count($searchResults); ?> adet
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($searchResults)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Harika!</strong> WHERE clause'da is_admin kullanımı bulunamadı.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                WHERE clause'da is_admin kullanımları bulundu. Bunları düzeltmek gerekiyor.
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Dosya</th>
                                            <th>Satır</th>
                                            <th>İçerik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($searchResults as $result): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars(basename($result['file'])); ?></code></td>
                                                <td><span class="badge bg-info"><?php echo $result['line']; ?></span></td>
                                                <td><small><code><?php echo htmlspecialchars($result['content']); ?></code></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <form method="POST" class="mt-3">
                                <button type="submit" name="manual_fix" class="btn btn-danger" 
                                        onclick="return confirm('Tüm is_admin kullanımları role = \'admin\' ile değiştirilecek. Emin misiniz?')">
                                    <i class="fas fa-magic me-1"></i>Otomatik Düzelt
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Manuel Test -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-flask me-2"></i>Manuel Test
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>FileManager uploadResponseFile metodunu test etmek için:</p>
                        <ol>
                            <li>MAMP'ı yeniden başlat</li>
                            <li>Browser cache'ini temizle (Ctrl+F5)</li>
                            <li>Tekrar yanıt dosyası yüklemeyi dene</li>
                            <li>Log viewer'da yeni hataları kontrol et</li>
                        </ol>
                        
                        <div class="d-flex gap-2 mt-3">
                            <a href="log-viewer.php" class="btn btn-outline-info">
                                <i class="fas fa-file-alt me-1"></i>Log Viewer
                            </a>
                            <a href="uploads.php" class="btn btn-outline-primary">
                                <i class="fas fa-upload me-1"></i>Dosya Yükleme Test
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
