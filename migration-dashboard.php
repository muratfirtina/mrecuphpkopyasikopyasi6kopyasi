<?php
/**
 * MR.ECU Legacy Migration - Main Dashboard
 * Migration sürecinin ana kontrol paneli
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Database bağlantısı
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// İstatistikleri al
$stats = [];
if (!isset($dbError)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
        $stats['total_users'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stats['total_admins'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads");
        $stats['total_files'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(credits) FROM users");
        $stats['total_credits'] = $stmt->fetchColumn() ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM brands");
        $stats['total_brands'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM models");
        $stats['total_models'] = $stmt->fetchColumn();
        
        // Sistem durumunu kontrol et
        $systemStatus = checkSystemStatus($pdo);
        
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

function checkSystemStatus($pdo) {
    $status = ['healthy' => true, 'issues' => []];
    
    try {
        // Temel tabloları kontrol et
        $requiredTables = ['users', 'brands', 'models', 'file_uploads', 'credit_transactions'];
        
        foreach ($requiredTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                $status['healthy'] = false;
                $status['issues'][] = "Tablo eksik: $table";
            }
        }
        
        // Users tablosunda admin var mı kontrol et
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ($stmt->fetchColumn() === 0) {
            $status['healthy'] = false;
            $status['issues'][] = "Hiç admin kullanıcısı yok";
        }
        
        // Legacy tablolar var mı kontrol et
        $legacyTables = ['legacy_tickets', 'legacy_ticket_admin', 'legacy_ticket_user'];
        $legacyExists = false;
        
        foreach ($legacyTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $legacyExists = true;
                break;
            }
        }
        
        $status['legacy_ready'] = $legacyExists;
        
    } catch (PDOException $e) {
        $status['healthy'] = false;
        $status['issues'][] = "Database kontrol hatası: " . $e->getMessage();
    }
    
    return $status;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MR.ECU Legacy Migration Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            padding: 30px 0;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #071e3d, #d32835);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .stats-card p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
        }
        
        .status-card {
            border-left: 5px solid;
            padding: 20px;
            margin: 15px 0;
        }
        
        .status-card.healthy {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .status-card.warning {
            border-color: #ffc107;
            background: #fff3cd;
        }
        
        .status-card.error {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .tool-card {
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .tool-card:hover {
            border-color: #007bff;
            transform: translateY(-3px);
        }
        
        .tool-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #007bff;
        }
        
        .btn-tool {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 10px;
            margin: 10px 0;
            transition: all 0.3s ease;
        }
        
        .btn-tool:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .progress-ring {
            transform: rotate(-90deg);
            width: 120px;
            height: 120px;
        }
        
        .progress-ring-bg {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 8;
        }
        
        .progress-ring-progress {
            fill: none;
            stroke: #007bff;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dasharray 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h1 class="mb-0">
                            <i class="bi bi-exchange-alt"></i> 
                            MR.ECU Legacy Migration Dashboard
                        </h1>
                        <p class="mb-0 mt-2">SQL Server'dan MySQL GUID sistemine veri geçiş merkezi</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if (isset($dbError)): ?>
                            <div class="status-card error">
                                <h5><i class="bi bi-exclamation-triangle"></i> Database Bağlantı Hatası</h5>
                                <p><?= htmlspecialchars($dbError) ?></p>
                                <a href="config/install-guid.php" class="btn btn-danger">Database'i Kur</a>
                            </div>
                        <?php else: ?>
                            <!-- Sistem Durumu -->
                            <?php if ($systemStatus['healthy']): ?>
                                <div class="status-card healthy">
                                    <h5><i class="bi bi-check-circle"></i> Sistem Sağlıklı</h5>
                                    <p>GUID MySQL veritabanı hazır. Migration işlemleri başlatılabilir.</p>
                                    <?php if ($systemStatus['legacy_ready']): ?>
                                        <small class="text-success"><i class="bi bi-info-circle"></i> Legacy tablolar mevcut</small>
                                    <?php else: ?>
                                        <small class="text-warning"><i class="bi bi-info-circle"></i> Legacy tablolar henüz oluşturulmamış</small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="status-card error">
                                    <h5><i class="bi bi-exclamation-triangle"></i> Sistem Sorunları</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($systemStatus['issues'] as $issue): ?>
                                            <li><?= htmlspecialchars($issue) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <a href="config/install-guid.php" class="btn btn-warning mt-2">Sistem Kur</a>
                                </div>
                            <?php endif; ?>
                            
                            <!-- İstatistikler -->
                            <div class="row mb-4">
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <h3><?= $stats['total_users'] ?? 0 ?></h3>
                                        <p><i class="bi bi-person"></i> Kullanıcılar</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <h3><?= $stats['total_admins'] ?? 0 ?></h3>
                                        <p><i class="bi bi-user-shield"></i> Adminler</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <h3><?= $stats['total_files'] ?? 0 ?></h3>
                                        <p><i class="bi bi-folder2-open"></i> Dosyalar</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <h3><?= number_format($stats['total_credits'] ?? 0, 0) ?></h3>
                                        <p><i class="bi bi-coin"></i> Krediler</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <h3><?= $stats['total_brands'] ?? 0 ?></h3>
                                        <p><i class="bi bi-car"></i> Markalar</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <h3><?= $stats['total_models'] ?? 0 ?></h3>
                                        <p><i class="bi bi-gear-wide-connected"></i> Modeller</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Migration Araçları -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card tool-card">
                                    <div class="card-body text-center">
                                        <div class="tool-icon">
                                            <i class="bi bi-code"></i>
                                        </div>
                                        <h5>1. Data Converter</h5>
                                        <p>SQL Server verilerini MySQL formatına dönüştürme kodları</p>
                                        <a href="legacy-data-converter.php" class="btn btn-info btn-tool">
                                            <i class="bi bi-exchange-alt"></i> Converter'a Git
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card tool-card">
                                    <div class="card-body text-center">
                                        <div class="tool-icon">
                                            <i class="bi bi-database"></i>
                                        </div>
                                        <h5>2. Sample Data</h5>
                                        <p>Test için örnek legacy verileri oluştur</p>
                                        <a href="sample-data-generator.php" class="btn btn-warning btn-tool">
                                            <i class="bi bi-plus"></i> Sample Data Oluştur
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card tool-card">
                                    <div class="card-body text-center">
                                        <div class="tool-icon">
                                            <i class="bi bi-upload"></i>
                                        </div>
                                        <h5>3. Migration Interface</h5>
                                        <p>CSV dosyalarını MySQL'e import etme arayüzü</p>
                                        <a href="legacy-migration-interface.php" class="btn btn-success btn-tool">
                                            <i class="bi bi-play"></i> Migration Başlat
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sistem Testleri -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="bi bi-check-circle"></i> Sistem Testleri</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>GUID Sistem Testleri</h6>
                                        <p>GUID sisteminin çalışıp çalışmadığını kontrol edin.</p>
                                        <a href="final-guid-migration-complete.php" class="btn btn-primary">
                                            <i class="bi bi-check"></i> GUID Test
                                        </a>
                                        <a href="test-guid-system.php" class="btn btn-secondary">
                                            <i class="bi bi-cog"></i> Basic Test
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Uygulama Testleri</h6>
                                        <p>Ana uygulama ve admin panelini test edin.</p>
                                        <a href="index.php" class="btn btn-info">
                                            <i class="bi bi-home"></i> Ana Sayfa
                                        </a>
                                        <a href="admin/" class="btn btn-warning">
                                            <i class="bi bi-user-shield"></i> Admin Panel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Migration Süreci -->
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5><i class="bi bi-list-ol"></i> Migration Süreci</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-database fa-3x text-primary"></i>
                                            </div>
                                            <h6>1. Hazırlık</h6>
                                            <p>SQL Server'dan verileri export edin</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-exchange-alt fa-3x text-info"></i>
                                            </div>
                                            <h6>2. Dönüştürme</h6>
                                            <p>Data Converter ile formatları uyarlayın</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-upload fa-3x text-success"></i>
                                            </div>
                                            <h6>3. Import</h6>
                                            <p>Migration Interface ile verileri aktarın</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-check-circle fa-3x text-warning"></i>
                                            </div>
                                            <h6>4. Test</h6>
                                            <p>Sistem testleri ile doğrulayın</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Otomatik sayfa yenileme (istatistikler için)
        setInterval(function() {
            // Sadece istatistik verilerini güncelle
            fetch('ajax-migration-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // İstatistikleri güncelle (sayfa yenilemeden)
                    const stats = data.data;
                    document.querySelectorAll('.stats-card h3')[0].textContent = stats.total_users || 0;
                    document.querySelectorAll('.stats-card h3')[1].textContent = stats.total_admins || 0;
                    document.querySelectorAll('.stats-card h3')[2].textContent = stats.total_files || 0;
                    document.querySelectorAll('.stats-card h3')[3].textContent = (stats.total_credits || 0).toLocaleString();
                    document.querySelectorAll('.stats-card h3')[4].textContent = stats.total_brands || 0;
                    document.querySelectorAll('.stats-card h3')[5].textContent = stats.total_models || 0;
                }
            })
            .catch(error => {
                console.log('İstatistik güncelleme hatası:', error);
            });
        }, 30000); // 30 saniyede bir güncelle
        
        // Tool card hover efektleri
        document.querySelectorAll('.tool-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 15px 35px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
            });
        });
    </script>
</body>
</html>