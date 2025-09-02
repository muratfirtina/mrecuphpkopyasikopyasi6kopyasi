<?php
/**
 * Contact Messages Admin Panel - Admin Paneli Entegreli
 * Gelen iletişim mesajlarını görüntüleme ve yönetme paneli
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin auth kontrolü
session_start();
if (!isset($_SESSION['user_id']) || !function_exists('isLoggedIn')) {
    if (!isset($_SESSION['admin_logged_in'])) {
        if (($_POST['admin_password'] ?? '') === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
        } else {
            header('Location: ../login.php?error=admin_required');
            exit;
        }
    }
}

// Sayfa ayarları
$pageTitle = 'İletişim Mesajları';
$pageDescription = 'Gelen iletişim mesajlarını görüntüleyin ve yönetin';
$pageIcon = 'bi bi-envelope';

$message = '';
$messageType = '';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_status':
                $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['message_id']]);
                $message = '✅ Mesaj durumu güncellendi!';
                $messageType = 'success';
                break;
                
            case 'delete_message':
                $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
                $stmt->execute([$_POST['message_id']]);
                $message = '✅ Mesaj silindi!';
                $messageType = 'success';
                break;
                
            case 'bulk_action':
                $message_ids = $_POST['message_ids'] ?? [];
                $bulk_action = $_POST['bulk_action_type'] ?? '';
                
                if (!empty($message_ids) && !empty($bulk_action)) {
                    $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
                    
                    if ($bulk_action === 'delete') {
                        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
                        $stmt->execute($message_ids);
                        $message = '✅ Seçilen mesajlar silindi!';
                    } else {
                        $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
                        $stmt->execute(array_merge([$bulk_action], $message_ids));
                        $message = '✅ Seçilen mesajların durumu güncellendi!';
                    }
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = '❌ Hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Total count
$count_query = "SELECT COUNT(*) FROM contact_messages $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_messages = $count_stmt->fetchColumn();
$total_pages = ceil($total_messages / $per_page);

// Get messages
$query = "SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status counts
$status_counts = [];
$status_query = "SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status";
$status_stmt = $pdo->query($status_query);
while ($row = $status_stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

// Quick actions
$quickActions = [
    ['text' => 'Tüm Mesajları Görüntüle', 'url' => 'contact-messages.php', 'icon' => 'bi bi-list', 'class' => 'outline-primary'],
    ['text' => 'İletişim Ayarları', 'url' => '../design/contact.php', 'icon' => 'bi bi-gear', 'class' => 'outline-secondary'],
    ['text' => 'İletişim Sayfası', 'url' => '../contact.php', 'icon' => 'bi bi-external-link-alt', 'class' => 'outline-success']
];

// Admin header include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Custom Styles -->
<style>
    /* Genel Tablo ve Dropdown Fix */
    .table-responsive {
        overflow: visible !important;
    }

    .table {
        border-collapse: collapse;
    }

    .btn-group {
        position: relative !important;
    }

    .dropdown-menu {
        z-index: 9999 !important;
        position: absolute !important;
        top: 100% !important;
        transform: none !important;
        min-width: 200px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        padding: 0.5rem 0;
    }

    .dropdown-item {
        padding: 0.5rem 1rem;
        color: #212529;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #0d6efd;
        transform: translateX(4px);
    }

    .dropdown-item.active {
        background-color: #0d6efd;
        color: white;
        width: -webkit-fill-available;
    }

    .dropdown-divider {
        margin: 0.25rem 0;
        border-top: 1px solid #e9ecef;
    }

    /* Status Badge */
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
    }

    /* Mesaj Önizleme */
    .message-preview {
        max-height: 80px;
        overflow: hidden;
        position: relative;
        line-height: 1.5;
    }

    .message-preview.expanded {
        max-height: none;
    }

    /* İstatistik Kartları */
    .stat-widget {
        padding: 1.5rem;
        border-radius: 12px;
        
        color: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #fff !important;
    }

    .stat-label {
        font-size: 0.95rem;
        opacity: 0.9;
        color: #fff !important;
    }

    /* Filtre Arayüzü */
    .admin-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .admin-card .card-header {
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }

    .admin-card .card-body {
        padding: 1.5rem;
    }

    /* Butonlar */
    .btn-outline-primary {
        border-radius: 25px;
        padding: 0.5rem 1rem;
    }

    /* Pagination */
    .pagination .page-link {
        color: #033e0a;
        border-radius: 8px;
        margin: 0 4px;
    }

    .pagination .page-item.active .page-link {
        background-color: #033e0a;
        border-color: #033e0a;
        color: white;
    }
</style>

<div class="container-fluid py-4">
    <!-- Başarı/Hata Mesajı -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-widget" style="background-color: green !important;">
                <div class="d-flex align-items-center">
                    <div class="me-3"><i class="bi bi-envelope-open fa-2x"></i></div>
                    <div><div class="stat-number"><?php echo $status_counts['new'] ?? 0; ?></div><div class="stat-label">Yeni Mesaj</div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-widget" style="background-color: #3498db !important;">
                <div class="d-flex align-items-center">
                    <div class="me-3"><i class="bi bi-eye fa-2x"></i></div>
                    <div><div class="stat-number"><?php echo $status_counts['read'] ?? 0; ?></div><div class="stat-label">Okundu</div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-widget" style="background-color:#ffc107 !important;">
                <div class="d-flex align-items-center">
                    <div class="me-3"><i class="bi bi-reply fa-2x"></i></div>
                    <div><div class="stat-number"><?php echo $status_counts['replied'] ?? 0; ?></div><div class="stat-label">Cevaplandı</div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-widget" style="background-color: gray !important;">
                <div class="d-flex align-items-center">
                    <div class="me-3"><i class="bi bi-archive fa-2x"></i></div>
                    <div><div class="stat-number"><?php echo $status_counts['archived'] ?? 0; ?></div><div class="stat-label">Arşivlendi</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="admin-card mb-4">
        <div class="card-header">
            <i class="bi bi-filter me-2"></i> Filtreler
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Durum</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>Yeni</option>
                        <option value="read" <?= $status_filter === 'read' ? 'selected' : '' ?>>Okundu</option>
                        <option value="replied" <?= $status_filter === 'replied' ? 'selected' : '' ?>>Cevaplandı</option>
                        <option value="archived" <?= $status_filter === 'archived' ? 'selected' : '' ?>>Arşivlendi</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Arama</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Ad, e-posta, konu veya mesajda ara..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-2"></i>Filtrele</button>
                    <a href="?" class="btn btn-outline-secondary"><i class="bi bi-times"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Ana Tablo -->
    <div class="admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-envelope me-2"></i>
                İletişim Mesajları 
                <span class="badge bg-primary"><?= $total_messages ?></span>
            </h6>
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                <i class="bi bi-sync-alt me-2"></i>Yenile
            </button>
        </div>

        <form method="POST" id="bulkForm">
            <input type="hidden" name="action" value="bulk_action">
            
            <!-- Toplu İşlemler -->
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="selectAll">
                    <label class="form-check-label" for="selectAll">Tümünü Seç</label>
                </div>
                <div class="d-flex gap-2">
                    <select name="bulk_action_type" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Toplu İşlem</option>
                        <option value="read">Okundu İşaretle</option>
                        <option value="replied">Cevaplandı İşaretle</option>
                        <option value="archived">Arşivle</option>
                        <option value="delete">Sil</option>
                    </select>
                    <button type="submit" class="btn btn-outline-primary btn-sm"
                            onclick="return confirm('Seçilen mesajlarda işlem yapmak istediğinizden emin misiniz?')">
                        Uygula
                    </button>
                </div>
            </div>

            <!-- Tablo -->
            <div class="table-responsive" style="overflow: visible !important;">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" class="form-check-input" id="selectAllHeader"></th>
                            <th>Gönderen</th>
                            <th>Konu</th>
                            <th>Mesaj</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th width="150">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Hiç mesaj bulunmuyor.</p>
                                    <a href="../contact.php" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-external-link-alt me-2"></i>İletişim Sayfası
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <tr class="<?= $msg['status'] === 'new' ? 'table-warning' : '' ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input message-checkbox" 
                                               name="message_ids[]" value="<?= $msg['id'] ?>">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($msg['name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($msg['email']) ?></small>
                                        <?php if ($msg['phone']): ?>
                                            <br><small class="text-muted">
                                                <i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($msg['phone']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($msg['subject']) ?></strong></td>
                                    <td class="message-preview">
                                        <?= nl2br(htmlspecialchars(substr($msg['message'], 0, 100))) ?>
                                        <?php if (strlen($msg['message']) > 100): ?>
                                            <button type="button" class="btn btn-link btn-sm text-primary p-0"
                                                    onclick="toggleMessage(<?= $msg['id'] ?>)">
                                                Devamı...
                                            </button>
                                            <div id="full-<?= $msg['id'] ?>" class="d-none">
                                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $msg['status'] === 'new' ? 'danger' : 
                                            ($msg['status'] === 'read' ? 'warning' : 
                                            ($msg['status'] === 'replied' ? 'success' : 'secondary')); 
                                        ?>">
                                            <?= $msg['status'] === 'new' ? 'Yeni' : 
                                               ($msg['status'] === 'read' ? 'Okundu' : 
                                               ($msg['status'] === 'replied' ? 'Cevaplandı' : 'Arşivlendi')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?= date('d.m.Y', strtotime($msg['created_at'])) ?><br>
                                            <?= date('H:i', strtotime($msg['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" style="position: relative;">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#messageModal"
                                                    onclick="viewMessage(<?= htmlspecialchars(json_encode($msg)) ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="btn-group" style="position: relative;">
                                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-gear"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="mailto:<?= htmlspecialchars($msg['email']) ?>">
                                                        <i class="bi bi-reply me-2"></i>E-posta Gönder</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php foreach (['new' => 'Yeni', 'read' => 'Okundu', 'replied' => 'Cevaplandı', 'archived' => 'Arşivle'] as $status => $label): ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                                <input type="hidden" name="status" value="<?= $status ?>">
                                                                <button type="submit" class="dropdown-item <?= $msg['status'] === $status ? 'active' : '' ?>">
                                                                    <?= $label ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endforeach; ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bu mesajı silmek istediğinizden emin misiniz?')">
                                                            <input type="hidden" name="action" value="delete_message">
                                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash me-2"></i>Sil
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Önceki</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Sonraki</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Mesaj Detay Modalı -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mesaj Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Ad Soyad:</strong> <span id="modalName"></span></p>
                        <p><strong>E-posta:</strong> <span id="modalEmail"></span></p>
                        <p><strong>Telefon:</strong> <span id="modalPhone"></span></p>
                        <p><strong>Konu:</strong> <span id="modalSubject"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Durum:</strong> <span id="modalStatus"></span></p>
                        <p><strong>Tarih:</strong> <span id="modalDate"></span></p>
                        <p><strong>IP:</strong> <span id="modalIP"></span></p>
                    </div>
                </div>
                <hr>
                <h6>Mesaj İçeriği</h6>
                <div class="border p-3 bg-light rounded" id="modalMessage"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <a id="modalReplyBtn" href="#" class="btn btn-primary">E-posta Gönder</a>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript
$pageJS = "
// Toplu seçim
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.message-checkbox').forEach(cb => cb.checked = this.checked);
});

// Modal için mesaj görüntüleme
function viewMessage(msg) {
    document.getElementById('modalName').textContent = msg.name;
    document.getElementById('modalEmail').textContent = msg.email;
    document.getElementById('modalPhone').textContent = msg.phone || 'Belirtilmemiş';
    document.getElementById('modalSubject').textContent = msg.subject;
    document.getElementById('modalMessage').innerHTML = msg.message.replace(/\\n/g, '<br>');
    document.getElementById('modalStatus').innerHTML = getStatusBadge(msg.status);
    document.getElementById('modalDate').textContent = new Date(msg.created_at).toLocaleString('tr-TR');
    document.getElementById('modalIP').textContent = msg.ip_address || 'Bilinmiyor';
    document.getElementById('modalReplyBtn').href = 'mailto:' + msg.email + '?subject=Re: ' + encodeURIComponent(msg.subject);
}

function getStatusBadge(status) {
    const badges = {
        'new': '<span class=\"badge bg-danger\">Yeni</span>',
        'read': '<span class=\"badge bg-warning\">Okundu</span>',
        'replied': '<span class=\"badge bg-success\">Cevaplandı</span>',
        'archived': '<span class=\"badge bg-secondary\">Arşivlendi</span>'
    };
    return badges[status] || status;
}

// Mesaj genişletme/kısaltma
function toggleMessage(id) {
    const preview = document.querySelector('#msg-' + id);
    const full = document.querySelector('#full-' + id);
    const button = preview.querySelector('button');
    if (full.classList.contains('d-none')) {
        full.classList.remove('d-none');
        preview.innerHTML += full.innerHTML;
        button.textContent = 'Kısalt';
    } else {
        full.classList.add('d-none');
        button.textContent = 'Devamı...';
    }
}

// Dropdown z-index fix
document.addEventListener('shown.bs.dropdown', function(e) {
    const menu = e.relatedTarget.nextElementSibling;
    if (menu && menu.classList.contains('dropdown-menu')) {
        menu.style.zIndex = '9999';
        menu.style.position = 'absolute';
        menu.style.transform = 'none';
    }
});
";

// Footer include
include '../includes/admin_footer.php';
?>