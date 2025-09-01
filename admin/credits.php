<?php
/**
 * Mr ECU - Admin Kredi Yönetimi (Düzeltilmiş Versiyon)
 */

// DEBUG: Sayfa açıldığını log'la
error_log('Credits.php sayfası açıldı. Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır
$user = new User($pdo);
$error = '';
$success = '';

// Session'dan mesajları al
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Kredi işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG: Form verisini log'la
    error_log('Credits.php POST verisi: ' . print_r($_POST, true));
    error_log('Session verisi: ' . print_r($_SESSION, true));
    error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    error_log('Content-Length: ' . ($_SERVER['CONTENT_LENGTH'] ?? 'not set'));
    
    // POST verilerini kontrol et
    $hasAddCredits = isset($_POST['add_credits']) && !empty($_POST['add_credits']);
    $hasDeductCredits = isset($_POST['deduct_credits']) && !empty($_POST['deduct_credits']);
    $hasResetQuota = isset($_POST['reset_quota']) && !empty($_POST['reset_quota']);
    $hasSetQuota = isset($_POST['set_quota']) && !empty($_POST['set_quota']);
    
    error_log('Has add_credits: ' . ($hasAddCredits ? 'true' : 'false'));
    error_log('Has deduct_credits: ' . ($hasDeductCredits ? 'true' : 'false'));
    error_log('Has reset_quota: ' . ($hasResetQuota ? 'true' : 'false'));
    error_log('Has set_quota: ' . ($hasSetQuota ? 'true' : 'false'));
    
    if ($hasAddCredits) {
        error_log('Add credits işlemi başlatılıyor...');
        $user_id = sanitize($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $description = sanitize($_POST['description']);
        
        if (!isValidUUID($user_id)) {
            $error = 'Geçersiz kullanıcı ID formatı.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } elseif ($amount <= 0) {
            $error = 'Kredi miktarı 0\'dan büyük olmalıdır.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } else {
            try {
                $pdo->beginTransaction();
                
                // Kullanıcının mevcut kredisini al
                $currentUser = $user->getUserById($user_id);
                if (!$currentUser) {
                    throw new Exception('Kullanıcı bulunamadı.');
                }
                
                // TERS KREDİ SİSTEMİ: Kredi kotası artırma
                $newCreditQuota = $currentUser['credit_quota'] + $amount;
                
                $stmt = $pdo->prepare("UPDATE users SET credit_quota = ? WHERE id = ?");
                $stmt->execute([$newCreditQuota, $user_id]);
                
                // Kullanılabilir kredi hesapla
                $availableCredits = $newCreditQuota - $currentUser['credit_used'];
                
                // Credit transactions tablosuna kaydet
                $transactionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, admin_id, transaction_type, type, amount, description, created_at) 
                    VALUES (?, ?, ?, 'add', 'quota_add', ?, ?, NOW())
                ");
                $stmt->execute([$transactionId, $user_id, $_SESSION['user_id'], $amount, $description]);
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'credit_quota_increased', "Kullanıcının kredi kotası {$amount} TL artırıldı: {$description}");
                
                $pdo->commit();
                $success = "{$amount} TL kredi kotası başarıyla artırıldı. Yeni kota: " . number_format($newCreditQuota, 2) . " TL (Kullanılabilir: " . number_format($availableCredits, 2) . " TL)";
                error_log('Kredi ekleme başarılı. Redirect yapılıyor...');
                
                // Başarı mesajını session'a kaydet ve redirect et
                $_SESSION['success_message'] = $success;
                header('Location: credits.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Kredi eklenirken hata oluştu: ' . $e->getMessage();
                error_log('Kredi ekleme hatası: ' . $e->getMessage());
                $_SESSION['error_message'] = $error;
                header('Location: credits.php');
                exit();
            }
        }
    }
    
    if ($hasDeductCredits) {
        error_log('Deduct credits işlemi başlatılıyor...');
        $user_id = sanitize($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $description = sanitize($_POST['description']);
        
        if (!isValidUUID($user_id)) {
            $error = 'Geçersiz kullanıcı ID formatı.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } elseif ($amount <= 0) {
            $error = 'Kredi miktarı 0\'dan büyük olmalıdır.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } else {
            try {
                $pdo->beginTransaction();
                
                $currentUser = $user->getUserById($user_id);
                if (!$currentUser) {
                    throw new Exception('Kullanıcı bulunamadı.');
                }
                
                // TERS KREDİ SİSTEMİ: Kullanılan krediyi azaltma (kredi iadesi)
                if ($currentUser['credit_used'] < $amount) {
                    throw new Exception('Kullanıcının iade edilebilir kredisi yok. Mevcut kullanım: ' . $currentUser['credit_used'] . ' TL');
                }
                
                // Kullanılan krediyi azalt (kredi iadesi)
                $newCreditUsed = $currentUser['credit_used'] - $amount;
                
                $stmt = $pdo->prepare("UPDATE users SET credit_used = ? WHERE id = ?");
                $stmt->execute([$newCreditUsed, $user_id]);
                
                // Kullanılabilir kredi hesapla
                $availableCredits = $currentUser['credit_quota'] - $newCreditUsed;
                
                // Credit transactions tablosuna kaydet
                $transactionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, admin_id, transaction_type, type, amount, description, created_at) 
                    VALUES (?, ?, ?, 'withdraw', 'refund', ?, ?, NOW())
                ");
                $stmt->execute([$transactionId, $user_id, $_SESSION['user_id'], $amount, $description]);
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'credit_usage_removed', "Kullanıcının kullanılan kredisi {$amount} TL azaltıldı (iade): {$description}");
                
                $pdo->commit();
                $success = "{$amount} TL kullanım kredisi başarıyla iade edildi. Kalan kullanım: " . number_format($newCreditUsed, 2) . " TL (Kullanılabilir: " . number_format($availableCredits, 2) . " TL)";
                error_log('Kredi düşme başarılı. Redirect yapılıyor...');
                
                // Başarı mesajını session'a kaydet ve redirect et
                $_SESSION['success_message'] = $success;
                header('Location: credits.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Kredi düşülürken hata oluştu: ' . $e->getMessage();
                error_log('Kredi düşme hatası: ' . $e->getMessage());
                $_SESSION['error_message'] = $error;
                header('Location: credits.php');
                exit();
            }
        }
    }
    
    if ($hasResetQuota) {
        error_log('Reset quota işlemi başlatılıyor...');
        $user_id = sanitize($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $description = sanitize($_POST['description']);
        
        if (!isValidUUID($user_id)) {
            $error = 'Geçersiz kullanıcı ID formatı.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } elseif ($amount < 0) {
            $error = 'Kredi miktarı 0 veya daha büyük olmalıdır.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } else {
            try {
                $pdo->beginTransaction();
                
                $currentUser = $user->getUserById($user_id);
                if (!$currentUser) {
                    throw new Exception('Kullanıcı bulunamadı.');
                }
                
                // KOTA SIFIRLA: Hem kotayı hem kullanımı sıfırla, yeni kota belirle
                $stmt = $pdo->prepare("UPDATE users SET credit_quota = ?, credit_used = 0 WHERE id = ?");
                $stmt->execute([$amount, $user_id]);
                
                // Credit transactions tablosuna kaydet
                $transactionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, admin_id, transaction_type, type, amount, description, created_at) 
                    VALUES (?, ?, ?, 'add', 'quota_reset', ?, ?, NOW())
                ");
                $stmt->execute([$transactionId, $user_id, $_SESSION['user_id'], $amount, $description]);
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'credit_quota_reset', "Kullanıcının kredi kotası sıfırlandı ve {$amount} TL yeni kota tanımlandı: {$description}");
                
                $pdo->commit();
                $success = "Kredi kotası başarıyla sıfırlandı ve {$amount} TL yeni kota tanımlandı. Kullanılabilir: " . number_format($amount, 2) . " TL";
                error_log('Kota sıfırlama başarılı. Redirect yapılıyor...');
                
                $_SESSION['success_message'] = $success;
                header('Location: credits.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Kota sıfırlanırken hata oluştu: ' . $e->getMessage();
                error_log('Kota sıfırlama hatası: ' . $e->getMessage());
                $_SESSION['error_message'] = $error;
                header('Location: credits.php');
                exit();
            }
        }
    }
    
    if ($hasSetQuota) {
        error_log('Set quota işlemi başlatılıyor...');
        $user_id = sanitize($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $description = sanitize($_POST['description']);
        
        if (!isValidUUID($user_id)) {
            $error = 'Geçersiz kullanıcı ID formatı.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } elseif ($amount < 0) {
            $error = 'Kredi miktarı 0 veya daha büyük olmalıdır.';
            $_SESSION['error_message'] = $error;
            header('Location: credits.php');
            exit();
        } else {
            try {
                $pdo->beginTransaction();
                
                $currentUser = $user->getUserById($user_id);
                if (!$currentUser) {
                    throw new Exception('Kullanıcı bulunamadı.');
                }
                
                // KOTA YENİLE: Mevcut kullanımı koru, sadece kotayı değiştir
                $stmt = $pdo->prepare("UPDATE users SET credit_quota = ? WHERE id = ?");
                $stmt->execute([$amount, $user_id]);
                
                // Kullanılabilir kredi hesapla
                $availableCredits = $amount - $currentUser['credit_used'];
                
                // Credit transactions tablosuna kaydet
                $transactionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, admin_id, transaction_type, type, amount, description, created_at) 
                    VALUES (?, ?, ?, 'add', 'quota_set', ?, ?, NOW())
                ");
                $stmt->execute([$transactionId, $user_id, $_SESSION['user_id'], $amount, $description]);
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'credit_quota_set', "Kullanıcının kredi kotası {$amount} TL olarak belirlendi: {$description}");
                
                $pdo->commit();
                $success = "Kredi kotası {$amount} TL olarak başarıyla belirlendi. Kullanılabilir: " . number_format($availableCredits, 2) . " TL";
                error_log('Kota belirleme başarılı. Redirect yapılıyor...');
                
                $_SESSION['success_message'] = $success;
                header('Location: credits.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Kota belirlenirken hata oluştu: ' . $e->getMessage();
                error_log('Kota belirleme hatası: ' . $e->getMessage());
                $_SESSION['error_message'] = $error;
                header('Location: credits.php');
                exit();
            }
        }
    }
    
    // Hiçbir işlem bulunamadıysa
    if (!$hasAddCredits && !$hasDeductCredits && !$hasResetQuota && !$hasSetQuota) {
        error_log('POST işlemi geldi ama hiçbir kredi işlemi bulunamadı!');
        error_log('POST keys: ' . implode(', ', array_keys($_POST)));
        $_SESSION['error_message'] = 'Geçersiz işlem tipi.';
        header('Location: credits.php');
        exit();
    }
}

// Kullanıcıları arama
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(10, min(100, intval($_GET['per_page']))) : 25; // 10-100 arası limit, default 10
$limit = $per_page; // Backward compatibility
$offset = ($page - 1) * $per_page;

// Pagination URL builder fonksiyonu
function buildPaginationUrl($pageNum, $search = '', $per_page = 10) {
    $params = array(
        'page' => $pageNum,
        'per_page' => $per_page
    );
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    return 'credits.php?' . http_build_query($params);
}

try {
    $whereClause = "WHERE role = 'user'";
    $params = [];
    
    if ($search) {
        $whereClause .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    // Toplam kullanıcı sayısı
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = $stmt->fetchColumn();
    
    // TERS KREDİ SİSTEMİ: Kullanıcıları getir - LIMIT ve OFFSET'i direkt sorguya ekle
    $query = "
        SELECT id, username, email, first_name, last_name, credit_quota, credit_used, 
               (credit_quota - credit_used) as available_credits, created_at, last_login
        FROM users 
        $whereClause 
        ORDER BY credit_quota DESC, available_credits DESC, username ASC 
        LIMIT $per_page OFFSET $offset
    ";
    
    if (!empty($params)) {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($query);
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPages = ceil($totalUsers / $per_page);
} catch (PDOException $e) {
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
    $error = 'Kullanıcılar yüklenirken hata oluştu: ' . $e->getMessage();
    error_log('Credits.php user loading error: ' . $e->getMessage());
}

// TERS KREDİ SİSTEMİ: Kredi istatistikleri
try {
    $stmt = $pdo->query("
        SELECT 
            SUM(credit_quota) as total_quota,
            SUM(credit_used) as total_used,
            SUM(credit_quota - credit_used) as total_available,
            AVG(credit_quota) as avg_quota,
            AVG(credit_used) as avg_used,
            AVG(credit_quota - credit_used) as avg_available,
            MAX(credit_quota) as max_quota,
            COUNT(*) as user_count,
            SUM(CASE WHEN credit_quota > 0 THEN 1 ELSE 0 END) as users_with_quota,
            SUM(CASE WHEN (credit_quota - credit_used) > 0 THEN 1 ELSE 0 END) as users_with_available_credits
        FROM users 
        WHERE role = 'user'
    ");
    $creditStats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $creditStats = [
        'total_quota' => 0,
        'total_used' => 0,
        'total_available' => 0,
        'avg_quota' => 0,
        'avg_used' => 0,
        'avg_available' => 0,
        'max_quota' => 0,
        'user_count' => 0,
        'users_with_quota' => 0,
        'users_with_available_credits' => 0
    ];
}

$pageTitle = 'Kredi Yönetimi';
$pageDescription = 'Kullanıcı kredilerini yönetin ve kontrol edin';
$pageIcon = 'bi bi-coins';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Kredi İstatistikleri -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format($creditStats['total_quota'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">Toplam Kredi Kotası</div>
                    <small class="text-muted"><?php echo number_format($creditStats['user_count'] ?? 0); ?> kullanıcı</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="bi bi-coins text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo number_format($creditStats['total_used'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">Kullanılan Krediler</div>
                    <small class="text-muted"><?php echo number_format($creditStats['total_quota'] > 0 ? ($creditStats['total_used'] / $creditStats['total_quota']) * 100 : 0, 1); ?>% kullanım</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="bi bi-chart-line text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo number_format($creditStats['total_available'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">Kullanılabilir Krediler</div>
                    <small class="text-muted"><?php echo number_format($creditStats['users_with_available_credits'] ?? 0); ?> aktif kullanıcı</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="bi bi-wallet text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format($creditStats['users_with_available_credits'] ?? 0); ?></div>
                    <div class="stat-label">Aktif Kullanıcılar</div>
                    <small class="text-muted">Kullanılabilir kredisi olan</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="bi bi-users text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Arama ve Filtreleme -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label">Kullanıcı Ara</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Kullanıcı adı, e-posta, ad veya soyad...">
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Ara
                </button>
            </div>
            
            <div class="col-md-2">
                <a href="credits.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-undo me-1"></i>Temizle
                </a>
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
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-users me-2"></i>Kullanıcılar (<?php echo number_format($totalUsers); ?> adet)
        </h5>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-users fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">
                    <?php if ($search): ?>
                        Arama kriterine uygun kullanıcı bulunamadı
                    <?php else: ?>
                        Henüz kullanıcı yok
                    <?php endif; ?>
                </h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kullanıcı</th>
                            <th>İletişim</th>
                            <th>Kredi Durumu</th>
                            <th>Kayıt Tarihi</th>
                            <th>Son Giriş</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userItem): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong>
                                            <a href="user-details.php?id=<?php echo $userItem['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($userItem['first_name'] . ' ' . $userItem['last_name']); ?>
                                            </a>
                                        </strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($userItem['username']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="bi bi-envelope me-1"></i>
                                        <small><?php echo htmlspecialchars($userItem['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Kota:</small>
                                            <strong class="text-primary"><?php echo number_format($userItem['credit_quota'], 2); ?> TL</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Kullanılan:</small>
                                            <span class="text-warning"><?php echo number_format($userItem['credit_used'], 2); ?> TL</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Kullanılabilir:</small>
                                            <strong class="text-success"><?php echo number_format($userItem['available_credits'], 2); ?> TL</strong>
                                        </div>
                                        <?php if ($userItem['credit_quota'] > 0): ?>
                                            <div class="progress mt-2" style="height: 5px;">
                                                <div class="progress-bar bg-warning" 
                                                     style="width: <?php echo min(100, ($userItem['credit_used'] / $userItem['credit_quota']) * 100); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo number_format(($userItem['credit_used'] / $userItem['credit_quota']) * 100, 1); ?>% kullanım</small>
                                        <?php else: ?>
                                            <small class="text-muted">Kota belirlenmemiş</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <small><?php echo date('d.m.Y', strtotime($userItem['created_at'])); ?></small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo $userItem['last_login'] ? date('d.m.Y H:i', strtotime($userItem['last_login'])) : 'Hiç giriş yapmamış'; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-success" 
                                                onclick="openCreditModal('add', '<?php echo $userItem['id']; ?>', '<?php echo htmlspecialchars(addslashes($userItem['first_name'] . ' ' . $userItem['last_name'])); ?>', '<?php echo $userItem['credit_quota']; ?>', '<?php echo $userItem['credit_used']; ?>', '<?php echo $userItem['available_credits']; ?>')"
                                                title="Kredi Kotası Artır">
                                            <i class="bi bi-plus me-1"></i>Kota +
                                        </button>
                                        <button type="button" class="btn btn-warning" 
                                                onclick="openCreditModal('set', '<?php echo $userItem['id']; ?>', '<?php echo htmlspecialchars(addslashes($userItem['first_name'] . ' ' . $userItem['last_name'])); ?>', '<?php echo $userItem['credit_quota']; ?>', '<?php echo $userItem['credit_used']; ?>', '<?php echo $userItem['available_credits']; ?>')"
                                                title="Kota Yenile">
                                            <i class="bi bi-edit me-1"></i>Yenile
                                        </button>
                                        <button type="button" class="btn btn-info" 
                                                onclick="openCreditModal('reset', '<?php echo $userItem['id']; ?>', '<?php echo htmlspecialchars(addslashes($userItem['first_name'] . ' ' . $userItem['last_name'])); ?>', '<?php echo $userItem['credit_quota']; ?>', '<?php echo $userItem['credit_used']; ?>', '<?php echo $userItem['available_credits']; ?>')"
                                                title="Kota Sıfırla">
                                            <i class="bi bi-sync me-1"></i>Sıfırla
                                        </button>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="openCreditModal('deduct', '<?php echo $userItem['id']; ?>', '<?php echo htmlspecialchars(addslashes($userItem['first_name'] . ' ' . $userItem['last_name'])); ?>', '<?php echo $userItem['credit_quota']; ?>', '<?php echo $userItem['credit_used']; ?>', '<?php echo $userItem['available_credits']; ?>')"
                                                title="Kredi İadesi">
                                            <i class="bi bi-undo me-1"></i>İade
                                        </button>
                                        <a href="user-details.php?id=<?php echo $userItem['id']; ?>" class="btn btn-outline-primary" title="Detaylar">
                                            <i class="bi bi-eye"></i>
                                        </a>
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
                                        $end = min($offset + $per_page, $totalUsers);
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
                                       href="<?php echo $page > 1 ? buildPaginationUrl(1, $search, $per_page) : '#'; ?>" 
                                       title="İlk Sayfa" 
                                       <?php echo $page <= 1 ? 'tabindex="-1"' : ''; ?>>
                                        <i class="bi bi-angle-double-left"></i>
                                        <span class="d-none d-sm-inline ms-1">İlk</span>
                                    </a>
                                </li>
                                
                                <!-- Önceki Sayfa -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="<?php echo $page > 1 ? buildPaginationUrl($page - 1, $search, $per_page) : '#'; ?>" 
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
                                        <a class="page-link" href="<?php echo buildPaginationUrl(1, $search, $per_page); ?>">1</a>
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
                                           href="<?php echo buildPaginationUrl($i, $search, $per_page); ?>">
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
                                        <a class="page-link" href="<?php echo buildPaginationUrl($totalPages, $search, $per_page); ?>"><?php echo $totalPages; ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Sonraki Sayfa -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="<?php echo $page < $totalPages ? buildPaginationUrl($page + 1, $search, $per_page) : '#'; ?>" 
                                       title="Sonraki Sayfa"
                                       <?php echo $page >= $totalPages ? 'tabindex="-1"' : ''; ?>>
                                        <span class="d-none d-sm-inline me-1">Sonraki</span>
                                        <i class="bi bi-angle-right"></i>
                                    </a>
                                </li>
                                
                                <!-- Son Sayfa -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-end" 
                                       href="<?php echo $page < $totalPages ? buildPaginationUrl($totalPages, $search, $per_page) : '#'; ?>" 
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
                            Sayfa başına <strong><?php echo $per_page; ?></strong> kayıt gösteriliyor
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

<!-- Kredi İşlemi Modal -->
<div class="modal fade" id="creditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="credits.php" id="creditForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Kredi İşlemi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    <!-- Işlem türü için ayrı hidden inputlar -->
                    <input type="hidden" name="add_credits" id="add_credits" value="">
                    <input type="hidden" name="deduct_credits" id="deduct_credits" value="">
                    <input type="hidden" name="reset_quota" id="reset_quota" value="">
                    <input type="hidden" name="set_quota" id="set_quota" value="">
                    
                    <div class="alert alert-info alert-permanent">
                        <strong>Kullanıcı:</strong> <span id="selectedUserName"></span><br>
                        <strong>Tanımlı Kota:</strong> <span id="creditQuota"></span> TL<br>
                        <strong>Mevcut Kullanım:</strong> <span id="creditUsed"></span> TL<br>
                        <strong>Kalan Kullanım:</strong> <span id="currentCredits"></span> TL
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Kredi Miktarı (TL)</label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" required placeholder="İşlem açıklamasını yazın..."></textarea>
                    </div>
                    
                    <!-- Önizleme Kutusu -->
                    <div id="previewBox" class="alert alert-light border alert-permanent">
                        <h6>İşlem Önizlemesi:</h6>
                        <div id="previewText">Lütfen miktar girin...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">İşlemi Onayla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hızlı sayfa atlama fonksiyonu
function quickJumpToPage() {
    const pageInput = document.getElementById('quickJump');
    const targetPage = parseInt(pageInput.value);
    const maxPage = parseInt(pageInput.getAttribute('max'));
    
    if (targetPage && targetPage >= 1 && targetPage <= maxPage) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('page', targetPage);
        
        window.location.href = '?' + urlParams.toString();
    } else {
        alert('Lütfen 1 ile ' + maxPage + ' arasında geçerli bir sayfa numarası girin.');
        pageInput.focus();
    }
}

// Tüm değişkenler ve fonksiyonlar
let currentOperation = '';
let userCurrentCredits = 0;
let userCreditQuota = 0;
let userCreditUsed = 0;

function openCreditModal(operation, userId, userName, creditQuota, creditUsed, availableCredits) {
    console.log('Modal açılıyor:', operation, userId, userName, creditQuota, creditUsed, availableCredits);
    
    currentOperation = operation;
    userCurrentCredits = operation === 'add' ? parseFloat(availableCredits) : parseFloat(creditUsed);
    userCreditQuota = parseFloat(creditQuota);
    userCreditUsed = parseFloat(creditUsed);
    
    // Modal verilerini ayarla
    document.getElementById('user_id').value = userId;
    document.getElementById('selectedUserName').textContent = userName;
    document.getElementById('creditQuota').textContent = userCreditQuota.toFixed(2);
    document.getElementById('creditUsed').textContent = userCreditUsed.toFixed(2);
    document.getElementById('currentCredits').textContent = parseFloat(availableCredits).toFixed(2);
    
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    
    // Hidden input'ları sıfırla
    document.getElementById('add_credits').value = '';
    document.getElementById('deduct_credits').value = '';
    document.getElementById('reset_quota').value = '';
    document.getElementById('set_quota').value = '';
    
    if (operation === 'add') {
        modalTitle.textContent = 'Kredi Kotası Artır';
        submitBtn.textContent = 'Kota Artır';
        submitBtn.className = 'btn btn-success';
    } else if (operation === 'set') {
        modalTitle.textContent = 'Kredi Kotası Yenile';
        submitBtn.textContent = 'Kota Yenile';
        submitBtn.className = 'btn btn-warning';
    } else if (operation === 'reset') {
        modalTitle.textContent = 'Kredi Kotası Sıfırla';
        submitBtn.textContent = 'Kota Sıfırla';
        submitBtn.className = 'btn btn-info';
    } else {
        modalTitle.textContent = 'Kredi İadesi';
        submitBtn.textContent = 'Kredi İade Et';
        submitBtn.className = 'btn btn-danger';
    }
    
    // Formu temizle
    document.getElementById('amount').value = '';
    document.getElementById('description').value = '';
    
    // Önizlemeyi başlangıç durumuna getir
    updatePreview();
    
    // Modalı göster
    const modal = new bootstrap.Modal(document.getElementById('creditModal'));
    modal.show();
}

function updatePreview() {
    const amountField = document.getElementById('amount');
    const previewBox = document.getElementById('previewBox');
    const previewText = document.getElementById('previewText');
    
    // Element kontrolü
    if (!amountField || !previewBox || !previewText) {
        console.log('Gerekli elementler bulunamadı');
        return;
    }
    
    const amount = parseFloat(amountField.value) || 0;
    
    console.log('Önizleme güncelleniyor:', amount, currentOperation, userCurrentCredits);
    
    // Önizleme kutusunu her zaman göster
    previewBox.style.display = 'block';
    
    if (amount === 0) {
        previewText.innerHTML = '<small class="text-muted">Lütfen miktar girin...</small>';
        return;
    }
    
    let previewHtml;
    
    if (currentOperation === 'add') {
        // Kredi Kotası Artırma İşlemi
        const newQuota = userCreditQuota + amount;
        const newAvailableCredits = newQuota - userCreditUsed;
        
        previewHtml = 
            '<strong>Mevcut Durum:</strong><br>' +
            'Tanımlı Kota: <span class="text-primary">' + userCreditQuota.toFixed(2) + ' TL</span><br>' +
            'Mevcut Kullanım: <span class="text-warning">' + userCreditUsed.toFixed(2) + ' TL</span><br>' +
            'Kalan Kullanım: <span class="text-info">' + (userCreditQuota - userCreditUsed).toFixed(2) + ' TL</span><br><br>' +
            '<strong>İşlem:</strong> <span class="text-success">+' + amount.toFixed(2) + ' TL Kota Artırma</span><br><br>' +
            '<strong>İşlem Sonrası:</strong><br>' +
            'Yeni Tanımlı Kota: <span class="text-success">' + newQuota.toFixed(2) + ' TL</span><br>' +
            'Mevcut Kullanım: <span class="text-warning">' + userCreditUsed.toFixed(2) + ' TL</span><br>' +
            'Yeni Kalan Kullanım: <span class="text-success">' + newAvailableCredits.toFixed(2) + ' TL</span>';
            
    } else if (currentOperation === 'set') {
        // Kredi Kotası Yenileme İşlemi
        const newAvailableCredits = amount - userCreditUsed;
        
        previewHtml = 
            '<strong>Mevcut Durum:</strong><br>' +
            'Tanımlı Kota: <span class="text-primary">' + userCreditQuota.toFixed(2) + ' TL</span><br>' +
            'Mevcut Kullanım: <span class="text-warning">' + userCreditUsed.toFixed(2) + ' TL</span><br>' +
            'Kalan Kullanım: <span class="text-info">' + (userCreditQuota - userCreditUsed).toFixed(2) + ' TL</span><br><br>' +
            '<strong>İşlem:</strong> <span class="text-warning">Kota Yenileme: ' + amount.toFixed(2) + ' TL</span><br>' +
            '<em>Mevcut kullanım korunacak</em><br><br>' +
            '<strong>İşlem Sonrası:</strong><br>' +
            'Yeni Tanımlı Kota: <span class="text-warning">' + amount.toFixed(2) + ' TL</span><br>' +
            'Mevcut Kullanım: <span class="text-warning">' + userCreditUsed.toFixed(2) + ' TL</span><br>' +
            'Yeni Kalan Kullanım: <span class="' + (newAvailableCredits >= 0 ? 'text-success' : 'text-danger') + '">' + newAvailableCredits.toFixed(2) + ' TL</span>';
            
        if (newAvailableCredits < 0) {
            previewHtml += '<br><br><span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Uyarı: Yeni kota mevcut kullanımdan az!</span>';
        }
        
    } else if (currentOperation === 'reset') {
        // Kredi Kotası Sıfırlama İşlemi
        
        previewHtml = 
            '<strong>Mevcut Durum:</strong><br>' +
            'Tanımlı Kota: <span class="text-primary">' + userCreditQuota.toFixed(2) + ' TL</span><br>' +
            'Mevcut Kullanım: <span class="text-warning">' + userCreditUsed.toFixed(2) + ' TL</span><br>' +
            'Kalan Kullanım: <span class="text-info">' + (userCreditQuota - userCreditUsed).toFixed(2) + ' TL</span><br><br>' +
            '<strong>İşlem:</strong> <span class="text-info">Kota Sıfırlama: ' + amount.toFixed(2) + ' TL</span><br>' +
            '<em class="text-danger">Mevcut kullanım sıfırlanacak!</em><br><br>' +
            '<strong>İşlem Sonrası:</strong><br>' +
            'Yeni Tanımlı Kota: <span class="text-info">' + amount.toFixed(2) + ' TL</span><br>' +
            'Yeni Mevcut Kullanım: <span class="text-success">0.00 TL</span><br>' +
            'Yeni Kalan Kullanım: <span class="text-success">' + amount.toFixed(2) + ' TL</span>';
            
    } else {
        // Kredi İadesi İşlemi
        const newUsedCredits = Math.max(0, userCreditUsed - amount);
        const newAvailableCredits = userCreditQuota - newUsedCredits;
        
        previewHtml = 
            '<strong>Mevcut Durum:</strong><br>' +
            'Tanımlı Kota: <span class="text-primary">' + userCreditQuota.toFixed(2) + ' TL</span><br>' +
            'Mevcut Kullanım: <span class="text-warning">' + userCreditUsed.toFixed(2) + ' TL</span><br>' +
            'Kalan Kullanım: <span class="text-info">' + (userCreditQuota - userCreditUsed).toFixed(2) + ' TL</span><br><br>' +
            '<strong>İşlem:</strong> <span class="text-danger">-' + amount.toFixed(2) + ' TL Kredi İadesi</span><br><br>' +
            '<strong>İşlem Sonrası:</strong><br>' +
            'Tanımlı Kota: <span class="text-primary">' + userCreditQuota.toFixed(2) + ' TL</span><br>' +
            'Yeni Mevcut Kullanım: <span class="text-success">' + newUsedCredits.toFixed(2) + ' TL</span><br>' +
            'Yeni Kalan Kullanım: <span class="text-success">' + newAvailableCredits.toFixed(2) + ' TL</span>';
            
        if (amount > userCreditUsed) {
            previewHtml += '<br><br><span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Uyarı: İade miktarı mevcut kullanımdan fazla!</span>';
        }
    }
    
    previewText.innerHTML = previewHtml;
}

// Sayfa yüklendiğinde event listener'ları ekle
document.addEventListener('DOMContentLoaded', function() {
    // Amount field event'leri
    const amountField = document.getElementById('amount');
    if (amountField) {
        amountField.addEventListener('input', updatePreview);
        amountField.addEventListener('keyup', updatePreview);
        amountField.addEventListener('change', updatePreview);
    }
    
    // Form validation
    const creditForm = document.getElementById('creditForm');
    if (creditForm) {
        creditForm.addEventListener('submit', function(e) {
            console.log('Form submit event tetiklendi!');
            console.log('Current operation:', currentOperation);
            
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const description = document.getElementById('description').value.trim();
            const userId = document.getElementById('user_id').value;
            
            console.log('Form values:', { amount, description, userId, currentOperation });
            
            if (amount <= 0) {
                e.preventDefault();
                alert('Lütfen geçerli bir miktar girin!');
                return false;
            }
            
            if (!description) {
                e.preventDefault();
                alert('Lütfen açıklama yazın!');
                return false;
            }
            
            if (!userId) {
                e.preventDefault();
                alert('Kullanıcı ID eksik!');
                return false;
            }
            
            if (!currentOperation) {
                e.preventDefault();
                alert('Işlem tipi belirlenmemiş!');
                return false;
            }
            
            if (currentOperation === 'deduct' && amount > userCurrentCredits) {
                e.preventDefault();
                alert('Yetersiz kullanılan kredi! Mevcut: ' + userCurrentCredits.toFixed(2) + ' TL');
                return false;
            }
            
            // Onay mesajı
            const username = document.getElementById('selectedUserName').textContent;
            let operationTitle, newBalance, additionalInfo = '';
            
            if (currentOperation === 'add') {
                operationTitle = 'Kredi Kotası Artırma';
                newBalance = userCurrentCredits + amount;
            } else if (currentOperation === 'set') {
                operationTitle = 'Kredi Kotası Yenileme';
                newBalance = amount - userCreditUsed;
                additionalInfo = '\nMevcut kullanım korunacak: ' + userCreditUsed.toFixed(2) + ' TL';
            } else if (currentOperation === 'reset') {
                operationTitle = 'Kredi Kotası Sıfırlama';
                newBalance = amount;
                additionalInfo = '\nMevcut kullanım sıfırlanacak!';
            } else {
                operationTitle = 'Kredi İadesi';
                newBalance = userCurrentCredits - amount;
            }
            
            const confirmMessage = 
                username + ' kullanıcısı için kredi işlemi:\n\n' +
                'İşlem: ' + operationTitle + '\n' +
                'Miktar: ' + amount.toFixed(2) + ' TL' + additionalInfo + '\n' +
                'Mevcut Durum: ' + userCurrentCredits.toFixed(2) + ' TL\n' +
                'Yeni Durum: ' + newBalance.toFixed(2) + ' TL\n\n' +
                'İşlemi onaylıyor musunuz?';
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                console.log('Kullanıcı işlemi iptal etti.');
                return false;
            }
            
            // Doğru hidden input'u ayarla
            const addCreditsInput = document.getElementById('add_credits');
            const deductCreditsInput = document.getElementById('deduct_credits');
            const resetQuotaInput = document.getElementById('reset_quota');
            const setQuotaInput = document.getElementById('set_quota');
            
            // Tüm input'ları temizle
            addCreditsInput.value = '';
            deductCreditsInput.value = '';
            resetQuotaInput.value = '';
            setQuotaInput.value = '';
            
            if (currentOperation === 'add') {
                addCreditsInput.value = '1';
                console.log('Add credits input set to: 1');
            } else if (currentOperation === 'set') {
                setQuotaInput.value = '1';
                console.log('Set quota input set to: 1');
            } else if (currentOperation === 'reset') {
                resetQuotaInput.value = '1';
                console.log('Reset quota input set to: 1');
            } else {
                deductCreditsInput.value = '1';
                console.log('Deduct credits input set to: 1');
            }
            
            console.log('Form submit ediliyor. İşlem:', currentOperation);
            console.log('Add credits value:', addCreditsInput.value);
            console.log('Deduct credits value:', deductCreditsInput.value);
            console.log('Form data before submit:');
            
            // FormData'yı log'la
            const formData = new FormData(this);
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Form submit'e izin ver
            return true;
        });
    }
    
    // Modal events
    const creditModal = document.getElementById('creditModal');
    if (creditModal) {
        creditModal.addEventListener('hidden.bs.modal', function () {
            console.log('Modal kapandı, temizleniyor');
            document.getElementById('creditForm').reset();
            document.getElementById('add_credits').value = '';
            document.getElementById('deduct_credits').value = '';
            document.getElementById('reset_quota').value = '';
            document.getElementById('set_quota').value = '';
            document.getElementById('previewText').innerHTML = 'Lütfen miktar girin...';
            currentOperation = ''; // Operation'u da sıfırla
        });
    }
});
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
