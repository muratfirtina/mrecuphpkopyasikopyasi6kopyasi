<?php
/**
 * MR.ECU Tuning - Security Dashboard
 * Güvenlik tarama ve kontrol paneli
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

// Admin kontrolü (opsiyonel - geliştirme aşamasında kapalı)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: login.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard - MR.ECU Tuning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .security-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .security-pass {
            border-left: 4px solid #28a745;
        }
        .security-fail {
            border-left: 4px solid #dc3545;
        }
        .security-warning {
            border-left: 4px solid #ffc107;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            height: 100%;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .vulnerability-badge {
            font-size: 0.9rem;
            padding: 6px 12px;
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <div class="card">
            <div class="card-header bg-danger text-white text-center py-4">
                <h2 class="mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    Security Dashboard
                </h2>
                <p class="mb-0 mt-2">Güvenlik Tarama ve Kontrol Paneli</p>
            </div>
            <div class="card-body p-4">

<?php
$securityIssues = [];
$criticalCount = 0;
$warningCount = 0;
$passCount = 0;

// CATEGORY 1: File Permissions
echo "<div class='mb-4'>";
echo "<h4><i class='fas fa-file-shield me-2'></i>Dosya İzinleri ve Güvenlik</h4>";

$sensitiveFiles = [
    'config/database.php' => ['writable' => false, 'critical' => true],
    '.env' => ['writable' => false, 'critical' => true],
    'uploads/' => ['writable' => true, 'critical' => false],
    'logs/' => ['writable' => true, 'critical' => false],
    'includes/' => ['writable' => false, 'critical' => false],
];

foreach ($sensitiveFiles as $file => $rules) {
    $filePath = __DIR__ . '/' . $file;
    $exists = file_exists($filePath) || is_dir($filePath);
    
    if ($exists) {
        $isWritable = is_writable($filePath);
        $shouldBeWritable = $rules['writable'];
        
        if ($isWritable === $shouldBeWritable) {
            $passCount++;
            echo "<div class='security-item security-pass'>";
            echo "<i class='fas fa-check-circle text-success me-2'></i>";
            echo "<strong>$file</strong> - İzinler doğru ";
            echo $isWritable ? "(Yazılabilir)" : "(Salt-okunur)";
        } else {
            if ($rules['critical']) {
                $criticalCount++;
                echo "<div class='security-item security-fail'>";
                echo "<i class='fas fa-exclamation-triangle text-danger me-2'></i>";
                echo "<strong>$file</strong> - KRİTİK: ";
            } else {
                $warningCount++;
                echo "<div class='security-item security-warning'>";
                echo "<i class='fas fa-exclamation-circle text-warning me-2'></i>";
                echo "<strong>$file</strong> - UYARI: ";
            }
            
            if ($isWritable && !$shouldBeWritable) {
                echo "Yazılabilir durumda (güvenlik riski)";
                $securityIssues[] = [
                    'file' => $file,
                    'issue' => 'Dosya/klasör yazılabilir durumda',
                    'severity' => $rules['critical'] ? 'critical' : 'warning',
                    'fix' => 'chmod 644 ' . $file
                ];
            } else {
                echo "Yazılamıyor (işlevsellik sorunu)";
            }
        }
        echo "</div>";
    } else {
        $warningCount++;
        echo "<div class='security-item security-warning'>";
        echo "<i class='fas fa-question-circle text-warning me-2'></i>";
        echo "<strong>$file</strong> - Bulunamadı";
        echo "</div>";
    }
}

echo "</div>";

// CATEGORY 2: PHP Configuration
echo "<div class='mb-4'>";
echo "<h4><i class='fas fa-cog me-2'></i>PHP Güvenlik Ayarları</h4>";

$phpSettings = [
    ['setting' => 'display_errors', 'expected' => '0', 'critical' => true, 'message' => 'Production\'da kapalı olmalı'],
    ['setting' => 'expose_php', 'expected' => 'Off', 'critical' => false, 'message' => 'PHP versiyonunu gizle'],
    ['setting' => 'file_uploads', 'expected' => '1', 'critical' => false, 'message' => 'Dosya yükleme aktif olmalı'],
    ['setting' => 'session.cookie_httponly', 'expected' => '1', 'critical' => true, 'message' => 'Cookie güvenliği'],
    ['setting' => 'session.use_strict_mode', 'expected' => '1', 'critical' => true, 'message' => 'Session güvenliği'],
];

foreach ($phpSettings as $setting) {
    $currentValue = ini_get($setting['setting']);
    $isSecure = ($currentValue == $setting['expected']) || 
                (strtolower($currentValue) == strtolower($setting['expected']));
    
    if ($isSecure) {
        $passCount++;
        echo "<div class='security-item security-pass'>";
        echo "<i class='fas fa-check-circle text-success me-2'></i>";
        echo "<strong>{$setting['setting']}</strong> = <code>$currentValue</code> ✓";
    } else {
        if ($setting['critical']) {
            $criticalCount++;
            echo "<div class='security-item security-fail'>";
            echo "<i class='fas fa-times-circle text-danger me-2'></i>";
            $securityIssues[] = [
                'file' => 'php.ini',
                'issue' => $setting['setting'] . ' = ' . $currentValue,
                'severity' => 'critical',
                'fix' => $setting['message']
            ];
        } else {
            $warningCount++;
            echo "<div class='security-item security-warning'>";
            echo "<i class='fas fa-exclamation-triangle text-warning me-2'></i>";
        }
        
        echo "<strong>{$setting['setting']}</strong> = <code>$currentValue</code> ";
        echo "<br><small class='text-muted'>{$setting['message']}</small>";
    }
    echo "</div>";
}

echo "</div>";

// CATEGORY 3: Database Security
echo "<div class='mb-4'>";
echo "<h4><i class='fas fa-database me-2'></i>Database Güvenliği</h4>";

try {
    // Admin kullanıcılarını kontrol et
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount > 0) {
        $passCount++;
        echo "<div class='security-item security-pass'>";
        echo "<i class='fas fa-check-circle text-success me-2'></i>";
        echo "<strong>Admin Kullanıcılar:</strong> $adminCount adet ✓";
        echo "</div>";
    } else {
        $criticalCount++;
        echo "<div class='security-item security-fail'>";
        echo "<i class='fas fa-times-circle text-danger me-2'></i>";
        echo "<strong>Admin Kullanıcılar:</strong> Bulunamadı ✗";
        echo "</div>";
        $securityIssues[] = [
            'file' => 'Database',
            'issue' => 'Admin kullanıcısı yok',
            'severity' => 'critical',
            'fix' => 'install-guid.php ile admin oluşturun'
        ];
    }
    
    // Zayıf şifreli kullanıcıları kontrol et (geliştirme aşamasında)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE password = ''");
    $emptyPassCount = $stmt->fetchColumn();
    
    if ($emptyPassCount === 0) {
        $passCount++;
        echo "<div class='security-item security-pass'>";
        echo "<i class='fas fa-check-circle text-success me-2'></i>";
        echo "<strong>Boş Şifreler:</strong> Yok ✓";
        echo "</div>";
    } else {
        $criticalCount++;
        echo "<div class='security-item security-fail'>";
        echo "<i class='fas fa-times-circle text-danger me-2'></i>";
        echo "<strong>Boş Şifreler:</strong> $emptyPassCount kullanıcı ✗";
        echo "</div>";
    }
    
    // System logs tablosunu kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    if ($stmt->rowCount() > 0) {
        $passCount++;
        $logCount = $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
        echo "<div class='security-item security-pass'>";
        echo "<i class='fas fa-check-circle text-success me-2'></i>";
        echo "<strong>Security Logs:</strong> Aktif ($logCount kayıt) ✓";
        echo "</div>";
    } else {
        $warningCount++;
        echo "<div class='security-item security-warning'>";
        echo "<i class='fas fa-exclamation-triangle text-warning me-2'></i>";
        echo "<strong>Security Logs:</strong> Tablo bulunamadı";
        echo "</div>";
    }
    
} catch (Exception $e) {
    $criticalCount++;
    echo "<div class='security-item security-fail'>";
    echo "<i class='fas fa-times-circle text-danger me-2'></i>";
    echo "<strong>Database Kontrolü:</strong> Hata - " . $e->getMessage();
    echo "</div>";
}

echo "</div>";

// CATEGORY 4: File Upload Security
echo "<div class='mb-4'>";
echo "<h4><i class='fas fa-upload me-2'></i>Dosya Yükleme Güvenliği</h4>";

$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxExecutionTime = ini_get('max_execution_time');

$uploadSizeMB = (int)$uploadMaxSize;
if ($uploadSizeMB <= 100) {
    $passCount++;
    echo "<div class='security-item security-pass'>";
    echo "<i class='fas fa-check-circle text-success me-2'></i>";
    echo "<strong>Upload Max Size:</strong> $uploadMaxSize ✓";
} else {
    $warningCount++;
    echo "<div class='security-item security-warning'>";
    echo "<i class='fas fa-exclamation-triangle text-warning me-2'></i>";
    echo "<strong>Upload Max Size:</strong> $uploadMaxSize (çok yüksek)";
}
echo "</div>";

echo "<div class='security-item security-pass'>";
echo "<i class='fas fa-info-circle text-info me-2'></i>";
echo "<strong>Post Max Size:</strong> $postMaxSize";
echo "</div>";

echo "<div class='security-item security-pass'>";
echo "<i class='fas fa-clock text-info me-2'></i>";
echo "<strong>Max Execution Time:</strong> {$maxExecutionTime}s";
echo "</div>";

echo "</div>";

// STATISTICS
echo "<div class='row mb-4'>";

echo "<div class='col-md-4'>";
echo "<div class='stat-card'>";
echo "<div class='stat-value text-success'>$passCount</div>";
echo "<div class='text-muted'>Güvenli</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='stat-card'>";
echo "<div class='stat-value text-warning'>$warningCount</div>";
echo "<div class='text-muted'>Uyarı</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='stat-card'>";
echo "<div class='stat-value text-danger'>$criticalCount</div>";
echo "<div class='text-muted'>Kritik</div>";
echo "</div>";
echo "</div>";

echo "</div>";

// VULNERABILITY SUMMARY
if (count($securityIssues) > 0) {
    echo "<div class='mb-4'>";
    echo "<h4 class='text-danger'><i class='fas fa-bug me-2'></i>Güvenlik Açıkları ve Çözümler</h4>";
    
    foreach ($securityIssues as $issue) {
        $badgeClass = $issue['severity'] === 'critical' ? 'bg-danger' : 'bg-warning';
        echo "<div class='security-item security-" . ($issue['severity'] === 'critical' ? 'fail' : 'warning') . "'>";
        echo "<div class='d-flex justify-content-between align-items-start'>";
        echo "<div>";
        echo "<span class='badge $badgeClass vulnerability-badge me-2'>" . strtoupper($issue['severity']) . "</span>";
        echo "<strong>{$issue['file']}</strong><br>";
        echo "<small>Sorun: {$issue['issue']}</small><br>";
        echo "<small class='text-primary'><i class='fas fa-wrench me-1'></i>Çözüm: {$issue['fix']}</small>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
}

// FINAL RESULT
$totalChecks = $passCount + $warningCount + $criticalCount;
$securityScore = round(($passCount / $totalChecks) * 100, 1);

if ($criticalCount === 0 && $warningCount === 0) {
    echo "
    <div class='alert alert-success'>
        <h4 class='alert-heading'>
            <i class='fas fa-shield-check me-2'></i>
            Güvenlik Skoru: {$securityScore}% - Mükemmel!
        </h4>
        <p class='mb-0'>Tüm güvenlik kontrolleri başarılı. Sistem güvenli.</p>
    </div>
    ";
} elseif ($criticalCount === 0) {
    echo "
    <div class='alert alert-warning'>
        <h4 class='alert-heading'>
            <i class='fas fa-shield-alt me-2'></i>
            Güvenlik Skoru: {$securityScore}% - İyi
        </h4>
        <p class='mb-0'>Kritik sorun yok ancak bazı iyileştirmeler yapılabilir.</p>
    </div>
    ";
} else {
    echo "
    <div class='alert alert-danger'>
        <h4 class='alert-heading'>
            <i class='fas fa-shield-virus me-2'></i>
            Güvenlik Skoru: {$securityScore}% - Dikkat!
        </h4>
        <p class='mb-0'>$criticalCount kritik güvenlik sorunu tespit edildi. Acil müdahale gerekli!</p>
    </div>
    ";
}
?>

                <div class="d-grid gap-2 mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Ana Sayfaya Dön
                    </a>
                    <a href="security-dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-2"></i>
                        Taramayı Yenile
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-white">
            <p class="mb-0">
                <i class="fas fa-shield-alt me-1"></i>
                MR.ECU Tuning v2.0 - Security Dashboard
            </p>
            <p class="mb-0">
                <small>Son Tarama: <?php echo date('d.m.Y H:i:s'); ?></small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
