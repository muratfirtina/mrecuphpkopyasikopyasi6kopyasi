<?php
/**
 * Mr ECU - Admin Kullanıcı Yönetimi (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$user = new User($pdo);

// Kredi yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credit'])) {
    $userId = sanitize($_POST['user_id']);
    $amount = (int)$_POST['amount']; // Tam sayı olarak al
    $description = sanitize($_POST['description']);
    
    // GUID format kontrolü
    if (!isValidUUID($userId)) {
        $_SESSION['error'] = 'Geçersiz kullanıcı ID formatı.';
    } elseif ($amount <= 0) {
        $_SESSION['error'] = 'Kredi miktarı 0\'dan büyük olmalıdır.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Kullanıcının mevcut kredisini al
            $currentUser = $user->getUserById($userId);
            if (!$currentUser) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            
            // Mevcut kredinin üstüne ekle
            $newCredits = $currentUser['credits'] + $amount;
            
            $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
            $stmt->execute([$newCredits, $userId]);
            
            // Admin ID kontrolü - Session'da GUID formatında mı kontrol et
            $adminId = null;
            if (isset($_SESSION['user_id'])) {
                // Eğer session'daki user_id GUID formatındaysa kullan
                if (isValidUUID($_SESSION['user_id'])) {
                    // GUID formatında admin var mı kontrol et
                    $stmt_admin = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                    $stmt_admin->execute([$_SESSION['user_id']]);
                    if ($stmt_admin->fetch()) {
                        $adminId = $_SESSION['user_id'];
                    }
                } else {
                    // Integer ID formatındaysa direkt kullan (eski sistem uyumluluğu)
                    $adminId = $_SESSION['user_id'];
                }
            }
            
            // Kredi işlem kaydı (GUID ID ile)
            $transactionId = generateUUID();
            
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (id, user_id, amount, type, description, admin_id) 
                VALUES (?, ?, ?, 'deposit', ?, ?)
            ");
            $stmt->execute([$transactionId, $userId, $amount, $description, $adminId]);
            
            $pdo->commit();
            $_SESSION['success'] = $amount . ' kredi başarıyla yüklendi. Yeni bakiye: ' . number_format($newCredits, 0);
            
            // SESSION güncellemesi (eğer aktif kullanıcı güncellenmişse)
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['credits'] = $user->getUserCredits($userId);
            }
            
            // Log kaydı
            $user->logAction($_SESSION['user_id'], 'credit_add', "Kullanıcı #{$userId} için {$amount} kredi eklendi");
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Kredi yükleme sırasında hata oluştu: ' . $e->getMessage();
        } catch(Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
        }
    }
    
    // POST işleminden sonra aynı sayfaya redirect yap (PRG pattern)
    $redirectUrl = 'users.php?id=' . urlencode($userId);
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $redirectUrl .= '&search=' . urlencode($_GET['search']);
    }
    if (isset($_GET['page']) && $_GET['page'] > 1) {
        $redirectUrl .= '&page=' . (int)$_GET['page'];
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Kullanıcı durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = sanitize($_POST['user_id']);
    $status = sanitize($_POST['status']);
    $credits = (int)$_POST['credits']; // Tam sayı olarak al
    
    // GUID format kontrolü
    if (!isValidUUID($userId)) {
        $_SESSION['error'] = 'Geçersiz kullanıcı ID formatı.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Kullanıcı durumu güncelle
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $userId]);
            
            // Kredi güncelle
            $currentUser = $user->getUserById($userId);
            $creditDiff = $credits - $currentUser['credits'];
            
            if ($creditDiff != 0) {
                $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
                $stmt->execute([$credits, $userId]);
                
                // Admin ID kontrolü
                $adminId = null;
                if (isset($_SESSION['user_id'])) {
                    if (isValidUUID($_SESSION['user_id'])) {
                        $stmt_admin = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                        $stmt_admin->execute([$_SESSION['user_id']]);
                        if ($stmt_admin->fetch()) {
                            $adminId = $_SESSION['user_id'];
                        }
                    } else {
                        $adminId = $_SESSION['user_id'];
                    }
                }
                
                // Kredi işlem kaydı (GUID ID ile)
                $transactionId = generateUUID();
                $type = $creditDiff > 0 ? 'deposit' : 'withdraw';
                $description = $creditDiff > 0 ? 'Admin tarafından kredi eklendi' : 'Admin tarafından kredi düşüldü';
                
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, amount, type, description, admin_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$transactionId, $userId, abs($creditDiff), $type, $description, $adminId]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Kullanıcı bilgileri güncellendi.';
            
            // SESSION güncellemesi (eğer aktif kullanıcı güncellenmişse)
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId && $creditDiff != 0) {
                $_SESSION['credits'] = $user->getUserCredits($userId);
            }
            
            // Log kaydı
            $user->logAction($_SESSION['user_id'], 'user_update', "Kullanıcı #{$userId} güncellendi");
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Güncelleme sırasında hata oluştu: ' . $e->getMessage();
        }
    }
    
    // POST işleminden sonra redirect yap
    $redirectUrl = 'users.php?id=' . urlencode($userId);
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $redirectUrl .= '&search=' . urlencode($_GET['search']);
    }
    if (isset($_GET['page']) && $_GET['page'] > 1) {
        $redirectUrl .= '&page=' . (int)$_GET['page'];
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Yeni kullanıcı ekleme (GUID ID ile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $data = [
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone']),
        'role' => sanitize($_POST['role']),
        'credits' => (int)$_POST['credits'] // Tam sayı olarak al
    ];
    
    // Validation
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
        empty($data['first_name']) || empty($data['last_name'])) {
        $_SESSION['error'] = 'Zorunlu alanları doldurun.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Geçerli bir email adresi girin.';
    } else {
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $newUserId = generateUUID(); // UUID oluştur
            
            $stmt = $pdo->prepare("
                INSERT INTO users (id, username, email, password, first_name, last_name, phone, role, credits, status, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', TRUE)
            ");
            
            $result = $stmt->execute([
                $newUserId,
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name'],
                $data['phone'],
                $data['role'],
                $data['credits']
            ]);
            
            if ($result) {
                // Kredi işlem kaydı
                if ($data['credits'] > 0) {
                    $user->addCredit($newUserId, $data['credits'], 'deposit', 'İlk kredi yüklemesi', null, 'manual', $_SESSION['user_id']);
                }
                
                $_SESSION['success'] = 'Kullanıcı başarıyla eklendi. ID: ' . $newUserId;
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'user_create', "Yeni kullanıcı oluşturuldu: {$data['username']} (ID: $newUserId)");
            }
            
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $_SESSION['error'] = 'Bu email veya kullanıcı adı zaten kullanılıyor.';
            } else {
                $_SESSION['error'] = 'Kullanıcı eklenirken hata oluştu: ' . $e->getMessage();
            }
        }
    }
    
    // POST işleminden sonra redirect yap
    $redirectUrl = 'users.php';
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $redirectUrl .= '?search=' . urlencode($_GET['search']);
    }
    if (isset($_GET['page']) && $_GET['page'] > 1) {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'page=' . (int)$_GET['page'];
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Session'dan mesajları al ve temizle
$error = '';
$success = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';

// Kullanıcıları getir
if ($search) {
    try {
        $offset = ($page - 1) * $limit;
        $searchTerm = "%{$search}%";
        
        // GUID uyumlu arama
        $stmt = $pdo->prepare("
            SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at,
                   (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
            FROM users 
            WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR id LIKE ?
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset
        ");
        
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $users = $stmt->fetchAll();
        
        error_log("GUID User search for: " . $search . " - Found: " . count($users) . " users");
        
    } catch(PDOException $e) {
        error_log("User search error: " . $e->getMessage());
        $users = [];
    }
} else {
    $users = $user->getAllUsers($page, $limit);
}

// Tek kullanıcı detayı görüntüleme
$selectedUser = null;
if (isset($_GET['id'])) {
    $userId = sanitize($_GET['id']);
    
    // GUID format kontrolü
    if (isValidUUID($userId)) {
        $selectedUser = $user->getUserById($userId);
        
        // Kullanıcının kredi işlemlerini getir
        if ($selectedUser) {
            try {
                $stmt = $pdo->prepare("
                    SELECT ct.*, u.username as admin_username 
                    FROM credit_transactions ct
                    LEFT JOIN users u ON ct.admin_id = u.id
                    WHERE ct.user_id = ?
                    ORDER BY ct.created_at DESC
                    LIMIT 20
                ");
                $stmt->execute([$userId]);
                $creditTransactions = $stmt->fetchAll();
            } catch(PDOException $e) {
                $creditTransactions = [];
            }
            
            // Kullanıcının dosyalarını getir
            try {
                $stmt = $pdo->prepare("
                    SELECT fu.*, b.name as brand_name, m.name as model_name
                    FROM file_uploads fu
                    LEFT JOIN brands b ON fu.brand_id = b.id
                    LEFT JOIN models m ON fu.model_id = m.id
                    WHERE fu.user_id = ?
                    ORDER BY fu.upload_date DESC
                    LIMIT 10
                ");
                $stmt->execute([$userId]);
                $userFiles = $stmt->fetchAll();
            } catch(PDOException $e) {
                $userFiles = [];
            }
        }
    } else {
        $error = 'Geçersiz kullanıcı ID formatı.';
    }
}

$pageTitle = 'Kullanıcı Yönetimi';
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
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
                        <small class="text-muted">(GUID System)</small>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-1"></i>Yeni Kullanıcı
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($selectedUser): ?>
                    <!-- Kullanıcı Detayı -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>Kullanıcı Detayları - <?php echo htmlspecialchars($selectedUser['username']); ?>
                                        <br><small class="text-muted">ID: <?php echo htmlspecialchars($selectedUser['id']); ?></small>
                                    </h5>
                                    <a href="users.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Kapat
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Sol Kolon - Kullanıcı Bilgileri -->
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Kişisel Bilgiler</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>User ID:</strong></td>
                                                    <td><code class="text-muted small"><?php echo $selectedUser['id']; ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Kullanıcı Adı:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUser['username']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Ad Soyad:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Email:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUser['email']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Telefon:</strong></td>
                                                    <td><?php echo htmlspecialchars($selectedUser['phone'] ?: '-'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Kayıt Tarihi:</strong></td>
                                                    <td><?php echo formatDate($selectedUser['created_at']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Son Güncelleme:</strong></td>
                                                    <td><?php echo formatDate($selectedUser['updated_at']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        
                                        <!-- Sağ Kolon - Hesap Bilgileri -->
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Hesap Bilgileri</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>Rol:</strong></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $selectedUser['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                            <?php echo ucfirst($selectedUser['role']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Durum:</strong></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        switch ($selectedUser['status']) {
                                                            case 'active':
                                                                $statusClass = 'success';
                                                                break;
                                                            case 'inactive':
                                                                $statusClass = 'warning';
                                                                break;
                                                            case 'banned':
                                                                $statusClass = 'danger';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                                            <?php echo ucfirst($selectedUser['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Kredi Bakiyesi:</strong></td>
                                                    <td>
                                                        <span class="badge bg-success fs-6"><?php echo number_format($selectedUser['credits'], 0); ?> Kredi</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Email Doğrulandı:</strong></td>
                                                    <td>
                                                        <?php if ($selectedUser['email_verified']): ?>
                                                            <span class="badge bg-success">Evet</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Hayır</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Toplam Dosya:</strong></td>
                                                    <td><?php echo count($userFiles); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Kullanıcı Güncelleme Formu -->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0">Kullanıcı Güncelle</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST" id="updateUserForm">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($selectedUser['id']); ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Durum</label>
                                                            <select class="form-select" name="status">
                                                                <option value="active" <?php echo $selectedUser['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                                <option value="inactive" <?php echo $selectedUser['status'] === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                                                                <option value="banned" <?php echo $selectedUser['status'] === 'banned' ? 'selected' : ''; ?>>Banlandı</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="credits" class="form-label">Kredi Bakiyesi</label>
                                                            <input type="number" step="1" class="form-control" name="credits" 
                                                                   value="<?php echo $selectedUser['credits']; ?>">
                                                        </div>
                                                        
                                                        <button type="submit" name="update_user" class="btn btn-primary">
                                                            <i class="fas fa-save me-1"></i>Güncelle
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Kredi Yükleme Butonu -->
                                                    <div class="mt-3 pt-3 border-top">
                                                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addCreditModal">
                                                            <i class="fas fa-plus-circle me-1"></i>Kredi Yükle
                                                        </button>
                                                        <small class="text-muted d-block mt-1">Mevcut kredinin üstüne ekleme yapar</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <!-- Son Kredi İşlemleri -->
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Son Kredi İşlemleri</h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($creditTransactions)): ?>
                                                        <p class="text-muted">Henüz kredi işlemi yok.</p>
                                                    <?php else: ?>
                                                        <div style="max-height: 300px; overflow-y: auto;">
                                                            <?php foreach ($creditTransactions as $transaction): ?>
                                                                <div class="border-bottom pb-2 mb-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <div>
                                                                            <?php
                                                                            $iconClass = '';
                                                                            $textClass = '';
                                                                            $typeText = '';
                                                                            
                                                                            switch ($transaction['type']) {
                                                                                case 'deposit':
                                                                                    $iconClass = 'fas fa-plus-circle text-success';
                                                                                    $textClass = 'text-success';
                                                                                    $typeText = 'Eklendi';
                                                                                    break;
                                                                                case 'withdraw':
                                                                                case 'file_charge':
                                                                                    $iconClass = 'fas fa-minus-circle text-danger';
                                                                                    $textClass = 'text-danger';
                                                                                    $typeText = 'Düşüldü';
                                                                                    break;
                                                                                case 'refund':
                                                                                    $iconClass = 'fas fa-undo text-info';
                                                                                    $textClass = 'text-info';
                                                                                    $typeText = 'İade';
                                                                                    break;
                                                                            }
                                                                            ?>
                                                                            <i class="<?php echo $iconClass; ?> me-2"></i>
                                                                            <strong class="<?php echo $textClass; ?>"><?php echo number_format($transaction['amount'], 0); ?> Kredi</strong>
                                                                            <span class="text-muted"><?php echo $typeText; ?></span>
                                                                        </div>
                                                                        <small class="text-muted"><?php echo formatDate($transaction['created_at']); ?></small>
                                                                    </div>
                                                                    <div class="mt-1">
                                                                        <small class="text-muted">ID: <code><?php echo substr($transaction['id'], 0, 8); ?>...</code></small>
                                                                    </div>
                                                                    <?php if ($transaction['description']): ?>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($transaction['description']); ?></small>
                                                                    <?php endif; ?>
                                                                    <?php if ($transaction['admin_username']): ?>
                                                                        <br><small class="text-muted">Admin: <?php echo htmlspecialchars($transaction['admin_username']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Son Dosyalar -->
                                    <?php if (!empty($userFiles)): ?>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <h6 class="text-muted">Son Yüklenen Dosyalar</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Dosya</th>
                                                                <th>Araç</th>
                                                                <th>Durum</th>
                                                                <th>Tarih</th>
                                                                <th>İşlem</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($userFiles as $file): ?>
                                                                <tr>
                                                                    <td><code class="small"><?php echo substr($file['id'], 0, 8); ?>...</code></td>
                                                                    <td>
                                                                        <i class="fas fa-file me-2"></i>
                                                                        <?php echo htmlspecialchars($file['original_name']); ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php echo htmlspecialchars($file['brand_name'] . ' ' . $file['model_name']); ?>
                                                                        (<?php echo $file['year']; ?>)
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        $statusClass = 'secondary';
                                                                        $statusText = $file['status'];
                                                                        
                                                                        switch ($file['status']) {
                                                                            case 'pending':
                                                                                $statusClass = 'warning';
                                                                                $statusText = 'Bekliyor';
                                                                                break;
                                                                            case 'processing':
                                                                                $statusClass = 'info';
                                                                                $statusText = 'İşleniyor';
                                                                                break;
                                                                            case 'completed':
                                                                                $statusClass = 'success';
                                                                                $statusText = 'Tamamlandı';
                                                                                break;
                                                                            case 'rejected':
                                                                                $statusClass = 'danger';
                                                                                $statusText = 'Reddedildi';
                                                                                break;
                                                                        }
                                                                        ?>
                                                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                                    </td>
                                                                    <td><?php echo formatDate($file['upload_date']); ?></td>
                                                                    <td>
                                                                        <a href="uploads.php?id=<?php echo urlencode($file['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Arama -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Kullanıcı ara (ad, email, ID)..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if ($search): ?>
                                <a href="users.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">Toplam: <?php echo count($users); ?> kullanıcı</small>
                    </div>
                </div>

                <!-- Kullanıcı Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Kullanıcı Listesi
                            <?php if ($search): ?>
                                - "<?php echo htmlspecialchars($search); ?>" araması
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-muted">Kullanıcı bulunamadı</h4>
                                <p class="text-muted">
                                    <?php if ($search): ?>
                                        Arama kriterinize uygun kullanıcı bulunamadı.
                                    <?php else: ?>
                                        Henüz kullanıcı eklenmemiş.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Kullanıcı</th>
                                            <th>İletişim</th>
                                            <th>Rol</th>
                                            <th>Durum</th>
                                            <th>Kredi</th>
                                            <th>Dosya</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $userItem): ?>
                                            <tr>
                                                <td>
                                                    <code class="small"><?php echo substr($userItem['id'], 0, 8); ?>...</code>
                                                    <br><small class="text-muted">GUID</small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($userItem['username']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($userItem['first_name'] . ' ' . $userItem['last_name']); ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($userItem['email']); ?></div>
                                                    <?php if ($userItem['phone']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($userItem['phone']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $userItem['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                        <?php echo ucfirst($userItem['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = 'secondary';
                                                    switch ($userItem['status']) {
                                                        case 'active':
                                                            $statusClass = 'success';
                                                            break;
                                                        case 'inactive':
                                                            $statusClass = 'warning';
                                                            break;
                                                        case 'banned':
                                                            $statusClass = 'danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($userItem['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo number_format($userItem['credits'], 0); ?> Kredi</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $userItem['total_uploads']; ?></span>
                                                </td>
                                                <td><?php echo formatDate($userItem['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="users.php?id=<?php echo urlencode($userItem['id']); ?>" class="btn btn-outline-primary" title="Detaylar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="../user/?login_as=<?php echo urlencode($userItem['id']); ?>" class="btn btn-outline-warning" title="Giriş Yap">
                                                            <i class="fas fa-sign-in-alt"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Sayfalama -->
                            <?php if (count($users) >= $limit): ?>
                                <nav aria-label="Sayfa navigasyonu">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Önceki</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $page; ?></span>
                                        </li>
                                        
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Yeni Kullanıcı Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Yeni Kullanıcı Ekle (GUID)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addUserForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Ad *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Soyad *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Şifre *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Rol</label>
                                <select class="form-select" name="role">
                                    <option value="user">Kullanıcı</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="credits" class="form-label">Başlangıç Kredisi</label>
                                <input type="number" step="1" min="0" class="form-control" name="credits" value="0">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Yeni kullanıcı için otomatik GUID oluşturulacak.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="add_user" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Kullanıcı Ekle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Kredi Yükleme Modal -->
    <?php if ($selectedUser): ?>
    <div class="modal fade" id="addCreditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Kredi Yükle - <?php echo htmlspecialchars($selectedUser['username']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addCreditForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($selectedUser['id']); ?>">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Mevcut Kredi Bakiyesi:</strong> <?php echo number_format($selectedUser['credits'], 0); ?> Kredi
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Yüklenecek Kredi Miktarı <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="1" max="10000" class="form-control" name="amount" id="creditAmount" required placeholder="Örn: 100">
                                <span class="input-group-text">Kredi</span>
                            </div>
                            <div class="form-text">Maksimum 10.000 kredi tek seferde yüklenebilir.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="Kredi yükleme nedenini belirtin...">Admin tarafından kredi yüklemesi</textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Bu işlem kullanıcının <strong>mevcut kredisinin üstüne eklenecektir</strong>.
                        </div>
                        
                        <!-- Yeni Bakiye Önizlemesi -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-2">Işlem Önizlemesi:</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <strong class="text-muted">Mevcut</strong><br>
                                        <span class="badge bg-secondary fs-6"><?php echo number_format($selectedUser['credits'], 0); ?> Kredi</span>
                                    </div>
                                    <div class="col-4">
                                        <strong class="text-success">+ Eklenecek</strong><br>
                                        <span class="badge bg-success fs-6" id="addAmount">0 Kredi</span>
                                    </div>
                                    <div class="col-4">
                                        <strong class="text-primary">= Yeni Bakiye</strong><br>
                                        <span class="badge bg-primary fs-6" id="newBalance"><?php echo number_format($selectedUser['credits'], 0); ?> Kredi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>İptal
                        </button>
                        <button type="submit" name="add_credit" class="btn btn-success">
                            <i class="fas fa-plus-circle me-1"></i>Kredi Yükle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // GUID validation function
        function isValidGUID(guid) {
            const guidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            return guidPattern.test(guid);
        }
        
        // Form validation with GUID checks
        document.addEventListener('DOMContentLoaded', function() {
            const updateForm = document.getElementById('updateUserForm');
            if (updateForm) {
                updateForm.addEventListener('submit', function(e) {
                    const userId = this.querySelector('input[name="user_id"]').value;
                    
                    if (!isValidGUID(userId)) {
                        e.preventDefault();
                        alert('Geçersiz kullanıcı GUID formatı: ' + userId);
                        return false;
                    }
                });
            }
        });
        
        // Modal açıldığında form temizle
        document.getElementById('addUserModal').addEventListener('shown.bs.modal', function () {
            this.querySelector('form').reset();
        });
        
        // Kredi yükleme modal'i için yeni bakiye hesaplama
        <?php if ($selectedUser): ?>
        const currentCredits = <?php echo $selectedUser['credits']; ?>;
        const creditAmountInput = document.getElementById('creditAmount');
        const addAmountElement = document.getElementById('addAmount');
        const newBalanceElement = document.getElementById('newBalance');
        
        function updateCreditPreview() {
            const addAmount = parseInt(creditAmountInput.value) || 0;
            const newBalance = currentCredits + addAmount;
            
            addAmountElement.textContent = addAmount.toLocaleString() + ' Kredi';
            newBalanceElement.textContent = newBalance.toLocaleString() + ' Kredi';
        }
        
        if (creditAmountInput) {
            creditAmountInput.addEventListener('input', updateCreditPreview);
            creditAmountInput.addEventListener('keyup', updateCreditPreview);
        }
        
        // Kredi modal açıldığında form temizle ve önizlemeyi sıfırla
        document.getElementById('addCreditModal').addEventListener('shown.bs.modal', function () {
            const form = this.querySelector('form');
            const amountInput = form.querySelector('#creditAmount');
            const descriptionInput = form.querySelector('textarea[name="description"]');
            
            amountInput.value = '';
            descriptionInput.value = 'Admin tarafından kredi yüklemesi';
            updateCreditPreview();
            
            // Input alanına focus ver
            setTimeout(() => amountInput.focus(), 100);
        });
        
        // Form gönderilmeden önce onay al
        document.getElementById('addCreditForm').addEventListener('submit', function(e) {
            const amount = parseInt(creditAmountInput.value) || 0;
            const newBalance = currentCredits + amount;
            
            const confirmMessage = `Bu kullanıcıya ${amount.toLocaleString()} kredi yüklemek istediğinizden emin misiniz?\n\nMevcut Bakiye: ${currentCredits.toLocaleString()} Kredi\nEklenecek: ${amount.toLocaleString()} Kredi\nYeni Bakiye: ${newBalance.toLocaleString()} Kredi`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
