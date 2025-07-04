<?php
/**
 * Mr ECU - Admin Kredi Yönetimi (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$user = new User($pdo);

// Kredi ekleme/çıkarma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_credits'])) {
        $user_id = sanitize($_POST['user_id']);
        $amount = (int)$_POST['amount']; // Tam sayı kontör
        $description = sanitize($_POST['description']);
        
        // GUID format kontrolü
        if (!isValidUUID($user_id)) {
            $_SESSION['message'] = 'Geçersiz kullanıcı ID formatı.';
            $_SESSION['message_type'] = 'danger';
        } elseif ($amount <= 0) {
            $_SESSION['message'] = 'Kredi miktarı 0\'dan büyük olmalıdır.';
            $_SESSION['message_type'] = 'danger';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Kullanıcının mevcut kredisini al
                $currentUser = $user->getUserById($user_id);
                if (!$currentUser) {
                    throw new Exception('Kullanıcı bulunamadı.');
                }
                
                // Mevcut kredinin üstüne ekle
                $newCredits = $currentUser['credits'] + $amount;
                
                $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
                $stmt->execute([$newCredits, $user_id]);
                
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
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, amount, type, description, admin_id) 
                    VALUES (?, ?, ?, 'deposit', ?, ?)
                ");
                $stmt->execute([$transactionId, $user_id, $amount, $description, $adminId]);
                
                $pdo->commit();
                $_SESSION['message'] = $amount . ' kredi başarıyla eklendi! Yeni bakiye: ' . number_format($newCredits, 0);
                $_SESSION['message_type'] = 'success';
                
                // SESSION güncellemesi
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                    $_SESSION['credits'] = $user->getUserCredits($user_id);
                }
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'credit_add', "Kullanıcı #{$user_id} için {$amount} kredi eklendi");
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = 'Hata: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        }
        
        // POST işleminden sonra aynı sayfaya redirect yap (PRG pattern)
        $redirectUrl = 'credits.php';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $redirectUrl .= '?search=' . urlencode($_GET['search']);
        }
        if (isset($_GET['page']) && $_GET['page'] > 1) {
            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'page=' . (int)$_GET['page'];
        }
        header('Location: ' . $redirectUrl);
        exit();
        
    } elseif (isset($_POST['subtract_credits'])) {
        $user_id = sanitize($_POST['user_id']);
        $amount = (int)$_POST['amount']; // Tam sayı kontör
        $description = sanitize($_POST['description']);
        
        // GUID format kontrolü
        if (!isValidUUID($user_id)) {
            $_SESSION['message'] = 'Geçersiz kullanıcı ID formatı.';
            $_SESSION['message_type'] = 'danger';
        } elseif ($amount <= 0) {
            $_SESSION['message'] = 'Kredi miktarı 0\'dan büyük olmalıdır.';
            $_SESSION['message_type'] = 'danger';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Kullanıcının mevcut kredisini kontrol et
                $currentUser = $user->getUserById($user_id);
                if (!$currentUser) {
                    throw new Exception('Kullanıcı bulunamadı.');
                }
                
                if ($currentUser['credits'] >= $amount) {
                    $newCredits = $currentUser['credits'] - $amount;
                    
                    // Kullanıcının kredisini güncelle
                    $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
                    $stmt->execute([$newCredits, $user_id]);
                    
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
                    $stmt = $pdo->prepare("
                        INSERT INTO credit_transactions (id, user_id, amount, type, description, admin_id) 
                        VALUES (?, ?, ?, 'withdraw', ?, ?)
                    ");
                    $stmt->execute([$transactionId, $user_id, $amount, $description, $adminId]);
                    
                    $pdo->commit();
                    $_SESSION['message'] = $amount . ' kredi başarıyla düşüldü! Yeni bakiye: ' . number_format($newCredits, 0);
                    $_SESSION['message_type'] = 'success';
                    
                    // SESSION güncellemesi
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                        $_SESSION['credits'] = $user->getUserCredits($user_id);
                    }
                    
                    // Log kaydı
                    $user->logAction($_SESSION['user_id'], 'credit_subtract', "Kullanıcı #{$user_id} için {$amount} kredi düşüldü");
                    
                } else {
                    $_SESSION['message'] = 'Yetersiz kredi! Mevcut: ' . number_format($currentUser['credits'], 0) . ' Kredi';
                    $_SESSION['message_type'] = 'danger';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = 'Hata: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        }
        
        // POST işleminden sonra aynı sayfaya redirect yap (PRG pattern)
        $redirectUrl = 'credits.php';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $redirectUrl .= '?search=' . urlencode($_GET['search']);
        }
        if (isset($_GET['page']) && $_GET['page'] > 1) {
            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'page=' . (int)$_GET['page'];
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
}

// Session'dan mesajları al ve temizle
$message = '';
$messageType = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Arama parametresi
$search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Kullanıcıları getir (arama ile)
if ($search) {
    try {
        $searchTerm = "%{$search}%";
        
        // Toplam arama sonucu sayısı
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM users 
            WHERE role = 'user' 
            AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR id LIKE ?)
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $total_users = $stmt->fetchColumn();
        $total_pages = ceil($total_users / $per_page);
        
        // Arama sonuçları
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM file_uploads WHERE user_id = u.id) as total_uploads,
                   (SELECT COUNT(*) FROM file_uploads WHERE user_id = u.id AND status = 'completed') as completed_uploads
            FROM users u 
            WHERE u.role = 'user' 
            AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.id LIKE ?)
            ORDER BY u.credits DESC, u.created_at DESC 
            LIMIT {$per_page} OFFSET {$offset}
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $users = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        error_log("User search error: " . $e->getMessage());
        $users = [];
        $total_users = 0;
        $total_pages = 0;
    }
} else {
    // Toplam kullanıcı sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);
    
    // Tüm kullanıcıları getir
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM file_uploads WHERE user_id = u.id) as total_uploads,
               (SELECT COUNT(*) FROM file_uploads WHERE user_id = u.id AND status = 'completed') as completed_uploads
        FROM users u 
        WHERE u.role = 'user' 
        ORDER BY u.credits DESC, u.created_at DESC 
        LIMIT {$per_page} OFFSET {$offset}
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
}

// Toplam kredi istatistikleri
$stmt = $pdo->query("
    SELECT 
        SUM(credits) as total_credits,
        AVG(credits) as avg_credits,
        COUNT(*) as user_count
    FROM users 
    WHERE role = 'user'
");
$stats = $stmt->fetch();

// Son kredi işlemleri (GUID uyumlu)
$stmt = $pdo->prepare("
    SELECT ct.*, u.username, u.email, a.username as admin_username
    FROM credit_transactions ct
    JOIN users u ON ct.user_id = u.id
    LEFT JOIN users a ON ct.admin_id = a.id
    ORDER BY ct.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_transactions = $stmt->fetchAll();

$pageTitle = 'Kredi Yönetimi';
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
                        <i class="fas fa-coins me-2"></i><?php echo $pageTitle; ?>
                        <small class="text-muted">(Kontör Sistemi)</small>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="users.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-users me-1"></i>Kullanıcı Yönetimi
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Kredi İstatistikleri -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo number_format((float)$stats['total_credits'], 0); ?></h4>
                                        <p class="mb-0">Toplam Kredi</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-coins" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo number_format((float)$stats['avg_credits'], 0); ?></h4>
                                        <p class="mb-0">Ortalama Kredi</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo number_format($stats['user_count']); ?></h4>
                                        <p class="mb-0">Aktif Kullanıcı</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Son İşlemler -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Son Kredi İşlemleri
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_transactions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">Henüz kredi işlemi bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>İşlem</th>
                                            <th>Miktar</th>
                                            <th>Açıklama</th>
                                            <th>Admin</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $tx): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($tx['username']); ?></td>
                                                <td>
                                                    <?php if ($tx['type'] === 'deposit'): ?>
                                                        <span class="badge bg-success">Kredi Eklendi</span>
                                                    <?php elseif ($tx['type'] === 'withdraw'): ?>
                                                        <span class="badge bg-danger">Kredi Düşüldü</span>
                                                    <?php elseif ($tx['type'] === 'file_charge'): ?>
                                                        <span class="badge bg-warning">Dosya Ücreti</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?php echo $tx['type']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="<?php echo $tx['type'] === 'deposit' ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo $tx['type'] === 'deposit' ? '+' : '-'; ?><?php echo number_format($tx['amount'], 0); ?> Kredi
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($tx['description']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($tx['admin_username'] ?? 'Sistem'); ?></td>
                                                <td>
                                                    <small><?php echo formatDate($tx['created_at']); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kullanıcı Kredi Listesi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Kullanıcı Kredileri
                            <?php if ($search): ?>
                                - "<?php echo htmlspecialchars($search); ?>" araması
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Arama -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Kullanıcı ara (ad, email, ID)..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="credits.php" class="btn btn-outline-secondary ms-2" title="Aramayı temizle">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <?php if ($search): ?>
                                        Arama sonucu: <?php echo count($users); ?> kullanıcı
                                    <?php else: ?>
                                        Toplam: <?php echo $total_users; ?> kullanıcı
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <?php if (empty($users)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-muted">Kullanıcı bulunamadı</h4>
                                <p class="text-muted">
                                    <?php if ($search): ?>
                                        "<?php echo htmlspecialchars($search); ?>" aramasına uygun kullanıcı bulunamadı.
                                    <?php else: ?>
                                        Henüz kullanıcı eklenmemiş.
                                    <?php endif; ?>
                                </p>
                                <?php if ($search): ?>
                                    <a href="credits.php" class="btn btn-outline-primary">
                                        <i class="fas fa-list me-1"></i>Tüm Kullanıcıları Göster
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Kullanıcı</th>
                                            <th>Mevcut Kredi</th>
                                            <th>Dosya Sayısı</th>
                                            <th>Kayıt Tarihi</th>
                                            <th width="150">İşlemler</th>
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
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 14px; font-weight: bold;">
                                                                <?php echo strtoupper(substr($userItem['first_name'], 0, 1) . substr($userItem['last_name'], 0, 1)); ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($userItem['username']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($userItem['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $userItem['credits'] > 0 ? 'success' : 'warning'; ?> fs-6">
                                                        <?php echo number_format((float)$userItem['credits'], 0); ?> Kredi
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-primary"><?php echo $userItem['total_uploads']; ?></span> toplam
                                                    <br>
                                                    <small class="text-success"><?php echo $userItem['completed_uploads']; ?> tamamlanan</small>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDate($userItem['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-outline-success" 
                                                                onclick="openCreditModal('<?php echo htmlspecialchars($userItem['id']); ?>', '<?php echo htmlspecialchars($userItem['username']); ?>', <?php echo $userItem['credits']; ?>, 'add')"
                                                                title="Kredi Ekle">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="openCreditModal('<?php echo htmlspecialchars($userItem['id']); ?>', '<?php echo htmlspecialchars($userItem['username']); ?>', <?php echo $userItem['credits']; ?>, 'subtract')"
                                                                title="Kredi Düş">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <a href="users.php?id=<?php echo urlencode($userItem['id']); ?>" class="btn btn-outline-primary" title="Detaylar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Sayfalama -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Sayfa navigasyonu">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Önceki</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($total_pages, $page + 2);
                                        ?>
                                        
                                        <?php if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($endPage < $total_pages): ?>
                                            <?php if ($endPage < $total_pages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $total_pages; ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Sonraki</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Kredi Modal -->
    <div class="modal fade" id="creditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" id="modalHeader">
                    <h5 class="modal-title" id="creditModalTitle">Kredi İşlemi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="creditForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="selectedUserId">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Kullanıcı:</strong> <span id="selectedUserName"></span><br>
                            <strong>Mevcut Kredi:</strong> <span id="currentCredits"></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">İşlem Miktarı <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="1" max="10000" name="amount" id="creditAmount" class="form-control" required placeholder="Örn: 100">
                                <span class="input-group-text">Kredi</span>
                            </div>
                            <div class="form-text">1-10.000 kredi arasında değer giriniz.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="İşlem nedenini belirtin..."></textarea>
                        </div>
                        
                        <!-- İşlem Önizlemesi -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-2">İşlem Önizlemesi:</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <strong class="text-muted">Mevcut</strong><br>
                                        <span class="badge bg-secondary fs-6" id="previewCurrent">0 Kredi</span>
                                    </div>
                                    <div class="col-4">
                                        <strong id="operationLabel">İşlem</strong><br>
                                        <span class="badge fs-6" id="previewOperation">0 Kredi</span>
                                    </div>
                                    <div class="col-4">
                                        <strong class="text-primary">= Yeni Bakiye</strong><br>
                                        <span class="badge bg-primary fs-6" id="previewResult">0 Kredi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>İptal
                        </button>
                        <button type="submit" name="add_credits" id="addCreditsBtn" class="btn btn-success" style="display: none;">
                            <i class="fas fa-plus me-1"></i>Kredi Ekle
                        </button>
                        <button type="submit" name="subtract_credits" id="subtractCreditsBtn" class="btn btn-danger" style="display: none;">
                            <i class="fas fa-minus me-1"></i>Kredi Düş
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentOperation = 'add';
        let userCurrentCredits = 0;
        
        function openCreditModal(userId, username, credits, action) {
            document.getElementById('selectedUserId').value = userId;
            document.getElementById('selectedUserName').textContent = username;
            document.getElementById('currentCredits').textContent = parseInt(credits).toLocaleString() + ' Kredi';
            userCurrentCredits = parseInt(credits);
            currentOperation = action;
            
            const modalHeader = document.getElementById('modalHeader');
            const creditAmount = document.getElementById('creditAmount');
            const description = document.querySelector('textarea[name="description"]');
            
            if (action === 'add') {
                document.getElementById('creditModalTitle').textContent = 'Kredi Ekle - ' + username;
                modalHeader.className = 'modal-header bg-success text-white';
                document.getElementById('addCreditsBtn').style.display = 'inline-block';
                document.getElementById('subtractCreditsBtn').style.display = 'none';
                document.getElementById('operationLabel').textContent = '+ Eklenecek';
                document.getElementById('operationLabel').className = 'text-success';
                description.value = 'Admin tarafından kredi eklendi';
            } else {
                document.getElementById('creditModalTitle').textContent = 'Kredi Düş - ' + username;
                modalHeader.className = 'modal-header bg-danger text-white';
                document.getElementById('addCreditsBtn').style.display = 'none';
                document.getElementById('subtractCreditsBtn').style.display = 'inline-block';
                document.getElementById('operationLabel').textContent = '- Düşülecek';
                document.getElementById('operationLabel').className = 'text-danger';
                description.value = 'Admin tarafından kredi düşüldü';
            }
            
            // Önizlemeyi sıfırla
            creditAmount.value = '';
            updatePreview();
            
            var modal = new bootstrap.Modal(document.getElementById('creditModal'));
            modal.show();
            
            // Input alanına focus ver
            setTimeout(() => creditAmount.focus(), 100);
        }
        
        function updatePreview() {
            const amount = parseInt(document.getElementById('creditAmount').value) || 0;
            const previewCurrent = document.getElementById('previewCurrent');
            const previewOperation = document.getElementById('previewOperation');
            const previewResult = document.getElementById('previewResult');
            
            previewCurrent.textContent = userCurrentCredits.toLocaleString() + ' Kredi';
            
            if (currentOperation === 'add') {
                previewOperation.textContent = '+' + amount.toLocaleString() + ' Kredi';
                previewOperation.className = 'badge bg-success fs-6';
                const newBalance = userCurrentCredits + amount;
                previewResult.textContent = newBalance.toLocaleString() + ' Kredi';
            } else {
                previewOperation.textContent = '-' + amount.toLocaleString() + ' Kredi';
                previewOperation.className = 'badge bg-danger fs-6';
                const newBalance = Math.max(0, userCurrentCredits - amount);
                previewResult.textContent = newBalance.toLocaleString() + ' Kredi';
                
                // Yetersiz bakiye uyarısı
                if (amount > userCurrentCredits) {
                    previewResult.className = 'badge bg-warning fs-6';
                    previewResult.innerHTML = newBalance.toLocaleString() + ' Kredi<br><small>⚠️ Yetersiz Bakiye</small>';
                } else {
                    previewResult.className = 'badge bg-primary fs-6';
                }
            }
        }
        
        // Input değişikliklerini dinle
        document.getElementById('creditAmount').addEventListener('input', updatePreview);
        document.getElementById('creditAmount').addEventListener('keyup', updatePreview);
        
        // Modal temizleme
        document.getElementById('creditModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('creditForm').reset();
        });
        
        // Form gönderme onayı
        document.getElementById('creditForm').addEventListener('submit', function(e) {
            const amount = parseInt(document.getElementById('creditAmount').value) || 0;
            const username = document.getElementById('selectedUserName').textContent;
            
            if (amount <= 0) {
                e.preventDefault();
                alert('Lütfen geçerli bir miktar girin.');
                return false;
            }
            
            let confirmMessage;
            if (currentOperation === 'add') {
                const newBalance = userCurrentCredits + amount;
                confirmMessage = `${username} kullanıcısına ${amount.toLocaleString()} kredi eklemek istediğinizden emin misiniz?\n\nMevcut: ${userCurrentCredits.toLocaleString()} Kredi\nEklenecek: +${amount.toLocaleString()} Kredi\nYeni Bakiye: ${newBalance.toLocaleString()} Kredi`;
            } else {
                if (amount > userCurrentCredits) {
                    e.preventDefault();
                    alert(`Yetersiz kredi! Kullanıcının mevcut kredisi: ${userCurrentCredits.toLocaleString()} Kredi`);
                    return false;
                }
                const newBalance = userCurrentCredits - amount;
                confirmMessage = `${username} kullanıcısından ${amount.toLocaleString()} kredi düşmek istediğinizden emin misiniz?\n\nMevcut: ${userCurrentCredits.toLocaleString()} Kredi\nDüşülecek: -${amount.toLocaleString()} Kredi\nYeni Bakiye: ${newBalance.toLocaleString()} Kredi`;
            }
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
