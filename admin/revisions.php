<?php
/**
 * Mr ECU - Admin Revize Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
$fileManager = new FileManager($pdo);
$user = new User($pdo);
$error = '';
$success = '';

// Direkt revize onaylama (yanıt dosyası revizeleri için)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_revision_direct'])) {
    $revisionId = sanitize($_POST['revision_id']);
    
    if (!isValidUUID($revisionId)) {
        $error = 'Geçersiz revize ID formatı.';
    } else {
        // Revize talebini getir
        $stmt = $pdo->prepare("SELECT * FROM revisions WHERE id = ?");
        $stmt->execute([$revisionId]);
        $revision = $stmt->fetch();
        
        if (!$revision) {
            $error = 'Revize talebi bulunamadı.';
        } else {
            // FileManager metodu ile revize talebini güncelle (kredi düşürmeden)
            $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'in_progress', 'Revize talebi işleme alındı.', 0);
            
            if ($result['success']) {
                if ($revision['response_id']) {
                    $success = 'Yanıt dosyası revize talebi işleme alındı. Dosya detay sayfasında revize edilmiş yanıt dosyasını yükleyebilirsiniz.';
                } else {
                    $success = 'Dosya revize talebi işleme alındı. Dosya detay sayfasında revize edilmiş dosyayı yükleyebilirsiniz.';
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Revize talebini reddet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_revision_direct'])) {
    $revisionId = sanitize($_POST['revision_id']);
    $adminNotes = sanitize($_POST['admin_notes']) ?: 'Revize talebi reddedildi.';
    
    if (!isValidUUID($revisionId)) {
        $error = 'Geçersiz revize ID formatı.';
    } else {
        $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'rejected', $adminNotes, 0);
        
        if ($result['success']) {
            $success = 'Revize talebi reddedildi.';
        } else {
            $error = $result['message'];
        }
    }
}

// Revize talebini işle (modal ile - sadece özel durumlar için)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_revision'])) {
    $revisionId = sanitize($_POST['revision_id']);
    $status = sanitize($_POST['status']);
    $adminNotes = sanitize($_POST['admin_notes']);
    $creditsCharged = (float)$_POST['credits_charged'];
    
    if (!isValidUUID($revisionId)) {
        $error = 'Geçersiz revize ID formatı.';
    } elseif (!in_array($status, ['in_progress', 'rejected'])) {
        $error = 'Geçersiz durum seçimi.';
    } elseif ($creditsCharged < 0) {
        $error = 'Kredi miktarı negatif olamaz.';
    } else {
        // Revize talebini getir
        $stmt = $pdo->prepare("SELECT * FROM revisions WHERE id = ?");
        $stmt->execute([$revisionId]);
        $revision = $stmt->fetch();
        
        if (!$revision) {
            $error = 'Revize talebi bulunamadı.';
        } else {
            // Response dosyası revize talebi mi kontrol et
            if ($revision['response_id']) {
                // Response dosyası revize talebi
                if ($status === 'in_progress') {
                    $success = 'Yanıt dosyası revize talebi işleme alındı. Dosya detay sayfasına giderek revize dosyasını yükleyebilirsiniz.';
                } else {
                    $success = 'Yanıt dosyası revize talebi reddedildi.';
                }
            } else {
                // Normal upload dosyası revize talebi
                if ($status === 'in_progress') {
                    $success = 'Dosya revize talebi işleme alındı. Dosya detay sayfasına giderek revize dosyasını yükleyebilirsiniz.';
                } else {
                    $success = 'Dosya revize talebi reddedildi.';
                }
            }
            
            // FileManager metodu ile revize talebini güncelle
            $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], $status, $adminNotes, $creditsCharged);
            
            if (!$result['success']) {
                $error = $result['message'];
                $success = ''; // Success mesajını temizle
            }
        }
    }
}

// Filtreleme ve arama parametreleri
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;

// Revize taleplerini getir (FileManager metodu ile)
$revisions = $fileManager->getAllRevisions($page, $limit, $status, $dateFrom, $dateTo);

// Toplam revize sayısı için ayrı sorgu
try {
    $countQuery = "SELECT COUNT(*) FROM revisions r";
    $countParams = [];
    
    if ($status) {
        $countQuery .= " WHERE r.status = ?";
        $countParams[] = $status;
    }
    
    if ($dateFrom) {
        $countQuery .= ($status ? " AND" : " WHERE") . " DATE(r.requested_at) >= ?";
        $countParams[] = $dateFrom;
    }
    
    if ($dateTo) {
        $operator = ($status || $dateFrom) ? " AND" : " WHERE";
        $countQuery .= $operator . " DATE(r.requested_at) <= ?";
        $countParams[] = $dateTo;
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRevisions = $stmt->fetchColumn();
    $totalPages = ceil($totalRevisions / $limit);
    
} catch(PDOException $e) {
    $totalRevisions = count($revisions);
    $totalPages = 1;
}

// İstatistikler
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_revisions,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as processing_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN requested_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as today_requests
        FROM revisions
    ");
    $stats = $stmt->fetch();
} catch(PDOException $e) {
    $stats = [
        'total_revisions' => 0,
        'pending_count' => 0, 
        'processing_count' => 0,
        'completed_count' => 0,
        'rejected_count' => 0,
        'today_requests' => 0
    ];
}

// Seçili revize detayı
$selectedRevision = null;
if (isset($_GET['detail_id'])) {
    $revisionId = sanitize($_GET['detail_id']);
    
    if (isValidUUID($revisionId)) {
        foreach ($revisions as $revision) {
            if ($revision['id'] === $revisionId) {
                $selectedRevision = $revision;
                break;
            }
        }
    }
}

$pageTitle = 'Revize Yönetimi';
$pageDescription = 'Kullanıcı revize taleplerini yönetin';
$pageIcon = 'fas fa-edit';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format($stats['total_revisions']); ?></div>
                    <div class="stat-label">Toplam Revize</div>
                    <small class="text-success">+<?php echo $stats['today_requests']; ?> bugün</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-edit text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo number_format($stats['pending_count']); ?></div>
                    <div class="stat-label">Bekleyen</div>
                    <small class="text-muted">İşlem bekliyor</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="fas fa-clock text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo number_format($stats['processing_count']); ?></div>
                    <div class="stat-label">İşleniyor</div>
                    <small class="text-muted">Aktif işlemde</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="fas fa-cogs text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format($stats['completed_count']); ?></div>
                    <div class="stat-label">Tamamlanan</div>
                    <small class="text-danger"><?php echo number_format($stats['rejected_count']); ?> reddedilen</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="fas fa-check-circle text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtre -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label">Durum</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Bekleyen</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>İşleniyor</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Tamamlanan</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">Başlangıç</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">Bitiş</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filtrele
                    </button>
                    <a href="revisions.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i>Temizle
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Revize Listesi -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Revize Talepleri (<?php echo $totalRevisions; ?> adet)
        </h5>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($revisions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($status || $dateFrom || $dateTo): ?>
                        Filtreye uygun revize talebi bulunamadı
                    <?php else: ?>
                        Henüz revize talebi yok
                    <?php endif; ?>
                </h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kullanıcı</th>
                            <th>Dosya</th>
                            <th>Talep Notları</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisions as $revision): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name']); ?></strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($revision['username']); ?></small><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($revision['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($revision['response_id']): ?>
                                            <strong><?php echo htmlspecialchars($revision['response_original_name'] ?? 'Yanıt dosyası'); ?></strong><br>
                                            <small class="text-muted">Yanıt: <?php echo htmlspecialchars($revision['original_name'] ?? 'Bilinmiyor'); ?></small><br>
                                        <?php else: ?>
                                            <strong><?php echo htmlspecialchars($revision['original_name'] ?? 'Dosya adı yok'); ?></strong><br>
                                        <?php endif; ?>
                                        <?php if (!empty($revision['brand_name']) || !empty($revision['model_name'])): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($revision['brand_name'] . ' ' . $revision['model_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 200px;">
                                        <?php echo htmlspecialchars(substr($revision['request_notes'], 0, 100)); ?>
                                        <?php if (strlen($revision['request_notes']) > 100): ?>...<?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $statusText = [
                                        'pending' => 'Bekliyor',
                                        'in_progress' => 'İşleniyor',
                                        'completed' => 'Tamamlandı',
                                        'rejected' => 'Reddedildi'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$revision['status']] ?? 'secondary'; ?>">
                                        <?php echo $statusText[$revision['status']] ?? 'Bilinmiyor'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo date('d.m.Y', strtotime($revision['requested_at'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($revision['requested_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <?php if ($revision['status'] === 'pending'): ?>
                                            <?php if ($revision['response_id']): ?>
                                                <!-- Yanıt dosyası revize talebi - Direkt butonlar -->
                                                <form method="POST" style="display: inline-block; width: 100%;">
                                                    <input type="hidden" name="revision_id" value="<?php echo $revision['id']; ?>">
                                                    <button type="submit" name="approve_revision_direct" class="btn btn-outline-success btn-sm" 
                                                            onclick="return confirm('Yanıt dosyası revize talebini işleme almak istediğinizden emin misiniz?')">
                                                        <i class="fas fa-check me-1"></i>Onayla
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-outline-danger btn-sm mt-1" 
                                                        onclick="showRejectModal('<?php echo $revision['id']; ?>')">
                                                    <i class="fas fa-times me-1"></i>Reddet
                                                </button>
                                            <?php else: ?>
                                                <!-- Normal dosya revize talebi - Modal butonlar -->
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="processRevision('<?php echo $revision['id']; ?>', 'in_progress')">
                                                    <i class="fas fa-check me-1"></i>Onayla
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="processRevision('<?php echo $revision['id']; ?>', 'rejected')">
                                                    <i class="fas fa-times me-1"></i>Reddet
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">İşlenmiş</span>
                                        <?php endif; ?>
                                        
                                        <!-- Dosyayı Gör Butonu -->
                                        <?php if ($revision['response_id']): ?>
                                            <!-- Yanıt dosyası revize talebi -->
                                            <a href="file-detail.php?id=<?php echo $revision['upload_id']; ?>&type=response" 
                                               class="btn btn-outline-info btn-sm mt-1">
                                                <i class="fas fa-eye me-1"></i>Yanıt Dosyasını Gör
                                            </a>
                                        <?php else: ?>
                                            <!-- Normal upload dosyası revize talebi -->
                                            <a href="file-detail.php?id=<?php echo $revision['upload_id']; ?>" 
                                               class="btn btn-outline-info btn-sm mt-1">
                                                <i class="fas fa-eye me-1"></i>Dosyayı Gör
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Sayfalama -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Sayfalama">
                        <ul class="pagination justify-content-center mb-0">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Revize İşleme Modal -->
<div class="modal fade" id="processRevisionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Revize Talebini İşle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="revision_id" id="modal_revision_id">
                    <input type="hidden" name="status" id="modal_status">
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Reddetme Sebebi</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Revize talebinin neden reddedildiğini açıklayın..."></textarea>
                    </div>
                    
                    <div class="mb-3" id="credits_section" style="display: none;">
                        <label for="credits_charged" class="form-label">Düşürülecek Kredi</label>
                        <input type="number" class="form-control" id="credits_charged" name="credits_charged" 
                               value="0" min="0" step="0.01">
                        <div class="form-text">Revize için kullanıcıdan düşürülecek kredi miktarı</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="process_revision" class="btn btn-danger" id="modal_submit_btn">Reddet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Yanıt dosyası revize talebi reddetme
function showRejectModal(revisionId) {
    const reason = prompt('Revize talebini reddetme sebebini belirtin:');
    if (reason !== null && reason.trim() !== '') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = \`
            <input type=\\\"hidden\\\" name=\\\"revision_id\\\" value=\\\"\${revisionId}\\\">
            <input type=\\\"hidden\\\" name=\\\"admin_notes\\\" value=\\\"\${reason}\\\">
            <input type=\\\"hidden\\\" name=\\\"reject_revision_direct\\\" value=\\\"1\\\">
        \`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Normal dosya revizeleri için modal
function processRevision(revisionId, status) {
    document.getElementById('modal_revision_id').value = revisionId;
    document.getElementById('modal_status').value = status;
    
    const modalTitle = status === 'in_progress' ? 'Revize Talebini İşleme Al' : 'Revize Talebini Reddet';
    document.querySelector('#processRevisionModal .modal-title').textContent = modalTitle;
    
    new bootstrap.Modal(document.getElementById('processRevisionModal')).show();
}
";

// Footer include
include '../includes/admin_footer.php';
?>
