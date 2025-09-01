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
    
    if (!in_array($status, ['active', 'inactive', 'banned'])) {
        $error = 'Geçersiz durum değeri.';
    } else if (!isValidUUID($userId)) {
        $error = 'Geçersiz kullanıcı ID formatı.';
    } else {
        try {
            // Admin kullanıcılarını pasif yapmaya izin verme
            $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Kullanıcı bulunamadı.';
            } else if ($user['role'] === 'admin' && $status !== 'active') {
                $error = 'Admin kullanıcılarının durumu pasif veya banlanamaz.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $result = $stmt->execute([$status, $userId]);
                
                if ($result) {
                    $success = 'Kullanıcı durumu güncellendi.';
                    $user->logAction($_SESSION['user_id'], 'user_status_update', "Kullanıcı durumu değiştirildi: {$userId} ({$user['username']}), Yeni Durum: {$status}");
                } else {
                    $error = 'Durum güncelleme sırasında hata oluştu.';
                }
            }
        } catch(PDOException $e) {
            $error = 'Durum güncelleme sırasında veritabanı hatası oluştu.';
            error_log("Status update error: " . $e->getMessage());
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
        $adminUsersSkipped = 0;
        
        foreach ($selectedUsers as $userId) {
            if (!isValidUUID($userId)) continue;
            
            try {
                // Önce kullanıcının rolünü kontrol et
                $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    continue;
                }
                
                // Admin kullanıcılarını işlemeye izin verme
                if ($user['role'] === 'admin') {
                    $adminUsersSkipped++;
                    continue;
                }
                
                switch ($action) {
                    case 'activate':
                        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                        $stmt->execute([$userId]);
                        $affectedUsers++;
                        $user->logAction($_SESSION['user_id'], 'user_bulk_activate', "Toplu işlem: Kullanıcı aktif yapıldı: {$userId} ({$user['username']})");
                        break;
                    case 'deactivate':
                        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', deleted_date = NULL WHERE id = ?");
                        $stmt->execute([$userId]);
                        $affectedUsers++;
                        $user->logAction($_SESSION['user_id'], 'user_bulk_deactivate', "Toplu işlem: Kullanıcı pasif yapıldı: {$userId} ({$user['username']})");
                        break;
                    case 'delete':
                        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', deleted_date = NOW() WHERE id = ?");
                        $stmt->execute([$userId]);
                        $affectedUsers++;
                        $user->logAction($_SESSION['user_id'], 'user_bulk_deactivate', "Toplu işlem: Kullanıcı pasif hale getirildi: {$userId} ({$user['username']})");
                        break;
                }
            } catch(PDOException $e) {
                error_log("Bulk action error for user $userId: " . $e->getMessage());
            }
        }
        
        $success = "$affectedUsers kullanıcı başarıyla güncellendi.";
        if ($adminUsersSkipped > 0) {
            $success .= " ($adminUsersSkipped adet admin kullanıcı işlem dışı bırakıldı.)";
        }
    }
}

// Kullanıcı silme işlemi (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = sanitize($_POST['user_id']);
    if (!isValidUUID($userId)) {
        $error = 'Geçersiz kullanıcı ID formatı.';
    } else {
        try {
            // Admin kullanıcılarını pasif yapmaya izin verme
            $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Kullanıcı bulunamadı.';
            } else if ($user['role'] === 'admin') {
                $error = 'Admin kullanıcıları pasif hale getirilemez.';
            } else {
                // Soft delete - status'u inactive yap ve deleted_date'i ayarla
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', deleted_date = NOW() WHERE id = ?");
                $result = $stmt->execute([$userId]);
                
                if ($result) {
                    $success = 'Kullanıcı başarıyla pasif hale getirildi.';
                    $user->logAction($_SESSION['user_id'], 'user_deactivated', "Kullanıcı pasif hale getirildi: {$userId} ({$user['username']})");
                } else {
                    $error = 'Kullanıcı pasif hale getirilirken bir hata oluştu.';
                }
            }
        } catch(PDOException $e) {
            $error = 'Kullanıcı pasif hale getirilirken veritabanı hatası oluştu.';
            error_log("User deactivation error: " . $e->getMessage());
        }
    }
}

// Filtreleme ve arama parametreleri
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '1';
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Geçerli sıralama alanları (SQL Injection korunması)
$allowedSortFields = [
    'created_at' => 'created_at',
    'username' => 'username',
    'email' => 'email',
    'credits' => 'credits',
    'last_login' => 'last_login',
    'first_name' => 'first_name',
    'last_name' => 'last_name',
    'role' => 'role',
    'status' => 'status'
];

// Sıralama alanını kontrol et
if (!isset($allowedSortFields[$sortBy])) {
    $sortBy = 'created_at';
}
$safeSortBy = $allowedSortFields[$sortBy];

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
// Geçerli per_page değerleri
$allowed_per_page = [10, 20, 25, 50, 100];
if (!in_array($per_page, $allowed_per_page)) {
    $per_page = 25;
}
$limit = $per_page;
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
    
    // DEBUG: Parametreleri kontrol et
    // echo "<script>console.log('Debug: page=$page, per_page=$per_page, limit=$limit, offset=$offset');</script>";
    // echo "<script>console.log('Debug: whereClause=$whereClause');</script>";
    // echo "<script>console.log('Debug: sortBy=$sortBy, safeSortBy=$safeSortBy, sortOrder=$sortOrder');</script>";
    // echo "<script>console.log('Debug params:', " . json_encode($params) . ");</script>";
    
    // Toplam kullanıcı sayısı
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = $stmt->fetchColumn();
    
    // DEBUG: Toplam kullanıcı sayısını kontrol et
    // echo "<script>console.log('Debug: totalUsers=$totalUsers');</script>";
    
    // DEBUG: Veritabanında gerçekten kullanıcı var mı?
    // $testQuery = "SELECT COUNT(*) FROM users";
    // $testStmt = $pdo->prepare($testQuery);
    // $testStmt->execute();
    // $testTotalUsers = $testStmt->fetchColumn();
    // echo "<script>console.log('Debug: Total users in DB (no filter)=$testTotalUsers');</script>";
    
    // Kullanıcıları getir
    $query = "
        SELECT id, username, email, first_name, last_name, phone, role, credits, 
               status, email_verified, created_at, last_login
        FROM users 
        $whereClause 
        ORDER BY $safeSortBy $sortOrder 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // DEBUG: Kullanıcı sayısını kontrol et
    // echo "<script>console.log('Debug: users count=', " . count($users) . ");</script>";
    
    $totalPages = ceil($totalUsers / $limit);
    
    // DEBUG: Pagination bilgilerini kontrol et
    // echo "<script>console.log('Debug: totalPages=$totalPages');</script>";
    
} catch(PDOException $e) {
    // DEBUG: Hata mesajını göster
    // echo "<script>console.log('Debug: PDO Error - " . addslashes($e->getMessage()) . "');</script>";
    $error = 'Veritabanı hatası: ' . $e->getMessage();
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
            SUM(credit_quota) as total_quota,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d
        FROM users
    ");
    $stats = $stmt->fetch();
} catch(PDOException $e) {
    $stats = ['total_users' => 0, 'admin_count' => 0, 'user_count' => 0, 'active_count' => 0, 'inactive_count' => 0, 'total_quota' => 0, 'new_users_30d' => 0];
}

// Sayfa bilgileri
$pageTitle = 'Kullanıcılar';
$pageDescription = $status === '1' ? 'Aktif kullanıcıları yönetin ve düzenleyin' : 
                 ($status === '0' ? 'Pasif kullanıcıları yönetin ve düzenleyin' : 'Sistem kullanıcılarını yönetin ve düzenleyin');
$pageIcon = 'bi bi-person';

// Sidebar için istatistikler
$totalUsersForSidebar = $stats['total_users'];
$totalUploads = 0; // Bu değer başka bir yerden gelecek

// Hızlı eylemler
$quickActions = [
    [
        'text' => 'Yeni Kullanıcı',
        'url' => '#',
        'icon' => 'bi bi-user-plus',
        'class' => 'success',
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#addUserModal'
    ],
    [
        'text' => 'Excel Export',
        'url' => 'export-users.php',
        'icon' => 'bi bi-folder2-open-excel',
        'class' => 'info'
    ],
    [
        'text' => 'İstatistikler',
        'url' => 'reports.php?type=users',
        'icon' => 'bi bi-chart-bar',
        'class' => 'warning'
    ]
];

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<script>
// Kullanıcı durumunu değiştirme fonksiyonu
function toggleUserStatus(userId, status) {
    if (confirm('Bu kullanıcının durumunu değiştirmek istediğinize emin misiniz?')) {
        const formData = new FormData();
        formData.append('update_status', '1');
        formData.append('user_id', userId);
        formData.append('status', status);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                // Başarılıysa sayfayı yenile
                window.location.reload();
            } else {
                return response.text().then(text => {
                    throw new Error('Sunucu hatası: ' + text);
                });
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Bir hata oluştu: ' + error.message);
        });
    }
}

// Kullanıcı silme fonksiyonu
function deleteUser(userId, username) {
    if (confirm(username + ' kullanıcısını pasif hale getirmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
        const formData = new FormData();
        formData.append('delete_user', '1');
        formData.append('user_id', userId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                // Başarılıysa sayfayı yenile
                window.location.reload();
            } else {
                return response.text().then(text => {
                    throw new Error('Sunucu hatası: ' + text);
                });
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Bir hata oluştu: ' + error.message);
        });
    }
}
</script>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-admin alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-admin alert-success" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
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
                    <i class="bi bi-person text-primary fa-lg"></i>
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
                    <i class="bi bi-user-check text-success fa-lg"></i>
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
                    <i class="bi bi-user-shield text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo number_format($stats['total_quota'], 2); ?> TL</div>
                    <div class="stat-label">Toplam Kredi</div>
                    <small class="text-muted">Sistem geneli</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="bi bi-coin text-info fa-lg"></i>
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
                    <i class="bi bi-search me-1"></i>Arama
                </label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Ad, soyad, email, kullanıcı adı...">
            </div>
            
            <div class="col-md-2">
                <label for="role" class="form-label">
                    <i class="bi bi-user-tag me-1"></i>Rol
                </label>
                <select class="form-select" id="role" name="role">
                    <option value="">Tüm Roller</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">
                    <i class="bi bi-toggle-on me-1"></i>Durum
                </label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Pasif</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="sort" class="form-label">
                    <i class="bi bi-sort me-1"></i>Sıralama
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
                        <i class="bi bi-search me-1"></i>Filtrele
                    </button>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="bi bi-undo me-1"></i>Temizle
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus me-1"></i>Yeni
                    </button>
                </div>
            </div>
            
            <!-- Per Page Seçimi -->
            <div class="col-md-12">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2">
                            <label for="per_page" class="form-label mb-0 fw-bold">
                                <i class="bi bi-list me-1 text-primary"></i>Sayfa başına:
                            </label>
                            <select class="form-select form-select-sm px-3 py-2" id="per_page" name="per_page" style="width: 120px; border: 2px solid #e9ecef;" onchange="this.form.submit()">
                                <option value="10" <?php echo $per_page === 10 ? 'selected' : ''; ?>>10 kayıt</option>
                                <option value="20" <?php echo $per_page === 20 ? 'selected' : ''; ?>>20 kayıt</option>
                                <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25 kayıt</option>
                                <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50 kayıt</option>
                                <option value="100" <?php echo $per_page === 100 ? 'selected' : ''; ?>>100 kayıt</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Toplam <?php echo number_format($totalUsers); ?> kayıt bulundu
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Kullanıcı Listesi -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-person me-2"></i>
            Kullanıcı Listesi (<?php echo $totalUsers; ?> kullanıcı)
        </h5>
        
        <?php if (!empty($users)): ?>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-cog me-1"></i>Toplu İşlemler
                </button>
                <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Seçili kullanıcılar için:</h6></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('activate')">
                        <i class="bi bi-check-circle me-2 text-success"></i>Aktif Yap
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('deactivate')">
                        <i class="bi bi-clock-history me-2 text-warning"></i>Pasif Yap
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('delete')">
                        <i class="bi bi-trash me-2 text-danger"></i>Sil
                    </a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person fa-3x text-muted mb-3"></i>
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
                                                <i class="bi bi-user text-primary"></i>
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
                                            <i class="bi bi-envelope me-1 text-muted"></i>
                                            <small><?php echo htmlspecialchars($userData['email']); ?></small>
                                            <?php if ($userData['email_verified']): ?>
                                                <i class="bi bi-check-circle text-success ms-1" title="Doğrulanmış"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($userData['phone']): ?>
                                            <div>
                                                <i class="bi bi-phone me-1 text-muted"></i>
                                                <small><?php echo htmlspecialchars($userData['phone']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-2">
                                        <span class="badge bg-<?php echo $userData['role'] === 'admin' ? 'warning' : 'info'; ?>">
                                            <i class="bi bi-<?php echo $userData['role'] === 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                                            <?php echo ucfirst($userData['role']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $userData['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <i class="bi bi-<?php echo $userData['status'] === 'active' ? 'check' : 'times'; ?> me-1"></i>
                                            <?php echo $userData['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                        </span>
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
                                        <a href="user-details.php?id=<?php echo $userData['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm" 
                                           target="_blank">
                                            <i class="bi bi-eye me-1"></i>Detay
                                        </a>
                                        
                                        <button type="button" class="btn btn-outline-<?php echo $userData['status'] === 'active' ? 'warning' : 'success'; ?> btn-sm" 
                                                onclick="toggleUserStatus('<?php echo $userData['id']; ?>', '<?php echo $userData['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                            <i class="bi bi-<?php echo $userData['status'] === 'active' ? 'pause' : 'play'; ?> me-1"></i>
                                            <?php echo $userData['status'] === 'active' ? 'Pasif Yap' : 'Aktif Yap'; ?>
                                        </button>
                                        
                                        <?php if ($userData['role'] !== 'admin'): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteUser('<?php echo $userData['id']; ?>', '<?php echo htmlspecialchars($userData['username']); ?>')">
                                                <i class="bi bi-trash me-1"></i>Sil
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Advanced Pagination Navigation -->
                <div class="pagination-wrapper bg-light border-top p-4">
                    <!-- Sayfa Bilgileri ve Kontroller -->
                    <div class="row align-items-center">
                        <!-- Sol taraf - Bilgi ve Hızlı Atlama -->
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="row align-items-center g-3">
                                <div class="col-auto">
                                    <div class="pagination-info">
                                        <span class="badge bg-primary fs-6 px-3 py-2">
                                            <i class="bi bi-list-ol me-2"></i>
                                            <?php 
                                            $start = $offset + 1;
                                            $end = min($offset + $limit, $totalUsers);
                                            echo "$start - $end / " . number_format($totalUsers);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Hızlı Sayfa Atlama -->
                                <?php if ($totalPages > 5): ?>
                                <div class="col-auto">
                                    <div class="quick-jump-container">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="bi bi-search text-muted"></i>
                                            </span>
                                            <input type="number" class="form-control border-start-0" 
                                                   id="quickJump" 
                                                   min="1" 
                                                   max="<?php echo $totalPages; ?>" 
                                                   value="<?php echo $page; ?>"
                                                   placeholder="Sayfa"
                                                   style="width: 80px;"
                                                   onkeypress="if(event.key==='Enter') quickJumpToPage()"
                                                   title="Sayfa numarası girin ve Enter'a basın">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    onclick="quickJumpToPage()" 
                                                    title="Sayfaya git">
                                                <i class="bi bi-arrow-right"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1">/ <?php echo $totalPages; ?> sayfa</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Sağ taraf - Pagination Kontrolleri -->
                        <div class="col-md-6">
                            <nav aria-label="Sayfa navigasyonu" class="d-flex justify-content-md-end justify-content-center">
                                <ul class="pagination pagination-lg mb-0 shadow-sm">
                                    <!-- İlk Sayfa -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link rounded-start" 
                                           href="<?php echo $page > 1 ? buildPaginationUrl(1) : '#'; ?>" 
                                           title="İlk Sayfa" 
                                           <?php echo $page <= 1 ? 'tabindex="-1"' : ''; ?>>
                                            <i class="bi bi-angle-double-left"></i>
                                            <span class="d-none d-sm-inline ms-1">İlk</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Önceki Sayfa -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" 
                                           href="<?php echo $page > 1 ? buildPaginationUrl($page - 1) : '#'; ?>" 
                                           title="Önceki Sayfa"
                                           <?php echo $page <= 1 ? 'tabindex="-1"' : ''; ?>>
                                            <i class="bi bi-angle-left"></i>
                                            <span class="d-none d-sm-inline ms-1">Önceki</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Sayfa Numaraları -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($totalPages, $page + 2);
                                    
                                    // Mobilde daha az sayfa göster
                                    if ($totalPages > 7) {
                                        $start_page = max(1, $page - 1);
                                        $end_page = min($totalPages, $page + 1);
                                    }
                                    
                                    // İlk sayfa elipsisi
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildPaginationUrl(1); ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled d-none d-md-block">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Sayfa numaraları -->
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link <?php echo $i === $page ? 'bg-primary border-primary' : ''; ?>" 
                                               href="<?php echo buildPaginationUrl($i); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Son sayfa elipsisi -->
                                    <?php if ($end_page < $totalPages): ?>
                                        <?php if ($end_page < $totalPages - 1): ?>
                                            <li class="page-item disabled d-none d-md-block">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildPaginationUrl($totalPages); ?>"><?php echo $totalPages; ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Sonraki Sayfa -->
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" 
                                           href="<?php echo $page < $totalPages ? buildPaginationUrl($page + 1) : '#'; ?>" 
                                           title="Sonraki Sayfa"
                                           <?php echo $page >= $totalPages ? 'tabindex="-1"' : ''; ?>>
                                            <span class="d-none d-sm-inline me-1">Sonraki</span>
                                            <i class="bi bi-angle-right"></i>
                                        </a>
                                    </li>
                                    
                                    <!-- Son Sayfa -->
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link rounded-end" 
                                           href="<?php echo $page < $totalPages ? buildPaginationUrl($totalPages) : '#'; ?>" 
                                           title="Son Sayfa"
                                           <?php echo $page >= $totalPages ? 'tabindex="-1"' : ''; ?>>
                                            <span class="d-none d-sm-inline me-1">Son</span>
                                            <i class="bi bi-angle-double-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    
                    <!-- Alt bilgi çubuğu -->
                    <div class="row mt-3 pt-3 border-top">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Sayfa <strong><?php echo $page; ?></strong> / <strong><?php echo $totalPages; ?></strong> - 
                                Sayfa başına <strong><?php echo $limit; ?></strong> kayıt gösteriliyor
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                <i class="bi bi-database me-1"></i>
                                Toplam <strong><?php echo number_format($totalUsers); ?></strong> kullanıcı bulundu
                            </small>
                        </div>
                    </div>
                </div>
        <?php endif; ?>
    </div>
</div>

<!-- Kullanıcı Ekleme Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-user-plus me-2"></i>Yeni Kullanıcı Ekle
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
                        <i class="bi bi-save me-2"></i>Kullanıcı Ekle
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
// Pagination URL oluşturma fonksiyonu
function buildPaginationUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'users.php?' . http_build_query($params);
}
?>

<?php
// Sayfa özel JavaScript


// Footer include
include '../includes/admin_footer.php';
?>

<style>
/* Advanced Pagination Styling */
.pagination-wrapper {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination-info .badge {
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.quick-jump-container .input-group {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 0.375rem;
    overflow: hidden;
}

.quick-jump-container .form-control {
    border: 2px solid #e9ecef;
    transition: all 0.15s ease-in-out;
}

.quick-jump-container .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Enhanced Pagination Controls */
.pagination-lg .page-link {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 2px solid #dee2e6;
    color: #495057;
    margin: 0 3px;
    border-radius: 0.5rem;
    transition: all 0.2s ease-in-out;
    font-weight: 500;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.pagination-lg .page-link:hover {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border-color: #0d6efd;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
}

.pagination-lg .page-item.active .page-link {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border-color: #0d6efd;
    color: white;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
    transform: scale(1.05);
}

.pagination-lg .page-item.disabled .page-link {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    color: #6c757d;
    opacity: 0.6;
    cursor: not-allowed;
    box-shadow: none;
}

.pagination-lg .page-link i {
    font-size: 0.9rem;
}

/* Per page selector enhanced styling */
.form-select {
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.15s ease-in-out;
    font-weight: 500;
}

.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Badge enhancements */
.badge.bg-light {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
    border: 2px solid #e9ecef;
    color: #495057 !important;
    font-weight: 500;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .pagination-lg .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        margin: 0 1px;
    }
    
    .pagination-wrapper {
        padding: 1rem !important;
    }
    
    .quick-jump-container {
        display: none;
    }
    
    .pagination-info .badge {
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .pagination-lg .page-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
    }
    
    .pagination-lg .page-link span {
        display: none !important;
    }
}

/* Animation for page changes */
.pagination-lg .page-link {
    position: relative;
    overflow: hidden;
}

.pagination-lg .page-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.pagination-lg .page-link:hover::before {
    left: 100%;
}

/* Loading state for quick jump */
.quick-jump-container.loading .btn {
    pointer-events: none;
}

.quick-jump-container.loading .btn i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
