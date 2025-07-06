<?php
/**
 * Mr ECU - Admin Kullanıcı Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
$user = new User($pdo);

$error = '';
$success = '';

// Kullanıcı ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $data = [
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone']),
        'role' => sanitize($_POST['role']),
        'credits' => floatval($_POST['credits'])
    ];
    
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        $error = 'Kullanıcı adı, e-posta ve şifre zorunludur.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } else {
        $result = $user->register($data, true); // Admin tarafından ekleme
        
        if ($result['success']) {
            $success = 'Kullanıcı başarıyla eklendi.';
        } else {
            $error = $result['message'];
        }
    }
}

// Kullanıcı durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $userId = sanitize($_POST['user_id']);
    $status = sanitize($_POST['status']);
    
    if (!isValidUUID($userId)) {
        $error = 'Geçersiz kullanıcı ID formatı.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $userId]);
            
            if ($result) {
                $success = 'Kullanıcı durumu güncellendi.';
                $user->logAction($_SESSION['user_id'], 'user_status_update', "Kullanıcı durumu değiştirildi: $userId");
            }
        } catch(PDOException $e) {
            $error = 'Durum güncelleme sırasında hata oluştu.';
        }
    }
}

// Kredi yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credit'])) {
    $userId = sanitize($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description']);
    
    if (!isValidUUID($userId)) {
        $error = 'Geçersiz kullanıcı ID formatı.';
    } elseif ($amount <= 0) {
        $error = 'Kredi miktarı 0\'dan büyük olmalıdır.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Kullanıcının mevcut kredisini güncelle
            $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // İşlem kaydı oluştur
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (user_id, admin_id, type, amount, description, created_at) 
                VALUES (?, ?, 'deposit', ?, ?, NOW())
            ");
            $stmt->execute([$userId, $_SESSION['user_id'], $amount, $description]);
            
            $pdo->commit();
            $success = number_format($amount, 2) . ' TL kredi başarıyla yüklendi.';
            
            $user->logAction($_SESSION['user_id'], 'admin_credit_add', "Kredi eklendi: $amount TL - User: $userId");
        } catch(PDOException $e) {
            $pdo->rollback();
            $error = 'Kredi yükleme sırasında hata oluştu.';
        }
    }
}

// Toplu işlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = sanitize($_POST['bulk_action']);
    $selectedUsers = $_POST['selected_users'] ?? [];
    
    if (empty($selectedUsers)) {
        $error = 'Lütfen işlem yapmak için kullanıcı seçin.';
    } else {
        $affectedUsers = 0;
        
        foreach ($selectedUsers as $userId) {
            if (!isValidUUID($userId)) continue;
            
            try {
                switch ($action) {
                    case 'activate':
                        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                        $stmt->execute([$userId]);
                        $affectedUsers++;
                        break;
                    case 'deactivate':
                        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                        $stmt->execute([$userId]);
                        $affectedUsers++;
                        break;
                    case 'delete':
                        // Status'u inactive yap
                        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role != 'admin'");
                        $stmt->execute([$userId]);
                        $affectedUsers++;
                        break;
                }
            } catch(PDOException $e) {
                // Hata logla ama devam et
            }
        }
        
        $success = "$affectedUsers kullanıcı başarıyla güncellendi.";
    }
}

// Filtreleme ve arama parametreleri
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Kullanıcıları getir
try {
    $whereClause = "WHERE 1=1"; // Tüm kullanıcıları göster
    $params = [];
    
    if ($search) {
        $whereClause .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if ($role) {
        $whereClause .= " AND role = ?";
        $params[] = $role;
    }
    
    if ($status !== '') {
        $whereClause .= " AND status = ?";
        $params[] = $status === '1' ? 'active' : 'inactive';
    }
    
    // Toplam kullanıcı sayısı
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = $stmt->fetchColumn();
    
    // Kullanıcıları getir
    $query = "
        SELECT id, username, email, first_name, last_name, phone, role, credits, 
               status, email_verified, created_at, last_login
        FROM users 
        $whereClause 
        ORDER BY $sortBy $sortOrder 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    $totalPages = ceil($totalUsers / $limit);
} catch(PDOException $e) {
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
}

// İstatistikler
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
            SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
            SUM(credits) as total_credits,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d
        FROM users
    ");
    $stats = $stmt->fetch();
} catch(PDOException $e) {
    $stats = ['total_users' => 0, 'admin_count' => 0, 'user_count' => 0, 'active_count' => 0, 'inactive_count' => 0, 'total_credits' => 0, 'new_users_30d' => 0];
}

// Sayfa bilgileri
$pageTitle = 'Kullanıcılar';
$pageDescription = 'Sistem kullanıcılarını yönetin ve düzenleyin.';
$pageIcon = 'fas fa-users';

// Sidebar için istatistikler
$totalUsers = $stats['total_users'];
$totalUploads = 0; // Bu değer başka bir yerden gelecek

// Hızlı eylemler
$quickActions = [
    [
        'text' => 'Yeni Kullanıcı',
        'url' => '#',
        'icon' => 'fas fa-user-plus',
        'class' => 'success',
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#addUserModal'
    ],
    [
        'text' => 'Excel Export',
        'url' => 'export-users.php',
        'icon' => 'fas fa-file-excel',
        'class' => 'info'
    ],
    [
        'text' => 'İstatistikler',
        'url' => 'reports.php?type=users',
        'icon' => 'fas fa-chart-bar',
        'class' => 'warning'
    ]
];

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success alert-dismissible fade show" role="alert">
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
                    <div class="stat-number text-primary"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-label">Toplam Kullanıcı</div>
                    <small class="text-success">+<?php echo $stats['new_users_30d']; ?> son 30 gün</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-users text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format($stats['active_count']); ?></div>
                    <div class="stat-label">Aktif Kullanıcı</div>
                    <small class="text-muted"><?php echo number_format($stats['inactive_count']); ?> pasif</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="fas fa-user-check text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo number_format($stats['admin_count']); ?></div>
                    <div class="stat-label">Admin</div>
                    <small class="text-muted"><?php echo number_format($stats['user_count']); ?> normal kullanıcı</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="fas fa-user-shield text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo number_format($stats['total_credits'], 2); ?> TL</div>
                    <div class="stat-label">Toplam Kredi</div>
                    <small class="text-muted">Sistem geneli</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="fas fa-coins text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtre ve Arama -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="search" class="form-label">
                    <i class="fas fa-search me-1"></i>Arama
                </label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Ad, soyad, email, kullanıcı adı...">
            </div>
            
            <div class="col-md-2">
                <label for="role" class="form-label">
                    <i class="fas fa-user-tag me-1"></i>Rol
                </label>
                <select class="form-select" id="role" name="role">
                    <option value="">Tüm Roller</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">
                    <i class="fas fa-toggle-on me-1"></i>Durum
                </label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Pasif</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="sort" class="form-label">
                    <i class="fas fa-sort me-1"></i>Sıralama
                </label>
                <select class="form-select" id="sort" name="sort">
                    <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Kayıt Tarihi</option>
                    <option value="username" <?php echo $sortBy === 'username' ? 'selected' : ''; ?>>Kullanıcı Adı</option>
                    <option value="email" <?php echo $sortBy === 'email' ? 'selected' : ''; ?>>E-posta</option>
                    <option value="credits" <?php echo $sortBy === 'credits' ? 'selected' : ''; ?>>Kredi</option>
                    <option value="last_login" <?php echo $sortBy === 'last_login' ? 'selected' : ''; ?>>Son Giriş</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filtrele
                    </button>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i>Temizle
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-1"></i>Yeni
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Kullanıcı Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>
            Kullanıcı Listesi (<?php echo $totalUsers; ?> kullanıcı)
        </h5>
        
        <?php if (!empty($users)): ?>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog me-1"></i>Toplu İşlemler
                </button>
                <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Seçili kullanıcılar için:</h6></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('activate')">
                        <i class="fas fa-check-circle me-2 text-success"></i>Aktif Yap
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('deactivate')">
                        <i class="fas fa-times-circle me-2 text-warning"></i>Pasif Yap
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('delete')">
                        <i class="fas fa-trash me-2 text-danger"></i>Sil
                    </a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($search || $role || $status !== ''): ?>
                        Filtreye uygun kullanıcı bulunamadı
                    <?php else: ?>
                        Henüz kullanıcı bulunmuyor
                    <?php endif; ?>
                </h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-admin table-hover mb-0" id="usersTable">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllUsers(this)">
                                </div>
                            </th>
                            <th>Kullanıcı Bilgileri</th>
                            <th>İletişim</th>
                            <th>Rol & Durum</th>
                            <th>Kredi</th>
                            <th>Kayıt Tarihi</th>
                            <th>Son Giriş</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userData): ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input user-checkbox" 
                                               value="<?php echo $userData['id']; ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                                            <small class="text-muted">@<?php echo htmlspecialchars($userData['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="mb-1">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            <small><?php echo htmlspecialchars($userData['email']); ?></small>
                                            <?php if ($userData['email_verified']): ?>
                                                <i class="fas fa-check-circle text-success ms-1" title="Doğrulanmış"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($userData['phone']): ?>
                                            <div>
                                                <i class="fas fa-phone me-1 text-muted"></i>
                                                <small><?php echo htmlspecialchars($userData['phone']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-2">
                                        <span class="badge bg-<?php echo $userData['role'] === 'admin' ? 'warning' : 'info'; ?>">
                                            <i class="fas fa-<?php echo $userData['role'] === 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                                            <?php echo ucfirst($userData['role']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $userData['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo $userData['status'] === 'active' ? 'check' : 'times'; ?> me-1"></i>
                                            <?php echo $userData['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <h6 class="mb-1 text-<?php echo $userData['credits'] > 0 ? 'success' : 'muted'; ?>">
                                            <?php echo number_format($userData['credits'], 2); ?> TL
                                        </h6>
                                        <button class="btn btn-outline-success btn-sm" 
                                                onclick="addCredit('<?php echo $userData['id']; ?>', '<?php echo htmlspecialchars($userData['username']); ?>')">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo date('d.m.Y', strtotime($userData['created_at'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($userData['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($userData['last_login']): ?>
                                        <div>
                                            <strong><?php echo date('d.m.Y', strtotime($userData['last_login'])); ?></strong><br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($userData['last_login'])); ?></small>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Henüz giriş yapmamış</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm" style="width: 120px;">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="viewUser('<?php echo $userData['id']; ?>')">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-<?php echo $userData['status'] === 'active' ? 'warning' : 'success'; ?> btn-sm" 
                                                onclick="toggleUserStatus('<?php echo $userData['id']; ?>', '<?php echo $userData['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                            <i class="fas fa-<?php echo $userData['status'] === 'active' ? 'pause' : 'play'; ?> me-1"></i>
                                            <?php echo $userData['status'] === 'active' ? 'Pasif Yap' : 'Aktif Yap'; ?>
                                        </button>
                                        
                                        <?php if ($userData['role'] !== 'admin'): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteUser('<?php echo $userData['id']; ?>', '<?php echo htmlspecialchars($userData['username']); ?>')">
                                                <i class="fas fa-trash me-1"></i>Sil
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Kullanıcı sayfalama">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <!-- Önceki sayfa -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role ? '&role=' . $role : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?><?php echo $sortBy !== 'created_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=asc' : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Sayfa numaraları -->
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role ? '&role=' . $role : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?><?php echo $sortBy !== 'created_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=asc' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Sonraki sayfa -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role ? '&role=' . $role : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?><?php echo $sortBy !== 'created_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=asc' : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> 
                            (Toplam <?php echo $totalUsers; ?> kullanıcı)
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Kullanıcı Ekleme Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Yeni Kullanıcı Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addUserForm">
                <div class="modal-body">
                    <input type="hidden" name="add_user" value="1">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Ad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Soyad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user">Kullanıcı</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="credits" class="form-label">Başlangıç Kredisi (TL)</label>
                                <input type="number" class="form-control" id="credits" name="credits" value="0" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Kullanıcı Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kredi Yükleme Modal -->
<div class="modal fade" id="addCreditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-coins me-2"></i>Kredi Yükle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addCreditForm">
                <div class="modal-body">
                    <input type="hidden" name="add_credit" value="1">
                    <input type="hidden" name="user_id" id="credit_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı</label>
                        <div class="form-control-plaintext" id="credit_username"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Kredi Miktarı (TL) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" required placeholder="Kredi yükleme nedeni..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Kredi Yükle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toplu İşlemler için Hidden Form -->
<form method="POST" id="bulkActionForm" style="display: none;">
    <input type="hidden" name="bulk_action" id="bulk_action_type">
    <div id="bulk_selected_users"></div>
</form>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Toggle all checkboxes
function toggleAllUsers(source) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

// Add credit modal
function addCredit(userId, username) {
    document.getElementById('credit_user_id').value = userId;
    document.getElementById('credit_username').textContent = username;
    document.getElementById('amount').value = '';
    document.getElementById('description').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('addCreditModal'));
    modal.show();
}

// Toggle user status
function toggleUserStatus(userId, newStatus) {
    const statusText = newStatus ? 'aktif' : 'pasif';
    
    if (confirmAdminAction('Kullanıcıyı ' + statusText + ' yapmak istediğinizden emin misiniz?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type=\"hidden\" name=\"update_status\" value=\"1\">
            <input type=\"hidden\" name=\"user_id\" value=\"\${userId}\">
            <input type=\"hidden\" name=\"status\" value=\"\${newStatus}\">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Delete user
function deleteUser(userId, username) {
    if (confirmAdminAction('\"' + username + '\" kullanıcısını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
        bulkAction('delete', [userId]);
    }
}

// View user details
function viewUser(userId) {
    window.open('user-details.php?id=' + userId, '_blank');
}

// Bulk actions
function bulkAction(action, userIds = null) {
    if (!userIds) {
        const checkboxes = document.querySelectorAll('.user-checkbox:checked');
        if (checkboxes.length === 0) {
            showAdminNotification('Lütfen işlem yapmak için kullanıcı seçin!', 'warning');
            return;
        }
        userIds = Array.from(checkboxes).map(cb => cb.value);
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'activate':
            confirmMessage = 'Seçili kullanıcıları aktif yapmak istediğinizden emin misiniz?';
            break;
        case 'deactivate':
            confirmMessage = 'Seçili kullanıcıları pasif yapmak istediğinizden emin misiniz?';
            break;
        case 'delete':
            confirmMessage = 'Seçili kullanıcıları silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!';
            break;
    }
    
    if (confirmAdminAction(confirmMessage)) {
        document.getElementById('bulk_action_type').value = action;
        
        const selectedUsersDiv = document.getElementById('bulk_selected_users');
        selectedUsersDiv.innerHTML = '';
        
        userIds.forEach(userId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_users[]';
            input.value = userId;
            selectedUsersDiv.appendChild(input);
        });
        
        document.getElementById('bulkActionForm').submit();
    }
}

// Table search functionality
function filterTable() {
    // Bu fonksiyon gerçek zamanlı arama için kullanılabilir
    // Şimdilik form submit ile çalışıyor
}

// Export functions
function exportUsers() {
    const params = new URLSearchParams(window.location.search);
    window.open('export-users.php?' + params.toString(), '_blank');
}
";

// Footer include
include '../includes/admin_footer.php';
?>
