<?php
/**
 * Mr ECU - Admin Sistem Logları
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$filter = sanitize($_GET['filter'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');

try {
    // security_logs tablosu var mı kontrol et
    $table_check = $pdo->query("SHOW TABLES LIKE 'security_logs'");
    $security_table_exists = $table_check->fetch() ? true : false;
    
    if (!$security_table_exists) {
        // security_logs tablosu yoksa oluştur
        $create_table = "
            CREATE TABLE IF NOT EXISTS security_logs (
                id int(11) NOT NULL AUTO_INCREMENT,
                event_type varchar(100) NOT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_id int(11) DEFAULT NULL,
                details text DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_event_type (event_type),
                KEY idx_ip_address (ip_address),
                KEY idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        $pdo->exec($create_table);
        
        // Örnek log verileri ekle
        $sample_logs = [
            ['page_access', '192.168.1.100', 1, '{"page":"admin/index.php","method":"GET"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
            ['login_success', '192.168.1.100', 1, '{"username":"admin"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
            ['failed_login', '192.168.1.101', null, '{"username":"test","attempts":3}', 'Chrome/91.0'],
            ['file_upload', '192.168.1.100', 1, '{"filename":"test.ecu","size":1024}', 'Mozilla/5.0'],
            ['sql_injection_attempt', '10.0.0.50', null, '{"query":"blocked","severity":"high"}', 'BadBot/1.0']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, ip_address, user_id, details, user_agent) VALUES (?, ?, ?, ?, ?)");
        foreach ($sample_logs as $log) {
            $stmt->execute($log);
        }
    }
    
    // Filtreleme koşulları
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($filter !== 'all') {
        $whereClause .= " AND event_type = ?";
        $params[] = $filter;
    }
    
    if ($search) {
        $whereClause .= " AND (event_type LIKE ? OR ip_address LIKE ? OR details LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }
    
    // Toplam log sayısı
    $countQuery = "SELECT COUNT(*) FROM security_logs $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalLogs = $stmt->fetchColumn();
    
    // Logları getir
    $query = "
        SELECT sl.*, u.username 
        FROM security_logs sl
        LEFT JOIN users u ON sl.user_id = u.id
        $whereClause 
        ORDER BY sl.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $logs = $stmt->fetchAll();
    
    $totalPages = ceil($totalLogs / $limit);
    
    // Event türleri
    $eventTypesQuery = "SELECT DISTINCT event_type, COUNT(*) as count FROM security_logs GROUP BY event_type ORDER BY count DESC";
    $stmt = $pdo->query($eventTypesQuery);
    $eventTypes = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $logs = [];
    $totalLogs = 0;
    $totalPages = 0;
    $eventTypes = [];
}

$pageTitle = 'Sistem Logları';
$pageDescription = 'Sistem güvenlik loglarını görüntüleyin';
$pageIcon = 'bi bi-list';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Log İstatistikleri -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo $totalLogs; ?></div>
                    <div class="stat-label">Toplam Log</div>
                    <small class="text-muted">Tüm kayıtlar</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="bi bi-list text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <?php
                    $todayLogs = 0;
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at) = CURDATE()");
                        $todayLogs = $stmt->fetchColumn();
                    } catch(Exception $e) {}
                    ?>
                    <div class="stat-number text-success"><?php echo $todayLogs; ?></div>
                    <div class="stat-label">Bugünkü Loglar</div>
                    <small class="text-muted">Son 24 saat</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="bi bi-calendar-day text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <?php
                    $errorLogs = 0;
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM security_logs WHERE event_type LIKE '%error%' OR event_type LIKE '%failed%'");
                        $errorLogs = $stmt->fetchColumn();
                    } catch(Exception $e) {}
                    ?>
                    <div class="stat-number text-danger"><?php echo $errorLogs; ?></div>
                    <div class="stat-label">Hata Logları</div>
                    <small class="text-muted">Başarısız işlemler</small>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded">
                    <i class="bi bi-exclamation-triangle text-danger fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo count($eventTypes); ?></div>
                    <div class="stat-label">Event Türü</div>
                    <small class="text-muted">Farklı olay türleri</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="bi bi-tags text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtreleme -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="filter" class="form-label">
                    <i class="bi bi-filter me-1"></i>Event Türü
                </label>
                <select class="form-select" id="filter" name="filter">
                    <option value="all">Tüm Eventler</option>
                    <?php foreach ($eventTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['event_type']); ?>" 
                                <?php echo $filter === $type['event_type'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['event_type']); ?> (<?php echo $type['count']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <label for="search" class="form-label">
                    <i class="bi bi-search me-1"></i>Arama
                </label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="IP adresi, event türü veya detay ara...">
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
            </div>
            
            <div class="col-md-1">
                <a href="logs.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Log Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-list me-2"></i>
            Güvenlik Logları (<?php echo $totalLogs; ?> kayıt)
        </h5>
        
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i>Dışa Aktar
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportLogs('csv')">
                    <i class="bi bi-folder2-open-csv me-2"></i>CSV Olarak
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="exportLogs('txt')">
                    <i class="bi bi-folder2-open me-2"></i>TXT Olarak
                </a></li>
            </ul>
        </div>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-list fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($search || $filter !== 'all'): ?>
                        Filtreye uygun log bulunamadı
                    <?php else: ?>
                        Henüz log kaydı yok
                    <?php endif; ?>
                </h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tarih/Saat</th>
                            <th>Event Türü</th>
                            <th>IP Adresi</th>
                            <th>Kullanıcı</th>
                            <th>Detaylar</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo date('d.m.Y', strtotime($log['created_at'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = 'secondary';
                                    if (strpos($log['event_type'], 'failed') !== false || strpos($log['event_type'], 'error') !== false) {
                                        $badgeClass = 'danger';
                                    } elseif (strpos($log['event_type'], 'success') !== false || strpos($log['event_type'], 'login') !== false) {
                                        $badgeClass = 'success';
                                    } elseif (strpos($log['event_type'], 'warning') !== false || strpos($log['event_type'], 'attempt') !== false) {
                                        $badgeClass = 'warning';
                                    } elseif (strpos($log['event_type'], 'access') !== false) {
                                        $badgeClass = 'info';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($log['event_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                </td>
                                <td>
                                    <?php if ($log['username']): ?>
                                        <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">Anonim</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['details']): ?>
                                        <?php 
                                        $details = json_decode($log['details'], true);
                                        if ($details): ?>
                                            <button type="button" class="btn btn-outline-info btn-sm" 
                                                    onclick="showDetails('<?php echo htmlspecialchars(json_encode($details)); ?>')">
                                                <i class="bi bi-eye me-1"></i>Detay
                                            </button>
                                        <?php else: ?>
                                            <small class="text-muted"><?php echo mb_substr(htmlspecialchars($log['details']), 0, 50); ?>...</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                        <?php echo mb_substr(htmlspecialchars($log['user_agent']), 0, 30); ?>...
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Log sayfalama">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $filter !== 'all' ? '&filter=' . $filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $filter !== 'all' ? '&filter=' . $filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $filter !== 'all' ? '&filter=' . $filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> 
                            (Toplam <?php echo $totalLogs; ?> kayıt)
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Detay Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>Log Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="detailContent" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<?php
$pageJS = "
function showDetails(details) {
    try {
        const detailObj = JSON.parse(details);
        document.getElementById('detailContent').textContent = JSON.stringify(detailObj, null, 2);
    } catch(e) {
        document.getElementById('detailContent').textContent = details;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}

function exportLogs(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    window.open('export-logs.php?' + params.toString(), '_blank');
}

// Auto-refresh every 30 seconds
setInterval(function() {
    if (!document.hidden) {
        window.location.reload();
    }
}, 30000);
";

// Footer include
include '../includes/admin_footer.php';
?>
