<?php
/**
 * Mr ECU - Güvenlik Dashboard
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
$pageDescription = 'Sistem güvenliğini izleyin ve yönetin';
$pageIcon = 'fas fa-shield-alt';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<?php if (!$securityTablesExist): ?>
    <div class="alert alert-admin alert-warning">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Güvenlik Sistemi Kurulum Gerekli</h5>
        <p class="mb-2">Güvenlik tabloları henüz oluşturulmamış. Lütfen önce güvenlik sistemini kurun.</p>
        <a href="../fix-missing-tables.php" class="btn btn-warning">
            <i class="fas fa-tools me-2"></i>Güvenlik Sistemini Kur
        </a>
    </div>
<?php else: ?>

<!-- Güvenlik İstatistikleri -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                        $dailyEvents = $stmt->fetch()['count'];
                    } catch (Exception $e) {
                        $dailyEvents = 0;
                    }
                    ?>
                    <div class="stat-number text-primary"><?php echo $dailyEvents; ?></div>
                    <div class="stat-label">Son 24 Saat Olay</div>
                    <small class="text-muted">Güvenlik olayları</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-chart-bar text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM failed_logins WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                        $failedLogins = $stmt->fetch()['count'];
                    } catch (Exception $e) {
                        $failedLogins = 0;
                    }
                    ?>
                    <div class="stat-number text-warning"><?php echo $failedLogins; ?></div>
                    <div class="stat-label">Başarısız Giriş</div>
                    <small class="text-muted">Son 24 saatte</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="fas fa-user-slash text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ip_security WHERE type = 'blacklist'");
                        $blockedIPs = $stmt->fetch()['count'];
                    } catch (Exception $e) {
                        $blockedIPs = 0;
                    }
                    ?>
                    <div class="stat-number text-danger"><?php echo $blockedIPs; ?></div>
                    <div class="stat-label">Engellenen IP</div>
                    <small class="text-muted">Blacklist'te</small>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded">
                    <i class="fas fa-ban text-danger fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM waf_rules WHERE is_active = 1");
                        $activeRules = $stmt->fetch()['count'];
                    } catch (Exception $e) {
                        $activeRules = 0;
                    }
                    ?>
                    <div class="stat-number text-success"><?php echo $activeRules; ?></div>
                    <div class="stat-label">Aktif WAF Kuralı</div>
                    <small class="text-muted">Koruma aktif</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="fas fa-shield text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tehdit Seviyeleri -->
    <div class="col-md-6 mb-4">
        <div class="card admin-card">
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
                            $level = 'success';
                            if ($threat['count'] > 10) $level = 'warning';
                            if ($threat['count'] > 50) $level = 'orange';
                            if ($threat['count'] > 100) $level = 'danger';
                            
                            echo "<div class='d-flex justify-content-between align-items-center p-2 mb-2 border-start border-$level border-4 bg-light rounded'>";
                            echo "<span>" . htmlspecialchars($threat['event_type']) . "</span>";
                            echo "<span class='badge bg-$level'>" . $threat['count'] . "</span>";
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
    <div class="col-md-6 mb-4">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Son Güvenlik Olayları
                </h5>
            </div>
            <div class="card-body">
                <div style="max-height: 400px; overflow-y: auto;">
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
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card admin-card">
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
                        echo "<table class='table table-admin table-sm'>";
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

    <div class="col-md-6 mb-4">
        <div class="card admin-card">
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

<?php
$pageJS = "
    // Auto refresh every 30 seconds
    setInterval(function() {
        window.location.reload();
    }, 30000);
";

// Footer include
include '../includes/admin_footer.php';
?>
