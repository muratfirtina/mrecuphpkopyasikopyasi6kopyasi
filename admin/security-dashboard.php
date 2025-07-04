<?php
/**
 * Mr ECU - Güvenlik Dashboard
 * Security monitoring and management
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Güvenlik tabloları var mı kontrol et
$securityTablesExist = true;
$securityTables = ['security_logs', 'ip_security', 'failed_logins', 'csrf_tokens', 'rate_limits', 'security_config', 'file_security_scans', 'waf_rules'];

foreach ($securityTables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (Exception $e) {
        $securityTablesExist = false;
        break;
    }
}

$pageTitle = 'Güvenlik Dashboard';
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
        .security-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin: 10px 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .threat-level-low { border-left: 5px solid #28a745; }
        .threat-level-medium { border-left: 5px solid #ffc107; }
        .threat-level-high { border-left: 5px solid #fd7e14; }
        .threat-level-critical { border-left: 5px solid #dc3545; }
        .security-stat {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            text-align: center;
        }
        .threat-timeline {
            max-height: 400px;
            overflow-y: auto;
        }
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
                        <i class="fas fa-shield-alt me-2"></i>Güvenlik Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshDashboard()">
                                <i class="fas fa-sync me-1"></i>Yenile
                            </button>
                            <?php if (!$securityTablesExist): ?>
                                <a href="../fix-missing-tables.php" class="btn btn-sm btn-warning">
                                    <i class="fas fa-tools me-1"></i>Güvenlik Kurulumu
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!$securityTablesExist): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Güvenlik Sistemi Kurulum Gerekli</h5>
                        <p class="mb-2">Güvenlik tabloları henüz oluşturulmamış. Lütfen önce güvenlik sistemini kurun.</p>
                        <a href="../fix-missing-tables.php" class="btn btn-warning">
                            <i class="fas fa-tools me-2"></i>Güvenlik Sistemini Kur
                        </a>
                    </div>
                <?php else: ?>

                <!-- Güvenlik İstatistikleri -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="security-card">
                            <h3><i class="fas fa-chart-bar me-2"></i>Güvenlik İstatistikleri</h3>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="security-stat">
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                                            $dailyEvents = $stmt->fetch()['count'];
                                        } catch (Exception $e) {
                                            $dailyEvents = 0;
                                        }
                                        ?>
                                        <h4><?php echo $dailyEvents; ?></h4>
                                        <p class="mb-0">Son 24 Saat Olay</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="security-stat">
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM failed_logins WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                                            $failedLogins = $stmt->fetch()['count'];
                                        } catch (Exception $e) {
                                            $failedLogins = 0;
                                        }
                                        ?>
                                        <h4><?php echo $failedLogins; ?></h4>
                                        <p class="mb-0">Başarısız Giriş</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="security-stat">
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM ip_security WHERE type = 'blacklist'");
                                            $blockedIPs = $stmt->fetch()['count'];
                                        } catch (Exception $e) {
                                            $blockedIPs = 0;
                                        }
                                        ?>
                                        <h4><?php echo $blockedIPs; ?></h4>
                                        <p class="mb-0">Engellenen IP</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="security-stat">
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM waf_rules WHERE is_active = 1");
                                            $activeRules = $stmt->fetch()['count'];
                                        } catch (Exception $e) {
                                            $activeRules = 0;
                                        }
                                        ?>
                                        <h4><?php echo $activeRules; ?></h4>
                                        <p class="mb-0">Aktif WAF Kuralı</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Tehdit Seviyeleri -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Tehdit Seviyeleri
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                try {
                                    $stmt = $pdo->query("
                                        SELECT event_type, COUNT(*) as count 
                                        FROM security_logs 
                                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                        GROUP BY event_type 
                                        ORDER BY count DESC 
                                        LIMIT 10
                                    ");
                                    $threatTypes = $stmt->fetchAll();
                                    
                                    if (empty($threatTypes)) {
                                        echo "<p class='text-center text-muted'>Son 7 günde güvenlik olayı bulunmadı.</p>";
                                    } else {
                                        foreach ($threatTypes as $threat) {
                                            $level = 'low';
                                            if ($threat['count'] > 10) $level = 'medium';
                                            if ($threat['count'] > 50) $level = 'high';
                                            if ($threat['count'] > 100) $level = 'critical';
                                            
                                            echo "<div class='d-flex justify-content-between align-items-center p-2 mb-2 threat-level-$level rounded'>";
                                            echo "<span>" . htmlspecialchars($threat['event_type']) . "</span>";
                                            echo "<span class='badge bg-secondary'>" . $threat['count'] . "</span>";
                                            echo "</div>";
                                        }
                                    }
                                } catch (Exception $e) {
                                    echo "<p class='text-danger'>Veri yüklenemedi: " . $e->getMessage() . "</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Son Güvenlik Olayları -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>Son Güvenlik Olayları
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="threat-timeline">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT event_type, ip_address, created_at, details 
                                            FROM security_logs 
                                            ORDER BY created_at DESC 
                                            LIMIT 15
                                        ");
                                        $recentEvents = $stmt->fetchAll();
                                        
                                        if (empty($recentEvents)) {
                                            echo "<p class='text-center text-muted'>Henüz güvenlik olayı kaydedilmemiş.</p>";
                                        } else {
                                            foreach ($recentEvents as $event) {
                                                $details = json_decode($event['details'], true);
                                                $icon = 'fas fa-info-circle text-info';
                                                
                                                switch ($event['event_type']) {
                                                    case 'sql_injection_attempt':
                                                        $icon = 'fas fa-database text-danger';
                                                        break;
                                                    case 'xss_attempt':
                                                        $icon = 'fas fa-code text-warning';
                                                        break;
                                                    case 'brute_force_detected':
                                                        $icon = 'fas fa-user-slash text-danger';
                                                        break;
                                                    case 'failed_login':
                                                        $icon = 'fas fa-sign-in-alt text-warning';
                                                        break;
                                                }
                                                
                                                echo "<div class='d-flex align-items-start mb-3'>";
                                                echo "<div class='me-3'><i class='$icon'></i></div>";
                                                echo "<div class='flex-grow-1'>";
                                                echo "<div class='fw-bold'>" . htmlspecialchars($event['event_type']) . "</div>";
                                                echo "<small class='text-muted'>IP: " . htmlspecialchars($event['ip_address']) . "</small><br>";
                                                echo "<small class='text-muted'>" . date('d.m.Y H:i', strtotime($event['created_at'])) . "</small>";
                                                if ($details && isset($details['message'])) {
                                                    echo "<div class='small text-secondary mt-1'>" . htmlspecialchars($details['message']) . "</div>";
                                                }
                                                echo "</div>";
                                                echo "</div>";
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo "<p class='text-danger'>Veri yüklenemedi: " . $e->getMessage() . "</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Şüpheli IP'ler ve WAF Kuralları -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-ban me-2"></i>Engellenen IP Adresleri
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                try {
                                    $stmt = $pdo->query("
                                        SELECT ip_address, reason, created_at 
                                        FROM ip_security 
                                        WHERE type = 'blacklist' 
                                        ORDER BY created_at DESC 
                                        LIMIT 10
                                    ");
                                    $blockedIPs = $stmt->fetchAll();
                                    
                                    if (empty($blockedIPs)) {
                                        echo "<p class='text-center text-muted'>Engellenen IP bulunmadı.</p>";
                                    } else {
                                        echo "<div class='table-responsive'>";
                                        echo "<table class='table table-sm'>";
                                        echo "<thead><tr><th>IP Adresi</th><th>Sebep</th><th>Tarih</th></tr></thead>";
                                        echo "<tbody>";
                                        foreach ($blockedIPs as $ip) {
                                            echo "<tr>";
                                            echo "<td><code>" . htmlspecialchars($ip['ip_address']) . "</code></td>";
                                            echo "<td>" . htmlspecialchars($ip['reason']) . "</td>";
                                            echo "<td>" . date('d.m.Y', strtotime($ip['created_at'])) . "</td>";
                                            echo "</tr>";
                                        }
                                        echo "</tbody></table>";
                                        echo "</div>";
                                    }
                                } catch (Exception $e) {
                                    echo "<p class='text-danger'>Veri yüklenemedi: " . $e->getMessage() . "</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-shield me-2"></i>WAF Kuralları Durumu
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                try {
                                    $stmt = $pdo->query("
                                        SELECT rule_type, COUNT(*) as count, 
                                               SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                                        FROM waf_rules 
                                        GROUP BY rule_type
                                    ");
                                    $wafStats = $stmt->fetchAll();
                                    
                                    if (empty($wafStats)) {
                                        echo "<p class='text-center text-muted'>WAF kuralı bulunmadı.</p>";
                                    } else {
                                        foreach ($wafStats as $stat) {
                                            $percentage = $stat['count'] > 0 ? round(($stat['active_count'] / $stat['count']) * 100) : 0;
                                            
                                            echo "<div class='mb-3'>";
                                            echo "<div class='d-flex justify-content-between mb-1'>";
                                            echo "<span>" . htmlspecialchars($stat['rule_type']) . "</span>";
                                            echo "<span>{$stat['active_count']}/{$stat['count']}</span>";
                                            echo "</div>";
                                            echo "<div class='progress' style='height: 8px;'>";
                                            echo "<div class='progress-bar' role='progressbar' style='width: {$percentage}%'></div>";
                                            echo "</div>";
                                            echo "</div>";
                                        }
                                    }
                                } catch (Exception $e) {
                                    echo "<p class='text-danger'>Veri yüklenemedi: " . $e->getMessage() . "</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshDashboard() {
            window.location.reload();
        }
        
        // Auto refresh every 30 seconds
        setInterval(refreshDashboard, 30000);
    </script>
</body>
</html>
