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

// Kredi ekleme/çıkarma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG: Form verisini log'la
    error_log('Credits.php POST verisi: ' . print_r($_POST, true));
    error_log('Session verisi: ' . print_r($_SESSION, true));
    error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    error_log('Content-Length: ' . ($_SERVER['CONTENT_LENGTH'] ?? 'not set'));
    
    // POST verilerini kontrol et
    $hasAddCredits = isset($_POST['add_credits']) && !empty($_POST['add_credits']);
    $hasDeductCredits = isset($_POST['deduct_credits']) && !empty($_POST['deduct_credits']);
    
    error_log('Has add_credits: ' . ($hasAddCredits ? 'true' : 'false'));
    error_log('Has deduct_credits: ' . ($hasDeductCredits ? 'true' : 'false'));
    
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
                
                // Kredi ekleme
                $newCredits = $currentUser['credits'] + $amount;
                
                $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
                $stmt->execute([$newCredits, $user_id]);
                
                // Credit transactions tablosuna kaydet
                $transactionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, admin_id, type, amount, description, created_at) 
                    VALUES (?, ?, ?, 'deposit', ?, ?, NOW())
                ");
                $stmt->execute([$transactionId, $user_id, $_SESSION['user_id'], $amount, $description]);
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'credit_added', "Kullanıcıya {$amount} TL kredi eklendi: {$description}");
                
                $pdo->commit();
                $success = "{$amount} TL kredi başarıyla eklendi. Yeni bakiye: " . number_format($newCredits, 2) . " TL";
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
                
                if ($currentUser['credits'] < $amount) {
                    throw new Exception('Kullanıcının yeterli kredisi yok.');
                }
                
                // Kredi düşme
                $newCredits = $currentUser['credits'] - $amount;
                
                $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
                $stmt->execute([$newCredits, $user_id]);
                
                // Credit transactions tablosuna kaydet
                $transactionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, admin_id, type, amount, description, created_at) 
                    VALUES (?, ?, ?, 'withdraw', ?, ?, NOW())
                ");
                $stmt->execute([$transactionId, $user_id, $_SESSION['user_id'], $amount, $description]);
                
                // Log kaydı
                $user->logAction($_SESSION['user_id'], 'credit_deducted', "Kullanıcıdan {$amount} TL kredi düşüldü: {$description}");
                
                $pdo->commit();
                $success = "{$amount} TL kredi başarıyla düşüldü. Kalan bakiye: " . number_format($newCredits, 2) . " TL";
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
    
    // Hiçbir işlem bulunamadıysa
    if (!$hasAddCredits && !$hasDeductCredits) {
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
$limit = 20;
$offset = ($page - 1) * $limit;

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
    
    // Kullanıcıları getir - LIMIT ve OFFSET'i direkt sorguya ekle
    $query = "
        SELECT id, username, email, first_name, last_name, credits, created_at, last_login
        FROM users 
        $whereClause 
        ORDER BY credits DESC, username ASC 
        LIMIT $limit OFFSET $offset
    ";
    
    if (!empty($params)) {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($query);
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPages = ceil($totalUsers / $limit);
} catch (PDOException $e) {
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
    $error = 'Kullanıcılar yüklenirken hata oluştu: ' . $e->getMessage();
    error_log('Credits.php user loading error: ' . $e->getMessage());
}

// Kredi istatistikleri
try {
    $stmt = $pdo->query("
        SELECT 
            SUM(credits) as total_credits,
            AVG(credits) as avg_credits,
            MAX(credits) as max_credits,
            COUNT(*) as user_count,
            SUM(CASE WHEN credits > 0 THEN 1 ELSE 0 END) as users_with_credits
        FROM users 
        WHERE role = 'user'
    ");
    $creditStats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $creditStats = [
        'total_credits' => 0,
        'avg_credits' => 0,
        'max_credits' => 0,
        'user_count' => 0,
        'users_with_credits' => 0
    ];
}

$pageTitle = 'Kredi Yönetimi';
$pageDescription = 'Kullanıcı kredilerini yönetin ve kontrol edin';
$pageIcon = 'fas fa-coins';

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

<!-- Kredi İstatistikleri -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-success"><?php echo number_format($creditStats['total_credits'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">Toplam Kredi</div>
                    <small class="text-muted"><?php echo number_format($creditStats['user_count'] ?? 0); ?> kullanıcı</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <i class="fas fa-coins text-success fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-primary"><?php echo number_format($creditStats['avg_credits'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">Ortalama Kredi</div>
                    <small class="text-muted">Kullanıcı başı</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                    <i class="fas fa-chart-line text-primary fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-warning"><?php echo number_format($creditStats['max_credits'] ?? 0, 2); ?> TL</div>
                    <div class="stat-label">En Yüksek Kredi</div>
                    <small class="text-muted">Maksimum bakiye</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <i class="fas fa-crown text-warning fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-widget">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number text-info"><?php echo number_format($creditStats['users_with_credits'] ?? 0); ?></div>
                    <div class="stat-label">Kredili Kullanıcı</div>
                    <small class="text-muted">Bakiyesi olan</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                    <i class="fas fa-users text-info fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Arama -->
<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label for="search" class="form-label">Kullanıcı Ara</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Kullanıcı adı, e-posta, ad veya soyad...">
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Ara
                </button>
            </div>
            
            <div class="col-md-2">
                <a href="credits.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-undo me-1"></i>Temizle
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Kullanıcı Listesi -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>Kullanıcılar (<?php echo number_format($totalUsers); ?> adet)
        </h5>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
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
                            <th>Mevcut Kredi</th>
                            <th>Kayıt Tarihi</th>
                            <th>Son Giriş</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userData): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong>
                                            <a href="user-details.php?id=<?php echo $userData['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                                            </a>
                                        </strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($userData['username']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-envelope me-1"></i>
                                        <small><?php echo htmlspecialchars($userData['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $userData['credits'] > 0 ? 'success' : 'secondary'; ?> fs-6">
                                        <?php echo number_format($userData['credits'], 2); ?> TL
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('d.m.Y', strtotime($userData['created_at'])); ?></small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo $userData['last_login'] ? date('d.m.Y H:i', strtotime($userData['last_login'])) : 'Hiç giriş yapmamış'; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-success" 
                                                onclick="openCreditModal('add', '<?php echo $userData['id']; ?>', '<?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>', <?php echo $userData['credits']; ?>)">
                                            <i class="fas fa-plus me-1"></i>Ekle
                                        </button>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="openCreditModal('deduct', '<?php echo $userData['id']; ?>', '<?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>', <?php echo $userData['credits']; ?>)">
                                            <i class="fas fa-minus me-1"></i>Düş
                                        </button>
                                        <a href="user-details.php?id=<?php echo $userData['id']; ?>" class="btn btn-outline-primary">
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
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Sayfalama">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
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
                    
                    <div class="alert alert-info alert-permanent">
                        <strong>Kullanıcı:</strong> <span id="selectedUserName"></span><br>
                        <strong>Mevcut Kredi:</strong> <span id="currentCredits"></span> TL
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
// Tüm değişkenler ve fonksiyonlar
let currentOperation = '';
let userCurrentCredits = 0;

function openCreditModal(operation, userId, userName, credits) {
    console.log('Modal açılıyor:', operation, userId, userName, credits);
    
    currentOperation = operation;
    userCurrentCredits = parseFloat(credits);
    
    // Modal verilerini ayarla
    document.getElementById('user_id').value = userId;
    document.getElementById('selectedUserName').textContent = userName;
    document.getElementById('currentCredits').textContent = parseFloat(credits).toFixed(2);
    
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    
    // Hidden input'ları sıfırla
    document.getElementById('add_credits').value = '';
    document.getElementById('deduct_credits').value = '';
    
    if (operation === 'add') {
        modalTitle.textContent = 'Kredi Ekle';
        submitBtn.textContent = 'Kredi Ekle';
        submitBtn.className = 'btn btn-success';
    } else {
        modalTitle.textContent = 'Kredi Düş';
        submitBtn.textContent = 'Kredi Düş';
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
    
    let newBalance;
    let operation;
    let color;
    
    if (currentOperation === 'add') {
        newBalance = userCurrentCredits + amount;
        operation = '+';
        color = 'text-success';
    } else {
        newBalance = userCurrentCredits - amount;
        operation = '-';
        color = newBalance >= 0 ? 'text-danger' : 'text-warning';
    }
    
    previewText.innerHTML = 
        'Mevcut: <strong>' + userCurrentCredits.toFixed(2) + ' TL</strong><br>' +
        'İşlem: <strong class="' + color + '">' + operation + amount.toFixed(2) + ' TL</strong><br>' +
        'Yeni Bakiye: <strong class="' + (newBalance >= 0 ? 'text-primary' : 'text-danger') + '">' + 
        newBalance.toFixed(2) + ' TL</strong>' +
        (newBalance < 0 ? '<br><span class="text-danger">⚠️ Bakiye negatif olacak!</span>' : '');
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
                alert('Yetersiz kredi! Mevcut: ' + userCurrentCredits.toFixed(2) + ' TL');
                return false;
            }
            
            // Onay mesajı
            const username = document.getElementById('selectedUserName').textContent;
            const newBalance = currentOperation === 'add' ? 
                userCurrentCredits + amount : userCurrentCredits - amount;
            
            const confirmMessage = 
                username + ' kullanıcısı için kredi işlemi:\n\n' +
                'İşlem: ' + (currentOperation === 'add' ? 'Kredi Ekleme' : 'Kredi Düşme') + '\n' +
                'Miktar: ' + amount.toFixed(2) + ' TL\n' +
                'Mevcut Bakiye: ' + userCurrentCredits.toFixed(2) + ' TL\n' +
                'Yeni Bakiye: ' + newBalance.toFixed(2) + ' TL\n\n' +
                'İşlemi onaylıyor musunuz?';
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                console.log('Kullanıcı işlemi iptal etti.');
                return false;
            }
            
            // Doğru hidden input'u ayarla
            const addCreditsInput = document.getElementById('add_credits');
            const deductCreditsInput = document.getElementById('deduct_credits');
            
            // Önce her ikisini de temizle
            addCreditsInput.value = '';
            deductCreditsInput.value = '';
            
            if (currentOperation === 'add') {
                addCreditsInput.value = '1';
                console.log('Add credits input set to: 1');
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
