<?php
/**
 * Mr ECU - Email Sistemi Kurulum Dashboard
 * Tüm hosting kurulum araçlarını tek yerden yönetme
 */

// Session ayarlarını düzelt
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.localhost');
ini_set('session.gc_maxlifetime', 7200);

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session debug
if (isset($_GET['session_debug'])) {
    echo "<h3>Session Debug:</h3>";
    echo "<pre>";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Status: " . session_status() . "\n";
    echo "Session Save Path: " . session_save_path() . "\n";
    echo "Cookie Params: ";
    print_r(session_get_cookie_params());
    echo "\nSession Data:\n";
    print_r($_SESSION);
    echo "</pre>";
    echo "<a href='?'>Geri dön</a>";
    exit;
}

// Debug için session bilgilerini kontrol et
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Session Debug:\n";
    echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'YOK') . "\n";
    echo "role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'YOK') . "\n";
    echo "All session data:\n";
    print_r($_SESSION);
    echo "</pre>";
    exit;
}

// Session kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Session yeniden başlat
    session_regenerate_id(true);
    
    // Session bilgilerini göster
    echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; margin: 20px;'>";
    echo "<h3>Session Debug Bilgileri:</h3>";
    echo "<p><strong>user_id:</strong> " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'YOK') . "</p>";
    echo "<p><strong>role:</strong> " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'YOK') . "</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Çözüm:</strong></p>";
    echo "<ul>";
    echo "<li><a href='../login.php'>Yeniden giriş yapın</a></li>";
    echo "<li><a href='index.php'>Admin Dashboard'a gidin</a></li>";
    echo "<li><a href='email-dashboard-mamp.php'>MAMP Email Dashboard'a gidin (session kontrolü yok)</a></li>";
    echo "<li><a href='?session_debug=1'>Detaylı session debug bilgileri</a></li>";
    echo "</ul>";
    echo "</div>";
    die('Bu sayfaya erişim yetkiniz yok. Lütfen admin olarak giriş yapın.');
}

require_once '../config/config.php';
require_once '../config/database.php';

// Sistem durumu kontrolü
$systemStatus = [
    'database' => false,
    'email_manager' => false,
    'email_tables' => false,
    'templates' => false,
    'smtp_config' => false
];

try {
    // Database kontrolü
    $systemStatus['database'] = $pdo instanceof PDO;
    
    // EmailManager kontrolü
    $systemStatus['email_manager'] = file_exists('../includes/EmailManager.php');
    
    // Email tabloları kontrolü
    if ($systemStatus['database']) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'email_queue'");
        $systemStatus['email_tables'] = $stmt->rowCount() > 0;
    }
    
    // Template kontrolü
    $systemStatus['templates'] = file_exists('../email_templates/verification.html');
    
    // SMTP config kontrolü
    $systemStatus['smtp_config'] = !empty(getenv('SMTP_HOST')) && !empty(getenv('SMTP_USERNAME'));
    
} catch (Exception $e) {
    // Hata durumunda false kalsın
}

$overallStatus = array_sum($systemStatus) / count($systemStatus) * 100;

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Sistemi Dashboard - Mr ECU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .dashboard-container { max-width: 1200px; margin: 2rem auto; }
        .dashboard-header { 
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%); 
            color: white; 
            padding: 2rem; 
            border-radius: 12px; 
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-card { 
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }
        .status-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-ok { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-error { background-color: #dc3545; }
        .progress-ring { 
            width: 120px; 
            height: 120px; 
            margin: 0 auto;
        }
        .tool-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .tool-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .btn-tool {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-tool:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container dashboard-container">
        <!-- Header -->
        <div class="dashboard-header text-center">
            <h1><i class="bi bi-envelope-gear"></i> Email Sistemi Kurulum Dashboard</h1>
            <p class="mb-3">Mr ECU Email Sistemi - Web Hosting Uyumlu Kurulum Merkezi</p>
            
            <!-- Sistem Durumu -->
            <div class="row mt-4">
                <div class="col-md-6 offset-md-3">
                    <div class="card bg-white bg-opacity-10 border-0">
                        <div class="card-body text-center">
                            <h5>Sistem Durumu</h5>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-light" role="progressbar" style="width: <?php echo $overallStatus; ?>%"></div>
                            </div>
                            <small class="text-light"><?php echo round($overallStatus); ?>% Tamamlandı</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sistem Durumu Kartları -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card status-card">
                    <div class="card-header">
                        <h5><i class="bi bi-activity"></i> Sistem Bileşenleri Durumu</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <span class="status-indicator <?php echo $systemStatus['database'] ? 'status-ok' : 'status-error'; ?>"></span>
                                    <br><small>Veritabanı</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <span class="status-indicator <?php echo $systemStatus['email_manager'] ? 'status-ok' : 'status-error'; ?>"></span>
                                    <br><small>Email Manager</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <span class="status-indicator <?php echo $systemStatus['email_tables'] ? 'status-ok' : 'status-warning'; ?>"></span>
                                    <br><small>Email Tabloları</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <span class="status-indicator <?php echo $systemStatus['templates'] ? 'status-ok' : 'status-warning'; ?>"></span>
                                    <br><small>Templates</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <span class="status-indicator <?php echo $systemStatus['smtp_config'] ? 'status-ok' : 'status-warning'; ?>"></span>
                                    <br><small>SMTP Ayarları</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <span class="status-indicator status-ok"></span>
                                    <br><small>Web Araçları</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kurulum Araçları -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card tool-card h-100">
                    <div class="card-body text-center">
                        <div class="tool-icon text-primary">
                            <i class="bi bi-tools"></i>
                        </div>
                        <h5>Adım Adım Kurulum</h5>
                        <p class="text-muted">4 adımlı kolay kurulum sihirbazı ile email sistemini kurun</p>
                        <a href="../email-setup.php" class="btn btn-primary btn-tool">
                            <i class="bi bi-arrow-right"></i> Kuruluma Başla
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card tool-card h-100">
                    <div class="card-body text-center">
                        <div class="tool-icon text-success">
                            <i class="bi bi-bug"></i>
                        </div>
                        <h5>Sistem Testi</h5>
                        <p class="text-muted">Email sisteminin tüm bileşenlerini kapsamlı olarak test edin</p>
                        <a href="../email-system-test.php" class="btn btn-success btn-tool">
                            <i class="bi bi-play-circle"></i> Testleri Çalıştır
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card tool-card h-100">
                    <div class="card-body text-center">
                        <div class="tool-icon text-info">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h5>Cron Job Rehberi</h5>
                        <p class="text-muted">Email queue için cron job kurulum URL'leri ve rehberi</p>
                        <a href="../cron-helper.php" class="btn btn-info btn-tool">
                            <i class="bi bi-link-45deg"></i> Cron Rehberi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- İşletim Araçları -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card tool-card h-100">
                    <div class="card-body text-center">
                        <div class="tool-icon text-warning">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <h5>Email Test</h5>
                        <p class="text-muted">SMTP ayarlarını test edin</p>
                        <a href="../email-test.php" class="btn btn-warning btn-tool">
                            <i class="bi bi-send"></i> Test Et
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card tool-card h-100">
                    <div class="card-body text-center">
                        <div class="tool-icon text-secondary">
                            <i class="bi bi-play"></i>
                        </div>
                        <h5>Queue İşle</h5>
                        <p class="text-muted">Email kuyruğunu manuel çalıştır</p>
                        <a href="../cron-web.php?key=<?php echo md5('mrecu_email_cron_2024'); ?>" target="_blank" class="btn btn-secondary btn-tool">
                            <i class="bi bi-play-circle"></i> Çalıştır
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card tool-card h-100">
                    <div class="card-body text-center">
                        <div class="tool-icon text-dark">
                            <i class="bi bi-gear"></i>
                        </div>
                        <h5>Email Ayarları</h5>
                        <p class="text-muted">Email sistem ayarları</p>
                        <a href="admin/email-settings.php" class="btn btn-dark btn-tool">
                            <i class="bi bi-gear"></i> Ayarlar
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card tool-card h-100">
                    <div class="card-body text-center">
                        <div class="tool-icon text-primary">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h5>Analytics</h5>
                        <p class="text-muted">Email istatistikleri</p>
                        <a href="admin/email-analytics.php" class="btn btn-primary btn-tool">
                            <i class="bi bi-graph-up"></i> İstatistik
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hızlı Başlangıç -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning"></i> Hızlı Başlangıç Kılavuzu</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>🚀 İlk Kurulum İçin:</h6>
                                <ol>
                                    <li><strong>Adım Adım Kurulum</strong> butonuna tıklayın</li>
                                    <li>4 adımı sırasıyla tamamlayın</li>
                                    <li>Email testini yapın</li>
                                    <li>Cron job kurun</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6>🔧 Sorun Giderme İçin:</h6>
                                <ol>
                                    <li><strong>Sistem Testi</strong> ile sorunları teşhis edin</li>
                                    <li>Email Test ile SMTP'yi kontrol edin</li>
                                    <li>Cron Rehberi ile queue kurulumunu yapın</li>
                                    <li>Analytics ile performansı izleyin</li>
                                </ol>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Hosting Özel Notlar:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Tüm araçlar web arayüzlü olarak tasarlanmıştır</li>
                                <li>CLI erişimi gerektirmez</li>
                                <li>Shared hosting ortamları için optimize edilmiştir</li>
                                <li>Cron job alternatifleri mevcuttur</li>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="../HOSTING_KURULUM_KILAVUZU.md" target="_blank" class="btn btn-outline-primary">
                                <i class="bi bi-book"></i> Detaylı Hosting Kurulum Kılavuzu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Duruma göre renk değişimi
        document.addEventListener('DOMContentLoaded', function() {
            const overallStatus = <?php echo $overallStatus; ?>;
            const progressBar = document.querySelector('.progress-bar');
            
            if (overallStatus >= 80) {
                progressBar.classList.add('bg-success');
            } else if (overallStatus >= 60) {
                progressBar.classList.add('bg-warning');
            } else {
                progressBar.classList.add('bg-danger');
            }
        });
    </script>
</body>
</html>
